<?php

namespace App\Support\Install;

use InvalidArgumentException;
use PDO;

/**
 * Creates the Daily Table database and its application user using MySQL admin access
 * (root over the local socket on a fresh Linux install, or an admin user + password).
 * Safe to run repeatedly: everything is "IF NOT EXISTS", and an existing user's
 * password is only changed when explicitly asked.
 */
class DatabaseProvisioner
{
    /** @var list<string> */
    public array $log = [];

    public function __construct(private PDO $admin) {}

    /**
     * @param  array{host?: string, port?: int|string, socket?: string, user: string, password?: ?string}  $admin
     */
    public static function connect(array $admin): self
    {
        $dsn = ! empty($admin['socket'])
            ? "mysql:unix_socket={$admin['socket']};charset=utf8mb4"
            : 'mysql:host='.($admin['host'] ?? '127.0.0.1').';port='.($admin['port'] ?? 3306).';charset=utf8mb4';

        $pdo = new PDO($dsn, $admin['user'], $admin['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]);

        return new self($pdo);
    }

    public function serverVersion(): string
    {
        return (string) $this->admin->query('select version()')->fetchColumn();
    }

    /**
     * @param  list<string>  $hosts  hosts the app user may connect from
     */
    public function provision(string $database, string $user, string $password, array $hosts = ['localhost', '127.0.0.1'], bool $resetPassword = false): void
    {
        $this->assertIdentifier($database, 'database');
        $this->assertIdentifier($user, 'user');

        $this->admin->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->log[] = "Database `{$database}` ready";

        $pw = $this->admin->quote($password);
        foreach ($hosts as $host) {
            $h = $this->admin->quote($host);
            $u = $this->admin->quote($user);
            $exists = (bool) $this->admin->query("SELECT COUNT(*) FROM mysql.user WHERE User = {$u} AND Host = {$h}")->fetchColumn();

            if (! $exists) {
                $this->admin->exec("CREATE USER {$u}@{$h} IDENTIFIED BY {$pw}");
                $this->log[] = "User {$user}@{$host} created";
            } elseif ($resetPassword) {
                $this->admin->exec("ALTER USER {$u}@{$h} IDENTIFIED BY {$pw}");
                $this->log[] = "User {$user}@{$host} password updated";
            } else {
                $this->log[] = "User {$user}@{$host} already exists (password unchanged)";
            }
            $this->admin->exec("GRANT ALL PRIVILEGES ON `{$database}`.* TO {$u}@{$h}");
        }
        $this->admin->exec('FLUSH PRIVILEGES');
    }

    public function userExists(string $user): bool
    {
        return (bool) $this->admin->query('SELECT COUNT(*) FROM mysql.user WHERE User = '.$this->admin->quote($user))->fetchColumn();
    }

    private function assertIdentifier(string $value, string $what): void
    {
        if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $value)) {
            throw new InvalidArgumentException("The MySQL {$what} name may only contain letters, digits and underscores (max 64).");
        }
    }
}
