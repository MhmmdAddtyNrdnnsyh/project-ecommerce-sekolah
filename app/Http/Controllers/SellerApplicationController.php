<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SellerApplication;
use App\Models\User;
use App\Support\ActorLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SellerApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $application = SellerApplication::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return Inertia::render('seller-application/index', [
            'application' => $application ? [
                'id' => $application->id,
                'store_name' => $application->store_name,
                'phone' => $application->phone,
                'product_plan' => $application->product_plan,
                'reason' => $application->reason,
                'status' => $application->status,
                'rejection_reason' => $application->rejection_reason,
                'created_at' => $application->created_at?->toIso8601String(),
                'reviewed_at' => $application->reviewed_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== UserRole::Buyer) {
            abort(403);
        }

        $hasPendingApplication = SellerApplication::query()
            ->where('user_id', $user->id)
            ->where('status', SellerApplication::PENDING)
            ->exists();

        if ($hasPendingApplication) {
            throw ValidationException::withMessages([
                'store_name' => 'Pengajuan seller kamu masih menunggu review admin.',
            ]);
        }

        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'product_plan' => ['required', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        SellerApplication::query()->create([
            ...$validated,
            'user_id' => $user->id,
            'status' => SellerApplication::PENDING,
        ]);

        return to_route('seller-application.index')
            ->with('success', 'Pengajuan seller berhasil dikirim.');
    }

    public function approve(Request $request, SellerApplication $application): RedirectResponse
    {
        $this->ensurePending($application);

        /** @var User $applicant */
        $applicant = $application->user;
        ActorLifecycle::assertCanPromoteToSeller($applicant);
        $this->authorize('promoteToSeller', $applicant);

        DB::transaction(function () use ($request, $application) {
            $application->update([
                'status' => SellerApplication::APPROVED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $application->user()->update([
                'role' => UserRole::Seller,
            ]);
        });

        return to_route('admin.seller-applications.index')
            ->with('success', 'Pengajuan seller disetujui.');
    }

    public function reject(Request $request, SellerApplication $application): RedirectResponse
    {
        $this->ensurePending($application);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $application->update([
            'status' => SellerApplication::REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return to_route('admin.seller-applications.index')
            ->with('success', 'Pengajuan seller ditolak.');
    }

    private function ensurePending(SellerApplication $application): void
    {
        abort_unless($application->status === SellerApplication::PENDING, 403);
    }
}
