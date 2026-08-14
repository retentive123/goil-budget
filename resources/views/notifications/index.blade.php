@extends('layouts.app')
@section('title', 'Notifications')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Notifications</h5>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-sm btn-outline-secondary">Mark all as read</button>
    </form>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        @forelse($notifications as $notif)
        <div class="d-flex align-items-start gap-3 p-3 border-bottom
            {{ $notif->isRead() ? '' : 'bg-light' }}">

            {{-- Icon --}}
            <div class="mt-1">
                @php
                    [$iconClass, $iconColor] = match(true) {
                        str_contains($notif->type, 'approved') => ['bi bi-check-circle-fill', '#10B981'],
                        str_contains($notif->type, 'rejected') => ['bi bi-x-circle-fill',     '#F43F5E'],
                        str_contains($notif->type, 'pending')  => ['bi bi-hourglass-split',   '#F59E0B'],
                        str_contains($notif->type, 'virement') => ['bi bi-arrow-left-right',  '#6366F1'],
                        default                                 => ['bi bi-clipboard2',        '#64748B'],
                    };
                @endphp
                <i class="{{ $iconClass }}" style="font-size:18px;color:{{ $iconColor }}"></i>
            </div>

            {{-- Content --}}
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="fw-semibold small {{ $notif->isRead() ? 'text-muted' : '' }}">
                        {{ $notif->subject }}
                        @if(!$notif->isRead())
                            <span class="badge bg-primary ms-1" style="font-size:9px">New</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2 ms-3">
                        <span class="text-muted" style="font-size:11px;white-space:nowrap">
                            {{ $notif->created_at->diffForHumans() }}
                        </span>
                        <form method="POST"
                              action="{{ route('notifications.destroy', $notif) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-link btn-sm p-0 text-muted"
                                    style="font-size:13px;line-height:1">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="small text-muted mt-1">{{ $notif->message }}</div>
            </div>

        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-bell" style="font-size:38px;color:#CBD5E1;display:block;margin-bottom:12px"></i>
            <div class="mt-2">No notifications yet.</div>
        </div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $notifications->links() }}</div>

@endsection
