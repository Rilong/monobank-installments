<?php

namespace Rilong\MonobankInstallments;

use Illuminate\Support\Facades\Http;
use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;

class MonobankClient
{
    public function __construct(
        private readonly string $storeId,
        private readonly string $storeSecret,
        private readonly string $baseUrl,
    ) {}

    public function post(string $endpoint, array $payload): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = base64_encode(hash_hmac('sha256', $body, $this->storeSecret, true));

        $response = Http::withHeaders([
            'store-id' => $this->storeId,
            'signature' => $signature,
        ])
        ->withBody($body, 'application/json')
        ->post("{$this->baseUrl}/api/order/{$endpoint}");

        if (!$response->successful()) {
            $message = $response->json('message') ?? $response->body();
            throw new MonobankInstallmentsException($message, $response->status());
        }

        return $response->json() ?? [];
    }
}
