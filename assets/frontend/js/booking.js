/**
 * Alam Al-Munawara — booking wizard.
 *
 * Database assignments and encrypted entity references come from the controller.
 * Visiting times and Tour guide availability come from the database. Bookings are saved after server validation.
 */
(function () {
  'use strict';

  var form = document.getElementById('booking-form');
  var raw = document.getElementById('alam-booking-data');
  if (!form || !raw) return;

  var DATA;
  try {
    DATA = JSON.parse(raw.textContent);
  } catch (err) {
    return;
  }

  function field(name) {
    return form.querySelector('[name="' + name + '"]');
  }

  function bookingFormData() {
    var payload = new FormData();
    $$('input, select, textarea', form).forEach(function (control) {
      if (!control.name || control.disabled) return;
      if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
      payload.append(control.name, control.value);
    });
    return payload;
  }

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var i18n = window.FrontendI18n;
  /* The step labels come from the same catalogue the progress pills were
     rendered from, so the announcement and the visible pill never disagree. */
  var STEP_LABELS = DATA.stepLabels || (i18n.config && i18n.config.stepLabels) || [];
  var TOTAL_STEPS = STEP_LABELS.length || 6;

  /* =====================================================================
   * State — one plain object, mirrored into real form controls
   * ================================================================== */
  var completedSteps = {};
  var detailsCompleted = false;
  var paymentLocked = false;
  var reviewedTotal = null;
  /* Frozen breakdown for the payment/confirmation steps, where estimatedTotal()
     returns the already-discounted reviewedTotal (so recomputing "original minus
     discount" from it would be wrong). Populated from every result that carries
     these fields (see syncPromoFromResult()), so it always reflects the last
     accepted Review. */
  var reviewedOriginalTotal = null;
  var reviewedDiscountAmount = 0;

  /* Promo code — applied/removed through its own endpoint (bookingPromo()),
     never through a step save. Kept in sync from every saveBookingStep()
     response (see syncPromoFromResult()) so guest/vehicle/slot changes
     always reprice an already-applied code. */
  var promoCode = '';
  var promoType = '';
  var promoValue = 0;
  var promoEligible = true;
  var promoIncomplete = false;
  var promoBusy = false;
  var promoRequestSeq = 0;

  var state = {
    step: 1,
    stepsCompleted: 0,
    tour: DATA.preselect.tour || '',
    vehicle: DATA.preselect.vehicle || '',
    language: DATA.preselect.language || '',
    date: DATA.preselect.date || '',
    slot: '',
    guests: Number(field('guests').value),
    guide: ''
  };

  /* =====================================================================
   * Helpers
   * ================================================================== */
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }
  function find(list, key, value) {
    for (var i = 0; i < list.length; i++) {
      if (list[i][key] === value) return list[i];
    }
    return null;
  }
  function refreshGuestLimit() {
    var tour = tourBySlug(state.tour);
    field('guests').max = tour ? tour.capacity : 0;
    field('guests').dispatchEvent(new Event('number-stepper:refresh'));
  }
  function tourBySlug(slug) { return find(DATA.tours, 'slug', slug); }
  function vehicleById(id) { return find(DATA.vehicles, 'id', id); }
  function slotById(id) { return find(DATA.slots, 'id', id); }
  function guideById(id) { return find(DATA.guides, 'id', id); }

  function languageLabel(id) {
    var l = find(DATA.languages, 'id', id);
    return l ? l.label : '';
  }

  /* The visible telephone control has a hidden E.164 twin named mobile. */
  var phoneInput = $('input.js-phone-input');

  function phoneDisplay() {
    if (phoneInput && window.FrontendPhone) {
      var readable = window.FrontendPhone.display(phoneInput);
      if (readable) return readable;
    }
    return field('mobile') ? field('mobile').value.trim() : '';
  }

  /* Rendered through the same `format.price` template as PHP's price(),
     so "SAR 450" and "450 ريال" are produced by one rule rather than two. */
  function money(amount) {
    return i18n.price(amount);
  }

  function toISO(d) {
    var m = String(d.getMonth() + 1);
    var day = String(d.getDate());
    return d.getFullYear() + '-' + (m.length < 2 ? '0' + m : m) + '-' + (day.length < 2 ? '0' + day : day);
  }

  function fromISO(iso) {
    var p = String(iso).split('-');
    if (p.length !== 3) return null;
    var d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
    return isNaN(d.getTime()) ? null : d;
  }

  /* Display only. The value POSTED in `tour_date` stays the Y-m-d string the
     hidden input holds — see the onChange handler on the inline calendar. */
  function formatDate(iso) {
    return iso ? i18n.date(iso) : '';
  }

  function startOfToday() {
    return fromISO(DATA.serverToday);
  }

  /* =====================================================================
   * Guide eligibility — enabled assignments, language and date/slot availability
   * ================================================================== */
  function guideMatches(guide, criteria) {
    if (!guide.active) return false;
    if (criteria.tour && guide.tours.indexOf(criteria.tour) === -1) return false;
    if (criteria.language && guide.languages.indexOf(criteria.language) === -1) return false;
    if (criteria.date && criteria.slot) {
      var slots = DATA.availability[criteria.date] || {};
      if (!slots[criteria.slot] || slots[criteria.slot].indexOf(guide.id) === -1) return false;
    }
    return true;
  }

  function matchingGuides(criteria) {
    return DATA.guides.filter(function (g) { return guideMatches(g, criteria); });
  }

  function criteria(extra) {
    var c = {
      tour: state.tour,
      language: state.language,
      vehicle: state.vehicle,
      date: state.date,
      slot: state.slot
    };
    if (extra) {
      for (var k in extra) {
        if (Object.prototype.hasOwnProperty.call(extra, k)) c[k] = extra[k];
      }
    }
    return c;
  }

  /** Is any guide free on this date, for any slot? */
  function dateHasAvailability(iso) {
    if (iso < DATA.minimumDate || iso > DATA.maximumDate) return false;
    return isExperience() || Object.keys(DATA.availability[iso] || {}).length > 0;
  }

  /* =====================================================================
   * Pricing
   * ================================================================== */
  /* Mirrors Tour_pricing::percentAmount(): whole currency units, rounded once. */
  function percentAmount(amount, percent) {
    return Math.max(0, Math.round(amount * (Number(percent) || 0) / 100));
  }

  function withTax(amount) {
    return amount + percentAmount(amount, DATA.pricing.tax);
  }

  /* Mirrors Booking_model::save(): tour price and meals are per person, guide and
     vehicle per booking, then profit is added. This pre-tax amount is what a
     discount applies to; customers only ever see it with tax included. */
  function priceBeforeTax() {
    var slot = slotById(state.slot);
    if (!slot) return null;
    var hours = slot.hours;
    var guests = state.guests || 0;
    var language = find(DATA.languages, 'id', state.language);
    var guidePrices = !language || language.isArabic ? DATA.pricing.arabicGuide : DATA.pricing.englishGuide;
    var subtotal = DATA.pricing.tour[hours] * guests + (isExperience() ? 0 : guidePrices[hours]);
    var vehicle = vehicleById(state.vehicle);
    if (vehicle) subtotal += vehicle.prices[hours] + vehicle.meals[hours] * guests;
    return subtotal + percentAmount(subtotal, DATA.pricing.profit);
  }

  /** The tax-inclusive total before any discount. */
  function estimatedTotal() {
    if (paymentLocked && reviewedTotal !== null) return reviewedTotal;
    var base = priceBeforeTax();
    return base === null ? null : withTax(base);
  }

  /* Mirrors Booking_model::calculateDiscount() so the live preview never
     disagrees with what the next step save will persist. The server is
     still the authority: every step save and every promo apply/remove
     response refreshes promoType/promoValue/promoEligible from it.
     `base` is the pre-tax priceBeforeTax(). */
  function promoDiscountAmount(base) {
    if (!promoCode || !promoEligible || promoIncomplete || base === null) return 0;
    var raw = promoType === 'Percentage' ? (base * promoValue / 100) : promoValue;
    return Math.min(Math.max(Math.round(raw), 0), base);
  }

  /** The payable total: tax on the pre-tax price minus the live discount, once locked the server's frozen total. */
  function payableTotal() {
    if (paymentLocked && reviewedTotal !== null) return reviewedTotal;
    var base = priceBeforeTax();
    if (base === null) return null;
    return withTax(base - promoDiscountAmount(base));
  }

  /* =====================================================================
   * Summary — every [data-summary="key"] node is kept in sync
   * ================================================================== */
  function setSummary(key, value) {
    $$('[data-summary="' + key + '"]').forEach(function (node) {
      node.textContent = value;
    });
  }

  function setPromoStatus(text, tone) {
    var el = $('[data-promo-status]');
    if (!el) return;
    el.textContent = text || '';
    el.classList.toggle('text-red-600', tone === 'error');
    el.classList.toggle('text-alam-700', tone === 'success');
    el.classList.toggle('text-ink-muted', tone !== 'error' && tone !== 'success');
  }

  function promoDetailText() {
    if (!promoType) return '';
    return promoType === 'Percentage' ? i18n.number(promoValue) + '%' : money(promoValue);
  }

  function renderPromoWidget() {
    var section = $('[data-promo-section]');
    if (!section) return;
    var formWrap = $('[data-promo-form]', section);
    var applied = $('[data-promo-applied]', section);
    var input = $('[data-promo-input]', section);
    var applyBtn = $('[data-promo-apply]', section);
    var removeBtn = $('[data-promo-remove]', section);
    var appliedCode = $('[data-promo-applied-code]', section);
    var appliedDetail = $('[data-promo-applied-detail]', section);
    var hasCode = promoCode !== '';

    if (formWrap) formWrap.classList.toggle('hidden', hasCode);
    if (applied) applied.classList.toggle('hidden', !hasCode);
    if (hasCode) {
      var detail = promoDetailText();
      if (appliedCode) appliedCode.textContent = detail ? promoCode + ' · ' + detail : promoCode;
      if (appliedDetail) {
        appliedDetail.textContent = promoIncomplete
          ? i18n.t('book.promo.incomplete')
          : (!promoEligible ? i18n.t('book.promo.ineligibleBadge') : '');
      }
    }
    var busy = promoBusy || savingBooking;
    if (input) input.disabled = busy;
    if (applyBtn) applyBtn.disabled = busy;
    if (removeBtn) removeBtn.disabled = busy;
  }

  /** Populates promo state from any saveBookingStep()/bookingPromo() JSON response. */
  function syncPromoFromResult(result) {
    if (!result || !('discount_code' in result)) return;
    if ('original_total' in result) {
      var original = Number(result.original_total);
      reviewedOriginalTotal = isFinite(original) ? original : null;
      reviewedDiscountAmount = Number(result.discount_amount) || 0;
    }
    var code = result.discount_code || '';
    if (code === '') {
      promoCode = '';
      promoType = '';
      promoValue = 0;
      promoIncomplete = false;
      promoEligible = true;
      return;
    }
    promoCode = code;
    promoType = result.discount_type || '';
    promoValue = Number(result.discount_value) || 0;
    promoIncomplete = !!result.discount_incomplete;
    promoEligible = result.discount_eligible !== false;
  }

  function updateSummary() {
    var tour = tourBySlug(state.tour);
    var vehicle = vehicleById(state.vehicle);
    var slot = slotById(state.slot);

    var none = i18n.t('book.notSelected');
    var sep = i18n.t('format.listSeparator');

    setSummary('tour', tour ? tour.title : none);
    setSummary('vehicle', vehicle ? vehicle.label + ' · ' + i18n.tn('count.upTo', vehicle.capacity) : none);
    setSummary('language', languageLabel(state.language) || none);
    setSummary('date', state.date ? formatDate(state.date) : none);
    setSummary('slot', slot ? slot.label + sep + slot.time : none);

    var selectedGuide = guideById(state.guide);
    var guideName = selectedGuide ? selectedGuide.name : none;
    setSummary('guide', guideName);
    setSummary('guests', state.guests ? i18n.number(state.guests) : none);
    var guideReview = $('[data-guide-review]');
    if (guideReview) guideReview.classList.toggle('hidden', isExperience());
    $$('[data-summary="guide"], [data-summary="language"]').forEach(function (node) {
      var row = node.closest('.summary-row') || node.parentElement;
      row.classList.toggle('hidden', isExperience());
    });
    $$('[data-step-pill="4"]').forEach(function (pill) {
      pill.parentElement.classList.toggle('hidden', isExperience());
    });

    // Once Review is accepted, estimatedTotal() returns the already-discounted
    // reviewedTotal, so the breakdown must come from the frozen snapshot instead
    // of recomputing "original minus discount" against an already-net figure.
    // Both breakdowns are tax-inclusive, matching Tour_pricing::customerAmounts().
    var base, discount;
    var total = payableTotal();
    if (paymentLocked && reviewedTotal !== null) {
      base = reviewedOriginalTotal !== null ? reviewedOriginalTotal : reviewedTotal;
      discount = reviewedDiscountAmount;
    } else {
      base = estimatedTotal();
      discount = base === null || total === null ? 0 : Math.max(0, base - total);
    }
    setSummary('total', total === null ? '—' : money(total));
    setSummary('total-row', total === null ? '—' : money(total));
    setSummary('original_total', base === null ? '—' : money(base));
    setSummary('discount_amount', discount > 0 ? '−' + money(discount) : '—');
    var showBreakdown = discount > 0;
    $$('[data-summary-row="original"]').forEach(function (row) { row.classList.toggle('hidden', !showBreakdown); });
    $$('[data-summary-row="discount"]').forEach(function (row) { row.classList.toggle('hidden', !showBreakdown); });
    renderPromoWidget();

    // Customer details (step 5 review)
    setSummary('full_name', field('full_name').value.trim() || '—');
    setSummary('email', field('email').value.trim() || '—');
    setSummary('country', field('country').value || '—');
    setSummary('mobile', phoneDisplay() || '—');
    setSummary('pickup_location', field('pickup_location').value.trim() || '—');

    var notes = field('notes').value.trim();
    var notesRow = $('[data-notes-row]');
    if (notesRow) {
      notesRow.classList.toggle('hidden', notes === '');
      if (notes) setSummary('notes', notes);
    }

    // Display-only values; the server independently calculates booking prices.
    var hidden = $('[data-hidden="guide_name"]');
    if (hidden) hidden.value = guideName;
    var hiddenTotal = $('[data-hidden="estimated_total"]');
    if (hiddenTotal) hiddenTotal.value = total === null ? '' : String(total);
  }

  /* =====================================================================
   * Step 3 — vehicle and language
   * ================================================================== */
  function isExperience() {
    var tour = tourBySlug(state.tour);
    return !!(tour && tour.isExperience);
  }

  function checkCapacity(selectFirstEligible) {
    var count = 0;
    var firstEligible = null;
    var keepPrefilled = false;
    $$('[data-vehicle-option]').forEach(function (card) {
      var input = card.querySelector('input');
      var vehicle = vehicleById(input.value);
      var eligible = vehicle && vehicle.tour === state.tour && Number.isSafeInteger(state.guests) && state.guests > 0 && vehicle.capacity >= state.guests;
      card.classList.toggle('hidden', !eligible);
      input.disabled = !eligible;
      if (eligible) {
        count++;
        firstEligible = firstEligible || input;
        // A vehicle chosen in the homepage search survives guest-count changes while it still fits.
        if (input.value === state.vehicle && input.value === DATA.preselect.vehicle) keepPrefilled = true;
      }
      if (!eligible && input.checked) {
        input.checked = false;
        state.vehicle = '';
      }
    });
    if (firstEligible && ((selectFirstEligible && !keepPrefilled) || !state.vehicle)) {
      firstEligible.checked = true;
      state.vehicle = firstEligible.value;
    }
    var empty = $('[data-vehicle-empty]');
    if (empty) empty.classList.toggle('hidden', count > 0);
    var warning = $('[data-capacity-warning]');
    if (warning) warning.classList.add('hidden');
  }

  var languageSelect = $('#preferred-language');
  function refreshLanguages() {
    var ids = [];
    DATA.guides.forEach(function (guide) {
      if (guide.tours.indexOf(state.tour) === -1) return;
      guide.languages.forEach(function (id) {
        if (ids.indexOf(id) === -1) ids.push(id);
      });
    });
    var previous = state.language;
    languageSelect.innerHTML = '';
    DATA.languages.forEach(function (language) {
      if (ids.indexOf(language.id) === -1) return;
      languageSelect.add(new Option(language.label, language.id));
    });
    state.language = ids.indexOf(previous) !== -1 ? previous : (ids.indexOf(DATA.arabic) !== -1 ? DATA.arabic : (ids[0] || ''));
    languageSelect.value = state.language;
    languageSelect.disabled = isExperience();
    $('[data-language-section]').classList.toggle('hidden', isExperience());
    if (window.jQuery) window.jQuery(languageSelect).trigger('change.select2');
  }
  languageSelect.addEventListener('change', function () {
    state.language = languageSelect.value;
    refreshDependents();
  });
  field('guests').addEventListener('input', function () {
    state.guests = Number(field('guests').value);
    checkCapacity(state.step === 1 && !detailsCompleted);
    hideError('guests');
    refreshDependents();
  });

  $$('input[name="vehicle_id"]').forEach(function (input) {
    input.addEventListener('change', function () {
      state.vehicle = input.value;
      hideError('vehicle');
      checkCapacity();
      refreshDependents();
    });
  });

  $$('input[name="language"]').forEach(function (input) {
    input.addEventListener('change', function () {
      state.language = input.value;
      refreshDependents();
    });
  });

  /* =====================================================================
   * Step 2 — calendar + visiting slots
   * ================================================================== */
  /* The calendar is Flatpickr in inline mode, built through the shared
   * window.FrontendDatepicker configuration in js/datepicker.js — so the wizard,
   * the homepage and Plan Your Visit all draw the same control. Availability
   * comes from the server through `dateHasAvailability()`, handed to Flatpickr
   * as a `disable` predicate. The hidden `tour_date` input keeps the Y-m-d value it always had. */
  var calHost = $('[data-cal-inline]');
  var calEmpty = $('[data-cal-empty]');
  var dateInput = $('[data-date-input]');
  var picker = null;

  /** Months the calendar may reach: this month through eleven months ahead. */
  function calendarMaxDate() {
    return fromISO(DATA.maximumDate);
  }

  /** True when no day of the month currently on screen can be booked. */
  function monthHasNoOpenDays(inst) {
    var today = startOfToday();
    var days = new Date(inst.currentYear, inst.currentMonth + 1, 0).getDate();
    for (var day = 1; day <= days; day++) {
      var cell = new Date(inst.currentYear, inst.currentMonth, day);
      if (cell >= today && dateHasAvailability(toISO(cell))) return false;
    }
    return true;
  }

  function syncCalendarEmpty() {
    if (!calEmpty || !picker) return;
    calEmpty.classList.toggle('hidden', !monthHasNoOpenDays(picker));
  }

  /** Redraw after an upstream choice (tour, vehicle, language) changed. */
  function renderCalendar() {
    if (!picker) return;
    picker.redraw();
    syncCalendarEmpty();
  }

  if (calHost && window.FrontendDatepicker) {
    picker = window.FrontendDatepicker.create(calHost, {
      inline: true,
      altInput: false,
      defaultDate: state.date || null,
      minDate: DATA.minimumDate,
      maxDate: calendarMaxDate(),
      disable: [function (date) {
        return !dateHasAvailability(toISO(date));
      }],
      onChange: function (dates) {
        state.date = dates.length ? toISO(dates[0]) : '';
        delete completedSteps[2];
        refreshSummaryEdits();
        if (dateInput) dateInput.value = state.date;
        hideError('date');
        syncCalendarEmpty();
        renderSlots();
        renderGuides();
      },
      onMonthChange: syncCalendarEmpty,
      onYearChange: syncCalendarEmpty,
      onReady: function (dates, str, inst) {
        inst.calendarContainer.classList.add('alam-flatpickr');
        syncCalendarEmpty();
      }
    });
  }

  /** Put the wizard's stored date back on the calendar (Back, or Edit from Review). */
  function syncCalendarValue() {
    if (!picker) return;
    picker.setDate(state.date || null, false);
    syncCalendarEmpty();
  }

  function renderSlots() {
    var context = $('[data-slot-context]');
    var emptyBox = $('[data-slot-empty]');
    var openCount = 0;

    DATA.slots.forEach(function (slot) {
      var label = $('[data-slot-option="' + slot.id + '"]');
      if (!label) return;
      var input = label.querySelector('input');
      var note = label.querySelector('[data-slot-note]');

      var open = false;
      if (state.date) {
        open = dateHasAvailability(state.date) && (isExperience() || !!(DATA.availability[state.date] || {})[slot.id]);
      }

      input.disabled = !open;
      label.classList.toggle('hidden', !isExperience() && !!state.date && !open);
      label.classList.toggle('cursor-not-allowed', !open);
      label.classList.toggle('opacity-55', !open);

      if (note) {
        if (!state.date) {
          note.textContent = slot.note;
        } else {
          note.textContent = open ? slot.note : i18n.t('book.slot.unavailable');
        }
      }

      if (open) {
        openCount++;
      } else if (input.checked) {
        input.checked = false;
        state.slot = '';
      }
    });

    if (context) {
      /* One counted message rather than an English "is/are" switch: Arabic
         needs six plural forms where English needs two, and both come from
         the same catalogue entry. */
      context.textContent = !state.date
        ? i18n.t('book.slot.chooseDate')
        : (openCount > 0
          ? i18n.tn('count.slotsOpen', openCount, { date: formatDate(state.date) })
          : i18n.t('book.slot.noneOpen', { date: formatDate(state.date) }));
    }
    if (emptyBox) emptyBox.classList.toggle('hidden', DATA.slots.length > 0 && (!state.date || openCount > 0));
  }

  $$('input[name="slot_id"]').forEach(function (input) {
    input.addEventListener('change', function () {
      state.slot = input.value;
      delete completedSteps[2];
      refreshSummaryEdits();
      hideError('slot');
      renderGuides();
    });
  });

  /* =====================================================================
   * Step 4 — guide selection
   * ================================================================== */
  var guideList = $('[data-guide-list]');
  var guideEmpty = $('[data-guide-empty]');
  var guideCount = $('[data-guide-count]');

  var guideTemplate = document.getElementById('booking-guide-card');

  function guideCard(guide) {
    var card = guideTemplate.content.firstElementChild.cloneNode(true);
    card.dataset.guideCard = guide.id;
    var input = card.querySelector('input');
    input.value = guide.id;
    input.checked = guide.id === state.guide;
    card.querySelector('[data-guide-name]').textContent = guide.name;
    var role = card.querySelector('[data-guide-role]');
    role.textContent = guide.role || '';
    role.classList.toggle('hidden', !guide.role);
    card.querySelector('[data-guide-bio]').textContent = guide.bio;
    card.querySelector('[data-guide-languages]').textContent = guide.languages.map(languageLabel).join(i18n.t('format.listSeparator'));
    var image = card.querySelector('[data-guide-image]');
    if (guide.image) image.src = guide.image;
    else image.remove();
    return card;
  }

  function renderGuides() {
    if (!guideList || !guideTemplate) return;

    var matches = isExperience() ? [] : matchingGuides(criteria());
    if (!find(matches, 'id', state.guide)) {
      state.guide = '';
      delete completedSteps[4];
    }
    guideList.replaceChildren();
    matches.forEach(function (guide) { guideList.appendChild(guideCard(guide)); });

    if (guideCount) {
      guideCount.textContent = matches.length === 0
        ? i18n.t('book.guide.none')
        : i18n.tn('count.guideMatches', matches.length);
    }
    if (guideEmpty) guideEmpty.classList.toggle('hidden', matches.length > 0);
    updateSummary();
  }

  if (guideList) {
    guideList.addEventListener('change', function (event) {
      if (event.target.name !== 'guide_id') return;
      if (savingBooking || savedBooking) {
        renderGuides();
        return;
      }
      state.guide = event.target.value;
      hideError('guide');
      updateSummary();
    });
  }

  /* =====================================================================
   * Cross-step refresh when an upstream choice changes
   * ================================================================== */
  function refreshDependents() {
    renderCalendar();
    syncCalendarValue();
    renderSlots();
    renderGuides();
    updateSummary();
  }

  /* =====================================================================
   * Validation
   * ================================================================== */
  function bookingAlert(icon, title, text, focusTarget) {
    if (!window.Swal) return;
    window.Swal.fire({
      icon: icon,
      titleText: title,
      text: text,
      confirmButtonText: DATA.alerts.confirm,
      buttonsStyling: false,
      customClass: { popup: 'booking-alert-popup', confirmButton: 'btn-primary' },
      returnFocus: false,
      timer: 4500,
      timerProgressBar: true,
      didClose: function () {
        if (focusTarget && focusTarget.focus) focusTarget.focus();
      }
    });
  }

  function showError(key) {
    var el = $('[data-error="' + key + '"]');
    if (el) el.classList.remove('hidden');
  }
  function hideError(key) {
    var el = $('[data-error="' + key + '"]');
    if (el) el.classList.add('hidden');
  }

  function validateStep(step) {
    var ok = true;
    var firstInvalid = null;

    if (step === 3) {
      hideError('vehicle');
      if (!state.vehicle) { showError('vehicle'); ok = false; firstInvalid = firstInvalid || $('input[name="vehicle_id"]'); }
    }

    if (step === 4 && !isExperience()) {
      hideError('guide');
      if (!find(matchingGuides(criteria()), 'id', state.guide)) {
        showError('guide');
        ok = false;
        firstInvalid = $('input[name="guide_id"]', guideList);
      }
    }

    if (step === 2) {
      hideError('date'); hideError('slot');
      if (!state.date || !dateHasAvailability(state.date)) { showError('date'); ok = false; }
      var assignedSlot = slotById(state.slot);
      var availableSlot = isExperience() || !!(DATA.availability[state.date] || {})[state.slot];
      if (!assignedSlot || !availableSlot) { showError('slot'); ok = false; }
    }

    if (step === 1) {
      [
        ['guests', function (v) { return /^[1-9][0-9]*$/.test(v) && Number(v) <= Number(field('guests').max); }],
        ['full_name', function (v) { return v.trim().length > 1; }],
        ['email', function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); }],
        ['country', function (v) { return v !== ''; }],
        ['pickup_location', function (v) { return v.trim().length > 1; }]
      ].forEach(function (pair) {
        var control = field(pair[0]);
        hideError(pair[0]);
        if (!control || pair[1](control.value)) return;
        showError(pair[0]);
        control.setAttribute('aria-invalid', 'true');
        ok = false;
        firstInvalid = firstInvalid || control;
      });

      /* intl-tel-input validates the number against the selected country's
         own rules, so a valid Pakistani or British number is never rejected by
         a Saudi-shaped check. It writes into the same [data-error] node. */
      if (phoneInput && window.FrontendPhone) {
        if (!window.FrontendPhone.check(phoneInput)) {
          ok = false;
          firstInvalid = firstInvalid || phoneInput;
        }
      } else if (!field('mobile').value.trim()) {
        showError('mobile');
        ok = false;
      }
    }

    if (step === 5) {
      hideError('terms');
      if (!field('terms').checked) {
        showError('terms');
        ok = false;
        firstInvalid = firstInvalid || field('terms');
      }
    }

    if (!ok && firstInvalid && firstInvalid.focus) firstInvalid.focus();
    return ok;
  }

  /* =====================================================================
   * Step navigation
   * ================================================================== */
  function scrollToWizard() {
    var target = $('[data-step="' + state.step + '"]');
    if (!target) return;
    var progressBar = $('[data-progress-bar]');
    var headerHeight = parseFloat(
      getComputedStyle(document.documentElement).getPropertyValue('--frontend-header-h')
    ) || 0;
    var progressHeight = progressBar ? progressBar.getBoundingClientRect().height : 0;
    var y = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - progressHeight - 16;
    window.scrollTo({ top: Math.max(y, 0), behavior: reduceMotion ? 'auto' : 'smooth' });
  }

  function refreshSummaryEdits() {
    $$('aside [data-goto-step], #mobile-summary [data-goto-step]').forEach(function (button) {
      button.toggleAttribute('data-step-completed', !paymentLocked && !!completedSteps[Number(button.dataset.gotoStep)]);
      button.disabled = paymentLocked;
    });
  }

  function invalidateCompletedStep(event) {
    var section = event.target.closest('[data-step]');
    if (!section) return;
    var step = Number(section.dataset.step);
    delete completedSteps[step];
    if (event.target.name === 'guests') {
      delete completedSteps[3];
      delete completedSteps[4];
    } else if (step === 3 && event.target.name !== 'notes') {
      delete completedSteps[4];
    }
    refreshSummaryEdits();
  }
  form.addEventListener('input', invalidateCompletedStep);
  form.addEventListener('change', invalidateCompletedStep);

  function showStep(step, opts) {
    opts = opts || {};
    if (paymentLocked && step !== 6) return;
    if (step === 4 && isExperience()) step = state.step === 5 ? 3 : 5;
    state.step = step;
    refreshSummaryEdits();

    $$('[data-step]').forEach(function (section) {
      section.classList.toggle('hidden', section.getAttribute('data-step') !== String(step));
    });

    $$('[data-step-pill]').forEach(function (pill) {
      var n = Number(pill.getAttribute('data-step-pill'));
      pill.parentElement.classList.toggle('hidden', n === 4 && isExperience());
      var index = pill.querySelector('.step-index');
      if (index) index.textContent = i18n.number(n >= 5 && isExperience() ? n - 1 : n);
      var isDone = n < step;
      pill.setAttribute('data-state', n === step ? 'active' : (isDone ? 'done' : 'todo'));
      if (n === step) {
        pill.setAttribute('aria-current', 'step');
      } else {
        pill.removeAttribute('aria-current');
      }
      pill.disabled = paymentLocked;
    });

    // Keep the active pill visible in the horizontally scrolling rail on mobile
    var activePill = $('[data-step-pill="' + step + '"]');
    if (activePill && activePill.parentElement && activePill.parentElement.parentElement) {
      var rail = activePill.parentElement.parentElement;
      if (rail.scrollWidth > rail.clientWidth) {
        rail.scrollTo({
          left: Math.max(activePill.offsetLeft - 24, 0),
          behavior: reduceMotion ? 'auto' : 'smooth'
        });
      }
    }

    var fill = $('[data-progress-fill]');
    if (fill) {
      var count = isExperience() ? 5 : TOTAL_STEPS;
      var displayStep = step >= 5 && isExperience() ? step - 1 : step;
      var pct = (displayStep / count) * 100;
      fill.style.width = pct + '%';
    }

    var announce = $('[data-progress-announce]');
    if (announce) {
      announce.textContent = i18n.tn('book.step.announce', isExperience() ? 5 : TOTAL_STEPS, {
        step: i18n.number(step >= 5 && isExperience() ? step - 1 : step),
        count: i18n.number(isExperience() ? 5 : TOTAL_STEPS),
        title: STEP_LABELS[step - 1] || ''
      });
    }

    $$('[data-step-caption]').forEach(function (caption) {
      var n = Number(caption.closest('[data-step]').getAttribute('data-step'));
      caption.textContent = DATA.stepCaption
        .replace('{step}', i18n.number(n >= 5 && isExperience() ? n - 1 : n))
        .replace('{count}', i18n.number(isExperience() ? 5 : TOTAL_STEPS));
    });

    if (step === 4) renderGuides();   // the guide panel
    if (step === 5) updateSummary();

    if (!opts.silent) scrollToWizard();

    // Move focus to the new step for keyboard and screen-reader users
    var section = $('[data-step="' + step + '"]');
    if (section && !opts.silent) {
      var heading = section.querySelector('h2');
      if (heading) {
        heading.setAttribute('tabindex', '-1');
        heading.focus({ preventScroll: true });
      }
    }
  }

  $$('[data-next]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = Number(btn.getAttribute('data-next'));
      if (!validateStep(state.step)) return;
      if (savingBooking || savedBooking || promoBusy) return;
      var step = state.step;
      var originalLabel = btn.innerHTML;
      btn.disabled = true;
      setButtonBusy(btn);
      startBusy(step);
      // The overlay is removed before the next step (or the error) is shown.
      saveBookingStep(step).then(function (result) {
        return stopBusy().then(function () { return result; });
      }, function (error) {
        return stopBusy().then(function () { throw error; });
      }).then(function (result) {
        if (!result.success) {
          if (typeof result.error === 'string' && result.error.indexOf('discount_') === 0) {
            handleDiscountEligibilityFailure(btn);
            return;
          }
          bookingAlert('error', DATA.alerts.errorTitle,
            DATA.alerts.errors[result.error] || DATA.alerts.errors.unavailable, btn);
          return;
        }
        if (step === 5) {
          reviewedTotal = Number(result.total);
          paymentLocked = true;
          form.querySelectorAll('[data-goto-step]').forEach(function (button) {
            button.hidden = true;
            button.disabled = true;
          });
        }
        completedSteps[step] = true;
        if (step === 1) detailsCompleted = true;
        refreshSummaryEdits();
        updateSummary();
        showStep(target);
        if (step === 5) prepareMoyasarPayment();
      }).catch(function () {
        bookingAlert('error', DATA.alerts.errorTitle, DATA.alerts.errors.unavailable, btn);
      }).finally(function () {
        btn.disabled = false;
        btn.innerHTML = originalLabel;
      });
    });
  });

  $$('[data-prev]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!savingBooking) showStep(Number(btn.getAttribute('data-prev')));
    });
  });

  // Edit links in the summary and the review screen
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-goto-step]');
    if (!btn || savingBooking) return;
    ev.preventDefault();
    showStep(Number(btn.getAttribute('data-goto-step')));
  });

  // Progress pills navigate backwards only (forward needs validation)
  $$('[data-step-pill]').forEach(function (pill) {
    pill.addEventListener('click', function () {
      var n = Number(pill.getAttribute('data-step-pill'));
      if (!savingBooking && n < state.step) showStep(n);
    });
  });

  /* =====================================================================
   * Mobile summary toggle
   * ================================================================== */
  var mobileToggle = $('[data-mobile-summary-toggle]');
  if (mobileToggle) {
    mobileToggle.addEventListener('click', function () {
      var panel = document.getElementById(mobileToggle.getAttribute('aria-controls'));
      var open = mobileToggle.getAttribute('aria-expanded') !== 'true';
      mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (panel) panel.setAttribute('data-open', open ? 'true' : 'false');
      var chevron = mobileToggle.querySelector('[data-summary-chevron]');
      if (chevron) chevron.classList.toggle('rotate-180', open);
    });
  }

  /* =====================================================================
   * Live-update the review while typing in step 1
   *
   * The mobile field is intl-tel-input (js/phone-input.js): the visitor types
   * into the visible input, while the `mobile` field is the hidden input holding the
   * canonical E.164 number. The wizard reads the hidden field — so Back,
   * Continue and Edit-from-Review all restore the number and its country for
   * free — and asks the plugin for the readable form when it prints it.
   * ================================================================== */
  ['full_name', 'email', 'country', 'mobile', 'pickup_location', 'notes'].forEach(function (name) {
    var control = field(name);
    if (!control) return;
    control.addEventListener('input', updateSummary);
    control.addEventListener('change', function () {
      control.removeAttribute('aria-invalid');
      hideError(name);
      updateSummary();
    });
  });

  /* =====================================================================
   * Loading overlay
   *
   * While a step saves, the step itself is covered by a light overlay that
   * says what is happening (DATA.busy). It appears only after BUSY_DELAY so
   * fast saves never flash it, stays at least BUSY_MIN_VISIBLE once shown,
   * and switches to a "taking longer" message after BUSY_SLOW_AFTER. The
   * header, progress pills and price summary stay visible and usable.
   * ================================================================== */
  var BUSY_DELAY = 300;
  var BUSY_MIN_VISIBLE = 400;
  var BUSY_SLOW_AFTER = 8000;
  var busyNode = $('[data-booking-busy]');
  var busyCard = busyNode ? $('[data-booking-busy-card]', busyNode) : null;
  var busyText = busyNode ? $('[data-booking-busy-text]', busyNode) : null;
  var busyHost = null;
  var busyShownAt = 0;
  var busyTimers = [];

  function clearBusyTimers() {
    busyTimers.forEach(clearTimeout);
    busyTimers = [];
  }

  function setButtonBusy(btn) {
    var spinner = document.createElement('span');
    spinner.className = 'btn-spinner';
    spinner.setAttribute('aria-hidden', 'true');
    btn.textContent = '';
    btn.appendChild(spinner);
    btn.appendChild(document.createTextNode(DATA.alerts.saving));
  }

  function startBusy(step) {
    var section = $('[data-step="' + step + '"]');
    if (!busyNode || !busyCard || !busyText || !section) return;
    clearBusyTimers();
    busyHost = section;
    busyShownAt = 0;
    var messages = (DATA.busy && DATA.busy.steps) || {};

    busyTimers.push(setTimeout(function () {
      var hadFocus = section.contains(document.activeElement)
        || document.activeElement === document.body;
      // Everything already in the step becomes unreachable; the overlay itself stays readable.
      Array.prototype.forEach.call(section.children, function (child) {
        child.inert = true;
      });
      section.classList.add('booking-busy-host');
      section.appendChild(busyNode);
      busyText.textContent = messages[step] || DATA.alerts.saving;
      busyNode.classList.remove('hidden');
      busyShownAt = Date.now();
      if (hadFocus) busyCard.focus({ preventScroll: true });
    }, BUSY_DELAY));

    busyTimers.push(setTimeout(function () {
      if (busyShownAt && DATA.busy && DATA.busy.slow) busyText.textContent = DATA.busy.slow;
    }, BUSY_SLOW_AFTER));
  }

  /** Resolves once the overlay is gone, honouring the minimum visible time. */
  function stopBusy() {
    clearBusyTimers();
    var wait = busyShownAt ? Math.max(0, BUSY_MIN_VISIBLE - (Date.now() - busyShownAt)) : 0;
    return new Promise(function (resolve) {
      setTimeout(function () {
        if (busyHost) {
          Array.prototype.forEach.call(busyHost.children, function (child) {
            child.inert = false;
          });
          busyHost.classList.remove('booking-busy-host');
        }
        if (busyNode) busyNode.classList.add('hidden');
        busyHost = null;
        busyShownAt = 0;
        resolve();
      }, wait);
    });
  }

  /* =====================================================================
   * Step persistence and final submission
   * ================================================================== */
  var savingBooking = false;
  var savedBooking = false;
  var bookingReference = '';

  /* reCAPTCHA Enterprise. Only the steps that create (1) or complete (6) the
     booking carry a token, and the server verifies each against its own
     action. A fresh token is generated for every such save because a token
     is single-use. Resolves '' for steps that need none; rejects when a
     token is required but reCAPTCHA is unavailable. */
  function bookingRecaptchaToken(step) {
    var config = DATA.recaptcha || {};
    var action = config.actions && config.actions[step];
    if (!action) return Promise.resolve('');
    if (!config.siteKey || !window.grecaptcha || !window.grecaptcha.enterprise) {
      return Promise.reject(new Error('recaptcha unavailable'));
    }
    return new Promise(function (resolve, reject) {
      window.grecaptcha.enterprise.ready(function () {
        window.grecaptcha.enterprise.execute(config.siteKey, { action: action }).then(resolve, reject);
      });
    });
  }

  function saveBookingStep(step) {
    savingBooking = true;
    form.setAttribute('aria-busy', 'true');
    var payload = bookingFormData();
    // Send the selected reference explicitly from wizard state.
    payload.set('guide_id', state.guide);
    payload.set('booking_step', String(step));
    payload.set('booking_id', bookingReference);
    return bookingRecaptchaToken(step).then(function (token) {
      if (token) payload.set('recaptcha_token', token);
      return fetch(form.getAttribute('data-action'), {
        method: 'POST',
        body: payload,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      }).then(function (response) {
        return response.json();
      }).then(function (result) {
        if (result.success) {
          bookingReference = result.booking_id;
          state.stepsCompleted = Number(result.steps_completed);
          form.setAttribute('data-steps-completed', String(state.stepsCompleted));
          syncPromoFromResult(result);
        }
        return result;
      });
    }, function () {
      return { success: false, error: 'recaptcha' };
    }).finally(function () {
      savingBooking = false;
      form.removeAttribute('aria-busy');
    });
  }

  /* =====================================================================
   * Moyasar payment form
   *
   * The server prepares the attempt from the stored reviewed booking. The
   * amount shown here can be inspected by a browser user, but payment return
   * and webhooks independently compare Moyasar's amount/currency/metadata to
   * that server-side booking before anything is marked paid.
   * ================================================================== */
  var moyasarInitialized = false;
  var moyasarPreparing = false;

  function paymentPanelState(loading, failed) {
    var loadingNode = $('[data-payment-loading]');
    var errorNode = $('[data-payment-error]');
    if (loadingNode) loadingNode.classList.toggle('hidden', !loading);
    if (errorNode) errorNode.classList.toggle('hidden', !failed);
  }

  function recordMoyasarPayment(url, attemptReference, paymentId) {
    var payload = new URLSearchParams();
    payload.set('attempt_reference', attemptReference);
    payload.set('payment_id', paymentId);
    return fetch(url, {
      method: 'POST',
      body: payload,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      if (!response.ok) throw new Error('Unable to record payment.');
      return response.json();
    }).then(function (result) {
      if (!result.success) throw new Error('Unable to record payment.');
      return true;
    });
  }

  function requestMoyasarAttempt() {
    var payload = new URLSearchParams();
    payload.set('booking_token', field('booking_token').value);
    payload.set('booking_id', bookingReference);

    return fetch(DATA.paymentPrepareUrl, {
      method: 'POST',
      body: payload,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      return response.json().then(function (result) {
        if (!response.ok || !result.success) throw new Error(result.error || 'unavailable');
        return result;
      });
    });
  }

  function prepareMoyasarPayment() {
    if (moyasarInitialized || moyasarPreparing) return;
    if (!DATA.moyasarEnabled || !window.Moyasar || !DATA.paymentPrepareUrl) {
      paymentPanelState(false, true);
      return;
    }

    moyasarPreparing = true;
    paymentPanelState(true, false);
    requestMoyasarAttempt().then(function (config) {
      window.Moyasar.init({
        element: '[data-moyasar-form]',
        amount: Number(config.amount),
        currency: config.currency,
        description: config.description,
        publishable_api_key: config.publishable_api_key,
        callback_url: config.callback_url,
        methods: config.methods,
        supported_networks: config.supported_networks,
        metadata: config.metadata,
        fixed_width: false,
        on_initiating: function () {
          /* Moyasar Form 1.15.0 only accepts amount, description, callback_url
             and metadata from this callback's resolved value — returning
             currency (even unchanged) throws "Changing currency is not
             allowed!" inside moyasar.js, which the form then reports to the
             customer as a generic "Network Error". */
          return requestMoyasarAttempt().then(function (nextConfig) {
            config = nextConfig;
            return {
              amount: Number(nextConfig.amount),
              description: nextConfig.description,
              callback_url: nextConfig.callback_url,
              metadata: nextConfig.metadata
            };
          });
        },
        on_completed: function (payment) {
          if (!payment || !payment.id) return Promise.reject(new Error('Missing payment ID.'));
          return recordMoyasarPayment(
            config.record_url,
            config.attempt_reference,
            String(payment.id)
          );
        }
      });
      moyasarInitialized = true;
      paymentPanelState(false, false);
    }).catch(function () {
      paymentPanelState(false, true);
      bookingAlert('error', DATA.alerts.errorTitle, DATA.alerts.errors.unavailable);
    }).finally(function () {
      moyasarPreparing = false;
    });
  }

  /* =====================================================================
   * Promo code — apply/remove (Review step). Its own endpoint, never a
   * step save: no terms, no wizard advance, no Review completion. Mutating
   * the code always reopens an already-accepted Review (see reopenReview()),
   * which is also the recovery path when a code stops being eligible
   * between Review acceptance and payment.
   * ================================================================== */
  function reopenReview() {
    if (!paymentLocked && completedSteps[5] !== true) return;
    paymentLocked = false;
    reviewedTotal = null;
    completedSteps[5] = false;
    form.querySelectorAll('[data-goto-step]').forEach(function (button) {
      button.hidden = false;
      button.disabled = false;
    });
  }

  function handleDiscountEligibilityFailure(focusTarget) {
    reopenReview();
    showStep(5);
    updateSummary();
    bookingAlert('error', i18n.t('book.promo.eligibilityTitle'), i18n.t('book.promo.eligibilityText'), focusTarget);
  }

  function postPromo(action, code) {
    var payload = new URLSearchParams();
    payload.set('booking_token', field('booking_token').value);
    payload.set('tour_id', state.tour);
    payload.set('booking_id', bookingReference);
    payload.set('discount_action', action);
    if (action === 'apply') payload.set('discount_code', code);
    return fetch(DATA.discountUrl, {
      method: 'POST',
      body: payload,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) { return response.json(); });
  }

  var promoSection = $('[data-promo-section]');
  var promoInput = promoSection && $('[data-promo-input]', promoSection);
  var promoApplyBtn = promoSection && $('[data-promo-apply]', promoSection);
  var promoRemoveBtn = promoSection && $('[data-promo-remove]', promoSection);

  function applyPromoCode(focusTarget) {
    if (promoBusy || savingBooking || !bookingReference) return;
    var code = ((promoInput && promoInput.value) || '').trim();
    if (!code) {
      if (promoInput) promoInput.focus();
      return;
    }
    promoBusy = true;
    var seq = ++promoRequestSeq;
    setPromoStatus(i18n.t('book.promo.applying'), 'neutral');
    renderPromoWidget();
    postPromo('apply', code).then(function (result) {
      if (seq !== promoRequestSeq) return;
      if (!result.success) {
        var message = DATA.alerts.promoErrors[result.error] || DATA.alerts.promoErrors.invalid;
        setPromoStatus(message, 'error');
        bookingAlert('error', i18n.t('book.promo.applyErrorTitle'), message, promoInput);
        return;
      }
      syncPromoFromResult({
        discount_code: result.code,
        discount_type: result.type,
        discount_value: result.value,
        discount_incomplete: result.incomplete,
        discount_eligible: true
      });
      reopenReview();
      if (promoInput) promoInput.value = '';
      setPromoStatus(promoIncomplete ? i18n.t('book.promo.incomplete') : '', promoIncomplete ? 'neutral' : 'success');
      updateSummary();
      bookingAlert('success', i18n.t('book.promo.applySuccessTitle'), i18n.t('book.promo.applySuccessText'), focusTarget || promoApplyBtn);
    }).catch(function () {
      if (seq !== promoRequestSeq) return;
      setPromoStatus(DATA.alerts.promoErrors.unavailable, 'error');
    }).finally(function () {
      if (seq !== promoRequestSeq) return;
      promoBusy = false;
      renderPromoWidget();
    });
  }

  if (promoApplyBtn) {
    promoApplyBtn.addEventListener('click', function () { applyPromoCode(promoApplyBtn); });
  }

  if (promoInput) {
    // Enter submits the promo code instead of bubbling up to the booking form's own submit handler.
    promoInput.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      applyPromoCode(promoInput);
    });
  }

  if (promoRemoveBtn) {
    promoRemoveBtn.addEventListener('click', function () {
      if (promoBusy || savingBooking || !bookingReference) return;
      promoBusy = true;
      var seq = ++promoRequestSeq;
      setPromoStatus(i18n.t('book.promo.removing'), 'neutral');
      renderPromoWidget();
      postPromo('remove', '').then(function (result) {
        if (seq !== promoRequestSeq) return;
        if (!result.success) {
          bookingAlert('error', i18n.t('book.promo.removeErrorTitle'), i18n.t('book.promo.removeErrorText'), promoRemoveBtn);
          setPromoStatus(i18n.t('book.promo.removeErrorText'), 'error');
          return;
        }
        syncPromoFromResult({ discount_code: '' });
        reopenReview();
        setPromoStatus('', 'neutral');
        updateSummary();
        bookingAlert('success', i18n.t('book.promo.removeSuccessTitle'), i18n.t('book.promo.removeSuccessText'), promoRemoveBtn);
      }).catch(function () {
        if (seq !== promoRequestSeq) return;
        bookingAlert('error', i18n.t('book.promo.removeErrorTitle'), i18n.t('book.promo.removeErrorText'), promoRemoveBtn);
      }).finally(function () {
        if (seq !== promoRequestSeq) return;
        promoBusy = false;
        renderPromoWidget();
      });
    });
  }

  /* =====================================================================
   * Boot
   * ================================================================== */
  refreshGuestLimit();
  refreshLanguages();
  checkCapacity();
  if (state.date) {
    var seeded = fromISO(state.date);
    if (!seeded || seeded < fromISO(DATA.minimumDate) || seeded > calendarMaxDate() || !dateHasAvailability(state.date)) {
      state.date = '';
      if (dateInput) dateInput.value = '';
    }
  }
  refreshDependents();
  showStep(1, { silent: true });
})();
