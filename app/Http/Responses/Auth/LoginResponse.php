<?php

namespace App\Http\Responses\Auth;

use App\Support\AuthRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $redirect = AuthRedirect::intendedPath($request);

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false, 'redirect' => $redirect])
            : redirect($redirect);
    }
}
