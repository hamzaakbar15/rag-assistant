<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use App\Models\Document;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

class SupportAssistant implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are a support assistant. Answer the user's question using ONLY
            information found via the search_documents tool. Always search
            before answering. If the search results don't contain the answer,
            say you don't have that information — do not make anything up.
            INSTRUCTIONS;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(
                model: Document::class,
                column: 'embedding',
                minSimilarity: 0.1,
            )->withDescription('Search the uploaded knowledge base documents for relevant information.'),
        ];
    }
}
