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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>

    <script>
        // Create, edit, complete/mark pending and delete tasks without reloading the page, using Axios.
        // The CSRF token is added to every Axios request in layouts/app.blade.php.
        // If JavaScript is off, every form still submits normally and the page reloads as before.
        $(document).ready(function () {   //wait until the page is ready
            const addTaskForm = $('#addTaskForm');
            const addTaskFormMessage = $('#addTaskFormMessage');
            const addTaskModal = $('#addTaskModal');
            const tasksTableBody = $('#tasksTableBody');

            // ---------- Shared helpers ----------

            // Disable the submit buttons while a request is in flight (stops double submissions)
            function setSubmitting(container, isSubmitting) {
                container.find('button[type="submit"]').prop('disabled', isSubmitting);
            }

            // Turn a failed Axios request into a message for the user: { text, withRefreshButton }
            function ajaxErrorMessage(error, forbiddenText) {
                // error.response is the server's answer; it is missing when no answer came back
                const status = error.response?.status;

                // The request was sent (error.request) but the server never answered: network problem
                if (!error.response && error.request) {
                    return { text: 'Could not reach the server. Check your internet connection and try again.', withRefreshButton: false };
                }

                // Neither response nor request: a bug in this script, not a server problem
                if (!error.response) {
                    console.error(error);
                }

                if (status === 401 || status === 419) {
                    return { text: 'Your session has expired. Please refresh the page (and log in again if asked), then try again.', withRefreshButton: true };
                }

                if (status === 403) {
                    return { text: forbiddenText, withRefreshButton: false };
                }

                if (status === 404) {
                    return { text: 'This task no longer exists. Please refresh the page.', withRefreshButton: true };
                }

                return { text: 'Something went wrong on the server. Please try again later.', withRefreshButton: false };
            }

            // Put a message in an alert box (optionally with a Refresh button) and show it
            function showMessage(element, text, withRefreshButton) {
                element.text(text);

                if (withRefreshButton) {
                    $('<button type="button" class="btn btn-sm btn-outline-danger ms-2">Refresh page</button>')
                        .on('click', function () {
                            window.location.reload();
                        })
                        .appendTo(element);
                }

                element.removeClass('d-none');
            }

            // Remove old error messages in a form (both Blade-rendered and ones added by this script)
            function clearFormErrors(form, messageElement) {
                form.find('.text-danger').remove();
                messageElement.addClass('d-none').empty();
            }

            // Show each validation error under its field (same style as the server-rendered errors)
            function showFieldErrors(form, messageElement, errors) {
                $.each(errors, function (fieldName, messages) {
                    const field = form[0].elements[fieldName];

                    if (!field) {
                        showMessage(messageElement, messages[0]);
                        return;
                    }

                    $('<div class="text-danger"></div>').text(messages[0]).insertAfter(field);
                });
            }

            // Error handling for the Add and Edit modal forms
            function showFormError(form, messageElement, error, forbiddenText) {
                // 422 = Laravel validation failed; error.response.data.errors holds { field: [messages] }
                if (error.response?.status === 422 && error.response.data.errors) {
                    showFieldErrors(form, messageElement, error.response.data.errors);
                    return;
                }

                const message = ajaxErrorMessage(error, forbiddenText);
                showMessage(messageElement, message.text, message.withRefreshButton);
            }

            // Page-level success message (replaces any old flash message)
            function showSuccessMessage(text) {
                $('#flashAlert').remove();
                $('#ajaxErrorAlert').addClass('d-none');
                $('#ajaxAlert').text(text).removeClass('d-none');
            }

            // Page-level error message, used by the row buttons (Complete/Mark Pending, Delete)
            function showPageError(text, withRefreshButton) {
                $('#flashAlert').remove();
                $('#ajaxAlert').addClass('d-none');
                showMessage($('#ajaxErrorAlert'), text, withRefreshButton);
            }

            // ---------- Rendering ----------
            // Task data is only ever inserted with .text() / .val(), never as HTML

            // Fill a table row with a task's current values (works for Pending and Completed)
            function fillTaskRow(row, task) {
                const isCompleted = task.status === 'Completed';

                const titleCell = row.find('[data-field="title"]');
                if (isCompleted) {
                    titleCell.empty().append($('<del></del>').text(task.title));
                } else {
                    titleCell.text(task.title);
                }

                row.find('[data-field="description"]').text(task.description ?? '—');
                row.find('[data-field="due_date"]').text(task.due_date ?? '—');

                row.find('[data-field="status"] .badge')
                    .attr('class', isCompleted ? 'badge bg-success' : 'badge bg-warning text-dark')
                    .text(isCompleted ? 'Completed' : 'Pending');

                // The Complete/Mark Pending button resends the current values with the opposite status,
                // so its hidden inputs must always match the latest saved values
                const toggleForm = row.find('[data-role="toggle-form"]');
                toggleForm.find('[name="title"]').val(task.title);
                toggleForm.find('[name="description"]').val(task.description ?? '');
                toggleForm.find('[name="due_date"]').val(task.due_date ?? '');
                toggleForm.find('[name="status"]').val(isCompleted ? 'Pending' : 'Completed');
                toggleForm.find('button[type="submit"]').text(isCompleted ? 'Mark Pending' : 'Complete');
            }

            // Fill a task's Edit modal form with its current values
            function fillEditForm(editForm, task) {
                editForm.find('[name="title"]').val(task.title);
                editForm.find('[name="description"]').val(task.description ?? '');
                editForm.find('[name="due_date"]').val(task.due_date ?? '');
                editForm.find('[name="status"]').val(task.status);
            }

            // After an update, refresh both the row and the Edit modal so neither holds stale values
            function applyUpdatedTask(task) {
                fillTaskRow(tasksTableBody.find('tr[data-task-id="' + task.id + '"]'), task);
                fillEditForm($('#editTaskModal-' + task.id).find('form'), task);
            }

            // Copy the first element of a <template>.
            // importNode (not cloneNode) creates the copy in this page, so the Delete form's
            // inline onsubmit="return confirm(...)" is active on the new row
            function cloneTemplate(templateId) {
                return $(document.importNode(document.getElementById(templateId).content.firstElementChild, true));
            }

            // Build the Edit modal for a new task from its <template>
            function buildEditModal(task, updateUrl) {
                const modal = cloneTemplate('editTaskModalTemplate');
                const labelId = 'editTaskModalLabel-' + task.id;

                modal.attr({ id: 'editTaskModal-' + task.id, 'aria-labelledby': labelId });
                modal.find('.modal-title').attr('id', labelId);

                const editForm = modal.find('form');
                editForm.attr('action', updateUrl);
                editForm.find('[name="task_id"]').val(task.id);
                fillEditForm(editForm, task);

                return modal;
            }

            // Build the table row for a new task from its <template>
            function buildTaskRow(task, createdDate, urls) {
                const row = cloneTemplate('taskRowTemplate');

                row.attr('data-task-id', task.id);
                row.find('[data-field="created"]').text(createdDate);
                row.find('[data-role="edit-button"]').attr('data-bs-target', '#editTaskModal-' + task.id);
                row.find('[data-role="toggle-form"]').attr('action', urls.update);
                row.find('[data-role="delete-form"]').attr('action', urls.destroy);

                fillTaskRow(row, task);

                return row;
            }

            // Show the "No tasks yet" message when the table becomes empty
            function updateEmptyState() {
                const isEmpty = tasksTableBody.children().length === 0;

                $('#emptyTasksMessage').toggleClass('d-none', !isEmpty);  //when there are tasks, the #emptyTasksMessage is hidden
                $('#tasksTableWrapper').toggleClass('d-none', isEmpty);  //when there are no tasks, the #tasksTableWrapper is hidden
            }

            // ---------- Create ----------
            addTaskForm.on('submit', async function (e) {   
                e.preventDefault();  //stops the browser's normal form submission (full page reload); must run before any await

                const form = $(this);

                // Collect every named field (title, description, due_date, _token...) into a plain object.
                // Axios sends a plain object as JSON, which Laravel reads like normal form input.
                const data = Object.fromEntries(new FormData(form[0]));

                clearFormErrors(form, addTaskFormMessage);  //each attempt starts clean
                setSubmitting(form, true);  //disable submit button until the request is completed..

                try {
                    // await: wait here until Laravel answers. Any error status (422, 419, 500...) jumps to catch
                const response = await axios.post("{{ route('tasks.store') }}", data);
         //response object when   //waits until the request succeeds or fails
         // request succeeds      

                    // response.data is the JSON from TaskController@store: { message, task, created_date, urls }
                    const result = response.data;  //JSON body that controller returned
                                    
                    // Newest tasks are shown first, so the new row goes at the top
                    tasksTableBody.prepend(buildTaskRow(result.task, result.created_date, result.urls)); //prepend() adds the new task at the top
                    addTaskModal.before(buildEditModal(result.task, result.urls.update));  //creates edit modal
                    updateEmptyState();

                    showSuccessMessage(result.message);

                    //clears the fields in add task form after a task is successfully saved, 
                    form.find('[name="title"], [name="description"], [name="due_date"]').val('');   //val('') sets fields to empty

                    bootstrap.Modal.getOrCreateInstance(addTaskModal[0]).hide();  //closes the model
                } catch (error) {
                    showFormError(form, addTaskFormMessage, error, 'You are not allowed to create tasks.');
                } finally {
                    // finally runs after success AND after errors, so the button always comes back
                    setSubmitting(form, false);
                }
            });

            // ---------- Edit (modal form) ----------
            // Delegated handlers also cover rows and modals added after page load

                                       //this is a filter, a CSS attribute selector
            $(document).on('submit', '[data-role="edit-form"]', async function (e) {  //the function only runs if the form submitted has the data-role="edit-form" attribute.
                e.preventDefault();

                const form = $(this);
                const messageElement = form.find('[data-role="form-message"]');
                const data = Object.fromEntries(new FormData(form[0]));

                clearFormErrors(form, messageElement);
                setSubmitting(form, true);

                try {
                    // A real PUT request, so Laravel ignores the form's hidden _method field
                    const response = await axios.put(form.attr('action'), data);

                    // response.data is the JSON from TaskController@update: { message, task }
                    applyUpdatedTask(response.data.task);
                    bootstrap.Modal.getOrCreateInstance(form.closest('.modal')[0]).hide();
                    showSuccessMessage(response.data.message);
                } catch (error) {
                    showFormError(form, messageElement, error, 'You are not allowed to edit this task.');
                } finally {
                    setSubmitting(form, false);
                }
            });

            // ---------- Status (Complete / Mark Pending button) ----------

            $(document).on('submit', '[data-role="toggle-form"]', async function (e) {
                e.preventDefault();

                const form = $(this);
                // Lock the whole row so Complete and Delete can't run at the same time
                const row = form.closest('tr');

                // The task's current values with the opposite status (hidden inputs)
                const data = Object.fromEntries(new FormData(form[0]));

                setSubmitting(row, true);

                try {
                    const response = await axios.put(form.attr('action'), data);

                    applyUpdatedTask(response.data.task);
                    showSuccessMessage(response.data.message);
                } catch (error) {
                    if (error.response?.status === 422 && error.response.data.errors) {
                        // There are no visible fields for this form, so show the first error at the top
                        const errors = error.response.data.errors;
                        showPageError(errors[Object.keys(errors)[0]][0]);
                        return;  // finally still runs after this return
                    }

                    const message = ajaxErrorMessage(error, 'You are not allowed to update this task.');
                    showPageError(message.text, message.withRefreshButton);
                } finally {
                    setSubmitting(row, false);
                }
            });

            // ---------- Delete ----------

            $(document).on('submit', '[data-role="delete-form"]', async function (e) {
                // The form's confirm() already cancelled the submission if the user clicked "Cancel"
                if (e.isDefaultPrevented()) {
                    return;
                }

                e.preventDefault();

                const form = $(this);
                const row = form.closest('tr');

                setSubmitting(row, true);

                try {
                    // DELETE needs only the address; the CSRF token goes in the header (see layouts/app.blade.php)
                    const response = await axios.delete(form.attr('action'));

                    // Remove the row (and its Edit modal) only after the server confirmed the delete
                    $('#editTaskModal-' + row.attr('data-task-id')).remove();
                    row.remove();
                    updateEmptyState();
                    showSuccessMessage(response.data.message);
                } catch (error) {
                    const message = ajaxErrorMessage(error, 'You are not allowed to delete this task.');
                    showPageError(message.text, message.withRefreshButton);
                } finally {
                    // On success the row is already gone, so this only matters after an error
                    setSubmitting(row, false);
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