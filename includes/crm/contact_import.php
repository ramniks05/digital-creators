<?php
declare(strict_types=1);

require_once __DIR__ . '/conversations.php';
require_once __DIR__ . '/contact_activity.php';
require_once __DIR__ . '/contact_tags.php';
require_once __DIR__ . '/compliance/SuppressionService.php';

/**
 * Import contacts from CSV. Does NOT set marketing consent unless explicit column with evidence.
 *
 * @return array{ok:bool, batch_id?:string, imported?:int, skipped?:int, errors?:list<string>}
 */
function crm_import_contacts_csv(PDO $pdo, int $tenantId, string $csvContent, ?int $adminId = null): array
{
    $batchId = 'imp_' . bin2hex(random_bytes(8));
    $lines = preg_split('/\r\n|\n|\r/', trim($csvContent)) ?: [];
    if ($lines === []) {
        return ['ok' => false, 'errors' => ['Empty CSV']];
    }
    $header = str_getcsv(array_shift($lines) ?: '');
    $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);
    $required = ['wa_id'];
    foreach ($required as $col) {
        if (!in_array($col, $header, true)) {
            return ['ok' => false, 'errors' => ["Missing column: {$col}"]];
        }
    }

    $imported = 0;
    $skipped = 0;
    $errors = [];
    $idx = array_flip($header);

    $pdo->beginTransaction();
    try {
        foreach ($lines as $lineNo => $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line);
            $waRaw = trim((string) ($cols[$idx['wa_id']] ?? ''));
            if ($waRaw === '') {
                $skipped++;
                continue;
            }
            require_once dirname(__DIR__) . '/whatsapp_send.php';
            $waId = whatsapp_normalize_recipient($waRaw);
            if ($waId === '') {
                $errors[] = 'Line ' . ($lineNo + 2) . ': invalid wa_id';
                $skipped++;
                continue;
            }

            $name = isset($idx['display_name']) ? trim((string) ($cols[$idx['display_name']] ?? '')) : '';
            $email = isset($idx['email']) ? trim((string) ($cols[$idx['email']] ?? '')) : '';
            $source = isset($idx['source']) ? trim((string) ($cols[$idx['source']] ?? '')) : 'import';
            if ($source === '') {
                $source = 'import';
            }

            $existing = $pdo->prepare('SELECT id FROM crm_contacts WHERE tenant_id = ? AND wa_id = ? LIMIT 1');
            $existing->execute([$tenantId, $waId]);
            $existingId = $existing->fetchColumn();

            if ($existingId !== false) {
                $skipped++;
                continue;
            }

            $pdo->prepare(
                'INSERT INTO crm_contacts
                 (tenant_id, wa_id, display_name, email, phone, source, consent_status, import_batch_id)
                 VALUES (?,?,?,?,?,?,\'unknown\',?)'
            )->execute([
                $tenantId,
                $waId,
                $name !== '' ? substr($name, 0, 160) : null,
                $email !== '' ? substr($email, 0, 160) : null,
                $waId,
                substr($source, 0, 64),
                $batchId,
            ]);
            $contactId = (int) $pdo->lastInsertId();

            if (isset($idx['tags'])) {
                $tagList = array_filter(array_map('trim', explode('|', (string) ($cols[$idx['tags']] ?? ''))));
                foreach ($tagList as $tagLabel) {
                    if ($tagLabel === '') {
                        continue;
                    }
                    $tagId = crm_ensure_tag($pdo, $tenantId, $tagLabel);
                    crm_contact_assign_tag($pdo, $tenantId, $contactId, $tagId, $adminId);
                }
            }

            $marketingConsent = isset($idx['marketing_consent'])
                ? strtolower(trim((string) ($cols[$idx['marketing_consent']] ?? '')))
                : '';
            $evidence = isset($idx['consent_evidence']) ? trim((string) ($cols[$idx['consent_evidence']] ?? '')) : '';
            if ($marketingConsent === 'yes' && $evidence !== '') {
                $pdo->prepare('UPDATE crm_contacts SET consent_status = \'opted_in\' WHERE id = ?')->execute([$contactId]);
                try {
                    $pdo->prepare(
                        'INSERT INTO crm_contact_consents (tenant_id, contact_id, purpose, source, evidence)
                         VALUES (?,?,?,?,?)'
                    )->execute([$tenantId, $contactId, 'marketing', 'import', substr($evidence, 0, 255)]);
                } catch (Throwable $e) {
                    // table may not exist yet
                }
            }

            crm_contact_log_activity(
                $pdo,
                $tenantId,
                $contactId,
                'imported',
                'Contact imported',
                ['batch_id' => $batchId, 'source' => $source],
                $adminId
            );
            $imported++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['ok' => false, 'errors' => [$e->getMessage()]];
    }

    return ['ok' => true, 'batch_id' => $batchId, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
}

/**
 * @return array{ok:bool, csv?:string}
 */
function crm_export_contacts_csv(PDO $pdo, int $tenantId, bool $includeArchived = false): array
{
    $sql = 'SELECT wa_id, display_name, email, phone, source, consent_status, created_at, archived_at
            FROM crm_contacts WHERE tenant_id = ?';
    if (!$includeArchived) {
        $sql .= ' AND archived_at IS NULL';
    }
    $sql .= ' ORDER BY id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tenantId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = fopen('php://temp', 'r+');
    if ($out === false) {
        return ['ok' => false];
    }
    fputcsv($out, ['wa_id', 'display_name', 'email', 'phone', 'source', 'consent_status', 'created_at']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['wa_id'],
            $row['display_name'],
            $row['email'],
            $row['phone'],
            $row['source'] ?? '',
            $row['consent_status'],
            $row['created_at'],
        ]);
    }
    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);

    return ['ok' => true, 'csv' => $csv !== false ? $csv : ''];
}
