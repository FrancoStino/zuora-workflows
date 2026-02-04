<?php

namespace Tests\Feature;

use App\Models\ChatThread;
use App\Models\User;
use App\Services\ChatService;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $service;

    private GeneralSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = app(GeneralSettings::class);
        $this->service = (new ChatService($this->settings))->setConnection('sqlite');
    }

    public function test_validate_query_allows_select_queries(): void
    {
        $this->service->validateQuery('SELECT * FROM workflows');
        $this->service->validateQuery('SELECT id, name FROM tasks WHERE workflow_id = 1');
        $this->service->validateQuery('SELECT COUNT(*) FROM customers');

        $this->assertTrue(true);
    }

    public function test_validate_query_blocks_insert_statements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation INSERT is not permitted');

        $this->service->validateQuery('INSERT INTO workflows (name) VALUES (\'test\')');
    }

    public function test_validate_query_blocks_update_statements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation UPDATE is not permitted');

        $this->service->validateQuery('UPDATE workflows SET name = \'hacked\' WHERE id = 1');
    }

    public function test_validate_query_blocks_delete_statements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation DELETE is not permitted');

        $this->service->validateQuery('DELETE FROM workflows WHERE id = 1');
    }

    public function test_validate_query_blocks_drop_statements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation DROP is not permitted');

        $this->service->validateQuery('DROP TABLE workflows');
    }

    public function test_validate_query_blocks_truncate_statements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation TRUNCATE is not permitted');

        $this->service->validateQuery('TRUNCATE TABLE workflows');
    }

    public function test_validate_query_blocks_alter_statements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation ALTER is not permitted');

        $this->service->validateQuery('ALTER TABLE workflows ADD COLUMN hacked VARCHAR(255)');
    }

    public function test_validate_query_blocks_create_statements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation CREATE is not permitted');

        $this->service->validateQuery('CREATE TABLE hacked (id INT)');
    }

    public function test_validate_query_rejects_non_select_queries(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only SELECT queries are allowed');

        $this->service->validateQuery('SHOW TABLES');
    }

    public function test_validate_query_allows_only_whitelisted_tables(): void
    {
        $this->service->validateQuery('SELECT * FROM workflows');
        $this->service->validateQuery('SELECT * FROM tasks');
        $this->service->validateQuery('SELECT * FROM customers');

        $this->assertTrue(true);
    }

    public function test_validate_query_blocks_non_whitelisted_tables(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not allowed: 'users'");

        $this->service->validateQuery('SELECT * FROM users');
    }

    public function test_validate_query_blocks_join_with_non_whitelisted_tables(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not allowed: 'users'");

        $this->service->validateQuery('SELECT w.* FROM workflows w JOIN users u ON w.id = u.id');
    }

    public function test_validate_query_allows_join_between_whitelisted_tables(): void
    {
        $this->service->validateQuery('SELECT * FROM workflows JOIN tasks ON workflows.id = tasks.workflow_id');
        $this->service->validateQuery('SELECT * FROM customers c JOIN workflows w ON c.id = w.customer_id');

        $this->assertTrue(true);
    }

    public function test_execute_query_adds_limit_if_missing(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::factory()->for($user)->create();

        $results = $this->service->executeQuery('SELECT 1 AS test');

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function test_execute_query_respects_existing_limit(): void
    {
        $user = User::factory()->create();

        $results = $this->service->executeQuery('SELECT 1 AS test LIMIT 5');

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function test_ask_requires_ai_chat_enabled(): void
    {
        $this->settings->aiChatEnabled = false;

        $user = User::factory()->create();
        $thread = ChatThread::factory()->for($user)->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI Chat is not enabled');

        $this->service->ask($thread, 'test question');
    }

    public function test_ask_requires_api_key_configured(): void
    {
        $this->settings->aiChatEnabled = true;
        $this->settings->aiProvider = 'openai';
        $this->settings->aiApiKey = '';

        $user = User::factory()->create();
        $thread = ChatThread::factory()->for($user)->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Key OpenAI not configured');

        $this->service->ask($thread, 'test question');
    }

    public function test_ask_requires_api_key_when_provider_is_anthropic(): void
    {
        $this->settings->aiChatEnabled = true;
        $this->settings->aiProvider = 'anthropic';
        $this->settings->aiApiKey = '';

        $user = User::factory()->create();
        $thread = ChatThread::factory()->for($user)->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Key Anthropic not configured');

        $this->service->ask($thread, 'test question');
    }

    public function test_ask_requires_api_key_when_provider_is_google(): void
    {
        $this->settings->aiChatEnabled = true;
        $this->settings->aiProvider = 'google';
        $this->settings->aiApiKey = '';

        $user = User::factory()->create();
        $thread = ChatThread::factory()->for($user)->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Key Google Gemini not configured');

        $this->service->ask($thread, 'test question');
    }

    public function test_validate_query_blocks_sql_injection_attempts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation DROP is not permitted');

        $this->service->validateQuery('SELECT * FROM workflows; DROP TABLE workflows;--');
    }

    public function test_validate_query_blocks_union_with_forbidden_tables(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not allowed: 'users'");

        $this->service->validateQuery('SELECT * FROM workflows UNION SELECT * FROM users');
    }

    public function test_validate_query_case_insensitive_keyword_detection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('operation DELETE is not permitted');

        $this->service->validateQuery('select * from workflows where name = \'test\'; delete from workflows');
    }
}
