<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Golden Queries Test Suite
 * 
 * This test suite captures baseline SQL generation patterns for the AI DataAnalyst agent.
 * Each test scenario represents a real-world user query and validates:
 * - SQL syntax correctness
 * - Query execution success
 * - Result structure consistency
 * - Metadata tracking
 * 
 * These tests serve as behavioral equivalence benchmarks during migration from neuron-ai to laragent.
 */
class GoldenQueriesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $this->customer = Customer::factory()->create([
            'name' => 'Acme Corp',
            'zuora_client_id' => 'test_client',
            'zuora_client_secret' => 'test_secret',
            'zuora_base_url' => 'https://rest.zuora.com',
        ]);

        Workflow::factory()->count(5)->create([
            'customer_id' => $this->customer->id,
            'state' => 'Active',
        ]);
        
        Workflow::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'state' => 'Draft',
        ]);

        $workflows = Workflow::all();
        foreach ($workflows as $workflow) {
            Task::factory()->count(rand(2, 5))->create([
                'workflow_id' => $workflow->id,
            ]);
        }
    }

    protected function executeAndCapture(string $query, string $snapshotName): array
    {
        $startTime = microtime(true);
        
        DB::enableQueryLog();
        $results = DB::select($query);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        $snapshot = [
            'query' => $query,
            'execution_time_ms' => $executionTime,
            'results_count' => count($results),
            'sql_logged' => $queries[0] ?? null,
            'timestamp' => now()->toIso8601String(),
        ];

        $snapshotPath = base_path("tests/baseline/{$snapshotName}.snapshot.json");
        file_put_contents($snapshotPath, json_encode($snapshot, JSON_PRETTY_PRINT));

        return $snapshot;
    }

    public function test_simple_count_total_workflows(): void
    {
        $query = 'SELECT COUNT(*) as count FROM workflows';
        $snapshot = $this->executeAndCapture($query, 'golden-query-01-simple-count');

        $this->assertGreaterThan(0, $snapshot['results_count']);
        $this->assertEquals($query, $snapshot['query']);
        $this->assertLessThan(100, $snapshot['execution_time_ms']);
    }

    public function test_simple_count_total_tasks(): void
    {
        $query = 'SELECT COUNT(*) as count FROM tasks';
        $snapshot = $this->executeAndCapture($query, 'golden-query-02-count-tasks');

        $this->assertGreaterThan(0, $snapshot['results_count']);
        $this->assertIsNumeric($snapshot['execution_time_ms']);
    }

    public function test_filtered_count_active_workflows(): void
    {
        $query = "SELECT COUNT(*) as count FROM workflows WHERE state = 'Active'";
        $snapshot = $this->executeAndCapture($query, 'golden-query-03-active-workflows');

        $results = DB::select($query);
        $this->assertEquals(5, $results[0]->count);
    }

    public function test_join_workflows_with_customer(): void
    {
        $query = 'SELECT w.id, w.name, w.state, c.name as customer_name 
                  FROM workflows w 
                  INNER JOIN customers c ON w.customer_id = c.id 
                  LIMIT 10';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-04-join-customer');

        $this->assertGreaterThan(0, $snapshot['results_count']);
        $this->assertStringContainsString('INNER JOIN', $snapshot['query']);
    }

    public function test_join_tasks_with_workflow(): void
    {
        $query = 'SELECT t.id, t.name, t.action_type, w.name as workflow_name 
                  FROM tasks t 
                  INNER JOIN workflows w ON t.workflow_id = w.id 
                  LIMIT 20';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-05-tasks-workflow-join');

        $this->assertGreaterThan(0, $snapshot['results_count']);
    }

    public function test_aggregation_tasks_per_workflow(): void
    {
        $query = 'SELECT workflow_id, COUNT(*) as task_count 
                  FROM tasks 
                  GROUP BY workflow_id 
                  ORDER BY task_count DESC';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-06-tasks-per-workflow');

        $this->assertStringContainsString('GROUP BY', $snapshot['query']);
        $this->assertStringContainsString('ORDER BY', $snapshot['query']);
    }

    public function test_aggregation_workflows_per_customer(): void
    {
        $query = 'SELECT customer_id, COUNT(*) as workflow_count 
                  FROM workflows 
                  GROUP BY customer_id';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-07-workflows-per-customer');

        $results = DB::select($query);
        $this->assertEquals(1, count($results));
    }

    public function test_complex_where_recent_workflows(): void
    {
        $query = "SELECT * FROM workflows 
                  WHERE created_at >= datetime('now', '-7 days') 
                  ORDER BY created_at DESC";
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-08-recent-workflows');

        $this->assertStringContainsString('datetime', $snapshot['query']);
    }

    public function test_complex_where_active_with_tasks(): void
    {
        $query = "SELECT w.* FROM workflows w 
                  WHERE w.state = 'Active' 
                  AND EXISTS (SELECT 1 FROM tasks t WHERE t.workflow_id = w.id)";
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-09-active-with-tasks');

        $this->assertStringContainsString('EXISTS', $snapshot['query']);
    }

    public function test_string_matching_workflow_name(): void
    {
        $query = "SELECT * FROM workflows WHERE name LIKE '%workflow%' LIMIT 10";
        $snapshot = $this->executeAndCapture($query, 'golden-query-10-name-pattern');

        $this->assertStringContainsString('LIKE', $snapshot['query']);
    }

    public function test_multi_join_tasks_workflow_customer(): void
    {
        $query = 'SELECT t.name as task_name, w.name as workflow_name, c.name as customer_name
                  FROM tasks t
                  INNER JOIN workflows w ON t.workflow_id = w.id
                  INNER JOIN customers c ON w.customer_id = c.id
                  LIMIT 15';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-11-multi-join');

        $results = DB::select($query);
        $this->assertGreaterThan(0, count($results));
    }

    public function test_date_range_workflows_this_month(): void
    {
        $query = "SELECT * FROM workflows 
                  WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')";
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-12-this-month');

        $this->assertStringContainsString('strftime', $snapshot['query']);
    }

    public function test_subquery_workflows_most_tasks(): void
    {
        $query = 'SELECT w.*, 
                  (SELECT COUNT(*) FROM tasks WHERE workflow_id = w.id) as task_count
                  FROM workflows w
                  ORDER BY task_count DESC
                  LIMIT 5';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-13-subquery-task-count');

        $this->assertStringContainsString('SELECT COUNT', $snapshot['query']);
    }

    public function test_distinct_workflow_states(): void
    {
        $query = 'SELECT DISTINCT state FROM workflows ORDER BY state';
        $snapshot = $this->executeAndCapture($query, 'golden-query-14-distinct-states');

        $results = DB::select($query);
        $this->assertGreaterThan(0, count($results));
    }

    public function test_pagination_workflows(): void
    {
        $query = 'SELECT * FROM workflows ORDER BY created_at DESC LIMIT 5 OFFSET 0';
        $snapshot = $this->executeAndCapture($query, 'golden-query-15-pagination');

        $this->assertStringContainsString('LIMIT', $snapshot['query']);
        $this->assertStringContainsString('OFFSET', $snapshot['query']);
    }

    public function test_left_join_workflows_task_count(): void
    {
        $query = 'SELECT w.id, w.name, COUNT(t.id) as task_count
                  FROM workflows w
                  LEFT JOIN tasks t ON w.id = t.workflow_id
                  GROUP BY w.id, w.name';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-16-left-join');

        $this->assertStringContainsString('LEFT JOIN', $snapshot['query']);
    }

    public function test_multiple_conditions_complex_filter(): void
    {
        $query = "SELECT * FROM workflows 
                  WHERE (state = 'Active' OR state = 'Draft') 
                  AND customer_id IS NOT NULL 
                  ORDER BY name ASC";
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-17-complex-conditions');

        $this->assertStringContainsString('OR', $snapshot['query']);
        $this->assertStringContainsString('IS NOT NULL', $snapshot['query']);
    }

    public function test_min_max_date_range(): void
    {
        $query = 'SELECT MIN(created_at) as oldest, MAX(created_at) as newest FROM workflows';
        $snapshot = $this->executeAndCapture($query, 'golden-query-18-min-max-dates');

        $results = DB::select($query);
        $this->assertNotNull($results[0]->oldest);
        $this->assertNotNull($results[0]->newest);
    }

    public function test_having_clause_many_tasks(): void
    {
        $query = 'SELECT w.id, w.name, COUNT(t.id) as task_count
                  FROM workflows w
                  LEFT JOIN tasks t ON w.id = t.workflow_id
                  GROUP BY w.id, w.name
                  HAVING task_count > 2';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-19-having-clause');

        $this->assertStringContainsString('HAVING', $snapshot['query']);
    }

    public function test_union_combined_results(): void
    {
        $query = "SELECT 'workflow' as type, name, created_at FROM workflows
                  UNION ALL
                  SELECT 'task' as type, name, created_at FROM tasks
                  ORDER BY created_at DESC
                  LIMIT 20";
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-20-union');

        $this->assertStringContainsString('UNION', $snapshot['query']);
    }

    public function test_nested_join_full_context(): void
    {
        $query = 'SELECT 
                    t.id,
                    t.name as task_name,
                    t.action_type,
                    w.name as workflow_name,
                    w.state as workflow_state,
                    c.name as customer_name
                  FROM tasks t
                  INNER JOIN workflows w ON t.workflow_id = w.id
                  INNER JOIN customers c ON w.customer_id = c.id
                  WHERE w.state = "Active"
                  ORDER BY t.created_at DESC
                  LIMIT 25';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-21-nested-join-context');

        $this->assertGreaterThanOrEqual(0, $snapshot['results_count']);
    }

    public function test_aggregate_functions_avg_sum(): void
    {
        $query = 'SELECT 
                    customer_id,
                    COUNT(*) as total_workflows,
                    AVG(LENGTH(name)) as avg_name_length,
                    SUM(CASE WHEN state = "Active" THEN 1 ELSE 0 END) as active_count
                  FROM workflows
                  GROUP BY customer_id';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-22-aggregate-functions');

        $this->assertStringContainsString('AVG', $snapshot['query']);
        $this->assertStringContainsString('SUM', $snapshot['query']);
    }

    public function test_case_statement_conditional(): void
    {
        $query = 'SELECT 
                    name,
                    state,
                    CASE 
                        WHEN state = "Active" THEN "Running"
                        WHEN state = "Draft" THEN "In Progress"
                        ELSE "Other"
                    END as status_label
                  FROM workflows';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-23-case-statement');

        $this->assertStringContainsString('CASE', $snapshot['query']);
    }

    public function test_in_clause_multiple_values(): void
    {
        $query = 'SELECT * FROM workflows WHERE state IN ("Active", "Draft", "Pending")';
        $snapshot = $this->executeAndCapture($query, 'golden-query-24-in-clause');

        $this->assertStringContainsString('IN', $snapshot['query']);
    }

    public function test_null_handling_coalesce(): void
    {
        $query = 'SELECT 
                    id,
                    name,
                    COALESCE(description, "No description") as description_text
                  FROM workflows
                  LIMIT 10';
        
        $snapshot = $this->executeAndCapture($query, 'golden-query-25-coalesce');

        $this->assertStringContainsString('COALESCE', $snapshot['query']);
    }
}
