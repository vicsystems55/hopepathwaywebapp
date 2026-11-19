<?php

namespace App\Console\Commands;

use App\Services\SupervisionArchiveStore;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ImportSupervisionArchive extends Command
{
    protected $signature = 'supervision:archive-import {file : Path to the prepared archive.json} {--dry-run : Validate without saving the archive}';

    protected $description = 'Import completed supervision records into private archive storage without changing live staff records';

    public function handle(SupervisionArchiveStore $archives): int
    {
        $path = realpath($this->argument('file'));
        if (!$path || !is_file($path) || !is_readable($path) || filesize($path) > SupervisionArchiveStore::MAX_BYTES) {
            $this->error('Provide a readable local archive.json file no larger than 5 MB.');
            return 1;
        }
        try {
            $archive = $archives->validateJson(file_get_contents($path));
            if (!$this->option('dry-run')) {
                $result = $archives->import($archive);
                $this->info($result['created'] ? 'Archive imported into private storage.' : 'Archive already exists; no records changed.');
            } else {
                $this->info('Archive validation passed. No files or database records changed.');
            }
            $this->line('Archive ID: ' . $archive['source']['sha256']);
            $this->line(sprintf('%d staff; %d completed sessions; %d saved answer rows.', $archive['counts']['staff'], $archive['counts']['completed_sessions'], $archive['counts']['answer_rows']));
            return 0;
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $errors) {
                foreach ($errors as $message) {
                    $this->error($message);
                }
            }
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
        }
        return 1;
    }
}
