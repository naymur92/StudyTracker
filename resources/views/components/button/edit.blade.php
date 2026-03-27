@props(['href' => '#', 'onclick' => null])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-sm btn-outline-warning br-5 waves-effect waves-light']) }}
    @if ($onclick) onclick="{{ $onclick }}" @endif>
    <i class="fa-solid fa-pen-to-square"></i> {{ strlen($slot) > 0 ? $slot : 'Edit' }}
</a>
