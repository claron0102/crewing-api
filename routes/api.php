<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrewClearance_Controller;
use App\Http\Controllers\RouteFleet_Controller;
use App\Http\Middleware\ValidateAppToken;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
 
Route::apiResource('crewClearance', CrewClearance_Controller::class);

Route::middleware([ValidateAppToken::class])->group(function () {
    Route::get('/conductors/{id}/fleet', [CrewClearance_Controller::class, 'conductor_verification']);
    Route::get('/fleets/{id}/crew', [CrewClearance_Controller::class, 'fleet_crew']);
    Route::get('/fleets/{id}/route/latest', [RouteFleet_Controller::class, 'fleet_route']);
    Route::get('/fleets/{id}/route', [RouteFleet_Controller::class, 'fleet_route']);
   // Route::get('/fleets/{id}/route', [RouteFleet_Controller::class, 'fleet_route']);
    
});
