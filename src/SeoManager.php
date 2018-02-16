<?php

namespace Drupal\yoast_seo;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SeoManager.
 *
 * @package Drupal\yoast_seo
 */
class SeoManager {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Type Bundle Info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructor for YoastSeoManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity Type Bundle Info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity Field Manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Returns an array of bundles that have a 'yoast_seo' field.
   *
   * @return array
   *   A nested array of entities and bundles. The outer array is keyed by
   *   entity id. The inner array is keyed by bundle id and contains the bundle
   *   label. If an entity has no bundles then the inner array is keyed by
   *   entity id.
   */
  public function getEnabledBundles() {
    $entities = [];

    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      $entity_id = $definition->id();
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_id);

      foreach ($bundles as $bundle_id => $bundle_metadata) {
        $bundle_label = $bundle_metadata['label'];
        $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_id, $bundle_id);

        if (!empty($field_definitions['yoast_seo'])) {
          if (!isset($entities[$entity_id])) {
            $entities[$entity_id] = [];
          }

          $entities[$entity_id][$bundle_id] = $bundle_label;
        }
      }
    }

    return $entities;
  }

  /**
   * Returns the Real-Time SEO field of the entity.
   *
   * Returns the first field of the entity that is a Real-Time SEO field or
   * null if none is found.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to find the Real-Time SEO field.
   *
   * @return NULL|\Drupal\Core\Field\FieldItemListInterface
   *   The field item list of the field or NULL if no RTSEO field was found.
   */
  public function getSeoField(FieldableEntityInterface $entity) {
    $definitions = $entity->getFieldDefinitions();

    // Find the first yoast_seo field on the entity.
    foreach ($definitions as $definition) {
      if ($definition->getType() === 'yoast_seo') {
        return $entity->get($definition->getName());
      }
    }

    // No field of yoast_seo type was found.
    return NULL;

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
