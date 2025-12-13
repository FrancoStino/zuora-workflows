<?php

namespace App\Filament\Concerns;

use CodebarAg\FilamentJsonField\Infolists\Components\JsonEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

trait HasTaskInfolist
{
    protected function getTaskInfolistSchema(): array
    {
        return [
            Section::make('General Information')
                ->columnSpanFull()
                ->icon('heroicon-o-information-circle')
                ->collapsible()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('task_id')
                                ->label('Task ID')
                                ->icon('heroicon-o-hashtag')
                                ->copyable()
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('action_type')
                                ->label('Action Type')
                                ->icon('heroicon-o-bolt')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'Email', 'Query' => 'info',
                                    'Export' => 'success',
                                    'Iterate', 'WriteOff' => 'warning',
                                    'SOAP' => 'primary',
                                    'Cancel' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('priority')
                                ->label('Priority')
                                ->icon('heroicon-o-flag')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'High' => 'danger',
                                    'Medium' => 'warning',
                                    'Low' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('object')
                                ->label('Object')
                                ->icon('heroicon-o-cube')
                                ->placeholder('N/A'),

                            TextEntry::make('object_id')
                                ->label('Object ID')
                                ->icon('heroicon-o-key')
                                ->copyable()
                                ->placeholder('N/A'),

                            TextEntry::make('call_type')
                                ->label('Call Type')
                                ->icon('heroicon-o-arrow-path')
                                ->badge()
                                ->placeholder('N/A'),

                            TextEntry::make('next_task_id')
                                ->label('Next Task ID')
                                ->icon('heroicon-o-link')
                                ->placeholder('N/A'),

                            TextEntry::make('concurrent_limit')
                                ->label('Concurrent Limit')
                                ->icon('heroicon-o-server')
                                ->numeric()
                                ->default(9999999),

                            TextEntry::make('state')
                                ->label('State')
                                ->icon('heroicon-o-signal')
                                ->badge()
                                ->color(fn (?string $state): string => match ($state) {
                                    'completed' => 'success',
                                    'in_progress' => 'warning',
                                    default => 'gray',
                                })
                                ->placeholder('N/A'),
                        ]),
                ]),

            Section::make('Parameters')
                ->columnSpanFull()
                ->icon('heroicon-o-cog-6-tooth')
                ->description('Complete task configuration')
                ->visible(fn ($record) => ! empty($record->parameters))
                ->collapsible()
                ->collapsed()
                ->schema([
                    JsonEntry::make('parameters')
                        ->hiddenLabel()
                        ->darkTheme(),
                ]),

            Section::make('CSS Position')
                ->icon('heroicon-o-map-pin')
                ->description('Task coordinates in workflow graph')
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record) => ! empty($record->css))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('css.top')
                                ->label('Top')
                                ->icon('heroicon-o-arrow-up'),

                            TextEntry::make('css.left')
                                ->label('Left')
                                ->icon('heroicon-o-arrow-left'),
                        ]),
                ]),

            Section::make('Tags & Assignments')
                ->icon('heroicon-o-tag')
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record) => ! empty($record->tags) || ! empty($record->assignment))
                ->schema([
                    TextEntry::make('tags')
                        ->label('Tags')
                        ->badge()
                        ->separator(',')
                        ->visible(fn ($record) => ! empty($record->tags))
                        ->placeholder('No tags'),

                    JsonEntry::make('assignment')
                        ->label('Assignments')
                        ->darkTheme()
                        ->visible(fn ($record) => ! empty($record->assignment)),
                ]),

            Section::make('Timestamps')
                ->description('Task creation and update timestamps')
                ->icon('heroicon-o-clock')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('created_on')
                                ->label('Created On')
                                ->icon('heroicon-o-calendar')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('N/A'),

                            TextEntry::make('updated_on')
                                ->label('Updated On')
                                ->icon('heroicon-o-calendar')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('N/A'),

                            TextEntry::make('created_at')
                                ->label('Created in DB')
                                ->icon('heroicon-o-calendar-days')
                                ->dateTime('d/m/Y H:i'),

                            TextEntry::make('updated_at')
                                ->label('Updated in DB')
                                ->icon('heroicon-o-calendar-days')
                                ->dateTime('d/m/Y H:i'),
                        ]),
                ]),
        ];
    }
}
