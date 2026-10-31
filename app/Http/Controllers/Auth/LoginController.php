<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan formulir login resmi SIGMA ERP & POS.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'cashier') {
                return redirect()->route('pos.index');
            }

            return redirect()->route('sales.index');
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login pengguna.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        $candidateUser = User::where('email', $request->input('email'))->first();
        if ($candidateUser && (! $candidateUser->is_active || ! $candidateUser->isActive())) {
            RateLimiter::hit($throttleKey, 60);

            AuditLog::record(
                module: 'AUTH',
                action: 'LOGIN_BLOCKED_INACTIVE',
                description: "Percobaan login ditolak: Akun {$candidateUser->name} [{$candidateUser->email}] berstatus nonaktif atau masa berlaku telah habis.",
                user: $candidateUser
            );

            return back()->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan atau masa kontrak telah berakhir. Hubungi Administrator.',
            ])->withInput($request->only('email'));
        }

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);

            $user = Auth::user();

            $request->session()->regenerate();

            AuditLog::record(
                module: 'AUTH',
                action: 'LOGIN_SUCCESS',
                description: "Pengguna {$user->name} ({$user->role}) berhasil masuk ke sistem dari IP " . ($request->ip() ?? '127.0.0.1'),
                user: $user
            );

            // Set session kasir jika role kasir
            if ($user->role === 'cashier') {
                session(['pos_active_cashier_id' => $user->id]);

                return redirect()->intended(route('pos.index'));
            }

            return redirect()->intended(route('sales.index'));
        }

        RateLimiter::hit($throttleKey, 60);

        AuditLog::record(
            module: 'AUTH',
            action: 'LOGIN_FAILED',
            description: "Percobaan login gagal untuk email [{$request->email}] dari IP " . ($request->ip() ?? '127.0.0.1')
        );

        return back()->withErrors([
            'email' => 'Kombinasi email dan password tidak sesuai.',
        ])->withInput($request->only('email'));
    }

    /**
     * Keluar dari sistem (Logout).
     */
    public function logout(Request $request): RedirectResponse
    {
        if ($user = Auth::user()) {
            AuditLog::record(
                module: 'AUTH',
                action: 'LOGOUT',
                description: "Pengguna {$user->name} ({$user->role}) telah keluar (logout) dari sistem.",
                user: $user
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar dari sistem.');
    }
}
