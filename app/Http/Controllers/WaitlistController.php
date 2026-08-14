<?php

namespace App\Http\Controllers;

use App\Models\WaitlistSignup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        WaitlistSignup::query()->updateOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'] ?? null],
        );

        return back()->with('status', 'Заявка принята. Напишем, когда откроем Cloud.');
    }
}
