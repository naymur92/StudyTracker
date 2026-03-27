@extends('layouts.app')

@section('title', 'View Log File')

@push('styles')
    <style>
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            max-height: 600px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .log-line {
            margin: 2px 0;
            padding: 2px 5px;
            border-radius: 3px;
        }

        .log-line:hover {
            background: #2d2d30;
        }

        .log-error {
            color: #f48771;
        }

        .log-warning {
            color: #dcdcaa;
        }

        .log-info {
            color: #4fc1ff;
        }

        .log-debug {
            color: #b5cea8;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 text-primary"><i class="fas fa-file-alt mr-1"></i>{{ $filename }}</h5>
                <div class="d-flex" style="gap:.4rem;">
                    <x-button.back href="{{ route('system-logs.index') }}">Back</x-button.back>
                    <x-button.download :href="route('system-logs.download', $filename)">Download</x-button.download>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search in logs..." value="{{ $search }}">
                        </div>
                        <div class="col-md-3">
                            <select name="lines" class="form-control">
                                <option value="50" {{ $lines == 50 ? 'selected' : '' }}>Last 50 lines</option>
                                <option value="100" {{ $lines == 100 ? 'selected' : '' }}>Last 100 lines</option>
                                <option value="200" {{ $lines == 200 ? 'selected' : '' }}>Last 200 lines</option>
                                <option value="500" {{ $lines == 500 ? 'selected' : '' }}>Last 500 lines</option>
                                <option value="1000" {{ $lines == 1000 ? 'selected' : '' }}>Last 1000 lines</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="log-viewer">
                    @if (count($content) > 0)
                        @foreach ($content as $index => $line)
                            @php
                                $lineClass = '';
                                if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
                                    $lineClass = 'log-error';
                                } elseif (stripos($line, 'warning') !== false) {
                                    $lineClass = 'log-warning';
                                } elseif (stripos($line, 'info') !== false) {
                                    $lineClass = 'log-info';
                                } elseif (stripos($line, 'debug') !== false) {
                                    $lineClass = 'log-debug';
                                }
                            @endphp
                            <div class="log-line {{ $lineClass }}">
                                <small>{{ $index + 1 }}:</small> {{ $line }}
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted">
                            @if ($search)
                                No matching logs found for "{{ $search }}"
                            @else
                                No logs available
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-3">
                    <small class="text-muted">Showing {{ count($content) }} lines</small>
                </div>
            </div>
        </div>
    </div>
@endsection
