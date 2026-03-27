@extends('layouts.app')

@section('title', 'OAuth Client Management')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">OAuth Clients</h4>

                        @can('oauth-client-create')
                            <x-button.add-new href="{{ route('oauth-clients.create') }}">
                                Create New Client
                            </x-button.add-new>
                        @endcan
                    </div>
                    <div class="card-body">

                        @if (session('client_credentials'))
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>

                                <strong>Important!</strong> Save these credentials securely. The secret cannot be retrieved again.
                                <hr>
                                <p class="mb-1"><strong>Client Name:</strong> {{ session('client_credentials')['name'] }}</p>
                                <p class="mb-1"><strong>Client ID:</strong> <code>{{ session('client_credentials')['id'] }}</code></p>
                                <p class="mb-0"><strong>Client Secret:</strong> <code>{{ session('client_credentials')['secret'] }}</code></p>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Client ID</th>
                                        <th>Grant Types</th>
                                        <th>Provider</th>
                                        <th>Created At</th>
                                        <th width="200">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($clients as $client)
                                        <tr>
                                            <td>{{ $client->name }}</td>
                                            <td><code>{{ $client->id }}</code></td>
                                            <td>
                                                @php
                                                    $grantTypes = json_decode($client->grant_types, true) ?? [];
                                                @endphp
                                                @foreach ($grantTypes as $type)
                                                    <span class="badge badge-primary">{{ $type }}</span>
                                                @endforeach
                                            </td>
                                            <td>{{ $client->provider ?? 'N/A' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($client->created_at)->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <div class="text-center d-flex justify-content-center align-items-center">
                                                    @can('oauth-client-edit')
                                                        <x-icon.pen href="{{ route('oauth-clients.edit', $client->id) }}"></x-icon.pen>

                                                        <x-icon.key onclick="confirmRegenerateSecret('{{ $client->id }}', '{{ $client->name }}')"></x-icon.key>
                                                    @endcan

                                                    @can('oauth-client-delete')
                                                        <x-icon.trash onclick="confirmDelete('{{ $client->id }}', '{{ $client->name }}')"></x-icon.trash>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No clients found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- Regenerate Secret Form (hidden) -->
    <form id="regenerateSecretForm" method="POST" style="display: none;">
        @csrf
    </form>

    @push('scripts')
        <script>
            function confirmDelete(clientId, clientName) {
                swalConfirmation(`Are you sure you want to revoke the client "${clientName}"?`).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deleteForm');
                        form.action = `/admin/oauth-clients/${clientId}`;
                        form.submit();
                    }
                });
            }

            function confirmRegenerateSecret(clientId, clientName) {
                swalConfirmation(`Are you sure you want to regenerate the secret for "${clientName}"? The old secret will stop working!`).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('regenerateSecretForm');
                        form.action = `/admin/oauth-clients/${clientId}/regenerate-secret`;
                        form.submit();
                    }
                });
            }
        </script>
    @endpush
@endsection
