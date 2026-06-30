@extends('layouts.app')
@section('title', 'Permissions')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Permissions</h5>
    <a href="{{ route('admin.roles.index') }}"
       class="btn btn-sm btn-outline-secondary">← Back to Roles</a>
</div>

<div class="row g-4">

    {{-- Add permission --}}
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <span class="fw-semibold small">Add Permission</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.permissions.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Permission name</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="form-control form-control-sm
                                   @error('name') is-invalid @enderror"
                            placeholder="e.g. export reports">
                        <div class="form-text">
                            Lowercase letters, numbers and spaces only.
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        Add Permission
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Permission list --}}
    <div class="col-md-8">
        @foreach($permissions as $group => $groupPerms)
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light py-2">
                <span class="fw-semibold small text-uppercase">{{ $group }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Permission</th>
                            <th>Assigned to Roles</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupPerms as $perm)
                        <tr>
                            <td class="small">
                                <code>{{ $perm->name }}</code>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $perm->roles_count }}
                                    role{{ $perm->roles_count != 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td>
                                @if($perm->roles_count === 0)
                                <form method="POST"
                                      action="{{ route('admin.permissions.destroy', $perm) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete permission: {{ $perm->name }}?')">
                                        Delete
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
