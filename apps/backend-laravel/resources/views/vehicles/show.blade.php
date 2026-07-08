@extends('layout', ['title' => $vehicle->external_code.' - AutoSync Hub'])

@section('content')
    <h1>{{ $vehicle->brand }} {{ $vehicle->model }}</h1>
    <p>{{ $vehicle->external_code }} · {{ $vehicle->version }} · {{ $vehicle->year }}/{{ $vehicle->model_year }}</p>

    <div class="actions">
        <form method="post" action="/api/vehicles/{{ $vehicle->id }}/sync">
            <button type="submit">Request Sync</button>
        </form>
        <a href="{{ route('vehicles.index') }}">Back to vehicles</a>
    </div>

    <section class="grid">
        <div class="panel"><span class="label">Price</span>R$ {{ number_format((float) $vehicle->price, 2, ',', '.') }}</div>
        <div class="panel"><span class="label">Mileage</span>{{ number_format($vehicle->mileage, 0, ',', '.') }} km</div>
        <div class="panel"><span class="label">Fuel</span>{{ $vehicle->fuel_type ?? 'Not informed' }}</div>
        <div class="panel"><span class="label">Transmission</span>{{ $vehicle->transmission ?? 'Not informed' }}</div>
    </section>

    <h2>Integration History</h2>
    <table>
        <thead>
            <tr>
                <th>Provider</th>
                <th>Operation</th>
                <th>Status</th>
                <th>External ref</th>
                <th>Error</th>
                <th>Last attempt</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vehicle->integrationLogs as $log)
                <tr>
                    <td>{{ $log->provider->value }}</td>
                    <td>{{ $log->operation->value }}</td>
                    <td><span class="badge {{ $log->status->value }}">{{ $log->status->value }}</span></td>
                    <td>{{ $log->external_reference ?? '-' }}</td>
                    <td>{{ $log->error_message ?? '-' }}</td>
                    <td>{{ $log->last_attempt_at?->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No integration logs yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
