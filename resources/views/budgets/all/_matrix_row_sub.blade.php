<tr>
    <td style="padding:10px 16px">
        <div style="font-size:13px;font-weight:600;color:#1B2A4A">{{ $sub->name }}</div>
        <div style="font-size:10px;color:#64748B">
            {{ $sub->code }}
            @if($sub->category)
            <span style="background:#EDE9FE;color:#5B21B6;border-radius:4px;
                         padding:0 5px;font-size:9px;font-weight:600;margin-left:3px">
                {{ $sub->category->name }}
            </span>
            @endif
        </div>
    </td>
    <td>
        <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600;
                     background:#EDE9FE;color:#5B21B6">
            Subsidiary
        </span>
    </td>
    <td>
        <span style="padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;
                     background:{{ match($status) {
                         'approved'     => '#D1FAE5', 'rejected'     => '#FEE2E2',
                         'submitted'    => '#DBEAFE', 'under_review' => '#FEF3C7',
                         'draft'        => '#F1F5F9', default        => '#F8FAFC'
                     } }};
                     color:{{ match($status) {
                         'approved'     => '#065F46', 'rejected'     => '#991B1B',
                         'submitted'    => '#1E40AF', 'under_review' => '#92400E',
                         'draft'        => '#475569', default        => '#94A3B8'
                     } }}">
            {{ ucfirst(str_replace('_',' ',$status)) }}
        </span>
    </td>
    <td class="text-end small fw-semibold">
        {{ $total > 0 ? number_format($total, 0) : '—' }}
    </td>
    <td class="text-end small" style="color:#10B981">
        {{ $actual > 0 ? number_format($actual, 0) : '—' }}
    </td>
    <td style="min-width:130px">
        @if($total > 0)
        <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
                <div class="progress-bar" style="width:{{ min($utilPct,100) }}%;border-radius:3px;
                     background:{{ $utilPct>90?'#F43F5E':($utilPct>70?'#F59E0B':'#10B981') }}">
                </div>
            </div>
            <span style="font-size:11px;color:#64748B;white-space:nowrap">{{ $utilPct }}%</span>
        </div>
        @else
        <span style="color:#94A3B8;font-size:11px">—</span>
        @endif
    </td>
    <td class="text-center small text-muted">{{ $row['versions']->count() }}</td>
    <td>
        <div class="d-flex gap-1">
            @if($latest)
            <a href="{{ route('budgets.show', $latest) }}"
               class="btn btn-sm"
               style="background:#1B2A4A;color:#fff;font-size:11px;border-radius:6px;padding:3px 12px">
                View
            </a>
            @endif
            <a href="{{ route('admin.subsidiaries.show', $sub) }}"
               class="btn btn-sm btn-outline-secondary"
               style="font-size:11px;border-radius:6px;padding:3px 10px">
                Profile
            </a>
        </div>
    </td>
</tr>
