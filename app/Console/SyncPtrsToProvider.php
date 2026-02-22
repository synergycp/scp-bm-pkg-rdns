<?php

namespace Packages\Rdns\App\Console;

use Illuminate\Console\Command;
use Packages\Rdns\App\Ptr\Ptr;
use Packages\Rdns\App\Server\ServerService;

class SyncPtrsToProvider extends Command
{
    protected $signature = 'rdns:sync-to-dns';

    protected $description = 'Sync all existing PTR records to the configured DNS provider';

    public function handle(ServerService $serverService)
    {
        $provider = $serverService->get();
        $providerClass = get_class($provider);
        $this->info("Using DNS provider: {$providerClass}");

        $ptrs = Ptr::all();
        $total = $ptrs->count();

        if ($total === 0) {
            $this->info('No PTR records found.');
            return 0;
        }

        $this->info("Syncing {$total} PTR records...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($ptrs as $ptr) {
            try {
                $result = $provider->createPtr($ptr->ip, $ptr->ptr);
                $success++;

                if ($result) {
                    $bar->clear();
                    $this->line("  {$ptr->ip}: {$result}");
                    $bar->display();
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "{$ptr->ip}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Done. {$success} succeeded, {$failed} failed.");

        if (!empty($errors)) {
            $this->newLine();
            $this->warn('Failures:');
            foreach ($errors as $error) {
                $this->error("  {$error}");
            }
        }

        return $failed > 0 ? 1 : 0;
    }
}
