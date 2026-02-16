<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessPayment;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class PaymentController extends Controller
{
    /**
     * Get all payments for a business
     *
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Business $business)
    {
        $payments = $business->payments()
            ->orderBy('paid_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'pagination' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ]
        ]);
    }

    /**
     * Create a new payment for a business
     *
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Business $business)
    {
        // Verify the authenticated user owns this business
        if ($business->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $payment = $business->payments()->create([
                'amount' => $validated['amount'],
                'paid_at' => $validated['paid_at'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $payment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
