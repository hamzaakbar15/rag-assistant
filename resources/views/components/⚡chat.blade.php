<?php

use App\Ai\Agents\SupportAssistant;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:2000')]
    public string $message = '';

    public Collection $log;

    public bool $thinking = false;

    public function mount(): void
    {
        $this->log = collect();
    }

    public function send(): void
    {
        $this->validate();

        $this->log->push([
            'role' => 'user',
            'content' => $this->message,
        ]);

        $question = $this->message;
        // $this->message = '';
        $this->reset('message');
        $this->thinking = true;

        $this->dispatch('ask-agent', question: $question);
    }

    #[On('ask-agent')]
    public function ask(string $question): void
    {
        $response = (new SupportAssistant)->prompt(
            $question,
            timeout: 180,
        );

        $this->log->push([
            'role' => 'assistant',
            'content' => (string) $response,
        ]);

        $this->thinking = false;
    }
};
?>

<div class="max-w-2xl mx-auto px-4 py-12">
    <div class="bg-white p-6 shadow rounded-lg">

        <div class="mb-4 space-y-3 max-h-96 overflow-y-auto" id="chat-log">
            @forelse ($log as $entry)
                <div class="{{ $entry['role'] === 'user' ? 'text-right' : 'text-left' }}">
                    <span class="inline-block px-3 py-2 rounded {{ $entry['role'] === 'user' ? 'bg-gray-100' : 'bg-indigo-50' }}">
                        {{ $entry['content'] }}
                    </span>
                </div>
            @empty
                <p class="text-gray-400 text-sm">Ask a question about the uploaded documents to get started.</p>
            @endforelse

            @if ($thinking)
                <div class="text-left">
                    <span class="inline-block px-3 py-2 rounded bg-indigo-50 text-gray-400 italic">
                        Thinking...
                    </span>
                </div>
            @endif
        </div>

        <form wire:submit="send" class="flex gap-2">
            <input
                type="text"
                wire:model="message"
                autocomplete="off"
                wire:key="chat-input-{{ $log->count() }}"
                placeholder="Ask a question about your documents..."
                class="flex-1 border-gray-300 rounded"
                @if ($thinking) disabled @endif
            >
            <button
                type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded border border-indigo-800 disabled:opacity-50"
                @if ($thinking) disabled @endif
            >
                Send
            </button>
        </form>

        @error('message')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror

    </div>
</div>
