<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Contact (/contact): hero with call / directions / booking actions, the
 * salon details with a map card, and the contact form (libraries/
 * Contact_form.php, js/contact.js).
 *
 * $hero       page hero data (see partials/page_hero.php)
 * $sections   Web Page Sections of the Contact page, keyed by section
 * $mapUrl     directions link (Website Settings > map link, or a search)
 * $form       'subjects' (value => label) and 'success_message'
 * $formToken  one-use form token
 * $status     result of the last submission: '', 'success', 'invalid',
 *             'expired', 'recaptcha' or 'error'
 * $errors     field => message after server-side validation
 * $values     the submitted values to show again
 * $recaptcha  'siteKey' (empty when not configured) and 'action'
 */
$details = isset($sections['details']) ? $sections['details'] : array();
$formSection = isset($sections['form']) ? $sections['form'] : array();
$value = function (array $fields, $key, $default = '') {
    return isset($fields[$key]) && trim((string) $fields[$key]) !== ''
        ? $fields[$key]
        : $default;
};
$old = function ($field, $default = '') use ($values) {
    return isset($values[$field]) ? (string) $values[$field] : $default;
};
$statusMessages = array(
    'success' => $form['success_message'],
    'invalid' => 'Please check the highlighted fields.',
    'expired' => 'The form expired before it was sent. Please send your message again.',
    'recaptcha' => 'We couldn’t confirm the message was sent by a person. Please try again, or call us.',
    'error' => 'Your message could not be sent. Please try again, or call us.',
);
$inputClass = 'w-full rounded-md border bg-background px-4 text-[1rem] text-foreground placeholder:text-muted-foreground/70'
    .' transition-[border-color,box-shadow] duration-200 ease-[var(--ease-out-soft)]'
    .' focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/15'
    .' disabled:cursor-not-allowed disabled:bg-muted disabled:opacity-70';
$borderClass = function ($field) use ($errors) {
    return isset($errors[$field]) ? 'border-destructive' : 'border-border-strong';
};
$invalidAttributes = function ($field, $hintId = '') use ($errors) {
    $describedBy = trim($hintId.(isset($errors[$field]) ? ' contact-'.$field.'-error' : ''));

    return (isset($errors[$field]) ? ' aria-invalid="true"' : '')
        .($describedBy !== '' ? ' aria-describedby="'.$describedBy.'"' : '');
};
$fieldError = function ($field) use ($errors) {
    return isset($errors[$field])
        ? '<p class="ci-field-error mt-1 text-sm text-destructive" id="contact-'.$field.'-error">'.html_escape($errors[$field]).'</p>'
        : '';
};
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-labelledby="details-heading">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                <div class="reveal lg:col-span-5">
                    <h2 id="details-heading" class="text-[clamp(1.5rem,3.2vw,2rem)]"><?php echo html_escape($value($details, 'heading', 'Where to find us')); ?></h2>
                    <?php $this->load->view('frontend/partials/visit_details', array('spacing' => 'mt-6')); ?>
                </div>
                <div class="reveal lg:col-span-7" style="transition-delay:80ms">
                    <div class="card-soft relative h-full min-h-80 overflow-hidden">
                        <div aria-hidden="true" class="absolute inset-0 opacity-70 [background-image:linear-gradient(to_right,var(--color-border)_1px,transparent_1px),linear-gradient(to_bottom,var(--color-border)_1px,transparent_1px)] [background-size:44px_44px]"></div>
                        <div class="relative flex h-full flex-col items-center justify-center gap-3 p-8 text-center">
                            <i class="fa-solid fa-location-dot size-8 text-primary" aria-hidden="true"></i>
                            <?php if (!empty($site['address_lines'])) { ?>
                                <p class="font-display text-2xl text-foreground"><?php echo html_escape($site['address_lines'][0]); ?></p>
                            <?php } ?>
                            <?php
                            $mapLine = array_filter(array(
                                isset($site['address_lines'][1]) ? $site['address_lines'][1] : '',
                                $site['address_note'] !== '' ? lcfirst($site['address_note']) : '',
                            ));
                            ?>
                            <?php if (!empty($mapLine)) { ?>
                                <p class="max-w-xs text-muted-foreground"><?php echo html_escape(implode(' — ', $mapLine)); ?></p>
                            <?php } ?>
                            <a
                                href="<?php echo html_escape($mapUrl); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7 mt-2')); ?>"
                            >
                                <i class="fa-solid fa-location-arrow size-4" aria-hidden="true"></i>
                                <?php echo html_escape($value($details, 'map_button_text', 'Open in Maps')); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" aria-labelledby="form-heading" id="contact-form">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                <div class="reveal lg:col-span-4">
                    <h2 id="form-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($value($formSection, 'heading', 'Send us a message')); ?></h2>
                    <?php foreach (preg_split('/\R\s*\R/', trim($value($formSection, 'contents'))) as $paragraph) { ?>
                        <?php if (trim($paragraph) !== '') { ?>
                            <?php
                            // "[text]" links to the booking page.
                            $paragraph = preg_replace(
                                '/\[([^\]]+)\]/',
                                '<a class="link-underline text-primary-ink" href="'.html_escape($site['book_url']).'">$1</a>',
                                html_escape(trim($paragraph))
                            );
                            ?>
                            <p class="mt-5 leading-relaxed text-muted-foreground"><?php echo $paragraph; ?></p>
                        <?php } ?>
                    <?php } ?>
                </div>
                <div class="reveal lg:col-span-8" style="transition-delay:80ms">
                    <div class="rounded-xl bg-background p-6 shadow-[var(--shadow-card)] sm:p-8">
                        <?php if (isset($statusMessages[$status])) { ?>
                            <?php $isSuccess = $status === 'success'; ?>
                            <div
                                id="contact-form-status"
                                role="<?php echo $isSuccess ? 'status' : 'alert'; ?>"
                                tabindex="-1"
                                class="mb-6 flex items-start gap-3 rounded-lg px-5 py-4 <?php echo $isSuccess ? 'bg-petal text-foreground' : 'bg-destructive/10 text-destructive'; ?>"
                            >
                                <i class="<?php echo $isSuccess ? 'fa-solid fa-circle-check text-primary' : 'fa-solid fa-circle-exclamation'; ?> mt-1 size-4 shrink-0" aria-hidden="true"></i>
                                <p><?php echo html_escape($statusMessages[$status]); ?></p>
                            </div>
                        <?php } ?>
                        <form
                            method="post"
                            action="<?php echo html_escape(base_url('contact')); ?>"
                            novalidate
                            class="grid gap-6 sm:grid-cols-2"
                            data-contact-form
                            data-recaptcha-site-key="<?php echo html_escape($recaptcha['siteKey']); ?>"
                            data-recaptcha-action="<?php echo html_escape($recaptcha['action']); ?>"
                        >
                            <input type="hidden" name="form_token" value="<?php echo html_escape($formToken); ?>">
                            <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
                            <div>
                                <label class="block text-sm font-semibold text-foreground" for="contact-name">Name<span class="ml-1 text-primary" aria-hidden="true">*</span></label>
                                <div class="mt-2">
                                    <input
                                        class="<?php echo $inputClass; ?> h-12 <?php echo $borderClass('name'); ?>"
                                        id="contact-name"
                                        autocomplete="name"
                                        placeholder="Anna Kowalska"
                                        name="name"
                                        maxlength="255"
                                        required
                                        value="<?php echo html_escape($old('name')); ?>"
                                        <?php echo $invalidAttributes('name'); ?>
                                    >
                                    <?php echo $fieldError('name'); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-foreground" for="contact-email">Email<span class="ml-1 text-primary" aria-hidden="true">*</span></label>
                                <div class="mt-2">
                                    <input
                                        class="<?php echo $inputClass; ?> h-12 <?php echo $borderClass('email'); ?>"
                                        id="contact-email"
                                        type="email"
                                        autocomplete="email"
                                        placeholder="anna@example.com"
                                        name="email"
                                        maxlength="100"
                                        required
                                        value="<?php echo html_escape($old('email')); ?>"
                                        <?php echo $invalidAttributes('email'); ?>
                                    >
                                    <?php echo $fieldError('email'); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-foreground" for="contact-phone">Phone</label>
                                <p class="mt-1.5 text-sm text-muted-foreground" id="contact-phone-hint">Optional — handy if you’d rather we call.</p>
                                <div class="mt-2">
                                    <input
                                        class="<?php echo $inputClass; ?> h-12 <?php echo $borderClass('phone'); ?>"
                                        id="contact-phone"
                                        type="tel"
                                        autocomplete="tel"
                                        placeholder="+48 500 000 000"
                                        name="phone"
                                        maxlength="50"
                                        value="<?php echo html_escape($old('phone')); ?>"
                                        <?php echo $invalidAttributes('phone', 'contact-phone-hint'); ?>
                                    >
                                    <?php echo $fieldError('phone'); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-foreground" for="contact-subject">Subject</label>
                                <div class="mt-2">
                                    <select
                                        class="<?php echo $inputClass; ?> h-12 cursor-pointer appearance-none pr-10 select-chevron <?php echo $borderClass('subject'); ?>"
                                        id="contact-subject"
                                        name="subject"
                                        <?php echo $invalidAttributes('subject'); ?>
                                    >
                                        <?php $selectedSubject = $old('subject', (string) key($form['subjects'])); ?>
                                        <?php foreach ($form['subjects'] as $subjectValue => $subjectLabel) { ?>
                                            <option
                                                value="<?php echo html_escape($subjectValue); ?>"
                                                <?php echo $selectedSubject === (string) $subjectValue ? 'selected' : ''; ?>
                                            ><?php echo html_escape($subjectLabel); ?></option>
                                        <?php } ?>
                                    </select>
                                    <?php echo $fieldError('subject'); ?>
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-foreground" for="contact-message">Message<span class="ml-1 text-primary" aria-hidden="true">*</span></label>
                                <div class="mt-2">
                                    <textarea
                                        class="<?php echo $inputClass; ?> min-h-32 py-3 leading-relaxed <?php echo $borderClass('message'); ?>"
                                        id="contact-message"
                                        name="message"
                                        maxlength="5000"
                                        required
                                        placeholder="Hello — I’d like to book a gel manicure with some art, ideally on a Saturday morning."
                                        <?php echo $invalidAttributes('message'); ?>
                                    ><?php echo html_escape($old('message')); ?></textarea>
                                    <?php echo $fieldError('message'); ?>
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <button class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>" type="submit" data-contact-submit>
                                    <?php echo html_escape($value($formSection, 'button_text', 'Send message')); ?>
                                </button>
                                <?php if ($value($formSection, 'note') !== '') { ?>
                                    <p class="mt-4 text-sm text-muted-foreground"><?php echo html_escape($formSection['note']); ?></p>
                                <?php } ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php if ($recaptcha['siteKey'] !== '') { ?>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($recaptcha['siteKey']); ?>" defer></script>
<?php } ?>
