@extends('layout', ['title' => 'New Vehicle - AutoSync Hub'])

@section('content')
    <h1>New Vehicle</h1>
    <p>Create a canonical inventory record before sending it to marketplace integrations.</p>

    <form method="post" action="{{ route('web.vehicles.store') }}" class="panel">
        @csrf
        @include('vehicles._form')

        <div class="actions">
            <button type="submit">Create Vehicle</button>
            <a href="{{ route('web.vehicles.index') }}">Cancel</a>
        </div>
    </form>
@endsection
