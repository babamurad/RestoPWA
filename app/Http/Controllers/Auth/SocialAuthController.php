<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        session()->put('google_login_redirect', self::safeRedirect($request->query('redirect')));

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $redirect = self::safeRedirect(session()->pull('google_login_redirect'));

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/login?error=google_auth_failed');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update(['google_id' => $googleUser->getId()]);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Google User',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null,
                    // The Google button carries the "by continuing you accept…" notice.
                    'terms_accepted_at' => now(),
                    'terms_version' => config('legal.version'),
                ]);
            }
        }

        if (! $user->avatar && $googleUser->getAvatar()) {
            $user->update(['avatar' => $googleUser->getAvatar()]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->to($redirect);
    }

    /**
     * Only paths inside this site: "?redirect=https://evil.example" (or "//evil.example",
     * "/\\evil.example") must not turn the login into a redirect to someone else's page.
     */
    private static function safeRedirect(mixed $target): string
    {
        if (! is_string($target)
            || ! str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('/[\x00-\x1F]/', $target) === 1) {
            return '/profile';
        }

        return $target;
    }
}
