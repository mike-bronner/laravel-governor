<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::resource("/assignments", "AssignmentController");
Route::resource("/entities", "EntityController");
Route::resource("/groups", "GroupController");
Route::resource("/permissions", "PermissionController");
Route::resource("/roles", "RoleController");
Route::resource("/teams", "TeamController");
Route::get("/team-switcher", "TeamSwitcherController@index");
Route::post("/team-switcher", "TeamSwitcherController@store");
Route::resource("/users", "UserController");
