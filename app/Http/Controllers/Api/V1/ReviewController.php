<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Business;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Business $business)
    {
        $reviews = $business->reviews()
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->paginate(15);

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $review = Review::create([
            'user_id' => $request->user()->id,
            'business_id' => $data['business_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($review, 201);
    }
}
