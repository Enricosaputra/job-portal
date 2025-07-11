<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HonorPoint;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HonorPointController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        if (Auth::user()->isFreelancer()) {
            return Auth::user()->honorPoints()->with('job')->get();
        }

        return response()->json(['message' => 'Only freelancers have honor points'], 403);
    }

    public function store(Request $request, Job $job)
    {
        if ($job->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:1'
        ]);

        $freelancer = User::find($validated['user_id']);

        if (!$freelancer->isFreelancer()) {
            return response()->json(['message' => 'User is not a freelancer'], 403);
        }

        $honorPoint = HonorPoint::create([
            'user_id' => $validated['user_id'],
            'job_id' => $job->id,
            'points' => $validated['points']
        ]);

        return response()->json($honorPoint, 201);
    }

    public function show(HonorPoint $honorPoint)
    {
        if ($honorPoint->user_id !== Auth::id() && $honorPoint->job->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $honorPoint->load('user', 'job');
    }
}
