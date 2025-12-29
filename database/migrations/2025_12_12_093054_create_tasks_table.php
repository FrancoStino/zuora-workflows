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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');

            // Task identifier from Zuora (renamed from zuora_id for clarity)
            $table->string('task_id')->nullable()->index();

            // Basic task information
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('state')->nullable();

            // Zuora workflow data
            $table->string('action_type')->nullable();
            $table->string('object')->nullable();
            $table->string('object_id')->nullable(); // String to support template variables
            $table->string('call_type')->nullable();
            $table->bigInteger('next_task_id')->nullable()->comment('Reference to parent/next task ID');
            $table->string('priority')->default('Medium');
            $table->integer('concurrent_limit')->default(9999999);

            // Complex data stored as JSON
            $table->json('parameters')->nullable();
            $table->json('css')->nullable();
            $table->json('tags')->nullable();
            $table->json('assignment')->nullable();

            // Other Zuora fields
            $table->string('zuora_org_id')->nullable();
            $table->json('zuora_org_ids')->nullable();
            $table->bigInteger('subprocess_id')->nullable();

            // Timestamps
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
