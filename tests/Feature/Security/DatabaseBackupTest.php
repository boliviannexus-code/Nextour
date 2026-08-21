<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class DatabaseBackupTest extends TestCase
{
    use DatabaseTransactions;

    public function test_backup_routes_require_the_manage_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('database-backups.index'))->assertForbidden();

        Permission::findOrCreate('database-backups.manage');
        $user->givePermissionTo('database-backups.manage');

        $this->actingAs($user)
            ->get(route('database-backups.index'))
            ->assertOk()
            ->assertSee('Respaldos de base de datos');
    }

    public function test_service_creates_a_signed_sql_backup_and_rejects_tampering(): void
    {
        Storage::fake('local');
        $service = app(DatabaseBackupService::class);

        $filename = $service->create();
        $path = 'private/database-backups/'.$filename;

        Storage::disk('local')->assertExists($path);
        $sql = Storage::disk('local')->get($path);

        $this->assertStringStartsWith('-- BASELARAVEL_BACKUP_FORMAT:', $sql);
        $this->assertStringContainsString('-- BASELARAVEL_BACKUP_SIGNATURE:', $sql);

        $this->expectException(ValidationException::class);
        $service->restoreUploaded(UploadedFile::fake()->createWithContent(
            'respaldo-alterado.sql',
            str_replace('PostgreSQL', 'PostgreSQL alterado', $sql),
        ));
    }
}
