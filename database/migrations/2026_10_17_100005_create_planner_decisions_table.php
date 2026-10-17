<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('psgc', 10);
            $table->foreign('psgc')->references('psgc')->on('municipalities');
            $table->enum('status', ['accepted', 'deferred']);
            $table->string('reason');
            $table->json('weights');
            $table->timestamps();
            $table->unique(['dataset_version_id', 'psgc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_decisions');
    }
};
