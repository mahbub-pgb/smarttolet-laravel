{{--
    Reusable central media-library picker. Include once on a page, then open it
    from JS: window.MediaLibrary.open(function (url) { ...use the picked url... }).
    Endpoints are read from data-* attributes (see public/js/media-library.js).
--}}
@once
<div class="modal-overlay" id="media-library-modal" aria-hidden="true"
    data-list-url="{{ route('admin.media.index') }}"
    data-upload-url="{{ route('admin.media.store') }}">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-label="Media library">
        <div class="modal-head">
            <h3>Media library</h3>
            <button type="button" class="modal-close" data-ml-close aria-label="Close">✕</button>
        </div>

        <div class="modal-tabs">
            <button type="button" class="modal-tab active" data-ml-tab="library">🗂️ Library</button>
            <button type="button" class="modal-tab" data-ml-tab="upload">⬆️ Upload new</button>
        </div>

        <div class="modal-body">
            <div class="modal-pane active" data-ml-pane="library">
                <div class="lib-grid ml-grid" id="ml-grid">
                    <p class="form-hint" id="ml-status">Loading…</p>
                </div>
                <div class="ml-more">
                    <button type="button" class="btn btn-ghost btn-sm" id="ml-load-more" hidden>Load more</button>
                </div>
            </div>

            <div class="modal-pane" data-ml-pane="upload">
                <label class="dropzone" id="ml-dropzone">
                    <span>Click to choose images, or drag &amp; drop here</span>
                    <small>JPG, PNG, WebP or GIF · up to 5&nbsp;MB each · auto-compressed before upload</small>
                    <input type="file" id="ml-file" accept="image/*" multiple hidden>
                </label>
                <p class="form-hint" id="ml-upload-status"></p>
            </div>
        </div>
    </div>
</div>
@endonce
