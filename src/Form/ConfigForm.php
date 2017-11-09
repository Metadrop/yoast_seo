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
        '#default_value' => $enabled_bundles[$entity_type],
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
    // Get the available entity types Yoast SEO supports.
    $entity_types = $this->seoManager->getSupportedEntityTypes();
    // Retrieve the form values.
    $values = $form_state->getValues();

    // Fields to attach.
    $to_attach = [];
    // Fields to detach.
    $to_detach = [];

    // Foreach entity types Yoast SEO can be enable for, check the bundle the
    // administrator wants to enable/disable Yoast SEO for.
    // TODO: Try and simplify the below logic.
    foreach ($entity_types as $entity_type_id => $entity_type_label) {
      // Get the available bundles Yoast SEO supports.
      $bundles = $this->seoManager->getEntityBundles($entity_type_id);
      // Get the bundles Yoast SEO has been enabled for.
      $enabled_bundles = $this->seoManager->getEnabledBundles($entity_type_id);

      // Foreach available bundles.
      foreach ($bundles as $bundle_id => $bundle_label) {
        // Yoast SEO is required to be enabled for.
        if (isset($values[$entity_type_id][$bundle_id])
            && $values[$entity_type_id][$bundle_id] !== 0
            && !in_array($bundle_id, $enabled_bundles)
        ) {
          $to_attach[$entity_type_id][] = $bundle_id;
        }
        // Yoast SEO is required to be disabled for.
        elseif (isset($values[$entity_type_id][$bundle_id])
                 && $values[$entity_type_id][$bundle_id] === 0
                 && in_array($bundle_id, $enabled_bundles)
        ) {
          $to_detach[$entity_type_id][] = $bundle_id;
        }
      }
    }

    // Attach fields to content types.
    foreach ($to_attach as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle_id) {
        $this->fieldManager->attachSeoFields($entity_type_id, $bundle_id);
      }
    }

    // Detach fields from content types.
    foreach ($to_detach as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle_id) {
        $this->fieldManager->detachSeoFields($entity_type_id, $bundle_id);
      }
    }

    drupal_set_message($this->t('Real-time SEO configuration by bundles has been saved successfully.'));
  }

}
