@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">

        {{-- Validation errors --}}
        @if($errors->any())
        <div class="alert d-flex align-items-start gap-3 mb-4"
             style="background:#FFF7ED;border:1.5px solid #E65C00;border-radius:12px;color:#92400E;">
            <i class="bi bi-exclamation-triangle-fill mt-1" style="font-size:18px;color:#E65C00;flex-shrink:0;"></i>
            <div>
                <div class="fw-bold mb-1">Please fix the following:</div>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- ── Profile Photo Card ── --}}
        <div class="card border-0 shadow-lg mb-4" style="border-radius:16px;overflow:hidden;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                         style="width:44px;height:44px;background:#E65C00;color:#fff;font-size:18px;flex-shrink:0;">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color:var(--navy);">Profile Photo</h5>
                        <p class="text-muted small mb-0">Upload a photo or take one with your camera</p>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-4">

                    {{-- Current avatar / initials --}}
                    <div id="avatarPreviewWrap" style="position:relative;flex-shrink:0;">
                        @if($user->avatar && Storage::disk('public')->exists($user->avatar))
                            <img id="avatarImg"
                                 src="{{ Storage::url($user->avatar) }}"
                                 alt="Profile photo"
                                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;
                                        border:3px solid #E65C00;display:block;">
                        @else
                            <div id="avatarInitials"
                                 style="width:100px;height:100px;border-radius:50%;
                                        background:linear-gradient(135deg,#E65C00,#b84600);
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:34px;font-weight:700;color:#fff;
                                        border:3px solid #E65C00;user-select:none;">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>

                    {{-- Upload & Camera buttons --}}
                    <div class="d-flex flex-column gap-2">

                        {{-- File upload form --}}
                        <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" id="avatarUploadForm">
                            @csrf
                            <input type="file" name="avatar" id="avatarFileInput"
                                   accept="image/*" class="d-none">
                            <button type="button" class="btn btn-sm px-4 py-2 d-flex align-items-center gap-2"
                                    style="background:#E65C00;color:#fff;border-radius:10px;font-weight:600;border:none;"
                                    onclick="document.getElementById('avatarFileInput').click()">
                                <i class="bi bi-upload"></i> Upload Photo
                            </button>
                        </form>

                        {{-- Camera capture button --}}
                        <button type="button" class="btn btn-sm px-4 py-2 d-flex align-items-center gap-2"
                                style="background:#F1F5F9;color:var(--slate);border-radius:10px;
                                       font-weight:600;border:1px solid var(--border);"
                                id="openCameraBtn">
                            <i class="bi bi-camera"></i> Take Photo
                        </button>
                    </div>

                    {{-- Camera modal/inline capture area --}}
                    <div id="cameraArea" class="d-none w-100 mt-3">
                        <div style="position:relative;display:inline-block;">
                            <video id="cameraVideo" autoplay playsinline muted
                                   style="width:280px;max-width:100%;border-radius:12px;
                                          background:#000;display:block;"></video>
                            <canvas id="cameraCanvas" style="display:none;"></canvas>
                        </div>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <button type="button" id="captureBtn"
                                    class="btn btn-sm px-4 py-2"
                                    style="background:#E65C00;color:#fff;border-radius:10px;font-weight:600;border:none;">
                                <i class="bi bi-camera-fill"></i> Capture
                            </button>
                            <button type="button" id="closeCameraBtn"
                                    class="btn btn-sm px-4 py-2"
                                    style="background:#F1F5F9;color:var(--slate);border-radius:10px;
                                           font-weight:600;border:1px solid var(--border);">
                                <i class="bi bi-x-lg"></i> Cancel
                            </button>
                        </div>
                        <p class="text-muted small mt-1 mb-0">
                            <i class="bi bi-info-circle"></i>
                            Position yourself in frame then click Capture.
                        </p>
                    </div>

                    {{-- Hidden form for camera-captured image --}}
                    <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" id="cameraUploadForm">
                        @csrf
                        <input type="hidden" name="_camera_capture" value="1">
                        <input type="file" name="avatar" id="cameraAvatarInput" class="d-none">
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Profile Info Card ── --}}
        <div class="card border-0 shadow-lg mb-4" style="border-radius:16px;overflow:hidden;">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                         style="width:44px;height:44px;background:#E65C00;color:#fff;font-size:18px;flex-shrink:0;">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color:var(--navy);">Account Information</h5>
                        <p class="text-muted small mb-0">Update your name and phone number</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">

                {{-- Read-only info rows --}}
                <div class="row g-3 mb-4 pb-3" style="border-bottom:1px solid var(--border);">
                    <div class="col-sm-6">
                        <div class="small fw-semibold text-uppercase mb-1" style="color:var(--slate);letter-spacing:.5px;">
                            <i class="bi bi-envelope me-1"></i>Email
                        </div>
                        <div style="font-size:14px;color:var(--navy);">{{ $user->email }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small fw-semibold text-uppercase mb-1" style="color:var(--slate);letter-spacing:.5px;">
                            <i class="bi bi-id-card me-1"></i>Employee ID
                        </div>
                        <div style="font-size:14px;color:var(--navy);">{{ $user->employee_id ?? '—' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small fw-semibold text-uppercase mb-1" style="color:var(--slate);letter-spacing:.5px;">
                            <i class="bi bi-building me-1"></i>
                            {{ $user->subsidiary_id ? 'Subsidiary' : 'Department' }}
                        </div>
                        <div style="font-size:14px;color:var(--navy);">
                            {{ $user->subsidiary?->name ?? $user->department?->name ?? '—' }}
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small fw-semibold text-uppercase mb-1" style="color:var(--slate);letter-spacing:.5px;">
                            <i class="bi bi-shield-check me-1"></i>Role
                        </div>
                        <div style="font-size:14px;color:var(--navy);">
                            @forelse($user->roles as $role)
                                <span class="badge" style="background:rgba(230,92,0,.12);color:#E65C00;font-size:12px;font-weight:600;border-radius:6px;">
                                    {{ ucfirst($role->name) }}
                                </span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small fw-semibold text-uppercase mb-1" style="color:var(--slate);letter-spacing:.5px;">
                            <i class="bi bi-clock-history me-1"></i>Last Login
                        </div>
                        <div style="font-size:14px;color:var(--navy);">
                            {{ $user->last_login_at ? $user->last_login_at->format('d M Y, g:i A') : '—' }}
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small fw-semibold text-uppercase mb-1" style="color:var(--slate);letter-spacing:.5px;">
                            <i class="bi bi-activity me-1"></i>Status
                        </div>
                        <div>
                            @if($user->is_active)
                                <span class="badge" style="background:rgba(34,197,94,.15);color:#16a34a;font-size:12px;font-weight:600;border-radius:6px;">
                                    <i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>Active
                                </span>
                            @else
                                <span class="badge" style="background:rgba(239,68,68,.12);color:#dc2626;font-size:12px;font-weight:600;border-radius:6px;">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Editable form --}}
                <form method="POST" action="{{ route('profile.update') }}" id="profileForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-uppercase" style="color:var(--slate);letter-spacing:.5px;">
                                <i class="bi bi-person"></i> Full Name
                            </label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   style="border-radius:10px;padding:10px 15px;">
                            @error('name')
                                <div class="invalid-feedback d-block mt-1 small">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-uppercase" style="color:var(--slate);letter-spacing:.5px;">
                                <i class="bi bi-telephone"></i> Phone
                            </label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   style="border-radius:10px;padding:10px 15px;"
                                   placeholder="e.g. +233 24 000 0000">
                            @error('phone')
                                <div class="invalid-feedback d-block mt-1 small">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn px-4"
                                style="background:#E65C00;color:#fff;border-radius:10px;
                                       padding:10px 30px;font-weight:600;border:none;">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4"
                           style="border-radius:10px;padding:10px 30px;">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Security shortcuts ── --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <a href="{{ route('password.change') }}" class="card border-0 shadow-sm text-decoration-none h-100"
                   style="border-radius:14px;border-left:4px solid #E65C00!important;display:block;">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:50%;background:rgba(230,92,0,.1);
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-key" style="color:#E65C00;font-size:16px;"></i>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:var(--navy);">Change Password</div>
                            <div class="text-muted small">Update your login password</div>
                        </div>
                        <i class="fas fa-chevron-right ms-auto" style="color:var(--slate);font-size:11px;"></i>
                    </div>
                </a>
            </div>
            <div class="col-sm-6">
                <a href="{{ route('2fa.setup') }}" class="card border-0 shadow-sm text-decoration-none h-100"
                   style="border-radius:14px;border-left:4px solid {{ Auth::user()->two_factor_enabled ? '#22C55E' : '#F59E0B' }}!important;display:block;">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:50%;
                                    background:{{ Auth::user()->two_factor_enabled ? 'rgba(34,197,94,.1)' : 'rgba(245,158,11,.1)' }};
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-shield-alt" style="color:{{ Auth::user()->two_factor_enabled ? '#22C55E' : '#F59E0B' }};font-size:16px;"></i>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:var(--navy);">
                                Two-Factor Auth
                                <span class="badge ms-1" style="background:{{ Auth::user()->two_factor_enabled ? '#22C55E' : '#F59E0B' }};color:#fff;font-size:9px;border-radius:4px;">
                                    {{ Auth::user()->two_factor_enabled ? 'ON' : 'OFF' }}
                                </span>
                            </div>
                            <div class="text-muted small">{{ Auth::user()->two_factor_enabled ? 'Manage your 2FA' : 'Enable extra security' }}</div>
                        </div>
                        <i class="fas fa-chevron-right ms-auto" style="color:var(--slate);font-size:11px;"></i>
                    </div>
                </a>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── File upload: preview then auto-submit ──
    const fileInput   = document.getElementById('avatarFileInput');
    const uploadForm  = document.getElementById('avatarUploadForm');

    fileInput.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;

        const file   = this.files[0];
        const reader = new FileReader();

        reader.onload = function (e) {
            replaceAvatarPreview(e.target.result);
        };
        reader.readAsDataURL(file);

        // Submit the form after a short delay so the preview shows
        setTimeout(() => uploadForm.submit(), 150);
    });

    // ── Camera capture ──
    const openBtn       = document.getElementById('openCameraBtn');
    const closeBtn      = document.getElementById('closeCameraBtn');
    const captureBtn    = document.getElementById('captureBtn');
    const cameraArea    = document.getElementById('cameraArea');
    const video         = document.getElementById('cameraVideo');
    const canvas        = document.getElementById('cameraCanvas');
    const cameraForm    = document.getElementById('cameraUploadForm');
    const cameraInput   = document.getElementById('cameraAvatarInput');
    let stream          = null;

    openBtn.addEventListener('click', async function () {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            video.srcObject = stream;
            cameraArea.classList.remove('d-none');
            openBtn.style.display = 'none';
        } catch (err) {
            alert('Camera access denied or not available.\n\nPlease upload a photo instead.');
        }
    });

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        video.srcObject = null;
        cameraArea.classList.add('d-none');
        openBtn.style.display = '';
    }

    closeBtn.addEventListener('click', stopCamera);

    captureBtn.addEventListener('click', function () {
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        canvas.toBlob(function (blob) {
            if (!blob) { alert('Capture failed. Try again.'); return; }

            // Preview
            replaceAvatarPreview(URL.createObjectURL(blob));

            // Attach to the hidden form's file input via DataTransfer
            try {
                const dt   = new DataTransfer();
                const file = new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' });
                dt.items.add(file);
                cameraInput.files = dt.files;
                stopCamera();
                cameraForm.submit();
            } catch (e) {
                // Fallback: convert to base64 and POST as hidden field
                const reader = new FileReader();
                reader.onload = function (ev) {
                    const hidden = document.createElement('input');
                    hidden.type  = 'hidden';
                    hidden.name  = 'avatar_base64';
                    hidden.value = ev.target.result;
                    cameraForm.appendChild(hidden);
                    stopCamera();
                    cameraForm.submit();
                };
                reader.readAsDataURL(blob);
            }
        }, 'image/jpeg', 0.9);
    });

    function replaceAvatarPreview(src) {
        const wrap     = document.getElementById('avatarPreviewWrap');
        const existing = wrap.querySelector('img, div');
        if (existing) existing.remove();

        const img = document.createElement('img');
        img.id    = 'avatarImg';
        img.src   = src;
        img.alt   = 'Profile photo';
        img.style.cssText = 'width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #E65C00;display:block;';
        wrap.appendChild(img);
    }
});
</script>

<style>
.form-control:focus {
    border-color: #E65C00;
    box-shadow: 0 0 0 .2rem rgba(230,92,0,.15);
}
.card { transition: box-shadow .2s ease; }
.card:hover { box-shadow: 0 8px 30px rgba(0,0,0,.08) !important; }
</style>
@endpush
@endsection
