<?php

namespace App\Filament\Widgets;

use App\Models\Workflow;
use App\Services\ZuoraService;
use Exception;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class WorkflowJsonWidget extends Widget
{
    protected static bool $isDiscovered = false;

    public ?Workflow $workflow = null;

    protected string $view = 'filament.widgets.workflow-json-widget';

    protected int|string|array $columnSpan = 'full';

    public function mount(Workflow $workflow): void
    {
        $this->workflow = $workflow;
    }

    public function copyToClipboard(): void
    {
        $this->dispatch('copy-to-clipboard', json : $this->getJson());

        Notification::make()
            ->title('Copied!')
            ->body('JSON copied to clipboard successfully.')
            ->success()
            ->send();
    }

    public function getJson(): ?string
    {
        if (! $this->workflow || ! $this->workflow->customer) {
            return null;
        }

        try {
            $service = new ZuoraService;
            $workflowData = $service->downloadWorkflow(
                $this->workflow->customer->zuora_client_id,
                $this->workflow->customer->zuora_client_secret,
                $this->workflow->customer->zuora_base_url,
                $this->workflow->zuora_id
            );

            return json_encode($workflowData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            return null;
        }
    }

    protected function getViewData(): array
    {
        return [
            'json' => $this->getJson(),
            'error' => null,
        ];
    }
}
