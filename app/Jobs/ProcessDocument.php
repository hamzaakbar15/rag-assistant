<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
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
            $text = $this->extractText();

            $embedding = Str::of($text)->toEmbeddings();

            $this->document->update([
                'content' => $text,
                'embedding' => $embedding,
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
}
