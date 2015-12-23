<?php

/**
 * @file
 * Contains the \Drupal\yoast_seo\YoastSeoFieldManager class.
 */

namespace Drupal\yoast_seo;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use \Drupal\field\Entity\FieldConfig;
use \Drupal\Component\Utility\NestedArray;
use \Drupal\Component\Utility\Html;

/**
 * Class YoastSeoFieldManager.
 *
 * @package Drupal\yoast_seo
 */
class YoastSeoFieldManager {

  public $fieldsConfiguration = [
    // Paths to access the fields inside the form array.
    'paths' => [
      'title' => 'title.widget.0.value',
      'summary' => 'body.widget.0.summary',
      'body' => 'body.widget.0.value',
      'focus_keyword' => 'field_yoast_seo.widget.0.yoast_seo.focus_keyword',
      'seo_status' => 'field_yoast_seo.widget.0.yoast_seo.status',
      'path' => 'path.widget.0.alias',
    ],

    // Fields to include in the field section of the configuration.
    'fields' => [
      'title',
      'summary',
      'body',
      'focus_keyword',
      'seo_status',
      'path'
    ],

    // Tokens for the fields.
    'tokens' => [
      '[current-page:title]' => 'title',
      '[node:title]' => 'title',
      '[current-page:body]' => 'body',
      '[node:body]' => 'body',
      '[current-page:summary]' => 'summary',
      '[node:summary]' => 'summary',
    ],

    'targets' => [

    ],
  ];

  /**
   * Metatag logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor for YoastSeoFieldManager.
   */
  public function __construct() {
    $this->entity_manager = \Drupal::entityManager();

    // Get Output generated ids.
    $this->fieldsConfiguration['targets'] = [
      'wrapper_target_id'       => Html::getUniqueId('yoast-wrapper'),
      'snippet_target_id'       => Html::getUniqueId('yoast-snippet'),
      'output_target_id'        => Html::getUniqueId('yoast-output'),
      'overall_score_target_id' => Html::getUniqueId('yoast-overall-score'),
    ];
  }


  /**
   * Our helper to insert values in a form from a given key.
   * example : formSet($form, 'myform.#value', 'valueToInsert');
   * TODO : move this helper somewhere else.
   * @param $form
   * @param $key
   * @param $value
   *
   * @return mixed
   */
  private function formSet(&$form, $key, $value) {
    return NestedArray::setValue(
      $form,
      explode('.', $key),
      $value
    );
  }

  /**
   * Our helper to retrieve values in a form from a given key.
   * example : formGet($form, 'myform.#value');
   * TODO : move this helper somewhere else.
   * @param $form
   * @param $key
   *
   * @return mixed
   */
  private function formGet($form, $key) {
    return NestedArray::getValue(
      $form,
      explode('.', $key)
    );
  }

  /**
   * Attach a field to a target content type.
   *
   * @param $entity_type
   * @param $bundle
   * @param $field
   */
  public function attachField($entity_type, $bundle, $field) {
    // Retrieve the yoast seo field attached to the target entity.
    $fieldStorageConfig = $this->entity_manager->getStorage('field_storage_config')
                                               ->load($entity_type . '.' . $field['field_name']);

    // If the field hasn't been attached yet to the target entity, attach it.
    if (is_null($fieldStorageConfig)) {
      $this->entity_manager->getStorage('field_storage_config')
                           ->create([
                             'field_name' => $field['field_name'],
                             'entity_type' => $entity_type,
                             'type' => $field['storage_type'],
                             'translatable' => $field['translatable'],
                           ])
                           ->save();
    }

    // Retrieve the yoast seo field attached to the target content type.
    $fields_config = \Drupal::service('entity_field.manager')
                            ->getFieldDefinitions($entity_type, $bundle);

    // If the field hasn't been attached yet to the content type, attach it.
    if (!isset($fields_config[$field['field_name']])) {

      $field_values = [
        'field_name' => $field['field_name'],
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field['field_label'],
        'translatable' => $field['translatable'],
      ];
      $this->entity_manager->getStorage('field_config')
                           ->create($field_values)
                           ->save();

      entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field['field_name'], array())
        ->save();
      entity_get_display($entity_type, $bundle, 'default')
        ->setComponent($field['field_name'], array())
        ->save();
    }
  }

  /**
   * Detach a field from a target content type.
   *
   * @param $entity_type
   * @param $bundle
   * @param $field_name
   */
  public function detachField($entity_type, $bundle, $field_name) {
    $fields_config = \Drupal::service('entity_field.manager')
                            ->getFieldDefinitions($entity_type, $bundle);

    if (isset($fields_config[$field_name])) {
      $fields_config[$field_name]->delete();
    }
  }

  /**
   * Check if a field has been already attached to a bundle.
   *
   * @param $entity_type
   * @param $bundle
   * @param $field_name
   *
   * @return boolean
   */
  public function isAttached($entity_type, $bundle, $field_name) {
    $fields_config = \Drupal::service('entity_field.manager')
                            ->getFieldDefinitions($entity_type, $bundle);

    return isset($fields_config[$field_name]);
  }


  /**
   * Set fields configuration from a form.
   *
   * Explores the field present in the form and build a setting array
   * that will be used by yoast_seo javascript.
   *
   * @param $formAfterBuild
   *   node form after build
   *
   * @return mixed
   *   transformed form
   */
  public function setFieldsConfiguration($formAfterBuild) {
    // Fields requested.

    // Attach settings in drupalSettings for each required field.
    foreach ($this->fieldsConfiguration['fields'] as $fieldName) {
      $fieldId = $this->formGet($formAfterBuild, $this->fieldsConfiguration['paths'][$fieldName] . '.#id');
      $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['fields'][$fieldName] = $fieldId;
    }

    // Attach settings for the tokens.
    foreach($this->fieldsConfiguration['tokens'] as $fieldName => $token) {
      $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['tokens'][$fieldName] = $token;
    }
    // Other tokens commonly used.
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['tokens']['[site:name]'] = \Drupal::config('system.site')->get('name');
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['tokens']['[site:slogan]'] = \Drupal::config('system.site')->get('slogan');


    // Attach settings for the targets.
    foreach($this->fieldsConfiguration['targets'] as $targetName => $targetId) {
      $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['targets'][$targetName] = $targetId;
    }

    $isDefaultMetaTitle = !empty($formAfterBuild['field_meta_tags']['widget'][0]['basic']['title']['#default_value']) ? TRUE : FALSE;
    $isDefaultKeyword = !empty($formAfterBuild['field_yoast_seo']['widget'][0]['yoast_seo']['focus_keyword']['#default_value']) ? TRUE : FALSE;
    $isDefaultMetaDescription = !empty($formAfterBuild['field_meta_tags']['widget'][0]['basic']['description']['#default_value']) ? TRUE : FALSE;
    $isDefaultBody = !empty($formAfterBuild['body']['widget'][0]['#default_value']) ? TRUE : FALSE;

    // TODO : move this configuration into YoastSEOFieldManager
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['default_text'] = [
      'url' => $formAfterBuild['path']['widget'][0]['alias']['#default_value'],
      'title' => $isDefaultMetaTitle ? $formAfterBuild['field_meta_tags']['widget'][0]['basic']['title']['#default_value'] : '',
      'keyword' => $isDefaultKeyword ? $formAfterBuild['field_yoast_seo']['widget'][0]['yoast_seo']['focus_keyword']['#default_value'] : '',
      'meta' => $isDefaultMetaDescription ? $formAfterBuild['field_meta_tags']['widget'][0]['basic']['description']['#default_value'] : '',
      'body' => $isDefaultBody ? $formAfterBuild['body']['widget'][0]['#default_value'] : '',
    ];
    // FIELDS
    // Add Metatag fields
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['fields']['meta_title'] = $formAfterBuild['field_meta_tags']['widget'][0]['basic']['title']['#id'];
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['fields']['meta_description'] = $formAfterBuild['field_meta_tags']['widget'][0]['basic']['description']['#id'];

    // Placeholders.
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['placeholder_text'] = [
      'title' => t('Please click here to alter your page meta title'),
      'description' => t('Please click here and alter your page meta description.'),
      'url' => t('example-post'),
    ];

    global $base_root;
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['seo_title_overwritten'] = $isDefaultMetaTitle;
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['text_format'] = $formAfterBuild['body']['widget'][0]['#format'];
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['base_root'] = $base_root;

    // Other conf
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['analyzer'] = TRUE;
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['snippet_preview'] = TRUE;
    $formAfterBuild['#attached']['drupalSettings']['yoast_seo']['form_id'] = $formAfterBuild['#id'];

    return $formAfterBuild;
  }

  /**
   * Add code for snippet.
   * @param $form
   */
  public function addSnippetEditorMarkup($form) {

    // Get template for the snippet.
    $snippetTpl = [
      '#theme' => 'snippet',
      '#wrapper_target_id' => $this->fieldsConfiguration['targets']['wrapper_target_id'],
      '#snippet_target_id' => $this->fieldsConfiguration['targets']['snippet_target_id'],
      '#output_target_id' => $this->fieldsConfiguration['targets']['output_target_id'],
    ];
    $output = drupal_render($snippetTpl);

    // Add rendered template to the form, where we want the snippet.
    $this->formSet($form, 'field_yoast_seo.widget.0.yoast_seo.snippet_analysis', [
      '#weight' => $form['body']['#weight'] + 1,
      '#markup' => $output,

    ]);

    return $form;
  }

  public function addOverallScoreMarkup($form) {

    // Get template for the snippet.
    $overallScoreTpl = [
      '#theme' => 'overall_score',
      '#overall_score_target_id' => $this->fieldsConfiguration['targets']['overall_score_target_id'],
      '#overall_score' => 0,
    ];
    $output = drupal_render($overallScoreTpl);

    $this->formSet($form, 'field_yoast_seo.widget.0.yoast_seo.focus_keyword.#suffix', $output);

    return $form;
  }

}
