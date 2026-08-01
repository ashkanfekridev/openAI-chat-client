<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentationController extends Controller
{
    public function __invoke(?string $path = null): BinaryFileResponse
    {
        $documentationRoot = realpath(base_path('docs'));
        abort_unless(is_string($documentationRoot), 404);

        $filePath = realpath($documentationRoot.DIRECTORY_SEPARATOR.($path ?: 'index.html'));

        abort_unless(
            is_string($filePath)
            && str_starts_with($filePath, $documentationRoot.DIRECTORY_SEPARATOR)
            && is_file($filePath),
            404,
        );

        $contentType = match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            'css' => 'text/css; charset=UTF-8',
            'html' => 'text/html; charset=UTF-8',
            default => 'application/octet-stream',
        };

        return response()->file($filePath, [
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
