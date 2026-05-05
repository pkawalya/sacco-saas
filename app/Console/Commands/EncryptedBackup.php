<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Encrypted database backup command.
 *
 * Usage: php artisan backup:encrypted
 */
class EncryptedBackup extends Command
{
    protected $signature = 'backup:encrypted {--tenant= : Specific tenant ID to backup}';

    protected $description = 'Create an encrypted database backup';

    public function handle(): int
    {
        $storagePath = config('security.backup.storage_path', storage_path('app/backups'));
        $encryptionKey = config('security.backup.encryption_key');

        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0700, true);
        }

        $tenantId = $this->option('tenant');
        $database = $tenantId
            ? 'tenant'.$tenantId.'-'
            : config('database.connections.mysql.database');

        $filename = $database.'-'.now()->format('Y-m-d-His').'.sql';
        $filepath = $storagePath.'/'.$filename;

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Dump database
        $dumpCmd = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s > %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $result = Process::run($dumpCmd);

        if ($result->failed()) {
            $this->error('Database dump failed: '.$result->errorOutput());

            return self::FAILURE;
        }

        $this->info("Database dumped: {$filename}");

        // Encrypt if key is configured
        if ($encryptionKey) {
            $encryptedPath = $filepath.'.enc';
            $encCmd = sprintf(
                'openssl enc -aes-256-cbc -salt -pbkdf2 -in %s -out %s -k %s',
                escapeshellarg($filepath),
                escapeshellarg($encryptedPath),
                escapeshellarg($encryptionKey)
            );

            $encResult = Process::run($encCmd);

            if ($encResult->failed()) {
                $this->error('Encryption failed: '.$encResult->errorOutput());

                return self::FAILURE;
            }

            // Remove unencrypted dump
            unlink($filepath);
            $this->info("Encrypted backup: {$filename}.enc");
        } else {
            $this->warn('No encryption key set (BACKUP_ENCRYPTION_KEY). Backup is unencrypted.');
        }

        // Clean old backups (keep last 30)
        $files = glob($storagePath.'/*');
        if (count($files) > 30) {
            usort($files, fn ($a, $b) => filemtime($a) - filemtime($b));
            $toDelete = array_slice($files, 0, count($files) - 30);
            foreach ($toDelete as $file) {
                unlink($file);
            }
            $this->info('Cleaned '.count($toDelete).' old backups.');
        }

        return self::SUCCESS;
    }
}
