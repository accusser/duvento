<?php

namespace App\Http\Controllers;

use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function stop(): RedirectResponse
    {
        abort_unless(Impersonation::active(), 403);
        Impersonation::stop();

        return redirect('/admin/users');
    }
}
