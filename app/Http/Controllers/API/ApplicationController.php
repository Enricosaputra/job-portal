<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Validation\Rule; // Add this line
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        if (Auth::user()->isCompany()) {
            $jobs = Auth::user()->jobs()->pluck('id');
            return Application::whereIn('job_id', $jobs)->with('user', 'job')->get();
        }

        return Auth::user()->applications()->with('job')->get();
    }

    public function store(Request $request, Job $job)
    {
        if (!Auth::user()->isFreelancer()) {
            return response()->json(['message' => 'Only freelancers can apply'], 403);
        }

        if ($job->status !== 'published') {
            return response()->json(['message' => 'Job is not published'], 403);
        }

        if (Application::where('job_id', $job->id)->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'You have already applied to this job'], 403);
        }

        $validated = $request->validate([
            'cover_letter' => 'required|string',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:2048'
        ]);

        $path = $request->file('cv')->store('cvs');

        $application = $job->applications()->create([
            'user_id' => Auth::id(),
            'cover_letter' => $validated['cover_letter']
        ]);

        Auth::user()->update(['cv_path' => $path]);

        return response()->json($application, 201);
    }

    public function show(Application $application)
    {
        if ($application->user_id !== Auth::id() && $application->job->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $application->load('user', 'job');
    }


    public function updateStatus(Request $request, Application $application)
    {
        // Verify requesting user owns the job
        if ($application->job->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized - You can only update applications for your own jobs'
            ], 403);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'reviewed',
                    'hired',
                    'rejected',
                    'completed' // Make sure this matches your enum
                ])
            ],
            'notes' => 'nullable|string|max:500'
        ]);

        $application->update([
            'status' => $validated['status'],
            'completion_notes' => $validated['notes'] ?? null
        ]);

        return response()->json([
            'message' => 'Application status updated',
            'application' => $application->fresh()->load('user:id,name')
        ]);
    }

    public function downloadCv(Application $application)
    {
        if ($application->job->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = $application->user;

        if (!$user->cv_path) {
            return response()->json(['message' => 'CV not found'], 404);
        }

        return Storage::download($user->cv_path);
    }


    public function viewAllCvs(Request $request)
    {
        try {
            // Get all applications for jobs owned by the current company
            $applications = Application::with(['user', 'job'])
                ->whereHas('job', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->whereHas('user')
                ->get();

            if ($applications->isEmpty()) {
                return response()->json(['message' => 'No applications found for your jobs'], 404);
            }

            $cvs = [];
            foreach ($applications as $application) {
                $freelancer = $application->user;

                if ($freelancer->cv_path && Storage::exists($freelancer->cv_path)) {
                    $fileContents = Storage::get($freelancer->cv_path);

                    $cvs[] = [
                        'application_id' => $application->id,
                        'job_id' => $application->job_id,
                        'job_title' => $application->job->title,
                        'applicant_name' => $freelancer->name,
                        'applicant_email' => $freelancer->email,
                        'cv_data' => [
                            'filename' => basename($freelancer->cv_path),
                            'mime_type' => Storage::mimeType($freelancer->cv_path),
                            'size' => Storage::size($freelancer->cv_path),
                            'last_updated' => Storage::lastModified($freelancer->cv_path),
                            'download_url' => url("/api/applications/{$application->id}/cv") // Existing download endpoint
                        ],
                        'application_status' => $application->status,
                        'applied_at' => $application->created_at
                    ];
                }
            }

            if (empty($cvs)) {
                return response()->json(['message' => 'No CVs found for your job applicants'], 404);
            }

            return response()->json([
                'count' => count($cvs),
                'cvs' => $cvs
            ]);
        } catch (\Exception $e) {
            Log::error('CV listing failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to retrieve CVs'], 500);
        }
    }
}
