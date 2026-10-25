<?php
declare(strict_types=1);

require_once __DIR__ . '/conversations.php';
require_once __DIR__ . '/contact_activity.php';
require_once __DIR__ . '/contact_tags.php';
require_once __DIR__ . '/contact_custom_fields.php';
require_once __DIR__ . '/compliance/SuppressionService.php';

final class CrmContactService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $sql = 'SELECT c.* FROM crm_contacts c WHERE c.tenant_id = ?';
        $params = [$this->tenantId];

        if (empty($filters['include_archived'])) {
            $sql .= ' AND c.archived_at IS NULL';
        }
        if (!empty($filters['archived_only'])) {
            $sql .= ' AND c.archived_at IS NOT NULL';
        }
        if (isset($filters['owner_admin_id']) && $filters['owner_admin_id'] !== '') {
            $sql .= ' AND c.owner_admin_id = ?';
            $params[] = (int) $filters['owner_admin_id'];
        }
        if (!empty($filters['source'])) {
            $sql .= ' AND c.source = ?';
            $params[] = substr((string) $filters['source'], 0, 64);
        }
        if (!empty($filters['consent_status'])) {
            $sql .= ' AND c.consent_status = ?';
            $params[] = (string) $filters['consent_status'];
        }
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .= ' AND (c.wa_id LIKE ? OR c.display_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($filters['tag_id'])) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM crm_contact_tag_assignments a
                WHERE a.contact_id = c.id AND a.tag_id = ?
            )';
            $params[] = (int) $filters['tag_id'];
        }

        $sql .= ' ORDER BY c.updated_at DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['tags'] = crm_contact_tags($this->pdo, $this->tenantId, (int) $row['id']);
        }

        return $rows;
    }

    private function exists(int $contactId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM crm_contacts WHERE tenant_id = ? AND id = ? LIMIT 1');
        $stmt->execute([$this->tenantId, $contactId]);

        return (bool) $stmt->fetchColumn();
    }

    public function get(int $contactId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM crm_contacts WHERE tenant_id = ? AND id = ? LIMIT 1'
        );
        $stmt->execute([$this->tenantId, $contactId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['tags'] = crm_contact_tags($this->pdo, $this->tenantId, $contactId);
        $row['custom_fields'] = crm_contact_custom_values($this->pdo, $this->tenantId, $contactId);
        $row['activity'] = crm_contact_list_activity($this->pdo, $this->tenantId, $contactId, 30);

        return $row;
    }

    public function create(string $waId, ?string $displayName = null, ?string $source = 'manual', ?int $ownerAdminId = null): ?int
    {
        $id = crm_touch_contact($this->pdo, $this->tenantId, $waId, $displayName);
        if ($id === null) {
            return null;
        }
        $this->pdo->prepare(
            'UPDATE crm_contacts SET source = COALESCE(NULLIF(?, \'\'), source),
             owner_admin_id = COALESCE(?, owner_admin_id) WHERE id = ? AND tenant_id = ?'
        )->execute([
            substr((string) $source, 0, 64),
            $ownerAdminId,
            $id,
            $this->tenantId,
        ]);
        crm_contact_log_activity($this->pdo, $this->tenantId, $id, 'created', 'Contact created', ['source' => $source]);

        return $id;
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function update(int $contactId, array $fields): bool
    {
        if ($this->exists($contactId) === false) {
            return false;
        }
        $sets = [];
        $params = [];
        foreach (['display_name', 'email', 'phone', 'source', 'consent_status'] as $key) {
            if (!array_key_exists($key, $fields)) {
                continue;
            }
            if ($key === 'consent_status') {
                $v = (string) $fields[$key];
                if (!in_array($v, ['unknown', 'opted_in', 'opted_out'], true)) {
                    continue;
                }
            }
            $sets[] = "{$key} = ?";
            $params[] = is_string($fields[$key]) ? substr($fields[$key], 0, 160) : $fields[$key];
        }
        if (array_key_exists('owner_admin_id', $fields)) {
            $sets[] = 'owner_admin_id = ?';
            $params[] = (int) $fields['owner_admin_id'] > 0 ? (int) $fields['owner_admin_id'] : null;
        }
        if ($sets === []) {
            if (isset($fields['custom_fields']) && is_array($fields['custom_fields'])) {
                crm_save_contact_custom_values($this->pdo, $this->tenantId, $contactId, $fields['custom_fields']);
                crm_contact_log_activity($this->pdo, $this->tenantId, $contactId, 'updated', 'Custom fields updated');

                return true;
            }

            return false;
        }
        $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        $params[] = $this->tenantId;
        $params[] = $contactId;
        $this->pdo->prepare(
            'UPDATE crm_contacts SET ' . implode(', ', $sets) . ' WHERE tenant_id = ? AND id = ?'
        )->execute($params);
        if (isset($fields['custom_fields']) && is_array($fields['custom_fields'])) {
            crm_save_contact_custom_values($this->pdo, $this->tenantId, $contactId, $fields['custom_fields']);
        }
        crm_contact_log_activity($this->pdo, $this->tenantId, $contactId, 'updated', 'Contact profile updated');

        return true;
    }

    public function archive(int $contactId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE crm_contacts SET archived_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE tenant_id = ? AND id = ? AND archived_at IS NULL'
        );
        $stmt->execute([$this->tenantId, $contactId]);
        if ($stmt->rowCount() > 0) {
            crm_contact_log_activity($this->pdo, $this->tenantId, $contactId, 'archived', 'Contact archived');

            return true;
        }

        return false;
    }

    public function unarchive(int $contactId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE crm_contacts SET archived_at = NULL, updated_at = UTC_TIMESTAMP()
             WHERE tenant_id = ? AND id = ? AND archived_at IS NOT NULL'
        );
        $stmt->execute([$this->tenantId, $contactId]);

        return $stmt->rowCount() > 0;
    }

    public function addNote(int $contactId, int $adminId, string $body): int
    {
        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('Note body is required.');
        }
        $this->pdo->prepare(
            'INSERT INTO crm_contact_notes (tenant_id, contact_id, admin_id, body) VALUES (?,?,?,?)'
        )->execute([$this->tenantId, $contactId, $adminId, $body]);
        $id = (int) $this->pdo->lastInsertId();
        crm_contact_log_activity($this->pdo, $this->tenantId, $contactId, 'note_added', substr($body, 0, 255), null, $adminId);

        return $id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listNotes(int $contactId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT n.*, a.username AS admin_username FROM crm_contact_notes n
                 LEFT JOIN admins a ON a.id = n.admin_id
                 WHERE n.tenant_id = ? AND n.contact_id = ? ORDER BY n.id DESC LIMIT 100'
            );
            $stmt->execute([$this->tenantId, $contactId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function conversationMessages(int $contactId, int $limit = 50): array
    {
        $contact = $this->get($contactId);
        if ($contact === null || empty($contact['wa_id'])) {
            return [];
        }
        require_once __DIR__ . '/messages.php';
        $convId = crm_conversation_id_for_peer($this->pdo, $this->tenantId, (string) $contact['wa_id']);
        if ($convId === null) {
            return [];
        }

        return crm_list_messages($this->pdo, $this->tenantId, $convId, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findDuplicates(): array
    {
        $groups = [];
        $stmt = $this->pdo->prepare(
            'SELECT id, wa_id, email, phone, display_name FROM crm_contacts
             WHERE tenant_id = ? AND archived_at IS NULL ORDER BY wa_id ASC'
        );
        $stmt->execute([$this->tenantId]);
        $byPhone = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $phone = preg_replace('/\D+/', '', (string) ($row['phone'] ?? ''));
            if ($phone !== '') {
                $byPhone[$phone][] = $row;
            }
        }
        foreach ($byPhone as $phone => $items) {
            if (count($items) > 1) {
                $groups[] = ['match' => 'phone', 'key' => $phone, 'contacts' => $items];
            }
        }

        return $groups;
    }

    public function merge(int $sourceId, int $targetId, ?int $adminId = null): bool
    {
        if ($sourceId === $targetId) {
            return false;
        }
        $source = $this->get($sourceId);
        $target = $this->get($targetId);
        if ($source === null || $target === null) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $snapshot = json_encode(['source' => $source, 'target_id' => $targetId], JSON_UNESCAPED_UNICODE);
            $this->pdo->prepare(
                'INSERT INTO crm_contact_merge_log (tenant_id, source_contact_id, target_contact_id, admin_id, snapshot_json)
                 VALUES (?,?,?,?,?)'
            )->execute([$this->tenantId, $sourceId, $targetId, $adminId, $snapshot]);

            if (!empty($source['wa_id'])) {
                $this->pdo->prepare(
                    'UPDATE crm_conversations SET contact_id = ?, peer_wa_id = ?
                     WHERE tenant_id = ? AND contact_id = ?'
                )->execute([$targetId, $target['wa_id'] ?? $source['wa_id'], $this->tenantId, $sourceId]);
            }

            $this->pdo->prepare(
                'INSERT IGNORE INTO crm_contact_tag_assignments (contact_id, tag_id, assigned_by_admin_id)
                 SELECT ?, tag_id, assigned_by_admin_id FROM crm_contact_tag_assignments WHERE contact_id = ?'
            )->execute([$targetId, $sourceId]);
            $this->pdo->prepare('DELETE FROM crm_contact_tag_assignments WHERE contact_id = ?')->execute([$sourceId]);

            $this->pdo->prepare('UPDATE crm_contact_activity SET contact_id = ? WHERE contact_id = ? AND tenant_id = ?')
                ->execute([$targetId, $sourceId, $this->tenantId]);
            $this->pdo->prepare('UPDATE crm_contact_notes SET contact_id = ? WHERE contact_id = ? AND tenant_id = ?')
                ->execute([$targetId, $sourceId, $this->tenantId]);

            $this->pdo->prepare(
                'UPDATE crm_contacts SET archived_at = UTC_TIMESTAMP(), merged_into_contact_id = ?, updated_at = UTC_TIMESTAMP()
                 WHERE id = ? AND tenant_id = ?'
            )->execute([$targetId, $sourceId, $this->tenantId]);

            crm_contact_log_activity(
                $this->pdo,
                $this->tenantId,
                $targetId,
                'merged',
                'Merged contact #' . $sourceId,
                ['source_contact_id' => $sourceId],
                $adminId
            );

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('Contact merge failed: ' . $e->getMessage());

            return false;
        }
    }

    public function recordOptOut(int $contactId, string $source = 'admin'): void
    {
        $contact = $this->get($contactId);
        if ($contact === null || empty($contact['wa_id'])) {
            return;
        }
        $this->update($contactId, ['consent_status' => 'opted_out']);
        $suppress = new CrmSuppressionService($this->pdo, $this->tenantId);
        $suppress->suppress((string) $contact['wa_id'], 'opt_out', $source);
        crm_contact_log_activity($this->pdo, $this->tenantId, $contactId, 'opt_out', 'Contact opted out');
    }

    public function recordConsent(int $contactId, string $purpose, string $source, ?string $evidence = null): void
    {
        $this->update($contactId, ['consent_status' => 'opted_in']);
        try {
            $this->pdo->prepare(
                'INSERT INTO crm_contact_consents (tenant_id, contact_id, purpose, source, evidence)
                 VALUES (?,?,?,?,?)'
            )->execute([
                $this->tenantId,
                $contactId,
                substr($purpose, 0, 64),
                substr($source, 0, 64),
                $evidence !== null ? substr($evidence, 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            // ignore
        }
        crm_contact_log_activity($this->pdo, $this->tenantId, $contactId, 'consent', 'Consent recorded: ' . $purpose);
    }
}
