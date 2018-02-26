<?php

namespace Drupal\faq\Plugin\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\faq\FaqHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides a simple block.
 *
 * @Block(
 *   id = "faq_categories",
 *   admin_label = @Translation("FAQ Categories")
 * )
 */
class FaqCategoriesBlock extends BlockBase {

  protected  $config;
  protected  $entityTypeManager;
  protected  $linkGenerator;
  protected  $moduleHandler;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $linkGenerator
   */
  public function __construct(
    ConfigFactoryInterface $config,
    EntityTypeManagerInterface $entityTypeManager,
    LinkGeneratorInterface $linkGenerator,
    ModuleHandler $moduleHandler
  ) {
    $this->config = $config;
    $this->entityTypeManager = $entityTypeManager;
    $this->linkGenerator = $linkGenerator;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('link_generator'),
      $container->get('module_handler')
    );
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    static $vocabularies, $terms;
    $items = array();

    $faq_settings = $this->config->get('faq.settings');
    if (!$faq_settings->get('use_categories')) {
      return;
    }
    $moduleHandler = $this->moduleHandler;

    if ($moduleHandler->moduleExists('taxonomy')) {
      if (!isset($terms)) {
        $terms = array();
        $vocabularies = Vocabulary::loadMultiple();
        $vocab_omit = array_flip($faq_settings->get('omit_vocabulary'));
        $vocabularies = array_diff_key($vocabularies, $vocab_omit);
        foreach ($vocabularies as $vocab) {
          foreach ($this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocab->id()) as $term) {
            if (FaqHelper::taxonomyTermCountNodes($term->tid)) {
              $terms[$term->name] = $term->tid;
            }
          }
        }
      }
      if (count($terms) > 0) {
        foreach ($terms as $name => $tid) {
          $items[] = $this->linkGenerator->generate($name, URL::fromUserInput('/faq-page/' . $tid));
        }
      }
    }
    return array(
      '#theme' => 'item_list',
      '#items' => $items,
      '#list_type' => $faq_settings->get('category_listing'),
    );
  }

}
