<?php defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('admin_pagination_config'))
{
    /**
     * Build the shared offset-based pagination configuration for admin listings.
     *
     * @param string $baseUrl
     * @param int    $totalRows
     * @param int    $perPage
     * @param int    $uriSegment
     * @param int    $numLinks
     * @return array
     */
    function admin_pagination_config($baseUrl, $totalRows, $perPage, $uriSegment, $numLinks = 4)
    {
        return array(
            'base_url' => $baseUrl,
            'total_rows' => $totalRows,
            'per_page' => $perPage,
            'uri_segment' => $uriSegment,
            'num_links' => $numLinks,
            'use_page_numbers' => FALSE,
            'page_query_string' => FALSE,
            'reuse_query_string' => FALSE,
            'first_link' => 'First',
            'last_link' => 'Last',
            'prev_link' => '<i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">Previous</span>',
            'next_link' => '<i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">Next</span>',
            'attributes' => array('class' => 'page-link'),
            'cur_tag_open' => '<li class="page-item active" aria-current="page"><span class="page-link">',
            'cur_tag_close' => '</span></li>',
            'full_tag_open' => '<ul class="pagination mb-0">',
            'full_tag_close' => '</ul>',
            'num_tag_open' => '<li class="page-item">',
            'num_tag_close' => '</li>',
            'next_tag_open' => '<li class="page-item">',
            'next_tag_close' => '</li>',
            'prev_tag_open' => '<li class="page-item">',
            'prev_tag_close' => '</li>',
            'last_tag_open' => '<li class="page-item">',
            'last_tag_close' => '</li>',
            'first_tag_open' => '<li class="page-item">',
            'first_tag_close' => '</li>'
        );
    }
}
