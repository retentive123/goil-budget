@extends('layouts.app')
@section('title', 'Page Not Found')
@section('content')

<div class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-6 col-lg-5">

            <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header border-0 text-center py-4" style="background: #1B2A4A; color: #fff;">
                    <div style="font-size: 64px; margin-bottom: 8px;">
                        <i class="fas fa-map-signs"></i>
                    </div>
                    <h3 class="fw-bold mb-0" style="color: #fff;">Page Not Found</h3>
                    <p class="mb-0" style="color: rgba(255,255,255,0.7); font-size: 14px;">
                        The page you're looking for doesn't exist
                    </p>
                </div>

                <div class="card-body p-4 text-center">
                    <div style="font-size: 80px; font-weight: 700; color: #1B2A4A; opacity: 0.1; line-height: 1; margin-top: -20px;">
                        404
                    </div>

                    <div style="font-size: 16px; color: #1B2A4A; font-weight: 600; margin-top: -20px; margin-bottom: 8px;">
                        {{ $exception->getMessage() ?: 'We couldn\'t find the page you were looking for.' }}
                    </div>

                    <p class="text-muted small" style="font-size: 13px;">
                        The link may be broken, the page may have been moved, or you may have mistyped the address.
                    </p>

                    <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
                        <a href="{{ url()->previous() }}" class="btn px-4 py-2"
                           style="background: #F1F5F9; color: #475569; border-radius: 10px; border: 1px solid #E2E8F0; font-weight: 500;">
                            <i class="fas fa-arrow-left me-1"></i> Go Back
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn px-4 py-2"
                           style="background: #E65C00; color: #fff; border-radius: 10px; border: none; font-weight: 500;">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </div>

                    <div class="mt-4 pt-3 border-top text-start" style="border-color: #E2E8F0 !important;">
                        <div style="font-size: 12px; font-weight: 600; color: #1B2A4A; margin-bottom: 6px;">
                            <i class="fas fa-lightbulb" style="color: #C9A84C;"></i> Common pages
                        </div>
                        <ul class="list-unstyled small text-muted mb-0" style="font-size: 12px;">
                            <li><i class="fas fa-circle" style="color: #E65C00; font-size: 6px; vertical-align: middle;"></i>
                                <a href="{{ route('dashboard') }}" style="color: #E65C00; text-decoration: none;"> Dashboard</a></li>
                            <li><i class="fas fa-circle" style="color: #E65C00; font-size: 6px; vertical-align: middle;"></i>
                                <a href="{{ route('reports.index') }}" style="color: #E65C00; text-decoration: none;"> Reports</a></li>
                            @can('manage users')
                            <li><i class="fas fa-circle" style="color: #E65C00; font-size: 6px; vertical-align: middle;"></i>
                                <a href="{{ route('admin.users.index') }}" style="color: #E65C00; text-decoration: none;"> User Management</a></li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <span style="font-size: 11px; color: #94A3B8;">
                    <i class="fas fa-shield-alt" style="color: #E65C00;"></i>
                    GOIL Budget Management System
                </span>
            </div>

        </div>
    </div>
</div>

@endsection
