(function (window, document, $) {
  'use strict';

  if (!$ || !$.fn.fileupload) { return; }

  function parseResponse(result) {
    if (typeof result === 'object' && result !== null) { return result; }
    try { return JSON.parse(result); }
    catch (error) { return { status: 'error', message: 'The upload server returned an invalid response.' }; }
  }

  function formatFileSize(bytes) {
    if (typeof bytes !== 'number') { return ''; }
    if (bytes >= 1000000) { return (bytes / 1000000).toFixed(2) + ' MB'; }
    return (bytes / 1000).toFixed(2) + ' KB';
  }

  function createQueueItem(file) {
    var item = document.createElement('li');
    var copy = document.createElement('p');
    var size = document.createElement('i');
    var cancel = document.createElement('button');
    var progress = document.createElement('div');
    var bar = document.createElement('div');

    copy.textContent = file.name;
    size.textContent = formatFileSize(file.size);
    copy.appendChild(size);
    cancel.type = 'button';
    cancel.className = 'upload-cancel';
    cancel.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Cancel upload</span>';
    progress.className = 'progress';
    progress.setAttribute('role', 'progressbar');
    progress.setAttribute('aria-label', 'Upload progress');
    progress.setAttribute('aria-valuemin', '0');
    progress.setAttribute('aria-valuemax', '100');
    progress.setAttribute('aria-valuenow', '0');
    bar.className = 'progress-bar';
    bar.style.width = '0%';
    progress.appendChild(bar);
    item.appendChild(copy);
    item.appendChild(cancel);
    item.appendChild(progress);
    item.progress = progress;
    item.progressBar = bar;
    item.cancelButton = cancel;
    return item;
  }

  function addHidden(form, name, value) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.id = name;
    input.value = value || '';
    form.appendChild(input);
  }

  function appendUploadedFields(output) {
    var counter = document.getElementById('totalImages');
    if (!counter) { return; }
    var index = Number(counter.value || 0) + 1;
    counter.value = index;

    var isSlider = window.AdminConfig && window.AdminConfig.uploadVariant === 'slider';
    var form = document.getElementById(isSlider ? 'mslider_form' : ((window.AdminConfig.controllerName || '') + '_form'));
    if (!form) { return; }

    var row = document.createElement('div');
    var previewColumn = document.createElement('div');
    var fieldColumn = document.createElement('div');
    var image = document.createElement('img');
    var label = document.createElement('label');
    var nameInput = document.createElement('input');
    var remove = document.createElement('button');
    var imageDirectory = isSlider ? 'slider' : window.AdminConfig.controllerName;

    row.className = 'row g-3 align-items-end admin-field mb-3';
    row.id = 'imgContainer' + index;
    previewColumn.className = 'col-sm-3 col-lg-2';
    fieldColumn.className = 'col-sm-7 col-lg-5';
    image.className = 'admin-uploaded-image';
    image.src = window.AdminConfig.baseUrl + 'assets/frontend/images/' + encodeURIComponent(imageDirectory) + '/' + encodeURIComponent(output.thumb_image || output.file_name || '');
    image.alt = 'Uploaded image preview';
    label.className = 'form-label';
    label.setAttribute('for', (isSlider ? 'slider_title' : 'name') + index);
    label.textContent = isSlider ? 'Title' : 'Name';
    nameInput.type = 'text';
    nameInput.className = 'form-control';
    nameInput.required = !isSlider;
    nameInput.value = isSlider ? '' : 'Image';
    nameInput.name = (isSlider ? 'slider_title' : 'name') + index;
    nameInput.id = nameInput.name;
    remove.type = 'button';
    remove.className = 'btn btn-outline-danger btn-sm mt-2';
    remove.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i> Remove image';
    remove.addEventListener('click', function () { row.remove(); });

    if (isSlider) {
      addHidden(row, 'slider_image' + index, output.file_name);
      addHidden(row, 'slider_thumb_image' + index, output.thumb_image);
    } else {
      addHidden(row, 'large_image' + index, output.file_name);
      addHidden(row, 'thumb_image' + index, output.thumb_image);
    }

    previewColumn.appendChild(image);
    fieldColumn.appendChild(label);
    fieldColumn.appendChild(nameInput);
    fieldColumn.appendChild(remove);
    row.appendChild(previewColumn);
    row.appendChild(fieldColumn);
    form.insertBefore(row, form.firstChild);
  }

  $(function () {
    var upload = document.getElementById('upload');
    if (!upload) { return; }
    var list = upload.querySelector('ul');
    var drop = document.getElementById('drop');
    var browse = drop && drop.querySelector('a');
    var fileInput = drop && drop.querySelector('input[type="file"]');

    if (browse && fileInput) {
      browse.setAttribute('role', 'button');
      browse.setAttribute('tabindex', '0');
      browse.addEventListener('click', function () { fileInput.click(); });
      browse.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); fileInput.click(); }
      });
    }

    $('#upload').fileupload({
      dropZone: $('#drop'),
      dataType: 'text',
      add: function (event, data) {
        var file = data.files[0];
        var accepted = /\.(jpe?g|png|webp)$/i.test(file.name) && (!file.type || /^image\/(jpeg|png|webp)$/i.test(file.type));
        var item = createQueueItem(file);
        list.appendChild(item);
        data.context = $(item);
        if (!accepted) {
          item.classList.add('error');
          item.querySelector('p').appendChild(document.createTextNode(' — Only JPG, JPEG, PNG, and WebP files are accepted.'));
          item.cancelButton.remove();
          return;
        }
        var request = data.submit();
        item.cancelButton.addEventListener('click', function () { request.abort(); item.remove(); });
      },
      progress: function (event, data) {
        var percent = Math.round(data.loaded / data.total * 100);
        var item = data.context && data.context[0];
        if (!item) { return; }
        item.progressBar.style.width = percent + '%';
        item.progress.setAttribute('aria-valuenow', String(percent));
      },
      done: function (event, data) {
        var output = parseResponse(data.result);
        var item = data.context && data.context[0];
        if (output.status === 'success') {
          appendUploadedFields(output);
          if (item) {
            item.classList.add('upload-complete');
            item.cancelButton.remove();
            item.querySelector('p').appendChild(document.createTextNode(' — Uploaded'));
          }
        } else if (item) {
          item.classList.add('error');
          item.querySelector('p').appendChild(document.createTextNode(' — ' + (output.message || 'Upload failed.')));
        }
      },
      fail: function (event, data) {
        var item = data.context && data.context[0];
        if (item) {
          item.classList.add('error');
          item.querySelector('p').appendChild(document.createTextNode(' — Upload failed. Please try again.'));
        }
      }
    });

    $(document).on('dragover dragenter', '#drop', function () { drop.classList.add('is-dragover'); });
    $(document).on('dragleave drop', '#drop', function () { drop.classList.remove('is-dragover'); });
    $(document).on('drop dragover', function (event) { event.preventDefault(); });
  });
}(window, document, window.jQuery));
