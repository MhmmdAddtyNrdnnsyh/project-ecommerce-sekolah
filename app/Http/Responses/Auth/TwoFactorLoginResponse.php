<?php

namespace App\Http\Responses\Auth;

use App\Support\AuthRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        $redirect = AuthRedirect::intendedPath($request);

        return $request->wantsJson()
            ? new JsonResponse(['redirect' => $redirect])
            : redirect($redirect);
    }
}
