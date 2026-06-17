<?php

namespace App\Http\Responses\Auth;

use App\Support\AuthRedirect;
use Illuminate\Contracts\Support\Responsable;

class RedirectAsIntended implements Responsable
{
    public function __construct(public string $name) {}

    public function toResponse($request)
    {
        return redirect(AuthRedirect::intendedPath($request));
    }
}
