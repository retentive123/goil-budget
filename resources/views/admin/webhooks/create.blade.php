@extends('layouts.app')
@section('title', 'Add Webhook')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0">Add Webhook</h5>
</div>

@include('admin.webhooks._form', ['webhook' => null, 'events' => $events])

@endsection
