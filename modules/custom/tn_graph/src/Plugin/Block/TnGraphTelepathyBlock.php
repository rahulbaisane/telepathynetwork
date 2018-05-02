<?php
namespace Drupal\tn_graph\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
/**
 * Provides a 'TnGraphTelepathyBlock' Block.
 *
 * @Block(
 *   id = "tngraphtelepathy_block",
 *   admin_label = @Translation("TnGraphTelepathy: Block"),
 *   category = @Translation("TnGraphTelepathy"),
 * )
 */
class TnGraphTelepathyBlock extends BlockBase {
   /**
   * {@inheritdoc}
   */
  public function build() {
    $current_path = \Drupal::service('path.current')->getPath();
    $current_path_args = explode('/', $current_path);
    $node_id = $current_path_args[2];
    $query = \Drupal::database()->query("select name, sum(status) as total from telepathynetwork_tb where status = 1 and nid = '". $node_id ."' group by name");
    $records = $query->fetchAll();
    foreach ($records as $key => $record) {
      $users[$key] = $record->name;
      $data[$key] = $record->total;
    }
    $build = array();
  	$build['#markup'] = '<div class="ct-chart ct-golden-section" id="chart1"></div>';
    $build['#attached']['library'] = 'tn_graph/tn-graph'; 
    $build['#attached']['drupalSettings']['tn_graph']['test'] = $data;
    $build['#attached']['drupalSettings']['tn_graph']['users'] = $users;
    $build['#cache']['max-age'] = 1;
  return $build;
  }
}