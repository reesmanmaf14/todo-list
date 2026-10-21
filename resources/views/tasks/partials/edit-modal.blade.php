{{-- A task's Edit popup. Used by tasks/partials/task-list and by TaskController@store for tasks added without a page reload.
     $editFailed: true when this task's Edit form was submitted without JavaScript and failed validation --}}
<div class="modal fade"
     id="editTaskModal-{{ $task->id }}"
     tabindex="-1"
     aria-labelledby="editTaskModalLabel-{{ $task->id }}"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">

            <form action="{{ route('tasks.update', $task) }}" method="POST" data-role="edit-form">
                @csrf
                @method('PUT')

                <input type="hidden" name="form_type" value="edit">
                <input type="hidden" name="task_id" value="{{ $task->id }}">

                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="editTaskModalLabel-{{ $task->id }}">Edit Task</h2>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    {{-- Shows session, permission, server and network errors from JavaScript --}}
                    <div class="alert alert-danger d-none" data-role="form-message" role="alert"></div>

                    <div class="mb-3">
                        <label class="form-label" for="editTaskTitle-{{ $task->id }}">Title</label>

                        <input type="text"
                               id="editTaskTitle-{{ $task->id }}"
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
                        <label class="form-label" for="editTaskDescription-{{ $task->id }}">Description</label>

                        <textarea id="editTaskDescription-{{ $task->id }}"
                                  name="description"
                                  class="form-control"
                                  rows="4">{{ $editFailed ? old('description') : $task->description }}</textarea>

                        @if($editFailed)
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="editTaskDueDate-{{ $task->id }}">Due Date</label>

                        <input type="date"
                               id="editTaskDueDate-{{ $task->id }}"
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
                        <label class="form-label" for="editTaskStatus-{{ $task->id }}">Status</label>

                        @php
                            $selectedStatus = $editFailed ? old('status') : $task->status;
                        @endphp

                        <select id="editTaskStatus-{{ $task->id }}" name="status" class="form-select">
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

                    {{-- Admins only: move the task to another user --}}
                    @can('assign tasks')
                        <div class="mb-3">
                            <label class="form-label" for="editTaskUser-{{ $task->id }}">Assigned to</label>

                            @php
                                $selectedUserId = $editFailed ? (int) old('user_id') : (int) $task->user_id;
                            @endphp

                            <select id="editTaskUser-{{ $task->id }}" name="user_id" class="form-select">
                                @if(! $task->user_id)
                                    <option value="">Unassigned</option>
                                @endif

                                @foreach($users as $assignee)
                                    <option value="{{ $assignee->id }}" {{ $selectedUserId === $assignee->id ? 'selected' : '' }}>
                                        {{ $assignee->name }}
                                    </option>
                                @endforeach
                            </select>

                            @if($editFailed)
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
                        Update Task
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
