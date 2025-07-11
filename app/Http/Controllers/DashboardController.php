<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isCompany()) {
            $jobs = $user->jobs()->withCount('applications')->get();
            return view('dashboard.company', compact('jobs'));
        }

        $honorPoints = $user->honorPoints()->sum('points');
        $jobs = \App\Models\Job::published()
            ->with(['user', 'applications' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get()
            ->each(function ($job) {
                $job->hasApplied = $job->applications->isNotEmpty();
            });

        return view('dashboard.freelancer', compact('jobs', 'honorPoints'));
    }
}
