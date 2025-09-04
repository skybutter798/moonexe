<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrxService
{
    protected $apiUrl;
    protected $key;
    protected $method;

    public function __construct()
    {
        $this->apiUrl = rtrim(env('TRX_API_URL'), '/') . '/';
        $secretKey = env('TRX_SECRET_KEY');
        $this->method = 'AES-256-CBC';
        $this->key = ctype_print($secretKey) ? openssl_digest($secretKey, 'SHA256', true) : $secretKey;

        if (!in_array(strtolower($this->method), openssl_get_cipher_methods())) {
            throw new \Exception("Unrecognized cipher method: {$this->method}");
        }
    }

    public function createAccount(string $accountName): array
    {
        $service = 'api/createWallet';
        $data = ['accountName' => $accountName];
        $encrypted = $this->encrypt(json_encode($data));
    
        Log::channel('admin')->info('[TrxService] Encrypted payload', [
            'accountName' => $accountName,
            'encrypted' => $encrypted,
            'url' => $this->apiUrl . $service
        ]);
    
        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post($this->apiUrl . $service, ['data' => $encrypted]);
    
            Log::channel('admin')->info('[TrxService] API Raw Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
    
            if (!$response->ok()) {
                return ['status' => 'error', 'message' => 'API connection failed'];
            }
    
            $decoded = $response->json();
            Log::channel('admin')->info('[TrxService] API JSON Decoded', ['json' => $decoded]);
    
            if (filter_var($decoded['status'], FILTER_VALIDATE_BOOLEAN)) {
                $decryptedRaw = $this->decrypt($decoded['data']);
                Log::channel('admin')->info('[TrxService] Decrypted payload', ['decrypted' => $decryptedRaw]);
    
                $decrypted = json_decode($decryptedRaw, true);
    
                if ($decrypted && $decrypted['status'] === 'success') {
                    return ['status' => 'success', 'message' => $decrypted['data']['address']];
                } else {
                    return ['status' => 'error', 'message' => $decrypted['msg'] ?? 'Decryption error'];
                }
            }
    
            return ['status' => 'error', 'message' => 'Invalid API response'];
        } catch (\Exception $e) {
            Log::channel('admin')->error('[TrxService] Exception caught', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['status' => 'error', 'message' => 'Unexpected error during TRX wallet creation'];
        }
    }


    private function encrypt(string $data): string
    {
        $iv = openssl_random_pseudo_bytes($this->ivBytes());
        return bin2hex($iv) . openssl_encrypt($data, $this->method, $this->key, 0, $iv);
    }

    private function decrypt(string $data)
    {
        $ivLen = 2 * $this->ivBytes();

        if (preg_match("/^(.{{$ivLen}})(.+)$/", $data, $matches)) {
            [$full, $ivHex, $encrypted] = $matches;

            if (ctype_xdigit($ivHex) && strlen($ivHex) % 2 === 0) {
                $iv = hex2bin($ivHex);
                return openssl_decrypt($encrypted, $this->method, $this->key, 0, $iv);
            }
        }

        return false;
    }

    private function ivBytes(): int
    {
        return openssl_cipher_iv_length($this->method);
    }
}
