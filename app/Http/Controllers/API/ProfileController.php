<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        $user = $request->user();

        if ($user->isFreelancer()) {
            return $this->getFreelancerProfile($user);
        }

        return $this->getCompanyProfile($user);
    }

    protected function getFreelancerProfile($user)
    {
        return response()->json([
            'role' => 'freelancer',
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'total_points' => $user->total_points ?? 0,
            'cv_url' => $user->cv_path ? url("storage/{$user->cv_path}") : null,
            'member_since' => $user->created_at
        ]);
    }

    protected function getCompanyProfile($user)
    {
        return response()->json([
            'role' => 'company',
            'id' => $user->id,
            'company_name' => $user->company_name,
            'email' => $user->email,
            'total_jobs_posted' => $user->jobs()->count(),
            'member_since' => $user->created_at
        ]);
    }
}
