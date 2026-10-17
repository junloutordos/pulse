<?php

namespace App\Console\Commands;

use App\Services\Import\ChecksumMismatch;
use App\Services\Import\DatasetImporter;
use App\Services\Import\VersionConflict;
use Illuminate\Console\Command;

class PulseImport extends Command
{
    protected $signature = 'pulse:import {versionDir : Path to a pipeline version folder} {--activate : Make this the active dataset version} {--force : Replace an existing version that has different content}';

    protected $description = 'Import a versioned pipeline dataset into MySQL';

    public function handle(DatasetImporter $importer): int
    {
        $dir = realpath($this->argument('versionDir'));
        if ($dir === false) {
            $this->error('Version folder not found.');

            return self::FAILURE;
        }

        try {
            $version = $importer->import($dir, (bool) $this->option('activate'), (bool) $this->option('force'));
        } catch (ChecksumMismatch|VersionConflict $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Imported {$version->version} ({$version->source}, cycle {$version->cycle})".($version->is_active ? ' and activated it.' : '.'));

        return self::SUCCESS;
    }
}
