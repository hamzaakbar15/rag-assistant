# RAG Assistant

A support assistant web app where an admin uploads a company's own documents (FAQs, policies, manuals, resumes, etc.), and any visitor — no login required — can ask natural-language questions and get answers grounded in that content, instead of a generic AI that might hallucinate wrong information.

Built as a learning/portfolio project to demonstrate practical **Retrieval-Augmented Generation (RAG)** skills for Laravel + AI roles.

## What it does

1. An admin logs into a Filament panel and uploads a document (PDF or text).
2. The document is processed in the background: text is extracted, split into overlapping chunks, and each chunk is embedded using a local LLM.
3. Any visitor can open the public chat page and ask a question — no account needed.
4. The assistant performs a vector similarity search over the document chunks, and answers **only** from what it finds — or says it doesn't know, rather than making something up.

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13, PHP 8.4 |
| Frontend | Livewire 4 (single-file components), Blade |
| Admin panel | Filament v4 |
| Auth | Laravel Breeze (scoped to admin/document management only) |
| Database | PostgreSQL 16 + pgvector |
| LLM runtime | Ollama (local, containerized) — `llama3.2:3b` for chat, `nomic-embed-text` for embeddings |
| AI integration | `laravel/ai` (official Laravel AI SDK — agents, tools, embeddings) |
| PDF parsing | `smalot/pdfparser` (pure PHP, no system dependency) |
| Infra | Docker (Postgres + Ollama containers), app runs locally via `php artisan serve` |

## Architecture

```
Admin uploads PDF/txt (Filament)
        │
        ▼
ProcessDocument job
  ├─ Extract text (pdfparser / plain read)
  ├─ Split into ~700-char chunks, 120-char overlap
  └─ Embed each chunk (nomic-embed-text) → document_chunks table
        │
        ▼
Visitor asks a question on /chat (public, no auth)
        │
        ▼
Livewire chat component → SupportAssistant agent
  ├─ SimilaritySearch tool: vector search over document_chunks
  └─ llama3.2:3b answers using only retrieved chunks
```

### Why chunking instead of one embedding per document

Early version embedded each whole document as a single vector. This doesn't scale — long documents lose retrieval precision because one vector has to represent an entire document's meaning. Chunking splits text into ~700-character pieces with 120-character overlap (so no fact gets fully severed at a chunk boundary), and each chunk gets its own embedding. Retrieval then finds the *specific* passage relevant to a question, not just "this whole document seems related."

### Database schema (core tables)

- **documents** — `id`, `user_id`, `title`, `file_path`, `content`, `status` (`pending` → `processing` → `ready`/`failed`)
- **document_chunks** — `id`, `document_id`, `chunk_index`, `content`, `embedding` (`vector(768)`, HNSW cosine index)

## Getting started

### Prerequisites

- PHP 8.4+, Composer
- Node 22 LTS
- Docker Desktop

### Setup

```bash
git clone https://github.com/hamzaakbar15/rag-assistant.git
cd rag-assistant
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Start the Postgres (pgvector) and Ollama containers, then pull the required models:

```bash
docker exec -it rag_ollama ollama pull llama3.2:3b
docker exec -it rag_ollama ollama pull nomic-embed-text
```

Run migrations and start the app:

```bash
php artisan migrate
php artisan serve
php artisan queue:work
```

Visit `/admin` to log in and upload documents, or `/chat` to ask questions as a visitor.

## Running tests

```bash
php artisan migrate --env=testing
php artisan test
```

Tests run against a dedicated `rag_assistant_test` Postgres database (SQLite isn't usable here — it has no `vector` column type or pgvector support). Ollama calls are faked via `Http::fake()` against real captured response shapes from `/api/embed` and `/api/chat`, so the suite runs fast and doesn't require Ollama to be running.

> **Note:** the test suite is still being finalized — a few edge cases are being worked through.

## What's deliberately simplified

This is a learning project, not a production system. A few things are intentionally scoped down:

- **No `is_admin` role** — every registered user has admin access; a real deployment would need a proper role/permission system.
- **No per-user document scoping** — the knowledge base is shared and global by design (like a company support widget), not private per user.
- **One embedding model, swappable** — Ollama is the default; `config/ai.php` supports swapping to Claude/OpenAI via a single `.env` change (not yet tested end-to-end).

## Roadmap

- [x] Document upload → background processing pipeline
- [x] Public chat interface (Livewire)
- [x] Document chunking with overlap
- [x] Unit + feature test suite (in progress)
- [ ] Backfill/reprocess script for documents uploaded before chunking existed
- [ ] Provider swap test (Ollama → Claude/OpenAI)
- [ ] Demo GIF

## What I learned building this

This project involved working through a number of real, non-obvious bugs — documented here because the debugging process was as valuable as the feature itself:

- Vector dimension mismatches between embedding models (OpenAI's 1536 vs. `nomic-embed-text`'s 768) require dropping and recreating the column — you can't just change a config value.
- Eloquent silently drops attributes that aren't in `$fillable` during mass assignment — no error, just missing data.
- Livewire 4's single-file component convention (`⚡component.blade.php`) is structurally significant, not decorative — renaming or moving the file breaks it.
- Livewire's DOM diffing can skip updating an input's visual value if the old/new HTML is structurally identical (no `value` attribute ever set) — `wire:key` forces a full node replacement instead of a patch, fixing it.
- Faking HTTP calls in tests only helps if the faked response shape matches reality — worth capturing real API responses first (`curl`) rather than guessing.

## License

This is a personal learning/portfolio project. Feel free to explore the code.
