<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::query()
            ->where('status', 'active')
            ->with('business')
            ->paginate($request->integer('per_page', 15));

        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $job = Job::create($data);

        return response()->json($job, 201);
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'job_id' => ['required', 'exists:jobs,id'],
            'message' => ['nullable', 'string'],
        ]);

        $application = JobApplication::create([
            'job_id' => $data['job_id'],
            'user_id' => $request->user()->id,
            'message' => $data['message'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($application, 201);
    }
}
