(function (window, document, $) {
  'use strict';

  var AdminUI = {
    showModal: function (id) {
      var element = document.getElementById(id);
      if (element && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(element).show();
      }
    },
    hideModal: function (id) {
      var element = document.getElementById(id);
      if (element && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(element).hide();
      }
    }
  };

  window.AdminUI = AdminUI;

  function initializeSidebar() {
    var body = document.body;
    var mobileToggle = document.querySelector('[data-sidebar-toggle]');
    var closeButtons = document.querySelectorAll('[data-sidebar-close]');
    var collapseButton = document.querySelector('[data-sidebar-collapse]');

    function closeSubnav(subnav) {
      if (!subnav.classList.contains('show')) { return; }
      var toggle = document.querySelector('[data-bs-target="#' + subnav.id + '"]');
      if (window.bootstrap) {
        bootstrap.Collapse.getOrCreateInstance(subnav, { toggle: false }).hide();
      } else {
        subnav.classList.remove('show');
        if (toggle) {
          toggle.classList.add('collapsed');
          toggle.setAttribute('aria-expanded', 'false');
        }
      }
    }

    if (window.localStorage && window.localStorage.getItem('adminSidebarCollapsed') === '1' && window.innerWidth >= 992) {
      body.classList.add('admin-sidebar-collapsed');
      document.querySelectorAll('.admin-subnav.show').forEach(closeSubnav);
    }

    function setMobileOpen(open) {
      body.classList.toggle('admin-sidebar-open', open);
      if (mobileToggle) {
        mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    }

    if (mobileToggle) {
      mobileToggle.addEventListener('click', function () {
        setMobileOpen(!body.classList.contains('admin-sidebar-open'));
      });
    }

    closeButtons.forEach(function (button) {
      button.addEventListener('click', function () { setMobileOpen(false); });
    });

    if (collapseButton) {
      collapseButton.addEventListener('click', function () {
        var collapsed = body.classList.toggle('admin-sidebar-collapsed');
        collapseButton.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        collapseButton.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        if (window.localStorage) {
          window.localStorage.setItem('adminSidebarCollapsed', collapsed ? '1' : '0');
        }
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && body.classList.contains('admin-sidebar-open')) {
        setMobileOpen(false);
        if (mobileToggle) { mobileToggle.focus(); }
      }
    });

    document.querySelectorAll('.admin-nav a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 992) { setMobileOpen(false); }
      });
    });

    document.querySelectorAll('.admin-subnav').forEach(function (subnav) {
      subnav.addEventListener('show.bs.collapse', function () {
        if (!body.classList.contains('admin-sidebar-collapsed')) { return; }
        var toggle = document.querySelector('[data-bs-target="#' + subnav.id + '"]');
        if (!toggle) { return; }
        var estimatedHeight = subnav.querySelectorAll('.admin-subnav-link').length * 34 + 16;
        var flyoutTop = Math.min(toggle.getBoundingClientRect().top, window.innerHeight - estimatedHeight - 8);
        subnav.style.top = Math.max(8, flyoutTop) + 'px';
      });
    });

    document.addEventListener('click', function (event) {
      document.querySelectorAll('.admin-subnav.show').forEach(function (openSubnav) {
        var toggle = document.querySelector('[data-bs-target="#' + openSubnav.id + '"]');
        if (openSubnav.contains(event.target) || (toggle && toggle.contains(event.target))) {
          return;
        }
        closeSubnav(openSubnav);
      });
    });

    document.querySelectorAll('.admin-subnav-link').forEach(function (link) {
      link.addEventListener('click', function () {
        var subnav = link.closest('.admin-subnav');
        if (subnav) { closeSubnav(subnav); }
      });
    });
  }

  function enhanceForms() {
    document.querySelectorAll('input.admin-input-md, input.admin-input-sm, input.admin-input-wide').forEach(function (input) {
      input.classList.add('form-control');
    });
    document.querySelectorAll('select.admin-input-md, select.admin-input-sm, select.admin-native-select').forEach(function (select) {
      if (!select.classList.contains('select2')) { select.classList.add('form-select'); }
    });
    document.querySelectorAll('select:not(.select2)').forEach(function (select) {
      select.classList.remove('form-control');
      select.classList.add('form-select');
    });
    document.querySelectorAll('input[type="file"]').forEach(function (input) { input.classList.add('form-control'); });
    document.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(function (input) { input.classList.add('form-check-input'); });

    document.querySelectorAll('.admin-field').forEach(function (group) {
      var hasGridChildren = Array.prototype.some.call(group.children, function (child) {
        return Array.prototype.some.call(child.classList || [], function (className) { return className.indexOf('col-') === 0; });
      });
      if (hasGridChildren) {
        group.classList.add('row', 'g-2', 'align-items-center');
      }
      var control = group.querySelector('input:not([type="hidden"]), select, textarea');
      var label = group.querySelector('label');
      if (!control || !label) { return; }
      if (!control.id && control.name) {
        control.id = 'field-' + control.name.replace(/[^a-zA-Z0-9_-]/g, '-');
      }
      if (control.id && !label.getAttribute('for') && !label.contains(control)) {
        label.setAttribute('for', control.id);
      }
      var validation = control.getAttribute('data-validate') || '';
      if (validation.indexOf('required') !== -1 || control.required || control.classList.contains('required')) {
        control.setAttribute('aria-required', 'true');
        label.classList.add('is-required');
      }
    });

    document.querySelectorAll('.alertBox').forEach(function (button) {
      button.classList.add('btn-close');
      button.textContent = '';
      button.setAttribute('type', 'button');
      button.setAttribute('aria-label', 'Dismiss notification');
      button.removeAttribute('data-bs-dismiss');
    });

    document.querySelectorAll('button').forEach(function (button) {
      if ((button.textContent || '').trim().toLowerCase() === 'cancel') {
        button.classList.remove('btn-danger', 'btn-success', 'btn-primary');
        button.classList.add('btn-outline-secondary');
      }
    });

    if ($ && $.fn.select2) {
      $('.select2').not('#blog_category, #blog_tags').each(function () {
        var $select = $(this);
        if (!$select.data('select2')) {
          // Inside a Bootstrap modal the dropdown must live in the modal, or it opens behind it.
          $select.select2({
            width: '100%',
            placeholder: $select.attr('data-placeholder') || undefined,
            dropdownParent: $select.closest('.modal').length ? $select.closest('.modal') : $(document.body)
          });
        }
      });
    }
  }

  function rulesFromAttribute(value) {
    var rules = {};
    (value || '').split(',').forEach(function (token) {
      token = token.trim();
      var match = token.match(/^([a-z]+)(?:\[([^\]]+)\])?$/i);
      if (!match) { return; }
      if (match[2]) { rules[match[1]] = /^\d+$/.test(match[2]) ? Number(match[2]) : match[2]; }
      else { rules[match[1]] = true; }
    });
    return rules;
  }

  // Turns a number, an H:i / H:i:s time, or an m/d/Y date into a comparable number.
  function comparableValue(raw) {
    var value = (raw === null || raw === undefined) ? '' : String(raw).trim();
    if (value === '') { return null; }
    if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(value)) {
      var parts = value.split(':');
      return (Number(parts[0]) * 3600) + (Number(parts[1]) * 60) + Number(parts[2] || 0);
    }
    if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(value)) {
      var dateParts = value.split('/');
      return (Number(dateParts[2]) * 10000) + (Number(dateParts[0]) * 100) + Number(dateParts[1]);
    }
    var number = parseFloat(value);
    return isNaN(number) ? null : number;
  }

  function compareWithField(value, param, compare) {
    var $other = $(param);
    if (!$other.length) { return true; }
    var other = comparableValue($other.val());
    var current = comparableValue(value);
    if (other === null || current === null) { return true; }
    return compare(current, other);
  }

  // Strips tags, decodes entities, and collapses whitespace so empty rich-text
  // editor output (e.g. "<p><br></p>", "&nbsp;") is treated as a blank field.
  function richTextToPlainText(html) {
    var withoutTags = String(html || '').replace(/<[^>]*>/g, '');
    var decoder = document.createElement('textarea');
    decoder.innerHTML = withoutTags;
    return decoder.value.replace(/[s ]/g, '');
  }

  function ckeditorInstanceFor(element) {
    if (!window.CKEDITOR) { return null; }
    return CKEDITOR.instances[element.id] || CKEDITOR.instances[element.name] || null;
  }

  // CKEditor only syncs its WYSIWYG content back into the source <textarea>
  // when the browser fires a native "submit" event on the form. jQuery
  // Validate's submitHandler calls form.submit() directly to avoid
  // re-triggering validation, and a programmatic .submit() call never fires
  // that event, so every CKEditor instance on the form must be told to push
  // its content into the textarea before we submit it manually.
  function syncCkeditorInstances(form) {
    if (!window.CKEDITOR) { return; }
    $(form).find('textarea').each(function () {
      var editor = ckeditorInstanceFor(this);
      if (editor) { editor.updateElement(); }
    });
  }

  // Classic CKEditor lays out its editing iframe using the container's
  // measured dimensions at creation time. An editor created inside a
  // Bootstrap accordion/collapse panel that starts hidden (display: none)
  // measures a zero-width container, so the toolbar renders once the panel
  // is expanded but the editable area stays unusable — clicking into it
  // does not accept input. Forcing a resize once the panel is actually
  // visible fixes the layout without needing to defer editor creation.
  function initializeCkeditorAccordions() {
    document.addEventListener('shown.bs.collapse', function (event) {
      if (!window.CKEDITOR) { return; }
      event.target.querySelectorAll('textarea').forEach(function (textarea) {
        var editor = ckeditorInstanceFor(textarea);
        if (editor && editor.status === 'ready') {
          editor.resize('100%', editor.config.height || '340px');
        }
      });
    });
  }

  function initializeValidation() {
    if (!$ || !$.fn.validate) { return; }
    if (!$.validator.methods.greaterthanfield) {
      $.validator.addMethod('greaterthanfield', function (value, element, param) {
        return compareWithField(value, param, function (current, other) { return current > other; });
      }, 'Please enter a greater value.');
    }
    if (!$.validator.methods.notlessthanfield) {
      $.validator.addMethod('notlessthanfield', function (value, element, param) {
        return compareWithField(value, param, function (current, other) { return current >= other; });
      }, 'Please enter a value that is not lower.');
    }
    if (!$.validator.methods.onepositiveingroup) {
      $.validator.addMethod('onepositiveingroup', function (value, element, param) {
        return $(param).toArray().some(function (field) {
          var raw = $.trim(field.value);
          return /^\d+$/.test(raw) && parseInt(raw, 10) > 0;
        });
      }, 'Enter a value greater than 0 for at least one of these fields.');
    }
    if (!$.validator.methods.htmlrequired) {
      $.validator.addMethod('htmlrequired', function (value, element) {
        var editor = ckeditorInstanceFor(element);
        var html = editor ? editor.getData() : value;
        return richTextToPlainText(html) !== '';
      }, 'This field is required.');
    }
    $('form.validate').not('#adm_setting, #admin_form').each(function () {
      var $form = $(this);
      $form.validate({
        // jQuery Validate ignores ":hidden" fields by default. CKEditor hides its
        // underlying textarea once it replaces it, so a required rich-text field
        // must be excluded from that default or it is silently never validated.
        // Everything else stays ignored while hidden, same as before.
        ignore: ':hidden:not([data-validate*="htmlrequired"])',
        errorClass: 'validate-has-error',
        errorElement: 'span',
        errorPlacement: function (error, element) {
          error.attr('role', 'alert');
          var editor = ckeditorInstanceFor(element[0]);
          if (editor && editor.container) {
            error.insertAfter($(editor.container.$));
          } else if (element.hasClass('select2') && element.next('.select2').length) {
            error.insertAfter(element.next('.select2'));
          } else if (element.hasClass('intl-phone') && element.closest('.iti').length) {
            error.insertAfter(element.closest('.iti'));
          } else if (element.closest('.input-group').length) {
            error.insertAfter(element.closest('.input-group'));
          } else {
            error.insertAfter(element);
          }
        },
        highlight: function (element) {
          $(element).addClass('is-invalid').attr('aria-invalid', 'true').closest('.admin-field').addClass('validate-has-error');
        },
        unhighlight: function (element) {
          $(element).removeClass('is-invalid').removeAttr('aria-invalid').closest('.admin-field').removeClass('validate-has-error');
        },
        submitHandler: function (form, event) {
          // Respect earlier feature-specific validation, such as the avatar editor.
          if (event && typeof event.isDefaultPrevented === 'function' && event.isDefaultPrevented()) {
            return false;
          }
          if (typeof form.manageTranslationSetSaving === 'function') {
            form.manageTranslationSetSaving();
          }
          syncCkeditorInstances(form);
          if (form.hasAttribute('data-submit-lock')) {
            $(form).find(':submit').prop('disabled', true).attr('aria-disabled', 'true');
          }
          form.submit();
        }
      });
      $form.find('[data-validate]').each(function () {
        $(this).rules('add', rulesFromAttribute(this.getAttribute('data-validate')));
      });
      $form.find('[data-validate*="onepositiveingroup"]').each(function () {
        var match = this.getAttribute('data-validate').match(/onepositiveingroup\[([^\]]+)\]/);
        if (!match) { return; }
        var $group = $form.find(match[1]);
        $(this).on('input blur', function () { $group.each(function () { $(this).valid(); }); });
      });
      $form.find('[data-validate*="htmlrequired"]').each(function () {
        var field = this;
        function revalidate() { $(field).valid(); }
        var editor = ckeditorInstanceFor(field);
        if (editor) { editor.on('blur', revalidate); }
        else if (window.CKEDITOR) { CKEDITOR.on('instanceReady', function (event) { if (event.editor.element.$ === field) event.editor.on('blur', revalidate); }); }
      });
    });
  }

  function initializeRequiredSubmitStates() {
    document.querySelectorAll('form[data-submit-requires]').forEach(function (form) {
      var selectors = (form.getAttribute('data-submit-requires') || '').split(',').filter(Boolean);
      var fields = selectors.map(function (selector) { return form.querySelector(selector.trim()); }).filter(Boolean);
      var submitButton = form.querySelector('button[type="submit"]');
      if (!fields.length || !submitButton) { return; }

      function requiredFieldsEntered() {
        return fields.every(function (field) {
          return field.type === 'password' ? field.value !== '' : field.value.trim() !== '';
        });
      }

      function updateSubmitState() {
        var enabled = requiredFieldsEntered();
        submitButton.disabled = !enabled;
        submitButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
      }

      fields.forEach(function (field) {
        field.addEventListener('input', updateSubmitState);
        field.addEventListener('change', updateSubmitState);
      });
      form.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !requiredFieldsEntered()) {
          event.preventDefault();
          updateSubmitState();
        }
      });

      updateSubmitState();
    });
  }

  function initializeDateTimeControls() {
    if (!window.flatpickr) { return; }
    document.querySelectorAll('.datepicker').forEach(function (input) {
      var longDate = input.id === 'pub_date' || input.id === 'unpub_date';
      flatpickr(input, {
        allowInput: true,
        dateFormat: longDate ? 'd F Y' : 'm/d/Y',
        disableMobile: true
      });
    });
    document.querySelectorAll('.timepicker').forEach(function (input) {
      var useMeridian = input.getAttribute('data-show-meridian') !== 'false';
      var showSeconds = input.getAttribute('data-show-seconds') === 'true';
      flatpickr(input, {
        allowInput: true,
        enableTime: true,
        noCalendar: true,
        enableSeconds: showSeconds,
        time_24hr: !useMeridian,
        dateFormat: useMeridian ? (showSeconds ? 'h:i:S K' : 'h:i K') : (showSeconds ? 'H:i:S' : 'H:i'),
        minuteIncrement: Number(input.getAttribute('data-minute-step') || 1),
        disableMobile: true
      });
    });
    document.querySelectorAll('.daterange').forEach(function (input) {
      flatpickr(input, {
        allowInput: true,
        mode: 'range',
        conjunction: ' to ',
        dateFormat: 'd-M-Y',
        disableMobile: true
      });
    });
  }

  function wrapTables() {
    document.querySelectorAll('.admin-content table.table').forEach(function (table) {
      if (table.parentElement && table.parentElement.classList.contains('table-responsive')) { return; }
      var wrapper = document.createElement('div');
      wrapper.className = 'table-responsive';
      table.parentNode.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    });

    document.querySelectorAll('.admin-content .pagination').forEach(function (pagination) {
      pagination.setAttribute('aria-label', pagination.getAttribute('aria-label') || 'Pagination');
    });
  }

  function styleStatus(element) {
    var status = (element.textContent || '').trim().toLowerCase();
    element.classList.add('status-badge');
    element.classList.remove('status-enabled', 'status-disabled', 'status-published', 'status-warning');
    if (status === 'enable' || status === 'enabled' || status === 'active' || status === 'yes') {
      element.classList.add('status-enabled');
    } else if (status === 'published') {
      element.classList.add('status-published');
    } else if (status === 'disable' || status === 'disabled' || status === 'inactive' || status === 'no' || status === 'un-published') {
      element.classList.add('status-disabled');
    } else {
      element.classList.add('status-warning');
    }
  }

  function enhanceListings() {
    document.querySelectorAll('.changestatus, [id^="statusID"]').forEach(function (element) {
      styleStatus(element);
      new MutationObserver(function () { styleStatus(element); }).observe(element, { childList: true, characterData: true, subtree: true });
    });
    var deleteAll = document.getElementById('deleteAllRecords');
    if (deleteAll) {
      deleteAll.classList.remove('btn-outline-secondary');
      deleteAll.classList.add('btn-outline-danger');
    }
    document.querySelectorAll('button[onclick*="/control"], button[onclick*="control/"]').forEach(function (button) {
      if (button.id !== 'deleteAllRecords') {
        button.classList.remove('btn-outline-secondary', 'btn-success');
        button.classList.add('btn-primary');
      }
    });
    document.querySelectorAll('.table').forEach(function (table) {
      var headings = table.querySelectorAll('thead th');
      if (!headings.length || !/^(actions?|details)$/i.test((headings[headings.length - 1].textContent || '').trim())) { return; }

      table.classList.add('admin-action-table');
      table.querySelectorAll('tbody tr').forEach(function (row) {
        var cells = row.querySelectorAll(':scope > td');
        if (!cells.length) { return; }
        var actionCell = cells[cells.length - 1];
        if (actionCell.hasAttribute('colspan')) { return; }
        actionCell.classList.add('admin-action-cell');

        actionCell.querySelectorAll('a, button').forEach(function (action) {
          if (action.closest('.dropdown-menu')) { return; }
          var icon = action.querySelector('i');
          if (!icon) { return; }

          var label = action.getAttribute('aria-label') || action.getAttribute('title') || '';
          var actionType = 'default';
          if (action.classList.contains('delitem') || icon.classList.contains('bi-x-circle') || icon.classList.contains('bi-trash')) {
            label = label || 'Delete';
            actionType = 'delete';
          } else if (action.classList.contains('dupitem') || icon.classList.contains('bi-copy')) {
            label = label || 'Duplicate';
            actionType = 'duplicate';
          } else if (icon.classList.contains('bi-pencil')) {
            label = label || 'Edit';
            actionType = 'edit';
          } else if (icon.classList.contains('bi-camera') || icon.classList.contains('bi-images')) {
            label = label || 'Manage images';
            actionType = 'media';
          } else if (icon.classList.contains('bi-signpost-split')) {
            label = label || 'Manage itineraries';
            actionType = 'itinerary';
          } else if (icon.classList.contains('bi-star-fill')) {
            label = label || 'View ratings';
            actionType = 'rating';
          } else if (icon.classList.contains('bi-question-circle')) {
            label = label || 'Manage FAQs';
            actionType = 'related';
          } else if (icon.classList.contains('bi-file-text')) {
            label = label || 'View details';
            actionType = 'view';
          } else if (icon.classList.contains('bi-eye')) {
            label = label || 'View';
            actionType = 'view';
          } else if (icon.classList.contains('bi-three-dots')) {
            label = label || 'More actions';
            actionType = 'more';
          } else {
            label = label || 'Open';
          }

          if (actionType === 'delete' && icon.classList.contains('bi-x-circle')) {
            icon.classList.remove('bi-x-circle');
            icon.classList.add('bi-trash');
          }
          var textProbe = action.cloneNode(true);
          textProbe.querySelectorAll('i, .badge').forEach(function (decoration) { decoration.remove(); });
          if ((textProbe.textContent || '').trim() !== '') { action.classList.add('admin-action-text'); }
          action.classList.add('admin-action-control', 'admin-action-' + actionType);
          // A count badge sits on the icon, so the control must not stay dimmed.
          if (action.querySelector('.badge')) { action.classList.add('admin-action-badged'); }
          action.setAttribute('aria-label', label);
          action.setAttribute('title', label);
          action.setAttribute('data-admin-tooltip', 'true');
          action.setAttribute('data-bs-placement', 'top');
        });
      });
    });

    document.querySelectorAll('.table tbody td[colspan]').forEach(function (cell) {
      var text = (cell.textContent || '').trim().toLowerCase();
      if (text.indexOf('no record') !== -1) {
        cell.classList.add('admin-empty-state');
        if (text === 'sorry! no records found.') { cell.textContent = 'No records found'; }
      }
    });
  }

  var adminPageCopy = {
    admins: { noun: 'administrator', list: 'Manage administrator accounts, access details, and account status.' },
    contactus: { noun: 'contact message', list: 'Review customer enquiries and the contact information submitted with each message.' },
    icategory: { noun: 'image category', list: 'Organize reusable website images into manageable categories.' },
    pages: { noun: 'web page', list: 'Create and manage the informational pages displayed on the website.' },
    slider: { noun: 'slider image', list: 'Manage promotional images, captions, links, display order, and visibility.' },
    sliders: { noun: 'image slider', list: 'Manage promotional image groups and where they appear on the website.' },
    'customer-reviews': { noun: 'customer review', list: 'Manage customer reviews, display order, and publishing status.' },
    menu: { noun: 'menu item', list: 'Manage website navigation items, links, hierarchy, and display order.' },
    foot: { noun: 'footer menu item', list: 'Manage footer navigation links, hierarchy, and display order.' }
  };

  function pageCopy(controllerName, headingText, kind) {
    var title = (headingText || '').trim();
    var lowerTitle = title.toLowerCase();
    var config = adminPageCopy[controllerName] || { noun: title.toLowerCase() || 'record', list: 'Review and manage the records available on this page.' };

    if (controllerName === 'home') {
      if (lowerTitle.indexOf('account') !== -1) { return 'Update your administrator profile, email address, and password.'; }
      if (lowerTitle.indexOf('contact') !== -1) { return 'Manage the contact details and location information displayed to customers.'; }
      if (lowerTitle.indexOf('website') !== -1) { return 'Manage core website identity, branding, contact, and integration settings.'; }
      return 'View booking requests, client messages, and important operational updates.';
    }
    if (/contact.*details/i.test(title)) { return 'Review the customer contact information and full enquiry message.'; }
    if (/upload/i.test(title)) { return 'Upload new ' + config.noun + ' files and save them to the current gallery.'; }
    if (/^add\b/i.test(title)) { return 'Add a new ' + config.noun + ' and provide the required information.'; }
    if (/^edit\b/i.test(title)) { return 'Update the selected ' + config.noun + '\u2019s information and operational details.'; }
    if (kind === 'form') { return 'Update the information below, then save your changes.'; }
    return config.list;
  }

  function enhanceStandardListingLayout() {
    var content = document.querySelector('.admin-content');
    if (!content || !document.body.classList.contains('admin-list-screen') || document.body.classList.contains('admin-report-screen')) { return; }
    if (content.querySelector('.web-pages-listing')) { return; }

    var table = content.querySelector('table[id^="table-"], #multiDel table.table');
    var heading = content.querySelector(':scope > h2');
    if (!table || !heading) { return; }

    var panel = table.closest('[class*="col-"]') || table.parentElement;
    var listingRow = panel && panel.closest('.row');
    var tableForm = table.closest('form');
    if (!panel || !tableForm) { return; }

    document.body.classList.add('admin-standard-listing');
    content.classList.add('web-pages-listing', 'admin-standard-listing');
    panel.classList.add('admin-standard-listing-panel');
    if (listingRow) { listingRow.classList.add('admin-standard-listing-row'); }
    table.classList.add('pages-listing-table');
    if (table.parentElement && table.parentElement.classList.contains('table-responsive')) {
      table.parentElement.classList.add('pages-table-responsive');
      table.parentElement.setAttribute('tabindex', '0');
      table.parentElement.setAttribute('aria-label', (heading.textContent || 'Records').trim() + ' table');
    }

    var header = document.createElement('header');
    header.className = 'pages-listing-header';
    var headingGroup = document.createElement('div');
    var description = document.createElement('p');
    var titleText = (heading.textContent || 'Records').trim();
    heading.classList.add('pages-listing-title');
    description.className = 'pages-listing-description';
    description.textContent = pageCopy((window.AdminConfig || {}).controllerName, titleText, 'listing');
    heading.parentNode.insertBefore(header, heading);
    headingGroup.appendChild(heading);
    headingGroup.appendChild(description);
    header.appendChild(headingGroup);

    var headerActions = document.createElement('div');
    headerActions.className = 'pages-header-actions';
    Array.prototype.slice.call(content.querySelectorAll('button[onclick*="/control"], button[onclick*="control/"], a.btn[href*="/control"]')).forEach(function (button) {
      if (button.id === 'deleteAllRecords') { return; }
      // Row-level actions belong to their table cell, not the page header.
      if (button.closest('table')) { return; }
      button.classList.remove('btn-outline-secondary', 'btn-success');
      button.classList.add('btn-primary');
      var buttonIcon = button.querySelector('i');
      if (buttonIcon) { button.insertBefore(buttonIcon, button.firstChild); }
      headerActions.appendChild(button);
    });
    if (headerActions.children.length) { header.appendChild(headerActions); }

    var filterForm = panel.querySelector('form.admin-filter-form');
    if (filterForm) {
      var toolbar = document.createElement('div');
      toolbar.className = 'pages-listing-toolbar';
      filterForm.classList.remove('float-end', 'pull-right');
      filterForm.classList.add('pages-filter-form');
      filterForm.setAttribute('role', 'search');
      filterForm.querySelectorAll('input:not([type="hidden"])').forEach(function (input) {
        input.setAttribute('aria-label', input.getAttribute('aria-label') || input.getAttribute('placeholder') || 'Search records');
      });
      filterForm.querySelectorAll('select').forEach(function (select) {
        select.classList.add('select2');
        select.setAttribute('data-minimum-results-for-search', '-1');
        select.setAttribute('aria-label', select.getAttribute('aria-label') || 'Filter by status');
        if ($ && $.fn.select2 && !$(select).data('select2')) {
          $(select).select2({ width: '100%', minimumResultsForSearch: Infinity });
        }
      });
      var searchInput = filterForm.querySelector('input:not([type="hidden"])');
      if (searchInput && !filterForm.querySelector('.pages-search-submit')) {
        var searchButton = document.createElement('button');
        searchButton.className = 'btn btn-outline-secondary pages-search-submit';
        searchButton.type = 'button';
        searchButton.innerHTML = '<i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span>';
        searchButton.setAttribute('aria-label', 'Search records');
        searchInput.insertAdjacentElement('afterend', searchButton);
        var triggerLegacySearch = function () {
          if ($) { $(searchInput).trigger({ type: 'keypress', keyCode: 13, which: 13 }); }
        };
        searchButton.addEventListener('click', triggerLegacySearch);
        filterForm.addEventListener('submit', function (event) {
          event.preventDefault();
          triggerLegacySearch();
        });
      }
      toolbar.appendChild(filterForm);
      panel.insertBefore(toolbar, tableForm);
    }

    var deleteAll = panel.querySelector('#deleteAllRecords');
    if (deleteAll) {
      var bulkActions = document.createElement('div');
      var bulkCount = document.createElement('span');
      var rowCheckboxes = table.querySelectorAll('.cselect');
      var selectAll = table.querySelector('#all-checkbox');
      bulkActions.className = 'pages-bulk-actions';
      bulkActions.setAttribute('aria-live', 'polite');
      bulkCount.className = 'pages-selection-count';
      bulkActions.appendChild(bulkCount);
      bulkActions.appendChild(deleteAll);
      panel.insertBefore(bulkActions, tableForm);

      var updateBulkState = function () {
        var selected = table.querySelectorAll('.cselect:checked').length;
        bulkCount.textContent = selected + ' selected';
        deleteAll.disabled = selected === 0;
        bulkActions.hidden = selected === 0;
      };
      rowCheckboxes.forEach(function (checkbox) { checkbox.addEventListener('change', updateBulkState); });
      if (selectAll) { selectAll.addEventListener('change', function () { window.setTimeout(updateBulkState, 0); }); }
      updateBulkState();
    }

    var perPage = panel.querySelector('.selectPerPage');
    var perPageSelect = perPage && perPage.querySelector('#per_page');
    if (perPage && perPageSelect) {
      perPageSelect.remove();
      perPage.textContent = '';
      perPage.className = 'pages-per-page-control';
      var perPageLabel = document.createElement('label');
      perPageLabel.setAttribute('for', 'per_page');
      perPageLabel.textContent = 'Rows per page';
      perPage.appendChild(perPageLabel);
      perPage.appendChild(perPageSelect);
    }

    var showingNode = null;
    var showingMatch = null;
    var walker = document.createTreeWalker(panel, window.NodeFilter.SHOW_TEXT, null);
    while (walker.nextNode()) {
      var node = walker.currentNode;
      var parent = node.parentElement;
      if (!parent || parent.closest('table, script, style')) { continue; }
      var match = (node.nodeValue || '').replace(/\s+/g, ' ').match(/Showing\s+(\d+)\s+to\s+(\d+)\s+of\s+(\d+)\s+Record(?:s)?/i);
      if (match) { showingNode = node; showingMatch = match; break; }
    }

    var pagination = panel.querySelector('.pagination');
    if (perPage || showingMatch || pagination) {
      var footer = document.createElement('footer');
      footer.className = 'pages-listing-footer';
      var summary = document.createElement('div');
      summary.className = 'pages-footer-summary';
      if (perPage) { summary.appendChild(perPage); }
      if (showingMatch) {
        var summaryText = document.createElement('p');
        summaryText.textContent = 'Showing ' + showingMatch[1] + '\u2013' + showingMatch[2] + ' of ' + showingMatch[3] + ' records';
        summary.appendChild(summaryText);
        showingNode.nodeValue = '';
      }
      if (summary.children.length) { footer.appendChild(summary); }
      if (pagination) {
        var paginationParent = pagination.parentElement;
        var paginationNav = document.createElement('nav');
        paginationNav.className = 'pages-pagination';
        paginationNav.setAttribute('aria-label', titleText + ' pagination');
        paginationNav.appendChild(pagination);
        footer.appendChild(paginationNav);
        if (paginationParent !== panel && paginationParent.children.length === 0 && !(paginationParent.textContent || '').trim()) {
          paginationParent.remove();
        }
      }
      panel.appendChild(footer);
    }

    content.querySelectorAll(':scope > br, :scope > hr').forEach(function (spacer) { spacer.remove(); });
    if (listingRow) {
      listingRow.querySelectorAll(':scope > br, :scope > hr').forEach(function (spacer) { spacer.remove(); });
    }
    panel.querySelectorAll(':scope > br, :scope > hr').forEach(function (spacer) { spacer.remove(); });
  }

  function enhanceStandardFormLayout() {
    var content = document.querySelector('.admin-content');
    if (!content || !document.body.classList.contains('admin-form-screen') || content.querySelector('.availability-form-card')) { return; }

    var card = content.querySelector('.admin-card');
    var heading = content.querySelector(':scope > h2');
    if (!card || !heading) { return; }

    document.body.classList.add('admin-standard-form');
    content.classList.add('admin-standard-form');
    card.classList.add('admin-standard-form-card');
    var breadcrumb = content.querySelector('.breadcrumb');
    if (breadcrumb) { breadcrumb.classList.add('admin-standard-form-breadcrumb'); }
    heading.classList.add('admin-standard-form-title');

    var next = heading.nextElementSibling;
    while (next && (next.tagName === 'BR' || next.tagName === 'HR')) {
      var removable = next;
      next = next.nextElementSibling;
      removable.remove();
    }
    if (!content.querySelector('.admin-standard-form-description')) {
      var formDescription = document.createElement('p');
      formDescription.className = 'admin-standard-form-description';
      formDescription.textContent = pageCopy((window.AdminConfig || {}).controllerName, heading.textContent, 'form');
      heading.insertAdjacentElement('afterend', formDescription);
    }

    card.querySelectorAll('label').forEach(function (label) {
      Array.prototype.forEach.call(label.childNodes, function (node) {
        if (node.nodeType === 3) { node.nodeValue = node.nodeValue.replace(/\s*:\s*$/, ''); }
      });
    });

    var form = card.querySelector('form');
    if (!form) { return; }
    form.classList.add('admin-standard-form-grid');
    Array.prototype.forEach.call(form.children, function (child) {
      if (child.tagName === 'HR' || child.classList.contains('row')) {
        child.classList.add('admin-standard-form-span');
        return;
      }
      if (!child.classList.contains('admin-field')) { return; }
      if (child.querySelector('textarea, input[type="file"], select[multiple], .cke, .note-editor')) {
        child.classList.add('admin-standard-form-span');
      }
    });
    var submit = form.querySelector('button[type="submit"], input[type="submit"]');
    if (submit) {
      var entity = (heading.textContent || '').trim().replace(/^(Add|Edit)\s+/i, '');
      if (submit.tagName === 'INPUT') { submit.value = 'Save ' + entity; }
      else if (/^(submit|save)$/i.test((submit.textContent || '').trim())) { submit.textContent = 'Save ' + entity; }
      var actionGroup = submit.closest('.admin-form-actions, .admin-field');
      if (actionGroup) { actionGroup.classList.add('admin-standard-form-actions', 'admin-standard-form-span'); }
    }

    form.querySelectorAll('select').forEach(function (select) {
      if (!/(^|_)status$/i.test(select.name || select.id || '')) { return; }
      select.classList.add('select2');
      select.setAttribute('data-minimum-results-for-search', '-1');
      if ($ && $.fn.select2 && !$(select).data('select2')) {
        $(select).select2({ width: '100%', minimumResultsForSearch: Infinity });
      }
    });
  }

  function initializeImagePreview() {
    var preview = document.getElementById('adminImagePreview');
    var modal = document.getElementById('adminImageModal');
    if (!preview || !modal || !window.bootstrap) { return; }
    document.addEventListener('click', function (event) {
      var link = event.target.closest('a.fancybox');
      if (!link) { return; }
      event.preventDefault();
      preview.src = link.href;
      preview.alt = (link.querySelector('img') && link.querySelector('img').alt) || 'Image preview';
      bootstrap.Modal.getOrCreateInstance(modal).show();
    });
    modal.addEventListener('hidden.bs.modal', function () { preview.removeAttribute('src'); });
  }

  function initializeTooltips() {
    if (!window.bootstrap) { return; }
    document.querySelectorAll('[data-bs-toggle="tooltip"], [data-admin-tooltip="true"]').forEach(function (element) {
      bootstrap.Tooltip.getOrCreateInstance(element);
    });
  }

  function enhancePageStructure() {
    document.querySelectorAll('.breadcrumb > li').forEach(function (item) {
      item.classList.add('breadcrumb-item');
      if (item.classList.contains('active')) { item.setAttribute('aria-current', 'page'); }
    });
    document.querySelectorAll('[data-rel="collapse"]').forEach(function (trigger) {
      trigger.setAttribute('role', 'button');
      trigger.setAttribute('aria-expanded', 'true');
      trigger.addEventListener('click', function (event) {
        event.preventDefault();
        var card = trigger.closest('.card');
        var body = card && card.querySelector(':scope > .card-body');
        if (!body) { return; }
        var hidden = body.classList.toggle('d-none');
        trigger.setAttribute('aria-expanded', hidden ? 'false' : 'true');
      });
    });

    var content = document.querySelector('.admin-content');
    if (!content) { return; }

    var listingTable = content.querySelector('table[id^="table-"], #multiDel table.table');
    var contentForms = content.querySelectorAll('form');
    var contentCards = content.querySelectorAll('.admin-card');
    var pageHeading = content.querySelector(':scope > h1, :scope > h2, :scope > .row h2');
    if (pageHeading) { pageHeading.classList.add('admin-page-title'); }

    document.body.classList.toggle('admin-list-screen', !!listingTable);
    document.body.classList.toggle('admin-form-screen', !listingTable && contentForms.length > 0 && contentCards.length > 0);
    document.body.classList.toggle('admin-report-screen', !!content.querySelector('#report-box'));

    content.querySelectorAll('button, a.btn').forEach(function (action) {
      var text = (action.textContent || '').trim();
      if (/filter resutls/i.test(text)) {
        Array.prototype.forEach.call(action.childNodes, function (node) {
          if (node.nodeType === 3) { node.nodeValue = node.nodeValue.replace(/Filter Resutls/i, 'Filter Results'); }
        });
      }
    });

    var detailTable = content.querySelector('.order-details');
    if (detailTable && detailTable.parentElement) { detailTable.parentElement.classList.add('admin-detail-table'); }
  }

  function addPageDescription() {
    var content = document.querySelector('.admin-content');
    if (!content) { return; }
    var heading = content.querySelector('.pages-listing-title, .admin-standard-form-title, :scope > h1, :scope > h2, :scope > .admin-page-heading h1, :scope > .admin-page-heading h2, :scope > .row h2');
    if (!heading) { return; }
    var next = heading.nextElementSibling;
    if (next && (next.matches('.pages-listing-description, .admin-standard-form-description, .admin-page-description') || (next.tagName === 'P' && next.textContent.trim() !== ''))) { return; }
    var description = document.createElement('p');
    description.className = 'admin-page-description';
    description.textContent = pageCopy((window.AdminConfig || {}).controllerName, heading.textContent, document.body.classList.contains('admin-form-screen') ? 'form' : 'page');
    heading.insertAdjacentElement('afterend', description);
  }

  function initializeServerClock() {
    var clock = document.getElementById('admin-server-time');
    var valueElement = clock && clock.querySelector('.admin-server-time-value');
    if (!clock || !valueElement) { return; }
    var serverTime = parseInt(clock.getAttribute('data-server-time'), 10);
    if (!serverTime) { return; }

    var offset = serverTime - Date.now();
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function pad(value) {
      return value < 10 ? '0' + value : String(value);
    }

    function formatServerTime(date) {
      var hours = date.getHours();
      var period = hours >= 12 ? 'pm' : 'am';
      var hours12 = hours % 12 || 12;
      return months[date.getMonth()] + ' ' + pad(date.getDate()) + ', ' + date.getFullYear() + ' ' + pad(hours12) + ':' + pad(date.getMinutes()) + ' ' + period;
    }

    function tick() {
      valueElement.textContent = formatServerTime(new Date(Date.now() + offset));
    }

    tick();
    setInterval(tick, 15000);
  }

  function labelPrimaryFormActions() {
    var content = document.querySelector('.admin-content');
    if (!content) { return; }
    var heading = content.querySelector('.admin-standard-form-title, :scope > h1, :scope > h2, :scope > .admin-page-heading h1, :scope > .admin-page-heading h2');
    var controllerName = (window.AdminConfig || {}).controllerName;
    var config = adminPageCopy[controllerName] || {};
    var noun = config.noun || ((heading && heading.textContent) || 'Changes').replace(/^(Add|Edit|Upload)\s+/i, '');
    content.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (submit) {
      var label = submit.tagName === 'INPUT' ? submit.value : submit.textContent;
      if (!/^submit$/i.test((label || '').trim())) { return; }
      var actionLabel = /^upload/i.test((heading && heading.textContent) || '') ? 'Save Uploads' : 'Save ' + noun.replace(/^./, function (letter) { return letter.toUpperCase(); });
      if (submit.tagName === 'INPUT') { submit.value = actionLabel; }
      else { submit.textContent = actionLabel; }
    });
  }

  function initializeCopyToClipboard() {
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-copy-value]');
      if (!trigger) { return; }
      event.preventDefault();

      var value = trigger.getAttribute('data-copy-value');
      if (!value) { return; }

      function showCopiedAlert() {
        if (window.Swal) {
          window.Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: trigger.getAttribute('data-copy-message') || 'Code copied to clipboard.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
          });
        }
      }

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(showCopiedAlert);
      } else {
        var textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try { document.execCommand('copy'); showCopiedAlert(); } catch (e) {}
        document.body.removeChild(textarea);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initializeSidebar();
    enhanceForms();
    initializeValidation();
    initializeCkeditorAccordions();
    initializeRequiredSubmitStates();
    initializeDateTimeControls();
    wrapTables();
    enhanceListings();
    initializeImagePreview();
    initializeTooltips();
    enhancePageStructure();
    enhanceStandardListingLayout();
    enhanceStandardFormLayout();
    addPageDescription();
    labelPrimaryFormActions();
    initializeTooltips();
    initializeServerClock();
    initializeCopyToClipboard();
  });
}(window, document, window.jQuery));
