<?php
/**
 * @file
 * Contains \Drupal\yoast_seo\Plugin\Field\FieldWidget\YoastSeoStatusWidget.
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
 * Advanced widget for yoast_seo_status field.
 *
 * @FieldWidget(
 *   id = "yoast_seo_widget",
 *   label = @Translation("Advanced meta tags form"),
 *   field_types = {
 *     "yoast_seo_status"
 *   }
 * )
 */
class YoastSeoStatusWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of YoastSeoManager service.
   */
  protected $yoast_seo_manager;

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
    $this->yoast_seoManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    //$item = $items[$delta];
    //$default_tags = yoast_seo_get_default_tags();
    //
    //// Retrieve the values for each yoast_seo from the serialized array.
    //$values = array();
    //if (!empty($item->value)) {
    //  $values = unserialize($item->value);
    //}
    //
    //// Populate fields which have not been overridden in the entity.
    //foreach ($default_tags as $tag_id => $tag_value) {
    //  if (!isset($values[$tag_id]) && !empty($tag_value)) {
    //    $values[$tag_id] = $tag_value;
    //  }
    //}

    // Create the form element.
    $element['yoast_seo'] = array(
      '#type' => 'details',
      '#title' => t('Yoast SEO for drupal'),
      '#open' => TRUE,
    );

    $element['yoast_seo']['yoast_seo_status'] = array(
      '#type' => 'number',
      '#title' => t('Yoast SEO score'),
      '#default_value' => '0',
      '#description' => t("The SEO status in points.")
    );

    $element['yoast_seo']['yoast_seo_focus_keyword'] = array(
      '#type' => 'textfield',
      '#title' => t('Yoast SEO focus keyword'),
      '#default_value' => '0',
      '#description' => t("The focus keyword for this entity.")
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return 'test';
    //// Flatten the values array to remove the groups and then serialize all the
    //// yoast_seos into one value for storage.
    //$tag_manager = \Drupal::service('plugin.manager.yoast_seo.tag');
    //$tags = $tag_manager->getDefinitions();
    //foreach ($values as &$value) {
    //  $flattened_value = array();
    //  foreach ($value as $group) {
    //    // Exclude the "original delta" value.
    //    if (is_array($group)) {
    //      foreach ($group as $tag_id => $tag_value) {
    //        $tag = $tag_manager->createInstance($tag_id);
    //        $tag->setValue($tag_value);
    //        if (!empty($tag->value())) {
    //          $flattened_value[$tag_id] = $tag->value();
    //        }
    //      }
    //    }
    //  }
    //  $value = serialize($flattened_value);
    //}
    //
    //return $values;
  }

}
