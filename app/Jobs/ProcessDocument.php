<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Document;

class ProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Target chunk size and overlap, in characters.
     */
    protected int $chunkSize = 700;
    protected int $chunkOverlap = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public Document $document)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->document->update(['status' => 'processing']);

        try {
            $text = trim($this->extractText());

            if ($text === '') {
                throw new \RuntimeException('No text could be extracted from this document.');
            }

            $chunks = $this->chunkText($text);

            if (empty($chunks)) {
                throw new \RuntimeException('Text extraction succeeded but chunking produced no chunks.');
            }

            // Wipe any chunks from a previous processing attempt (e.g. reprocessing
            // a previously failed document) before inserting fresh ones.
            $this->document->chunks()->delete();

            foreach ($chunks as $index => $chunkContent) {
                $embedding = Str::of($chunkContent)->toEmbeddings();

                $this->document->chunks()->create([
                    'chunk_index' => $index,
                    'content' => $chunkContent,
                    'embedding' => $embedding,
                ]);
            }

            $this->document->update([
                'content' => $text,
                'status' => 'ready',
            ]);
        } catch (\Throwable $e) {
            $this->document->update(['status' => 'failed']);
            throw $e;
        }
    }

    protected function extractText(): string
    {
        $path = Storage::path($this->document->file_path);

        if (str_ends_with(strtolower($path), '.pdf')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);

            return $pdf->getText();
        }

        return Storage::get($this->document->file_path);
    }

    /**
     * Split text into fixed-size, overlapping chunks.
     *
     * Chunks break on whitespace where possible, rather than mid-word, so
     * search results read as complete phrases instead of truncated text.
     *
     * @return string[]
     */
    public function chunkText(string $text): array
    {
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $this->chunkSize, $length);

            // If we're not at the very end of the text, try to break on the
            // last whitespace before $end instead of cutting mid-word.
            if ($end < $length) {
                $slice = mb_substr($text, $start, $end - $start);
                $lastSpace = mb_strrpos($slice, ' ');

                if ($lastSpace !== false && $lastSpace > 0) {
                    $end = $start + $lastSpace;
                }
            }

            $chunk = trim(mb_substr($text, $start, $end - $start));

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            // Advance start, stepping back by the overlap amount so
            // consecutive chunks share trailing/leading context.
            $nextStart = $end - $this->chunkOverlap;

            // Guard against an infinite loop if overlap >= chunk size,
            // or if $end never advanced past $start (e.g. one giant "word").
            $start = $nextStart > $start ? $nextStart : $end;
        }

        return $chunks;
    }
}
