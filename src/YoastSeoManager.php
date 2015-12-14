<?php

/**
 * @file
 * Contains the \Drupal\yoast_seo\YoastSeoManager class.
 */

namespace Drupal\yoast_seo;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use \Drupal\field\Entity\FieldConfig;

/**
 * Class YoastSeoManager.
 *
 * @package Drupal\yoast_seo
 */
class YoastSeoManager {

  /**
   * Metatag logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor for YoastSeoManager.
   */
  public function __construct() {
    $this->yoast_seo_field_manager = \Drupal::service('yoast_seo.field_manager');
  }

  /**
   * Attach the yoast seo fields to a target content type.
   *
   * @param $bundle
   * @param string $entity_type
   */
  public function attachYoastSeoFields($entity_type = 'node', $bundle) {
    // Attach metatag field to the target content.
    $metatag_field = array(
      'field_name' => 'field_meta_tags',
      'field_label' => 'Meta tags',
      'storage_type' => 'metatag',
      'translatable' => TRUE,
    );
    $this->yoast_seo_field_manager->attachField($entity_type, $bundle, $metatag_field);

    // Attach yoast seo field to the target content type.
    $yoast_fields = [
      'field_yoast_seo' => array(
        'field_name' => 'field_yoast_seo',
        'field_label' => 'Yoast SEO',
        'storage_type' => 'yoast_seo',
        'translatable' => TRUE,
      ),
    ];
    foreach ($yoast_fields as $field) {
      $this->yoast_seo_field_manager->attachField($entity_type, $bundle, $field);
    }
  }

  /**
   * Delete the yoast seo fields from a target content type.
   *
   * @param $bundle
   * @param string $entity_type
   */
  public function detachYoastSeoFields($entity_type = 'node', $bundle) {
    // Detach seo fields from the target content type.
    $yoast_fields = [
      'field_yoast_seo',
    ];

    foreach ($yoast_fields as $field_name) {
      $this->yoast_seo_field_manager->detachField($entity_type, $bundle, $field_name);
    }
  }

}
