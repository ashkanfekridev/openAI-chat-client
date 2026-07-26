<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranscribeSpeechRequest;
use App\Services\OpenAIClient;
use Illuminate\Http\JsonResponse;

class SpeechController extends Controller
{
    public function __invoke(TranscribeSpeechRequest $request, OpenAIClient $openAI): JsonResponse
    {
        return response()->json([
            'text' => $openAI->transcribe($request->file('audio')),
        ]);
    }
}
