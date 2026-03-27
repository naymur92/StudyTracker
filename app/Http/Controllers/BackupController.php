<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupController extends Controller
{
    private $backupPath = 'backups';

    /**
     * Resolve and validate a filename stays within the backup directory.
     * Aborts with 403 if path traversal is attempted.
     */
    private function resolveBackupPath(string $filename): string
    {
        $backupDir = realpath(storage_path('app/' . $this->backupPath));
        $filePath  = $backupDir . DIRECTORY_SEPARATOR . basename($filename);
        $realPath  = realpath($filePath);

        if (!$realPath || !str_starts_with($realPath, $backupDir . DIRECTORY_SEPARATOR)) {
            abort(403, 'Access denied.');
        }

        return $realPath;
    }

    /**
     * List all database backups
     */
    public function index()
    {
        $this->authorize('backup-create');

        $backupDir = storage_path('app/' . $this->backupPath);

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $files = File::files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => $file->getFilename(),
                'size' => $this->formatBytes($file->getSize()),
                'size_bytes' => $file->getSize(),
                'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('Y-m-d H:i:s'),
                'path' => $file->getPathname(),
            ];
        }

        // Sort by creation date (newest first)
        usort($backups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return response()->json(['success' => true, 'data' => $backups]);
    }

    /**
     * Create a new database backup
     */
    public function create(Request $request)
    {
        $this->authorize('backup-create');

        try {
            $backupDir = storage_path('app/' . $this->backupPath);

            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port') ?? 3306;

            $filename = 'backup_' . Carbon::now()->format('Y-m-d_His') . '.sql';
            $filePath = $backupDir . '/' . $filename;

            // mysqldump command — password passed via env var to avoid exposure in process list
            $command = sprintf(
                'mysqldump --user=%s --host=%s --port=%s %s > %s 2>&1',
                escapeshellarg($username),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($filePath)
            );

            putenv('MYSQL_PWD=' . $password);

            exec($command, $output, $returnVar);
            putenv('MYSQL_PWD');

            if ($returnVar !== 0) {
                // Delete partially created file if exists
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create database backup'
                ], 500);
            }

            // Verify backup was created
            if (!File::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file was not created'
                ], 500);
            }

            $fileSize = File::size($filePath);

            // Verify file has content
            if ($fileSize == 0) {
                File::delete($filePath);
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file is empty'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'name' => $filename,
                    'size' => $this->formatBytes($fileSize),
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ],
                'message' => 'Database backup created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($filename)
    {
        $this->authorize('backup-create');

        $filePath = $this->resolveBackupPath($filename);

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found'
            ], 404);
        }

        return response()->download($filePath);
    }

    /**
     * Delete a backup file
     */
    public function destroy($filename)
    {
        $this->authorize('backup-delete');

        $filePath = $this->resolveBackupPath($filename);

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found'
            ], 404);
        }

        File::delete($filePath);

        return response()->json([
            'success' => true,
            'message' => 'Backup deleted successfully'
        ]);
    }

    /**
     * Restore database from backup
     */
    public function restore($filename)
    {
        $this->authorize('backup-restore');

        try {
            $filePath = $this->resolveBackupPath($filename);

            if (!File::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port') ?? 3306;

            // mysql command to restore — password passed via env var to avoid process list exposure
            $command = sprintf(
                'mysql --user=%s --host=%s --port=%s %s < %s 2>&1',
                escapeshellarg($username),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($filePath)
            );

            putenv('MYSQL_PWD=' . $password);
            exec($command, $output, $returnVar);
            putenv('MYSQL_PWD');

            if ($returnVar !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to restore database'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Database restored successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format bytes to human readable size
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
