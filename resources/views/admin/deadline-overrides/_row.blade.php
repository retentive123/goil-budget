@php
$_statusKey = ($info['has_override'] && $override?->isValid()) ? 'extended'
            : ($info['passed'] ? 'overdue' : 'on-time');
@endphp
<div data-row
     data-name="{{ strtolower($dept->name . ' ' . $dept->code) }}"
     data-status="{{ $_statusKey }}"
     style="display:flex;align-items:center;gap:12px;
            padding:10px 0;border-bottom:1px solid var(--border)">
    <div style="width:40px;height:40px;border-radius:8px;
                background:var(--surface);border:1px solid var(--border);
                display:flex;align-items:center;justify-content:center;
                font-size:10px;font-weight:700;color:var(--navy);
                flex-shrink:0;text-align:center;line-height:1.2">
        {{ $dept->code }}
    </div>
    <div class="flex-grow-1">
        <div style="font-size:13px;font-weight:600;color:var(--navy)">
            {{ $dept->name }}
            @if($dept->isServiceStation())
            <span style="font-size:10px;font-weight:600;background:#EDE9FE;color:#5B21B6;
                         padding:1px 6px;border-radius:3px;margin-left:4px">Station</span>
            @endif
        </div>
        <div style="font-size:11px;color:var(--slate)">
            @if($info['has_override'] && $override?->isValid())
                Extended until: {{ $info['deadline']?->format('d M Y H:i') ?? 'Indefinite' }}
                · Granted by {{ $override->grantedBy->name }}
            @elseif($info['passed'])
                Deadline passed
            @elseif($info['deadline'])
                Deadline: {{ $info['deadline']->format('d M Y H:i') }}
                ({{ $info['deadline']->diffForHumans() }})
            @else
                No deadline set
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($info['has_override'] && $override?->isValid())
            <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                         font-weight:600;background:#D1FAE5;color:#065F46">
                Extended
            </span>
            <form method="POST"
                  action="{{ route('admin.deadline-overrides.revoke', $override) }}">
                @csrf
                <button class="btn btn-sm btn-outline-danger"
                        style="font-size:11px;padding:2px 8px"
                        onclick="return confirm('Revoke override for {{ addslashes($dept->name) }}?')">
                    Revoke
                </button>
            </form>
        @elseif($info['passed'])
            <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                         font-weight:600;background:#FEE2E2;color:#991B1B">
                Overdue
            </span>
            <button class="btn btn-sm btn-outline-primary"
                    style="font-size:11px;padding:2px 8px"
                    onclick="openGrantForm({{ $dept->id }}, '{{ addslashes($dept->name) }}')">
                Grant Extension
            </button>
        @else
            <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                         font-weight:600;background:#F1F5F9;color:#64748B">
                On Time
            </span>
        @endif
    </div>
</div>
