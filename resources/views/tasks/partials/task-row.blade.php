{{-- One task in the table. Used by tasks/index and by TaskController@store for tasks added without a page reload.
     data-status / data-due-state: read by the filter tabs. task-completed: fades the row (see tasks/index) --}}
<tr data-task-id="{{ $task->id }}"
    data-status="{{ $task->status }}"
    data-due-state="{{ $task->due_state }}"
    class="{{ $task->status === 'Completed' ? 'task-completed' : '' }}">
    <td data-field="title">
        @if($task->status === 'Completed')
            <del>{{ $task->title }}</del>
        @else
            {{ $task->title }}
        @endif
    </td>

    @can('view all tasks')
        <td data-field="owner">
            {{ $task->user->name ?? 'Unassigned' }}
        </td>
    @endcan

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

    {{-- Pending tasks get an "Overdue" or "Due today" badge (due_state is worked out in the Task model) --}}
    <td data-field="due_date">
        <span data-role="due-date-text">{{ $task->due_date ?? '—' }}</span>

        <span data-role="due-badge"
              class="badge ms-1 {{ $task->due_state === 'overdue' ? 'bg-danger' : 'bg-warning text-dark' }} {{ $task->due_state ? '' : 'd-none' }}">
            {{ $task->due_state === 'overdue' ? 'Overdue' : ($task->due_state === 'today' ? 'Due today' : '') }}
        </span>
    </td>

    <td data-field="created">
        {{ $task->created_at->format('Y-m-d') }}
    </td>

    <td>
        <div class="d-flex gap-2 flex-wrap">

            @can('update', $task)
                <button type="button"
                        class="btn btn-sm btn-primary"
                        data-bs-toggle="modal"
                        data-role="edit-button"
                        data-bs-target="#editTaskModal-{{ $task->id }}">
                    <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                </button>
            @endcan

            <form action="{{ route('tasks.status', $task) }}"
                  method="POST"
                  data-role="toggle-form">
                @csrf
                @method('PATCH')

                <input type="hidden"
                       name="status"
                       value="{{ $task->status === 'Pending' ? 'Completed' : 'Pending' }}">

                {{-- JavaScript swaps the icon and the label text when the status changes --}}
                <button type="submit"
                        class="btn btn-sm btn-success">
                    <i class="bi {{ $task->status === 'Pending' ? 'bi-check-lg' : 'bi-arrow-counterclockwise' }}" aria-hidden="true"></i>
                    <span data-role="toggle-label">{{ $task->status === 'Pending' ? 'Complete' : 'Mark Pending' }}</span>
                </button>
            </form>

            {{-- JavaScript asks "Are you sure?" in the #deleteTaskModal popup before sending the delete --}}
            @can('delete', $task)
                <form action="{{ route('tasks.destroy', $task) }}"
                      method="POST"
                      data-role="delete-form">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            class="btn btn-sm btn-danger">
                        <i class="bi bi-trash" aria-hidden="true"></i> Delete
                    </button>
                </form>
            @endcan

        </div>
    </td>
</tr>
