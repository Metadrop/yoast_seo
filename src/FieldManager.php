<?php

namespace Drupal\yoast_seo;

use \Drupal\Component\Utility\NestedArray;

/**
 * Class FieldManager.
 *
 * @package Drupal\yoast_seo
 */
class FieldManager {

  /**
   * Metatag logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor for YoastSeoFieldManager.
   */
  public function __construct() {
    $this->entity_manager = \Drupal::entityManager();
  }

  /**
   * Attach a field to a target content type.
   *
   * @param string $entity_type
   *   Entity type. Example 'node'.
   * @param string $bundle
   *   Bundle type.
   * @param mixed $field
   *   Field.
   */
  public function attachField($entity_type, $bundle, $field) {
    // Retrieve the yoast seo field attached to the target entity.
    $field_storage_config = $this->entity_manager->getStorage('field_storage_config')
                                               ->load($entity_type . '.' . $field['field_name']);

    // If the field hasn't been attached yet to the target entity, attach it.
    if (is_null($field_storage_config)) {
      $this->entity_manager->getStorage('field_storage_config')
                           ->create([
                             'field_name' => $field['field_name'],
                             'entity_type' => $entity_type,
                             'type' => $field['storage_type'],
                             'translatable' => $field['translatable'],
                           ])
                           ->save();
    }

    // Retrieve the yoast seo field attached to the target content type.
    $fields_config = \Drupal::service('entity_field.manager')
                            ->getFieldDefinitions($entity_type, $bundle);

    // If the field hasn't been attached yet to the content type, attach it.
    if (!isset($fields_config[$field['field_name']])) {

      $field_values = [
        'field_name' => $field['field_name'],
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field['field_label'],
        'translatable' => $field['translatable'],
      ];
      $this->entity_manager->getStorage('field_config')
                           ->create($field_values)
                           ->save();

      entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field['field_name'], array())
        ->save();
      entity_get_display($entity_type, $bundle, 'default')
        ->setComponent($field['field_name'], array())
        ->save();
    }
  }

  /**
   * Detach a field from a target content type.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param string $field_name
   *   Field name.
   */
  public function detachField($entity_type, $bundle, $field_name) {
    $fields_config = \Drupal::service('entity_field.manager')
                            ->getFieldDefinitions($entity_type, $bundle);

    if (isset($fields_config[$field_name])) {
      $fields_config[$field_name]->delete();
    }
  }

  /**
   * Check if a field has been already attached to a bundle.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param string $field_name
   *   Field name.
   *
   * @return bool
   *   Whether it is attached or not.
   */
  public function isAttached($entity_type, $bundle, $field_name) {
    $fields_config = \Drupal::service('entity_field.manager')
                            ->getFieldDefinitions($entity_type, $bundle);

    return isset($fields_config[$field_name]);
  }


}
