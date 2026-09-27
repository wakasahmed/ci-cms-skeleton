(function () {
  'use strict';

  var groups = Array.prototype.slice.call(document.querySelectorAll('[data-review-rating]'));

  var success = document.querySelector('[data-review-success]');
  if (success) {
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        var header = document.querySelector('[data-site-header]');
        var headerHeight = header ? header.getBoundingClientRect().height : 0;
        var targetTop = success.getBoundingClientRect().top + window.scrollY - headerHeight - 24;
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        window.scrollTo({
          top: Math.max(0, targetTop),
          behavior: reduceMotion ? 'auto' : 'smooth'
        });
      });
    });
  }

  groups.forEach(function (root) {
    var input = root.querySelector('[data-review-rating-input]');
    var caption = root.querySelector('[data-review-rating-caption]');
    var stars = Array.prototype.slice.call(root.querySelectorAll('[data-review-star]'));
    if (!input || !stars.length) return;

    function value() {
      var selected = parseInt(input.value, 10);
      return selected >= 1 && selected <= 5 ? selected : 0;
    }

    function paint(upTo) {
      stars.forEach(function (star) {
        var active = parseInt(star.getAttribute('data-review-star'), 10) <= upTo;
        var icon = star.querySelector('i');
        star.classList.toggle('text-alam-500', active);
        star.classList.toggle('text-line-strong', !active);
        if (icon) icon.className = (active ? 'fa-solid' : 'fa-regular') + ' fa-star';
      });
    }

    function sync() {
      var selected = value();
      paint(selected);
      stars.forEach(function (star) {
        var starValue = parseInt(star.getAttribute('data-review-star'), 10);
        star.setAttribute('aria-checked', starValue === selected ? 'true' : 'false');
        star.tabIndex = starValue === (selected || 1) ? 0 : -1;
      });
      if (caption) caption.textContent = selected ? selected + ' / 5' : root.getAttribute('data-empty-label') || '';
    }

    function select(selected, focus) {
      input.value = selected;
      root.classList.remove('border-red-300', 'bg-red-50/40');
      var error = root.querySelector('[data-rating-error]');
      if (error) error.remove();
      sync();
      if (focus) stars[selected - 1].focus();
    }

    stars.forEach(function (star) {
      var starValue = parseInt(star.getAttribute('data-review-star'), 10);
      star.addEventListener('mouseenter', function () { paint(starValue); });
      star.addEventListener('focus', function () { paint(starValue); });
      star.addEventListener('click', function () { select(starValue, false); });
    });
    root.addEventListener('mouseleave', function () { paint(value()); });
    root.addEventListener('focusout', function (event) {
      if (!root.contains(event.relatedTarget)) paint(value());
    });
    root.addEventListener('keydown', function (event) {
      var selected = value();
      if (event.key === 'ArrowRight' || event.key === 'ArrowUp') selected = Math.min(5, selected + 1 || 1);
      else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') selected = Math.max(1, selected - 1);
      else if (event.key === 'Home') selected = 1;
      else if (event.key === 'End') selected = 5;
      else return;
      event.preventDefault();
      select(selected, true);
    });
    sync();
  });

  var form = document.querySelector('[data-review-form]');
  if (!form) return;

  var recaptchaScript = document.querySelector('script[data-recaptcha-site-key]');
  var recaptchaToken = form.querySelector('[data-recaptcha-token]');
  var recaptchaSiteKey = recaptchaScript ? recaptchaScript.getAttribute('data-recaptcha-site-key') : '';
  var recaptchaAction = recaptchaScript ? recaptchaScript.getAttribute('data-recaptcha-action') : '';
  var tokenRequested = false;

  form.addEventListener('submit', function (event) {
    if (tokenRequested) {
      event.preventDefault();
      return;
    }
    var firstMissing = groups.find(function (root) {
      return !root.querySelector('[data-review-rating-input]').value;
    });
    if (firstMissing) {
      event.preventDefault();
      firstMissing.classList.add('border-red-300', 'bg-red-50/40');
      var firstStar = firstMissing.querySelector('[data-review-star]');
      if (firstStar) firstStar.focus();
      return;
    }
    var button = form.querySelector('[data-review-submit]');
    var label = form.querySelector('[data-review-submit-label]');
    if (button) button.disabled = true;
    if (label && button) label.textContent = button.getAttribute('data-submitting-label');

    if (
      !recaptchaSiteKey
      || !recaptchaAction
      || !recaptchaToken
      || !window.grecaptcha
      || !window.grecaptcha.enterprise
    ) {
      /* reCAPTCHA did not load (blocked script, offline). Submit without a
         token: the server refuses it and redisplays the form with the
         visitor's ratings and comments preserved, so nothing is lost. */
      return;
    }

    /* The token is single-use and short-lived, so it is requested only once
       the form is otherwise valid and then submitted immediately. */
    event.preventDefault();
    tokenRequested = true;
    window.grecaptcha.enterprise.ready(function () {
      window.grecaptcha.enterprise.execute(recaptchaSiteKey, { action: recaptchaAction })
        .then(function (token) {
          recaptchaToken.value = token;
        })
        .catch(function () {
          recaptchaToken.value = '';
        })
        .then(function () {
          form.submit();
        });
    });
  });
}());
