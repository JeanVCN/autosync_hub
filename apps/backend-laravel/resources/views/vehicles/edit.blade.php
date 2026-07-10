@extends('layout', ['title' => 'Edit '.$vehicle->external_code.' - AutoSync Hub'])

@section('content')
    <h1>Edit Vehicle</h1>
    <p>{{ $vehicle->external_code }} · {{ $vehicle->brand }} {{ $vehicle->model }}</p>

    <form method="post" action="{{ route('web.vehicles.update', $vehicle) }}" class="panel">
        @csrf
        @method('put')
        @include('vehicles._form')

        <div class="actions">
            <button type="submit">Save Vehicle</button>
            <a href="{{ route('web.vehicles.show', $vehicle) }}">Cancel</a>
        </div>
    </form>
@endsection
