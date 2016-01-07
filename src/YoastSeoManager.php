<?php

/**
 * @file
 * Contains the \Drupal\yoast_seo\YoastSeoManager class.
 */

namespace Drupal\yoast_seo;

use \Drupal\views\Views;

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

  /**
   * Attach a field handler for yoast seo in the content view.
   */
  public function attachFieldHandlerToContentView() {
    $content_view = Views::getView('content');

    if ($content_view) {
      $display_id = 'page_1';

      $handlers = $content_view->getHandlers('field', $display_id);
      if (!isset($handlers['field_yoast_seo'])) {
        $content_view->addHandler(
          $display_id,
          'field',
          'node__field_yoast_seo',
          'field_yoast_seo',
          [
            'type' => 'yoastseo_formatter',
          ],
          'field_yoast_seo'
        );
        $content_view->save();
      }
    }
  }

  /**
   * Detach the field handler for yoast seo from the content view.
   */
  public function detachFieldHandlerFromContentView() {
    $content_view = Views::getView('content');

    if ($content_view) {
      $display_id = 'page_1';

      $handlers = $content_view->getHandlers('field', $display_id);
      if (isset($handlers['field_yoast_seo'])) {
        $content_view->removeHandler($display_id, 'field', 'field_yoast_seo');
        $content_view->save();
      }
    }
  }

}
