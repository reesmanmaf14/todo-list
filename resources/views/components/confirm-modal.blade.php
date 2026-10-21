{{-- A reusable "Are you sure?" popup for destructive actions (Bootstrap modal).
     The page's JavaScript fills [data-role="confirm-message"] and listens for clicks on [data-role="confirm-button"].
     Cancel gets the focus when it opens, so pressing Enter by accident never deletes anything. --}}
@props([
    'id',
    'title' => 'Are you sure?',
    'confirmLabel' => 'Delete',
])

<div class="modal fade"
     id="{{ $id }}"
     tabindex="-1"
     aria-labelledby="{{ $id }}Label"
     aria-describedby="{{ $id }}Message"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-confirm">
        <div class="modal-content">

            <div class="modal-header">
                <h2 class="modal-title fs-5" id="{{ $id }}Label">{{ $title }}</h2>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0" id="{{ $id }}Message" data-role="confirm-message">{{ $slot }}</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-autofocus>
                    Cancel
                </button>

                <button type="button" class="btn btn-danger" data-role="confirm-button">
                    <i class="bi bi-trash" aria-hidden="true"></i> {{ $confirmLabel }}
                </button>
            </div>

        </div>
    </div>
</div>
