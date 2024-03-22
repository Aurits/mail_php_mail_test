<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Api\EmailApiController;
use App\Api\EmailSendController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/fetch-emails', [EmailApiController::class, 'fetchEmails']);

Route::post('/send-email', [EmailSendController::class, 'sendEmail']);
