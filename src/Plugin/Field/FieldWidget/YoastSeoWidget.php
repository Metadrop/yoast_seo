<?php

namespace Drupal\yoast_seo\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\yoast_seo\SeoManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced widget for yoast_seo field.
 *
 * @FieldWidget(
 *   id = "yoast_seo_widget",
 *   label = @Translation("Real-time SEO form"),
 *   field_types = {
 *     "yoast_seo"
 *   }
 * )
 */
class YoastSeoWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Instance of YoastSeoManager service.
   *
   * @var \Drupal\yoast_seo\SeoManager
   */
  protected $yoastSeoManager;

  /**
   * Target elements for Javascript.
   *
   * @var array
   */
  public static $jsTargets = [
    'wrapper_target_id'       => 'yoast-wrapper',
    'snippet_target_id'       => 'yoast-snippet',
    'output_target_id'        => 'yoast-output',
    'overall_score_target_id' => 'yoast-overall-score',
  ];

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
      $container->get('entity_field.manager'),
      $container->get('yoast_seo.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityFieldManagerInterface $entity_field_manager, SeoManager $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->yoastSeoManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $form['#yoast_settings'] = $this->getSettings();

    // Create the form element.
    $element['yoast_seo'] = [
      '#type' => 'details',
      '#title' => $this->t('Real-time SEO for drupal'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'yoast_seo/yoast_seo_core',
          'yoast_seo/yoast_seo_admin',
        ],
      ],
    ];

    $element['yoast_seo']['focus_keyword'] = [
      '#id' => Html::getUniqueId('yoast_seo-' . $delta . '-focus_keyword'),
      '#type' => 'textfield',
      '#title' => $this->t('Focus keyword'),
      '#default_value' => isset($items[$delta]->focus_keyword) ? $items[$delta]->focus_keyword : NULL,
      '#description' => $this->t("Pick the main keyword or keyphrase that this post/page is about."),
    ];

    $element['yoast_seo']['overall_score'] = [
      '#theme' => 'overall_score',
      '#overall_score_target_id' => self::$jsTargets['overall_score_target_id'],
      '#overall_score' => $this->yoastSeoManager->getScoreStatus(isset($items[$delta]->status) ? $items[$delta]->status : 0),
    ];

    $element['yoast_seo']['status'] = [
      '#id' => Html::getUniqueId('yoast_seo-' . $delta . '-status'),
      '#type' => 'hidden',
      '#title' => $this->t('Real-time SEO status'),
      '#default_value' => isset($items[$delta]->status) ? $items[$delta]->status : NULL,
      '#description' => $this->t("The SEO status in points."),
    ];

    // Snippet.
    $element['yoast_seo']['snippet_analysis'] = [
      '#theme' => 'yoast_snippet',
      '#wrapper_target_id' => self::$jsTargets['wrapper_target_id'],
      '#snippet_target_id' => self::$jsTargets['snippet_target_id'],
      '#output_target_id' => self::$jsTargets['output_target_id'],
    ];

    $js_config = $this->getJavaScriptConfiguration();

    $js_config['fields']['focus_keyword'] = $element['yoast_seo']['focus_keyword']['#id'];
    $js_config['fields']['seo_status'] = $element['yoast_seo']['status']['#id'];

    $form_object = $form_state->getFormObject();

    if ($form_object instanceof EntityForm) {
      $js_config['is_new'] = $form_object->getEntity()->isNew();
    }
    else {
      // If we aren't working with an entity we assume whatever we are working
      // with is new.
      $js_config['is_new'] = TRUE;
    }

    $element['#attached']['drupalSettings']['yoast_seo'] = $js_config;

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

  /**
   * Returns the JavaScript configuration for this widget.
   *
   * @return array
   *   The configuration that should be attached for the module to work.
   */
  protected function getJavaScriptConfiguration() {
    global $base_root;
    $score_to_status_rules = $this->yoastSeoManager->getConfiguration()['score_to_status_rules'];

    // TODO: Use dependency injection for language manager.
    // TODO: Translate to something usable by YoastSEO.js.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $configuration = [
      // Set localization within the YoastSEO.js library.
      'language' => $language,
      // Set the base for URL analysis.
      'base_root' => $base_root,
      // Set up score to indiciator word rules.
      'score_status' => $score_to_status_rules,
    ];

    // Set up the names of the text outputs.
    foreach (self::$jsTargets as $js_target_name => $js_target_id) {
      $configuration['targets'][$js_target_name] = $js_target_id;
    }

    return $configuration;
  }

}
