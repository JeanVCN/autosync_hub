@extends('layout', ['title' => 'Vehicles - AutoSync Hub'])

@section('content')
    <h1>Vehicles</h1>
    <p>Canonical inventory records ready to be synchronized with automotive marketplaces.</p>

    <table>
        <thead>
            <tr>
                <th>External code</th>
                <th>Vehicle</th>
                <th>Year</th>
                <th>Price</th>
                <th>Status</th>
                <th>Latest integrations</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vehicles as $vehicle)
                <tr>
                    <td><a href="{{ route('vehicles.show', $vehicle) }}">{{ $vehicle->external_code }}</a></td>
                    <td>{{ $vehicle->brand }} {{ $vehicle->model }}<br><span class="muted">{{ $vehicle->version }}</span></td>
                    <td>{{ $vehicle->year }}/{{ $vehicle->model_year }}</td>
                    <td>R$ {{ number_format((float) $vehicle->price, 2, ',', '.') }}</td>
                    <td><span class="badge">{{ $vehicle->status->value }}</span></td>
                    <td>
                        @foreach ($vehicle->integrationLogs->unique('provider')->take(3) as $log)
                            <span class="badge {{ $log->status->value }}">{{ $log->provider->value }}: {{ $log->status->value }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $vehicles->links() }}
@endsection
