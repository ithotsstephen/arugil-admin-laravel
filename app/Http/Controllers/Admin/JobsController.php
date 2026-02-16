<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class JobsController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::query()
            ->with('business')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.jobs.index', compact('jobs'));
    }

    public function create()
    {
        $businesses = \App\Models\Business::where('is_approved', true)->orderBy('name')->get();
        return view('admin.jobs.create', compact('businesses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:active,approved,rejected,expired'],
        ]);

        Job::create($data);

        return redirect()->route('admin.jobs.index')->with('status', 'Job created.');
    }

    public function edit(Job $job)
    {
        $businesses = \App\Models\Business::where('is_approved', true)->orderBy('name')->get();
        return view('admin.jobs.edit', compact('job', 'businesses'));
    }

    public function update(Request $request, Job $job)
    {
        $data = $request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:active,approved,rejected,expired'],
        ]);

        $job->update($data);

        return redirect()->route('admin.jobs.index')->with('status', 'Job updated.');
    }

    public function destroy(Job $job)
    {
        $job->delete();

        return redirect()->back()->with('status', 'Job deleted.');
    }

    public function approve(Job $job)
    {
        $job->update(['status' => 'approved']);

        return redirect()->back()->with('status', 'Job approved.');
    }

    public function reject(Job $job)
    {
        $job->update(['status' => 'rejected']);

        return redirect()->back()->with('status', 'Job rejected.');
    }
}
