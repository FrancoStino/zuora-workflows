<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('workflows')) {
            Schema::create('workflows', function (Blueprint $table) {
                $table->id();

                // Customer relationship
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');

                // Basic workflow information
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('state')->nullable();

                // Zuora sync fields
                $table->string('zuora_id')->unique()->nullable();
                $table->timestamp('last_synced_at')->nullable();

                // JSON export field
                $table->json('json_export')->nullable();

                // Timestamps
                $table->timestamp('created_on')->nullable();
                $table->timestamp('updated_on')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
