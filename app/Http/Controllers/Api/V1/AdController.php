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

    public function update(Request $request, Ad $ad)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('super_admin', 'moderator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'image_url' => ['sometimes', 'required', 'string', 'max:2048'],
            'link' => ['nullable', 'string', 'max:2048'],
            'placement' => ['sometimes', 'required', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
        ]);

        $ad->update($data);

        return response()->json($ad);
    }

    public function destroy(Request $request, Ad $ad)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('super_admin', 'moderator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ad->delete();

        return response()->json(['message' => 'Ad deleted']);
    }
}
