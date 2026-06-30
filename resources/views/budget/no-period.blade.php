@extends('layouts.app')
@section('title', 'Budget Entry')
@section('content')

<div class="text-center py-5">
    <h5 class="fw-bold text-muted">No Active Budget Period</h5>
    <p class="text-muted">There is no open budget period at the moment.
       Please contact the Finance team or check back later.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary mt-2">Back to Dashboard</a>
</div>

@endsection
