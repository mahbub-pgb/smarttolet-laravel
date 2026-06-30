/* Admin blog: CKEditor 5 (self-hosted classic build) rich-text body with image
   upload, cover preview, and delete confirmation. jQuery.

   The editor library is served locally from public/vendor/ckeditor5 so it does
   not depend on any CDN. The classic build exposes window.ClassicEditor and
   injects its own CSS. */
(function ($) {
    'use strict';

    // Premium / collaboration plugins bundled in the super-build that need a
    // license key or token. Verified present in this build — leaving any of
    // them in (e.g. AIAssistant) makes create() reject and the editor vanish.
    // CloudServices is intentionally NOT removed: it's a shared dependency and
    // orphaning it breaks init.
    var REMOVE = [
        'AIAssistant',
        'CKBox', 'CKFinder', 'EasyImage',
        'ExportPdf', 'ExportWord',
        'RealTimeCollaborativeComments', 'RealTimeCollaborativeTrackChanges',
        'RealTimeCollaborativeRevisionHistory', 'PresenceList', 'Comments',
        'TrackChanges', 'TrackChangesData', 'RevisionHistory', 'Pagination',
        'WProofreader', 'MathType', 'SlashCommand', 'Template', 'DocumentOutline',
        'FormatPainter', 'TableOfContents', 'PasteFromOfficeEnhanced', 'CaseChange',
        'MultiLevelList'
    ];

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    // Custom upload adapter — posts the dropped / pasted / picked file to our
    // endpoint and resolves the stored URL for CKEditor to embed.
    function UploadAdapter(loader, url, token) {
        this.loader = loader;
        this.url = url;
        this.token = token;
    }
    UploadAdapter.prototype.upload = function () {
        var self = this;
        return this.loader.file.then(function (file) {
            return new Promise(function (resolve, reject) {
                var data = new FormData();
                data.append('upload', file);
                $.ajax({
                    url: self.url,
                    method: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': self.token, 'Accept': 'application/json' },
                    xhr: function () {
                        var xhr = $.ajaxSettings.xhr();
                        if (xhr.upload) {
                            xhr.upload.addEventListener('progress', function (e) {
                                if (e.lengthComputable) {
                                    self.loader.uploadTotal = e.total;
                                    self.loader.uploaded = e.loaded;
                                }
                            });
                        }
                        return xhr;
                    }
                }).done(function (res) {
                    if (res && res.url) { resolve({ default: res.url }); }
                    else { reject('Upload failed.'); }
                }).fail(function (xhr) {
                    var msg = 'Upload failed.';
                    try {
                        var j = JSON.parse(xhr.responseText);
                        msg = j.message || (j.error && j.error.message) || msg;
                    } catch (e) { /* keep default */ }
                    reject(msg);
                });
            });
        });
    };
    UploadAdapter.prototype.abort = function () { /* nothing to abort */ };

    function uploadPlugin(uploadUrl, token) {
        return function (editor) {
            if (!editor.plugins.has('FileRepository')) { return; }
            editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
                return new UploadAdapter(loader, uploadUrl, token);
            };
        };
    }

    $(function () {
        // ---- Rich text editor for the post body --------------------------
        var body = document.querySelector('#post-body');
        var Editor = (window.CKEDITOR && window.CKEDITOR.ClassicEditor) || window.ClassicEditor;

        function warn(msg) {
            $(body).before($('<p class="form-hint"></p>').css('color', '#b91c1c').text(msg));
        }

        var editorInstance = null;

        if (body && !Editor) {
            warn('Rich editor could not load. You can still type / paste HTML in the box below.');
        } else if (body && Editor) {
            var uploadUrl = $(body).data('upload-url');
            Editor.create(body, {
                removePlugins: REMOVE,
                extraPlugins: uploadUrl ? [uploadPlugin(uploadUrl, csrfToken())] : [],
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                        'outdent', 'indent', '|',
                        'uploadImage', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
                        'undo', 'redo'
                    ]
                },
                image: {
                    // Drag the corner handles to resize, or pick a preset width.
                    resizeUnit: '%',
                    resizeOptions: [
                        { name: 'resizeImage:original', value: null, label: 'Original' },
                        { name: 'resizeImage:25', value: '25', label: '25%' },
                        { name: 'resizeImage:50', value: '50', label: '50%' },
                        { name: 'resizeImage:75', value: '75', label: '75%' }
                    ],
                    toolbar: [
                        'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|',
                        'toggleImageCaption', 'imageTextAlternative', '|',
                        'resizeImage', '|',
                        'linkImage'
                    ]
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                }
            }).then(function (editor) {
                editorInstance = editor;
            }).catch(function (err) {
                if (window.console) console.error('[blog] CKEditor failed to start:', err);
                warn('Rich editor failed to start — writing in plain HTML mode. Open the browser console (F12) for details.');
            });
        }

        // ---- Central media library buttons -------------------------------
        // Insert a library image into the article body at the cursor.
        $('#ml-content-btn').on('click', function () {
            if (!window.MediaLibrary) { return; }
            window.MediaLibrary.open(function (url) {
                if (editorInstance) {
                    editorInstance.execute('insertImage', { source: url });
                    editorInstance.editing.view.focus();
                } else {
                    // Plain-HTML fallback when the rich editor didn't load.
                    var $b = $('#post-body');
                    $b.val($b.val() + '\n<img src="' + url + '" alt="">');
                }
            });
        });

        // Pick a cover image from the library.
        $('#ml-cover-btn').on('click', function () {
            if (!window.MediaLibrary) { return; }
            window.MediaLibrary.open(function (url) {
                $('#cover_image').val(url);
                $('#cover-preview').attr('src', url).removeAttr('hidden');
                $('#cover-current').attr('hidden', true);
                $('input[name="remove_cover"]').prop('checked', false);
            });
        });

        // ---- Cover image: live preview before upload ---------------------
        $('#cover_file').on('change', function () {
            var file = this.files && this.files[0];
            var $preview = $('#cover-preview');
            if (!file) { $preview.attr('hidden', true); return; }
            $preview.attr('src', URL.createObjectURL(file)).removeAttr('hidden');
            $('#cover-current').attr('hidden', true);
        });

        // ---- Confirm destructive submits ---------------------------------
        $('form[data-confirm]').on('submit', function () {
            return window.confirm($(this).data('confirm'));
        });
    });
})(jQuery);
