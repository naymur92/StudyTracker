@php $registry = app(\App\Services\StudyTracker\DataDeletion\DataCategoryRegistry::class); @endphp
<table class="table table-sm table-bordered mb-3">
    <tbody>
        @forelse ($counts as $key => $count)
            <tr>
                <td>{{ $registry->recordLabel($key) }}</td>
                <td class="text-right">{{ $count }}</td>
            </tr>
        @empty
            <tr><td class="text-muted text-center">No rows</td></tr>
        @endforelse
    </tbody>
</table>
