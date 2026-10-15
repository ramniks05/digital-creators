<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

final class RazorpayGateway
{
    private string $keyId;
    private string $keySecret;

    public function __construct()
    {
        $cfg = saas_config();
        $this->keyId = $cfg['razorpay_key_id'];
        $this->keySecret = $cfg['razorpay_key_secret'];
    }

    public function isConfigured(): bool
    {
        return $this->keyId !== '' && $this->keySecret !== '';
    }

    public function publicKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok:bool, data?:array, error?:string, http_code?:int}
     */
    public function apiPost(string $path, array $body): array
    {
        return $this->request('POST', $path, $body);
    }

    /**
     * @return array{ok:bool, data?:array, error?:string, http_code?:int}
     */
    public function apiGet(string $path): array
    {
        return $this->request('GET', $path, null);
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{ok:bool, data?:array, error?:string, http_code?:int}
     */
    private function request(string $method, string $path, ?array $body): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Razorpay keys are not configured.'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL is required for Razorpay API.'];
        }

        $url = 'https://api.razorpay.com/v1' . $path;
        $ch = curl_init($url);
        $auth = base64_encode($this->keyId . ':' . $this->keySecret);
        $headers = [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($method === 'POST' && $body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            $json = json_encode($body, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                return ['ok' => false, 'error' => 'Invalid request body.'];
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($raw === false) {
            return ['ok' => false, 'error' => curl_error($ch), 'http_code' => $httpCode];
        }
        $data = json_decode($raw, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = is_array($data) ? (string) ($data['error']['description'] ?? $data['error'] ?? 'Razorpay API error') : $raw;

            return ['ok' => false, 'error' => $msg, 'http_code' => $httpCode, 'data' => is_array($data) ? $data : null];
        }

        return ['ok' => true, 'data' => is_array($data) ? $data : [], 'http_code' => $httpCode];
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $payload = $orderId . '|' . $paymentId;
        $expected = hash_hmac('sha256', $payload, $this->keySecret);

        return hash_equals($expected, $signature);
    }

    public function verifySubscriptionSignature(string $paymentId, string $subscriptionId, string $signature): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $payload = $paymentId . '|' . $subscriptionId;
        $expected = hash_hmac('sha256', $payload, $this->keySecret);

        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = saas_config()['razorpay_webhook_secret'];
        if ($secret === '' || $signatureHeader === null || $signatureHeader === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
