<?php

namespace Drupal\tiles_grid\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TilesGridBlock' block.
 *
 * @Block(
 *  id = "tiles_grid_block",
 *  admin_label = @Translation("Tiles grid block"),
 * )
 */
class TilesGridBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = $this->database->query("SELECT esi.items_target_id FROM {entity_subqueue} as es JOIN {entity_subqueue__items} esi ON es.queue=esi.entity_id 
	JOIN {node_field_data} as nb ON nb.nid=esi.items_target_id
	where es.queue='tile_grid' AND nb.promote=1 ORDER BY esi.delta ASC");
    $result = $query->fetchAll();
    foreach ($result as $key => $value) {
      $node = $this->entityTypeManager->getStorage('node')->load($value->items_target_id);
      $type = $node->get('field_tile_type')->value;
      // type=article tile.
      if ($type == 1) {
        $rows[$key]['type'] = 'article';
        $rows[$key]['title'] = $node->field_article->entity->label();
        $rows[$key]['image_url'] = file_create_url($node->field_article->entity->field_image->entity->getFileUri());
        $rows[$key]['tile_link'] = $node->field_article->entity->toUrl()->setAbsolute()->toString();
        // Check if article tag exist then use article tag.
        if ($node->field_article->entity->field_tags->target_id) {
          $rows[$key]['tag_icon'] = $this->getTagIcon($node->field_article->entity->field_tags->target_id);
        }
        else {
          $rows[$key]['tag_icon'] = $this->getTagIcon($node->field_tiles_tags->target_id);
        }
      }
      // type=video tile.
      if ($type == 2) {
        $rows[$key]['type'] = 'video';
        $rows[$key]['image_url'] = file_create_url($node->field_tile_image->entity->getFileUri());
        $rows[$key]['play_icon'] = base_path() . drupal_get_path('module', 'tiles_grid') . "/images/playicon.png";
        $rows[$key]['video_url'] = $node->field_youtube_video->getString();
        $rows[$key]['title'] = $node->label();
        $rows[$key]['tag_icon'] = $this->getTagIcon($node->field_tiles_tags->target_id);
      }
      // type=informative tile.
      if ($type == 3) {
        $rows[$key]['type'] = 'informative';
        $rows[$key]['image_url'] = file_create_url($node->field_tile_image->entity->getFileUri());
        $rows[$key]['tile_link'] = $node->field_tile_link->getString();
        $rows[$key]['title'] = $node->label();
        $rows[$key]['tag_icon'] = $this->getTagIcon($node->field_tiles_tags->target_id);
      }
    }
    $build = [];
    $build['#theme'] = 'tiles_grid_block';
    $build['#rows'] = $rows;
    $build['#cache']['max-age'] = 0;
    $build['#attached']['library'][] = 'tiles_grid/tiles_grid';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * Get Icon of the Tags associated with tile or article.
   */
  public function getTagIcon($tid = 0) {

    if ($tid > 0) {
      $entity = $this->entityTypeManager->getStorage("taxonomy_term")->load($tid);
      $name = $entity->getName();
      switch ($name) {
        case 'Food':
          $icon = base_path() . drupal_get_path('module', 'tiles_grid') . "/images/apple.png";
          break;

        case 'Planet':
          $icon = base_path() . drupal_get_path('module', 'tiles_grid') . "/images/saturn.png";
          break;

        case 'People':
          $icon = base_path() . drupal_get_path('module', 'tiles_grid') . "/images/doctor.png";
          break;

        default:
          break;
      }
      return $icon;
    }
    else {
      return '';
    }
  }

}
