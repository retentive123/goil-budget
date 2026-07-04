@extends('layouts.app')
@section('title', 'Too Many Requests')
@section('content')

<div class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-6 col-lg-5">

            <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header border-0 text-center py-4" style="background: #065F46; color: #fff;">
                    <div style="font-size: 64px; margin-bottom: 8px;">
                        <i class="fas fa-hand-paper"></i>
                    </div>
                    <h3 class="fw-bold mb-0" style="color: #fff;">Slow Down</h3>
                    <p class="mb-0" style="color: rgba(255,255,255,0.7); font-size: 14px;">
                        Too many requests in a short period
                    </p>
                </div>

                <div class="card-body p-4 text-center">
                    <div style="font-size: 80px; font-weight: 700; color: #065F46; opacity: 0.1; line-height: 1; margin-top: -20px;">
                        429
                    </div>

                    <div style="font-size: 16px; color: #1B2A4A; font-weight: 600; margin-top: -20px; margin-bottom: 8px;">
                        You've made too many requests
                    </div>

                    <p class="text-muted small" style="font-size: 13px;">
                        For security reasons, access is temporarily limited after too many rapid requests.
                        Please wait a moment before trying again.
                    </p>

                    <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
                        <a href="javascript:history.back()" class="btn px-4 py-2"
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
                            <i class="fas fa-lightbulb" style="color: #C9A84C;"></i> What now?
                        </div>
                        <ul class="list-unstyled small text-muted mb-0" style="font-size: 12px;">
                            <li><i class="fas fa-check-circle" style="color: #10B981; font-size: 10px;"></i> Wait 60 seconds and try again</li>
                            <li><i class="fas fa-check-circle" style="color: #10B981; font-size: 10px;"></i> Avoid clicking buttons repeatedly</li>
                            <li><i class="fas fa-check-circle" style="color: #10B981; font-size: 10px;"></i> Contact your administrator if this persists</li>
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
