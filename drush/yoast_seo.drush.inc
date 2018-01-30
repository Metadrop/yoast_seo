<?php

/**
 * @file
 * Drush integration for the yoast_seo module.
 */

/**
 * Implements hook_drush_command().
 */
function yoast_seo_drush_command() {
  $items = [];

  // Command yoastseo-prepare-uninstall
  // prepares the module to be uninstalled.
  $items['yoastseo-prepare-uninstall'] = [
    'description' => "Prepare uninstalling Real-time SEO. Remove fields and data.",
    'aliases' => ['ypu'],
  ];

  return $items;
}

/**
 * Prepare uninstall for yoast seo.
 *
 * This enables to delete the fields from drupal so uninstalling the module
 * doesn't fail.
 * It does 2 things :
 * - Detach Yoast SEO from all yoastseo enabled content types
 * - Purge the fields list from all temporary deleted fields.
 *
 * @see https://www.drupal.org/node/2418659
 */
function drush_yoast_seo_yoastseo_prepare_uninstall() {

  /** @var \Drupal\yoast_seo\SeoManager $yoast_seo_manager */
  $yoast_seo_manager = \Drupal::service('yoast_seo.manager');

  // 2) Detach Yoast SEO from all the content types it is attached to.
  foreach ($yoast_seo_manager->getEnabledBundles() as $entity_type_id => $bundles) {
    foreach ($bundles as $bundle_id) {
      echo "Detach Real-Time SEO fields for {$entity_type_id} {$bundle_id}\n";
      $yoast_seo_manager->disableFor($entity_type_id, $bundle_id);
    }
  }

  // 3) Purge field data.
  do {
    field_purge_batch(1000);
    $properties = [
      'deleted' => TRUE,
      'include_deleted' => TRUE,
    ];
    $fields = entity_load_multiple_by_properties('field_config', $properties);
  } while ($fields);

}
