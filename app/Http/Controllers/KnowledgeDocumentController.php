<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKnowledgeDocumentRequest;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Services\OpenAIClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class KnowledgeDocumentController extends Controller
{
    public function store(StoreKnowledgeDocumentRequest $request, OpenAIClient $openAI): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $file = $request->file('document');
        $path = $file->store("knowledge-documents/{$user->id}", 'local');
        $document = $user->knowledgeDocuments()->create([
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'path' => $path,
            'status' => 'processing',
        ]);

        try {
            $openAIFile = $openAI->uploadKnowledgeFile($file);
            $vectorStoreId = $user->vector_store_id ?: $openAI->createVectorStore("Knowledge for user {$user->id}");
            $openAI->attachFileToVectorStore($vectorStoreId, $openAIFile['id']);
            $user->forceFill(['vector_store_id' => $vectorStoreId])->save();
            $document->update(['openai_file_id' => $openAIFile['id'], 'status' => 'ready']);
        } catch (Throwable $exception) {
            report($exception);
            $document->update(['status' => 'failed', 'error' => 'بارگذاری سند در OpenAI ناموفق بود.']);

            return $request->expectsJson()
                ? response()->json(['message' => $document->error], 502)
                : back()->withErrors(['document' => $document->error]);
        }

        return $request->expectsJson()
            ? response()->json(['document' => $document->fresh()], 201)
            : back()->with('status', 'سند به کتابخانه اضافه شد.');
    }

    public function destroy(KnowledgeDocument $knowledgeDocument, OpenAIClient $openAI): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $knowledgeDocument);

        if ($knowledgeDocument->openai_file_id !== null) {
            $openAI->deleteFile($knowledgeDocument->openai_file_id);
        }

        Storage::disk('local')->delete($knowledgeDocument->path);
        $knowledgeDocument->delete();

        return request()->expectsJson()
            ? response()->json(status: 204)
            : back()->with('status', 'سند حذف شد.');
    }
}
