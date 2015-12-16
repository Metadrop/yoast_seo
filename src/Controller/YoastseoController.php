<?php
/**
 * @file
 * Contains \Drupal\yoast_seo\Controller\YoastSeoController.
 */

namespace Drupal\yoast_seo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class YoastSeoController extends ControllerBase {

  /**
   * A simple demo content page.
   *
   * @return array
   */
  public function content() {
    return [
      '#type'   => 'markup',
      '#markup' => $this->t('Hello, World!'),
    ];
  }

  /**
   * Settings page.
   *
   * @return array
   */
  public function settings() {
    $form = [ ];

    // Check if XML Sitemap is installed and enabled.
    if (\Drupal::moduleHandler()->moduleExists('xmlsitemap')) {
      // Inform the user about altering the XML Sitemap configuration on the
      // module configuration page if he has access to do so.
      if (\Drupal::currentUser()->hasPermission('administer xmlsitemap')) {
        $xmlsitemap_description = t(
          'You can configure the XML Sitemap settings at the !url.',
          [
            '!url' => \Drupal::l(
              t('XML Sitemap configuration page'),
              Url::fromRoute('xmlsitemap.admin_search')
            )
          ]
        );
      }
      else {
        $xmlsitemap_description
          = t('You do not have the permission to administer XML Sitemap.');
      }
    }
    else {
      // XML Sitemap is not enabled, inform the user he should think about
      // installing and enabling it.
      $xmlsitemap_description = t(
        'You currently do not have XML Sitemap enabled. We strongly recommend you to install XML Sitemap. You can download the module from <a href="@project-page-url">@project-page-url</a>.',
        [ '@project-page-url' => 'https://www.drupal.org/project/xmlsitemap' ]
      );
    }

    $form['xmlsitemap'] = [
      '#type'   => 'details',
      '#title'  => t('XML Sitemap'),
      '#markup' => $xmlsitemap_description,
      '#open'   => TRUE,
    ];

    // Inform the user about altering the Metatag configuration on the module
    // configuration page if he has access to do so.
    // We do not check if the module is enabled since it is our dependency.
    if (\Drupal::currentUser()->hasPermission('administer meta tags')) {
      $metatag_description = t(
        'You can configure and override the Metatag title & description default settings at the @url.',
        [
          '@url' => \Drupal::l(
            t('Metatag configuration page'),
            Url::fromRoute('entity.metatag_defaults.collection')
          )
        ]
      );
    }
    else {
      $metatag_description
        = t('You currently do not have the permission to administer Metatag.');
    }

    $form['metatag'] = [
      '#type'   => 'details',
      '#title'  => t('Configure Metatag default templates'),
      '#markup' => $metatag_description,
      '#open'   => TRUE,
    ];

    // Add to the page the Yoast SEO form which allows the administrator to enable/disable
    // Yoast SEO by bundles.
    $config_form       = \Drupal::formBuilder()
                                ->getForm('Drupal\yoast_seo\Form\YoastSeoConfigForm');
    $form['yoast_seo'] = [
      '#type'        => 'details',
      '#title'       => 'Configure Yoast SEO by bundles',
      '#description' => 'Select the bundles Yoast SEO will be enabled for',
      '#markup'      => render($config_form),
      '#open'        => TRUE
    ];

    return $form;
  }
}