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
    $yoast_seo_manager = \Drupal::service('yoast_seo.manager');

    // Available entity types supported by Yoast SEO.
    $entity_types = $this->getAvailableEntityTypes();
    foreach ($entity_types as $entity_type => $entity_label) {
      // Get the available bundles Yoast SEO supports.
      $options = $yoast_seo_manager->getAvailableBundles($entity_type);
      // Get the bundles Yoast SEO has been enabled for.
      $enabled_bundles = $yoast_seo_manager->getEnabledBundles($entity_type);

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
      $bundles = $yoast_seo_manager->getAvailableBundles($entity_type_id);
      // Get the bundles Yoast SEO has been enabled for.
      $enabled_bundles = $yoast_seo_manager->getEnabledBundles($entity_type_id);

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

    // If Yoast SEO is enabled for at least one content type,
    // we add it in the content view (if not already added).
    $node_enabled_bundles = $yoast_seo_manager->getEnabledBundles('node');
    if (sizeof($node_enabled_bundles) > 0) {
      $yoast_seo_manager->attachFieldHandlerToContentView();
    }
    // Else, if Yoast SEO is not enabled for no content types,
    // we removed it from the content view.
    else {
      // TODO : WHY DOES IT BREAK THE DRUPAL VIEW SYSTEM ?
      //$yoast_seo_manager->detachFieldHandlerFromContentView();
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

}
