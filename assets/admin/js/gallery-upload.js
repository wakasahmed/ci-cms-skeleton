/*
 * Bulk upload for the Gallery module (manage/gallery/upload).
 *
 * Each dropped file is posted to the form's action as "file", together with
 * the category, related service and status chosen in the options container.
 * The server creates one gallery record per file and answers with JSON.
 */
(function (window, document) {
    'use strict';

    if (window.Dropzone) {
        window.Dropzone.autoDiscover = false;
    }

    function parseResponse(response) {
        if (typeof response !== 'string') {
            return response;
        }

        try {
            return JSON.parse(response);
        } catch (error) {
            return null;
        }
    }

    function responseMessage(response) {
        response = parseResponse(response);

        if (response && response.message) {
            return response.message;
        }

        return 'The image could not be uploaded.';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('gallery-dropzone');

        if (!form || !window.Dropzone) {
            return;
        }

        var options = document.querySelector(form.getAttribute('data-options-container'));
        var summary = document.getElementById('gallery-upload-summary');
        var added = 0;
        var failed = 0;

        function updateSummary() {
            if (!summary) {
                return;
            }

            var parts = [];

            if (added > 0) {
                parts.push(added + (added === 1 ? ' photo added.' : ' photos added.'));
            }

            if (failed > 0) {
                parts.push(failed + (failed === 1 ? ' photo could not be uploaded.' : ' photos could not be uploaded.'));
            }

            summary.textContent = parts.join(' ');
        }

        new window.Dropzone(form, {
            paramName: 'file',
            acceptedFiles: 'image/jpeg,image/png,image/webp',
            maxFilesize: Number((window.AdminConfig || {}).uploadMaxSizeMb || 20),
            parallelUploads: 3,
            timeout: 120000,
            dictInvalidFileType: 'Only JPG, PNG and WebP images are allowed.',
            init: function () {
                this.on('sending', function (file, xhr, formData) {
                    if (!options) {
                        return;
                    }

                    options.querySelectorAll('select, input').forEach(function (field) {
                        if (field.name) {
                            formData.append(field.name, field.value);
                        }
                    });
                });

                this.on('success', function (file, response) {
                    response = parseResponse(response);

                    if (!response || response.status !== 'success') {
                        this.emit('error', file, responseMessage(response));

                        return;
                    }

                    added++;
                    updateSummary();

                    if (response.edit_url && file.previewElement) {
                        var link = document.createElement('a');
                        link.href = response.edit_url;
                        link.className = 'dz-edit-link';
                        link.textContent = 'Edit caption';
                        link.setAttribute('aria-label', 'Edit caption for ' + (response.caption || 'this photo'));
                        file.previewElement.appendChild(link);
                    }
                });

                this.on('error', function (file, message) {
                    failed++;
                    updateSummary();

                    if (typeof message !== 'string' && file.previewElement) {
                        var messageElement = file.previewElement.querySelector('[data-dz-errormessage]');

                        if (messageElement) {
                            messageElement.textContent = responseMessage(message);
                        }
                    }
                });
            }
        });
    });
})(window, document);
