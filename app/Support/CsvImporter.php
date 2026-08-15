<?php

namespace App\Support;

use App\Models\AssetType;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class CsvImporter
{
    public function importClients(Workspace $workspace, UploadedFile $file, PlanGuard $guard): int
    {
        $rows = $this->rows($file);
        $created = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? $row['имя'] ?? ''));

            if ($name === '') {
                continue;
            }

            $guard->assertCanCreateClient($workspace);
            $client = $workspace->clients()->create([
                'name' => mb_substr($name, 0, 160),
                'contact_name' => $this->nullable($row['contact'] ?? $row['contact_name'] ?? $row['контакт'] ?? null),
                'email' => $this->email($row['email'] ?? $row['почта'] ?? null),
                'website' => $this->nullable($row['website'] ?? $row['сайт'] ?? $row['url'] ?? null),
                'notes' => $this->nullable($row['notes'] ?? $row['заметки'] ?? null),
            ]);
            ActivityLogger::log($workspace, 'client.created', $client, ['name' => $client->name]);
            $created++;
        }

        return $created;
    }

    public function importAssets(Workspace $workspace, UploadedFile $file, PlanGuard $guard): int
    {
        $rows = $this->rows($file);
        $created = 0;
        $types = AssetType::query()->availableFor($workspace)->get();

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? $row['название'] ?? ''));

            if ($name === '') {
                continue;
            }

            $client = $this->resolveClient($workspace, $row);
            $type = $this->resolveType($types, $row);

            if ($client === null || $type === null) {
                continue;
            }

            $guard->assertCanCreateAsset($workspace);
            $asset = $workspace->assets()->create([
                'client_id' => $client->id,
                'asset_type_id' => $type->id,
                'name' => mb_substr($name, 0, 255),
                'expires_at' => $this->nullable($row['expires_at'] ?? $row['истекает'] ?? null),
                'owner' => $this->enum($row['owner'] ?? $row['владелец'] ?? null, ['agency', 'client', 'unknown'], 'unknown'),
                'payer' => $this->enum($row['payer'] ?? $row['плательщик'] ?? null, ['agency', 'client', 'unknown'], 'unknown'),
                'auto_renew' => $this->enum($row['auto_renew'] ?? $row['автопродление'] ?? null, ['yes', 'no', 'unknown'], 'unknown'),
                'notice_email' => $this->email($row['notice_email'] ?? $row['email'] ?? null),
                'notes' => $this->nullable($row['notes'] ?? $row['заметки'] ?? null),
                'ssl_check_enabled' => filter_var($row['ssl_check'] ?? $row['ssl'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'renewal_cost' => $this->money($row['renewal_cost'] ?? $row['стоимость'] ?? null),
                'currency' => $this->currency($row['currency'] ?? $row['валюта'] ?? null, $workspace->currencyCode()),
            ]);
            ActivityLogger::log($workspace, 'asset.created', $asset, ['name' => $asset->name]);
            $created++;
        }

        return $created;
    }

    private function rows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new RuntimeException(__('app.import.read_failed'));
        }

        $header = fgetcsv($handle);

        if (! is_array($header) || $header === []) {
            fclose($handle);

            return [];
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn ($col) => strtolower(trim((string) $col)), $header);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false && count($rows) < 500) {
            if ($this->emptyLine($line)) {
                continue;
            }

            $rows[] = array_combine($header, array_pad($line, count($header), null)) ?: [];
        }

        fclose($handle);

        return $rows;
    }

    private function resolveClient(Workspace $workspace, array $row): ?object
    {
        $id = (int) ($row['client_id'] ?? 0);

        if ($id > 0) {
            return $workspace->clients()->find($id);
        }

        $name = trim((string) ($row['client'] ?? $row['client_name'] ?? $row['клиент'] ?? ''));

        if ($name === '') {
            return null;
        }

        return $workspace->clients()->where('name', $name)->first();
    }

    private function resolveType($types, array $row): ?AssetType
    {
        $key = strtolower(trim((string) ($row['type'] ?? $row['тип'] ?? '')));

        if ($key === '') {
            return $types->firstWhere('key', 'other') ?? $types->first();
        }

        return $types->first(fn (AssetType $type) => $type->matchesName($key));
    }

    private function email(mixed $value): ?string
    {
        $value = trim((string) $value);

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function enum(mixed $value, array $allowed, string $default): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function money(mixed $value): ?string
    {
        $value = str_replace(' ', '', trim((string) $value));

        if ($value === '' || ! is_numeric($value) || (float) $value < 0) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function currency(mixed $value, string $fallback): string
    {
        return UpcomingPayments::normalizeCurrency($value ?: $fallback);
    }

    private function emptyLine(array $line): bool
    {
        return collect($line)->every(fn ($cell) => trim((string) $cell) === '');
    }
}
