<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Settings\GeneralSettings;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class ChatService
{
    private const array ALLOWED_TABLES = ['workflows', 'tasks', 'customers'];

    private const array FORBIDDEN_KEYWORDS
        = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE',
            'CREATE', 'GRANT', 'REVOKE', 'REPLACE', 'LOAD', 'CALL', 'EXECUTE',
            'EXEC',
        ];

    private const int MAX_RESULTS = 100;

    private const string READONLY_CONNECTION = 'ai_chat_readonly';

    // Only special-case providers with non-standard auth/message format
    // All OpenAI-compatible providers fetch endpoints from models.dev API
    private const array PROVIDER_ENDPOINTS
        = [
            'anthropic' => 'https://api.anthropic.com/v1/messages',
            'google' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
        ];

    private ?string $connectionOverride = null;

    public function __construct(
        private readonly GeneralSettings $settings,
    ) {}

    public function setConnection(string $connection): self
    {
        $this->connectionOverride = $connection;

        return $this;
    }

    public function ask(ChatThread $thread, string $question): ChatMessage
    {
        $this->validateAiEnabled();

        $thread->messages()->create([
            'role' => 'user',
            'content' => $question,
        ]);

        $thread->generateTitleFromFirstMessage();

        return $this->generateAssistantResponse($thread, $question);
    }

    public function generateAssistantResponse(ChatThread $thread, string $question): ChatMessage
    {
        $this->validateAiEnabled();

        try {
            $sql = $this->generateSqlFromQuestion($thread, $question);
            $this->validateQuery($sql);
            $results = $this->executeQuery($sql);
            $response = $this->generateResponse($question, $sql,
                $results);

            return $thread->messages()->create([
                'role' => 'assistant',
                'content' => $response,
                'query_generated' => $sql,
                'query_results' => $results,
                'metadata' => [
                    'provider' => $this->settings->aiProvider,
                    'model' => $this->getModel(),
                    'results_count' => count($results),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('ChatService error', [
                'thread_id' => $thread->id,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            return $thread->messages()->create([
                'role' => 'assistant',
                'content' => $this->formatErrorResponse($e),
                'metadata' => [
                    'error' => true,
                    'error_message' => $e->getMessage(),
                ],
            ]);
        }
    }

    private function validateAiEnabled(): void
    {
        if (! $this->settings->aiChatEnabled) {
            throw new RuntimeException('AI Chat is not enabled. Enable it in settings.');
        }

        $apiKey = $this->getApiKey();
        $provider = $this->settings->aiProvider;

        if (empty($apiKey)) {
            $providerNames = [
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic',
                'google' => 'Google Gemini',
                'groq' => 'Groq',
                'mistral' => 'Mistral',
                'deepseek' => 'DeepSeek',
                'openrouter' => 'OpenRouter',
                'nvidia' => 'NVIDIA',
            ];
            $providerName = $providerNames[$provider] ?? $provider;
            throw new RuntimeException("API Key {$providerName} not configured. Configure it in settings.");
        }
    }

    private function getApiKey(): string
    {
        return $this->settings->aiApiKey;
    }

    private function generateSqlFromQuestion(
        ChatThread $thread,
        string $question,
    ): string {
        $messages = $this->buildConversationContext($thread, $question);

        $responseText = $this->callLlm($messages, 500, 0.1);

        return $this->extractSqlFromResponse($responseText);
    }

    private function buildConversationContext(
        ChatThread $thread,
        string $question,
    ): array {
        $messages = [
            [
                'role' => 'system',
                'content' => '',
            ],
        ];

        $messages[0]['content'] = $this->buildSystemPrompt();

        foreach (
            $thread->messages->where('role', '!=', 'system') as $msg
        ) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        return $messages;
    }

    private function buildSystemPrompt(): string
    {
        return "Your task is to convert natural language questions into valid SQL queries for MariaDB/MySQL.

## IMPORTANT RULES:
1. Generate ONLY valid SELECT queries
2. NEVER use INSERT, UPDATE, DELETE, DROP, ALTER or other modification operations
3. Use ONLY the tables listed below in database schema
4. Limit results to maximum 100 records
5. Respond ONLY with the SQL query, no explanations or comments
6. If you cannot generate a valid query, respond with: SELECT 'Query not generable' AS error

## DATABASE SCHEMA:
{$this->getDatabaseSchema()}

## EXAMPLES:
- \"How many workflows are there?\" → SELECT COUNT(*) AS total_workflows FROM workflows
- \"Show all active workflows\" → SELECT id, name, state, description FROM workflows WHERE state = 'Active' LIMIT 100
- \"Which tasks belong to workflow X?\" → SELECT t.id, t.name, t.action_type FROM tasks t JOIN workflows w ON t.workflow_id = w.id WHERE w.name LIKE '%X%' LIMIT 100
- \"Count tasks by action type\" → SELECT action_type, COUNT(*) AS count FROM tasks GROUP BY action_type ORDER BY count DESC

        Respond ONLY with the SQL query.
";
    }

    private function getModel(): string
    {
        return $this->settings->aiModel;
    }

    private function getDatabaseSchema(): string
    {
        return <<<'SCHEMA'
            ### Table: customers
            - id (bigint, PK)
            - name (varchar) - Customer name
            - zuora_client_id (varchar) - Zuora client ID
            - zuora_base_url (varchar) - Zuora API base URL
            - created_at, updated_at (timestamp)

            ### Table: workflows
            - id (bigint, PK)
            - customer_id (bigint, FK → customers.id)
            - name (varchar) - Workflow name
            - description (text) - Description
            - state (varchar) - State: 'Active', 'Inactive', 'Deleted'
            - zuora_id (varchar, UNIQUE) - Zuora workflow ID
            - last_synced_at (timestamp) - Last synchronization
            - json_export (longtext) - Workflow JSON export
            - created_on, updated_on (timestamp) - Zuora dates
            - created_at, updated_at (timestamp)

            ### Table: tasks
            - id (bigint, PK)
            - workflow_id (bigint, FK → workflows.id)
            - task_id (varchar) - Zuora task ID
            - name (varchar) - Task name
            - description (text) - Generic description
            - state (varchar) - Task state
            - action_type (varchar) - Action type: 'Email', 'Export', 'SOAP', 'REST', 'Logic', 'Iterate', etc.
            - object (varchar) - Zuora object involved
            - object_id (varchar) - Object ID
            - call_type (varchar) - Call type
            - next_task_id (bigint) - Next task
            - priority (varchar) - Priority: 'High', 'Medium', 'Low'
            - concurrent_limit (int) - Concurrency limit
            - parameters (json) - Task parameters
            - css (json) - Graph position CSS styles
            - tags (json) - Task tags
            - assignment (json) - Assignments
            - zuora_org_id (varchar) - Zuora org ID
            - zuora_org_ids (json) - List of org IDs
            - subprocess_id (bigint) - Subprocess ID
            - created_on, updated_on (timestamp)
            - created_at, updated_at (timestamp)

            ### RELATIONS:
            - workflows.customer_id → customers.id (N:1)
            - tasks.workflow_id → workflows.id (N:1)
            SCHEMA;
    }

    private function callLlm(
        array $messages,
        int $maxTokens,
        float $temperature,
    ): string {
        $provider = $this->settings->aiProvider;
        $model = $this->getModel();
        $apiKey = $this->getApiKey();

        if ($provider === 'anthropic') {
            return $this->callAnthropic($messages, $model, $apiKey, $maxTokens,
                $temperature);
        }

        if ($provider === 'google') {
            return $this->callGoogle($messages, $model, $apiKey, $maxTokens,
                $temperature);
        }

        return $this->callOpenAiCompatible($provider, $messages, $model,
            $apiKey, $maxTokens, $temperature);
    }

    private function callAnthropic(
        array $messages,
        string $model,
        string $apiKey,
        int $maxTokens,
        float $temperature,
    ): string {
        $systemPrompt = '';
        $anthropicMessages = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemPrompt = $message['content'];
            } else {
                $anthropicMessages[] = [
                    'role' => $message['role'],
                    'content' => $message['content'],
                ];
            }
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => $anthropicMessages,
        ];

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])
            ->timeout(120)
            ->post(self::PROVIDER_ENDPOINTS['anthropic'], $payload);

        if (! $response->successful()) {
            Log::error('Anthropic API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Anthropic API call error: '
                .$response->status());
        }

        $data = $response->json();

        return $data['content'][0]['text'] ?? '';
    }

    private function callGoogle(
        array $messages,
        string $model,
        string $apiKey,
        int $maxTokens,
        float $temperature,
    ): string {
        $endpoint = str_replace('{model}', $model,
            self::PROVIDER_ENDPOINTS['google']);
        $endpoint .= "?key={$apiKey}";

        $systemInstruction = '';
        $contents = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemInstruction = $message['content'];
            } else {
                $role = $message['role'] === 'assistant' ? 'model'
                    : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $message['content']]],
                ];
            }
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature' => $temperature,
            ],
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->timeout(120)
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            Log::error('Google API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Google API call error: '
                .$response->status());
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function callOpenAiCompatible(
        string $provider,
        array $messages,
        string $model,
        string $apiKey,
        int $maxTokens,
        float $temperature,
    ): string {
        // Get endpoint from models.dev for OpenAI-compatible providers
        $baseEndpoint = app(ModelsDevService::class)->getApiEndpoint($provider)
            ?? 'https://api.openai.com/v1';
        $endpoint = rtrim($baseEndpoint, '/').'/chat/completions';

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])
            ->timeout(120)
            ->post($endpoint, [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

        if (! $response->successful()) {
            Log::error('LLM API error', [
                'provider' => $provider,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('API call error: '.$response->status());
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }

    private function extractSqlFromResponse(string $content): string
    {
        // Remove thinking/reasoning blocks from models like DeepSeek, Minimax, etc.
        $content = $this->stripThinkingBlocks($content);

        // Remove markdown code blocks
        $content = preg_replace('/```sql\s*/i', '', $content);
        $content = preg_replace('/```\s*/', '', $content);

        $sql = trim($content);

        if (empty($sql)) {
            throw new InvalidArgumentException('No SQL query was generated.');
        }

        return $sql;
    }

    public function validateQuery(string $sql): void
    {
        $normalizedSql = strtoupper(trim($sql));

        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b'.$keyword.'\b/', $normalizedSql)) {
                throw new InvalidArgumentException(
                    "Query not allowed: operation {$keyword} is not permitted.",
                );
            }
        }

        if (! str_starts_with($normalizedSql, 'SELECT')) {
            throw new InvalidArgumentException(
                'Only SELECT queries are allowed.',
            );
        }

        $this->validateTablesInQuery($sql);
    }

    private function validateTablesInQuery(string $sql): void
    {
        $normalizedSql = strtolower($sql);

        preg_match_all('/\b(?:from|join)\s+[`"]?(\w+)[`"]?/i', $normalizedSql,
            $matches);

        $tablesUsed = $matches[1] ?? [];

        foreach ($tablesUsed as $table) {
            if (! in_array($table, self::ALLOWED_TABLES, true)) {
                throw new InvalidArgumentException(
                    "Table not allowed: '{$table}'. Available tables: "
                    .implode(', ', self::ALLOWED_TABLES),
                );
            }
        }
    }

    public function executeQuery(string $sql): array
    {
        if (! preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql = rtrim($sql, ';').' LIMIT '.self::MAX_RESULTS;
        }

        return DB::connection($this->getConnection())->select($sql);
    }

    private function getConnection(): string
    {
        return $this->connectionOverride ?? self::READONLY_CONNECTION;
    }

    private function generateResponse(
        string $question,
        string $sql,
        array $results,
    ): string {
        $resultsSummary = $this->summarizeResults($results);

        $prompt = <<<PROMPT
            Original question: {$question}

            SQL query executed:
            ```sql
            {$sql}
            ```

            Results found ({$resultsSummary['count']} records):
            ```json
            {$resultsSummary['preview']}
            ```

            Generate a clear and formatted response for the user. Use markdown for formatting.
            If there are many results, show a summary with the most relevant data.
            PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ];

        return $this->callLlm($messages, 1500, 0.3);
    }

    /**
     * Remove thinking/reasoning blocks from LLM responses.
     * Models like DeepSeek, Minimax, etc. may include <think>...</think> blocks.
     */
    private function stripThinkingBlocks(string $content): string
    {
        return trim(preg_replace('/<think>.*?<\/think>/s', '', $content));
    }

    private function summarizeResults(array $results): array
    {
        $count = count($results);
        $preview = array_slice($results, 0, 10);

        return [
            'count' => $count,
            'preview' => json_encode($preview,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }

    private function formatErrorResponse(Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Query not allowed')
            || str_contains($message, 'Only SELECT queries are allowed')
        ) {
            return "⚠️ **Operation not allowed**\n\n".
                "For security reasons, I can only execute read queries (SELECT) on the workflows, tasks, and customers tables.\n\n"
                .
                'Please rephrase your question as a data visualization request.';
        }

        if (str_contains($message, 'Table not allowed')) {
            return "⚠️ **Table not accessible**\n\n".
                "I can only access the following tables:\n".
                "- **workflows** - Synchronized Zuora workflows\n".
                "- **tasks** - Workflow tasks\n".
                "- **customers** - Configured customers\n\n".
                'Please rephrase your question using these tables.';
        }

        if (str_contains($message, 'SQLSTATE')) {
            return "⚠️ **Query error**\n\n".
                'The generated query is invalid. Please rephrase your question more specifically.';
        }

        return "⚠️ **An error occurred**\n\n".
            'I was unable to process your request. Please try rephrasing your question.';
    }
}
