@extends('layouts.app')
@section('title', 'Edit Webhook')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0">Edit Webhook: {{ $webhook->name }}</h5>
</div>

@include('admin.webhooks._form', ['webhook' => $webhook, 'events' => $events])

@endsection
