<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DatabaseBackupController extends Controller
{
    public function __construct(private readonly DatabaseBackupService $backups) {}

    public function index(): View
    {
        return view('database-backups.index', ['backups' => $this->backups->all()]);
    }

    public function store(): RedirectResponse
    {
        $filename = $this->backups->create();

        return back()->with('success', "Respaldo {$filename} generado correctamente.");
    }

    public function download(string $backup): BinaryFileResponse
    {
        return response()->download($this->backups->path($backup), $backup, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function restoreStored(Request $request, string $backup): RedirectResponse
    {
        $request->validate(['confirm_restore' => ['accepted']]);
        $this->backups->restoreStored($backup);

        return back()->with('success', 'Base de datos restaurada correctamente.');
    }

    public function restoreUpload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup' => ['required', 'file', 'extensions:sql', 'max:51200'],
            'confirm_restore' => ['accepted'],
        ]);
        $this->backups->restoreUploaded($validated['backup']);

        return back()->with('success', 'Base de datos restaurada correctamente.');
    }

    public function destroy(string $backup): RedirectResponse
    {
        $this->backups->delete($backup);

        return back()->with('success', "Respaldo {$backup} eliminado correctamente.");
    }
}
