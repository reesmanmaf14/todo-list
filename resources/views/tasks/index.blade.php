@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    {{-- The dashboards (which extend this page) replace the heading and add their counts below --}}
    <h2>@section('heading')@can('view all tasks') All Tasks @else My Tasks @endcan @show</h2>

    <button type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#addTaskModal">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add Task
    </button>
</div>

@yield('counts')

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
            <div id="emptyTasksMessage" class="text-center text-muted py-4 {{ $tasks->isEmpty() ? '' : 'd-none' }}">
                <i class="bi bi-clipboard-check display-4 d-block mb-2" aria-hidden="true"></i>

                <p class="mb-3">No tasks yet. Add your first task!</p>

                <button type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#addTaskModal">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Add Task
                </button>
            </div>

            <div id="tasksTableWrapper" class="table-responsive {{ $tasks->isEmpty() ? 'd-none' : '' }}">

                {{-- Filter tabs: JavaScript shows only the rows that match (see applyFilter) --}}
                <ul class="nav nav-pills mb-3" id="taskFilters">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" data-filter="all">All</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-filter="Pending">Pending</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-filter="Completed">Completed</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-filter="overdue">Overdue</button>
                    </li>
                </ul>

                <p id="noFilterMatchesMessage" class="text-center text-muted d-none">
                    No tasks match this filter.
                </p>

                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>Title</th>
                            @can('view all tasks')
                                <th>Assigned to</th>
                            @endcan
                            <th>Description</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="tasksTableBody">
                        @foreach($tasks as $task)
                            @include('tasks.partials.task-row')
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
    {{-- No Edit popup for tasks an admin assigned to you (you can only change their status) --}}
    @can('update', $task)
        @include('tasks.partials.edit-modal', ['editFailed' => $editTaskFailedId === $task->id])
    @endcan
@endforeach

<div class="modal fade"
     id="addTaskModal"
     tabindex="-1"
     aria-labelledby="addTaskModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">

            <form action="{{ route('tasks.store') }}" method="POST" id="addTaskForm">
                @csrf

                <input type="hidden" name="form_type" value="create">

                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="addTaskModalLabel">Add New Task</h2>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    {{-- Shows session, permission, server and network errors from JavaScript --}}
                    <div class="alert alert-danger d-none" id="addTaskFormMessage" role="alert"></div>

                    <div class="mb-3">
                        <label class="form-label" for="addTaskTitle">Title</label>

                        <input type="text"
                               id="addTaskTitle"
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
                        <label class="form-label" for="addTaskDescription">Description</label>

                        <textarea id="addTaskDescription"
                                  name="description"
                                  class="form-control"
                                  rows="4">{{ $addTaskFailed ? old('description') : '' }}</textarea>

                        @if($addTaskFailed)
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="addTaskDueDate">Due Date</label>

                        <input type="date"
                               id="addTaskDueDate"
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

                    {{-- Admins only: create the task for another user (defaults to yourself) --}}
                    @can('assign tasks')
                        <div class="mb-3">
                            <label class="form-label" for="addTaskUser">Assign to</label>

                            @php
                                $selectedUserId = $addTaskFailed ? (int) old('user_id') : (int) Auth::id();
                            @endphp

                            <select id="addTaskUser" name="user_id" class="form-select">
                                @foreach($users as $assignee)
                                    <option value="{{ $assignee->id }}" {{ $selectedUserId === $assignee->id ? 'selected' : '' }}>
                                        {{ $assignee->name }}
                                    </option>
                                @endforeach
                            </select>

                            @if($addTaskFailed)
                                @error('user_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                    @endcan

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

{{-- One "Are you sure?" popup for every Delete button (the JavaScript puts the task's title in it) --}}
<x-confirm-modal id="deleteTaskModal" title="Delete this task?" confirm-label="Delete task">
    This task will be deleted permanently. This cannot be undone.
</x-confirm-modal>

@endsection

@push('styles')
    <style>
        /* Completed tasks are faded so pending work stands out; the Status badge and Actions column stay clear */
        .task-completed > td:not(:last-child):not([data-field="status"]) {
            opacity: 0.55;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>

    <script>
        $(document).ready(function () {
            const addTaskForm = $('#addTaskForm');
            const addTaskFormMessage = $('#addTaskFormMessage');
            const addTaskModal = $('#addTaskModal');
            const tasksTableBody = $('#tasksTableBody');

            // Disable the submit buttons while a request is running (stops double submissions)
            function setSubmitting(container, isSubmitting) {
                container.find('button[type="submit"]').prop('disabled', isSubmitting);
            }

            // Show the green success alert at the top of the page (replaces any old message)
            function showSuccessMessage(text) {
                $('#flashAlert').remove();
                $('#ajaxErrorAlert').addClass('d-none');
                $('#ajaxAlert').text(text).removeClass('d-none');
            }

            // Show a failed Axios request in an alert box (with a Refresh button when that helps).
            // For the Add/Edit forms, pass the form too: validation errors then appear under each field
            function showError(box, error, forbiddenText, form) {
                const status = error.response?.status;        // e.g. 422, 403 (undefined if the server never answered)
                const errors = error.response?.data?.errors;   // Laravel validation errors: { field: [messages] }

                // 1. Validation failed in the Add/Edit form: show each message under its field
                if (status === 422 && errors && form) {
                    $.each(errors, function (fieldName, messages) {
                        $('<div class="text-danger"></div>').text(messages[0]).insertAfter(form[0].elements[fieldName]);
                    });
                    return;
                }

                // 2. Choose the message, and whether a Refresh button would help
                let text = 'Something went wrong on the server. Please try again later.';
                let withRefreshButton = false;

                if (status === 422 && errors) {
                    text = Object.values(errors)[0][0];   // row buttons have no fields, so show the first message
                } else if (status === 401 || status === 419) {
                    text = 'Your session has expired. Please refresh the page (and log in again if asked), then try again.';
                    withRefreshButton = true;
                } else if (status === 403) {
                    text = forbiddenText;
                } else if (status === 404) {
                    text = 'This task no longer exists. Please refresh the page.';
                    withRefreshButton = true;
                } else if (!error.response && error.request) {
                    text = 'Could not reach the server. Check your internet connection and try again.';   // sent, but no answer
                } else if (!error.response) {
                    console.error(error);   // never sent: a bug in this script, not a server problem
                }

                // 3. Show it
                box.text(text);

                if (withRefreshButton) {
                    $('<button type="button" class="btn btn-sm btn-outline-danger ms-2">Refresh page</button>')
                        .on('click', function () {
                            window.location.reload();
                        })
                        .appendTo(box);
                }

                box.removeClass('d-none');
            }

            // Show a task's current values in its table row and in its Edit modal
            function updateTaskOnPage(task) {
                const isCompleted = task.status === 'Completed';
                const row = tasksTableBody.find('tr[data-task-id="' + task.id + '"]');
                const editForm = $('#editTaskModal-' + task.id).find('form');

                // Table row (Completed tasks get a crossed-out title and a green badge)
                const titleCell = row.find('[data-field="title"]');
                if (isCompleted) {
                    titleCell.empty().append($('<del></del>').text(task.title));
                } else {
                    titleCell.text(task.title);
                }

                row.find('[data-field="owner"]').text(task.user?.name ?? 'Unassigned');   // admins only
                row.find('[data-field="description"]').text(task.description ?? '—');

                row.find('[data-field="status"] .badge')
                    .attr('class', isCompleted ? 'badge bg-success' : 'badge bg-warning text-dark')
                    .text(isCompleted ? 'Completed' : 'Pending');

                // Due date, with an "Overdue" / "Due today" badge (task.due_state comes from the Task model)
                row.find('[data-role="due-date-text"]').text(task.due_date ?? '—');
                row.find('[data-role="due-badge"]')
                    .attr('class', task.due_state === 'overdue' ? 'badge ms-1 bg-danger' : 'badge ms-1 bg-warning text-dark')
                    .toggleClass('d-none', !task.due_state)
                    .text(task.due_state === 'overdue' ? 'Overdue' : (task.due_state === 'today' ? 'Due today' : ''));

                // Faded row for completed tasks, and the values the filter tabs read
                row.toggleClass('task-completed', isCompleted);
                row.attr('data-status', task.status);
                row.attr('data-due-state', task.due_state ?? '');

                // Complete/Mark Pending button: sends the opposite status
                const toggleForm = row.find('[data-role="toggle-form"]');
                toggleForm.find('[name="status"]').val(isCompleted ? 'Pending' : 'Completed');
                toggleForm.find('.bi').attr('class', isCompleted ? 'bi bi-arrow-counterclockwise' : 'bi bi-check-lg');
                toggleForm.find('[data-role="toggle-label"]').text(isCompleted ? 'Mark Pending' : 'Complete');

                // Edit modal
                editForm.find('[name="title"]').val(task.title);
                editForm.find('[name="description"]').val(task.description ?? '');
                editForm.find('[name="due_date"]').val(task.due_date ?? '');
                editForm.find('[name="status"]').val(task.status);
                editForm.find('[name="user_id"]').val(task.user_id);   // admins only

                // The task may no longer match the chosen tab (e.g. just completed while "Pending" is shown)
                applyFilter();
            }

            // ---------- Filter tabs (All / Pending / Completed / Overdue) ----------
            let currentFilter = 'all';

            // Show only the rows that match the chosen tab
            function applyFilter() {
                const rows = tasksTableBody.children('tr');

                rows.each(function () {
                    const row = $(this);
                    const matches = currentFilter === 'all'
                        || row.attr('data-status') === currentFilter
                        || row.attr('data-due-state') === currentFilter;

                    row.toggleClass('d-none', !matches);
                });

                // "No tasks match this filter." when there are tasks but none are visible
                $('#noFilterMatchesMessage').toggleClass('d-none', rows.length === 0 || rows.not('.d-none').length > 0);

                updateCounts();
            }

            // Dashboard count cards: recount from the rows, so they stay right after every add/complete/delete.
            // The rows are exactly the tasks the counts cover (yours, or every task for admins); no cards on this page = no-op
            function updateCounts() {
                const rows = tasksTableBody.children('tr');

                $('[data-count="total"]').text(rows.length);
                $('[data-count="pending"]').text(rows.filter('[data-status="Pending"]').length);
                $('[data-count="completed"]').text(rows.filter('[data-status="Completed"]').length);
            }

            $('#taskFilters').on('click', '[data-filter]', function () {
                currentFilter = $(this).attr('data-filter');

                $('#taskFilters .nav-link').removeClass('active');
                $(this).addClass('active');

                applyFilter();
            });

            // ---------- Create ----------
            addTaskForm.on('submit', async function (e) {
                e.preventDefault();

                const form = $(this);
                const data = Object.fromEntries(new FormData(form[0]));

                // Remove error messages from the last attempt
                form.find('.text-danger').remove();
                addTaskFormMessage.addClass('d-none').empty();

                setSubmitting(form, true);

                try {
                    const response = await axios.post("{{ route('tasks.store') }}", data);

                 
                    tasksTableBody.prepend(response.data.row);
                    addTaskModal.before(response.data.modal);

                    $('#emptyTasksMessage').addClass('d-none');
                    $('#tasksTableWrapper').removeClass('d-none');
                    applyFilter();

                    showSuccessMessage(response.data.message);

                    form.find('[name="title"], [name="description"], [name="due_date"]').val('');

                    bootstrap.Modal.getOrCreateInstance(addTaskModal[0]).hide();
                } catch (error) {
                    showError(addTaskFormMessage, error, 'You are not allowed to create tasks.', form);
                } finally {
                    setSubmitting(form, false);
                }
            });

            // ---------- Edit (modal form) ----------
            $(document).on('submit', '[data-role="edit-form"]', async function (e) {
                e.preventDefault();

                const form = $(this);
                const messageElement = form.find('[data-role="form-message"]');
                const data = Object.fromEntries(new FormData(form[0]));

                // Remove error messages from the last attempt
                form.find('.text-danger').remove();
                messageElement.addClass('d-none').empty();

                setSubmitting(form, true);

                try {
                    const response = await axios.put(form.attr('action'), data);

                    updateTaskOnPage(response.data.task);
                    bootstrap.Modal.getOrCreateInstance(form.closest('.modal')[0]).hide();
                    showSuccessMessage(response.data.message);
                } catch (error) {
                    showError(messageElement, error, 'You are not allowed to edit this task.', form);
                } finally {
                    setSubmitting(form, false);
                }
            });

            // ---------- Status (Complete / Mark Pending button) ----------
            $(document).on('submit', '[data-role="toggle-form"]', async function (e) {
                e.preventDefault();

                const form = $(this);
                const row = form.closest('tr');
                const data = Object.fromEntries(new FormData(form[0]));

                setSubmitting(row, true);

                try {
                    const response = await axios.patch(form.attr('action'), data);

                    updateTaskOnPage(response.data.task);
                    showSuccessMessage(response.data.message);
                } catch (error) {
                    // Hide old success messages so "success" and "error" never show at the same time
                    $('#flashAlert').remove();
                    $('#ajaxAlert').addClass('d-none');
                    showError($('#ajaxErrorAlert'), error, 'You are not allowed to update this task.');
                } finally {
                    setSubmitting(row, false);
                }
            });

            // ---------- Delete ----------
            const deleteTaskModal = $('#deleteTaskModal');
            let formToDelete = null;   // the Delete form waiting for "Delete task" in the popup

            // 1. The Delete button only opens the "Are you sure?" popup, with the task's title in it
            $(document).on('submit', '[data-role="delete-form"]', function (e) {
                e.preventDefault();

                formToDelete = $(this);

                const title = formToDelete.closest('tr').find('[data-field="title"]').text().trim();
                deleteTaskModal.find('[data-role="confirm-message"]')
                    .text('"' + title + '" will be deleted permanently. This cannot be undone.');

                bootstrap.Modal.getOrCreateInstance(deleteTaskModal[0]).show();
            });

            // 2. "Delete task" in the popup sends the request
            deleteTaskModal.on('click', '[data-role="confirm-button"]', function () {
                const form = formToDelete;
                formToDelete = null;

                bootstrap.Modal.getOrCreateInstance(deleteTaskModal[0]).hide();

                if (form) {
                    deleteTask(form);
                }
            });

            // Cancelled: put the keyboard focus back on that row's Delete button
            deleteTaskModal.on('hidden.bs.modal', function () {
                if (formToDelete) {
                    formToDelete.find('button[type="submit"]').trigger('focus');
                    formToDelete = null;
                }
            });

            async function deleteTask(form) {
                const row = form.closest('tr');

                setSubmitting(row, true);

                try {
                    // DELETE needs only the address; the CSRF token goes in the header (see layouts/app.blade.php)
                    const response = await axios.delete(form.attr('action'));

                    // Remove the row (and its Edit modal) only after the server confirmed the delete
                    $('#editTaskModal-' + row.attr('data-task-id')).remove();
                    row.remove();

                    // Show "No tasks yet" when the last task is gone
                    if (tasksTableBody.children().length === 0) {
                        $('#emptyTasksMessage').removeClass('d-none');
                        $('#tasksTableWrapper').addClass('d-none');
                    }

                    applyFilter();

                    showSuccessMessage(response.data.message);
                } catch (error) {
                    // Hide old success messages so "success" and "error" never show at the same time
                    $('#flashAlert').remove();
                    $('#ajaxAlert').addClass('d-none');
                    showError($('#ajaxErrorAlert'), error, 'You are not allowed to delete this task.');
                } finally {
                    // On success the row is already gone, so this only matters after an error
                    setSubmitting(row, false);
                }
            }
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
