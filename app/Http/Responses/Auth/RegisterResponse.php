<?php

namespace App\Http\Responses\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        Auth::guard(config('fortify.guard', 'web'))->logout();

        if ($request->hasSession()) {
            $request->session()->regenerateToken();
        }

        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()
                ->route('login')
                ->with('status', __('Registrasi berhasil. Silakan masuk menggunakan akun yang baru dibuat.'));
    }
}
