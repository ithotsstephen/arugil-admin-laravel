<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index(Request $request)
    {
        $placement = $request->string('placement')->toString() ?: 'home';

        $ads = Ad::query()
            ->where('placement', $placement)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->orderByDesc('start_date')
            ->paginate($request->integer('per_page', 15));

        return response()->json($ads);
    }

    public function click(Ad $ad)
    {
        $ad->increment('clicks');

        return response()->json(['message' => 'click recorded']);
    }
}
