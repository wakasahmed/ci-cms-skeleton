/*
 * Book (/book): the booking wizard, rebuilt from the reference design's
 * booking.js on CMS data.
 *
 * The page embeds its configuration as JSON (#booking-config): the
 * catalogue (service groups, artists with the services they offer, offers),
 * editable notes, the availability URL, the one-use form token and
 * reCAPTCHA settings. The five steps are rendered here. The free days and
 * times come from the server (/book/availability) for the chosen services
 * and artist; the booking is posted with AJAX and checked again under a
 * lock (libraries/Booking_request.php), which answers with JSON. On success
 * the browser opens /book/confirmed.
 *
 * ?service={slug}, ?artist={slug} and ?offer={slug} preselect choices.
 */
(function ($) {
    'use strict';

    var STEPS = [
        { id: 'service', label: 'Choose your service', short: 'Service' },
        { id: 'artist', label: 'Choose your artist', short: 'Artist' },
        { id: 'datetime', label: 'Pick your date and time', short: 'Date & time' },
        { id: 'details', label: 'Your details', short: 'Details' },
        { id: 'review', label: 'Review your appointment', short: 'Review' }
    ];
    var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var PHONE = /^\+?[0-9 ()\-]+$/;

    var PRIMARY_BUTTON = 'inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-full bg-primary-cta px-7'
        + ' text-[0.95rem] font-semibold text-primary-foreground shadow-[var(--shadow-card)] transition-colors'
        + ' hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-45';
    var QUIET_BUTTON = 'inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-full border'
        + ' border-border-strong bg-background px-6 text-[0.95rem] font-semibold text-foreground'
        + ' hover:border-primary hover:bg-petal hover:text-primary';
    var PICKED_CARD = 'border-primary bg-petal shadow-[var(--shadow-card)]';
    var OPEN_CARD = 'border-border-strong bg-background hover:border-primary hover:bg-petal/60';

    var config;
    var root;
    var services = [];
    var state = {
        step: 0,
        group: '',
        serviceSlugs: [],
        artistSlug: 'any',
        offerSlug: '',
        date: '',
        time: '',
        details: { name: '', phone: '', email: '', contact: 'phone', notes: '' },
        errors: {},
        alert: '',
        sending: false,
        // Free times from /book/availability: key (services + artist) and Y-m-d => ["HH:MM", …].
        availability: { key: '', days: null, loading: false, failed: false }
    };

    /* Helpers ------------------------------------------------------------ */

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
        });
    }

    function price(amount) {
        var fixed = Number(amount).toFixed(2).replace(/\.00$/, '');

        return fixed + ' zł';
    }

    function duration(minutes) {
        var hours = Math.floor(minutes / 60);
        var rest = minutes % 60;

        if (!minutes) {
            return '—';
        }

        return !hours ? rest + ' min' : hours + ' hr' + (rest ? ' ' + rest + ' min' : '');
    }

    function fromISO(iso) {
        var parts = iso.split('-').map(Number);

        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function toISO(date) {
        return date.getFullYear() + '-'
            + String(date.getMonth() + 1).padStart(2, '0') + '-'
            + String(date.getDate()).padStart(2, '0');
    }

    function longDate(iso) {
        return iso
            ? fromISO(iso).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
            : '';
    }

    function timeLabel(value) {
        var parts = value.split(':').map(Number);
        var period = parts[0] < 12 ? 'AM' : 'PM';

        return (parts[0] % 12 || 12) + ':' + String(parts[1]).padStart(2, '0') + ' ' + period;
    }

    function findService(slug) {
        return services.find(function (service) {
            return service.slug === slug;
        });
    }

    function selectedServices() {
        return state.serviceSlugs.map(findService).filter(Boolean);
    }

    function totalMinutes() {
        return selectedServices().reduce(function (sum, service) {
            return sum + (service.minutes || 0);
        }, 0);
    }

    function totalPrice() {
        var priced = selectedServices().filter(function (service) {
            return service.price !== null;
        });

        return priced.length
            ? priced.reduce(function (sum, service) {
                return sum + service.price;
            }, 0)
            : null;
    }

    function selectedArtist() {
        return config.catalogue.artists.find(function (artist) {
            return artist.slug === state.artistSlug;
        }) || null;
    }

    function selectedOffer() {
        return config.catalogue.offers.find(function (offer) {
            return offer.slug === state.offerSlug;
        }) || null;
    }

    function artistName() {
        var artist = selectedArtist();

        if (artist) {
            return artist.name;
        }

        return needsSeveralArtists() ? 'Matched to each service' : 'Next available artist';
    }

    /** TRUE when the artist offers every chosen service. */
    function offersAll(artist) {
        return state.serviceSlugs.every(function (slug) {
            return artist.services.indexOf(slug) !== -1;
        });
    }

    /** TRUE when no single artist offers every chosen service. */
    function needsSeveralArtists() {
        return state.serviceSlugs.length > 1 && !config.catalogue.artists.some(offersAll);
    }

    /** A chosen artist who no longer offers every chosen service is dropped. */
    function syncArtist() {
        var artist = selectedArtist();
        if (artist && !offersAll(artist)) {
            state.artistSlug = 'any';
        }
    }

    /** An offer stays attached while all of its services are chosen. */
    function syncOffer() {
        var offer = selectedOffer();
        if (offer && !offer.services.every(function (slug) {
            return state.serviceSlugs.indexOf(slug) !== -1;
        })) {
            state.offerSlug = '';
        }
    }

    /* Availability (from /book/availability) ---------------------------- */

    function availabilityKey() {
        return state.serviceSlugs.join(',') + '|' + state.artistSlug;
    }

    /** Load the free times for the current services and artist, once per choice. */
    function loadAvailability(force) {
        var key = availabilityKey();
        var params = new URLSearchParams();

        if (!force && state.availability.key === key && (state.availability.days || state.availability.loading)) {
            return;
        }

        state.availability = { key: key, days: null, loading: true, failed: false };
        state.serviceSlugs.forEach(function (slug) {
            params.append('services[]', slug);
        });
        params.append('artist', state.artistSlug);

        fetch(config.availabilityUrl + '?' + params.toString(), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Availability request failed.');
                }
                return response.json();
            })
            .then(function (result) {
                if (state.availability.key !== key) {
                    return;
                }
                state.availability = { key: key, days: result.days || {}, loading: false, failed: false };
                syncSlot();
                if (state.step === 2) {
                    render();
                }
            })
            .catch(function () {
                if (state.availability.key !== key) {
                    return;
                }
                state.availability = { key: key, days: null, loading: false, failed: true };
                if (state.step === 2) {
                    render();
                }
            });
    }

    function freeTimes(iso) {
        var days = state.availability.days;

        return days && days[iso] ? days[iso] : [];
    }

    function days() {
        var dates = state.availability.days ? Object.keys(state.availability.days) : [];

        return dates.map(function (iso, index) {
            return { date: fromISO(iso), iso: iso, today: index === 0, open: freeTimes(iso).length > 0 };
        });
    }

    /** Drop a chosen day or time that is no longer free (services or artist changed). */
    function syncSlot() {
        if (!state.availability.days || state.availability.key !== availabilityKey()) {
            return;
        }
        if (state.date && freeTimes(state.date).indexOf(state.time) === -1) {
            state.time = '';
        }
        if (state.date && !freeTimes(state.date).length) {
            state.date = '';
        }
    }

    /* Steps ---------------------------------------------------------------- */

    function progress() {
        var percent = ((state.step + 1) / STEPS.length) * 100;
        var items = STEPS.map(function (step, index) {
            var active = index === state.step;
            var done = index < state.step;
            var badge = active
                ? 'bg-primary-cta text-primary-foreground ring-4 ring-primary/20'
                : (done ? 'bg-primary text-primary-foreground' : 'bg-rose-100 text-primary-ink');

            return '<li class="flex flex-1 items-center gap-2">'
                + '<button type="button" data-step="' + index + '" ' + (done ? '' : 'disabled')
                + ' class="flex min-h-11 items-center gap-2.5 rounded-full px-1 text-left"'
                + (active ? ' aria-current="step"' : '') + '>'
                + '<span class="grid size-8 shrink-0 place-items-center rounded-full font-display text-sm font-semibold ' + badge + '">'
                + (done ? '<i class="fa-solid fa-check" aria-hidden="true"></i><span class="sr-only">Done: </span>' : index + 1)
                + '</span>'
                + '<span class="hidden text-sm lg:block ' + (active ? 'font-semibold text-foreground' : 'text-muted-foreground') + '">'
                + step.short + '</span>'
                + '</button>'
                + (index < STEPS.length - 1 ? '<span class="h-px flex-1 ' + (done ? 'bg-primary' : 'bg-border-strong') + '"></span>' : '')
                + '</li>';
        }).join('');

        return '<div class="mt-10">'
            + '<div class="sm:hidden">'
            + '<p class="flex items-baseline justify-between gap-3">'
            + '<span class="font-display text-lg text-foreground">' + STEPS[state.step].label + '</span>'
            + '<span class="text-sm text-muted-foreground">Step ' + (state.step + 1) + ' of ' + STEPS.length + '</span>'
            + '</p>'
            + '<div class="mt-3 h-1.5 overflow-hidden rounded-full bg-rose-100">'
            + '<span class="block h-full rounded-full bg-primary" style="width:' + percent + '%"></span>'
            + '</div>'
            + '</div>'
            + '<ol class="hidden items-center gap-2 sm:flex" aria-label="Booking steps">' + items + '</ol>'
            + '</div>';
    }

    function serviceStep() {
        var groups = config.catalogue.groups;
        var group = groups.find(function (item) {
            return item.slug === state.group;
        }) || groups[0];
        var chips = groups.map(function (item) {
            var active = item.slug === group.slug;

            return '<button type="button" data-group="' + esc(item.slug) + '" aria-pressed="' + active + '"'
                + ' class="min-h-11 rounded-full border px-5 text-sm font-semibold '
                + (active ? 'border-primary bg-primary text-primary-foreground' : 'border-border-strong bg-background text-foreground-soft hover:border-primary hover:bg-petal')
                + '">' + esc(item.name) + '</button>';
        }).join('');
        var cards = group.services.map(function (service) {
            var picked = state.serviceSlugs.indexOf(service.slug) !== -1;
            var meta = [service.priceLabel, service.durationLabel].filter(Boolean);

            return '<li>'
                + '<button type="button" data-service="' + esc(service.slug) + '" aria-pressed="' + picked + '"'
                + ' class="group flex h-full w-full cursor-pointer gap-4 rounded-lg border p-4 text-left transition-colors ' + (picked ? PICKED_CARD : OPEN_CARD) + '">'
                + '<span class="relative size-16 shrink-0 overflow-hidden rounded-lg bg-muted">'
                + '<img src="' + esc(service.image) + '" alt="" loading="lazy" class="h-full w-full object-cover">'
                + '</span>'
                + '<span class="min-w-0 flex-1">'
                + '<span class="flex items-start justify-between gap-3">'
                + '<span class="font-display text-lg text-foreground">' + esc(service.name) + '</span>'
                + '<span aria-hidden="true" class="grid size-5 shrink-0 place-items-center rounded-full border '
                + (picked ? 'border-primary bg-primary text-primary-foreground' : 'border-border-strong bg-background') + '">'
                + (picked ? '<i class="fa-solid fa-check text-xs"></i>' : '') + '</span>'
                + '</span>'
                + (service.summary ? '<span class="mt-1 block text-sm text-muted-foreground">' + esc(service.summary) + '</span>' : '')
                + (meta.length ? '<span class="mt-2 block text-sm"><b>' + esc(meta[0]) + '</b>' + (meta[1] ? ' · ' + esc(meta[1]) : '') + '</span>' : '')
                + '</span>'
                + '</button>'
                + '</li>';
        }).join('');

        return '<div>'
            + '<div class="flex flex-wrap gap-2" role="group" aria-label="Filter services">' + chips + '</div>'
            + (group.description ? '<p class="mt-4 text-muted-foreground">' + esc(group.description) + '</p>' : '')
            + '<ul class="mt-6 grid gap-3 sm:grid-cols-2">' + cards + '</ul>'
            + (config.notes.service ? '<p class="mt-6 text-sm text-muted-foreground">' + esc(config.notes.service) + '</p>' : '')
            + '</div>';
    }

    function artistStep() {
        var options = [{
            slug: 'any',
            name: 'Any available artist',
            role: '',
            specialty: 'We’ll match you with whoever is free at the time you choose — usually the quickest way to get the slot you want.',
            services: [],
            image: '',
            placeholder: false
        }].concat(config.catalogue.artists);

        var several = needsSeveralArtists();
        var intro = several
            ? '<p class="mb-6 rounded-lg bg-lilac px-4 py-3 text-sm text-foreground-soft">Your services are done by different people,'
                + ' so we’ll book them back to back with the right artist for each.</p>'
            : '';

        return intro + '<ul class="grid gap-3 sm:grid-cols-2">' + options.map(function (artist, index) {
            var picked = artist.slug === state.artistSlug;
            var unavailable = artist.slug !== 'any' && !offersAll(artist);
            var portrait = artist.image
                ? '<span class="relative size-16 shrink-0 overflow-hidden rounded-full bg-muted"><img src="' + esc(artist.image) + '" alt="" loading="lazy" class="h-full w-full object-cover object-top"></span>'
                : '<span aria-hidden="true" class="grid size-16 shrink-0 place-items-center rounded-full bg-rose-100 text-2xl text-primary"><i class="fa-solid fa-wand-magic-sparkles"></i></span>';

            return '<li class="' + (index === 0 ? 'sm:col-span-2' : '') + '">'
                + '<button type="button" data-artist="' + esc(artist.slug) + '" aria-pressed="' + picked + '"'
                + (unavailable ? ' disabled aria-describedby="artist-' + esc(artist.slug) + '-note"' : '')
                + ' class="flex h-full w-full items-center gap-4 rounded-lg border p-4 text-left disabled:cursor-not-allowed disabled:opacity-55 '
                + (picked ? PICKED_CARD : OPEN_CARD) + '">'
                + portrait
                + '<span class="min-w-0 flex-1">'
                + '<span class="font-display text-lg text-foreground">' + esc(artist.name) + '</span>'
                + (artist.role ? '<span class="mt-0.5 block text-sm text-foreground-soft">' + esc(artist.role) + '</span>' : '')
                + (artist.specialty ? '<span class="mt-1 block text-sm text-muted-foreground">' + esc(artist.specialty) + '</span>' : '')
                + (artist.placeholder ? '<span class="mt-1 block text-xs text-primary-ink">Placeholder profile</span>' : '')
                + (unavailable
                    ? '<span class="mt-1 block text-xs text-muted-foreground" id="artist-' + esc(artist.slug) + '-note">Doesn’t offer every service you chose</span>'
                    : '')
                + '</span>'
                + '<span aria-hidden="true" class="grid size-6 shrink-0 place-items-center rounded-full border '
                + (picked ? 'border-primary bg-primary text-primary-foreground' : 'border-border-strong') + '">'
                + (picked ? '<i class="fa-solid fa-check text-xs"></i>' : '') + '</span>'
                + '</button>'
                + '</li>';
        }).join('') + '</ul>';
    }

    function dateTimeStep() {
        var dayButtons = days().map(function (day) {
            var picked = day.iso === state.date;

            return '<button type="button" role="radio" aria-checked="' + picked + '"'
                + ' aria-label="' + esc(longDate(day.iso) + (day.open ? '' : ' — not available')) + '"'
                + ' data-date="' + day.iso + '" ' + (day.open ? '' : 'disabled')
                + ' class="flex w-16 shrink-0 flex-col items-center gap-1 rounded-lg border py-3 '
                + (picked ? 'border-primary bg-primary-cta text-primary-foreground' : 'border-border-strong bg-background hover:border-primary hover:bg-petal')
                + ' disabled:cursor-not-allowed disabled:opacity-45">'
                + '<span class="text-xs">' + day.date.toLocaleDateString('en-GB', { weekday: 'short' }) + '</span>'
                + '<span class="font-display text-xl">' + day.date.getDate() + '</span>'
                + '<span class="text-xs">' + (day.today ? 'Today' : day.date.toLocaleDateString('en-GB', { month: 'short' })) + '</span>'
                + '</button>';
        }).join('');
        var times = state.date ? freeTimes(state.date) : [];
        var timeArea;

        if (state.availability.loading || (!state.availability.days && !state.availability.failed)) {
            return '<p class="text-muted-foreground" role="status">Checking the diary…</p>';
        }
        if (state.availability.failed) {
            return '<p role="alert" class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">We couldn’t load the free times.'
                + ' <button type="button" data-reload-times class="font-semibold underline">Try again</button></p>';
        }
        if (!days().some(function (day) {
            return day.open;
        })) {
            return '<p class="rounded-lg bg-lilac px-4 py-3 text-foreground-soft">There are no free times for this choice in the next three weeks.'
                + ' Try another artist, fewer services, or call the salon.</p>';
        }

        if (!state.date) {
            timeArea = '<p class="mt-3 text-muted-foreground">Pick a day above and we’ll show the free times.</p>';
        } else {
            timeArea = '<div role="radiogroup" aria-label="Choose a time" class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">'
                + times.map(function (time) {
                    var picked = state.time === time;

                    return '<button type="button" role="radio" aria-checked="' + picked + '" data-time="' + time + '"'
                        + ' class="min-h-12 rounded-lg border px-2 text-sm font-medium '
                        + (picked ? 'border-primary bg-primary-cta text-primary-foreground' : 'border-border-strong bg-background hover:border-primary hover:bg-petal')
                        + '">' + timeLabel(time) + '</button>';
                }).join('')
                + '</div>';
        }

        return '<div>'
            + (config.notes.schedule ? '<p class="rounded-lg bg-lilac px-4 py-3 text-sm text-foreground-soft">' + esc(config.notes.schedule) + '</p>' : '')
            + (selectedArtist() ? '<p class="mt-4 text-sm text-muted-foreground">Showing ' + esc(selectedArtist().name) + '’s free times.</p>' : '')
            + '<h3 class="mt-8 font-display text-lg">Choose a day</h3>'
            + '<div role="radiogroup" aria-label="Choose a day" class="-mx-5 mt-4 flex gap-2 overflow-x-auto px-5 pb-2 sm:mx-0 sm:px-0">' + dayButtons + '</div>'
            + '<h3 class="mt-10 font-display text-lg">' + (state.date ? 'Times on ' + esc(longDate(state.date)) : 'Choose a time') + '</h3>'
            + timeArea
            + (state.errors.datetime ? '<p class="mt-3 text-sm text-destructive">' + esc(state.errors.datetime) + '</p>' : '')
            + '</div>';
    }

    function field(options) {
        var error = state.errors[options.key];
        var hintId = options.id + '-hint';
        var errorId = options.id + '-error';
        var describedBy = [options.hint ? hintId : '', error ? errorId : ''].filter(Boolean).join(' ');

        return '<div class="' + (options.span ? 'sm:col-span-2' : '') + '">'
            + '<label for="' + options.id + '" class="block text-sm font-semibold text-foreground">' + options.label
            + ' <span class="text-primary" aria-hidden="true">*</span></label>'
            + (options.hint ? '<p class="mt-1 text-sm text-muted-foreground" id="' + hintId + '">' + esc(options.hint) + '</p>' : '')
            + '<input id="' + options.id + '" name="' + options.key + '" autocomplete="' + options.autocomplete + '"'
            + ' data-detail="' + options.key + '" required type="' + options.type + '" maxlength="' + options.maxlength + '"'
            + ' value="' + esc(state.details[options.key]) + '" placeholder="' + esc(options.placeholder) + '"'
            + ' class="mt-2 min-h-12 w-full rounded-lg border bg-background px-4 outline-none '
            + (error ? 'border-destructive' : 'border-border-strong focus:border-primary') + '"'
            + ' aria-invalid="' + Boolean(error) + '"' + (describedBy ? ' aria-describedby="' + describedBy + '"' : '') + '>'
            + (error ? '<p class="mt-1 text-sm text-destructive" id="' + errorId + '">' + esc(error) + '</p>' : '')
            + '</div>';
    }

    function detailsStep() {
        var notesError = state.errors.notes;

        return '<div class="grid gap-6 sm:grid-cols-2">'
            + field({ id: 'booking-name', key: 'name', label: 'Full name', type: 'text', autocomplete: 'name', maxlength: 150, placeholder: 'Anna Kowalska', span: true })
            + field({ id: 'booking-phone', key: 'phone', label: 'Phone number', type: 'tel', autocomplete: 'tel', maxlength: 40, placeholder: '+48 500 000 000', hint: 'So we can reach you if anything changes.' })
            + field({ id: 'booking-email', key: 'email', label: 'Email', type: 'email', autocomplete: 'email', maxlength: 190, placeholder: 'anna@example.com', hint: 'A copy of your request goes here.' })
            + '<div class="sm:col-span-2 sm:max-w-xs">'
            + '<label for="booking-contact" class="block text-sm font-semibold">Preferred contact</label>'
            + '<p class="mt-1 text-sm text-muted-foreground" id="booking-contact-hint">How we’ll reach you to confirm.</p>'
            + '<select id="booking-contact" name="contact" data-detail="contact" aria-describedby="booking-contact-hint"'
            + ' class="select-chevron mt-2 min-h-12 w-full cursor-pointer appearance-none rounded-lg border border-border-strong bg-background px-4 pr-10">'
            + '<option value="phone"' + (state.details.contact === 'phone' ? ' selected' : '') + '>Phone</option>'
            + '<option value="email"' + (state.details.contact === 'email' ? ' selected' : '') + '>Email</option>'
            + '</select>'
            + '</div>'
            + '<div class="sm:col-span-2">'
            + '<label for="booking-notes" class="block text-sm font-semibold">Notes or special requests</label>'
            + '<p class="mt-1 text-sm text-muted-foreground" id="booking-notes-hint">Anything we should know — a design you have in mind, gel that needs removing, or a first visit.</p>'
            + '<textarea id="booking-notes" name="notes" data-detail="notes" rows="5" maxlength="2000"'
            + ' aria-describedby="booking-notes-hint' + (notesError ? ' booking-notes-error' : '') + '" aria-invalid="' + Boolean(notesError) + '"'
            + ' class="mt-2 w-full rounded-lg border ' + (notesError ? 'border-destructive' : 'border-border-strong') + ' bg-background px-4 py-3"'
            + ' placeholder="I’d like something in the deeper autumn shades, and I have gel on from last month.">'
            + esc(state.details.notes) + '</textarea>'
            + (notesError ? '<p class="mt-1 text-sm text-destructive" id="booking-notes-error">' + esc(notesError) + '</p>' : '')
            + '</div>'
            + '</div>';
    }

    function reviewRow(label, value, step) {
        return '<div class="flex flex-col gap-2 py-5 sm:flex-row sm:items-start sm:gap-6">'
            + '<dt class="w-40 shrink-0 font-medium">' + label + '</dt>'
            + '<dd class="min-w-0 flex-1 text-muted-foreground">' + value + '</dd>'
            + '<button type="button" data-step="' + step + '" class="self-start text-sm font-semibold text-primary-ink">'
            + '<i class="fa-solid fa-pen mr-1" aria-hidden="true"></i>Edit<span class="sr-only"> ' + label.toLowerCase() + '</span></button>'
            + '</div>';
    }

    function reviewStep() {
        var serviceList = '<ul class="space-y-1">' + selectedServices().map(function (service) {
            var meta = [service.price !== null ? price(service.price) : '', service.durationLabel].filter(Boolean).join(' · ');

            return '<li class="flex justify-between gap-4"><span>' + esc(service.name) + '</span><span>' + esc(meta) + '</span></li>';
        }).join('') + '</ul>';
        var artist = selectedArtist();
        var offer = selectedOffer();
        var total = totalPrice();
        var details = esc(state.details.name)
            + '<span class="block text-foreground-soft">' + esc(state.details.phone) + ' · ' + esc(state.details.email) + '</span>'
            + '<span class="block text-foreground-soft">Prefers contact by ' + esc(state.details.contact) + '</span>';

        return '<dl class="divide-y divide-border border-y border-border">'
            + reviewRow('Services', serviceList, 0)
            + (offer ? reviewRow('Offer', esc(offer.title + (offer.price !== null ? ' — ' + price(offer.price) : '')), 0) : '')
            + reviewRow('Artist', esc(artist ? artist.name + (artist.role ? ' — ' + artist.role : '') : artistName()), 1)
            + reviewRow('Date & time', esc(longDate(state.date) + ' at ' + timeLabel(state.time)), 2)
            + reviewRow('Time needed', duration(totalMinutes()), 2)
            + reviewRow('Your details', details, 3)
            + (state.details.notes ? reviewRow('Notes', esc(state.details.notes).replace(/\n/g, '<br>'), 3) : '')
            + '</dl>'
            + (total !== null
                ? '<div class="mt-6 flex items-baseline justify-between rounded-lg bg-petal px-5 py-4"><span class="font-medium">Estimated total</span><span class="font-display text-2xl">' + price(total) + '</span></div>'
                : '')
            + (config.notes.review ? '<p class="mt-4 text-sm text-muted-foreground">' + esc(config.notes.review) + '</p>' : '');
    }

    function summaryBar() {
        var chosen = selectedServices();
        var total = totalPrice();

        return '<div class="mb-6 flex items-center justify-between gap-4 rounded-lg bg-petal px-4 py-3 lg:hidden">'
            + '<div class="min-w-0">'
            + '<p class="truncate text-sm font-medium">' + (chosen.length ? chosen.map(function (service) {
                return esc(service.name);
            }).join(' + ') : 'No services chosen yet') + '</p>'
            + (chosen.length ? '<p class="text-sm text-muted-foreground">' + duration(totalMinutes()) + '</p>' : '')
            + '</div>'
            + '<p class="shrink-0 font-display text-xl">' + (total !== null ? price(total) : '—') + '</p>'
            + '</div>';
    }

    function summary() {
        var chosen = selectedServices();
        var offer = selectedOffer();
        var total = totalPrice();
        var servicesHtml = chosen.length
            ? '<ul class="space-y-1">' + chosen.map(function (service) {
                return '<li class="flex justify-between gap-3"><span>' + esc(service.name) + '</span>'
                    + '<span class="shrink-0">' + (service.price !== null ? price(service.price) : '') + '</span></li>';
            }).join('') + '</ul>'
            : 'Not chosen yet';
        var help = config.help;

        return '<aside aria-label="Your appointment so far" class="hidden rounded-xl bg-petal p-6 shadow-[var(--shadow-card)] lg:block">'
            + '<h2 class="font-display text-xl">Your appointment</h2>'
            + '<dl class="mt-5 space-y-4 text-sm">'
            + '<div><dt class="font-medium">Services</dt><dd class="mt-1 text-muted-foreground">' + servicesHtml + '</dd></div>'
            + (offer ? '<div><dt class="font-medium">Offer</dt><dd class="mt-1 text-muted-foreground">' + esc(offer.title) + '</dd></div>' : '')
            + '<div><dt class="font-medium">Artist</dt><dd class="mt-1 text-muted-foreground">' + esc(artistName()) + '</dd></div>'
            + '<div><dt class="font-medium">Date & time</dt><dd class="mt-1 text-muted-foreground">'
            + (state.date ? esc(longDate(state.date) + (state.time ? ' at ' + timeLabel(state.time) : '')) : 'Not chosen yet') + '</dd></div>'
            + '<div><dt class="font-medium">Time needed</dt><dd class="mt-1 text-muted-foreground">' + (chosen.length ? duration(totalMinutes()) : '—') + '</dd></div>'
            + '</dl>'
            + '<div class="mt-6 flex items-baseline justify-between border-t border-rose-200 pt-5">'
            + '<span class="font-medium">Estimated total</span>'
            + '<span class="font-display text-2xl">' + (total !== null ? price(total) : '—') + '</span>'
            + '</div>'
            + (config.notes.pricing ? '<p class="mt-2 text-sm text-muted-foreground">' + esc(config.notes.pricing) + '</p>' : '')
            + '</aside>'
            + (help.phoneHref
                ? '<p class="mt-6 rounded-lg bg-lilac px-4 py-3 text-sm">' + esc(help.text)
                    + ' <a class="font-semibold text-primary-ink" href="' + esc(help.phoneHref) + '">' + esc(help.phone) + '</a></p>'
                    + '<a href="' + esc(help.phoneHref) + '" class="' + QUIET_BUTTON + ' mt-4 w-full"><i class="fa-solid fa-phone" aria-hidden="true"></i>Call the salon</a>'
                : '');
    }

    function canContinue() {
        if (state.step === 0) {
            return state.serviceSlugs.length > 0;
        }
        if (state.step === 2) {
            return Boolean(state.date && state.time);
        }

        return true;
    }

    function currentStep() {
        return [serviceStep, artistStep, dateTimeStep, detailsStep, reviewStep][state.step]();
    }

    function render() {
        var last = state.step === STEPS.length - 1;
        var continueLabel = last
            ? (state.sending ? 'Booking…' : 'Confirm booking')
            : 'Continue <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>';
        var blocked = !canContinue()
            ? '<p class="mt-4 text-sm text-muted-foreground">'
                + (state.step === 0 ? 'Choose at least one service to continue.' : 'Choose a day and a time to continue.') + '</p>'
            : '';

        root.innerHTML = progress()
            + '<div class="mt-10 grid gap-10 lg:grid-cols-12 lg:gap-14">'
            + '<div class="min-w-0 lg:col-span-7 xl:col-span-8">'
            + summaryBar()
            + (state.alert ? '<p role="alert" class="mb-6 rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">' + esc(state.alert) + '</p>' : '')
            + '<h2 tabindex="-1" class="text-[clamp(1.6rem,3.4vw,2.25rem)]">' + STEPS[state.step].label + '</h2>'
            + '<div class="mt-6">' + currentStep() + '</div>'
            + '<div class="mt-10 flex flex-col-reverse gap-3 border-t border-border pt-6 sm:flex-row sm:justify-between">'
            + '<button type="button" data-back class="' + QUIET_BUTTON + '"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>'
            + (state.step ? 'Back' : 'Browse services') + '</button>'
            + '<button type="button" data-continue ' + (canContinue() && !state.sending ? '' : 'disabled') + ' class="' + PRIMARY_BUTTON + '">'
            + continueLabel + '</button>'
            + '</div>'
            + blocked
            + '</div>'
            + '<div class="lg:col-span-5 xl:col-span-4"><div class="lg:sticky lg:top-28">' + summary() + '</div></div>'
            + '</div>';
    }

    function goToStep(next) {
        state.step = next;
        if (next === 2) {
            loadAvailability(false);
        }
        render();
        window.requestAnimationFrame(function () {
            var heading = root.querySelector('h2[tabindex="-1"]');
            if (heading) {
                heading.focus({ preventScroll: true });
                heading.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
        });
    }

    /* Validation and sending --------------------------------------------- */

    function validateDetails() {
        var details = state.details;
        var phone = details.phone.trim();
        var email = details.email.trim();

        state.errors = {};
        if (!details.name.trim()) {
            state.errors.name = 'Please tell us your name.';
        }
        if (!phone || !PHONE.test(phone) || phone.replace(/[^0-9]/g, '').length < 6) {
            state.errors.phone = 'We need a phone number to confirm.';
        }
        if (!email) {
            state.errors.email = 'Please add an email address.';
        } else if (!EMAIL.test(email)) {
            state.errors.email = 'That email doesn’t look quite right.';
        }

        return Object.keys(state.errors).length === 0;
    }

    function recaptchaToken() {
        var siteKey = config.recaptcha.siteKey;

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
                window.grecaptcha.enterprise.execute(siteKey, { action: config.recaptcha.action }).then(resolve, reject);
            });
        });
    }

    function fail(message, step) {
        state.sending = false;
        state.alert = message;
        goToStep(typeof step === 'number' ? step : state.step);
    }

    function send() {
        state.sending = true;
        state.alert = '';
        render();

        recaptchaToken()
            .then(function (token) {
                var body = new URLSearchParams();

                body.append('form_token', config.formToken);
                body.append('recaptcha_token', token);
                state.serviceSlugs.forEach(function (slug) {
                    body.append('services[]', slug);
                });
                body.append('artist', state.artistSlug);
                body.append('offer', state.offerSlug);
                body.append('date', state.date);
                body.append('time', state.time);
                Object.keys(state.details).forEach(function (key) {
                    body.append(key, state.details[key]);
                });

                return fetch(config.submitUrl, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
                });
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (result.formToken) {
                    config.formToken = result.formToken;
                }
                if (result.success) {
                    window.location.href = result.redirect;
                    return;
                }
                state.errors = result.errors || {};
                if (state.errors.datetime) {
                    state.time = '';
                    state.availability.key = '';
                }
                fail(result.message || 'Your booking could not be saved. Please try again, or call us.', result.step);
            })
            .catch(function () {
                fail('Your booking could not be saved. Please check your connection and try again, or call us.');
            });
    }

    /* Events --------------------------------------------------------------- */

    function onClick(event) {
        var target = event.target.closest('button');
        var slug;
        var index;

        if (!target || target.disabled || state.sending) {
            return;
        }

        if (target.dataset.group) {
            state.group = target.dataset.group;
            render();
        } else if (target.dataset.service) {
            slug = target.dataset.service;
            index = state.serviceSlugs.indexOf(slug);
            if (index < 0) {
                state.serviceSlugs.push(slug);
            } else {
                state.serviceSlugs.splice(index, 1);
            }
            syncOffer();
            syncArtist();
            render();
        } else if (target.dataset.artist) {
            state.artistSlug = target.dataset.artist;
            render();
        } else if (target.hasAttribute('data-reload-times')) {
            loadAvailability(true);
            render();
        } else if (target.dataset.date) {
            state.date = target.dataset.date;
            state.time = '';
            delete state.errors.datetime;
            render();
        } else if (target.dataset.time) {
            state.time = target.dataset.time;
            delete state.errors.datetime;
            render();
        } else if (target.dataset.step !== undefined) {
            goToStep(Number(target.dataset.step));
        } else if (target.hasAttribute('data-back')) {
            state.alert = '';
            if (state.step === 0) {
                window.location.href = config.servicesUrl;
            } else {
                goToStep(state.step - 1);
            }
        } else if (target.hasAttribute('data-continue')) {
            state.alert = '';
            if (state.step === 3 && !validateDetails()) {
                render();
                var invalid = root.querySelector('[aria-invalid="true"]');
                if (invalid) {
                    invalid.focus();
                }
                return;
            }
            if (state.step === STEPS.length - 1) {
                send();
            } else {
                goToStep(state.step + 1);
            }
        }
    }

    function onInput(event) {
        var key = event.target.getAttribute('data-detail');
        if (key) {
            state.details[key] = event.target.value;
        }
    }

    /** Apply ?service=, ?offer= and ?artist= from the address. */
    function preselect() {
        var params = new URLSearchParams(window.location.search);
        var service = findService(params.get('service') || '');
        var offer = config.catalogue.offers.find(function (item) {
            return item.slug === params.get('offer');
        });
        var artist = config.catalogue.artists.find(function (item) {
            return item.slug === params.get('artist');
        });

        if (service) {
            state.serviceSlugs.push(service.slug);
        }
        if (offer) {
            offer.services.forEach(function (slug) {
                if (findService(slug) && state.serviceSlugs.indexOf(slug) === -1) {
                    state.serviceSlugs.push(slug);
                }
            });
            state.offerSlug = offer.services.length ? offer.slug : '';
        }
        if (artist) {
            state.artistSlug = artist.slug;
            syncArtist();
        }

        // Open the group of the first chosen service.
        state.group = config.catalogue.groups.length ? config.catalogue.groups[0].slug : '';
        config.catalogue.groups.some(function (group) {
            if (state.serviceSlugs.length && group.services.some(function (item) {
                return item.slug === state.serviceSlugs[0];
            })) {
                state.group = group.slug;
                return true;
            }
            return false;
        });
    }

    $(function () {
        var data = document.getElementById('booking-config');

        root = document.querySelector('[data-booking-root]');
        if (!root || !data) {
            return;
        }

        config = JSON.parse(data.textContent);
        config.catalogue.groups.forEach(function (group) {
            services = services.concat(group.services);
        });
        if (!services.length) {
            root.innerHTML = '<p class="mt-10 max-w-2xl rounded-lg bg-lilac px-5 py-4 text-foreground-soft">Services will be listed here soon.</p>';
            return;
        }

        preselect();
        render();

        root.addEventListener('click', onClick);
        root.addEventListener('input', onInput);
        root.addEventListener('change', onInput);
    });
})(jQuery);
