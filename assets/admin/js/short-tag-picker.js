/**
 * Short tag picker for notification template forms
 * (application/views/admin/partials/short_tag_picker.php).
 *
 * The + action inserts {{tag}} into the field marked data-short-tag-target that
 * last had focus, or into the field marked data-short-tag-default. CKEditor
 * fields receive the tag at the cursor, or at the end if the editor has not
 * been clicked into yet. The copy action is handled by the shared
 * data-copy-value handler in admin.js.
 */
(function () {
  'use strict';

  function initialize() {
    document.querySelectorAll('[data-short-tag-picker]').forEach(function (picker) {
      if (picker.getAttribute('data-short-tag-picker-ready') === 'true') {
        return;
      }
      picker.setAttribute('data-short-tag-picker-ready', 'true');
      initializePicker(picker);
    });
  }

  function initializePicker(picker) {
    var scope = picker.closest('form') || document;
    var targets = Array.prototype.slice.call(scope.querySelectorAll('[data-short-tag-target]'));
    var target = targets.filter(function (field) {
      return field.hasAttribute('data-short-tag-default') && !field.readOnly;
    })[0] || targets.filter(function (field) { return !field.readOnly; })[0] || null;

    targets.forEach(function (field) {
      field.addEventListener('focus', function () {
        if (!field.readOnly) {
          target = field;
        }
      });
      watchEditor(field, function () {
        target = field;
      });
    });

    picker.querySelectorAll('[data-short-tag-insert]').forEach(function (button) {
      // Keep the caret in the field being edited while the button is pressed.
      button.addEventListener('mousedown', function (event) {
        event.preventDefault();
      });
      button.addEventListener('click', function () {
        if (!target) {
          return;
        }
        var text = '{{' + button.getAttribute('data-short-tag-insert') + '}}';
        var editor = editorFor(target);
        if (editor) {
          insertIntoEditor(editor, text);
        } else {
          insertIntoField(target, text);
        }
      });
    });
  }

  function editorFor(field) {
    if (!window.CKEDITOR || !field.id) {
      return null;
    }

    return CKEDITOR.instances[field.id] || null;
  }

  // CKEditor replaces its textarea, so focus is tracked through the editor instead.
  function watchEditor(field, onFocus) {
    if (!window.CKEDITOR || !field.id) {
      return;
    }

    var attach = function (editor) {
      if (editor.shortTagPickerWatched) {
        return;
      }
      editor.shortTagPickerWatched = true;
      editor.on('focus', function () {
        editor.shortTagPickerFocused = true;
        onFocus();
      });
    };

    if (CKEDITOR.instances[field.id]) {
      attach(CKEDITOR.instances[field.id]);
    }
    CKEDITOR.on('instanceReady', function (event) {
      if (event.editor.name === field.id) {
        attach(event.editor);
      }
    });
  }

  function insertIntoEditor(editor, text) {
    if (editor.mode !== 'wysiwyg') {
      editor.setData(editor.getData() + text);
      return;
    }

    var firstUse = !editor.shortTagPickerFocused;
    editor.focus();
    if (firstUse) {
      var range = editor.createRange();
      range.moveToElementEditEnd(range.root);
      editor.getSelection().selectRanges([range]);
    }
    editor.insertText(text);
  }

  function insertIntoField(field, text) {
    var start = typeof field.selectionStart === 'number' ? field.selectionStart : field.value.length;
    var end = typeof field.selectionEnd === 'number' ? field.selectionEnd : field.value.length;
    var max = parseInt(field.getAttribute('maxlength'), 10) || 0;
    if (max && field.value.length - (end - start) + text.length > max) {
      return;
    }

    field.value = field.value.slice(0, start) + text + field.value.slice(end);
    field.focus();
    field.selectionStart = field.selectionEnd = start + text.length;
    field.dispatchEvent(new Event('input', { bubbles: true }));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
  } else {
    initialize();
  }
})();
