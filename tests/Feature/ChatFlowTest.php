<?php

namespace Tests\Feature;

use App\Ai\Agents\SupportAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ChatFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeChatResponse(string $answer): void
    {
        Http::fake([
            'localhost:11434/api/chat*' => Http::response([
                'message' => ['role' => 'assistant', 'content' => $answer],
                'done' => true,
            ], 200),
        ]);
    }

    public function test_sending_a_message_adds_it_to_the_log_immediately(): void
    {
        $this->fakeChatResponse('This is a test answer.');

        Livewire::test('chat')
            ->set('message', 'How many years of experience does he have?')
            ->call('send')
            ->assertSet('message', '')
            ->assertSee('How many years of experience does he have?');
    }

    public function test_asking_the_agent_appends_the_assistant_response_to_the_log(): void
    {
        $this->fakeChatResponse('He has 5 years of experience.');

        Livewire::test('chat')
            ->set('message', 'How many years of experience does he have?')
            ->call('send')
            ->call('ask', 'How many years of experience does he have?')
            ->assertSet('thinking', false)
            ->assertSee('He has 5 years of experience.');
    }

    public function test_an_empty_message_fails_validation_and_is_not_sent(): void
    {
        Livewire::test('chat')
            ->set('message', '')
            ->call('send')
            ->assertHasErrors('message');
    }
}