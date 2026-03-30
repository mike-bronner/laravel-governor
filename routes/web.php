<?php

use GeneaLabs\LaravelGovernor\Http\Controllers\AssignmentsController;
use GeneaLabs\LaravelGovernor\Http\Controllers\GroupsController;
use GeneaLabs\LaravelGovernor\Http\Controllers\InvitationController;
use GeneaLabs\LaravelGovernor\Http\Controllers\RolesController;
use GeneaLabs\LaravelGovernor\Http\Controllers\TeamsController;
use Illuminate\Support\Facades\Route;

Route::resource('roles', RolesController::class);
Route::resource('groups', GroupsController::class);
Route::resource('teams', TeamsController::class);
Route::post('teams/{team}/transfer-ownership', [TeamsController::class, 'transferOwnership'])
    ->name('teams.transfer-ownership');
Route::resource('assignments', AssignmentsController::class);
Route::resource("invitations", InvitationController::class);
