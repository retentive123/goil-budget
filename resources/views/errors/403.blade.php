@extends('layouts.app')
@section('title', 'Access Denied')
@section('content')

<div class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-6 col-lg-5">

            <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                {{-- Card Header --}}
                <div class="card-header border-0 text-center py-4" style="background: #C44D00; color: #fff;">
                    <div style="font-size: 64px; margin-bottom: 8px;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 class="fw-bold mb-0" style="color: #fff;">Access Denied</h3>
                    <p class="mb-0" style="color: rgba(255,255,255,0.7); font-size: 14px;">
                        You don't have permission to access this page
                    </p>
                </div>

                {{-- Card Body --}}
                <div class="card-body p-4 text-center">

                    {{-- Error Code --}}
                    <div style="font-size: 80px; font-weight: 700; color: #E65C00; opacity: 0.15; line-height: 1; margin-top: -20px;">
                        403
                    </div>

                    {{-- Message --}}
                    <div style="font-size: 16px; color: #1B2A4A; font-weight: 600; margin-top: -20px; margin-bottom: 8px;">
                        {{ $exception->getMessage() ?: 'You do not have the required permissions to view this page.' }}
                    </div>

                    <p class="text-muted small" style="font-size: 13px;">
                        Please contact your system administrator if you believe this is an error.
                    </p>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
                        <a href="{{ url()->previous() }}" class="btn px-4 py-2" style="background: #F1F5F9; color: #475569; border-radius: 10px; border: 1px solid #E2E8F0; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-arrow-left"></i> Go Back
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn px-4 py-2" style="background: #E65C00; color: #fff; border-radius: 10px; border: none; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </div>

                    {{-- Helpful Tips --}}
                    <div class="mt-4 pt-3 border-top text-start" style="border-color: #E2E8F0 !important;">
                        <div style="font-size: 12px; font-weight: 600; color: #1B2A4A; margin-bottom: 6px;">
                            <i class="fas fa-lightbulb" style="color: #C9A84C;"></i> Need help?
                        </div>
                        <ul class="list-unstyled small text-muted mb-0" style="font-size: 12px;">
                            <li><i class="fas fa-check-circle" style="color: #10B981; font-size: 10px;"></i> Ensure you are logged in with the correct account</li>
                            <li><i class="fas fa-check-circle" style="color: #10B981; font-size: 10px;"></i> Contact your manager or administrator for access</li>
                            <li><i class="fas fa-check-circle" style="color: #10B981; font-size: 10px;"></i> You may need to request additional permissions</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-3">
                <span style="font-size: 11px; color: #94A3B8;">
                    <i class="fas fa-shield-alt" style="color: #E65C00;"></i>
                    GOIL Budget Management System
                </span>
            </div>

        </div>
    </div>
</div>

<style>
    .btn-primary:hover {
        background: #C44D00 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(230, 92, 0, 0.3);
    }

    .btn-outline-secondary:hover {
        background: #F8FAFC;
        border-color: #CBD5E1;
    }
</style>

@endsection
