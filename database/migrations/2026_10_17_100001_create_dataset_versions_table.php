<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->enum('source', ['synthetic', 'real']);
            $table->unsignedSmallInteger('cycle');
            $table->unsignedSmallInteger('prior_cycle')->nullable();
            $table->string('formula_version');
            $table->unsignedSmallInteger('schema_version');
            $table->dateTime('refreshed_at');
            $table->json('checksums');
            $table->json('reconciliation');
            $table->json('issues');
            $table->string('boundaries_path');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_versions');
    }
};
