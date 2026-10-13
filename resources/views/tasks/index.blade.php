@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Tasks</h2>

    <button type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#addTaskModal">
        + Add Task
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success" id="flashAlert">
        {{ session('success') }}
    </div>
@endif

{{-- Filled by JavaScript after a task is added without a page reload --}}
<div class="alert alert-success d-none" id="ajaxAlert" role="status"></div>

<div class="card shadow-sm">
    <div class="card-body">

        {{-- Both the message and the table are always rendered so JavaScript can switch between them --}}
            <p id="emptyTasksMessage" class="text-center text-muted mb-0 {{ $tasks->isEmpty() ? '' : 'd-none' }}">
                No tasks yet. Add your first task!
            </p>

            <div id="tasksTableWrapper" class="table-responsive {{ $tasks->isEmpty() ? 'd-none' : '' }}">
                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="tasksTableBody">
                        @foreach($tasks as $task)

                            <tr>
                                <td>
                                    @if($task->status === 'Completed')
                                        <del>{{ $task->title }}</del>
                                    @else
                                        {{ $task->title }}
                                    @endif
                                </td>

                                <td>
                                    {{ $task->description ?? '—' }}
                                </td>

                                <td>
                                    @if($task->status === 'Completed')
                                        <span class="badge bg-success">
                                            Completed
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $task->due_date ?? '—' }}
                                </td>

                                <td>
                                    {{ $task->created_at->format('Y-m-d') }}
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">

                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editTaskModal-{{ $task->id }}">
                                            Edit
                                        </button>

                                        <form action="{{ route('tasks.update', $task) }}"
                                              method="POST">
                                            @csrf
                                            @method('PUT')

                                            <input type="hidden"
                                                   name="title"
                                                   value="{{ $task->title }}">

                                            <input type="hidden"
                                                   name="description"
                                                   value="{{ $task->description }}">

                                            <input type="hidden"
                                                   name="due_date"
                                                   value="{{ $task->due_date }}">

                                            <input type="hidden"
                                                   name="status"
                                                   value="{{ $task->status === 'Pending' ? 'Completed' : 'Pending' }}">

                                            <button type="submit"
                                                    class="btn btn-sm btn-success">
                                                {{ $task->status === 'Pending' ? 'Complete' : 'Mark Pending' }}
                                            </button>
                                        </form>

                                        <form action="{{ route('tasks.destroy', $task) }}"
                                              method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this task?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="btn btn-sm btn-danger">
                                                Delete
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                        @endforeach
                    </tbody>

                </table>
            </div>

    </div>
</div>

@php
    // True only when the Add Task form was submitted and failed validation
    $addTaskFailed = old('form_type') === 'create' && $errors->any();

    // ID of the task whose Edit form was submitted and failed validation (null otherwise)
    $editTaskFailedId = old('form_type') === 'edit' && $errors->any() ? (int) old('task_id') : null;
@endphp

@foreach($tasks as $task)
    @php
        $editFailed = $editTaskFailedId === $task->id;
    @endphp

    <div class="modal fade"
         id="editTaskModal-{{ $task->id }}"
         tabindex="-1"
         aria-labelledby="editTaskModalLabel-{{ $task->id }}"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <form action="{{ route('tasks.update', $task) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="form_type" value="edit">
                    <input type="hidden" name="task_id" value="{{ $task->id }}">

                    <div class="modal-header">
                        <h2 class="modal-title fs-4" id="editTaskModalLabel-{{ $task->id }}">Edit Task</h2>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Title</label>

                            <input type="text"
                                   name="title"
                                   class="form-control"
                                   value="{{ $editFailed ? old('title') : $task->title }}">

                            @if($editFailed)
                                @error('title')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>

                            <textarea name="description"
                                      class="form-control"
                                      rows="4">{{ $editFailed ? old('description') : $task->description }}</textarea>

                            @if($editFailed)
                                @error('description')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Due Date</label>

                            <input type="date"
                                   name="due_date"
                                   class="form-control"
                                   min="{{ min(date('Y-m-d'), $task->due_date ?? date('Y-m-d')) }}"
                                   value="{{ $editFailed ? old('due_date') : $task->due_date }}">

                            @if($editFailed)
                                @error('due_date')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>

                            @php
                                $selectedStatus = $editFailed ? old('status') : $task->status;
                            @endphp

                            <select name="status" class="form-select">
                                <option value="Pending" {{ $selectedStatus === 'Pending' ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="Completed" {{ $selectedStatus === 'Completed' ? 'selected' : '' }}>
                                    Completed
                                </option>
                            </select>

                            @if($editFailed)
                                @error('status')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Update Task
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endforeach

<div class="modal fade"
     id="addTaskModal"
     tabindex="-1"
     aria-labelledby="addTaskModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <form action="{{ route('tasks.store') }}" method="POST" id="addTaskForm">
                @csrf

                <input type="hidden" name="form_type" value="create">

                <div class="modal-header">
                    <h2 class="modal-title fs-4" id="addTaskModalLabel">Add New Task</h2>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    {{-- Shows session, permission, server and network errors from JavaScript --}}
                    <div class="alert alert-danger d-none" id="addTaskFormMessage" role="alert"></div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>

                        <input type="text"
                               name="title"
                               class="form-control"
                               value="{{ $addTaskFailed ? old('title') : '' }}">

                        @if($addTaskFailed)
                            @error('title')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>

                        <textarea name="description"
                                  class="form-control"
                                  rows="4">{{ $addTaskFailed ? old('description') : '' }}</textarea>

                        @if($addTaskFailed)
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Due Date</label>

                        <input type="date"
                               name="due_date"
                               class="form-control"
                               min="{{ date('Y-m-d') }}"
                               value="{{ $addTaskFailed ? old('due_date') : '' }}">

                        @if($addTaskFailed)
                            @error('due_date')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Task
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

{{-- Blueprints for a task added without a page reload. JavaScript clones them and fills in the task's data.
     New tasks are always Pending, so only the Pending markup is needed. --}}
<template id="taskRowTemplate">
    <tr>
        <td data-field="title"></td>

        <td data-field="description"></td>

        <td>
            <span class="badge bg-warning text-dark">
                Pending
            </span>
        </td>

        <td data-field="due_date"></td>

        <td data-field="created"></td>

        <td>
            <div class="d-flex gap-2 flex-wrap">

                <button type="button"
                        class="btn btn-sm btn-primary"
                        data-bs-toggle="modal"
                        data-role="edit-button">
                    Edit
                </button>

                <form method="POST" data-role="toggle-form">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="title">
                    <input type="hidden" name="description">
                    <input type="hidden" name="due_date">
                    <input type="hidden" name="status" value="Completed">

                    <button type="submit"
                            class="btn btn-sm btn-success">
                        Complete
                    </button>
                </form>

                <form method="POST"
                      data-role="delete-form"
                      onsubmit="return confirm('Are you sure you want to delete this task?')">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            class="btn btn-sm btn-danger">
                        Delete
                    </button>
                </form>

            </div>
        </td>
    </tr>
</template>

<template id="editTaskModalTemplate">
    <div class="modal fade"
         tabindex="-1"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <form method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="form_type" value="edit">
                    <input type="hidden" name="task_id">

                    <div class="modal-header">
                        <h2 class="modal-title fs-4">Edit Task</h2>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Title</label>

                            <input type="text"
                                   name="title"
                                   class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>

                            <textarea name="description"
                                      class="form-control"
                                      rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Due Date</label>

                            <input type="date"
                                   name="due_date"
                                   class="form-control"
                                   min="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>

                            <select name="status" class="form-select">
                                <option value="Pending" selected>
                                    Pending
                                </option>
                                <option value="Completed">
                                    Completed
                                </option>
                            </select>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Update Task
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</template>

@endsection

@push('scripts')
    <script>
        // Add a task without reloading the page, using the Fetch API.
        // If JavaScript is off, the form still submits normally and the page reloads as before.
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('addTaskForm');
            const submitButton = form.querySelector('button[type="submit"]');
            const formMessage = document.getElementById('addTaskFormMessage');
            const addTaskModal = document.getElementById('addTaskModal');

            let isSubmitting = false; // stops double submissions while a request is in flight

            // Remove old error messages (both Blade-rendered and ones added by this script)
            function clearFormErrors() {
                form.querySelectorAll('.text-danger').forEach(function (element) {
                    element.remove();
                });

                formMessage.classList.add('d-none');
                formMessage.replaceChildren();
            }

            // Show a general message at the top of the form (optionally with a Refresh button)
            function showFormMessage(text, withRefreshButton) {
                formMessage.replaceChildren();
                formMessage.appendChild(document.createTextNode(text));

                if (withRefreshButton) {
                    const refreshButton = document.createElement('button');
                    refreshButton.type = 'button';
                    refreshButton.className = 'btn btn-sm btn-outline-danger ms-2';
                    refreshButton.textContent = 'Refresh page';
                    refreshButton.addEventListener('click', function () {
                        window.location.reload();
                    });
                    formMessage.appendChild(refreshButton);
                }

                formMessage.classList.remove('d-none');
            }

            // Show each validation error under its field (same style as the server-rendered errors)
            function showFieldErrors(errors) {
                Object.keys(errors).forEach(function (fieldName) {
                    const field = form.elements[fieldName];
                    const message = errors[fieldName][0];

                    if (!field) {
                        showFormMessage(message);
                        return;
                    }

                    const errorElement = document.createElement('div');
                    errorElement.className = 'text-danger';
                    errorElement.textContent = message;
                    field.after(errorElement);
                });
            }

            // Build the Edit modal for the new task from its <template>
            function buildEditModal(task, updateUrl) {
                const modal = document.getElementById('editTaskModalTemplate').content.firstElementChild.cloneNode(true);
                const modalId = 'editTaskModal-' + task.id;
                const labelId = 'editTaskModalLabel-' + task.id;

                modal.id = modalId;
                modal.setAttribute('aria-labelledby', labelId);
                modal.querySelector('.modal-title').id = labelId;

                const editForm = modal.querySelector('form');
                editForm.action = updateUrl;
                editForm.elements.task_id.value = task.id;
                editForm.elements.title.value = task.title;
                editForm.elements.description.value = task.description ?? '';
                editForm.elements.due_date.value = task.due_date ?? '';

                return modal;
            }

            // Build the table row for the new task from its <template>
            function buildTaskRow(task, createdDate, urls) {
                const row = document.getElementById('taskRowTemplate').content.firstElementChild.cloneNode(true);

                row.querySelector('[data-field="title"]').textContent = task.title;
                row.querySelector('[data-field="description"]').textContent = task.description ?? '—';
                row.querySelector('[data-field="due_date"]').textContent = task.due_date ?? '—';
                row.querySelector('[data-field="created"]').textContent = createdDate;

                row.querySelector('[data-role="edit-button"]')
                    .setAttribute('data-bs-target', '#editTaskModal-' + task.id);

                // "Complete" button resends the current values with status = Completed
                const toggleForm = row.querySelector('[data-role="toggle-form"]');
                toggleForm.action = urls.update;
                toggleForm.elements.title.value = task.title;
                toggleForm.elements.description.value = task.description ?? '';
                toggleForm.elements.due_date.value = task.due_date ?? '';

                row.querySelector('[data-role="delete-form"]').action = urls.destroy;

                return row;
            }

            function handleSuccess(data) {
                // Newest tasks are shown first, so the new row goes at the top
                document.getElementById('tasksTableBody').prepend(buildTaskRow(data.task, data.created_date, data.urls));
                addTaskModal.before(buildEditModal(data.task, data.urls.update));

                document.getElementById('emptyTasksMessage').classList.add('d-none');
                document.getElementById('tasksTableWrapper').classList.remove('d-none');

                // Clear the form (set values directly: form.reset() would restore old() values from a failed submit)
                form.elements.title.value = '';
                form.elements.description.value = '';
                form.elements.due_date.value = '';

                bootstrap.Modal.getOrCreateInstance(addTaskModal).hide();

                // Replace any old flash message with the new success message
                const flashAlert = document.getElementById('flashAlert');
                if (flashAlert) {
                    flashAlert.remove();
                }

                const ajaxAlert = document.getElementById('ajaxAlert');
                ajaxAlert.textContent = data.message;
                ajaxAlert.classList.remove('d-none');
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (isSubmitting) {
                    return;
                }

                isSubmitting = true;
                submitButton.disabled = true;
                clearFormErrors();

                try {
                    let response;

                    try {
                        response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': form.elements._token.value,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });
                    } catch (networkError) {
                        showFormMessage('Could not reach the server. Check your internet connection and try again.');
                        return;
                    }

                    // The body may not be JSON (e.g. an HTML error page), so don't let parsing crash
                    const data = await response.json().catch(function () {
                        return null;
                    });

                    if (response.status === 201 && data && data.task) {
                        handleSuccess(data);
                    } else if (response.status === 422 && data && data.errors) {
                        showFieldErrors(data.errors);
                    } else if (response.status === 401 || response.status === 419) {
                        showFormMessage('Your session has expired. Please refresh the page (and log in again if asked), then try again.', true);
                    } else if (response.status === 403) {
                        showFormMessage('You are not allowed to create tasks.');
                    } else {
                        showFormMessage('Something went wrong on the server. Please try again later.');
                    }
                } finally {
                    isSubmitting = false;
                    submitButton.disabled = false;
                }
            });
        });
    </script>
@endpush

@if($addTaskFailed)
    @push('scripts')
        <script>
            // Reopen the Add Task modal so validation errors and entered values are visible
            document.addEventListener('DOMContentLoaded', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('addTaskModal')).show();
            });
        </script>
    @endpush
@endif

@if($editTaskFailedId)
    @push('scripts')
        <script>
            // Reopen the Edit modal of the task that failed validation
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('editTaskModal-{{ $editTaskFailedId }}');

                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endpush
@endif