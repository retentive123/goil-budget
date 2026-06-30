@extends('layouts.app')
@section('title', 'Add User')
@section('content')

<div class="row justify-content-center">
<div class="col-md-7">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.users.index') }}" class="text-muted text-decoration-none">Users</a>
    <span class="text-muted">/</span>
    <span>Add User</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Add New User</h5>

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email address</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Employee ID</label>
                    <input type="text" name="employee_id" value="{{ old('employee_id') }}"
                        class="form-control @error('employee_id') is-invalid @enderror">
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="form-control @error('phone') is-invalid @enderror">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department_id"
                        class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">— None —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}"
                                {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Role</label>
                    <select name="role"
                        class="form-select @error('role') is-invalid @enderror">
                        <option value="">— Select role —</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}"
                                {{ old('role') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror">
                    <div class="form-text">Min 8 characters, must include uppercase, number and symbol.</div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
