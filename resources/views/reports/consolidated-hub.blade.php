@extends('layouts.app')
@section('title', 'Consolidated Reports')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-0">Consolidated Reports</h5>
    <p class="text-muted small mb-0">
        <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
        / Consolidated
    </p>
</div>

<div class="row g-4 mt-1">

    {{-- Group --}}
    <div class="col-12 col-md-4">
        <a href="{{ route('reports.consolidated') }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm" style="border-radius:14px; transition:transform .15s,box-shadow .15s"
                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#3B82F6,#1D4ED8);display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-layers-fill text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Consolidated Group</h6>
                            <span class="badge text-bg-primary" style="font-size:10px">All entities</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Full group rollup combining all departments and subsidiaries into one P&amp;L view.
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 px-4 pb-4 pt-0">
                    <span class="small fw-semibold text-primary">Open report <i class="bi bi-arrow-right"></i></span>
                </div>
            </div>
        </a>
    </div>

    {{-- Departments --}}
    <div class="col-12 col-md-4">
        <a href="{{ route('reports.consolidated-departments') }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm" style="border-radius:14px; transition:transform .15s,box-shadow .15s"
                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#10B981,#047857);display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-diagram-3-fill text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Consolidated Departments</h6>
                            <span class="badge text-bg-success" style="font-size:10px">GOIL departments</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Side-by-side view of all GOIL departments with account codes and budget totals.
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 px-4 pb-4 pt-0">
                    <span class="small fw-semibold text-success">Open report <i class="bi bi-arrow-right"></i></span>
                </div>
            </div>
        </a>
    </div>

    {{-- Subsidiaries --}}
    <div class="col-12 col-md-4">
        <a href="{{ route('reports.consolidated-subsidiaries') }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm" style="border-radius:14px; transition:transform .15s,box-shadow .15s"
                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#8B5CF6,#6D28D9);display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-buildings-fill text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Consolidated Subsidiaries</h6>
                            <span class="badge text-bg-purple" style="font-size:10px;background:#8B5CF6;color:#fff">Subsidiaries</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Combined budget view across GEL, GUL, GOB and all subsidiary entities.
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 px-4 pb-4 pt-0">
                    <span class="small fw-semibold" style="color:#8B5CF6">Open report <i class="bi bi-arrow-right"></i></span>
                </div>
            </div>
        </a>
    </div>

</div>
@endsection
