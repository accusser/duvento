<?php

namespace App\Support;

use App\Models\Workspace;

final class Onboarding
{
    public const KEYS = ['client', 'asset', 'notice', 'payer', 'check', 'report'];

    public static function steps(Workspace $workspace): array
    {
        return collect(self::KEYS)
            ->map(fn (string $key) => [
                'key' => $key,
                'title' => __("app.onboarding.{$key}.title"),
                'hint' => __("app.onboarding.{$key}.hint"),
            ])
            ->all();
    }

    public static function withStatus(Workspace $workspace): array
    {
        $skipped = collect($workspace->onboarding_done ?? []);
        $detected = self::detected($workspace);

        return collect(self::steps($workspace))
            ->map(fn (array $step) => [
                ...$step,
                'done' => $detected[$step['key']] || $skipped->contains($step['key']),
            ])
            ->all();
    }

    public static function remaining(array $steps): int
    {
        return collect($steps)->where('done', false)->count();
    }

    public static function targetId(Workspace $workspace, string $key): ?int
    {
        $id = match ($key) {
            'notice' => $workspace->clients()
                ->where(fn ($q) => $q->whereNull('email')->orWhere('email', ''))
                ->orderBy('id')
                ->value('id'),
            'payer' => $workspace->assets()->where('payer', 'unknown')->orderBy('id')->value('id'),
            'check' => $workspace->assets()
                ->whereHas('assetType', fn ($q) => $q->where('key', 'ssl'))
                ->orderBy('id')
                ->value('id'),
            default => null,
        };

        return $id === null ? null : (int) $id;
    }

    public static function complete(Workspace $workspace, string $key): void
    {
        if (! in_array($key, self::KEYS, true)) {
            return;
        }

        $done = collect($workspace->onboarding_done ?? [])->push($key)->unique()->values()->all();
        $workspace->forceFill(['onboarding_done' => $done])->save();
    }

    private static function detected(Workspace $workspace): array
    {
        return [
            'client' => $workspace->clients()->exists(),
            'asset' => $workspace->assets()->exists(),
            'notice' => $workspace->clients()->whereNotNull('email')->where('email', '!=', '')->exists()
                || $workspace->assets()->whereNotNull('notice_email')->exists(),
            'payer' => $workspace->assets()->whereIn('payer', ['agency', 'client'])->exists(),
            'check' => $workspace->assets()->whereNotNull('last_checked_at')->exists(),
            'report' => false,
        ];
    }
}
