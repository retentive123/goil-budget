@extends('layouts.app')
@section('title', 'Add Custom Field')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.custom-budget-fields.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0">Add Custom Budget Field</h5>
</div>

@include('admin.custom-budget-fields._form', ['field' => null, 'optionsText' => ''])

@endsection
