<?php

use App\Http\Controllers\API\ApplicationController as APIApplicationController;
use App\Http\Controllers\API\AuthController as APIAuthController;
use App\Http\Controllers\API\HonorPointController as APIHonorPointController;
use App\Http\Controllers\API\JobController as APIJobController;
use App\Http\Controllers\API\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\HonorPointController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [APIAuthController::class, 'register']);
Route::post('/login', [APIAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [APIAuthController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'getProfile']);

    // Jobs
    Route::apiResource('jobs', APIJobController::class);
    Route::get('/jobs/{job}/applications', [APIJobController::class, 'applications']);
    Route::get('/jobs/{job}/applicants', [APIJobController::class, 'getApplicants']);
    Route::post('/jobs/{job}/complete', [APIJobController::class, 'completeJob'])
        ->whereNumber('job');

    // Applications
    Route::apiResource('applications', APIApplicationController::class)->except(['store']);
    Route::post('/jobs/{job}/applications', [APIApplicationController::class, 'store']);
    Route::patch('/applications/{application}/status', [APIApplicationController::class, 'updateStatus']);
    Route::get('/company/cvs', [APIApplicationController::class, 'viewAllCvs']);


    // Honor Points
    Route::apiResource('honor-points', APIHonorPointController::class)->except(['store']);
    Route::post('/jobs/{job}/honor-points', [APIHonorPointController::class, 'store']);
});
