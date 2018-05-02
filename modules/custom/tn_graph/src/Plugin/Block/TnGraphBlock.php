<?php
namespace Drupal\tn_graph\Plugin\Block;
use Drupal\Core\Block\BlockBase;
/**
 * Provides a 'TnGraphBlock' Block.
 *
 * @Block(
 *   id = "tngraph_block",
 *   admin_label = @Translation("TnGraph: Block"),
 *   category = @Translation("TnGraph"),
 * )
 */
class TnGraphBlock extends BlockBase {
   /**
   * {@inheritdoc}
   */
  public function build() {
    $query = \Drupal::database()->query("select name, sum(status) as total from telepathynetwork_tb where status = 1 group by name");
    $records = $query->fetchAll();
    foreach ($records as $key => $record) {
      $users[$key] = $record->name;
      $total[$key] = $record->total;
    }
  	$build = [];
    $build['#markup'] = '<div class="ct-chart ct-golden-section" id="chart1"></div>';
    $build['#attached']['library'] = 'tn_graph/tn-graph'; 
    $build['#attached']['drupalSettings']['tn_graph']['test'] = $total;
    $build['#attached']['drupalSettings']['tn_graph']['users'] = $users;
    $build['#cache']['max-age'] = 1;
  return $build;
  }
}