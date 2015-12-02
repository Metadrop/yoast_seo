<?php

/**
 * @file
 * Contains Drupal\field_example\Plugin\Field\FieldType\RgbItem.
 */

namespace Drupal\yoast_seo\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_yoast_seo_status' field type.
 *
 * @FieldType(
 *   id = "field_yoast_seo_status",
 *   label = @Translation("Yoast SEO status"),
 *   module = "yoast_seao",
 *   description = @Translation("The SEO status in points.")
 * )
 */
class YoastSeoStatusItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string');

    return $properties;
  }
}
