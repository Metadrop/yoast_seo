<?php

namespace Drupal\yoast_seo;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Class FieldManager.
 *
 * @package Drupal\yoast_seo
 */
class FieldManager {

  /**
   * The field definitions that are required for this module.
   *
   * @return array
   *   The field definitions required for this module to work for an entity.
   */
  public function getFieldDefinitions() {
    return [
      'field_yoast_seo' => [
        'field_name' => 'field_yoast_seo',
        'field_label' => 'Real-time SEO',
        'storage_type' => 'yoast_seo',
        'translatable' => TRUE,
      ],
    ];
  }

  /**
   * Attach the yoast seo fields to a target content type.
   *
   * @param string $entity_type
   *   Bundle.
   * @param string $bundle
   *   Entity type.
   */
  public function attachSeoFields($entity_type, $bundle) {
    foreach ($this->getFieldDefinitions() as $field) {
      $this->attachField($entity_type, $bundle, $field);
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
  public function detachSeoFields($entity_type, $bundle) {
    foreach ($this->getFieldDefinitions() as $field_name => $field) {
      $this->detachField($entity_type, $bundle, $field_name);
    }
  }

  /**
   * Attach a field to a target entity type.
   *
   * @param string $entity_type_id
   *   Entity type. Example 'node'.
   * @param string $bundle
   *   Bundle type.
   * @param mixed $field
   *   Field.
   */
  public function attachField($entity_type_id, $bundle, $field) {
    // Retrieve the yoast seo field attached to the target entity.
    $field_storage = $this->findOrCreateStorageConfig($entity_type_id, $field);

    $field['field_storage'] = $field_storage;
    $field['bundle'] = $bundle;

    $this->createFieldIfNotExists($entity_type_id, $field);
  }

  /**
   * Detach a field from a target content type.
   *
   * @param string $entity_type_id
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param string $field_name
   *   Field name.
   */
  public function detachField($entity_type_id, $bundle, $field_name) {
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);

    if (!is_null($field)) {
      $field->delete();
    }
  }

  /**
   * Finds or creates storage configuration for a given entity and field.
   *
   * This will load a FieldStorageConfig entity if one exists. If it doesn't
   * yet exist then one will be created.
   *
   * @param string $entity_type_id
   *   The entity type for which to get the field storage configuration.
   * @param array $field
   *   A field storage configuration to find or create.
   *
   * @return \Drupal\field\Entity\FieldStorageConfig
   *   The found or created FieldStorageConfig entity.
   */
  protected function findOrCreateStorageConfig($entity_type_id, array $field) {
    $storage = FieldStorageConfig::loadByName($entity_type_id, $field['field_name']);

    if (is_null($storage)) {
      $field['entity_type'] = $entity_type_id;

      $storage = FieldStorageConfig::create($field);

      $storage->save();
    }

    return $storage;
  }

  /**
   * Creates the field config for an entity if it doesn't already exist.
   *
   * @param string $entity_type_id
   *   The entity type for which to create the field.
   * @param array $field_config
   *   The field configuration with the bundle that defines the field.
   */
  protected function createFieldIfNotExists($entity_type_id, array $field_config) {
    $bundle = $field_config['bundle'];
    $field_name = $field_config['field_name'];
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);

    if (is_null($field)) {
      // Create the field for this bundle.
      $field = FieldConfig::create($field_config);

      $field->save();

      // Set up form and view displays.
      EntityFormDisplay::load($entity_type_id . '.' . $bundle . '.default')
        ->setComponent($field_name, [])
        ->save();

      EntityViewDisplay::load($entity_type_id . '.' . $bundle . '.default')
        ->setComponent($field_name, [])
        ->save();
    }
  }

  /**
   * Check if a field has been already attached to a bundle.
   *
   * @param string $entity_type_id
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param string $field_name
   *   Field name.
   *
   * @return bool
   *   Whether it is attached or not.
   */
  public function isAttached($entity_type_id, $bundle, $field_name) {
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    return !is_null($field);
  }

}
