<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InitializeSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:initialize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the setup process for a fresh installation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Initializing setup process...');

        try {
            // Create setup_completed table if it doesn't exist
            if (! Schema::hasTable('setup_completed')) {
                $this->info('Creating setup_completed table...');

                Schema::create('setup_completed', function ($table) {
                    $table->id();
                    $table->boolean('completed')->default(false);
                    $table->timestamp('completed_at')->nullable();
                    $table->timestamps();
                });

                // Insert initial record
                DB::table('setup_completed')->insert([
                    'completed' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->info('setup_completed table created successfully.');
            } else {
                $this->info('setup_completed table already exists.');
            }

            // Create app_settings table if it doesn't exist
            if (! Schema::hasTable('app_settings')) {
                $this->info('Creating app_settings table...');

                Schema::create('app_settings', function ($table) {
                    $table->id();
                    $table->string('key')->unique();
                    $table->json('value')->nullable();
                    $table->timestamps();
                });

                $this->info('app_settings table created successfully.');
            } else {
                $this->info('app_settings table already exists.');
            }

            $this->info('Setup initialization completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Setup initialization failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
