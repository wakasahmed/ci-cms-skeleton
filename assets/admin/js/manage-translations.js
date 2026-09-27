(function () {
    'use strict';

    var statusMeta = {
        MISSING: { label: 'Missing', className: 'translation-status-missing' },
        PENDING: { label: 'Pending', className: 'translation-status-pending' },
        RUNNING: { label: 'Translating', className: 'translation-status-running' },
        SUCCEEDED: { label: 'Ready', className: 'translation-status-succeeded status-enabled' },
        FAILED: { label: 'Failed', className: 'translation-status-failed' }
    };

    function csrfParams() {
        var params = new URLSearchParams();
        if (window.AdminConfig && window.AdminConfig.csrfName && window.AdminConfig.csrfHash) {
            params.append(window.AdminConfig.csrfName, window.AdminConfig.csrfHash);
            return params;
        }
        var token = document.querySelector('meta[name="csrf-token"]');
        var name = document.querySelector('meta[name="csrf-name"]');
        if (token && name) params.append(name.content, token.content);
        return params;
    }

    function refreshCsrf(data) {
        if (!data || !data._csrf || !window.AdminConfig) return;
        window.AdminConfig.csrfName = data._csrf.name;
        window.AdminConfig.csrfHash = data._csrf.hash;
        document.querySelectorAll('input[name="' + data._csrf.name + '"]').forEach(function (input) { input.value = data._csrf.hash; });
    }

    function setFormValue(form, control, value) {
        var field = form.elements[control];
        if (!field) return;
        field.value = value;
        if (window.CKEDITOR && field.id && CKEDITOR.instances[field.id]) {
            form.dataset.translationSync = 'true';
            CKEDITOR.instances[field.id].setData(value, function () { delete form.dataset.translationSync; });
        }
    }

    function initializeLanguageForm() {
        var form = document.querySelector('[data-manage-language-form]');
        if (!form) return;
        var localeControl = form.elements.active_locale || form.elements.locale;
        var isArabicForm = localeControl && localeControl.value === 'ar';

        if (isArabicForm) {
            form.classList.add('manage-form-rtl');
            form.setAttribute('dir', 'rtl');
            form.querySelectorAll('select').forEach(function (select) {
                select.setAttribute('dir', 'rtl');
            });
        }

        var dirty = false;
        var pendingLink = null;
        var modalElement = document.getElementById('manageLanguageModal');
        var modal = modalElement && window.bootstrap ? new bootstrap.Modal(modalElement) : null;
        var redirectInput = form.querySelector('[data-redirect-locale]');

        function markDirty() { dirty = true; form.dataset.dirty = 'true'; }
        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);
        function bindEditor(editor) {
            if (!editor || !editor.element || !form.contains(editor.element.$) || editor._manageTranslationBound) return;
            editor._manageTranslationBound = true;
            editor.on('change', function () { if (form.dataset.translationSync !== 'true') markDirty(); });
        }
        if (window.CKEDITOR) {
            Object.keys(CKEDITOR.instances || {}).forEach(function (name) { bindEditor(CKEDITOR.instances[name]); });
            CKEDITOR.on('instanceReady', function (event) { bindEditor(event.editor); });
        }

        document.querySelectorAll('[data-language-switch]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (link.getAttribute('aria-current') === 'true' || !dirty) return;
                event.preventDefault();
                pendingLink = link;
                if (modal) modal.show();
            });
        });
        var discard = document.querySelector('[data-language-discard]');
        if (discard) discard.addEventListener('click', function () { if (pendingLink) window.location.href = pendingLink.href; });
        var save = document.querySelector('[data-language-save]');
        if (save) save.addEventListener('click', function () {
            if (!pendingLink) return;
            if (redirectInput) redirectInput.value = pendingLink.dataset.locale;
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });
        function setSavingState() {
            form.querySelectorAll('[data-save-button]').forEach(function (button) {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
                var label = button.querySelector('[data-save-label]');
                if (label) label.textContent = 'Saving…';
            });
        }
        form.manageTranslationSetSaving = setSavingState;
        form.addEventListener('submit', function () {
            // Validated forms are locked by admin.js only after validation succeeds.
            if (form.classList.contains('validate')) return;
            setSavingState();
        });

        var translate = document.querySelector('[data-translate-source]');
        if (translate) translate.addEventListener('click', function () {
            if (translate.disabled) return;
            var label = translate.querySelector('[data-translate-label]');
            var feedback = document.querySelector('[data-translation-feedback]');
            var original = label ? label.textContent : '';
            translate.disabled = true;
            if (label) label.textContent = 'Translating…';
            if (feedback) { feedback.classList.remove('visually-hidden'); feedback.textContent = 'Translating English content…'; }
            var params = csrfParams();
            params.append('module', translate.dataset.module);
            params.append('id', translate.dataset.recordId);
            fetch(translate.dataset.url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body: params.toString(), credentials: 'same-origin' })
                .then(function (response) { return response.json().then(function (data) { refreshCsrf(data); if (!response.ok) throw new Error(data.message || 'Translation failed.'); return data; }); })
                .then(function (data) {
                    Object.keys(data.values || {}).forEach(function (control) {
                        setFormValue(form, control, data.values[control]);
                    });
                    dirty = true;
                    form.dataset.dirty = 'true';
                    if (feedback) feedback.textContent = data.message || 'Arabic draft created. Save the form to keep it.';
                })
                .catch(function (error) { if (feedback) feedback.textContent = error.message; })
                .finally(function () { translate.disabled = false; if (label) label.textContent = original; });
        });

        form.manageTranslationIsDirty = function () { return dirty; };
    }

    function updateBadge(badge, status) {
        var meta = statusMeta[status] || statusMeta.MISSING;
        Object.keys(statusMeta).forEach(function (key) {
            statusMeta[key].className.split(' ').forEach(function (className) { badge.classList.remove(className); });
        });
        meta.className.split(' ').forEach(function (className) { badge.classList.add(className); });
        badge.dataset.status = status;
        badge.textContent = meta.label;
    }

    function initializePolling() {
        var root = document.querySelector('[data-translation-poll]');
        if (!root) return;
        var timer = null;
        var stopped = false;
        var interval = Math.max(2, parseInt(root.dataset.pollSeconds || '4', 10)) * 1000;

        function activeBadges() {
            return Array.prototype.slice.call(root.querySelectorAll('[data-translation-badge]')).filter(function (badge) {
                return badge.dataset.status === 'PENDING' || badge.dataset.status === 'RUNNING';
            });
        }

        function schedule() {
            if (!stopped && activeBadges().length) timer = window.setTimeout(poll, interval);
        }

        function poll() {
            var badges = activeBadges();
            if (!badges.length || stopped) return;
            var params = csrfParams();
            params.append('module', root.dataset.module);
            badges.forEach(function (badge) { params.append('ids[]', badge.dataset.recordId); });
            var form = document.querySelector('[data-manage-language-form]');
            if (form && root.dataset.locale === 'ar') params.append('include_values', '1');
            fetch(root.dataset.statusUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body: params.toString(), credentials: 'same-origin' })
                .then(function (response) { return response.json().then(function (data) { refreshCsrf(data); if (!response.ok) throw new Error('Status request failed.'); return data; }); })
                .then(function (data) {
                    badges.forEach(function (badge) {
                        if (!data.states || !data.states[badge.dataset.recordId]) updateBadge(badge, 'MISSING');
                    });
                    Object.keys(data.states || {}).forEach(function (id) {
                        var badge = root.querySelector('[data-translation-badge][data-record-id="' + id + '"]');
                        var state = data.states[id];
                        if (badge) updateBadge(badge, state.status);
                        if (form && root.dataset.locale === 'ar' && state.status === 'SUCCEEDED' && form.dataset.dirty !== 'true' && state.values) {
                            Object.keys(state.values).forEach(function (control) {
                                setFormValue(form, control, state.values[control]);
                            });
                        }
                    });
                })
                .catch(function () { stopped = true; })
                .finally(schedule);
        }

        window.addEventListener('beforeunload', function () { stopped = true; if (timer) window.clearTimeout(timer); });
        schedule();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeLanguageForm();
        initializePolling();
    });
}());
