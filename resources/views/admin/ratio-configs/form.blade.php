@extends('layouts.app')
@section('title', $ratio->exists ? 'Edit Ratio' : 'New Ratio')
@section('content')

<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <div class="small mb-1">
                    <a href="{{ route('admin.ratio-configs.index') }}"
                       style="color:#64748B;text-decoration:none">Ratio Configs</a>
                    <span class="mx-1" style="color:#CBD5E1">/</span>
                    <span style="color:#1B2A4A;font-weight:600">
                        {{ $ratio->exists ? 'Edit' : 'New Ratio' }}
                    </span>
                </div>
                <h5 class="fw-bold mb-0" style="color:#1B2A4A">
                    {{ $ratio->exists ? $ratio->name : 'New Ratio' }}
                </h5>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 small">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ $ratio->exists ? route('admin.ratio-configs.update', $ratio) : route('admin.ratio-configs.store') }}">
            @csrf
            @if($ratio->exists) @method('PUT') @endif

            {{-- Identity --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
                <div class="card-header px-4 py-3 border-0"
                     style="background:#E65C00;color:#fff;border-radius:11px 11px 0 0">
                    <span class="fw-semibold" style="font-size:13px">Identity</span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">Name</label>
                        <input type="text" name="name"
                               value="{{ old('name', $ratio->name) }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Expense to Revenue Ratio">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">Description</label>
                        <textarea name="description" rows="2"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="What does this ratio measure?">{{ old('description', $ratio->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Unit / Symbol</label>
                            <input type="text" name="unit"
                                   value="{{ old('unit', $ratio->unit ?? '%') }}"
                                   class="form-control @error('unit') is-invalid @enderror"
                                   placeholder="%">
                            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Multiply by</label>
                            <input type="number" name="multiply_by" step="0.0001"
                                   value="{{ old('multiply_by', $ratio->multiply_by ?? 100) }}"
                                   class="form-control @error('multiply_by') is-invalid @enderror">
                            <div class="form-text">100 for %, 1 for raw ratio</div>
                            @error('multiply_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Sort Order</label>
                            <input type="number" name="sort_order" min="0"
                                   value="{{ old('sort_order', $ratio->sort_order ?? 0) }}"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="higher_is_better" value="1"
                                       id="hib" class="form-check-input"
                                       {{ old('higher_is_better', $ratio->higher_is_better ?? true) ? 'checked' : '' }}>
                                <label for="hib" class="form-check-label small" style="color:#1B2A4A">
                                    Higher value is better
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1"
                                       id="active" class="form-check-input"
                                       {{ old('is_active', $ratio->is_active ?? true) ? 'checked' : '' }}>
                                <label for="active" class="form-check-label small" style="color:#1B2A4A">
                                    Active (show in report)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formula --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
                <div class="card-header px-4 py-3 border-0"
                     style="background:#1B2A4A;color:#fff;border-radius:11px 11px 0 0">
                    <span class="fw-semibold" style="font-size:13px">Formula &nbsp;
                        <span style="font-weight:400;opacity:.7;font-size:12px">Numerator ÷ Denominator × Multiply by</span>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">

                        {{-- Numerator --}}
                        <div class="col-md-6">
                            <div class="p-3 rounded-2" style="background:#EFF6FF;border:1px solid #BFDBFE">
                                <div class="fw-semibold mb-2" style="color:#1E40AF;font-size:13px">Numerator (top)</div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold" style="color:#1B2A4A">Data Source</label>
                                    <select name="numerator_source" class="form-select form-select-sm">
                                        <option value="budget" {{ old('numerator_source', $ratio->numerator_source ?? 'budget') === 'budget' ? 'selected' : '' }}>
                                            Budget (approved line items)
                                        </option>
                                        <option value="actual" {{ old('numerator_source', $ratio->numerator_source ?? 'budget') === 'actual' ? 'selected' : '' }}>
                                            Actuals (confirmed spend)
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold" style="color:#1B2A4A">
                                        Include Category Types
                                    </label>
                                    @php $selNum = old('numerator_types', $ratio->numerator_types ?? []); @endphp
                                    @foreach($allTypes as $key => $label)
                                    <div class="form-check">
                                        <input type="checkbox" name="numerator_types[]" value="{{ $key }}"
                                               id="nt_{{ $key }}" class="form-check-input num-type"
                                               {{ in_array($key, $selNum) ? 'checked' : '' }}>
                                        <label for="nt_{{ $key }}" class="form-check-label small"
                                               style="color:#1B2A4A">{{ $label }}</label>
                                    </div>
                                    @endforeach
                                    @error('numerator_types')<div class="text-danger" style="font-size:11px">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Denominator --}}
                        <div class="col-md-6">
                            <div class="p-3 rounded-2" style="background:#F0FDF4;border:1px solid #BBF7D0">
                                <div class="fw-semibold mb-2" style="color:#166534;font-size:13px">Denominator (bottom)</div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold" style="color:#1B2A4A">Data Source</label>
                                    <select name="denominator_source" class="form-select form-select-sm">
                                        <option value="budget" {{ old('denominator_source', $ratio->denominator_source ?? 'budget') === 'budget' ? 'selected' : '' }}>
                                            Budget (approved line items)
                                        </option>
                                        <option value="actual" {{ old('denominator_source', $ratio->denominator_source ?? 'budget') === 'actual' ? 'selected' : '' }}>
                                            Actuals (confirmed spend)
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold" style="color:#1B2A4A">
                                        Include Category Types
                                    </label>
                                    @php $selDen = old('denominator_types', $ratio->denominator_types ?? []); @endphp
                                    @foreach($allTypes as $key => $label)
                                    <div class="form-check">
                                        <input type="checkbox" name="denominator_types[]" value="{{ $key }}"
                                               id="dt_{{ $key }}" class="form-check-input den-type"
                                               {{ in_array($key, $selDen) ? 'checked' : '' }}>
                                        <label for="dt_{{ $key }}" class="form-check-label small"
                                               style="color:#1B2A4A">{{ $label }}</label>
                                    </div>
                                    @endforeach
                                    @error('denominator_types')<div class="text-danger" style="font-size:11px">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Live formula preview --}}
                    <div class="mt-3 p-3 rounded-2" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:12px">
                        <span class="fw-semibold" style="color:#1B2A4A">Preview: </span>
                        <span id="formula-preview" style="color:#475569;font-family:monospace"></span>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-semibold px-4"
                        style="background:#E65C00;color:#fff;border:none;border-radius:8px">
                    {{ $ratio->exists ? 'Save Changes' : 'Create Ratio' }}
                </button>
                <a href="{{ route('admin.ratio-configs.index') }}"
                   class="btn fw-semibold px-4"
                   style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<style>
.form-control,.form-select{border-color:#E2E8F0;border-radius:8px;padding:8px 12px;font-size:13px}
.form-control:focus,.form-select:focus{border-color:#E65C00;box-shadow:0 0 0 3px rgba(230,92,0,.1)}
.form-check-input:checked{background-color:#E65C00;border-color:#E65C00}
</style>

<script>
function updatePreview() {
    const numTypes  = [...document.querySelectorAll('.num-type:checked')].map(el => el.parentElement.querySelector('label').textContent.trim());
    const denTypes  = [...document.querySelectorAll('.den-type:checked')].map(el => el.parentElement.querySelector('label').textContent.trim());
    const numSrc    = document.querySelector('[name=numerator_source]')?.value || 'budget';
    const denSrc    = document.querySelector('[name=denominator_source]')?.value || 'budget';
    const mult      = document.querySelector('[name=multiply_by]')?.value || '100';
    const unit      = document.querySelector('[name=unit]')?.value || '%';
    const numStr    = numTypes.length ? numTypes.join(' + ') : '—';
    const denStr    = denTypes.length ? denTypes.join(' + ') : '—';
    document.getElementById('formula-preview').textContent =
        `(${numSrc}: ${numStr}) ÷ (${denSrc}: ${denStr}) × ${mult}  [${unit}]`;
}
document.querySelectorAll('.num-type,.den-type,[name=numerator_source],[name=denominator_source],[name=multiply_by],[name=unit]')
    .forEach(el => el.addEventListener('change', updatePreview));
document.querySelector('[name=multiply_by]')?.addEventListener('input', updatePreview);
document.querySelector('[name=unit]')?.addEventListener('input', updatePreview);
updatePreview();
</script>
@endsection
