<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Short tags available to an email notification template.
 *
 * Tag names come from EmailService::shortTagFields(), the list the emails are
 * actually filled from; config/short_tags.php only adds labels, example values
 * and the template map.
 */
class Short_tags
{
    private $CI;
    private $loaded = false;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('EmailService');
    }

    /**
     * Tags for a template with their example values, e.g. array('first_name' => 'Anna'),
     * including the entity's HTML tags.
     */
    public function forTemplate($templateId)
    {
        $entityKey = $this->entityKey($templateId);
        if ($entityKey === '') {
            return array();
        }

        $names = array_merge(
            $this->CI->emailservice->shortTagFields($entityKey),
            $this->CI->emailservice->htmlShortTags($entityKey)
        );

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
