<?php

use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('documentation is publicly accessible from the domain', function () {
    $response = $this->get('/docs')
        ->assertSuccessful()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->baseResponse)
        ->toBeInstanceOf(BinaryFileResponse::class)
        ->and($response->baseResponse->getFile()->getRealPath())
        ->toBe(realpath(base_path('docs/index.html')));
});

test('documentation pages and assets are served', function (string $path) {
    $response = $this->get("/docs/{$path}")->assertSuccessful();

    expect($response->baseResponse)
        ->toBeInstanceOf(BinaryFileResponse::class)
        ->and($response->baseResponse->getFile()->getRealPath())
        ->toBe(realpath(base_path("docs/{$path}")));
})->with([
    'architecture' => ['architecture.html'],
    'data model' => ['data-model.html'],
    'request flows' => ['request-flows.html'],
    'frontend and security' => ['frontend-security.html'],
    'operations' => ['operations.html'],
    'stylesheet' => ['assets/styles.css'],
]);

test('files outside the documentation directory cannot be served', function () {
    $this->get('/docs/not-found.html')->assertNotFound();
    $this->get('/docs/%2e%2e/.env')->assertNotFound();
});

test('all documentation links and styles resolve under the docs path', function () {
    $previousUseInternalErrors = libxml_use_internal_errors(true);

    try {
        foreach (glob(base_path('docs/*.html')) as $documentationFile) {
            $document = new DOMDocument;
            $document->loadHTMLFile($documentationFile, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $links = new DOMXPath($document)->query('//*[@href]');

            foreach ($links as $link) {
                $href = $link->getAttribute('href');

                if (str_starts_with($href, '#')) {
                    continue;
                }

                expect($href)->toStartWith('/docs');
                $this->get(explode('#', $href, 2)[0])->assertSuccessful();
            }
        }
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);
    }

    $this->get('/docs/assets/styles.css')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
});

test('documentation uses the Vazirmatn font', function () {
    $stylesheet = file_get_contents(base_path('docs/assets/styles.css'));

    expect($stylesheet)
        ->toContain('family=Vazirmatn')
        ->toContain('font-family: "Vazirmatn", Tahoma, Arial, sans-serif;');
});
