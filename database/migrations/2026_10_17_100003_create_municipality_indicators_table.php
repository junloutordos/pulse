<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')->constrained()->cascadeOnDelete();
            $table->string('psgc', 10);
            $table->foreign('psgc')->references('psgc')->on('municipalities');
            $table->unsignedInteger('applicants')->nullable();
            $table->unsignedInteger('examinees')->nullable();
            $table->unsignedInteger('qualifiers')->nullable();
            $table->unsignedInteger('grade6_pop')->nullable();
            $table->unsignedInteger('prior_applicants')->nullable();
            $table->double('rate_per_1000')->nullable();
            $table->double('attendance')->nullable();
            $table->double('change')->nullable();
            $table->enum('status', ['ok', 'low', 'suppressed', 'missing']);
            $table->string('lisa_cluster', 2)->nullable();
            $table->unique(['dataset_version_id', 'psgc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_indicators');
    }
};
