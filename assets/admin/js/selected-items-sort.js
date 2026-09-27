(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		if (typeof window.Sortable !== 'function' || typeof window.jQuery === 'undefined') return;
		var $ = window.jQuery;

		function optionLabel(select, value) {
			var option = select.querySelector('option[value="' + value + '"]');
			return option ? option.textContent : '';
		}

		function buildItem(value, label) {
			var li = document.createElement('li');
			li.className = 'admin-selected-sort-item';
			li.setAttribute('data-value', value);
			li.innerHTML = '<span class="admin-selected-sort-handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>'
				+ '<span class="admin-selected-sort-label"></span>'
				+ '<button type="button" class="admin-selected-sort-remove"><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
			li.querySelector('.admin-selected-sort-label').textContent = label;
			li.querySelector('.admin-selected-sort-remove').setAttribute('aria-label', 'Remove ' + label);
			return li;
		}

		function reorderSelectOptions(select, list) {
			Array.prototype.forEach.call(list.children, function (li) {
				var option = select.querySelector('option[value="' + li.getAttribute('data-value') + '"]');
				if (option) select.appendChild(option);
			});
		}

		function syncListFromSelect(select, list) {
			var selectedValues = Array.prototype.map.call(select.selectedOptions || [], function (option) { return option.value; });
			var existingValues = Array.prototype.map.call(list.children, function (li) { return li.getAttribute('data-value'); });

			Array.prototype.slice.call(list.children).forEach(function (li) {
				if (selectedValues.indexOf(li.getAttribute('data-value')) === -1) li.remove();
			});

			selectedValues.forEach(function (value) {
				if (existingValues.indexOf(value) === -1) list.appendChild(buildItem(value, optionLabel(select, value)));
			});
		}

		function initSelectSort(select, list) {
			Sortable.create(list, {
				handle: '.admin-selected-sort-handle',
				animation: 150,
				delay: 150,
				delayOnTouchOnly: true,
				touchStartThreshold: 5,
				ghostClass: 'admin-selected-sort-ghost',
				chosenClass: 'admin-selected-sort-chosen',
				onEnd: function () { reorderSelectOptions(select, list); }
			});

			list.addEventListener('click', function (event) {
				var button = event.target.closest('.admin-selected-sort-remove');
				if (!button) return;
				var li = button.closest('.admin-selected-sort-item');
				if (!li) return;
				var option = select.querySelector('option[value="' + li.getAttribute('data-value') + '"]');
				if (option) option.selected = false;
				li.remove();
				$(select).trigger('change');
			});

			$(select).on('change', function () {
				syncListFromSelect(select, list);
				reorderSelectOptions(select, list);
			});
		}

		document.querySelectorAll('select[data-sortable-list]').forEach(function (select) {
			var list = document.getElementById(select.getAttribute('data-sortable-list'));
			if (list) initSelectSort(select, list);
		});
	});
})();
