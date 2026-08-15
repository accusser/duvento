<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SecureTicketAttachment implements ValidationRule
{
    /** @var array<string, array<int, string>> */
    private const MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/cdfv2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/cdfv2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail(__('validation.file', ['attribute' => $attribute]));

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mime = strtolower((string) $value->getMimeType());
        $allowedMimes = self::MIME_BY_EXTENSION[$extension] ?? [];

        if (! in_array($mime, $allowedMimes, true)) {
            $fail(__('validation.mimes', [
                'attribute' => $attribute,
                'values' => implode(', ', array_keys(self::MIME_BY_EXTENSION)),
            ]));

            return;
        }

        if (! str_starts_with($mime, 'image/')) {
            return;
        }

        $dimensions = @getimagesize($value->getRealPath());

        if (
            $dimensions === false
            || ($dimensions[0] * $dimensions[1]) > 40_000_000
        ) {
            $fail(__('validation.dimensions', ['attribute' => $attribute]));
        }
    }
}
