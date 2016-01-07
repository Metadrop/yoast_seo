<?php

/**
 * @file
 * Yoast Seo Formatter.
 */

namespace Drupal\yoast_seo\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'example_formatter' formatter.
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
      $yoast_seo_field_manager = \Drupal::service('yoast_seo.field_manager');
      $status = $yoast_seo_field_manager->getScoreStatus($item->status);

      // TODO : find a way to give a weight, so the column doesn't appear
      // at the end.
      // Get template for the snippet.
      $overall_score_tpl = [
        '#theme' => 'overall_score',
        '#overall_score' => $status,
        '#attached' => [
          'library' => [
            'yoast_seo/yoast_seo_view',
          ],
        ],
      ];
      $output = drupal_render($overall_score_tpl);

      $elements[$delta] = array(
        '#markup' => $output,
      );
    }

    return $elements;
  }

}
