<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE documents DROP COLUMN embedding');
        DB::statement('ALTER TABLE documents ADD COLUMN embedding vector(768)');
        DB::statement('CREATE INDEX documents_embedding_idx ON documents USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE documents DROP COLUMN embedding');
        DB::statement('ALTER TABLE documents ADD COLUMN embedding vector(1536)');
    }
};
