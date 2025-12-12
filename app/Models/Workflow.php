<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = [
        'customer_id',
        'zuora_id',
        'name',
        'description',
        'state',
        'created_on',
        'updated_on',
        'last_synced_at',
        'json_export',
    ];

    protected $casts = [
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'last_synced_at' => 'datetime',
        'json_export' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Popola i task dal JSON export del workflow
     *
     * @return int Numero di task creati/aggiornati
     */
    public function syncTasksFromJson(): int
    {
        if (! $this->json_export || ! isset($this->json_export['tasks'])) {
            return 0;
        }

        $tasksData = $this->json_export['tasks'];
        $syncedCount = 0;

        foreach ($tasksData as $taskData) {
            // Usa zuora_id come chiave univoca per evitare duplicati
            $this->tasks()->updateOrCreate(
                [
                    'workflow_id' => $this->id,
                    'zuora_id' => $taskData['id'] ?? null,
                ],
                [
                    'name' => $taskData['name'] ?? 'Unnamed Task',
                    'description' => $this->generateTaskDescription($taskData),
                    'action_type' => $taskData['action_type'] ?? null,
                    'object' => $taskData['object'] ?? null,
                    'object_id' => $taskData['object_id'] ?? null,
                    'call_type' => $taskData['call_type'] ?? null,
                    'task_id' => $taskData['task_id'] ?? null,
                    'priority' => $taskData['priority'] ?? 'Medium',
                    'concurrent_limit' => $taskData['concurrent_limit'] ?? 9999999,
                    'parameters' => $taskData['parameters'] ?? null,
                    'css' => $taskData['css'] ?? null,
                    'tags' => $taskData['tags'] ?? [],
                    'assignment' => $taskData['assignment'] ?? [],
                    'zuora_org_id' => $taskData['zuora_org_id'] ?? null,
                    'zuora_org_ids' => $taskData['zuora_org_ids'] ?? [],
                    'subprocess_id' => $taskData['subprocess_id'] ?? null,
                    'state' => 'pending', // Default state
                ]
            );
            $syncedCount++;
        }

        return $syncedCount;
    }

    /**
     * Genera una descrizione leggibile per il task
     */
    private function generateTaskDescription(array $taskData): ?string
    {
        $parts = [];

        if (! empty($taskData['action_type'])) {
            $parts[] = "Tipo: {$taskData['action_type']}";
        }

        if (! empty($taskData['object'])) {
            $parts[] = "Oggetto: {$taskData['object']}";
        }

        if (! empty($taskData['call_type'])) {
            $parts[] = "Call: {$taskData['call_type']}";
        }

        // Aggiungi informazioni dai parametri se esistono
        if (! empty($taskData['parameters'])) {
            $params = $taskData['parameters'];

            // Per task Email
            if (isset($params['email']['subject'])) {
                $parts[] = "Subject: {$params['email']['subject']}";
            }

            // Per task Export
            if (isset($params['where_clause'])) {
                $parts[] = 'Where: '.substr($params['where_clause'], 0, 50).'...';
            }
        }

        return ! empty($parts) ? implode(' | ', $parts) : null;
    }
}
