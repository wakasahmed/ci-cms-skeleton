/*
 * Contact form (/contact): checks the required fields and the email address
 * before sending (the server repeats every check), then adds a reCAPTCHA
 * Enterprise token when a site key is configured and submits the form.
 * Field errors use the reference design's .ci-field-error markup.
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

    function validate($form) {
        var valid = true;

        $form.find('[required]').each(function () {
            var $field = $(this);
            var empty = String($field.val() || '').trim() === '';
            setError($field, empty ? 'This field is required.' : '');
            if (empty) {
                valid = false;
            }
        });

        $form.find('input[type=email]').each(function () {
            var $field = $(this);
            var value = String($field.val() || '').trim();
            if (value !== '' && !EMAIL.test(value)) {
                setError($field, 'That email doesn’t look quite right.');
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
        var $button = $form.find('[data-contact-submit]');
        var label = $button.text();
        var sending = false;

        $form.on('input change', '[aria-invalid="true"]', function () {
            if (String($(this).val() || '').trim() !== '') {
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
            $button.prop('disabled', true).text('Sending…');

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

        // Bring the result of the last submission into view for screen readers too.
        var status = document.getElementById('contact-form-status');
        if (status) {
            status.focus({ preventScroll: true });
        }
    }

    $(function () {
        document.querySelectorAll('[data-contact-form]').forEach(setup);
    });
})(jQuery);
