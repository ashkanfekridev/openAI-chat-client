<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View|RedirectResponse
    {
        return request()->user()?->hasVerifiedEmail()
            ? redirect()->route('chat')
            : view('auth.verify-email');
    }
}
