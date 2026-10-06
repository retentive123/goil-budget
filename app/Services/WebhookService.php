<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Fire all active webhooks that listen for the given event.
     *
     * @param string $event   e.g. 'budget_submitted'
     * @param array  $payload Serialisable data to POST
     */
    public function fire(string $event, array $payload): void
    {
        $webhooks = Webhook::forEvent($event);

        foreach ($webhooks as $webhook) {
            $this->dispatch($webhook, $event, $payload);
        }
    }

    private function dispatch(Webhook $webhook, string $event, array $payload): void
    {
        $body = array_merge($payload, [
            'event'     => $event,
            'fired_at'  => now()->toIso8601String(),
        ]);

        $headers = ['Content-Type' => 'application/json'];

        if ($webhook->secret) {
            $signature = hash_hmac('sha256', json_encode($body), $webhook->secret);
            $headers['X-GOIL-Signature'] = "sha256={$signature}";
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($webhook->url, $body);

            $webhook->update([
                'last_fired_at'     => now(),
                'last_status_code'  => $response->status(),
                'last_response'     => substr($response->body(), 0, 500),
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook dispatch failed [{$webhook->name}]: {$e->getMessage()}");
            $webhook->update([
                'last_fired_at'    => now(),
                'last_status_code' => 0,
                'last_response'    => $e->getMessage(),
            ]);
        }
    }
}
