<?php

namespace Tests;

use Duvento\Cloud\CloudServiceProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function skipWithoutCloud(): void
    {
        if (! class_exists(CloudServiceProvider::class)) {
            $this->markTestSkipped('Cloud-пакет не установлен (OSS-сборка).');
        }
    }

    protected function postPaddleWebhook(array $payload, ?int $ts = null, string $secret = 'whsec_test'): TestResponse
    {
        config(['paddle.webhook_secret' => $secret]);
        $ts ??= time();
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $ts.':'.$body, $secret);

        return $this->call(
            'POST',
            route('billing.paddle.webhook'),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_PADDLE_SIGNATURE' => "ts={$ts};h1={$signature}",
            ],
            content: $body,
        );
    }
}
