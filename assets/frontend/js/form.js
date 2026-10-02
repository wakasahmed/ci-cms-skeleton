/*
 * Public forms ([data-public-form]: contact, account pages): checks the
 * required fields, email addresses, minimum lengths and matching fields
 * ([data-match="#other"]) before sending (the server repeats every check),
 * then adds a reCAPTCHA Enterprise token when the form has a site key
 * (data-recaptcha-site-key / data-recaptcha-action) and submits it.
 * The submit button ([data-form-submit]) shows its data-busy-label while
 * sending. Field errors use the reference design's .ci-field-error markup.
 */
(function ($) {
    'use strict';

    var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function setError($field, message) {
        var errorId = $field.attr('id') + '-error';
        var describedBy = String($field.attr('aria-describedby') || '')
            .split(' ')
            .filter(function (id) {
                return id !== '' && id !== errorId;
            });

        $field.siblings('.ci-field-error').remove();
        $field
            .toggleClass('border-destructive', Boolean(message))
            .toggleClass('border-border-strong', !message)
            .attr('aria-invalid', message ? 'true' : 'false');

        if (message) {
            $('<p class="ci-field-error mt-1 text-sm text-destructive"></p>')
                .attr('id', errorId)
                .text(message)
                .insertAfter($field);
            describedBy.push(errorId);
        }

        if (describedBy.length) {
            $field.attr('aria-describedby', describedBy.join(' '));
        } else {
            $field.removeAttr('aria-describedby');
        }
    }

    function problem($field) {
        var value = String($field.val() || '');
        var trimmed = value.trim();
        var minLength = parseInt($field.attr('minlength'), 10);
        var match = $field.data('match');

        if ($field.prop('required') && trimmed === '') {
            return 'This field is required.';
        }
        if (trimmed !== '' && $field.attr('type') === 'email' && !EMAIL.test(trimmed)) {
            return 'That email doesn’t look quite right.';
        }
        if (value !== '' && minLength > 0 && value.length < minLength) {
            return 'Use at least ' + minLength + ' characters.';
        }
        if (match && value !== String($(match).val() || '')) {
            return 'The two passwords do not match.';
        }

        return '';
    }

    function validate($form) {
        var valid = true;

        $form.find('input, select, textarea').not('[type=hidden]').each(function () {
            var $field = $(this);
            var message = problem($field);
            setError($field, message);
            if (message) {
                valid = false;
            }
        });

        return valid;
    }

    function recaptchaToken(siteKey, action) {
        return new Promise(function (resolve, reject) {
            if (!siteKey) {
                resolve('');
                return;
            }
            if (!window.grecaptcha || !window.grecaptcha.enterprise) {
                reject(new Error('reCAPTCHA did not load.'));
                return;
            }
            window.grecaptcha.enterprise.ready(function () {
                window.grecaptcha.enterprise.execute(siteKey, { action: action }).then(resolve, reject);
            });
        });
    }

    function setup(form) {
        var $form = $(form);
        var $button = $form.find('[data-form-submit]');
        var label = $button.text();
        var sending = false;

        $form.on('input change', '[aria-invalid="true"]', function () {
            if (!problem($(this))) {
                setError($(this), '');
            }
        });

        $form.on('submit', function (event) {
            if (sending) {
                return;
            }
            event.preventDefault();

            if (!validate($form)) {
                $form.find('[aria-invalid="true"]').first().trigger('focus');
                return;
            }

            sending = true;
            $button.prop('disabled', true).text($button.data('busy-label') || 'Sending…');

            recaptchaToken($form.data('recaptcha-site-key'), $form.data('recaptcha-action'))
                .then(function (token) {
                    $form.find('[data-recaptcha-token]').val(token);
                    form.submit();
                })
                .catch(function () {
                    sending = false;
                    $button.prop('disabled', false).text(label);
                    window.alert('The form could not be checked. Please try again, or call us.');
                });
        });
    }

    $(function () {
        document.querySelectorAll('[data-public-form]').forEach(setup);

        // Bring the result of the last submission into view for screen readers too.
        var status = document.querySelector('[data-form-status]');
        if (status) {
            status.focus({ preventScroll: true });
        }
    });
})(jQuery);
