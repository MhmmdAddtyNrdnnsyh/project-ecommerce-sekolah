<?php

namespace App\Http\Controllers;

use App\Models\SellerApplication;
use Inertia\Inertia;
use Inertia\Response;

class AdminSellerApplicationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/seller-applications/index', [
            'sellerApplications' => SellerApplication::query()
                ->with('user:id,name,email')
                ->where('status', SellerApplication::PENDING)
                ->latest()
                ->get()
                ->map(fn (SellerApplication $application) => [
                    'id' => $application->id,
                    'store_name' => $application->store_name,
                    'phone' => $application->phone,
                    'product_plan' => $application->product_plan,
                    'reason' => $application->reason,
                    'user' => [
                        'name' => $application->user->name,
                        'email' => $application->user->email,
                    ],
                    'created_at' => $application->created_at?->toIso8601String(),
                ]),
        ]);
    }
}
