<?php

namespace App\Http\Responses\Auth;

use App\Support\AuthRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return redirect($this->verifiedPath(AuthRedirect::intendedPath($request)));
    }

    private function verifiedPath(string $path): string
    {
        $separator = str_contains($path, '?') ? '&' : '?';

        return $path.$separator.'verified=1';
    }
}
