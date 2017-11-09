<?php

namespace Drupal\yoast_seo\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\yoast_seo\YoastSeoManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

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
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityFieldManagerInterface $entity_field_manager, YoastSeoManager $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
//    $this->entityFieldManager = $entity_field_manager;
//    $this->yoastSeoManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $form['#yoast_settings'] = $this->getSettings();

    // Create the form element.
    $element['yoast_seo'] = array(
      '#type' => 'details',
      '#title' => $this->t('Real-time SEO for drupal'),
      '#open' => TRUE,
      '#attached' => array(
        'library' => array(
          'yoast_seo/yoast_seo_core',
          'yoast_seo/yoast_seo_admin',
        ),
      ),
    );

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
      '#overall_score' => $this->getScoreStatus(isset($items[$delta]->status) ? $items[$delta]->status : 0),
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

    $js_confg = $this->getJavaScriptConfiguration();

    $js_confg['fields']['focus_keyword'] = $element['yoast_seo']['focus_keyword']['#id'];
    $js_confg['fields']['seo_status'] = $element['yoast_seo']['status']['#id'];

    $element['#attached']['drupalSettings']['yoast_seo'] = $js_confg;

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
    $score_to_status_rules = $this->getConfiguration()['score_to_status_rules'];

    // TODO: Use dependency injection for language manager
    // TODO: Translate to something usable by YoastSEO.js
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $configuration = [
      // Set localization within the YoastSEO.js library.
      'language' => $language,
      // Set the base for URL analysis.
      'base_root' => $base_root,
      // Set up score to indiciator word rules.
      'score_status' => $score_to_status_rules,
    ];

    // Set up the names of the text outputs
    foreach (self::$jsTargets as $js_target_name => $js_target_id) {
      $configuration['targets'][$js_target_name] = $js_target_id;
    }

    return $configuration;
  }


  /**
   * Get configuration from Yaml file.
   *
   * @return mixed
   *    Configuration details will be returned.
   *
   * @TODO: Turn this into proper Drupal configuration!
   */
  public function getConfiguration() {
    $conf = Yaml::parse(
      file_get_contents(
        drupal_get_path('module', 'yoast_seo') . '/config/yoast_seo.yml'
      )
    );
    return $conf;
  }

  /**
   * Get the status for a given score.
   *
   * @param int $score
   *   Score in points.
   *
   * @return string
   *   Status corresponding to the score.
   * TODO: Move this back to something like an SEO Assessor.
   */
  public function getScoreStatus($score) {
    $rules = $this->getConfiguration()['score_to_status_rules'];
    $default = $rules['default'];
    unset($rules['default']);

    foreach ($rules as $status => $status_rules) {
      $min_max_isset = isset($status_rules['min']) && isset($status_rules['max']);
      if (isset($status_rules['equal']) && $status_rules['equal'] == $score) {
        return $status;
      }
      elseif ($min_max_isset && $score > $status_rules['min'] && $score <= $status_rules['max']) {
        return $status;
      }
    }

    return $default;
  }

}
