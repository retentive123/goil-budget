@extends('layouts.app')
@section('title', 'Approved Budget Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Approved Budget Report</h5>
    @can('export reports')
    <div class="d-flex gap-2">
        <a href="{{ route('reports.export.approved', request()->query()) }}"
           class="btn btn-sm btn-outline-success">Export Excel</a>
        <a href="{{ route('reports.export.pdf', array_merge(['type'=>'approved'], request()->query())) }}"
           class="btn btn-sm btn-outline-danger">Export PDF</a>
    </div>
    @endcan
</div>

{{-- Filters --}}
<form method="GET" class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Period</label>
                <select name="period_id" class="form-select form-select-sm">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}"
                            {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Dept / Station</label>
                @include('reports._dept_filter', [
                    'selectedId' => request('department_id'),
                    'selectId'   => 'rptApprovedDeptSel',
                ])
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

@if($versions->isEmpty())
    <div class="alert alert-info">No approved budgets found for this selection.</div>
@else

{{-- Grand totals --}}
@php
    $gt = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,'total'=>0];
    foreach($data as $cat => $codes) {
        foreach($codes as $code => $vals) {
            $gt['q1'] += $vals['q1'];
            $gt['q2'] += $vals['q2'];
            $gt['q3'] += $vals['q3'];
            $gt['q4'] += $vals['q4'];
            $gt['total'] += $vals['total'];
        }
    }
@endphp

<div class="card mb-3 border-0 bg-dark text-white">
    <div class="card-body py-2">
        <div class="row text-center">
            @foreach(['Q1'=>'q1','Q2'=>'q2','Q3'=>'q3','Q4'=>'q4'] as $label => $key)
            <div class="col">
                <div class="small text-white-50">{{ $label }}</div>
                <div class="fw-bold">{{ currency() }} {{ number_format($gt[$key],2) }}</div>
            </div>
            @endforeach
            <div class="col border-start border-secondary">
                <div class="small text-white-50">Grand Total</div>
                <div class="fw-bold fs-5">{{ currency() }} {{ number_format($gt['total'],2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Data by category --}}
@foreach($data as $categoryName => $codes)
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light py-2 d-flex justify-content-between">
        <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
        <span class="small text-muted">
            Total: <strong>{{ currency() }} {{ number_format(collect($codes)->sum('total'), 2) }}</strong>
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:35%">Account</th>
                    <th>Q1 ({{ currency() }})</th>
                    <th>Q2 ({{ currency() }})</th>
                    <th>Q3 ({{ currency() }})</th>
                    <th>Q4 ({{ currency() }})</th>
                    <th>Total ({{ currency() }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($codes as $code => $vals)
                <tr>
                    <td class="small"><code>{{ $code }}</code> {{ $vals['name'] }}</td>
                    <td class="small">{{ number_format($vals['q1'],2) }}</td>
                    <td class="small">{{ number_format($vals['q2'],2) }}</td>
                    <td class="small">{{ number_format($vals['q3'],2) }}</td>
                    <td class="small">{{ number_format($vals['q4'],2) }}</td>
                    <td class="small fw-semibold">{{ number_format($vals['total'],2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

@endif
@endsection
