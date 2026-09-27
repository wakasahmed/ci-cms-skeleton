/*
 * Manage/admin reports: filter form, rows-per-page, commission-paid controls
 * and the printer-friendly view. Loaded only by report pages.
 */
(function (window, document) {
  'use strict';

  var $ = window.jQuery;

  function csrfParams(params) {
    var config = window.AdminConfig || {};
    if (config.csrfName && config.csrfHash) {
      params.append(config.csrfName, config.csrfHash);
    }
    return params;
  }

  function refreshCsrf(data) {
    if (data && data.csrf_name && data.csrf_hash && window.AdminConfig) {
      window.AdminConfig.csrfName = data.csrf_name;
      window.AdminConfig.csrfHash = data.csrf_hash;
    }
  }

  /*
   * Filter form: empty fields are disabled just before submit so they are left
   * out of the URL. Hidden fields (sort, order) always travel with the form.
   */
  function initFilterForm() {
    var form = document.querySelector('[data-report-filter]');
    if (!form) { return; }

    form.addEventListener('submit', function () {
      Array.prototype.forEach.call(form.elements, function (field) {
        if (field.name && field.type !== 'hidden' && field.type !== 'submit' && field.type !== 'button' && field.value === '') {
          field.disabled = true;
        }
      });
    });

    // Coming back with the browser's Back button must not leave fields disabled.
    window.addEventListener('pageshow', function () {
      Array.prototype.forEach.call(form.elements, function (field) { field.disabled = false; });
    });
  }

  /*
   * Rows per page: keep every filter and the sort, go back to the first page.
   */
  function initPerPage() {
    var select = document.getElementById('report_per_page');
    if (!select) { return; }

    function apply() {
      var url = new URL(window.location.href);
      url.searchParams.set('per_page', select.value);
      url.searchParams.delete('page');
      window.location.assign(url.toString());
    }

    // Select2 raises a jQuery change event, which a native listener would miss.
    if ($) { $(select).on('change', apply); } else { select.addEventListener('change', apply); }
  }

  function showAlert(type, message, autoDismissMs) {
    // Same SweetAlert2 toast style used across the admin area.
    if (window.Swal) {
      window.Swal.fire({
        icon: type === 'danger' ? 'error' : type,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: autoDismissMs || 4000,
        timerProgressBar: true
      });
      return;
    }

    var container = document.getElementById('report-alerts');
    if (!container) { return; }

    var alert = document.createElement('div');
    alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    alert.appendChild(document.createTextNode(message));

    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'alert');
    close.setAttribute('aria-label', 'Dismiss notification');
    alert.appendChild(close);

    container.textContent = '';
    container.appendChild(alert);

    if (autoDismissMs) {
      window.setTimeout(function () {
        if (alert.parentNode) { alert.parentNode.removeChild(alert); }
      }, autoDismissMs);
    }
  }

  function updateSummary(summary) {
    if (!summary) { return; }
    Object.keys(summary).forEach(function (key) {
      var target = document.querySelector('[data-summary-key="' + key + '"]');
      if (target) { target.innerHTML = summary[key]; }
    });
  }

  function setGroupBusy(group, busy) {
    group.setAttribute('aria-busy', busy ? 'true' : 'false');
    Array.prototype.forEach.call(group.querySelectorAll('input'), function (input) { input.disabled = busy; });
  }

  function restoreGroup(group, value) {
    Array.prototype.forEach.call(group.querySelectorAll('input[type="radio"]'), function (input) {
      input.checked = input.value === value;
    });
  }

  /*
   * Commission-paid radios. The page is only updated once the server confirms
   * the change; on any failure the previous value is restored.
   */
  function saveCommission(input) {
    var group = input.closest('[data-report-commission]');
    var table = input.closest('[data-report-commission-endpoint]');
    if (!group || !table) { return; }

    var previous = group.getAttribute('data-current');
    var value = input.value;
    if (value === previous) { return; }

    var params = new URLSearchParams();
    params.append('book_id', group.getAttribute('data-book-id'));
    params.append('value', value);
    params.append('filters', window.location.search.replace(/^\?/, ''));
    csrfParams(params);

    setGroupBusy(group, true);

    window.fetch(table.getAttribute('data-report-commission-endpoint'), {
      method: 'POST',
      body: params,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      return response.json().then(function (data) {
        return { ok: response.ok, data: data };
      }, function () {
        return { ok: false, data: null };
      });
    }).then(function (result) {
      refreshCsrf(result.data);
      setGroupBusy(group, false);
      if (result.ok && result.data && result.data.success) {
        group.setAttribute('data-current', value);
        restoreGroup(group, value);
        updateSummary(result.data.summary);
        showAlert('success', 'Saved.', 2500);
        return;
      }
      restoreGroup(group, previous);
      showAlert('danger', (result.data && result.data.message) || 'The change could not be saved. Please try again.');
    }).catch(function () {
      setGroupBusy(group, false);
      restoreGroup(group, previous);
      showAlert('danger', 'The change could not be saved. Check your connection and try again.');
    });
  }

  function initCommissionControls() {
    if (!document.querySelector('[data-report-commission-endpoint]')) { return; }

    document.addEventListener('change', function (event) {
      var input = event.target;
      if (input && input.matches && input.matches('[data-report-commission] input[type="radio"]')) {
        saveCommission(input);
      }
    });
  }

  /*
   * Printer-friendly view: the Print button prints without touching the DOM,
   * and ?autoprint=1 opens the print dialog once the page has loaded.
   */
  function initPrintView() {
    var body = document.body;
    if (!body.classList.contains('report-print-page')) { return; }

    var button = document.querySelector('[data-report-print]');
    if (button) { button.addEventListener('click', function () { window.print(); }); }

    if (body.getAttribute('data-report-autoprint') === '1') {
      window.addEventListener('load', function () { window.setTimeout(window.print, 300); });
    }
  }

  function init() {
    initFilterForm();
    initPerPage();
    initCommissionControls();
    initPrintView();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}(window, document));
