@props(['action', 'confirm' => 'Deactivate this user?'])
<form action="{{ $action }}" method="POST" onsubmit="swalConfirmationOnSubmit(event, '{{ $confirm }}');">
    @csrf @method('put')
    <input type="hidden" name="is_active" value="0">
    <button type="submit" class="btn btn-sm btn-outline-danger">
        <i class="fas fa-times mr-1"></i>Mark Inactive
    </button>
</form>
