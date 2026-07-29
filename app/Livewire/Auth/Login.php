<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Login-only. Tidak ada registrasi mandiri, akun dibuat oleh Admin,
 * sesuai kebijakan sistem informasi rumah sakit.
 */
#[Layout('layouts.guest')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok.',
            ]);
        }

        // Akun yang dinonaktifkan tidak boleh masuk, walau kredensialnya benar.
        if (! Auth::user()->is_active) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Hubungi Admin.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirectIntended(route(Auth::user()->role->homeRoute()), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
