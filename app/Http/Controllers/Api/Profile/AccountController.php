<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Services\Account\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AccountController extends Controller
{
    public function destroy(Request $request, AccountDeletionService $deletion): JsonResponse
    {
        $user = $request->user();

        // Password accounts confirm with the password; Google-only accounts have none
        if ($user->password !== null) {
            $request->validate(['password' => 'required|string']);

            if (! Hash::check($request->input('password'), $user->password)) {
                throw ValidationException::withMessages(['password' => __('api.account.wrong_password')]);
            }
        }

        try {
            $deletion->delete($user);
        } catch (RuntimeException $e) {
            $message = $e->getCode() === 409 ? __('api.account.active_orders') : __('api.account.staff');

            return response()->json(['success' => false, 'message' => $message], $e->getCode() === 409 ? 409 : 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true, 'message' => __('api.account.deleted')]);
    }
}
