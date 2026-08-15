<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class TelegramNotifier
{
    public function bot(string $token): array
    {
        return $this->call($token, 'getMe');
    }

    public function chat(string $token, string $chatId): array
    {
        return $this->call($token, 'getChat', ['chat_id' => $chatId]);
    }

    public function send(string $token, string $chatId, string $text): void
    {
        $this->call($token, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    /** @return list<array{id: string, title: string, type: string}> */
    public function recentChats(string $token): array
    {
        $updates = $this->call($token, 'getUpdates', [
            'limit' => 50,
            'timeout' => 0,
        ]);

        return collect($updates)
            ->map(fn (array $update) => $update['message']['chat']
                ?? $update['my_chat_member']['chat']
                ?? $update['channel_post']['chat']
                ?? null)
            ->filter()
            ->unique(fn (array $chat) => (string) $chat['id'])
            ->map(fn (array $chat) => [
                'id' => (string) $chat['id'],
                'title' => $this->chatTitle($chat),
                'type' => (string) ($chat['type'] ?? 'private'),
            ])
            ->values()
            ->all();
    }

    public function chatTitle(array $chat): string
    {
        if (filled($chat['title'] ?? null)) {
            return (string) $chat['title'];
        }

        if (filled($chat['first_name'] ?? null)) {
            return trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? ''));
        }

        if (filled($chat['username'] ?? null)) {
            return '@'.$chat['username'];
        }

        return (string) ($chat['id'] ?? '');
    }

    private function call(string $token, string $method, array $payload = []): array
    {
        $response = Http::timeout(12)
            ->acceptJson()
            ->asJson()
            ->post('https://api.telegram.org/bot'.$token.'/'.$method, $payload ?: (object) []);

        $json = $response->json();

        if (! is_array($json) || ! ($json['ok'] ?? false)) {
            throw new RuntimeException(
                is_array($json) ? (string) ($json['description'] ?? __('app.reminders.telegram_failed')) : __('app.reminders.telegram_failed'),
            );
        }

        return is_array($json['result'] ?? null) ? $json['result'] : [];
    }
}
