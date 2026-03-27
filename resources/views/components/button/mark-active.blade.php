@props(['action', 'confirm' => 'Activate this user?'])
<form action="{{ $action }}" method="POST" onsubmit="swalConfirmationOnSubmit(event, '{{ $confirm }}');">
    @csrf @method('put')
    <input type="hidden" name="is_active" value="1">
    <button type="submit" class="btn btn-sm btn-outline-success">
        <i class="fas fa-check mr-1"></i>Mark Active
    </button>
</form>
