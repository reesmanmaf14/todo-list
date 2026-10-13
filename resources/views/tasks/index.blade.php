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

{{-- Shows errors from the Complete/Mark Pending and Delete buttons --}}
<div class="alert alert-danger d-none" id="ajaxErrorAlert" role="alert"></div>

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

                            <tr data-task-id="{{ $task->id }}">
                                <td data-field="title">
                                    @if($task->status === 'Completed')
                                        <del>{{ $task->title }}</del>
                                    @else
                                        {{ $task->title }}
                                    @endif
                                </td>

                                <td data-field="description">
                                    {{ $task->description ?? '—' }}
                                </td>

                                <td data-field="status">
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

                                <td data-field="due_date">
                                    {{ $task->due_date ?? '—' }}
                                </td>

                                <td data-field="created">
                                    {{ $task->created_at->format('Y-m-d') }}
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">

                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                data-bs-toggle="modal"
                                                data-role="edit-button"
                                                data-bs-target="#editTaskModal-{{ $task->id }}">
                                            Edit
                                        </button>

                                        <form action="{{ route('tasks.update', $task) }}"
                                              method="POST"
                                              data-role="toggle-form">
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

                <form action="{{ route('tasks.update', $task) }}" method="POST" data-role="edit-form">
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

                        {{-- Shows session, permission, server and network errors from JavaScript --}}
                        <div class="alert alert-danger d-none" data-role="form-message" role="alert"></div>

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

        <td data-field="status">
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

                <form method="POST" data-role="edit-form">
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

                        {{-- Shows session, permission, server and network errors from JavaScript --}}
                        <div class="alert alert-danger d-none" data-role="form-message" role="alert"></div>

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
    <script src="{{ asset('js/ajax.js') }}"></script>

    <script>
        // Create, edit, complete/mark pending and delete tasks without reloading the page.
        // Requests go through ajaxRequest() in public/js/ajax.js.
        // If JavaScript is off, every form still submits normally and the page reloads as before.
        document.addEventListener('DOMContentLoaded', function () {
            const addTaskForm = document.getElementById('addTaskForm');
            const addTaskFormMessage = document.getElementById('addTaskFormMessage');
            const addTaskModal = document.getElementById('addTaskModal');
            const tasksTableBody = document.getElementById('tasksTableBody');

            // ---------- Shared helpers ----------

            // Run `work` unless the same element already has a request in flight (stops double submissions)
            async function submitOnce(lockElement, work) {
                if (lockElement.dataset.submitting === 'true') {
                    return;
                }

                const submitButtons = lockElement.querySelectorAll('button[type="submit"]');

                lockElement.dataset.submitting = 'true';
                submitButtons.forEach(function (button) {
                    button.disabled = true;
                });

                try {
                    await work();
                } finally {
                    delete lockElement.dataset.submitting;
                    submitButtons.forEach(function (button) {
                        button.disabled = false;
                    });
                }
            }

            // Put a message in an alert box (optionally with a Refresh button) and show it
            function showMessage(element, text, withRefreshButton) {
                element.replaceChildren();
                element.appendChild(document.createTextNode(text));

                if (withRefreshButton) {
                    const refreshButton = document.createElement('button');
                    refreshButton.type = 'button';
                    refreshButton.className = 'btn btn-sm btn-outline-danger ms-2';
                    refreshButton.textContent = 'Refresh page';
                    refreshButton.addEventListener('click', function () {
                        window.location.reload();
                    });
                    element.appendChild(refreshButton);
                }

                element.classList.remove('d-none');
            }

            // Remove old error messages in a form (both Blade-rendered and ones added by this script)
            function clearFormErrors(form, messageElement) {
                form.querySelectorAll('.text-danger').forEach(function (element) {
                    element.remove();
                });

                messageElement.classList.add('d-none');
                messageElement.replaceChildren();
            }

            // Show each validation error under its field (same style as the server-rendered errors)
            function showFieldErrors(form, messageElement, errors) {
                Object.keys(errors).forEach(function (fieldName) {
                    const field = form.elements[fieldName];
                    const message = errors[fieldName][0];

                    if (!field) {
                        showMessage(messageElement, message);
                        return;
                    }

                    const errorElement = document.createElement('div');
                    errorElement.className = 'text-danger';
                    errorElement.textContent = message;
                    field.after(errorElement);
                });
            }

            // Page-level success message (replaces any old flash message)
            function showSuccessMessage(text) {
                const flashAlert = document.getElementById('flashAlert');
                if (flashAlert) {
                    flashAlert.remove();
                }

                document.getElementById('ajaxErrorAlert').classList.add('d-none');

                const ajaxAlert = document.getElementById('ajaxAlert');
                ajaxAlert.textContent = text;
                ajaxAlert.classList.remove('d-none');
            }

            // Page-level error message, used by the row buttons (Complete/Mark Pending, Delete)
            function showPageError(text, withRefreshButton) {
                const flashAlert = document.getElementById('flashAlert');
                if (flashAlert) {
                    flashAlert.remove();
                }

                document.getElementById('ajaxAlert').classList.add('d-none');
                showMessage(document.getElementById('ajaxErrorAlert'), text, withRefreshButton);
            }

            // ---------- Rendering ----------

            // Fill a table row with a task's current values (works for Pending and Completed)
            function fillTaskRow(row, task) {
                const isCompleted = task.status === 'Completed';

                const titleCell = row.querySelector('[data-field="title"]');
                if (isCompleted) {
                    const del = document.createElement('del');
                    del.textContent = task.title;
                    titleCell.replaceChildren(del);
                } else {
                    titleCell.textContent = task.title;
                }

                row.querySelector('[data-field="description"]').textContent = task.description ?? '—';
                row.querySelector('[data-field="due_date"]').textContent = task.due_date ?? '—';

                const badge = row.querySelector('[data-field="status"] .badge');
                badge.className = isCompleted ? 'badge bg-success' : 'badge bg-warning text-dark';
                badge.textContent = isCompleted ? 'Completed' : 'Pending';

                // The Complete/Mark Pending button resends the current values with the opposite status,
                // so its hidden inputs must always match the latest saved values
                const toggleForm = row.querySelector('[data-role="toggle-form"]');
                toggleForm.elements.title.value = task.title;
                toggleForm.elements.description.value = task.description ?? '';
                toggleForm.elements.due_date.value = task.due_date ?? '';
                toggleForm.elements.status.value = isCompleted ? 'Pending' : 'Completed';
                toggleForm.querySelector('button[type="submit"]').textContent = isCompleted ? 'Mark Pending' : 'Complete';
            }

            // Fill a task's Edit modal form with its current values
            function fillEditForm(editForm, task) {
                editForm.elements.title.value = task.title;
                editForm.elements.description.value = task.description ?? '';
                editForm.elements.due_date.value = task.due_date ?? '';
                editForm.elements.status.value = task.status;
            }

            // After an update, refresh both the row and the Edit modal so neither holds stale values
            function applyUpdatedTask(task) {
                const row = tasksTableBody.querySelector('tr[data-task-id="' + task.id + '"]');
                if (row) {
                    fillTaskRow(row, task);
                }

                const editModal = document.getElementById('editTaskModal-' + task.id);
                if (editModal) {
                    fillEditForm(editModal.querySelector('form'), task);
                }
            }

            // Build the Edit modal for a new task from its <template>
            function buildEditModal(task, updateUrl) {
                const modal = document.importNode(document.getElementById('editTaskModalTemplate').content.firstElementChild, true);
                const modalId = 'editTaskModal-' + task.id;
                const labelId = 'editTaskModalLabel-' + task.id;

                modal.id = modalId;
                modal.setAttribute('aria-labelledby', labelId);
                modal.querySelector('.modal-title').id = labelId;

                const editForm = modal.querySelector('form');
                editForm.action = updateUrl;
                editForm.elements.task_id.value = task.id;
                fillEditForm(editForm, task);

                return modal;
            }

            // Build the table row for a new task from its <template>
            function buildTaskRow(task, createdDate, urls) {
                // importNode (not cloneNode) creates the copy in this page, so the Delete form's
                // inline onsubmit="return confirm(...)" is active on the new row
                const row = document.importNode(document.getElementById('taskRowTemplate').content.firstElementChild, true);

                row.dataset.taskId = task.id;
                row.querySelector('[data-field="created"]').textContent = createdDate;
                row.querySelector('[data-role="edit-button"]')
                    .setAttribute('data-bs-target', '#editTaskModal-' + task.id);
                row.querySelector('[data-role="toggle-form"]').action = urls.update;
                row.querySelector('[data-role="delete-form"]').action = urls.destroy;

                fillTaskRow(row, task);

                return row;
            }

            // Show the "No tasks yet" message when the table becomes empty
            function updateEmptyState() {
                const isEmpty = tasksTableBody.children.length === 0;

                document.getElementById('emptyTasksMessage').classList.toggle('d-none', !isEmpty);
                document.getElementById('tasksTableWrapper').classList.toggle('d-none', isEmpty);
            }

            // ---------- Create ----------

            function handleCreateSuccess(data) {
                // Newest tasks are shown first, so the new row goes at the top
                tasksTableBody.prepend(buildTaskRow(data.task, data.created_date, data.urls));
                addTaskModal.before(buildEditModal(data.task, data.urls.update));
                updateEmptyState();

                // Clear the form (set values directly: form.reset() would restore old() values from a failed submit)
                addTaskForm.elements.title.value = '';
                addTaskForm.elements.description.value = '';
                addTaskForm.elements.due_date.value = '';

                bootstrap.Modal.getOrCreateInstance(addTaskModal).hide();

                showSuccessMessage(data.message);
            }

            addTaskForm.addEventListener('submit', function (event) {
                event.preventDefault();

                submitOnce(addTaskForm, async function () {
                    clearFormErrors(addTaskForm, addTaskFormMessage);

                    const result = await ajaxRequest(addTaskForm.action, {
                        method: 'POST',
                        body: new FormData(addTaskForm),
                    });
                    const data = result.data;

                    if (result.status === 201 && data && data.task) {
                        handleCreateSuccess(data);
                    } else if (result.status === 422 && data && data.errors) {
                        showFieldErrors(addTaskForm, addTaskFormMessage, data.errors);
                    } else {
                        const error = ajaxErrorMessage(result, { forbidden: 'You are not allowed to create tasks.' });
                        showMessage(addTaskFormMessage, error.text, error.withRefreshButton);
                    }
                });
            });

            // ---------- Edit (modal form) ----------

            function submitEditForm(editForm) {
                const messageElement = editForm.querySelector('[data-role="form-message"]');

                submitOnce(editForm, async function () {
                    clearFormErrors(editForm, messageElement);

                    // POST with the form's hidden _method=PUT, exactly like the normal form submission
                    const result = await ajaxRequest(editForm.action, {
                        method: 'POST',
                        body: new FormData(editForm),
                    });
                    const data = result.data;

                    if (result.ok && data && data.task) {
                        applyUpdatedTask(data.task);
                        bootstrap.Modal.getOrCreateInstance(editForm.closest('.modal')).hide();
                        showSuccessMessage(data.message);
                    } else if (result.status === 422 && data && data.errors) {
                        showFieldErrors(editForm, messageElement, data.errors);
                    } else {
                        const error = ajaxErrorMessage(result, { forbidden: 'You are not allowed to edit this task.' });
                        showMessage(messageElement, error.text, error.withRefreshButton);
                    }
                });
            }

            // ---------- Status (Complete / Mark Pending button) ----------

            function submitToggleForm(toggleForm) {
                // Lock the whole row so Complete and Delete can't run at the same time
                submitOnce(toggleForm.closest('tr'), async function () {
                    // POST with the form's hidden _method=PUT, exactly like the normal form submission
                    const result = await ajaxRequest(toggleForm.action, {
                        method: 'POST',
                        body: new FormData(toggleForm),
                    });
                    const data = result.data;

                    if (result.ok && data && data.task) {
                        applyUpdatedTask(data.task);
                        showSuccessMessage(data.message);
                    } else if (result.status === 422 && data && data.errors) {
                        // There are no visible fields for this form, so show the first error at the top
                        const firstField = Object.keys(data.errors)[0];
                        showPageError(data.errors[firstField][0]);
                    } else {
                        const error = ajaxErrorMessage(result, { forbidden: 'You are not allowed to update this task.' });
                        showPageError(error.text, error.withRefreshButton);
                    }
                });
            }

            // ---------- Delete ----------

            function submitDeleteForm(deleteForm) {
                const row = deleteForm.closest('tr');

                submitOnce(row, async function () {
                    // POST with the form's hidden _method=DELETE, exactly like the normal form submission
                    const result = await ajaxRequest(deleteForm.action, {
                        method: 'POST',
                        body: new FormData(deleteForm),
                    });

                    if (result.ok) {
                        // Remove the row (and its Edit modal) only after the server confirmed the delete
                        const editModal = document.getElementById('editTaskModal-' + row.dataset.taskId);
                        if (editModal) {
                            editModal.remove();
                        }

                        row.remove();
                        updateEmptyState();
                        showSuccessMessage(result.data && result.data.message ? result.data.message : 'Task deleted successfully!');
                    } else {
                        const error = ajaxErrorMessage(result, { forbidden: 'You are not allowed to delete this task.' });
                        showPageError(error.text, error.withRefreshButton);
                    }
                });
            }

            // One listener for all edit, toggle and delete forms, including ones added after page load
            document.addEventListener('submit', function (event) {
                const form = event.target;

                // The Delete form's confirm() already cancelled the submission if the user clicked "Cancel"
                if (event.defaultPrevented) {
                    return;
                }

                if (form.matches('[data-role="edit-form"]')) {
                    event.preventDefault();
                    submitEditForm(form);
                } else if (form.matches('[data-role="toggle-form"]')) {
                    event.preventDefault();
                    submitToggleForm(form);
                } else if (form.matches('[data-role="delete-form"]')) {
                    event.preventDefault();
                    submitDeleteForm(form);
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