<?php

namespace Drupal\yoast_seo\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'yoast_seo' field type.
 *
 * @FieldType(
 *   id = "yoast_seo",
 *   label = @Translation("Real-time SEO status & focused keywords"),
 *   module = "yoast_seo",
 *   description = @Translation("The Real-time SEO status in points and the focused keywords."),
 *   default_widget = "yoast_seo_widget",
 *   default_formatter = "string"
 * )
 */
class YoastSeoItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'status' => array(
          'type' => 'varchar',
          'length' => 256,
          'not null' => FALSE,
        ),
        'focus_keyword' => array(
          'type' => 'varchar',
          'length' => 256,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['status']        = DataDefinition::create('string');
    $properties['focus_keyword'] = DataDefinition::create('string');

    return $properties;
  }

}
