<?php

namespace Drupal\counter\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a 'counter' block.
 *
 * @Block(
 *   id = "counter_block",
 *   admin_label = @Translation("Counter"),
 * )
 */
class CounterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer counter');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = \Drupal::config('counter.settings');
    $counter_show_site_counter   = $config->get('counter_show_site_counter');
    $counter_show_unique_visitor = $config->get('counter_show_unique_visitor');
    $counter_registered_user     = $config->get('counter_registered_user');
    $counter_unregistered_user   = $config->get('counter_unregistered_user');
    $counter_blocked_user        = $config->get('counter_blocked_user');
    $counter_published_node      = $config->get('counter_published_node');
    $counter_unpublished_node    = $config->get('counter_unpublished_node');
    $counter_show_server_ip      = $config->get('counter_show_server_ip');
    $counter_show_ip             = $config->get('counter_show_ip');
    $counter_show_counter_since  = $config->get('counter_show_counter_since');
    $counter_skip_admin           = $config->get('counter_skip_admin');
    $counter_refresh_delay        = $config->get('counter_refresh_delay');
    $counter_insert_delay         = $config->get('counter_insert_delay');
    $counter_initial_counter        = $config->get('counter_initial_counter');
    $counter_initial_unique_visitor = $config->get('counter_initial_unique_visitor');
    $counter_initial_since          = $config->get('counter_initial_since');
    $counter_statistic_today      = $config->get('counter_statistic_today');
    $counter_statistic_week       = $config->get('counter_statistic_week');
    $counter_statistic_month      = $config->get('counter_statistic_month');
    $counter_statistic_year       = $config->get('counter_statistic_year');
    $ip = \Drupal::request()->getClientIp();
    $counter_svr_ip = $_SERVER['SERVER_ADDR'];
    $created = \time();
    $url = SafeMarkup::checkPlain(\Drupal::request()->getRequestUri());

    // Counter_insert_delay.
    $db_types = db_driver();
    switch ($db_types) {
      case 'mssql':
        $sql = " SELECT TOP 1 created FROM {counter} WHERE created<>0 ORDER BY created DESC";
        break;

      case 'oracle':
        $sql = " SELECT created FROM {counter} WHERE ROWNUM=1 AND created<>0 ORDER BY created DESC";
        break;

      default:
        $sql = " SELECT created FROM {counter} WHERE created<>0 ORDER BY created DESC LIMIT 1";
    }

    $counter_lastdate = db_query($sql)->fetchField();

    // Check if permited to insert data.
    $interval = \time() - $counter_lastdate;
    $data_insert = ($interval >= $counter_insert_delay ? 1 : 0);
    $data_update = ($interval >= $counter_refresh_delay ? 1 : 0);

    // Uid, nid, type, browser name, browser version, platform.
    $node = \Drupal::request()->attributes->get('node');
    $nid  = 0;
    $type = '';
    $account = \Drupal::currentUser();
    $path_args = \explode('/', \Drupal::request()->getRequestUri());
    if ($path_args[0] == 'node' && is_numeric($path_args[1])) {
      $nid  = $node->nid;
      $type = $node->type;
    }
    module_load_include('inc', 'counter', 'counter.lib');
    $browser = counter_get_browser();
    $browser_name    = $browser['browser_name'];
    $browser_version = $browser['browser_version'];
    $platform        = $browser['platform'];

    $sql = " INSERT INTO {counter} (ip,created,url, uid, nid, type, browser_name, browser_version, platform)" .
    " VALUES (:ip, :created, :url, :uid, :nid, :type, :browser_name, :browser_version, :platform)";

    $counter_exec = FALSE;

    if ($data_insert && ($account->id() <> 1)) {
      $counter_exec = TRUE;
    }
    else {
      if ($data_insert && ($account->id() == 1) && !$counter_skip_admin) {
        $counter_exec = TRUE;
      }
    }

    if ($counter_exec) {
      $results = db_query($sql, array(
        ':ip' => $ip,
        ':created' => $created,
        ':url' => $url,
        ':uid' => $account->id(),
        ':nid' => $nid,
        ':type' => $type,
        ':browser_name' => $browser_name,
        ':browser_version' => $browser_version,
        ':platform' => $platform,
      ));
    }
    else {
      return;
    }

    $sql_site_counter = "SELECT counter_value FROM {counter_data} WHERE counter_name='site_counter'";
    $site_counter     = db_query($sql_site_counter)->fetchField();

    $new_site_counter = $site_counter + 1;

    $sql = " UPDATE {counter_data} SET counter_value = :counter_value WHERE counter_name='site_counter'";
    $results = db_query($sql, array(':counter_value' => $new_site_counter));

    // Read counter_data.
    $sql = " SELECT * FROM {counter_data} ORDER BY counter_name";
    $results = db_query($sql);
    $i = 0;

    foreach ($results as $data) {
      $i++;
      $counter_value[$i] = $data->counter_value;
    }

    // Write output.
    $output = '<div  id="counter">';
    $output .= '<ul>';
    if ($counter_show_site_counter) {
      $output .= '<li>' . t('Site Counter:') . '<strong>' . number_format($counter_initial_counter + $counter_value[4]) . '</strong></li>';
    }

    if ($counter_show_unique_visitor) {
      if ($data_update) {
        $sql = " SELECT count(*) as total " .
        " FROM (SELECT ip FROM {counter} GROUP BY ip) c";
        $counter_unique = db_query($sql)->fetchField();

        $sql = " UPDATE {counter_data} SET counter_value= :counter_value WHERE counter_name='unique_visitor' ";
        $results = db_query($sql, array(':counter_value' => $counter_unique));
      }
      else {
        $counter_unique = $counter_value[5];
      }
      $output .= '<li>' . t('Unique Visitor:') . '<strong>' . number_format($counter_initial_unique_visitor + $counter_unique) . '</strong></li>';
    }

    if ($counter_registered_user) {
      if ($data_update) {
        $sql = " SELECT count(*) as total FROM {users_field_data} WHERE access<>0 and uid<>0";
        $total   = db_query($sql)->fetchField();

        $sql = " UPDATE {counter_data} SET counter_value= :counter_value WHERE counter_name='registered_user' ";
        $results = db_query($sql, array(':counter_value' => $total));
      }
      else {
        $total = $counter_value[3];
      }
      $output .= '<li>' . t('Registered Users:') . '<strong>' . number_format($total) . '</strong></li>';
    }

    if ($counter_unregistered_user) {
      if ($data_update) {
        $sql = " SELECT count(*) as total FROM {users_field_data} WHERE access=0 and uid<>0";
        $total   = db_query($sql)->fetchField();

        $sql = "UPDATE {counter_data} SET counter_value = :counter_value WHERE counter_name='unregistered_user' ";
        $results = db_query($sql, array(':counter_value' => $total));
      }
      else {
        $total = $counter_value[7];
      }
      $output .= '<li>' . t('Unregistered Users:') . '<strong>' . number_format($total) . '</strong></li>';
    }

    if ($counter_blocked_user) {
      if ($data_update) {
        $sql = " SELECT count(*) as total FROM {users_field_data} WHERE status=0 and uid<>0";
        $total   = db_query($sql)->fetchField();

        $sql = " UPDATE {counter_data} SET counter_value = :counter_value WHERE counter_name='blocked_user' ";
        $results = db_query($sql, array(':counter_value' => $total));
      }
      else {
        $total = $counter_value[1];
      }
      $output .= '<li>' . t('Blocked Users:') . '<strong>' . number_format($total) . '</strong></li>';
    }

    if ($counter_published_node) {
      if ($data_update) {
        $sql = " SELECT count(*) as total FROM {node_field_data} WHERE status=1";
        $total   = db_query($sql)->fetchField();

        $sql = " UPDATE {counter_data} SET counter_value= :counter_value WHERE counter_name='published_node' ";
        $results = db_query($sql, array(':counter_value' => $total));
      }
      else {
        $total = $counter_value[2];
      }
      $output .= '<li>' . t('Published Nodes:') . '<strong>' . number_format($total) . '</strong></li>';
    }

    if ($counter_unpublished_node) {
      if ($data_update) {
        $sql = " SELECT count(*) as total FROM {node_field_data} WHERE status=0";
        $total   = db_query($sql)->fetchField();

        $sql = "UPDATE {counter_data} SET counter_value = :counter_value WHERE counter_name='unpublished_node' ";
        $results = db_query($sql, array(':counter_value' => $total));
      }
      else {
        $total = $counter_value[6];
      }
      $output .= '<li>' . t('Unpublished Nodes:') . '<strong>' . number_format($total) . '</strong></li>';
    }

    if ($counter_show_server_ip) {
      $output .= '<li>' . t("Server IP:") . '<strong>' . $counter_svr_ip . '</strong></li>';
    }

    if ($counter_show_ip) {
      $output .= '<li>' . t("Your IP:") . '<strong>' . $ip . '</strong></li>';
    }

    if ($counter_show_counter_since) {
      switch ($db_types) {
        case 'mssql':
          $sql = " SELECT TOP 1 created FROM {counter} WHERE created>0 ORDER BY created ASC";
          break;

        case 'oracle':
          $sql = " SELECT created FROM {counter} WHERE ROWNUM=1 AND created>0 ORDER BY created ASC";
          break;

        default:
          $sql = " SELECT created FROM {counter} WHERE created>0 ORDER BY created ASC LIMIT 1";
      }

      $counter_since = db_query($sql)->fetchField();

      if ($counter_initial_since <> 0) {
        $counter_since = $counter_initial_since;
      }
      $output .= '<li>' . t("Since:") . '<strong>' . date('d M Y', $counter_since) . '</strong></li>';
    }

    if ($counter_statistic_today || $counter_statistic_week || $counter_statistic_month || $counter_statistic_year) {
      $output .= '<li>' . t("Visitors:") . '</li>';
    }

    $output .= '<ul>';

    if ($counter_statistic_today) {
      $date1 = strtotime(date('Y-m-d'));
      $sql = "SELECT count(*) AS total FROM {counter} WHERE created >= :counter_stat_today";
      $results = db_query($sql, array(':counter_stat_today' => $date1));
      $statistic = $results->fetchField();
      $output .= '<li>' . t("Today:") . '<strong>' . number_format($statistic) . "</strong></li>";
    }
    if ($counter_statistic_week) {
      $date1 = strtotime(date('Y-m-d')) - 7 * 24 * 60 * 60;
      $date2 = time();
      $sql = " SELECT count(*) AS total FROM {counter} WHERE created > :date1 AND created <= :date2";
      $results = db_query($sql, array(':date1' => $date1, ':date2' => $date2));
      $statistic = $results->fetchField();
      $output .= '<li>' . t("This week:") . '<strong>' . number_format($statistic) . "</strong></li>";
    }
    if ($counter_statistic_month) {
      $date1 = strtotime(date('Y-m-d')) - 30 * 24 * 60 * 60;
      $date2 = time();
      $sql = " SELECT count(*) AS total FROM {counter} WHERE created > :date1 AND created <= :date2";
      $results = db_query($sql, array(':date1' => $date1, ':date2' => $date2));
      $statistic = $results->fetchField();
      $output .= '<li>' . t("This month:") . '<strong>' . number_format($statistic) . "</strong></li>";
    }
    if ($counter_statistic_year) {
      $date1 = strtotime(date('Y-m-d')) - 365 * 24 * 60 * 60;
      $date2 = time();
      $sql = " SELECT count(*) AS total FROM {counter} WHERE created > :date1 AND created <= :date2";
      $results = db_query($sql, array(':date1' => $date1, ':date2' => $date2));
      $statistic = $results->fetchField();
      $output .= '<li>' . t("This year:") . '<strong>' . number_format($statistic) . '</strong></li>';
    }

    $output .= '</ul>';
    $output .= '</ul>';
    $output .= '</div>';

    return [
      '#attached' => [
        'library' => array('counter/counter.custom'),
      ],
      '#markup' => $output,
    ];
  }

}
