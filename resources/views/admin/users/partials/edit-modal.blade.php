{{-- A user's Edit popup on the Manage Users list. Saves to the same route as the Edit User page.
     $editFailed: true when this popup's form was submitted and failed validation (it is then reopened) --}}
<div class="modal fade"
     id="editUserModal-{{ $user->id }}"
     tabindex="-1"
     aria-labelledby="editUserModalLabel-{{ $user->id }}"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">

            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Tells the list page which popup to reopen after a validation error --}}
                <input type="hidden" name="form_type" value="edit-user">
                <input type="hidden" name="user_id" value="{{ $user->id }}">

                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="editUserModalLabel-{{ $user->id }}">Edit {{ $user->name }}</h2>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @include('admin.users.partials.user-fields', [
                        'prefix' => 'editUser'.$user->id,
                        'user' => $user,
                        'showErrors' => $editFailed,
                    ])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
