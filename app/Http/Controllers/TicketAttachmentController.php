<?php

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function __invoke(Request $request, TicketAttachment $attachment): StreamedResponse|Response
    {
        $attachment->loadMissing('message.ticket');
        $ticket = $attachment->message->ticket;

        $isAdmin = auth('admin')->check();
        $isWorkspaceMember = auth()->check()
            && auth()->user()->workspaces()->whereKey($ticket->workspace_id)->exists();

        abort_unless($isAdmin || $isWorkspaceMember, 403);
        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        $headers = [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($request->boolean('download') || ! $attachment->opensInBrowser()) {
            return $disk->download($attachment->path, $attachment->file_name, $headers);
        }

        return $disk->response($attachment->path, $attachment->file_name, $headers);
    }
}
