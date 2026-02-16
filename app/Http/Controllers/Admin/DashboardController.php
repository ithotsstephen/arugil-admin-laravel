<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessPayment;
use App\Models\User;
use App\Models\Review;
use App\Models\Job;
use App\Models\Ad;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'active_businesses' => Business::where('is_approved', true)->count(),
            'pending_approvals' => Business::where('is_approved', false)->count(),
            'reviews_pending' => Review::where('status', 'pending')->count(),
            'jobs_active' => Job::where('status', 'active')->count(),
            'ads_active' => Ad::where('status', 'active')->count(),
        ];

        $start = Carbon::now()->startOfMonth()->subMonths(5);
        $end = Carbon::now()->endOfMonth();

        $userGrowth = DB::table('users')
            ->selectRaw("to_char(created_at, 'YYYY-MM') as month, count(*) as total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $months = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $months[$key] = $userGrowth[$key] ?? 0;
            $cursor->addMonth();
        }

        $paymentStart = Carbon::now()->startOfMonth()->subMonths(5);
        $paymentEnd = Carbon::now()->endOfMonth();

        $paymentGrowth = BusinessPayment::query()
            ->selectRaw("to_char(paid_at, 'YYYY-MM') as month, sum(amount) as total")
            ->whereBetween('paid_at', [$paymentStart, $paymentEnd])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $paymentMonths = [];
        $paymentCursor = $paymentStart->copy();
        while ($paymentCursor <= $paymentEnd) {
            $key = $paymentCursor->format('Y-m');
            $paymentMonths[$key] = (float) ($paymentGrowth[$key] ?? 0);
            $paymentCursor->addMonth();
        }

        $payments = BusinessPayment::with('business')
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get();

        $revenueSummary = [
            'monthly' => (float) BusinessPayment::whereBetween('paid_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('amount'),
            'ytd' => (float) BusinessPayment::whereBetween('paid_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])->sum('amount'),
            'total' => (float) BusinessPayment::sum('amount'),
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'months' => $months,
            'paymentMonths' => $paymentMonths,
            'payments' => $payments,
            'revenueSummary' => $revenueSummary,
        ]);
    }
}
