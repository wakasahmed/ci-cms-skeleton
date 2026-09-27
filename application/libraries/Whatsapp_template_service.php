<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rules and Meta synchronisation for managed WhatsApp templates.
 *
 * Each whatsapp_templates row holds an English and an optional Arabic body.
 * Meta stores them as two language versions of one template name and reviews
 * each one separately, so every language keeps its own Meta ID, review status,
 * rejection reason and the body that was last accepted for review.
 */
class Whatsapp_template_service
{
    const TABLE = 'whatsapp_templates';
    const BODY_MAX_LENGTH = 1024;

    /** Local language key => Meta language code. */
    const LANGUAGES = array(
        'en' => 'en',
        'ar' => 'ar',
    );

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('Whatsapp_gateway');
        $this->CI->load->library('Short_tags');
    }

    public function languageLabel($language)
    {
        return $language === 'ar' ? 'Arabic' : 'English';
    }

    /**
     * Short tags available to one template, with their example values, e.g.
     * array('book_name' => 'Ahmed Khan'). The template ID matches the email
     * template the code sends for the same notification.
     */
    public function tagsFor($templateId)
    {
        return $this->CI->short_tags->forTemplate($templateId, Short_tags::CHANNEL_WHATSAPP);
    }

    /** Short tags used in a body, in order of appearance. */
    public function bodyTags($body)
    {
        preg_match_all('/\{\{([^{}]*)\}\}/', (string) $body, $matches);

        return $matches[1];
    }

    /**
     * Check a body against the allowed short tags and Meta's placement rules.
     * Returns an error message, or an empty string when the body is valid.
     */
    public function bodyError($body, $languageLabel, $templateId)
    {
        $body = (string) $body;
        if (trim($body) === '') {
            return 'Enter the ' . $languageLabel . ' message.';
        }
        if (mb_strlen($body, 'UTF-8') > self::BODY_MAX_LENGTH) {
            return 'The ' . $languageLabel . ' message must not exceed '
                . self::BODY_MAX_LENGTH . ' characters.';
        }

        $withoutTags = preg_replace('/\{\{[^{}]*\}\}/', '', $body);
        if (strpos($withoutTags, '{{') !== false || strpos($withoutTags, '}}') !== false) {
            return 'The ' . $languageLabel . ' message has an incomplete short tag. '
                . 'Use the {{tag_name}} format.';
        }

        $examples = $this->tagsFor($templateId);
        $tags = $this->bodyTags($body);
        foreach ($tags as $tag) {
            if (!array_key_exists($tag, $examples)) {
                return 'The ' . $languageLabel . ' message uses {{' . $tag
                    . '}}, which is not available for this notification.';
            }
        }
        if (count($tags) !== count(array_unique($tags))) {
            return 'The ' . $languageLabel . ' message may use each short tag only once.';
        }

        $trimmed = trim($body);
        if (!empty($tags) && (strpos($trimmed, '{{') === 0 || substr($trimmed, -2) === '}}')) {
            return 'The ' . $languageLabel . ' message cannot start or end with a short tag.';
        }
        if (preg_match('/\}\}\s*\{\{/', $body) === 1) {
            return 'The ' . $languageLabel . ' message cannot place two short tags next to each other. '
                . 'Add some text between them.';
        }

        return '';
    }

    /** Whether a language version is in review and therefore cannot be edited. */
    public function isLocked(array $record, $language)
    {
        return $this->field($record, $language, 'meta_status') === 'PENDING';
    }

    /** Whether the saved body differs from the body Meta last accepted for review. */
    public function hasUnsubmittedChanges(array $record, $language)
    {
        $body = (string) $this->value($record, 'wt_body_' . $language);
        if (trim($body) === '') {
            return false;
        }

        return $this->field($record, $language, 'meta_id') === ''
            || $body !== (string) $this->field($record, $language, 'submitted_body');
    }

    /** Name and category are fixed once any language has been accepted by Meta. */
    public function isSubmitted(array $record)
    {
        foreach (array_keys(self::LANGUAGES) as $language) {
            if ($this->field($record, $language, 'meta_id') !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Submit every language whose saved body has not been accepted by Meta yet:
     * new languages are created, changed ones are edited. Returns error messages.
     */
    public function submit($recordId)
    {
        $errors = array();
        foreach (array_keys(self::LANGUAGES) as $language) {
            $record = $this->record($recordId);
            if (empty($record)) {
                return array('The WhatsApp template no longer exists.');
            }
            if (!$this->hasUnsubmittedChanges($record, $language) || $this->isLocked($record, $language)) {
                continue;
            }

            $error = $this->submitLanguage($record, $language);
            if ($error !== '') {
                $errors[] = $this->languageLabel($language) . ': ' . $error;
            }
        }

        return $errors;
    }

    /**
     * Read the current review status of every language from Meta. A version that
     * already exists on Meta (for example, one created in WhatsApp Manager) is linked
     * to the record. Returns an error message, or an empty string on success.
     */
    public function refresh($recordId)
    {
        $record = $this->record($recordId);
        if (empty($record)) {
            return 'The WhatsApp template no longer exists.';
        }

        $result = $this->CI->whatsapp_gateway->templatesByName($record['wt_name']);
        if (empty($result['success'])) {
            return $result['error'];
        }

        $languages = array_flip(self::LANGUAGES);
        foreach ($result['data'] as $template) {
            $metaLanguage = isset($template['language']) ? (string) $template['language'] : '';
            if (!isset($languages[$metaLanguage], $template['id'])) {
                continue;
            }

            $language = $languages[$metaLanguage];
            $update = array(
                'wt_' . $language . '_meta_id' => substr((string) $template['id'], 0, 64),
                'wt_' . $language . '_meta_status' => $this->cleanStatus(
                    isset($template['status']) ? $template['status'] : ''
                ),
            );
            $metaBody = $this->metaBody($template);
            if ($metaBody !== null) {
                $update['wt_' . $language . '_submitted_body'] = $metaBody;
            }
            if ($update['wt_' . $language . '_meta_status'] !== 'REJECTED') {
                $update['wt_' . $language . '_meta_reason'] = '';
            }
            $this->CI->SqlModel->updateRecord(self::TABLE, $update, array('wt_id' => (int) $recordId));
        }

        return '';
    }

    /**
     * Send an approved template to one number with each short tag filled by its
     * example value, for testing and Meta review recordings. Uses the body Meta
     * approved, not any unsubmitted edits. Returns a Whatsapp_gateway result array.
     */
    public function sendSample($to, $templateId, $language)
    {
        if (!isset(self::LANGUAGES[$language])) {
            return $this->sampleFailure('Unknown language. Use en or ar.');
        }

        $record = $this->record($templateId);
        if (empty($record)) {
            return $this->sampleFailure('WhatsApp template ' . (int) $templateId . ' was not found.');
        }

        $status = $this->field($record, $language, 'meta_status');
        if ($status !== 'APPROVED') {
            $statusMeta = $this->statusMeta($status);

            return $this->sampleFailure(
                'The ' . $this->languageLabel($language) . ' version of "' . $record['wt_name']
                . '" is not approved by Meta (status: ' . $statusMeta['label'] . ').'
            );
        }

        $examples = $this->tagsFor($record['wt_id']);
        $parameters = array();
        foreach ($this->bodyTags($this->field($record, $language, 'submitted_body')) as $tag) {
            $value = isset($examples[$tag]) ? $examples[$tag] : $tag;
            $parameters[] = array(
                'type' => 'text',
                'parameter_name' => $tag,
                // Meta rejects parameter values containing line breaks or tabs.
                'text' => trim(preg_replace('/\s+/', ' ', (string) $value)),
            );
        }

        $components = empty($parameters)
            ? array()
            : array(
                array(
                    'type' => 'body',
                    'parameters' => $parameters,
                ),
            );

        return $this->CI->whatsapp_gateway->sendTemplate(
            $to,
            $record['wt_name'],
            self::LANGUAGES[$language],
            $components
        );
    }

    /** Apply a message_template_status_update webhook event. */
    public function applyStatusEvent($metaId, $event, $reason)
    {
        $metaId = (string) $metaId;
        if (preg_match('/^\d{1,30}$/D', $metaId) !== 1) {
            return false;
        }

        $status = $this->cleanStatus($event);
        $reason = strtoupper((string) $reason) === 'NONE' ? '' : (string) $reason;
        foreach (array_keys(self::LANGUAGES) as $language) {
            $record = $this->CI->SqlModel->getSingleRecord(
                self::TABLE,
                array('wt_' . $language . '_meta_id' => $metaId)
            );
            if (empty($record)) {
                continue;
            }

            return (bool) $this->CI->SqlModel->updateRecord(
                self::TABLE,
                array(
                    'wt_' . $language . '_meta_status' => $status,
                    'wt_' . $language . '_meta_reason' => $status === 'APPROVED'
                        ? ''
                        : mb_substr($reason, 0, 500, 'UTF-8'),
                ),
                array('wt_id' => (int) $record['wt_id'])
            );
        }

        return false;
    }

    /** Badge label and CSS class for a Meta review status. */
    public function statusMeta($status)
    {
        $statuses = array(
            'NOT_SUBMITTED' => array('label' => 'Not submitted', 'class' => 'translation-status-missing'),
            'PENDING' => array('label' => 'In review', 'class' => 'translation-status-pending'),
            'APPROVED' => array('label' => 'Approved', 'class' => 'translation-status-succeeded status-enabled'),
            'REJECTED' => array('label' => 'Rejected', 'class' => 'translation-status-failed'),
            'PAUSED' => array('label' => 'Paused', 'class' => 'translation-status-pending'),
            'DISABLED' => array('label' => 'Disabled by Meta', 'class' => 'translation-status-failed'),
        );

        if (isset($statuses[$status])) {
            return $statuses[$status];
        }

        return array(
            'label' => ucfirst(strtolower(str_replace('_', ' ', (string) $status))),
            'class' => 'translation-status-missing',
        );
    }

    private function submitLanguage(array $record, $language)
    {
        $body = (string) $record['wt_body_' . $language];
        $examples = array();
        $allowed = $this->tagsFor($record['wt_id']);
        foreach ($this->bodyTags($body) as $tag) {
            if (!isset($allowed[$tag])) {
                return 'The message uses {{' . $tag . '}}, which is not available for this notification.';
            }
            $examples[$tag] = $allowed[$tag];
        }

        $metaId = $this->field($record, $language, 'meta_id');
        $result = $metaId === ''
            ? $this->CI->whatsapp_gateway->createTemplate(
                $record['wt_name'],
                self::LANGUAGES[$language],
                $record['wt_category'],
                $body,
                $examples
            )
            : $this->CI->whatsapp_gateway->editTemplate($metaId, $body, $examples);

        $prefix = 'wt_' . $language . '_';
        if (empty($result['success'])) {
            $this->CI->SqlModel->updateRecord(
                self::TABLE,
                array($prefix . 'meta_reason' => mb_substr($result['error'], 0, 500, 'UTF-8')),
                array('wt_id' => (int) $record['wt_id'])
            );

            return $result['error'];
        }

        $update = array(
            $prefix . 'meta_status' => 'PENDING',
            $prefix . 'meta_reason' => '',
            $prefix . 'submitted_body' => $body,
        );
        if ($metaId === '') {
            $update[$prefix . 'meta_id'] = isset($result['data']['id'])
                ? substr((string) $result['data']['id'], 0, 64)
                : '';
            if (isset($result['data']['status'])) {
                $update[$prefix . 'meta_status'] = $this->cleanStatus($result['data']['status']);
            }
        }
        $this->CI->SqlModel->updateRecord(self::TABLE, $update, array('wt_id' => (int) $record['wt_id']));

        return '';
    }

    private function metaBody(array $template)
    {
        if (empty($template['components']) || !is_array($template['components'])) {
            return null;
        }

        foreach ($template['components'] as $component) {
            if (isset($component['type'], $component['text']) && strtoupper($component['type']) === 'BODY') {
                return (string) $component['text'];
            }
        }

        return null;
    }

    private function cleanStatus($status)
    {
        $status = strtoupper(preg_replace('/[^A-Za-z_]/', '', (string) $status));

        return $status === '' ? 'NOT_SUBMITTED' : substr($status, 0, 30);
    }

    private function sampleFailure($message)
    {
        return array(
            'success' => false,
            'status_code' => 0,
            'data' => array(),
            'error' => $message,
        );
    }

    private function record($recordId)
    {
        return $this->CI->SqlModel->getSingleRecord(self::TABLE, array('wt_id' => (int) $recordId));
    }

    private function field(array $record, $language, $name)
    {
        return (string) $this->value($record, 'wt_' . $language . '_' . $name);
    }

    private function value(array $record, $key)
    {
        return isset($record[$key]) ? $record[$key] : '';
    }
}
