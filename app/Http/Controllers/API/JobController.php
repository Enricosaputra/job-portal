<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\HonorPoint;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        if (Auth::user()->isCompany()) {
            return Auth::user()->jobs;
        }

        return Job::published()->get();
    }

    public function store(Request $request)
    {
        try {
            // Check company authorization
            if (!Auth::user()->isCompany()) {
                return response()->json([
                    'error' => [
                        'message' => 'Only companies can post jobs',
                        'code' => 'company_required'
                    ]
                ], 403);
            }

            // Validate request data
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'requirements' => 'required|string',
                'status' => 'required|in:draft,published'
            ]);

            // Create job
            $job = Auth::user()->jobs()->create($validated);

            return response()->json([
                'message' => 'Job created successfully',
                'data' => $job
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'error' => [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 'validation_error'
                ]
            ], 422);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'error' => [
                    'message' => 'Failed to create job',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                    'code' => 'internal_error'
                ]
            ], 500);
        }
    }

    public function show(Job $job)
    {
        if ($job->status === 'draft' && $job->user_id !== Auth::id()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return $job;
    }

    public function update(Request $request, Job $job)
    {
        if ($job->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'requirements' => 'sometimes|string',
            'status' => 'sometimes|in:draft,published'
        ]);

        $job->update($validated);

        return $job;
    }

    public function destroy(Job $job)
    {
        if ($job->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $job->delete();

        return response()->json(null, 204);
    }

    public function applications(Job $job)
    {
        if ($job->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $job->applications()->with('user')->get();
    }

    /**
     * Get all applicants for a specific job
     * 
     * @param Job $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApplicants(Job $job)
    {
        // Verify the requesting user owns the job
        if ($job->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized - You can only view applicants for your own jobs'
            ], 403);
        }

        // Eager load the applicants with their user data and CV information
        $applicants = $job->applications()
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'email', 'cv_path', 'created_at');
            }])
            ->get()
            ->map(function ($application) {
                return [
                    'application_id' => $application->id,
                    'status' => $application->status,
                    'applied_at' => $application->created_at,
                    'freelancer' => $application->user,
                    'cover_letter' => $application->cover_letter,
                    'cv_download_url' => $application->user->cv_path
                        ? url("/api/applications/{$application->id}/cv")
                        : null
                ];
            });

        return response()->json([
            'job_id' => $job->id,
            'job_title' => $job->title,
            'total_applicants' => count($applicants),
            'applicants' => $applicants
        ]);
    }

    /**
     * Mark job as completed and award honor points
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Job $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeJob(Request $request, Job $job)
    {


        // Verify the requesting user owns the job
        if ($job->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized - You can only complete your own jobs'
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'freelancer_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:1|max:100',
            'completion_notes' => 'nullable|string|max:500'
        ]);

        // Verify the freelancer actually applied to this job
        $application = Application::where('job_id', $job->id)
            ->where('user_id', $validated['freelancer_id'])
            ->first();

        if (!$application) {
            return response()->json([
                'message' => 'This freelancer did not apply to this job'
            ], 400);
        }

        DB::transaction(function () use ($job, $validated, $application) {
            // 1. Update job status
            $job->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // 2. Update application status - use existing enum value or 'hired'
            $application->update([
                'status' => 'hired', // or use 'completed' after migration
                'completion_notes' => $validated['completion_notes'] ?? null
            ]);

            // 3. Award honor points
            HonorPoint::create([
                'user_id' => $validated['freelancer_id'],
                'job_id' => $job->id,
                'points' => $validated['points'],
                'awarded_by' => auth()->id()
            ]);

            // 4. Update freelancer's total points (optional)
            $freelancer = User::find($validated['freelancer_id']);
            $freelancer->increment('total_points', $validated['points']);
        });

        return response()->json([
            'message' => 'Job marked as completed and honor points awarded',
            'job' => $job->fresh(),
            'awarded_points' => $validated['points'],
            'freelancer' => User::find($validated['freelancer_id'])->only('id', 'name', 'total_points')
        ]);
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
