@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Tasks</h2>

    <button type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#addTaskModal">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add Task
    </button>
</div>

@include('tasks.partials.task-list')

@endsection
