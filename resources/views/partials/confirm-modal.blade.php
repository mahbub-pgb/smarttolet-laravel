{{-- Reusable confirmation popup. Any `<form data-confirm="…">` is intercepted
     by public/js/confirm-dialog.js, which shows this before submitting. --}}
<div class="modal-overlay confirm-modal" id="confirm-modal" aria-hidden="true">
    <div class="modal confirm-box" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div class="modal-head">
            <h3 id="confirm-title">Please confirm</h3>
            <button type="button" class="modal-close" data-confirm-cancel aria-label="Close">✕</button>
        </div>
        <div class="modal-body">
            <p id="confirm-message" style="margin:0">Are you sure?</p>
        </div>
        <div class="modal-foot" style="gap:10px">
            <button type="button" class="btn btn-ghost" data-confirm-cancel>Cancel</button>
            <button type="button" class="btn confirm-accept" id="confirm-accept">Delete</button>
        </div>
    </div>
</div>
