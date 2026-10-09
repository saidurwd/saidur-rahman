<?php

namespace App\Console\Commands;

use App\Services\LaunchChecks;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('launch:check {--strict : Treat warnings as failures too}')]
#[Description('Check that the site is ready to go live: environment, services, background processes, backups and content')]
class LaunchCheck extends Command
{
    private const MARKS = [LaunchChecks::OK => '<fg=green>✔</>', LaunchChecks::WARN => '<fg=yellow>!</>', LaunchChecks::FAIL => '<fg=red>✖</>'];

    public function handle(LaunchChecks $checks): int
    {
        $results = $checks->run();

        foreach ($results as $section => $items) {
            $this->newLine();
            $this->line("<options=bold>{$section}</>");

            foreach ($items as $item) {
                $this->line('  '.self::MARKS[$item['level']].' '.$item['name'].($item['detail'] !== '' ? " <fg=gray>— {$item['detail']}</>" : ''));
            }
        }

        $fails = LaunchChecks::count($results, LaunchChecks::FAIL);
        $warns = LaunchChecks::count($results, LaunchChecks::WARN);

        $this->newLine();
        $this->line($fails === 0
            ? '<fg=green>No blocking problems.</>'.($warns > 0 ? " {$warns} thing(s) worth a look." : ' Ready to launch.')
            : "<fg=red>{$fails} blocking problem(s)</> and {$warns} warning(s).");

        return $fails > 0 || ($this->option('strict') && $warns > 0) ? self::FAILURE : self::SUCCESS;
    }
}
