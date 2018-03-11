<?php

/**
 * @file
 * Integrated to Views.
 */

/**
 * Implements hook_views_data().
 */
function counter_views_data() {
  // Basic table information.
  $data['counter']['table']['group']  = t('Counter');

  // Advertise this table as a possible base table.
  $data['counter']['table']['base'] = array(
    'field' => 'counter_id',
    'title' => t('Counter'),
    'help' => t('Counter list.'),
    'weight' => 10,
  );

  $data['users_field_data']['table']['join'] = array(
    'counter' => array(
      'left_field' => 'uid',
      'field' => 'uid',
      'type' => 'LEFT',
    ),
  );

  $data['counter']['counter_id'] = array(
    'title' => t('Counter ID'),
    'help' => t('The Counter ID.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  $data['counter']['ip'] = array(
    'title' => t('IP Address'),
    'help' => t('The Counter IP Address.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['counter']['url'] = array(
    'title' => t('URL'),
    'help' => t('The URL when Counter counted.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['counter']['created'] = array(
    'title' => t('Created'),
    'help' => t('The created time of Counter item.'),
    'field' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  $data['counter']['uid'] = array(
    'title' => t('UID'),
    'help' => t('The UID who visit Counter URL.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  // nid, type, browser_name, browser_version, platform.
  $data['counter']['nid'] = array(
    'title' => t('NID'),
    'help' => t('The NID of URL if node was visited.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  $data['counter']['type'] = array(
    'title' => t('NODE TYPE'),
    'help' => t('The NODE TYPE of node if node was visited.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['counter']['browser_name'] = array(
    'title' => t('BROWSER NAME'),
    'help' => t('The Browser Name of Client Browser.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['counter']['browser_version'] = array(
    'title' => t('BROWSER VERSION'),
    'help' => t('The Browser Version of Client Browser.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['counter']['platform'] = array(
    'title' => t('PLATFORM'),
    'help' => t('The Platform Name of Client System/OS.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  return $data;
}
