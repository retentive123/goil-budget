@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $action    = $webhook ? route('admin.webhooks.update', $webhook) : route('admin.webhooks.store');
    $method    = $webhook ? 'PUT' : 'POST';
    $selEvents = old('events', $webhook?->events ?? []);
@endphp

<form method="POST" action="{{ $action }}">
    @csrf @method($method)

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $webhook?->name) }}"
                           placeholder="e.g. Slack Notifications" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Endpoint URL</label>
                    <input type="url" name="url" class="form-control"
                           value="{{ old('url', $webhook?->url) }}"
                           placeholder="https://hooks.example.com/..." required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Events to send</label>
                    <div class="row g-2">
                        @foreach($events as $key => $label)
                        <div class="col-md-4 col-sm-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="events[]" value="{{ $key }}"
                                       id="ev_{{ $key }}"
                                       {{ in_array($key, $selEvents) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ev_{{ $key }}">{{ $label }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">
                        Signing Secret
                        <span class="text-muted fw-normal">(optional)</span>
                    </label>
                    <input type="text" name="secret" class="form-control font-monospace"
                           value="{{ old('secret', $webhook?->secret) }}"
                           placeholder="Leave blank for unsigned requests"
                           autocomplete="off">
                    <div class="form-text">
                        If set, payloads include an <code>X-GOIL-Signature: sha256=&lt;hex&gt;</code> header.
                    </div>
                </div>

                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active"
                               value="1" id="whActive"
                               {{ old('is_active', $webhook?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="whActive">Active</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-transparent d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                {{ $webhook ? 'Save Changes' : 'Create Webhook' }}
            </button>
            <a href="{{ route('admin.webhooks.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>
