{{-- A regular user's dashboard: their counts + a link to their task list (on the Tasks page) --}}
@extends('layouts.app')

@section('content')

<h2 class="mb-4">My Dashboard</h2>

@include('tasks.partials.task-counts')

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-list-check" aria-hidden="true"></i> My Tasks</div>
    <div class="card-body">
        <a href="{{ route('tasks.index') }}" class="btn btn-primary">View My Tasks</a>
    </div>
</div>

@endsection
