(function () {
  'use strict';

  function extensionOf(name) {
    var parts = String(name || '').toLowerCase().split('.');
    return parts.length > 1 ? parts.pop() : '';
  }

  function documentIcon(extension) {
    return extension === 'pdf' ? 'bi-file-earmark-pdf' :
      (extension === 'doc' || extension === 'docx' ? 'bi-file-earmark-word' : 'bi-file-earmark');
  }

  document.querySelectorAll('[data-file-upload]').forEach(function (component) {
    var input = component.querySelector('[data-file-upload-input]');
    var preview = component.querySelector('[data-file-upload-preview]');
    var error = component.querySelector('[data-file-upload-error]');
    var maxSize = parseInt(component.getAttribute('data-max-size'), 10) || 0;
    var previewSize = parseInt(component.getAttribute('data-preview-size'), 10) || 0;
    var allowed = (component.getAttribute('data-allowed-extensions') || '').split(',').filter(Boolean);
    var objectUrl = '';
    if (!input || !preview) return;

    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      var extension = file ? extensionOf(file.name) : '';
      input.classList.remove('is-invalid');
      error.textContent = '';
      if (!file) return;

      if (allowed.length && allowed.indexOf(extension) === -1) {
        input.value = '';
        input.classList.add('is-invalid');
        error.textContent = 'Choose a file in one of these formats: ' + allowed.join(', ').toUpperCase() + '.';
        return;
      }
      if (maxSize && file.size > maxSize) {
        input.value = '';
        input.classList.add('is-invalid');
        error.textContent = 'The selected file is larger than the allowed upload size.';
        return;
      }

      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = URL.createObjectURL(file);
      preview.replaceChildren();
      if (file.type.indexOf('image/') === 0) {
        var link = document.createElement('a');
        var image = document.createElement('img');
        link.className = 'fancybox admin-file-preview-live';
        link.href = objectUrl;
        if (previewSize) { link.style.width = previewSize + 'px'; link.style.height = previewSize + 'px'; }
        image.src = objectUrl;
        image.alt = 'Selected image preview';
        link.appendChild(image);
        preview.appendChild(link);
      } else {
        var documentLink = document.createElement('a');
        var icon = document.createElement('i');
        var label = document.createElement('span');
        documentLink.className = 'admin-document-preview';
        documentLink.href = objectUrl;
        documentLink.target = '_blank';
        documentLink.rel = 'noopener';
        icon.className = 'bi ' + documentIcon(extension);
        icon.setAttribute('aria-hidden', 'true');
        label.textContent = file.name;
        documentLink.appendChild(icon);
        documentLink.appendChild(label);
        preview.appendChild(documentLink);
      }
    });
  });
}());
