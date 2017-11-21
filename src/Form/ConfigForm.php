<?php

namespace Drupal\yoast_seo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yoast_seo\FieldManager;
use Drupal\yoast_seo\SeoManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class YoastSeoConfigForm.
 *
 * @package Drupal\metatag\Form
 */
class ConfigForm extends FormBase {

  /**
   * The Real-Time SEO Manager service.
   *
   * @var \Drupal\yoast_seo\SeoManager
   */
  protected $seoManager;

  /**
   * The Field Manager service.
   *
   * @var \Drupal\yoast_seo\FieldManager
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(SeoManager $seoManager, FieldManager $fieldManager) {
    $this->seoManager = $seoManager;
    $this->fieldManager = $fieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yoast_seo.manager'),
      $container->get('yoast_seo.field_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yoast_seo_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = $this->seoManager->getSupportedEntityTypes();
    $supported_bundles = $this->seoManager->getEntityBundles();
    $enabled_bundles = $this->seoManager->getEnabledBundles();

    foreach ($entity_types as $entity_type => $entity_label) {
      // Add a checkboxes collection to allow the administrator to
      // enable/disable SEO Analysis for supported bundles.
      $form[$entity_type] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('@label', ['@label' => $entity_label]),
        '#options' => $supported_bundles[$entity_type],
        '#required' => FALSE,
        '#default_value' => !empty($enabled_bundles[$entity_type]) ? array_keys($enabled_bundles[$entity_type]) : [],
      ];
    }

    // Add a save action.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the available entity/bundles that the module supports.
    $entity_bundles = $this->seoManager->getEntityBundles();
    // Retrieve the form values.
    $values = $form_state->getValues();

    // Iterate over all supported entities.
    foreach ($entity_bundles as $entity_type_id => $bundles) {
      // Then over all bundles for that entity.
      foreach ($bundles as $bundle_id => $bundle_label) {
        // If this value was not in our form we skip it.
        if (!isset($values[$entity_type_id][$bundle_id])) {
          continue;
        }

        // If it's checked now but wasn't enabled, enable it.
        if ($values[$entity_type_id][$bundle_id] !== 0
          && !$this->fieldManager->isEnabledFor($entity_type_id, $bundle_id)) {
          $this->fieldManager->attachSeoFields($entity_type_id, $bundle_id);
        }
        // If it's not checked but it was enabled, disable it.
        elseif ($values[$entity_type_id][$bundle_id] === 0
          && $this->fieldManager->isEnabledFor($entity_type_id, $bundle_id)) {
          $this->fieldManager->detachSeoFields($entity_type_id, $bundle_id);
        }
      }
    }

    drupal_set_message($this->t('Real-time SEO configuration by bundles has been saved successfully.'));
  }

}
