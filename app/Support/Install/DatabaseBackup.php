<?php

namespace App\Support\Install;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Gzipped SQL dump of the application database, taken before an upgrade.
 * Uses mysqldump/mariadb-dump when installed (consistent snapshot, fast);
 * otherwise falls back to a pure-PHP dump so upgrades never run without a backup.
 */
class DatabaseBackup
{
    public string $method = '';

    public function __construct(private bool $useDumpTool = true) {}

    public function dump(string $directory, string $label = 'before-update'): string
    {
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create backup folder {$directory}");
        }
        $cfg = config('database.connections.mysql');
        $file = rtrim($directory, '/').'/'.$cfg['database'].'-'.now()->format('Ymd-His')."-{$label}.sql.gz";

        $binary = $this->useDumpTool ? ((new ExecutableFinder)->find('mysqldump') ?? (new ExecutableFinder)->find('mariadb-dump')) : null;
        $binary ? $this->withDumpTool($binary, $cfg, $file) : $this->withPhp($file);

        if (! is_file($file) || filesize($file) < 20) {
            throw new RuntimeException('The backup file is empty; the upgrade was stopped.');
        }
        @chmod($file, 0640);

        return $file;
    }

    private function withDumpTool(string $binary, array $cfg, string $file): void
    {
        $this->method = basename($binary);
        // Credentials go in a private options file, never on the command line (visible in `ps`).
        $opts = tempnam(sys_get_temp_dir(), 'dtdump');
        chmod($opts, 0600);
        file_put_contents($opts, "[client]\nuser=\"{$cfg['username']}\"\npassword=\"".addcslashes((string) $cfg['password'], '"\\')."\"\n"
            .($cfg['unix_socket'] ? "socket=\"{$cfg['unix_socket']}\"\n" : "host=\"{$cfg['host']}\"\nport={$cfg['port']}\n"));

        try {
            $process = new Process([$binary, "--defaults-extra-file={$opts}", '--single-transaction', '--quick',
                '--routines', '--triggers', '--no-tablespaces', '--default-character-set=utf8mb4', $cfg['database']]);
            $process->setTimeout(3600);
            $gz = gzopen($file, 'wb6');
            $process->run(function ($type, $buffer) use ($gz) {
                if ($type === Process::OUT) {
                    gzwrite($gz, $buffer);
                }
            });
            gzclose($gz);
            if (! $process->isSuccessful()) {
                @unlink($file);
                $this->method = '';
                // Fall back rather than upgrading without a backup.
                $this->withPhp($file);
            }
        } finally {
            @unlink($opts);
        }
    }

    private function withPhp(string $file): void
    {
        $this->method = 'php';
        $pdo = DB::connection()->getPdo();
        $gz = gzopen($file, 'wb6');
        gzwrite($gz, "-- Daily Table database backup (PHP dumper) ".now()->toIso8601String()."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(\PDO::FETCH_NUM) as [$table]) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM)[1];
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n");

            // Unbuffered so large tables stream instead of loading into memory.
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $stmt = $pdo->query("SELECT * FROM `{$table}`", \PDO::FETCH_ASSOC);
            $batch = [];
            foreach ($stmt as $row) {
                $batch[] = '('.implode(',', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)).')';
                if (count($batch) === 200) {
                    gzwrite($gz, "INSERT INTO `{$table}` VALUES ".implode(",\n", $batch).";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                gzwrite($gz, "INSERT INTO `{$table}` VALUES ".implode(",\n", $batch).";\n");
            }
            $stmt->closeCursor();
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            gzwrite($gz, "\n");
        }
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);
    }
}
