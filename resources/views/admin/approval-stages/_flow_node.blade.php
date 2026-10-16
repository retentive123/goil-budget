<div style="background:{{ $color }};color:#fff;border-radius:10px;
            padding:10px 16px;text-align:center;min-width:120px">
    <div style="font-size:9px;color:rgba(255,255,255,.55);text-transform:uppercase;
                letter-spacing:.5px;margin-bottom:2px">
        {{ $label ?? '' }}
    </div>
    <div style="font-size:13px;font-weight:700">{{ $stage->name }}</div>
    <div style="font-size:10px;color:var(--gold);margin-top:2px">
        {{ ucfirst(str_replace('_',' ',$stage->role_name)) }}
    </div>
</div>
