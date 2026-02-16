<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class OffersController extends Controller
{
    public function index(Request $request)
    {
        $businesses = Business::query()
            ->whereNotNull('offers')
            ->with(['category', 'owner'])
            ->orderByDesc('created_at')
            ->get();

        // Filter and flatten offers with business info
        $allOffers = [];
        foreach ($businesses as $business) {
            if ($business->offers && is_array($business->offers)) {
                foreach ($business->offers as $offer) {
                    $startDate = isset($offer['start_date']) ? \Carbon\Carbon::parse($offer['start_date']) : null;
                    $endDate = isset($offer['end_date']) ? \Carbon\Carbon::parse($offer['end_date']) : null;
                    
                    // Check if offer is active
                    $isActive = false;
                    $status = 'expired';
                    
                    if ($startDate && $endDate) {
                        $now = now();
                        if ($now->between($startDate, $endDate)) {
                            $isActive = true;
                            $status = 'active';
                        } elseif ($now->isBefore($startDate)) {
                            $status = 'scheduled';
                        }
                    }
                    
                    // Filter by status if requested
                    if ($request->filled('status')) {
                        $requestedStatus = $request->string('status')->toString();
                        if ($requestedStatus !== 'all' && $requestedStatus !== $status) {
                            continue;
                        }
                    }
                    
                    $allOffers[] = [
                        'business_id' => $business->id,
                        'business_name' => $business->name,
                        'category' => $business->category->name ?? 'N/A',
                        'image_url' => $offer['image_url'] ?? null,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status' => $status,
                        'is_active' => $isActive,
                    ];
                }
            }
        }

        // Sort by status (active first) and then by start date
        usort($allOffers, function ($a, $b) {
            if ($a['is_active'] != $b['is_active']) {
                return $b['is_active'] <=> $a['is_active'];
            }
            return $b['start_date'] <=> $a['start_date'];
        });

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $offersCollection = collect($allOffers);
        $offers = new LengthAwarePaginator(
            $offersCollection->forPage($page, $perPage)->values(),
            $offersCollection->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('admin.offers.index', compact('offers'));
    }
}
