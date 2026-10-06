@extends('layouts.app')
@section('title', 'Dashboard Widget Visibility')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Dashboard Widget Visibility</h5>
        <p class="text-muted small mb-0">Control which widgets each role sees on the dashboard.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('admin.widget-settings.update') }}">
    @csrf @method('PUT')

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="min-width:700px">
                    <thead class="table-light">
                        <tr>
                            <th style="width:220px">Widget</th>
                            @foreach($roles as $roleKey => $roleLabel)
                                <th class="text-center" style="font-size:12px;white-space:nowrap">
                                    {{ $roleLabel }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($widgets as $widgetKey => $widgetLabel)
                        <tr>
                            <td class="fw-semibold align-middle" style="font-size:13px">
                                {{ $widgetLabel }}
                            </td>
                            @foreach($roles as $roleKey => $roleLabel)
                            <td class="text-center align-middle">
                                <div class="form-check d-flex justify-content-center mb-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="visible[{{ $roleKey }}][{{ $widgetKey }}]"
                                           value="1"
                                           {{ ($grid[$roleKey][$widgetKey] ?? true) ? 'checked' : '' }}>
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-transparent">
            <button type="submit" class="btn btn-primary">Save Visibility</button>
            <span class="text-muted small ms-3">
                <i class="bi bi-info-circle"></i>
                Unchecked widgets are hidden; checked widgets are visible for that role.
            </span>
        </div>
    </div>
</form>

@endsection
