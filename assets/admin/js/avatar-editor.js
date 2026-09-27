(function () {
  'use strict';

  document.querySelectorAll('[data-avatar-editor]').forEach(function (root) {
    if (typeof window.Cropper !== 'function' || !window.bootstrap) return;

    var form = root.closest('form');
    var sourceInput = root.querySelector('[data-avatar-source]');
    var outputInput = root.querySelector('[data-avatar-output]');
    var removeInput = root.querySelector('[data-avatar-remove-input]');
    var cropImage = root.querySelector('[data-avatar-crop-image]');
    var modalElement = root.querySelector('[data-avatar-modal]');
    var preview = root.querySelector('[data-avatar-preview]');
    var status = root.querySelector('[data-avatar-status]');
    var chooseButton = root.querySelector('[data-avatar-choose]');
    if (!form || !sourceInput || !outputInput || !cropImage || !modalElement || !preview || !status || !chooseButton) return;

    var modal = new window.bootstrap.Modal(modalElement, { backdrop: 'static' });
    var fallbackInitial = root.getAttribute('data-avatar-fallback') || 'A';
    var imageRequired = root.getAttribute('data-avatar-required') === 'true';
    var hasSavedImage = root.getAttribute('data-avatar-has-image') === 'true';
    var maxBytes = parseInt(root.getAttribute('data-avatar-max-bytes'), 10) || (10 * 1024 * 1024);
    var outputSize = parseInt(root.getAttribute('data-avatar-output-size'), 10) || 512;
    var hasPreparedImage = false;
    var cropper = null;
    var objectUrl = '';
    var pendingSelection = false;
    var applying = false;

    function clearObjectUrl() { if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = ''; } }
    function destroyCropper() { if (cropper) cropper.destroy(); cropper = null; }
    function showError(message) { status.textContent = message; status.classList.remove('text-primary'); status.classList.add('text-danger'); }

    chooseButton.addEventListener('click', function () { sourceInput.click(); });
    sourceInput.addEventListener('change', function () {
      var file = sourceInput.files && sourceInput.files[0];
      if (!file) return;
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { sourceInput.value = ''; showError('Please choose a JPG, PNG or WebP image.'); return; }
      if (file.size > maxBytes) { sourceInput.value = ''; showError('The selected image is larger than the allowed upload size.'); return; }
      destroyCropper(); clearObjectUrl(); objectUrl = URL.createObjectURL(file);
      cropImage.onload = function () { pendingSelection = true; modal.show(); };
      cropImage.src = objectUrl;
    });

    modalElement.addEventListener('shown.bs.modal', function () {
      if (!pendingSelection || cropper) return;
      cropper = new window.Cropper(cropImage, {
        aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 0.8, responsive: true,
        restore: false, modal: true, guides: true, center: true, highlight: false,
        background: true, movable: true, rotatable: true, scalable: false, zoomable: true,
        zoomOnTouch: true, zoomOnWheel: true, cropBoxMovable: true, cropBoxResizable: true,
        toggleDragModeOnDblclick: false, minCropBoxWidth: 80, minCropBoxHeight: 80
      });
    });
    modalElement.addEventListener('hidden.bs.modal', function () {
      if (!applying && pendingSelection) { sourceInput.value = ''; pendingSelection = false; }
      applying = false; destroyCropper(); clearObjectUrl();
    });

    root.querySelector('.admin-avatar-crop-tools').addEventListener('click', function (event) {
      var button = event.target.closest('[data-avatar-action]');
      if (!button || !cropper) return;
      switch (button.getAttribute('data-avatar-action')) {
        case 'zoom-in': cropper.zoom(0.1); break;
        case 'zoom-out': cropper.zoom(-0.1); break;
        case 'rotate-left': cropper.rotate(-90); break;
        case 'rotate-right': cropper.rotate(90); break;
        case 'reset': cropper.reset(); break;
      }
    });

    root.querySelector('[data-avatar-apply]').addEventListener('click', function () {
      if (!cropper) return;
      applying = true;
      var canvas = cropper.getCroppedCanvas({ width: outputSize, height: outputSize, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
      if (!canvas) { applying = false; showError('The adjusted image could not be created. Please try another image.'); return; }
      canvas.toBlob(function (blob) {
        if (!blob) { applying = false; showError('The adjusted image could not be created. Please try another image.'); return; }
        var transfer = new DataTransfer();
        transfer.items.add(new File([blob], 'avatar.png', { type: 'image/png' }));
        outputInput.files = transfer.files; hasPreparedImage = true;
        if (removeInput) removeInput.value = '0';
        preview.replaceChildren();
        var previewUrl = canvas.toDataURL('image/png');
        var previewLink = document.createElement('a');
        var image = document.createElement('img');
        previewLink.className = 'fancybox admin-avatar-preview-link';
        previewLink.href = previewUrl;
        previewLink.setAttribute('aria-label', 'Preview adjusted image');
        image.src = previewUrl;
        image.alt = 'New image preview';
        previewLink.appendChild(image);
        preview.appendChild(previewLink);
        status.textContent = 'Image adjusted and ready to save.'; status.classList.remove('text-danger'); status.classList.add('text-primary');
        root.classList.remove('validate-has-error'); pendingSelection = false; modal.hide();
      }, 'image/png');
    });

    var removeButton = root.querySelector('[data-avatar-remove]');
    if (removeButton) removeButton.addEventListener('click', function () {
      if (removeInput) removeInput.value = '1';
      outputInput.value = ''; hasPreparedImage = false; hasSavedImage = false;
      preview.innerHTML = '<span aria-hidden="true"></span>'; preview.querySelector('span').textContent = fallbackInitial;
      status.textContent = 'The current image will be removed when you save.'; status.classList.remove('text-danger'); status.classList.add('text-primary'); removeButton.disabled = true;
    });

    form.addEventListener('submit', function (event) {
      if (pendingSelection) { event.preventDefault(); showError('Finish adjusting the selected image or cancel the editor before submitting.'); modal.show(); return; }
      if (imageRequired && !hasSavedImage && !hasPreparedImage) {
        event.preventDefault(); showError('An image is required. Choose and adjust an image before submitting.'); root.classList.add('validate-has-error'); chooseButton.focus();
      }
    });
  });
}());
