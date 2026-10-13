@extends('layouts.app')

@section('content')

<div class="card shadow-sm">
    <div class="card-body">

        <h2 class="mb-4">Edit Task</h2>

        <form action="{{ route('tasks.update', $task) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Title</label>

                <input type="text"
                       name="title"
                       class="form-control"
                       value="{{ old('title', $task->title) }}">

                @error('title')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>

                <textarea name="description"
                          class="form-control"
                          rows="4">{{ old('description', $task->description) }}</textarea>

                @error('description')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Due Date</label>

                <input type="date"
                       name="due_date"
                       class="form-control"
                        min="{{ date('Y-m-d') }}"
                       value="{{ old('due_date', $task->due_date) }}">

                @error('due_date')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>

                <select name="status" class="form-select">

                    <option value="Pending"
                        {{ old('status', $task->status) === 'Pending' ? 'selected' : '' }}>
                        Pending
                    </option>

                    <option value="Completed"
                        {{ old('status', $task->status) === 'Completed' ? 'selected' : '' }}>
                        Completed
                    </option>

                </select>

                @error('status')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                Update Task
            </button>

            <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                Cancel
            </a>

        </form>

    </div>
</div>

@endsection