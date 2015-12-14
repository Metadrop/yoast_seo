<?php

/**
 * @file
 * Contains \Drupal\yoast_seo\Form\YoastSeoConfigForm.
 */

namespace Drupal\yoast_seo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class YoastSeoConfigForm.
 *
 * @package Drupal\metatag\Form
 */
class YoastSeoConfigForm extends FormBase {

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
    // Available entity types supported by Yoast SEO.
    $entity_types = $this->getAvailableEntityTypes();
    foreach ($entity_types as $entity_type => $entity_label) {
      // Get the available bundles Yoast SEO supports.
      $options = $this->getAvailableBundles($entity_type);
      // Get the bundles Yoast SEO has been enabled for.
      $enabled_bundles = $this->getEnabledBundles($entity_type);

      // Add a checkboxes collection to allow the administrator to enable/disable
      // Yoast SEO for supported bundles.
      $form[$entity_type] = array(
        '#type' => 'checkboxes',
        '#title' => t($entity_label),
        '#options' => $options,
        '#required' => FALSE,
        '#default_value' => $enabled_bundles,
      );
    }

    // Add a save action.
    $form['actions']           = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $yoast_seo_manager = \Drupal::service('yoast_seo.manager');
    // Get the available entity types Yoast SEO supports.
    $entity_types = $this->getAvailableEntityTypes();
    // Retrieve the form values.
    $values = $form_state->getValues();

    // Foreach entity types Yoast SEO can be enable for, check the bundle the
    // administrator wants to enable/disable Yoast SEO for.
    foreach ($entity_types as $entity_type_id => $entity_type_label) {
      // Get the available bundles Yoast SEO supports.
      $bundles = $this->getAvailableBundles($entity_type_id);
      // Get the bundles Yoast SEO has been enabled for.
      $enabled_bundles = $this->getEnabledBundles($entity_type_id);

      // Foreach available bundles.
      foreach ($bundles as $bundle_id => $bundle_label) {
        // Yoast SEO is required to be enabled for.
        if (isset($values[$entity_type_id][$bundle_id])
            && $values[$entity_type_id][$bundle_id] !== 0
            && !in_array($bundle_id, $enabled_bundles)
        ) {
          $yoast_seo_manager->attachYoastSeoFields($entity_type_id, $bundle_id);
        }
        // Yoast SEO is required to be disabled for.
        else if (isset($values[$entity_type_id][$bundle_id])
                 && $values[$entity_type_id][$bundle_id] === 0
                 && in_array($bundle_id, $enabled_bundles)
        ) {
          $yoast_seo_manager->detachYoastSeoFields($entity_type_id, $bundle_id);
        }
      }
    }

    drupal_set_message($this->t('Yoast SEO configuration by bundles has been saved successfully.'));
  }

  /**
   * Returns an array of available entity types Yoast SEO can be enabled for.
   *
   * @return array
   *   A list of available entity types as $id => $label.
   */
  protected function getAvailableEntityTypes() {
    // @todo Should be the same than the ones supported by the metatag module.
    return [
      'node' => 'Node',
      'taxonomy_term' => 'Taxonomy term',
    ];
  }

  /**
   * Returns an array of available bundles Yoast SEO can be enabled for.
   *
   * @param string $entity_type The entity
   *
   * @return array
   *   A list of available bundles as $id => $label.
   */
  protected function getAvailableBundles($entity_type = 'node') {
    $options        = array();
    $entity_manager = \Drupal::service('entity.manager');

    // Retrieve the bundles the entity type contains.
    $bundles = $entity_manager->getBundleInfo($entity_type);
    foreach ($bundles as $bundle_id => $bundle_metadata) {
      $options[$bundle_id] = $bundle_metadata['label'];
    }

    return $options;
  }

  /**
   * Returns an array of bundles Yoast SEO has been enabled for.
   *
   * @param string $entity_type The entity
   *
   * @return array
   *   A list of enabled bundles as $id => $label.
   */
  protected function getEnabledBundles($entity_type = 'node') {
    $enabled_bundles         = array();
    $yoast_seo_field_manager = \Drupal::service('yoast_seo.field_manager');

    // Get the available bundles Yoast SEO supports.
    $bundles = $this->getAvailableBundles($entity_type);

    // Retrieve the bundles for which Yoast SEO has already been enabled for.
    foreach ($bundles as $bundle_id => $bundle_label) {
      if ($yoast_seo_field_manager->isAttached($entity_type, $bundle_id, 'field_yoast_seo')) {
        $enabled_bundles[] = $bundle_id;
      }
    }

    return $enabled_bundles;
  }

}
