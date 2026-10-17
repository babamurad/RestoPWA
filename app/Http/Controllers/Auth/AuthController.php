<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'user' => Auth::user(),
                    'redirect_url' => session('url.intended', route('home')),
                ]);
            }

            return redirect()->intended(route('home'));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => __('api.invalid_credentials'),
            ], 401);
        }

        return back()->withErrors([
            'email' => __('api.invalid_credentials'),
        ])->onlyInput('email');
    }

    public function register(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->filled('phone')) {
            $phone = preg_replace('/\D/', '', (string) $request->phone);
            if (strlen($phone) === 8) {
                $request->merge(['phone' => '+993'.$phone]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'regex:/^\+993\d{8}$/', 'unique:users,phone'],
            'terms_accepted' => 'accepted',
            'locale' => ['nullable', Rule::in(config('app.supported_locales'))],
        ], [
            'terms_accepted.accepted' => __('api.terms_required'),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'locale' => $validated['locale'] ?? app()->getLocale(),
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.version'),
        ]);

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'user' => $user,
                'redirect_url' => route('home'),
            ], 201);
        }

        return redirect(route('home'));
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return redirect(route('home'));
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role?->value ?? $user->role,
                'avatar' => $user->avatar,
                'locale' => $user->locale,
            ],
        ]);
    }

    /** Language of the customer app, push notifications and SMS. */
    public function updateLocale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(config('app.supported_locales'))],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);

        return response()->json([
            'success' => true,
            'locale' => $validated['locale'],
        ]);
    }
}
