<?php

namespace Drupal\yoast_seo;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SeoManager.
 *
 * @package Drupal\yoast_seo
 */
class SeoManager {

  /**
   * Real Time SEO Field Manager service.
   *
   * @var \Drupal\yoast_seo\FieldManager
   */
  protected $fieldManager;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructor for YoastSeoManager.
   *
   * @param \Drupal\yoast_seo\FieldManager $fieldManager
   *   Real Time SEO Field Manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity Type Manager service.
   */
  public function __construct(FieldManager $fieldManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->fieldManager = $fieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Returns an array of available entity types Yoast SEO can be enabled for.
   *
   * @return array
   *   A list of available entity types as $id => $label.
   */
  public function getSupportedEntityTypes() {
    // @todo Should be the same than the ones supported by the metatag module.
    return [
      'node' => 'Node',
    ];
  }

  /**
   * Returns an array of available bundles this module can be enabled for.
   *
   * Loads the bundles for the given entity types or loads the bundles for all
   * supported entities if no entity types are specified.
   *
   * @param array $entity_types
   *   (optional) The entity types to return the bundles for.
   *
   * @return array
   *   A list of available bundles in the form of:
   *
   *   $entity_type => [
   *    $bundle => $label
   *   ]
   */
  public function getEntityBundles(array $entity_types = NULL) {
    if (is_null($entity_types)) {
      $entity_types = array_keys($this->getSupportedEntityTypes());
    }

    $entities = [];
    foreach ($entity_types as $entity_type) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);

      foreach ($bundles as $bundle_id => $bundle_metadata) {
        $bundles[$bundle_id] = $bundle_metadata['label'];
      }

      if (!empty($bundles)) {
        $entities[$entity_type] = $bundles;
      }
    }

    return $entities;
  }

  /**
   * Returns an array of bundles this module has been enabled for.
   *
   * Returns the bundles for the given entity types or checks the bundles for
   * all supported entities if no entity types are specified.
   *
   * @param array $entity_types
   *   (optional) The entity types to check the bundles for.
   *
   * @return array
   *   A list of enabled bundles in the form of:
   *
   *   $entity_type => [
   *    $bundle => $label
   *   ]
   */
  public function getEnabledBundles(array $entity_types = NULL) {
    $entities = $this->getEntityBundles($entity_types);
    // TODO: Clean up the next line.
    $field_name = array_keys($this->fieldManager->getFieldDefinitions())[0];

    foreach ($entities as $entity_type => &$bundles) {
      foreach ($bundles as $bundle_id => $bundle_label) {
        if (!$this->fieldManager->isAttached($entity_type, $bundle_id, $field_name)) {
          unset($bundles[$bundle_id]);
        }
      }

      if (empty($bundles)) {
        unset($entities[$entity_type]);
      }
    }

    return $entities;
  }

  /**
   * Get the status for a given score.
   *
   * TODO: Move this back to something like an SEO Assessor.
   *
   * @param int $score
   *   Score in points.
   *
   * @return string
   *   Status corresponding to the score.
   */
  public function getScoreStatus($score) {
    $rules = $this->getConfiguration()['score_to_status_rules'];
    $default = $rules['default'];
    unset($rules['default']);

    foreach ($rules as $status => $status_rules) {
      $min_max_isset = isset($status_rules['min']) && isset($status_rules['max']);
      if (isset($status_rules['equal']) && $status_rules['equal'] == $score) {
        return $status;
      }
      elseif ($min_max_isset && $score > $status_rules['min'] && $score <= $status_rules['max']) {
        return $status;
      }
    }

    return $default;
  }

  /**
   * Get configuration from Yaml file.
   *
   * @return mixed
   *   Configuration details will be returned.
   *
   * @TODO: Turn this into proper Drupal configuration!
   */
  public function getConfiguration() {
    $conf = Yaml::parse(
      file_get_contents(
        drupal_get_path('module', 'yoast_seo') . '/config/yoast_seo.yml'
      )
    );
    return $conf;
  }

}
