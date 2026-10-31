<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if (! $user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun Anda dinonaktifkan atau masa berlaku telah habis.'], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan atau masa berlaku telah habis.',
            ]);
        }

        // Wildcard or no specific role required
        if (empty($roles) || in_array('*', $roles, true)) {
            return $next($request);
        }

        // Owner always has broad administrative access
        if ($user->role === 'owner') {
            return $next($request);
        }

        // Check if user role matches one of allowed roles
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Peran Anda ('.$user->role.') tidak memiliki izin untuk tindakan ini.',
            ], 403);
        }

        abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
