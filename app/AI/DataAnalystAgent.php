<?php

namespace App\AI;

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\Toolkits\MySQL\MySQLSchemaTool;
use NeuronAI\Tools\Toolkits\MySQL\MySQLSelectTool;
use PDO;

class DataAnalystAgent extends Agent
{
    public function __construct(protected ?PDO $pdo = null)
    {
        $this->pdo ??= DB::connection()->getPdo();
    }

    protected function provider(): AIProviderInterface
    {
        $settings = app(GeneralSettings::class);

        return new OpenAI(
            key: $settings->aiApiKey,
            model: $settings->aiModel,
        );
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a data analyst. Analyze database queries and provide insights.',
            ]
        );
    }

    protected function tools(): array
    {
        return [
            MySQLSchemaTool::make($this->pdo),
            MySQLSelectTool::make($this->pdo),
        ];
    }
}
