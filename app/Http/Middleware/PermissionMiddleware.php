<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Evaluasi izin matriks UserGroup (atau fallback role default jika user belum memiliki grup).
     * Mendukung satu atau beberapa kunci izin (OR logic, serupa dengan RoleMiddleware).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if ($user->role === 'owner' || empty($permissions) || in_array('*', $permissions, true)) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        $permLabel = implode(', ', $permissions);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => "Akses ditolak. Kelompok hak akses Anda tidak memiliki izin ({$permLabel}) untuk tindakan ini.",
            ], 403);
        }

        abort(403, "Akses ditolak. Kelompok hak akses Anda tidak memiliki izin ({$permLabel}) untuk mengakses fitur ini.");
    }
}
