<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

final class DatabaseBackupService
{
    private const DIRECTORY = 'private/database-backups';

    private const DISK = 'local';

    private const FORMAT = 'baselaravel.postgresql-backup.v1';

    private const SIGNATURE_MARKER = '-- BASELARAVEL_BACKUP_SIGNATURE: ';

    public function create(): string
    {
        return Cache::lock('database-backup:create', 300)->block(5, function (): string {
            $filename = 'respaldo-base-datos-'.now()->format('Ymd-His').'.sql';
            $dump = $this->runDump();
            $signedContent = $this->header().$dump;
            $signedDump = $signedContent.self::SIGNATURE_MARKER.$this->signature($signedContent)."\n";

            Storage::disk(self::DISK)->put(self::DIRECTORY.'/'.$filename, $signedDump);

            return $filename;
        });
    }

    /**
     * @return array<int, array{name: string, size: int, created_at: Carbon|null}>
     */
    public function all(): array
    {
        return collect(Storage::disk(self::DISK)->files(self::DIRECTORY))
            ->filter(fn (string $path): bool => str_ends_with($path, '.sql'))
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'size' => Storage::disk(self::DISK)->size($path),
                'created_at' => rescue(
                    fn () => now()->createFromTimestamp(Storage::disk(self::DISK)->lastModified($path)),
                    null,
                    false,
                ),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    public function path(string $filename): string
    {
        $path = $this->storedPath($filename);

        if (! Storage::disk(self::DISK)->exists($path)) {
            throw ValidationException::withMessages(['backup' => 'El respaldo seleccionado no existe.']);
        }

        return Storage::disk(self::DISK)->path($path);
    }

    public function delete(string $filename): void
    {
        $path = $this->storedPath($filename);

        if (! Storage::disk(self::DISK)->exists($path)) {
            throw ValidationException::withMessages(['backup' => 'El respaldo seleccionado no existe.']);
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function restoreStored(string $filename): void
    {
        $this->restore((string) file_get_contents($this->path($filename)));
    }

    public function restoreUploaded(UploadedFile $file): void
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw ValidationException::withMessages(['backup' => 'No se pudo leer el archivo seleccionado.']);
        }

        $this->restore($contents);
    }

    private function restore(string $sql): void
    {
        $this->verify($sql);

        Cache::lock('database-backup:restore', 600)->block(5, function () use ($sql): void {
            $process = new Process([
                'psql',
                '--host='.$this->connection('host'),
                '--port='.(string) $this->connection('port'),
                '--username='.$this->connection('username'),
                '--dbname='.$this->connection('database'),
                '--no-password',
                '--single-transaction',
                '--set=ON_ERROR_STOP=1',
            ], null, $this->environment(), $sql, 600);
            $process->run();

            if (! $process->isSuccessful()) {
                report(new \RuntimeException($process->getErrorOutput()));
                throw ValidationException::withMessages([
                    'backup' => 'No se pudo restaurar el respaldo. La base de datos no fue modificada.',
                ]);
            }
        });
    }

    private function runDump(): string
    {
        $this->ensurePostgres();

        $process = new Process([
            'pg_dump',
            '--host='.$this->connection('host'),
            '--port='.(string) $this->connection('port'),
            '--username='.$this->connection('username'),
            '--dbname='.$this->connection('database'),
            '--no-password',
            '--format=plain',
            '--clean',
            '--if-exists',
            '--no-owner',
            '--no-privileges',
        ], null, $this->environment(), null, 300);
        $process->run();

        if (! $process->isSuccessful()) {
            report(new \RuntimeException($process->getErrorOutput()));
            throw ValidationException::withMessages([
                'backup' => 'No se pudo generar el respaldo de la base de datos.',
            ]);
        }

        return rtrim($process->getOutput())."\n";
    }

    private function verify(string $sql): void
    {
        $this->ensurePostgres();

        if (! str_starts_with($sql, '-- BASELARAVEL_BACKUP_FORMAT: '.self::FORMAT."\n")) {
            throw ValidationException::withMessages([
                'backup' => 'El archivo no es un respaldo compatible generado por esta aplicacion.',
            ]);
        }

        $position = strrpos($sql, self::SIGNATURE_MARKER);

        if ($position === false) {
            throw ValidationException::withMessages(['backup' => 'El respaldo esta incompleto o alterado.']);
        }

        $signedContent = substr($sql, 0, $position);
        $providedSignature = trim(substr($sql, $position + strlen(self::SIGNATURE_MARKER)));

        if (! hash_equals($this->signature($signedContent), $providedSignature)) {
            throw ValidationException::withMessages(['backup' => 'La firma del respaldo no es valida.']);
        }
    }

    private function header(): string
    {
        return '-- BASELARAVEL_BACKUP_FORMAT: '.self::FORMAT."\n"
            .'-- BASELARAVEL_BACKUP_GENERATED_AT: '.now()->toIso8601String()."\n";
    }

    private function signature(string $contents): string
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: $key;
        }

        return hash_hmac('sha256', $contents, $key);
    }

    /** @return array<string, string> */
    private function environment(): array
    {
        return ['PGPASSWORD' => (string) $this->connection('password')];
    }

    private function connection(string $key): mixed
    {
        return config('database.connections.'.config('database.default').'.'.$key);
    }

    private function ensurePostgres(): void
    {
        if ($this->connection('driver') !== 'pgsql') {
            throw ValidationException::withMessages([
                'backup' => 'El modulo de respaldos requiere una conexion PostgreSQL.',
            ]);
        }
    }

    private function storedPath(string $filename): string
    {
        if (! preg_match('/\A[A-Za-z0-9_.-]+\.sql\z/', $filename)) {
            throw ValidationException::withMessages(['backup' => 'El nombre del respaldo no es valido.']);
        }

        return self::DIRECTORY.'/'.$filename;
    }
}
