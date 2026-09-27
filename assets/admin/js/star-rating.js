(function () {
  'use strict';

  document.querySelectorAll('[data-star-rating]').forEach(function (root) {
    var input = root.querySelector('[data-star-input]');
    var group = root.querySelector('[data-star-group]');
    var caption = root.querySelector('[data-star-caption]');
    if (!input || !group) return;

    var stars = Array.prototype.slice.call(group.querySelectorAll('[data-star-value]'));
    if (!stars.length) return;

    var max = parseInt(root.getAttribute('data-star-max'), 10) || stars.length;
    var emptyText = root.getAttribute('data-star-empty-text') || 'No rating selected';

    function currentValue() {
      var value = parseInt(input.value, 10);
      if (isNaN(value) || value < 0) value = 0;
      if (value > max) value = max;
      return value;
    }

    // Paints the stars up to `upTo` without touching the stored value.
    function paint(upTo) {
      stars.forEach(function (star) {
        var value = parseInt(star.getAttribute('data-star-value'), 10);
        var on = value <= upTo;
        star.classList.toggle('is-on', on);
        star.querySelector('i').className = 'bi ' + (on ? 'bi-star-fill' : 'bi-star');
      });
    }

    function syncState() {
      var value = currentValue();
      paint(value);
      stars.forEach(function (star) {
        var starValue = parseInt(star.getAttribute('data-star-value'), 10);
        var selected = starValue === value;
        star.setAttribute('aria-checked', selected ? 'true' : 'false');
        star.tabIndex = (selected || (value === 0 && starValue === 1)) ? 0 : -1;
      });
      if (caption) caption.textContent = value > 0 ? value + ' of ' + max : emptyText;
    }

    function setValue(value, focus) {
      if (value < 0) value = 0;
      if (value > max) value = max;
      input.value = value;
      syncState();
      input.dispatchEvent(new Event('change', { bubbles: true }));
      if (focus && value > 0) stars[value - 1].focus();
    }

    stars.forEach(function (star) {
      var value = parseInt(star.getAttribute('data-star-value'), 10);
      star.addEventListener('mouseenter', function () { paint(value); });
      star.addEventListener('focus', function () { paint(value); });
      star.addEventListener('click', function () { setValue(value, false); });
    });

    group.addEventListener('mouseleave', function () { paint(currentValue()); });
    group.addEventListener('focusout', function (event) {
      if (!group.contains(event.relatedTarget)) paint(currentValue());
    });

    group.addEventListener('keydown', function (event) {
      var value = currentValue();
      if (event.key === 'ArrowRight' || event.key === 'ArrowUp') { setValue(Math.min(max, value + 1) || 1, true); }
      else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') { setValue(Math.max(1, value - 1), true); }
      else if (event.key === 'Home') { setValue(1, true); }
      else if (event.key === 'End') { setValue(max, true); }
      else if (/^[1-9]$/.test(event.key) && parseInt(event.key, 10) <= max) { setValue(parseInt(event.key, 10), true); }
      else return;
      event.preventDefault();
    });

    syncState();
  });
}());
