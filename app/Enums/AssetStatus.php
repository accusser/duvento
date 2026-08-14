<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum AssetStatus: string
{
    case Ok = 'ok';
    case Upcoming = 'upcoming';
    case Urgent = 'urgent';
    case Critical = 'critical';
    case Expired = 'expired';
    case Unknown = 'unknown';

    public static function fromExpiration(?CarbonInterface $expiresAt): self
    {
        if ($expiresAt === null) {
            return self::Unknown;
        }

        $days = (int) now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false);

        return match (true) {
            $days < 0 => self::Expired,
            $days <= 7 => self::Critical,
            $days <= 14 => self::Urgent,
            $days <= 30 => self::Upcoming,
            default => self::Ok,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Upcoming => 'Upcoming',
            self::Urgent => 'Urgent',
            self::Critical => 'Critical',
            self::Expired => 'Expired',
            self::Unknown => 'Unknown',
        };
    }

    public function colorToken(): string
    {
        return match ($this) {
            self::Ok => 'ok',
            self::Upcoming => 'upcoming',
            self::Urgent => 'urgent',
            self::Critical, self::Expired => 'critical',
            self::Unknown => 'muted',
        };
    }

    public function dashboardKey(): string
    {
        return match ($this) {
            self::Expired, self::Critical => 'critical',
            default => $this->value,
        };
    }

    public function borderClass(): string
    {
        return match ($this->colorToken()) {
            'ok' => 'border-ok',
            'upcoming' => 'border-upcoming',
            'urgent' => 'border-urgent',
            'critical' => 'border-critical',
            default => 'border-muted',
        };
    }

    public function dotClass(): string
    {
        return match ($this->colorToken()) {
            'ok' => 'bg-ok',
            'upcoming' => 'bg-upcoming',
            'urgent' => 'bg-urgent',
            'critical' => 'bg-critical',
            default => 'bg-muted',
        };
    }

    public static function dashboardKeys(): array
    {
        return ['critical', 'urgent', 'upcoming', 'ok', 'unknown'];
    }
}
