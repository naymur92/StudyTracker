<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;

class SystemLogController extends Controller
{
    /**
     * Resolve and validate a log filename stays within the logs directory.
     * Aborts with 403 if path traversal or non-.log file is attempted.
     */
    private function resolveLogPath(string $filename): string
    {
        if (!str_ends_with($filename, '.log')) {
            abort(404, 'Log file not found');
        }

        $logsDir  = realpath(storage_path('logs'));
        $filePath = $logsDir . DIRECTORY_SEPARATOR . basename($filename);
        $realPath = realpath($filePath);

        if (!$realPath || !str_starts_with($realPath, $logsDir . DIRECTORY_SEPARATOR)) {
            abort(403, 'Access denied.');
        }

        return $realPath;
    }
    /**
     * Display a listing of system logs.
     */
    public function index(Request $request)
    {
        $this->authorize('system-log-list');

        $logPath = storage_path('logs');
        $files = File::glob($logPath . '/*.log');

        // Sort by modified time, newest first
        usort($files, function ($a, $b) {
            return File::lastModified($b) - File::lastModified($a);
        });

        $logs = collect($files)->map(function ($file) {
            return [
                'name' => basename($file),
                'path' => $file,
                'size' => $this->formatBytes(File::size($file)),
                'modified' => File::lastModified($file),
                'modified_human' => \Carbon\Carbon::createFromTimestamp(File::lastModified($file))->diffForHumans(),
            ];
        });

        // Filename search filter
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $logs = $logs->filter(fn($l) => str_contains(strtolower($l['name']), $search))->values();
        }

        $perPage = 20;
        $page    = (int) $request->get('page', 1);
        $total   = $logs->count();
        $paged   = $logs->forPage($page, $perPage)->values();

        $logs = new LengthAwarePaginator($paged, $total, $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);

        return view('pages.system-logs.index', compact('logs'));
    }

    /**
     * Display the specified log file.
     */
    public function show(Request $request, $filename)
    {
        $this->authorize('system-log-view');

        $logPath = $this->resolveLogPath($filename);

        if (!File::exists($logPath)) {
            abort(404, 'Log file not found');
        }

        $lines = $request->get('lines', 100);
        $search = $request->get('search', '');

        // Read file from bottom
        $content = $this->tailFile($logPath, $lines);

        // Filter by search term
        if ($search) {
            $content = array_filter($content, function ($line) use ($search) {
                return stripos($line, $search) !== false;
            });
        }

        return view('pages.system-logs.show', [
            'filename' => $filename,
            'content' => $content,
            'lines' => $lines,
            'search' => $search,
        ]);
    }

    /**
     * Download the specified log file.
     */
    public function download($filename)
    {
        $this->authorize('system-log-download');

        $logPath = $this->resolveLogPath($filename);

        if (!File::exists($logPath)) {
            abort(404, 'Log file not found');
        }

        return response()->download($logPath);
    }

    /**
     * Delete the specified log file.
     */
    public function destroy($filename)
    {
        $this->authorize('system-log-delete');

        $logPath = $this->resolveLogPath($filename);

        if (!File::exists($logPath)) {
            abort(404, 'Log file not found');
        }

        // Don't allow deleting current day's log
        if ($filename === 'laravel.log' || $filename === date('Y-m-d') . '.log') {
            return redirect()->route('system-logs.index')
                ->with('error', 'Cannot delete the current log file.');
        }

        File::delete($logPath);

        return redirect()->route('system-logs.index')
            ->with('success', 'Log file deleted successfully.');
    }

    /**
     * Read the last N lines from a file.
     */
    private function tailFile($filepath, $lines = 100)
    {
        $handle = fopen($filepath, "r");
        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = [];

        while ($linecounter > 0) {
            $t = " ";
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos--;
            }
            $linecounter--;
            if ($beginning) {
                rewind($handle);
            }
            $text[$lines - $linecounter - 1] = fgets($handle);
            if ($beginning) break;
        }
        fclose($handle);

        return array_reverse($text);
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
