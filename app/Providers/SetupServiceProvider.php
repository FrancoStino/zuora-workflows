<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SetupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure setup tables exist during application boot
        try {
            if (Schema::hasTable('setup_completed')) {
                return;
            }

            // Create setup_completed table
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

        } catch (\Exception $e) {
            // Silently fail - tables may not exist during initial setup
        }
    }
}
