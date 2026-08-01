<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatPageController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ConversationFolderController;
use App\Http\Controllers\ConversationManagementController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\KnowledgeDocumentController;
use App\Http\Controllers\PublicConversationController;
use App\Http\Controllers\SpeechController;
use Illuminate\Support\Facades\Route;

Route::get('/docs/{path?}', DocumentationController::class)
    ->where('path', '.*')
    ->name('documentation');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::get('/shared/{shareToken}', PublicConversationController::class)
    ->middleware('throttle:60,1')
    ->name('conversations.public');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', EmailVerificationNotificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware(['active', 'verified'])->group(function (): void {
        Route::get('/', ChatPageController::class)->name('chat');
        Route::get('/chat', ChatPageController::class)->name('chat.legacy');

        Route::get('/conversations/{conversation}', ConversationController::class)
            ->middleware('throttle:60,1')
            ->name('conversations.show');

        Route::patch('/conversations/{conversation}', [ConversationManagementController::class, 'update'])->name('conversations.update');
        Route::post('/conversations/{conversation}/pin', [ConversationManagementController::class, 'togglePin'])->name('conversations.pin');
        Route::post('/conversations/{conversation}/archive', [ConversationManagementController::class, 'toggleArchive'])->name('conversations.archive');
        Route::post('/conversations/{conversation}/share', [ConversationManagementController::class, 'share'])->name('conversations.share');
        Route::delete('/conversations/{conversation}/share', [ConversationManagementController::class, 'unshare'])->name('conversations.unshare');
        Route::delete('/conversations/{conversation}', [ConversationManagementController::class, 'destroy'])->name('conversations.destroy');
        Route::get('/conversations/{conversation}/export/{format}', [ConversationManagementController::class, 'export'])->name('conversations.export');

        Route::post('/folders', [ConversationFolderController::class, 'store'])->name('folders.store');
        Route::patch('/folders/{conversationFolder}', [ConversationFolderController::class, 'update'])->name('folders.update');
        Route::delete('/folders/{conversationFolder}', [ConversationFolderController::class, 'destroy'])->name('folders.destroy');

        Route::get('/conversations/{conversation}/messages/{chatMessage}/attachments/{attachment}', ChatAttachmentController::class)
            ->whereNumber('attachment')
            ->middleware('throttle:120,1')
            ->name('chat.attachments.show');

        Route::post('/chat', ChatController::class)
            ->middleware('throttle:10,1')
            ->name('chat.send');

        Route::post('/knowledge-documents', [KnowledgeDocumentController::class, 'store'])->middleware('throttle:10,1')->name('knowledge-documents.store');
        Route::delete('/knowledge-documents/{knowledgeDocument}', [KnowledgeDocumentController::class, 'destroy'])->name('knowledge-documents.destroy');
        Route::post('/speech/transcribe', SpeechController::class)->middleware('throttle:10,1')->name('speech.transcribe');

        Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::put('/users/{user}/usage-limit', [AdminUserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/usage-reset', [AdminUserController::class, 'resetUsage'])->name('users.usage.reset');
        });
    });
});
