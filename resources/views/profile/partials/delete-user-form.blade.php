<section>
    <h3 class="fs-5 text-danger">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Delete Account
    </h3>

    <p class="text-muted small">
        Once your account is deleted, all of its resources and data will be permanently deleted.
        Before deleting your account, please download any data or information that you wish to retain.
    </p>

    <button type="button"
            class="btn btn-danger"
            data-bs-toggle="modal"
            data-bs-target="#confirmUserDeletionModal">
        <i class="bi bi-trash" aria-hidden="true"></i> Delete Account
    </button>

    <div class="modal fade"
         id="confirmUserDeletionModal"
         tabindex="-1"
         aria-labelledby="confirmUserDeletionModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">

                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="confirmUserDeletionModalLabel">
                            Are you sure you want to delete your account?
                        </h2>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p class="text-muted small">
                            Once your account is deleted, all of its resources and data will be permanently deleted.
                            Please enter your password to confirm you would like to permanently delete your account.
                        </p>

                        <label class="form-label" for="password">Password</label>

                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="Password">

                        {{-- Errors for this form are kept separately (in the "userDeletion" error bag) --}}
                        @error('password', 'userDeletion')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash" aria-hidden="true"></i> Delete Account
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</section>

@if($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script>
            // Wrong password: reopen the confirmation popup so the error is visible
            document.addEventListener('DOMContentLoaded', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmUserDeletionModal')).show();
            });
        </script>
    @endpush
@endif
