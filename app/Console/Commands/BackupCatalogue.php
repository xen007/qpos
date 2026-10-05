<?php
namespace App\Console\Commands;

use App\Services\CatalogueFingerprint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupCatalogue extends Command
{
    protected $signature = 'qpos:catalogue-backup {directory}';
    protected $description = 'Create a protected catalogue conversion backup and prove its isolated restoration.';

    public function handle(CatalogueFingerprint $fingerprint): int
    {
        $directory = rtrim($this->argument('directory'), '/\\');
        $resolved = realpath($directory);
        $normalized = $resolved ? strtolower(str_replace('\\', '/', $resolved)) : '';
        $application = strtolower(str_replace('\\', '/', base_path()));
        $backupRoot = strtolower(str_replace('\\', '/', storage_path('app/backups')));
        $privateFallback = str_starts_with($normalized, $backupRoot.'/');
        if (!$resolved || !is_dir($resolved) || count(array_diff(scandir($resolved), ['.', '..'])) !== 0
            || ($normalized === $application || (str_starts_with($normalized, $application.'/') && !$privateFallback))) {
            $this->error('Use an empty protected directory outside the application, or under storage/app/backups.');
            return self::FAILURE;
        }
        $connection = DB::connection();
        if ($privateFallback && file_put_contents(storage_path('app/backups/.htaccess'), "Require all denied\n") === false) {
            $this->error('Cannot protect the private fallback backup directory.');
            return self::FAILURE;
        }
        $config = $connection->getConfig();
        if ($connection->getDriverName() !== 'mysql') { $this->error('This command requires MariaDB/MySQL.'); return self::FAILURE; }
        $sqlPath = $directory.'/database.sql';
        $credentials = ['--host='.$config['host'], '--port='.($config['port'] ?? 3306), '--user='.$config['username']];
        $env = ['MYSQL_PWD' => (string) $config['password']];
        $source = $fingerprint->snapshot($connection);
        $restorationSource = $fingerprint->snapshot($connection, null, true);
        $dump = new Process(array_merge(['C:/xampp/mysql/bin/mysqldump.exe'], $credentials,
            ['--single-transaction', '--routines', '--triggers', '--events', '--hex-blob', '--default-character-set=utf8mb4', '--result-file='.$sqlPath, $config['database']]), base_path(), $env);
        $dump->setTimeout(600);
        if ($dump->run() !== 0) { $this->error('SQL backup failed; inspect the protected backup directory.'); return self::FAILURE; }
        $probe = 'qpos_phase2_probe_'.date('Ymd_His').'_'.bin2hex(random_bytes(4));
        $connection->statement('CREATE DATABASE `'.$probe.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $restore = new Process(array_merge(['C:/xampp/mysql/bin/mysql.exe'], $credentials, ['--default-character-set=utf8mb4', $probe]), base_path(), $env);
        $restore->setTimeout(600);
        $input = fopen($sqlPath, 'rb');
        $restore->setInput($input);
        $ok = $restore->run() === 0;
        fclose($input);
        if (!$ok) { $this->error('Isolated restoration failed. Source has not been converted.'); return self::FAILURE; }
        config(['database.connections.catalogue_probe' => array_merge($config, ['database' => $probe])]);
        DB::purge('catalogue_probe');
        if ($fingerprint->snapshot(DB::connection('catalogue_probe'), $restorationSource) !== $restorationSource
            || $fingerprint->snapshot($connection, $source) !== $source) {
            $this->error('Restoration fingerprints do not match. Conversion is blocked.'); return self::FAILURE;
        }
        $zip = new ZipArchive;
        if ($zip->open($directory.'/files.zip', ZipArchive::CREATE | ZipArchive::EXCL) !== true) { throw new \RuntimeException('Cannot create files archive.'); }
        $files = [];
        $paths = ['.env', 'app', 'config', 'database', 'resources', 'routes', 'docs', 'lang', 'scripts', 'storage',
            'public/media', 'public/assets/images/logo', 'public/build', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'vite.config.js'];
        foreach ($paths as $relative) {
            $path = base_path($relative);
            if (!file_exists($path) || is_link($path)) { continue; }
            $entries = is_dir($path) ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) : [new \SplFileInfo($path)];
            foreach ($entries as $file) {
                if (!$file->isFile() || $file->isLink()) { continue; }
                // Backups contain secrets and must never recursively archive themselves.
                $filePath = strtolower(str_replace('\\', '/', $file->getPathname()));
                if (str_starts_with($filePath, $backupRoot.'/')) { continue; }
                $name = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                $files[$name] = hash_file('sha256', $file->getPathname());
                if (!$zip->addFile($file->getPathname(), $name)) { throw new \RuntimeException('Cannot archive '.$name); }
            }
        }
        if (!$zip->close()) { throw new \RuntimeException('Files archive failed.'); }
        $check = new ZipArchive;
        if ($check->open($directory.'/files.zip') !== true || !$check->extractTo($directory.'/restore-check')) { throw new \RuntimeException('Files restoration failed.'); }
        $check->close();
        foreach ($files as $name => $hash) {
            if (!hash_equals($hash, hash_file('sha256', $directory.'/restore-check/'.$name))) { throw new \RuntimeException('Files fingerprint mismatch.'); }
        }
        $git = new Process(['git', 'rev-parse', 'HEAD'], base_path());
        $git->run();
        $manifest = ['created_at' => now()->toIso8601String(), 'source_database' => $config['database'],
            'probe_database' => $probe, 'database_sha256' => hash_file('sha256', $sqlPath),
            'files_archive_sha256' => hash_file('sha256', $directory.'/files.zip'),
            'files' => $files, 'fingerprints' => $source, 'restoration_fingerprints' => $restorationSource, 'code_head' => trim($git->getOutput()),
            'restoration_verified' => true, 'verified_at' => now()->toIso8601String()];
        file_put_contents($directory.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->info('Backup and isolated restoration verified.');
        $this->line('Probe database: '.$probe);
        $this->line('Business tables: '.count($source).'; files: '.count($files));
        return self::SUCCESS;
    }
}
