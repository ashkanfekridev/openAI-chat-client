<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationFolderRequest;
use App\Http\Requests\UpdateConversationFolderRequest;
use App\Models\ConversationFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConversationFolderController extends Controller
{
    public function store(StoreConversationFolderRequest $request): JsonResponse|RedirectResponse
    {
        $folder = $request->user()->conversationFolders()->create($request->validated());

        return $request->expectsJson()
            ? response()->json(['folder' => $folder], 201)
            : back()->with('status', 'پوشه ساخته شد.');
    }

    public function update(UpdateConversationFolderRequest $request, ConversationFolder $conversationFolder): JsonResponse|RedirectResponse
    {
        $conversationFolder->update($request->validated());

        return $request->expectsJson()
            ? response()->json(['folder' => $conversationFolder->refresh()])
            : back()->with('status', 'پوشه به‌روزرسانی شد.');
    }

    public function destroy(Request $request, ConversationFolder $conversationFolder): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $conversationFolder);
        $conversationFolder->delete();

        return $request->expectsJson()
            ? response()->json(status: 204)
            : back()->with('status', 'پوشه حذف شد.');
    }
}
