<?php

namespace App\Support;

use App\Rules\SecureTicketAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

final class TicketAttachmentSecurity
{
    /** @var array<int, string> */
    private const ALLOWED_TYPES = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'txt',
        'zip',
    ];

    /** @return array<int, mixed> */
    public static function rules(bool $nullable = true): array
    {
        return [
            $nullable ? 'nullable' : 'required',
            File::types(self::ALLOWED_TYPES)->max('10mb'),
            'extensions:'.implode(',', self::ALLOWED_TYPES),
            new SecureTicketAttachment,
        ];
    }

    public static function validate(UploadedFile $file): void
    {
        Validator::make(
            ['attachment' => $file],
            ['attachment' => self::rules(nullable: false)],
        )->validate();
    }

    public static function safeOriginalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F\x{202A}-\x{202E}\x{2066}-\x{2069}]+/u', ' ', $name) ?? '';
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return Str::limit($name !== '' ? $name : 'attachment', 180, '');
    }
}
