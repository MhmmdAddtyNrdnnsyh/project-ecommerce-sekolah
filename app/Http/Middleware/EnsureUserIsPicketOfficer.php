<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPicketOfficer
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== UserRole::PicketOfficer) {
            abort(403);
        }

        if ($request->user()->up_jurusan_id === null) {
            return to_route('picket.unassigned');
        }

        return $next($request);
    }
}
