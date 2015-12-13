<?php

/**
 * @file
 * Contains Drupal\yoast_seo\Plugin\Field\FieldType\YoastSeoFocusKeywordItem.
 */

namespace Drupal\yoast_seo\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'yoast_seo_focus_keyword' field type.
 *
 * @FieldType(
 *   id = "yoast_seo_focus_keyword",
 *   label = @Translation("Yoast SEO focus keyword"),
 *   module = "yoast_seo",
 *   description = @Translation("The focus keyword for this entity.")
 * )
 */
class YoastSeoFocusKeywordItem extends FieldItemBase {
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
