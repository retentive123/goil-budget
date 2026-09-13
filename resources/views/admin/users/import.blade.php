@extends('layouts.app')
@section('title', 'Bulk Import Users')

@section('content')
<div class="px-3 px-lg-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="small text-muted mb-1">
                <a href="{{ route('admin.users.index') }}" class="text-decoration-none" style="color:#E65C00;">
                    <i class="fas fa-users me-1"></i>Users
                </a>
                <span class="mx-1">/</span>
                <span>Bulk Import</span>
            </div>
            <h5 class="fw-bold mb-0" style="color:#1B2A4A;">
                <i class="fas fa-file-csv me-2" style="color:#E65C00;"></i>Bulk Import Users
            </h5>
            <p class="text-muted small mb-0">Upload a CSV file to create multiple user accounts at once.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="fas fa-arrow-left me-1"></i>Back to Users
        </a>
    </div>

    @if(session('import_success'))
    <div class="alert alert-success d-flex align-items-start gap-2 mb-4" style="border-radius:12px;">
        <i class="bi bi-check-circle-fill mt-1" style="flex-shrink:0;"></i>
        <div>
            <div class="fw-semibold">{{ session('import_success') }}</div>
            @if(session('import_skipped'))
            <ul class="mb-0 mt-2 small">
                @foreach(session('import_skipped') as $skip)
                    <li>{{ $skip }}</li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger mb-4" style="border-radius:12px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $errors->first() }}
    </div>
    @endif

    <div class="row g-4">

        {{-- Upload Form --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <span class="fw-semibold" style="color:#1B2A4A;font-size:14px;">
                        <i class="fas fa-upload me-2" style="color:#E65C00;"></i>Upload CSV File
                    </span>
                </div>
                <div class="card-body p-4">
                    <form method="POST"
                          action="{{ route('admin.users.import.process') }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold small" style="color:#1B2A4A;">
                                CSV File <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   name="csv_file"
                                   accept=".csv,.txt"
                                   class="form-control @error('csv_file') is-invalid @enderror"
                                   style="border-radius:8px;">
                            <div class="form-text text-muted small mt-1">
                                <i class="bi bi-info-circle me-1"></i>
                                Max 2 MB. Must be a .csv file with the columns described on the right.
                            </div>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert py-3 px-3 small mb-4"
                             style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;color:#92400E;">
                            <i class="bi bi-key me-1"></i>
                            <strong>Default password:</strong> If the <code>password</code> column is blank,
                            the account is created with <code>Welcome@{{ now()->year }}</code>.
                            Every imported user is forced to change their password on first login.
                        </div>

                        <button type="submit"
                                class="btn w-100 py-2"
                                style="background:#E65C00;color:#fff;border-radius:10px;font-weight:600;font-size:15px;">
                            <i class="fas fa-upload me-2"></i>Import Users
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Instructions + Template --}}
        <div class="col-lg-5">

            {{-- Template Download --}}
            <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;overflow:hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <span class="fw-semibold" style="color:#1B2A4A;font-size:14px;">
                        <i class="bi bi-download me-2" style="color:#10B981;"></i>Download Template
                    </span>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        Use this template — it has the correct column headers pre-filled
                        with two example rows you can replace.
                    </p>
                    <a href="{{ route('admin.users.import.template') }}"
                       class="btn btn-outline-success w-100"
                       style="border-radius:8px;">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i>
                        users-import-template.csv
                    </a>
                </div>
            </div>

            {{-- Column Guide --}}
            <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <span class="fw-semibold" style="color:#1B2A4A;font-size:14px;">
                        <i class="bi bi-table me-2" style="color:#6366F1;"></i>Column Reference
                    </span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" style="font-size:12px;">
                        <thead style="background:#F8FAFC;">
                            <tr>
                                <th class="px-4 py-2" style="color:#1B2A4A;">Column</th>
                                <th class="px-4 py-2" style="color:#1B2A4A;">Required</th>
                                <th class="px-4 py-2" style="color:#1B2A4A;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2"><code>name</code></td>
                                <td class="px-4 py-2"><span class="badge" style="background:#FEE2E2;color:#991B1B;">Yes</span></td>
                                <td class="px-4 py-2 text-muted">Full name</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2"><code>email</code></td>
                                <td class="px-4 py-2"><span class="badge" style="background:#FEE2E2;color:#991B1B;">Yes</span></td>
                                <td class="px-4 py-2 text-muted">Must be unique</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2"><code>role</code></td>
                                <td class="px-4 py-2"><span class="badge" style="background:#FEE2E2;color:#991B1B;">Yes</span></td>
                                <td class="px-4 py-2 text-muted">
                                    Exact role name:
                                    @foreach($roles as $role)
                                        <span class="badge" style="background:#F1F5F9;color:#475569;font-weight:400;">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2"><code>employee_id</code></td>
                                <td class="px-4 py-2"><span class="badge" style="background:#F0FDF4;color:#166534;">Optional</span></td>
                                <td class="px-4 py-2 text-muted">Staff ID</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2"><code>phone</code></td>
                                <td class="px-4 py-2"><span class="badge" style="background:#F0FDF4;color:#166534;">Optional</span></td>
                                <td class="px-4 py-2 text-muted">Phone number</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2"><code>department_code</code></td>
                                <td class="px-4 py-2"><span class="badge" style="background:#F0FDF4;color:#166534;">Optional</span></td>
                                <td class="px-4 py-2 text-muted">
                                    Dept code, e.g.
                                    @foreach($departments->take(3) as $d)
                                        <code>{{ $d->code }}</code>{{ !$loop->last ? ',' : '' }}
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2"><code>subsidiary_code</code></td>
                                <td class="px-4 py-2"><span class="badge" style="background:#F0FDF4;color:#166534;">Optional</span></td>
                                <td class="px-4 py-2 text-muted">Overrides dept if set</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2"><code>password</code></td>
                                <td class="px-4 py-2"><span class="badge" style="background:#F0FDF4;color:#166534;">Optional</span></td>
                                <td class="px-4 py-2 text-muted">Blank → default used</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
