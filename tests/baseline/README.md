# Performance & API Baseline Documentation

## Overview

This directory contains baseline performance metrics and API response snapshots captured during the migration from neuron-ai to laragent. These baselines serve as reference points for behavioral equivalence validation.

## Contents

### 1. Golden Query Snapshots (`*.snapshot.json`)

Each snapshot file contains:
- **query**: The exact SQL query executed
- **execution_time_ms**: Query execution time in milliseconds
- **results_count**: Number of rows returned
- **sql_logged**: Full query log from Laravel Query Builder
- **timestamp**: ISO 8601 timestamp of snapshot creation

**Total Scenarios**: 25 golden queries covering:
- Simple COUNT queries
- JOIN operations (INNER, LEFT, multi-table)
- Aggregations (COUNT, AVG, SUM, MIN, MAX)
- Complex WHERE clauses
- Subqueries and UNION
- Pagination and DISTINCT

### 2. Performance Benchmark Files

- **`api-performance.txt`**: Apache Bench results for API endpoint performance
- **`streaming-performance.txt`**: Streaming SSE endpoint benchmarks
- **`thread-response.json`**: API snapshot for ChatThread endpoint
- **`messages-response.json`**: API snapshot for ChatMessage endpoint

## Usage

### Running Golden Queries Test Suite

```bash
# Run all golden queries tests
lando artisan test --filter=GoldenQueriesTest

# Run specific golden query test
lando artisan test --filter=GoldenQueriesTest::test_simple_count_total_workflows

# Count test methods (should be 25+)
grep -c "public function test" tests/Feature/GoldenQueriesTest.php
```

### Validating API Snapshots

```bash
# Capture current API response
curl -s http://localhost:8000/api/chat/threads/123 | jq . > /tmp/current-thread.json

# Compare with baseline
diff tests/baseline/thread-response.json /tmp/current-thread.json

# Validate JSON schema
jq . tests/baseline/thread-response.json > /dev/null && echo "Valid JSON"
```

### Performance Benchmarking

```bash
# API endpoint benchmark (100 requests, concurrency 10)
ab -n 100 -c 10 http://localhost:8000/api/chat/threads > tests/baseline/api-performance.txt

# Streaming endpoint benchmark (50 requests, concurrency 5)
ab -n 50 -c 5 -p message.json http://localhost:8000/api/chat/threads/123/messages > tests/baseline/streaming-performance.txt

# Extract p95 latency
grep "95%:" tests/baseline/api-performance.txt
```

## Baseline Methodology

### Test Data Setup

Each test run uses controlled test data:
- **Users**: 1 test user (`Test User`, `test@example.com`)
- **Customers**: 1 customer (`Acme Corp` with test Zuora credentials)
- **Workflows**: 
  - 5 workflows with `state = 'Active'`
  - 3 workflows with `state = 'Draft'`
- **Tasks**: Random 2-5 tasks per workflow

### Snapshot Generation

Snapshots are automatically created by `GoldenQueriesTest::executeAndCapture()`:

1. Enable Laravel Query Log
2. Execute raw SQL query
3. Measure execution time (microseconds → milliseconds)
4. Capture query log metadata
5. Save to `tests/baseline/{scenario-name}.snapshot.json`

### Performance Metrics

**Acceptable Thresholds** (from migration plan):
- **p95 Latency**: ≤ +20% from baseline
- **Query Execution**: < 100ms for simple queries
- **API Response Time**: < 500ms (reasonable baseline)

## Validation During Migration

### Golden Query Validation (Task 9)

```bash
# Run with laragent provider
AI_PROVIDER=laragent php artisan test --filter=GoldenQueriesTest

# Compare execution time
# Expected: execution_time_ms within +20% of baseline
```

### API Contract Preservation

```bash
# Capture response from new system
curl -s http://localhost:8000/api/chat/threads/123 | jq . > laragent-thread-response.json

# Compare structure (not values, but schema)
diff <(jq -S 'keys' tests/baseline/thread-response.json) \
     <(jq -S 'keys' laragent-thread-response.json)

# Assert: Exit code 0 (identical structure)
```

## File Inventory

| File Pattern | Count | Purpose |
|--------------|-------|---------|
| `golden-query-*.snapshot.json` | 25 | SQL query baselines |
| `api-performance.txt` | 1 | Apache Bench API results |
| `streaming-performance.txt` | 1 | Apache Bench streaming results |
| `thread-response.json` | 1 | ChatThread API snapshot |
| `messages-response.json` | 1 | ChatMessage API snapshot |

## Maintenance

### Updating Baselines

⚠️ **CRITICAL**: Only update baselines when:
1. Intentional schema changes occur
2. Database structure is modified
3. API contract deliberately changes

**NEVER** modify snapshots to make failing tests pass!

```bash
# Safe baseline update process
1. Review all failing tests
2. Confirm changes are intentional
3. Re-run test suite to regenerate snapshots
4. Git diff to verify changes are expected
5. Commit with descriptive message
```

### Troubleshooting

**Missing Snapshot Files**:
```bash
# Regenerate all snapshots
php artisan test --filter=GoldenQueriesTest
ls -1 tests/baseline/*.snapshot.json | wc -l  # Should be 25
```

**Invalid JSON**:
```bash
# Validate all snapshots
for file in tests/baseline/*.snapshot.json; do
  echo "Checking $file..."
  jq . "$file" > /dev/null || echo "INVALID: $file"
done
```

**Performance Regression**:
```bash
# Check if execution times increased
jq '.execution_time_ms' tests/baseline/golden-query-*.snapshot.json | \
  awk '{sum+=$1; count++} END {print "Avg:", sum/count, "ms"}'
```

## Migration Acceptance Criteria

From `.sisyphus/plans/laragent-migration.md` Task 2:

- ✅ `grep -c "public function test" tests/Feature/GoldenQueriesTest.php` → Count ≥ 20
- ✅ `ls -1 tests/baseline/*.txt tests/baseline/*.json | wc -l` → Count ≥ 4
- ✅ `grep "95%:" tests/baseline/api-performance.txt` → p95 latency exists

## References

- **Test Suite**: `tests/Feature/GoldenQueriesTest.php` (25 tests, 35+ assertions)
- **Migration Plan**: `.sisyphus/plans/laragent-migration.md` (Task 2)
- **Pattern Reference**: `tests/Feature/NeuronChatServiceTest.php`
- **Data Models**: `app/Models/{ChatThread,ChatMessage,Workflow,Task}.php`

## Related Documentation

- [Laravel Testing](https://laravel.com/docs/testing)
- [Apache Bench](https://httpd.apache.org/docs/2.4/programs/ab.html)
- [JSON Schema Validation](https://json-schema.org/)
