<?php

namespace Drupal\yoast_seo;

use Drupal\Core\Extension\ModuleHandlerInterface;
use \Drupal\views\Views;
use \Drupal\Component\Utility\Html;
use \Symfony\Component\Yaml\Yaml;

/**
 * Class YoastSeoManager.
 *
 * @package Drupal\yoast_seo
 */
class YoastSeoManager {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Target elements for Javascript.
   *
   * @var array
   */
  public static $jsTargets = [
    'wrapper_target_id'       => 'yoast-wrapper',
    'snippet_target_id'       => 'yoast-snippet',
    'output_target_id'        => 'yoast-output',
    'overall_score_target_id' => 'yoast-overall-score',
  ];

  /**
   * Constructor for YoastSeoManager.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->yoast_seo_field_manager = \Drupal::service('yoast_seo.field_manager');
  }

  /**
   * Attach the yoast seo fields to a target content type.
   *
   * @param string $entity_type
   *   Bundle.
   * @param array $bundle
   *   Entity type.
   */
  public function attachYoastSeoFields($entity_type, $bundle) {
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
        'field_label' => 'Real-time SEO',
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
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   */
  public function detachYoastSeoFields($entity_type, $bundle) {
    // Detach seo fields from the target content type.
    $yoast_fields = [
      'field_yoast_seo',
    ];

    foreach ($yoast_fields as $field_name) {
      $this->yoast_seo_field_manager->detachField($entity_type, $bundle, $field_name);
    }
  }

  /**
   * Returns an array of available bundles Yoast SEO can be enabled for.
   *
   * @param string $entity_type
   *   The entity.
   *
   * @return array
   *   A list of available bundles as $id => $label.
   */
  public function getAvailableBundles($entity_type = 'node') {
    $options        = array();
    $entity_manager = \Drupal::service('entity.manager');

    // Retrieve the bundles the entity type contains.
    $bundles = $entity_manager->getBundleInfo($entity_type);
    foreach ($bundles as $bundle_id => $bundle_metadata) {
      $options[$bundle_id] = $bundle_metadata['label'];
    }

    return $options;
  }

  /**
   * Returns an array of bundles Yoast SEO has been enabled for.
   *
   * @param string $entity_type
   *   The entity.
   *
   * @return array
   *   A list of enabled bundles as $id => $label.
   */
  public function getEnabledBundles($entity_type = 'node') {
    $enabled_bundles         = array();
    $yoast_seo_field_manager = \Drupal::service('yoast_seo.field_manager');

    // Get the available bundles Yoast SEO supports.
    $bundles = $this->getAvailableBundles($entity_type);

    // Retrieve the bundles for which Yoast SEO has already been enabled for.
    foreach ($bundles as $bundle_id => $bundle_label) {
      if ($yoast_seo_field_manager->isAttached($entity_type, $bundle_id, 'field_yoast_seo')) {
        $enabled_bundles[] = $bundle_id;
      }
    }

    return $enabled_bundles;
  }

}
