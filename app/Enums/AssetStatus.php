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
        return __('app.enums.status.'.$this->value);
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
            'ok' => 'ny-status-ok',
            'upcoming' => 'ny-status-upcoming',
            'urgent' => 'ny-status-urgent',
            'critical' => 'ny-status-critical',
            default => 'ny-status-muted',
        };
    }

    public function dotClass(): string
    {
        return match ($this->colorToken()) {
            'ok' => 'status-dot status-online',
            'upcoming' => 'status-dot status-upcoming',
            'urgent' => 'status-dot status-away',
            'critical' => 'status-dot status-busy',
            default => 'status-dot status-offline',
        };
    }

    public function badgeClass(): string
    {
        return match ($this->colorToken()) {
            'ok' => 'badge badge-soft-success',
            'upcoming' => 'badge badge-soft-info',
            'urgent' => 'badge badge-soft-warning',
            'critical' => 'badge badge-soft-danger',
            default => 'badge badge-soft-secondary',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Expired, self::Critical => 4,
            self::Urgent => 3,
            self::Upcoming => 2,
            self::Ok => 1,
            self::Unknown => 0,
        };
    }

    public static function dashboardKeys(): array
    {
        return ['critical', 'urgent', 'upcoming', 'ok', 'unknown'];
    }
}
