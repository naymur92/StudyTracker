@props(['href' => '#'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-sm btn-outline-info']) }}>
    <i class="fa-solid fa-key mr-1"></i>{{ $slot->isEmpty() ? 'Reset Password' : $slot }}
</a>
