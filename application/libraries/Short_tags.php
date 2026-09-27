<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Short tags available to an email or WhatsApp notification template.
 *
 * Email and WhatsApp templates for the same notification share the template ID
 * the code sends, so both use the same entity's tags. Tag names come from
 * EmailService::shortTagFields(), the list the emails are actually filled from;
 * config/short_tags.php only adds labels, example values and the template map.
 */
class Short_tags
{
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_WHATSAPP = 'whatsapp';

    private $CI;
    private $loaded = false;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('EmailService');
    }

    /**
     * Tags for a template with their example values, e.g. array('book_name' => 'Ahmed Khan').
     * Emails also get the entity's HTML tags; WhatsApp templates cannot use them.
     */
    public function forTemplate($templateId, $channel = self::CHANNEL_EMAIL)
    {
        $entityKey = $this->entityKey($templateId);
        if ($entityKey === '') {
            return array();
        }

        $names = $this->CI->emailservice->shortTagFields($entityKey);
        if ($channel !== self::CHANNEL_WHATSAPP) {
            $names = array_merge($names, $this->CI->emailservice->htmlShortTags($entityKey));
        }

        $entity = $this->entity($entityKey);
        $examples = isset($entity['examples']) && is_array($entity['examples'])
            ? $entity['examples']
            : array();

        $tags = array();
        foreach ($names as $name) {
            $tags[$name] = isset($examples[$name]) && (string) $examples[$name] !== ''
                ? (string) $examples[$name]
                : ucfirst(str_replace('_', ' ', $name));
        }

        return $tags;
    }

    /** Display name of the entity a template's tags come from, e.g. "Contact request". */
    public function entityLabel($templateId)
    {
        $entity = $this->entity($this->entityKey($templateId));

        return isset($entity['label']) ? (string) $entity['label'] : '';
    }

    private function entityKey($templateId)
    {
        $map = $this->setting('short_tag_template_entities');
        $templateId = (int) $templateId;

        return isset($map[$templateId]) ? (string) $map[$templateId] : '';
    }

    private function entity($entityKey)
    {
        $entities = $this->setting('short_tag_entities');

        return isset($entities[$entityKey]) && is_array($entities[$entityKey])
            ? $entities[$entityKey]
            : array();
    }

    private function setting($key)
    {
        if (!$this->loaded) {
            $this->CI->config->load('short_tags', true);
            $this->loaded = true;
        }
        $value = $this->CI->config->item($key, 'short_tags');

        return is_array($value) ? $value : array();
    }
}
