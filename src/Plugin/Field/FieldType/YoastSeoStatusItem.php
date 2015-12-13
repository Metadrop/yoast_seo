<?php

/**
 * @file
 * Contains Drupal\yoast_seo\Plugin\Field\FieldType\YoastSeoStatusItem.
 */

namespace Drupal\yoast_seo\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'yoast_seo_status' field type.
 *
 * @FieldType(
 *   id = "yoast_seo_status",
 *   label = @Translation("Yoast SEO status"),
 *   module = "yoast_seo",
 *   description = @Translation("The SEO status in points."),
 *   default_widget = "yoast_seo_widget",
 *   default_formatter = "string"
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
