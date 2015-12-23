<?php
namespace Drupal\yoast_seo\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'example_formatter' formatter
 *
 * @FieldFormatter(
 *    id = "yoastseo_formatter",
 *    label = @Translation("YoastSeo formatter"),
 *    field_types = {
 *      "yoast_seo",
 *    }
 * )
 */
class YoastSeoFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      //$url = $item->url;
//      $elements[$delta] = array(
//        '#theme' => 'content_score',
//        '#scoretest' => 'test'
//      );

      $elements[$delta] = array(
        '#markup' => '<div>' . $item->status . '</div>',
      );
    }

    return $elements;
  }

}