@props(['type' => 'submit', 'icon' => 'fas fa-save'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'btn btn-sm btn-primary waves-effect waves-light br-5']) }}>
    <i class="{{ $icon }} me-1"></i> {{ strlen($slot) > 0 ? $slot : 'Save' }}
</button>
