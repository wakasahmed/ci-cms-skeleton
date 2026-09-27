/*
 * Admin colour picker.
 *
 * Binds Coloris to every .color-picker input and keeps the stored value in a
 * single canonical shape: rgba(r, g, b, a). Coloris is configured with
 * forceAlpha so it emits rgba() even at full opacity, and normaliseRgba()
 * covers values typed by hand or left over from an older format.
 */
(function (window, document) {
	'use strict';

	if (typeof window.Coloris !== 'function') {
		return;
	}

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function normaliseRgba(value) {
		var text = String(value == null ? '' : value).trim();
		if (text === '') {
			return '';
		}

		var hex = text.match(/^#([0-9a-f]{3,8})$/i);
		if (hex) {
			var digits = hex[1];
			if (digits.length === 3 || digits.length === 4) {
				digits = digits.replace(/./g, function (c) { return c + c; });
			}
			if (digits.length !== 6 && digits.length !== 8) {
				return '';
			}
			var alpha = digits.length === 8 ? parseInt(digits.slice(6, 8), 16) / 255 : 1;
			return 'rgba(' + parseInt(digits.slice(0, 2), 16) + ', ' +
				parseInt(digits.slice(2, 4), 16) + ', ' +
				parseInt(digits.slice(4, 6), 16) + ', ' +
				Math.round(alpha * 100) / 100 + ')';
		}

		var parts = text.match(/^rgba?\(([^)]+)\)$/i);
		if (!parts) {
			return '';
		}
		var pieces = parts[1].split(/[,\s/]+/).filter(function (piece) { return piece !== ''; });
		if (pieces.length < 3) {
			return '';
		}
		var channels = pieces.slice(0, 3).map(function (piece) {
			return clamp(Math.round(parseFloat(piece) || 0), 0, 255);
		});
		var a = pieces.length > 3 ? parseFloat(pieces[3]) : 1;
		if (isNaN(a)) {
			a = 1;
		}
		a = Math.round(clamp(a, 0, 1) * 100) / 100;

		return 'rgba(' + channels.join(', ') + ', ' + a + ')';
	}

	window.Coloris({
		el: '.color-picker',
		themeMode: 'light',
		alpha: true,
		forceAlpha: true,
		format: 'rgb',
		formatToggle: false,
		clearButton: true,
		clearLabel: 'Clear',
		swatches: [
			'rgba(0, 0, 0, 0.5)',
			'rgba(0, 0, 0, 1)',
			'rgba(255, 255, 255, 1)',
			'rgba(33, 37, 41, 1)',
			'rgba(13, 110, 253, 1)',
			'rgba(25, 135, 84, 1)',
			'rgba(220, 53, 69, 1)',
			'rgba(255, 193, 7, 1)'
		]
	});

	// Normalise anything the field ends up holding, whether it came from the
	// picker, a paste, or manual typing.
	document.addEventListener('change', function (event) {
		var field = event.target;
		if (!field || !field.classList || !field.classList.contains('color-picker')) {
			return;
		}
		var normalised = normaliseRgba(field.value);
		if (normalised !== field.value) {
			field.value = normalised;
			field.dispatchEvent(new Event('input', { bubbles: true }));
		}
	}, true);
}(window, document));
