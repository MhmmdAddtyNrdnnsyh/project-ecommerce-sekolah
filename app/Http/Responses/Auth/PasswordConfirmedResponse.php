<?php

namespace App\Http\Responses\Auth;

use App\Support\AuthRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;

class PasswordConfirmedResponse implements PasswordConfirmedResponseContract
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect(AuthRedirect::intendedPath($request));
    }
}
