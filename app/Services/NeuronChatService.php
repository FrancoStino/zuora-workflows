<?php

namespace App\Services;

use App\AI\DataAnalystAgent;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Settings\GeneralSettings;
use App\Support\LoggedPDO;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\UserMessage;

class NeuronChatService
{
    private ?LoggedPDO $pdo = null;

    public function __construct(
        private readonly GeneralSettings $settings,
    ) {}

    public function ask(ChatThread $thread, string $question): ChatMessage
    {
        if (! $this->settings->aiChatEnabled) {
            throw new \RuntimeException('AI chat is not enabled');
        }

        $this->pdo = $this->createLoggedPdo();
        $agent = $this->getAgent();

        try {
            $response = $agent->chat(new UserMessage($question));
            $content = $response->getContent();

            $queryGenerated = $this->pdo->getLastQuery();

            return $thread->messages()->create([
                'role' => 'assistant',
                'content' => $content,
                'query_generated' => $queryGenerated,
                'metadata' => [
                    'provider' => $this->settings->aiProvider,
                    'model' => $this->settings->aiModel,
                    'results_count' => count($this->pdo->log),
                    'query_generated' => $queryGenerated,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('NeuronChatService error', [
                'thread_id' => $thread->id,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            return $thread->messages()->create([
                'role' => 'assistant',
                'content' => 'Error: '.$e->getMessage(),
                'metadata' => [
                    'error' => true,
                    'error_message' => $e->getMessage(),
                ],
            ]);
        }
    }

    public function askStream(ChatThread $thread, string $question): \Generator
    {
        if (! $this->settings->aiChatEnabled) {
            throw new \RuntimeException('AI chat is not enabled');
        }

        // Headers anti-buffering
        header('X-Accel-Buffering: no');
        header('Cache-Control: no-cache');
        header('Content-Type: text/event-stream');

        $this->pdo = $this->createLoggedPdo();
        $agent = $this->getAgent();

        $fullResponse = '';

        try {
            foreach ($agent->stream(new UserMessage($question)) as $chunk) {
                $fullResponse .= $chunk;
                yield $chunk;
            }

            // Salva messaggio completo alla fine
            $queryGenerated = $this->pdo->getLastQuery();

            $thread->messages()->create([
                'role' => 'assistant',
                'content' => $fullResponse,
                'query_generated' => $queryGenerated,
                'metadata' => [
                    'provider' => $this->settings->aiProvider,
                    'model' => $this->settings->aiModel,
                    'streaming' => true,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('NeuronChatService streaming error', [
                'thread_id' => $thread->id,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw per permettere fallback nel componente Livewire
        }
    }

    protected function getAgent(): DataAnalystAgent
    {
        return new DataAnalystAgent($this->pdo);
    }

    public function getQueryLog(): array
    {
        return $this->pdo?->log ?? [];
    }

    public function clearQueryLog(): void
    {
        $this->pdo?->clearLog();
    }

    private function createLoggedPdo(): LoggedPDO
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($connection === 'sqlite') {
            $dsn = "sqlite:{$config['database']}";
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                $config['host'],
                $config['port'] ?? 3306,
                $config['database']
            );
        }

        return new LoggedPDO($dsn, $config['username'] ?? '', $config['password'] ?? '');
    }
}
