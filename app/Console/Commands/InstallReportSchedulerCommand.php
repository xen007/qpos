<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallReportSchedulerCommand extends Command
{
    protected $signature = 'reports:install-scheduler';
    protected $description = 'Register the Windows QPOS scheduler for the current interactive user.';

    public function handle(): int
    {
        if (PHP_OS_FAMILY !== 'Windows') { $this->error('On this host configure artisan schedule:run every minute.'); return self::FAILURE; }
        $target = base_path('scripts/qpos-scheduler.vbs');
        $task = 'wscript.exe //B //Nologo "'.$target.'"';
        $process = proc_open(['schtasks.exe', '/Create', '/TN', 'QPOS-Phase5', '/SC', 'MINUTE', '/MO', '1', '/TR', $task, '/IT'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) return self::FAILURE;
        fclose($pipes[0]); $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        $code = proc_close($process);
        $this->line(trim($out.$err));
        return $code === 0 ? self::SUCCESS : self::FAILURE;
    }
}
