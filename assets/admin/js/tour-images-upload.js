(function (window, document) {
    'use strict';

    if (window.Dropzone) {
        window.Dropzone.autoDiscover = false;
    }

    function updateSaveButton() {
        var saveButton = document.getElementById('save-uploaded-images');
        var detailsHeading = document.getElementById('tour-images-details-heading');
        var count = document.querySelectorAll('#tour-images-uploaded-list [data-uploaded-image]').length;

        if (saveButton) {
            saveButton.disabled = count === 0;
        }

        if (detailsHeading) {
            detailsHeading.hidden = count === 0;
        }
    }

    function addUploadedImage(response) {
        var list = document.getElementById('tour-images-uploaded-list');
        var counter = document.getElementById('totalImages');
        if (!list || !counter) {
            return;
        }

        var index = Number(counter.value || 0) + 1;
        var row = document.createElement('div');
        var preview = document.createElement('img');
        var fields = document.createElement('div');
        var englishGroup = document.createElement('div');
        var arabicGroup = document.createElement('div');
        var englishLabel = document.createElement('label');
        var arabicLabel = document.createElement('label');
        var englishField = document.createElement('input');
        var arabicField = document.createElement('input');
        var remove = document.createElement('button');
        var imageField = document.createElement('input');

        counter.value = String(index);
        row.className = 'tour-images-uploaded-item';
        row.setAttribute('data-uploaded-image', '');
        preview.src = window.AdminConfig.baseUrl + 'assets/frontend/images/tour-images/' + encodeURIComponent(response.thumb_image || response.file_name);
        preview.alt = 'Uploaded image preview';
        fields.className = 'tour-images-uploaded-fields';
        englishGroup.className = 'admin-field mb-0';
        arabicGroup.className = 'admin-field mb-0';

        englishLabel.className = 'form-label is-required';
        englishLabel.htmlFor = 'name' + index;
        englishLabel.textContent = 'Image Name (English)';
        englishField.type = 'text';
        englishField.name = 'name' + index;
        englishField.id = englishField.name;
        englishField.className = 'form-control';
        englishField.required = true;
        englishField.autocomplete = 'off';

        arabicLabel.className = 'form-label is-required';
        arabicLabel.htmlFor = 'name_ar' + index;
        arabicLabel.textContent = 'Image Name (Arabic)';
        arabicField.type = 'text';
        arabicField.name = 'name_ar' + index;
        arabicField.id = arabicField.name;
        arabicField.className = 'form-control';
        arabicField.required = true;
        arabicField.autocomplete = 'off';
        arabicField.dir = 'rtl';
        arabicField.lang = 'ar';

        englishGroup.appendChild(englishLabel);
        englishGroup.appendChild(englishField);
        arabicGroup.appendChild(arabicLabel);
        arabicGroup.appendChild(arabicField);
        fields.appendChild(englishGroup);
        fields.appendChild(arabicGroup);
        imageField.type = 'hidden';
        imageField.name = 'large_image' + index;
        imageField.value = response.file_name;
        remove.type = 'button';
        remove.className = 'btn btn-outline-danger btn-sm';
        remove.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Remove image</span>';
        remove.addEventListener('click', function () {
            row.remove();
            updateSaveButton();
        });
        row.appendChild(preview);
        row.appendChild(fields);
        row.appendChild(remove);
        row.appendChild(imageField);
        list.appendChild(row);
        updateSaveButton();
    }

    function responseMessage(response) {
        if (response && response.message) {
            return response.message;
        }

        return 'The image could not be uploaded.';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('tour-images-dropzone');
        var saveForm = document.getElementById('tour-images-save-form');
        if (!form || !window.Dropzone) {
            return;
        }

        Dropzone.autoDiscover = false;
        new window.Dropzone(form, {
            paramName: 'upl',
            acceptedFiles: 'image/jpeg,image/png,image/gif,image/webp',
            maxFilesize: Number(window.AdminConfig.uploadMaxSizeMb || 20),
            parallelUploads: 3,
            timeout: 120000,
            addRemoveLinks: true,
            dictRemoveFile: 'Remove',
            dictInvalidFileType: 'Only JPG, JPEG, PNG, GIF, and WebP images are allowed.',
            init: function () {
                this.on('success', function (file, response) {
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (error) {
                            response = null;
                        }
                    }

                    if (!response || response.status !== 'success') {
                        this.emit('error', file, responseMessage(response));
                        return;
                    }

                    addUploadedImage(response);
                });
            }
        });

        if (saveForm) {
            saveForm.addEventListener('submit', function (event) {
                var missingField = saveForm.querySelector('[data-uploaded-image] input[required]:invalid');

                if (!missingField) {
                    return;
                }

                event.preventDefault();
                missingField.focus();
            });
        }
    });
}(window, document));
