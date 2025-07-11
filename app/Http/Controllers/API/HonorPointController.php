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
}
