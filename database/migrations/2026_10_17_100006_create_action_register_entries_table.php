<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_register_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('planner_decision_id')->nullable()->constrained()->nullOnDelete();
            $table->string('psgc', 10);
            $table->foreign('psgc')->references('psgc')->on('municipalities');
            $table->string('action');
            $table->string('office')->default('PSHS-CRC Outreach');
            $table->enum('status', ['planned', 'completed'])->default('planned');
            $table->date('planned_on');
            $table->date('completed_on')->nullable();
            $table->string('next_review')->nullable();
            $table->timestamps();
            $table->unique(['dataset_version_id', 'psgc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_register_entries');
    }
};
