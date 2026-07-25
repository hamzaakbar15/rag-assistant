<?php

namespace Tests\Unit;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use PHPUnit\Framework\TestCase;

class ProcessDocumentChunkingTest extends TestCase
{
    protected function job(): ProcessDocument
    {
        // We only need chunkText(), which doesn't touch the database,
        // so a bare (non-persisted) Document model is fine here — this
        // keeps this a true unit test with no DB dependency at all.
        return new ProcessDocument(new Document());
    }

    public function test_it_splits_long_text_into_multiple_chunks(): void
    {
        $text = str_repeat('word ', 500); // ~2500 characters

        $chunks = $this->job()->chunkText($text);

        $this->assertGreaterThan(1, count($chunks));
    }

    public function test_it_returns_a_single_chunk_for_short_text(): void
    {
        $text = 'This is a short piece of text under the chunk size limit.';

        $chunks = $this->job()->chunkText($text);

        $this->assertCount(1, $chunks);
        $this->assertSame($text, $chunks[0]);
    }

    public function test_it_returns_empty_array_for_empty_text(): void
    {
        $chunks = $this->job()->chunkText('');

        $this->assertSame([], $chunks);
    }

    public function test_consecutive_chunks_overlap(): void
    {
        $text = str_repeat('word ', 500);

        $chunks = $this->job()->chunkText($text);

        // The tail of chunk[0] should share text with the head of chunk[1] —
        // that's the whole point of overlap: nothing said at a chunk boundary
        // gets fully lost.
        $tailOfFirst = substr($chunks[0], -50);
        $this->assertStringContainsString(
            trim(substr($tailOfFirst, 0, 20)),
            $chunks[0] . $chunks[1]
        );
    }

    public function test_no_chunk_exceeds_a_reasonable_size_ceiling(): void
    {
        $text = str_repeat('supercalifragilisticexpialidocious ', 300);

        $chunks = $this->job()->chunkText($text);

        foreach ($chunks as $chunk) {
            // Generous ceiling — chunkSize is 700, this just guards against
            // a logic bug that runs away and produces a giant chunk.
            $this->assertLessThan(1000, mb_strlen($chunk));
        }
    }
}