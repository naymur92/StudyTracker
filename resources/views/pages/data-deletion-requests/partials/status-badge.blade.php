@php
    $badge = [
        'pending' => 'warning',
        'approved' => 'info',
        'processing' => 'info',
        'completed' => 'success',
        'failed' => 'danger',
        'rejected' => 'secondary',
        'restored' => 'primary',
    ][$request->status] ?? 'light';
@endphp
<span class="badge badge-{{ $badge }}">{{ $request->statusLabel() }}</span>
