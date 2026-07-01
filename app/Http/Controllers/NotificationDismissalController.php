<?php

namespace App\Http\Controllers;

use App\Models\NotificationDismissal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationDismissalController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:191'],
        ]);

        NotificationDismissal::query()->updateOrCreate([
            'user_id' => $request->user()->id,
            'key' => $validated['key'],
        ]);

        return back();
    }
}
