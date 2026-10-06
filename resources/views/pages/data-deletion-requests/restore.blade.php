@extends('layouts.app')

@section('title', 'Restore ' . $deletionRequest->reference())

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Restore <code>{{ $deletionRequest->reference() }}</code></h1>
            <a href="{{ route('data-requests.show', $deletionRequest) }}" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left mr-1"></i>Back to request</a>
        </div>

        <div class="alert alert-info">
            This preview has not changed any data. Rows come back with their original IDs for
            <strong>{{ $deletionRequest->user->name ?? 'the user' }}</strong>. Where the user has created
            clashing data since, their current data is kept and the archived row is skipped.
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">What would be restored</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="restore-preview">
                        <thead class="thead-light">
                            <tr>
                                <th>Records</th>
                                <th class="text-right">In archive</th>
                                <th class="text-right">Restored</th>
                                <th class="text-right">Skipped</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['tables'] as $table)
                                <tr class="{{ $table['skipped'] > 0 ? 'table-warning' : '' }}">
                                    <td>{{ $table['label'] }}</td>
                                    <td class="text-right">{{ $table['archived'] }}</td>
                                    <td class="text-right">{{ $table['restored'] }}</td>
                                    <td class="text-right">{{ $table['skipped'] }}</td>
                                    <td class="small">
                                        @foreach ($table['skip_reasons'] as $reason => $count)
                                            <div>{{ $count }} skipped: {{ $reasons[$reason] ?? $reason }}</div>
                                        @endforeach
                                        @foreach ($table['renamed'] as $rename)
                                            <div>Slug <code>{{ $rename['from'] }}</code> is taken; restored as <code>{{ $rename['to'] }}</code></div>
                                        @endforeach
                                        @if ($table['nulled'] > 0)
                                            <div>{{ $table['nulled'] }} link(s) to rows that no longer exist will be empty</div>
                                        @endif
                                        @if ($table['dropped_columns'])
                                            <div>Columns no longer in the schema: {{ implode(', ', $table['dropped_columns']) }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th></th>
                                <th class="text-right">{{ $report['totals']['restored'] }}</th>
                                <th class="text-right">{{ $report['totals']['skipped'] }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <ul class="small mb-0">
                    <li>
                        Links on kept rows: {{ $report['references']['restored'] }} put back, {{ $report['references']['skipped'] }} left as they are
                        @foreach ($report['references']['skip_reasons'] as $reason => $count)
                            ({{ $count }}: {{ strtolower($reasons[$reason] ?? $reason) }})
                        @endforeach
                    </li>
                    @if ($report['preferences'] === 'restored')
                        <li>Study preferences will be restored.</li>
                    @elseif ($report['preferences'] === 'kept_current')
                        <li>Study preferences: the user has saved new ones, so the archived preferences are skipped.</li>
                    @endif
                </ul>
            </div>
        </div>

        @if ($report['skipped'])
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-warning">Skipped rows</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="restore-skipped">
                        <thead><tr><th>Record</th><th>ID</th><th>Title / date</th><th>Reason</th></tr></thead>
                        <tbody>
                            @foreach ($report['skipped'] as $row)
                                <tr>
                                    <td>{{ $report['tables'][$row['table']]['label'] ?? $row['table'] }}</td>
                                    <td>{{ $row['id'] }}</td>
                                    <td>{{ $row['title'] ?? '—' }}</td>
                                    <td>{{ $reasons[$row['reason']] ?? $row['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('data-requests.restore', $deletionRequest) }}"
            onsubmit="return confirm('Restore {{ $report['totals']['restored'] }} rows for this user?');">
            @csrf
            <button type="submit" class="btn btn-primary" id="confirm-restore"><i class="fas fa-undo mr-1"></i>Restore now</button>
            <a href="{{ route('data-requests.show', $deletionRequest) }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
@endsection
