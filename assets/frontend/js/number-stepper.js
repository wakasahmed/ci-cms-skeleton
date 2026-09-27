(function () {
  'use strict';

  document.querySelectorAll('[data-number-stepper]').forEach(function (component) {
    var input = component.querySelector('input[type="number"]');
    var decrease = component.querySelector('[data-number-step="-1"]');
    var increase = component.querySelector('[data-number-step="1"]');
    if (!input || !decrease || !increase) return;

    function refresh() {
      var value = Number(input.value);
      var maximum = Number(input.max);
      decrease.disabled = input.disabled || value <= 1 || maximum < 1;
      increase.disabled = input.disabled || value >= maximum || maximum < 1;
    }

    component.querySelectorAll('[data-number-step]').forEach(function (button) {
      button.addEventListener('click', function () {
        var maximum = Number(input.max);
        if (maximum < 1) return;
        var current = Number(input.value);
        if (!Number.isInteger(current)) current = 1;
        input.value = Math.max(1, Math.min(maximum, current + Number(button.dataset.numberStep)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
    input.addEventListener('input', refresh);
    input.addEventListener('number-stepper:refresh', refresh);
    refresh();
  });
})();
