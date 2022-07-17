<?php

/**
 * @file
 * Views integration for GA4 Counter.
 *
 * @see http://drupal.org/project/views
 */

/**
 * Implements hook_views_data()
 */
function ga4_counter_views_data() {
  // Define the return array.
  $data = [];

  // Define table for ga4_tid_storage
  $data['ga4_tid_storage'] = [];
  $data['ga4_tid_storage']['table'] = [];
  $data['ga4_tid_storage']['table']['group'] = t('GA4 Counter terms');
  $data['ga4_tid_storage']['table']['provider'] = 'ga4_counter';
  $data['ga4_tid_storage']['table']['base'] = [
    'field' => 'tid',
    'title' => t('GA4 Counter terms'),
    'help' => t('GA4 Counter module table containing pageview counts for terms.'),
    'weight' => -10,
  ];

  $data['ga4_tid_storage']['tid'] = [
    'title' => t('Term field data'),
    'help' => t('Relate Google Analytics Counter content to the term content'),
    'relationship' => [
      'base' => 'taxonomy_term_field_data',
      'base field' => 'tid',
      'id' => 'standard',
      'label' => t('terms'),
    ],
  ];

  // Numeric field, exposed as a field, sort, filter, and argument.
  $data['ga4_tid_storage']['pageview_total'] = [
    'title' => t('GA4 Counter Pageviews total for terms'),
    'help' => t('Total pageview counts for terms'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'numeric',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'numeric',
    ],
    'argument' => [
      // ID of argument handler plugin to use.
      'id' => 'numeric',
    ],
  ];

  $data['ga4_tid_storage']['table']['join'] = [
    'taxonomy_term_field_data' => [
      'left_field' => 'tid',
      'field' => 'tid',
    ],
  ];

  // Define table for ga4_nid_storage
  $data['ga4_nid_storage'] = [];
  $data['ga4_nid_storage']['table'] = [];
  $data['ga4_nid_storage']['table']['group'] = t('GA4 Counter node');
  $data['ga4_nid_storage']['table']['provider'] = 'ga4_counter';
  $data['ga4_nid_storage']['table']['base'] = [
    'field' => 'nid',
    'title' => t('GA4 Counter node'),
    'help' => t('GA4 Counter module table containing pageview counts for nodes.'),
    'weight' => -10,
  ];

  $data['ga4_nid_storage']['nid'] = [
    'title' => t('Node field data'),
    'help' => t('Relate Google Analytics Counter content to the node content'),
    'relationship' => [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('nodes'),
    ],
  ];

  // Numeric field, exposed as a field, sort, filter, and argument.
  $data['ga4_nid_storage']['pageview_total'] = [
    'title' => t('GA4 Counter node Pageviews total'),
    'help' => t('Total pageview counts for nodes'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'numeric',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'numeric',
    ],
    'argument' => [
      // ID of argument handler plugin to use.
      'id' => 'numeric',
    ],
  ];

  $data['ga4_nid_storage']['table']['join'] = [
    'node_field_data' => [
      'left_field' => 'nid',
      'field' => 'nid',
    ],
  ];

  return $data;
}
