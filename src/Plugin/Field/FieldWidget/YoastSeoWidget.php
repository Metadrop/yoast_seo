<?php
/**
 * @file
 * Contains \Drupal\yoast_seo\Plugin\Field\FieldWidget\YoastSeoWidget.
 */

namespace Drupal\yoast_seo\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\yoast_seo\YoastSeoManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced widget for yoast_seo field.
 *
 * @FieldWidget(
 *   id = "yoast_seo_widget",
 *   label = @Translation("Yoast SEO form"),
 *   field_types = {
 *     "yoast_seo"
 *   }
 * )
 */
class YoastSeoWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of YoastSeoManager service.
   */
  protected $yoastSeoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('yoast_seo.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, YoastSeoManager $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->yoastSeoManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Create the form element.
    $element['yoast_seo'] = array(
      '#type' => 'details',
      '#title' => t('Yoast SEO for drupal'),
      '#open' => TRUE,
      '#attached' => array(
        'library' => array(
          'yoast_seo/yoast_seo.edit_node',
          'yoast_seo/yoast_seo_admin',
        ),
      ),
    );

    $element['yoast_seo']['focus_keyword'] = array(
      '#type' => 'textfield',
      '#title' => t('Focus keyword'),
      '#default_value' => isset($items[$delta]->focus_keyword) ? $items[$delta]->focus_keyword : NULL,
      '#description' => t("Pick the main keyword or keyphrase that this post/page is about."),
    );

    $element['yoast_seo']['status'] = array(
      '#type' => 'hidden',
      '#title' => t('Yoast SEO status'),
      '#default_value' => isset($items[$delta]->status) ? $items[$delta]->status : NULL,
      '#description' => t("The SEO status in points."),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['status']        = $value['yoast_seo']['status'];
      $value['focus_keyword'] = $value['yoast_seo']['focus_keyword'];
    }
    return $values;
  }

}
