@props(['href' => '#', 'onclick' => null])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-sm btn-outline-danger br-5 waves-effect waves-light']) }}
    @if ($onclick) onclick="{{ $onclick }}" @endif>
    <i class="fa-solid fa-trash"></i> {{ strlen($slot) > 0 ? $slot : 'Delete' }}
</a>
