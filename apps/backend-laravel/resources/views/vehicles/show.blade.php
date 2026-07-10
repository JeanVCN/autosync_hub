@extends('layout', ['title' => $vehicle->external_code.' - AutoSync Hub'])

@section('content')
    <h1>{{ $vehicle->brand }} {{ $vehicle->model }}</h1>
    <p>{{ $vehicle->external_code }} · {{ $vehicle->version }} · {{ $vehicle->year }}/{{ $vehicle->model_year }}</p>

    <div class="actions">
        <form method="post" action="{{ route('web.vehicles.sync', $vehicle) }}">
            @csrf
            <button type="submit">Request Sync</button>
        </form>
        <a class="button-link" href="{{ route('web.vehicles.edit', $vehicle) }}">Edit Vehicle</a>
        <form method="post" action="{{ route('web.vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Delete this vehicle?');">
            @csrf
            @method('delete')
            <button class="button-danger" type="submit">Delete Vehicle</button>
        </form>
        <a href="{{ route('web.vehicles.index') }}">Back to vehicles</a>
    </div>

    <section class="grid">
        <div class="panel"><span class="label">Price</span>R$ {{ number_format((float) $vehicle->price, 2, ',', '.') }}</div>
        <div class="panel"><span class="label">Mileage</span>{{ number_format($vehicle->mileage, 0, ',', '.') }} km</div>
        <div class="panel"><span class="label">Fuel</span>{{ $vehicle->fuel_type ?? 'Not informed' }}</div>
        <div class="panel"><span class="label">Transmission</span>{{ $vehicle->transmission ?? 'Not informed' }}</div>
    </section>

    <h2>Current Integration Status</h2>
    <table>
        <thead>
            <tr>
                <th>Provider</th>
                <th>Status</th>
                <th>Operation</th>
                <th>External ref</th>
                <th>Attempts</th>
                <th>Last attempt</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($integrationSummary as $summary)
                <tr>
                    <td>{{ $summary['provider'] }}</td>
                    <td><span class="badge {{ $summary['status'] }}">{{ $summary['status'] }}</span></td>
                    <td>{{ $summary['operation'] ?? '-' }}</td>
                    <td>{{ $summary['external_reference'] ?? '-' }}</td>
                    <td>{{ $summary['attempts'] }}</td>
                    <td>{{ $summary['last_attempt_at'] ? \Illuminate\Support\Carbon::parse($summary['last_attempt_at'])->format('Y-m-d H:i') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

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
