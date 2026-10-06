@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $action = $field
        ? route('admin.custom-budget-fields.update', $field)
        : route('admin.custom-budget-fields.store');
    $method = $field ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $action }}">
    @csrf @method($method)

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Label <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control"
                           value="{{ old('label', $field?->label) }}"
                           placeholder="e.g. Cost Centre Code" required>
                    <div class="form-text">Shown as column header on the entry form.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Field Type <span class="text-danger">*</span></label>
                    <select name="field_type" id="fieldType" class="form-select" required>
                        @foreach(['text' => 'Text', 'number' => 'Number', 'select' => 'Dropdown (Select)', 'boolean' => 'Yes / No (Boolean)'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('field_type', $field?->field_type) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Display Order</label>
                    <input type="number" name="display_order" class="form-control"
                           value="{{ old('display_order', $field?->display_order ?? 0) }}"
                           min="0">
                    <div class="form-text">Lower numbers appear first.</div>
                </div>

                <div class="col-12" id="optionsRow"
                     style="{{ old('field_type', $field?->field_type) === 'select' ? '' : 'display:none' }}">
                    <label class="form-label fw-semibold">Options (one per line)</label>
                    <textarea name="options" class="form-control font-monospace" rows="5"
                              placeholder="Option A&#10;Option B&#10;Option C">{{ old('options', $optionsText) }}</textarea>
                    <div class="form-text">Each line becomes a selectable dropdown option.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Placeholder</label>
                    <input type="text" name="placeholder" class="form-control"
                           value="{{ old('placeholder', $field?->placeholder) }}"
                           placeholder="Hint text shown inside the input">
                </div>

                <div class="col-md-3 d-flex align-items-center gap-3 mt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_required"
                               value="1" id="isRequired"
                               {{ old('is_required', $field?->is_required) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="isRequired">Required</label>
                    </div>

                    @if($field)
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active"
                               value="1" id="isActive"
                               {{ old('is_active', $field->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="isActive">Active</label>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-footer bg-transparent d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                {{ $field ? 'Save Changes' : 'Create Field' }}
            </button>
            <a href="{{ route('admin.custom-budget-fields.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

<script>
document.getElementById('fieldType').addEventListener('change', function() {
    document.getElementById('optionsRow').style.display = this.value === 'select' ? '' : 'none';
});
</script>
