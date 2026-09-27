<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central registry and persistence service for drag-and-drop admin listings.
 *
 * Only modules declared here can be reordered. Table and column names are
 * never accepted from the request.
 */
class Admin_record_sorter
{
	private $CI;

	private $modules = array(
		'service-categories' => array(
			'table' => 'service_categories',
			'primary_key' => 'category_id',
			'order_column' => 'category_order',
			'fallback_column' => 'category_name',
		),
		'services' => array(
			'table' => 'services',
			'primary_key' => 'service_id',
			'order_column' => 'service_order',
			'fallback_column' => 'service_name',
		),
		'artists' => array(
			'table' => 'artists',
			'primary_key' => 'artist_id',
			'order_column' => 'artist_order',
			'fallback_column' => 'artist_name',
		),
		'gallery-categories' => array(
			'table' => 'gallery_categories',
			'primary_key' => 'category_id',
			'order_column' => 'category_order',
			'fallback_column' => 'category_name',
		),
		'gallery' => array(
			'table' => 'gallery_images',
			'primary_key' => 'image_id',
			'order_column' => 'image_order',
			'fallback_column' => 'image_id',
		),
		'offers' => array(
			'table' => 'offers',
			'primary_key' => 'offer_id',
			'order_column' => 'offer_order',
			'fallback_column' => 'offer_title',
		),
		'customer-reviews' => array(
			'table' => 'customer_reviews',
			'primary_key' => 'review_id',
			'order_column' => 'review_order',
			'fallback_column' => 'review_name',
		),
		'tour-slots' => array(
			'table' => 'tour_slots',
			'primary_key' => 'slot_id',
			'order_column' => 'slot_order',
			'fallback_column' => 'slot_name',
		),
		'vehicles' => array(
			'table' => 'vehicles',
			'primary_key' => 'vehicle_id',
			'order_column' => 'vehicle_order',
			'fallback_column' => 'vehicle_name',
		),
		'tour-languages' => array(
			'table' => 'tour_languages',
			'primary_key' => 'lang_id',
			'order_column' => 'lang_order',
			'fallback_column' => 'lang_id',
		),
		'tour-guides' => array(
			'table' => 'tour_guides',
			'primary_key' => 'tour_guide_id',
			'order_column' => 'tour_guide_order',
			'fallback_column' => 'tour_guide_name',
		),
		'tour-categories' => array(
			'table' => 'tour_categories',
			'primary_key' => 'cat_id',
			'order_column' => 'cat_order',
			'fallback_column' => 'cat_name',
		),
		'faqs-categories' => array(
			'table' => 'faqs_categories',
			'primary_key' => 'cat_id',
			'order_column' => 'cat_order',
			'fallback_column' => 'cat_name',
		),
		'faqs' => array(
			'table' => 'faqs',
			'primary_key' => 'faq_id',
			'order_column' => 'faq_order',
			'fallback_column' => 'faq_question',
		),
		'tours' => array(
			'table' => 'tours',
			'primary_key' => 'tour_id',
			'order_column' => 'tour_order',
			'fallback_column' => 'tour_name',
			'scope_column' => 'tour_type',
			'scope_values' => array('Tour', 'Experience'),
		),
		'tour-itineraries' => array(
			'table' => 'tour_itineraries',
			'primary_key' => 'id',
			'order_column' => 'itinerary_order',
			'fallback_column' => 'title',
			'scope_column' => 'tour_id',
			'scope_required' => TRUE,
		),
		'tour-images' => array(
			'table' => 'tour_images',
			'primary_key' => 'image_id',
			'order_column' => 'image_order',
			'fallback_column' => 'image_name',
			'scope_column' => 'image_tour_id',
			'scope_required' => TRUE,
		),
	);

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	public function supports($module)
	{
		return isset($this->modules[$module]);
	}

	public function nextOrder($module, $scopeValue = NULL)
	{
		$config = $this->configuration($module);
		$this->CI->db->select_max($config['order_column'], 'max_order');
		if ($this->scopeApplies($config, $scopeValue))
		{
			$this->CI->db->where($config['scope_column'], $this->validatedScope($config, $scopeValue));
		}
		$row = $this->CI->db->get($config['table'])->row_array();

		return isset($row['max_order']) ? (int) $row['max_order'] + 1 : 1;
	}

	public function reorder($module, $recordIds, $offset = 0, $scopeValue = NULL)
	{
		$config = $this->configuration($module);
		$recordIds = array_values(array_unique(array_filter(array_map('intval', (array) $recordIds))));
		if (empty($recordIds))
		{
			throw new InvalidArgumentException('No records were supplied.');
		}

		$this->CI->db->select($config['primary_key']);
		$this->CI->db->from($config['table']);
		if ($this->scopeApplies($config, $scopeValue))
		{
			$this->CI->db->where($config['scope_column'], $this->validatedScope($config, $scopeValue));
		}
		$this->CI->db->order_by($config['order_column'], 'ASC');
		if ($config['fallback_column'] !== $config['primary_key'])
		{
			$this->CI->db->order_by($config['fallback_column'], 'ASC');
		}
		$this->CI->db->order_by($config['primary_key'], 'ASC');
		$rows = $this->CI->db->get()->result_array();
		$allIds = array_map('intval', array_column($rows, $config['primary_key']));

		if (count(array_intersect($recordIds, $allIds)) !== count($recordIds))
		{
			throw new InvalidArgumentException('One or more records no longer exist.');
		}

		$remainingIds = array_values(array_diff($allIds, $recordIds));
		$offset = max(0, min((int) $offset, count($remainingIds)));
		array_splice($remainingIds, $offset, 0, $recordIds);

		$updates = array();
		foreach ($remainingIds as $index => $recordId)
		{
			$updates[] = array(
				$config['primary_key'] => $recordId,
				$config['order_column'] => $index + 1,
			);
		}

		$this->CI->db->trans_begin();
		$updated = empty($updates) || $this->CI->SqlModel->batchUpdate($config['table'], $updates, $config['primary_key']);
		if (!$updated || $this->CI->db->trans_status() === FALSE)
		{
			$this->CI->db->trans_rollback();
			return FALSE;
		}

		$this->CI->db->trans_commit();
		return TRUE;
	}

	private function configuration($module)
	{
		if (!$this->supports($module))
		{
			throw new InvalidArgumentException('This module does not support record sorting.');
		}

		return $this->modules[$module];
	}

	private function scopeApplies($config, $scopeValue)
	{
		if (empty($config['scope_column']))
		{
			return FALSE;
		}

		return !empty($config['scope_required']) || ($scopeValue !== NULL && $scopeValue !== '');
	}

	private function validatedScope($config, $scopeValue)
	{
		if (!empty($config['scope_values']))
		{
			$scopeValue = is_string($scopeValue) ? trim($scopeValue) : $scopeValue;
			if (!in_array($scopeValue, $config['scope_values'], TRUE))
			{
				throw new InvalidArgumentException('A valid '.$config['scope_column'].' scope is required for this module.');
			}

			return $scopeValue;
		}

		$scopeValue = (int) $scopeValue;
		if ($scopeValue < 1)
		{
			throw new InvalidArgumentException('A valid '.$config['scope_column'].' scope is required for this module.');
		}

		return $scopeValue;
	}
}
