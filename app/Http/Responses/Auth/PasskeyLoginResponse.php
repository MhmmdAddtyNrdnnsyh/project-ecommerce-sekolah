<?php

namespace App\Http\Responses\Auth;

use App\Support\AuthRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request)
    {
        $redirect = AuthRedirect::intendedPath($request);

        if ($request->wantsJson()) {
            return new JsonResponse([
                'redirect' => $redirect,
            ]);
        }

        return redirect($redirect);
    }
}
