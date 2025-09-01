<?php

use App\Http\Controllers\AdvisorGenerationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/advisors')->group(function () {
    Route::post('/generate', [AdvisorGenerationController::class, 'startGeneration'])
        ->name('advisors.generate');
    
    Route::get('/jobs', [AdvisorGenerationController::class, 'listJobs'])
        ->name('advisors.jobs.list');
    
    Route::get('/jobs/{id}/status', [AdvisorGenerationController::class, 'getStatus'])
        ->name('advisors.jobs.status');
    
    Route::get('/jobs/{id}/result', [AdvisorGenerationController::class, 'getResult'])
        ->name('advisors.jobs.result');
    
    Route::delete('/jobs/{id}', [AdvisorGenerationController::class, 'cancelJob'])
        ->name('advisors.jobs.cancel');
});
