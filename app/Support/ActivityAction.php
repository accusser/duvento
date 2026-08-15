<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

final class ActivityAction
{
    public static function label(string $action): string
    {
        return __('app.activity.actions')[$action] ?? $action;
    }

    /**
     * @param  array<string, mixed>|null  $properties
     * @return list<array{label: string, value: string}>
     */
    public static function propertyRows(?array $properties): array
    {
        if ($properties === null || $properties === []) {
            return [];
        }

        $rows = [];

        foreach ($properties as $key => $value) {
            $formatted = self::propertyValue((string) $key, $value);

            if ($formatted === '') {
                continue;
            }

            $rows[] = [
                'label' => self::propertyLabel((string) $key),
                'value' => $formatted,
            ];
        }

        return $rows;
    }

    public static function teaser(?array $properties): string
    {
        if ($properties === null || $properties === []) {
            return '';
        }

        $name = filled($properties['name'] ?? null) ? (string) $properties['name'] : '';
        $model = isset($properties['model']) ? self::propertyValue('model', $properties['model']) : '';

        if ($name !== '' && $model !== '') {
            return $model.' · '.$name;
        }

        return $name !== '' ? $name : ($model !== '' ? $model : collect(self::propertyRows($properties))
            ->map(fn (array $row): string => $row['value'])
            ->implode(' · '));
    }

    public static function propertyLabel(string $key): string
    {
        foreach ([
            "admin.activity.keys.{$key}",
            "admin.fields.{$key}",
            "admin.tickets.{$key}",
        ] as $translation) {
            if (Lang::has($translation)) {
                return __($translation);
            }
        }

        return match ($key) {
            'last_message_at' => __('admin.tickets.last_message'),
            default => $key,
        };
    }

    private static function propertyValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($key === 'model' && is_string($value)) {
            $translation = 'admin.activity.models.'.$value;

            return Lang::has($translation) ? __($translation) : $value;
        }

        if ($key === 'fields' && is_array($value)) {
            return collect($value)
                ->map(fn ($field) => self::propertyLabel((string) $field))
                ->filter()
                ->implode(', ');
        }

        if (is_bool($value)) {
            return __($value ? 'admin.enums.auto_renew.yes' : 'admin.enums.auto_renew.no');
        }

        if (is_array($value)) {
            $items = array_is_list($value)
                ? $value
                : collect($value)->map(fn ($item, $name) => $name.': '.$item)->all();

            return collect($items)
                ->map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE))
                ->implode(', ');
        }

        return (string) $value;
    }
}
