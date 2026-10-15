<?php
declare(strict_types=1);

final class ChatbotBrochureAssetService
{
    private const ASSET_KEY = 'company_brochure';

    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    public function filename(): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT filename FROM chatbot_assets WHERE tenant_id = ? AND asset_key = ? LIMIT 1'
        );
        $stmt->execute([$this->tenantId, self::ASSET_KEY]);
        $name = $stmt->fetchColumn();

        return is_string($name) && $name !== '' ? $name : 'digital-creatorss-brochure.pdf';
    }

    public function resolveMediaId(): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT meta_media_id FROM chatbot_assets WHERE tenant_id = ? AND asset_key = ? LIMIT 1'
        );
        $stmt->execute([$this->tenantId, self::ASSET_KEY]);
        $id = $stmt->fetchColumn();

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function saveMediaId(string $mediaId, ?string $filename = null): void
    {
        $this->pdo->prepare(
            'INSERT INTO chatbot_assets (tenant_id, asset_key, filename, meta_media_id)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE meta_media_id = VALUES(meta_media_id),
               filename = COALESCE(VALUES(filename), filename),
               updated_at = CURRENT_TIMESTAMP'
        )->execute([$this->tenantId, self::ASSET_KEY, $filename, $mediaId]);
    }

    /**
     * Upload PDF from local path configured in env or default uploads folder.
     */
    public function refreshUpload(): ?string
    {
        $path = $this->localPath();
        if ($path === null || !is_readable($path)) {
            return null;
        }
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $result = whatsapp_upload_media_from_path($path, 'application/pdf');
        if (!$result['ok'] || empty($result['media_id'])) {
            return null;
        }
        $mediaId = (string) $result['media_id'];
        $this->saveMediaId($mediaId, basename($path));

        return $mediaId;
    }

    private function localPath(): ?string
    {
        $env = getenv('CHATBOT_BROCHURE_PATH') ?: '';
        if ($env !== '' && is_readable($env)) {
            return $env;
        }
        $config = require dirname(__DIR__) . '/config.php';
        $uploadDir = (string) ($config['app']['upload_dir'] ?? '');
        $candidates = [
            $uploadDir . '/brochures/' . $this->filename(),
            dirname(__DIR__, 2) . '/assets/uploads/brochures/' . $this->filename(),
        ];
        foreach ($candidates as $p) {
            if (is_readable($p)) {
                return $p;
            }
        }

        return null;
    }
}
