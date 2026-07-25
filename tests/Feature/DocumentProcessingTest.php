<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentProcessingTest extends TestCase
{
    use RefreshDatabase;

   protected function fakeEmbeddingResponses(): void
    {
        Http::fake([
            'localhost:11434/api/embed*' => Http::response([
                'model' => 'nomic-embed-text',
                'embeddings' => [array_fill(0, 768, 0.01)],
                'total_duration' => 100000000,
                'load_duration' => 50000000,
                'prompt_eval_count' => 3,
            ], 200),
        ]);
    }

    public function test_processing_a_document_creates_chunks_with_embeddings(): void
    {
        $this->fakeEmbeddingResponses();
        Storage::fake('local');

        $user = User::factory()->create();

        Storage::put('documents/sample.txt', str_repeat('This is sample content. ', 200));

        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Sample Document',
            'file_path' => 'documents/sample.txt',
            'status' => 'pending',
        ]);

        (new ProcessDocument($document))->handle();

        $document->refresh();

        $this->assertSame('ready', $document->status);
        $this->assertNotNull($document->content);
        $this->assertGreaterThan(0, $document->chunks()->count());

        // Every chunk should have gotten an embedding, and chunk_index
        // should be sequential starting from 0 — both are easy to silently
        // break (e.g. the mass-assignment bug you hit with documents.embedding).
        $document->chunks->each(function ($chunk, $index) {
            $this->assertNotNull($chunk->embedding);
            $this->assertSame($index, $chunk->chunk_index);
        });
    }

    public function test_a_document_that_fails_extraction_is_marked_failed(): void
    {
        $this->fakeEmbeddingResponses();
        Storage::fake('local');

        $user = User::factory()->create();

        // Empty file content — should trigger the "No text could be
        // extracted" exception path in the job.
        Storage::put('documents/empty.txt', '');

        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Empty Document',
            'file_path' => 'documents/empty.txt',
            'status' => 'pending',
        ]);

        try {
            (new ProcessDocument($document))->handle();
        } catch (\RuntimeException $e) {
            // Expected — the job rethrows after marking as failed.
        }

        $document->refresh();

        $this->assertSame('failed', $document->status);
        $this->assertSame(0, $document->chunks()->count());
    }

    public function test_reprocessing_a_document_replaces_old_chunks_instead_of_duplicating(): void
    {
        $this->fakeEmbeddingResponses();
        Storage::fake('local');

        $user = User::factory()->create();
        Storage::put('documents/sample.txt', str_repeat('Some content here. ', 100));

        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Sample Document',
            'file_path' => 'documents/sample.txt',
            'status' => 'pending',
        ]);

        (new ProcessDocument($document))->handle();
        $firstRunChunkCount = $document->chunks()->count();

        (new ProcessDocument($document->fresh()))->handle();
        $secondRunChunkCount = $document->fresh()->chunks()->count();

        $this->assertSame($firstRunChunkCount, $secondRunChunkCount);
    }
}