@php
    $statusValue = old('status', $vehicle->status instanceof \App\Enums\VehicleStatus ? $vehicle->status->value : ($vehicle->status ?? 'active'));
@endphp

<div class="form-grid">
    <label>
        External code
        <input name="external_code" value="{{ old('external_code', $vehicle->external_code) }}" required maxlength="50">
    </label>

    <label>
        Status
        <select name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected($statusValue === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </label>

    <label>
        Brand
        <input name="brand" value="{{ old('brand', $vehicle->brand) }}" required maxlength="80">
    </label>

    <label>
        Model
        <input name="model" value="{{ old('model', $vehicle->model) }}" required maxlength="80">
    </label>

    <label>
        Version
        <input name="version" value="{{ old('version', $vehicle->version) }}" maxlength="120">
    </label>

    <label>
        Color
        <input name="color" value="{{ old('color', $vehicle->color) }}" maxlength="50">
    </label>

    <label>
        Year
        <input type="number" name="year" value="{{ old('year', $vehicle->year) }}" required min="1900" max="2100">
    </label>

    <label>
        Model year
        <input type="number" name="model_year" value="{{ old('model_year', $vehicle->model_year) }}" required min="1900" max="2100">
    </label>

    <label>
        Price
        <input type="number" name="price" value="{{ old('price', $vehicle->price) }}" required min="0" step="0.01">
    </label>

    <label>
        Mileage
        <input type="number" name="mileage" value="{{ old('mileage', $vehicle->mileage) }}" required min="0">
    </label>

    <label>
        Fuel type
        <input name="fuel_type" value="{{ old('fuel_type', $vehicle->fuel_type) }}" maxlength="50">
    </label>

    <label>
        Transmission
        <input name="transmission" value="{{ old('transmission', $vehicle->transmission) }}" maxlength="50">
    </label>

    <label class="full-span">
        Description
        <textarea name="description">{{ old('description', $vehicle->description) }}</textarea>
    </label>
</div>
