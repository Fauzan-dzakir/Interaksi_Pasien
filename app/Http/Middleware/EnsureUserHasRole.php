<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gating kasar per grup route, mis. Route::middleware('role:admin').
 * Aturan scoping data per record (mis. Nakes cuma boleh lihat order unitnya sendiri)
 * ditangani terpisah lewat Policy, bukan di sini.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Akun tidak aktif atau tidak dikenali.');
        }

        $allowed = array_map(fn (string $r) => UserRole::from($r), $roles);

        abort_unless($user->hasRole($allowed), 403, 'Anda tidak punya hak akses ke halaman ini.');

        return $next($request);
    }
}
