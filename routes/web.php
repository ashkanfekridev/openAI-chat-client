<?php

use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatPageController;
use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::get('/', ChatPageController::class)->name('chat');

Route::get('/conversations/{conversation}', ConversationController::class)
    ->middleware('throttle:60,1')
    ->name('conversations.show');

Route::get('/conversations/{conversation}/messages/{chatMessage}/attachments/{attachment}', ChatAttachmentController::class)
    ->whereNumber('attachment')
    ->middleware('throttle:120,1')
    ->name('chat.attachments.show');

Route::post('/chat', ChatController::class)
    ->middleware('throttle:10,1')
    ->name('chat.send');
