<?php

namespace App\Enums;

use App\Support\Edition;

enum WorkspacePlan: string
{
    case SelfHosted = 'self-hosted';
    case FreeTrial = 'free-trial';
    case Starter = 'starter';
    case Agency = 'agency';

    public function label(): string
    {
        return __('admin.plans.'.$this->value);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $plan) => [$plan->value => $plan->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function optionsForEdition(?self $current = null): array
    {
        if (Edition::isCloud()) {
            return self::options();
        }

        $plans = collect([self::SelfHosted]);

        if ($current instanceof self && $current !== self::SelfHosted) {
            $plans->push($current);
        }

        return $plans
            ->unique()
            ->mapWithKeys(fn (self $plan) => [$plan->value => $plan->label()])
            ->all();
    }
}
