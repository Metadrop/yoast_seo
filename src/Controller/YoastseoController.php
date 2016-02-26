<?php
/**
 * @file
 * Contains \Drupal\yoast_seo\Controller\YoastSeoController.
 */

namespace Drupal\yoast_seo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\yoast_seo_premimum;

/**
 * YoastSeoController.
 */
class YoastSeoController extends ControllerBase {

  /**
   * A simple demo content page.
   *
   * @return array
   *   Content.
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello, World!'),
    ];
  }

  /**
   * Returns a set of tokens' values.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *   * data The context to use to retrieve the tokens value,
   *     see Drupal\Core\Utility\token::replace()
   *   * tokens An array of tokens to get the values for.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   In case of AccessDeniedException.
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   In case of NotFoundHttpException.
   */
  public function tokens(Request $request) {
    $token_values = array();
    $tokens       = $request->request->get('tokens');
    $data         = $request->request->get('data');

    if (is_null($data)) {
      $data = array();
    }

    // Retrieve the tokens values.
    // Use the metatag token service, which use either core or token module
    // regarding if this one is installed.
    foreach ($tokens as $token) {
      $token_values[$token] = \Drupal::service('metatag.token')
                                     ->tokenReplace($token, $data);
    }

    return new JsonResponse($token_values);
  }

  /**
   * Settings page.
   *
   * @return array
   *   The configuration form.
   */
  public function settings() {
    $form              = [];
    $yoast_seo_manager = \Drupal::service('yoast_seo.manager');

    // Add an advertisement for the Yoast SEO premium module.
    if (!$yoast_seo_manager->isPremiumInstalled()) {
      $premium_message           = $yoast_seo_manager->getPremiumMessage();
      $form['yoast_seo_premium'] = array(
        '#type' => 'text',
        '#markup' => $premium_message,
        '#attached' => array(
          'library' => array(
            'yoast_seo/yoast_seo_admin',
          ),
        ),
      );
    }
    else {
      $yoast_seo_premimum_manager = \Drupal::service('yoast_seo_premium.manager');

      // If the premium plugin is not activated yet, display a warning message.
      if (!$yoast_seo_premimum_manager->isPremiumActivated()) {
        $activate_premium_message  = $yoast_seo_premimum_manager->getActivatePremiumMessage();
        $form['yoast_seo_premium'] = array(
          '#type' => 'text',
          '#markup' => $activate_premium_message,
          '#attached' => array(
            'library' => array(
              'yoast_seo/yoast_seo_admin',
            ),
          ),
        );

        // Add to the page the Yoast SEO form which allows the administrator
        // to enter a valid license key.
        $activate_license_form     = \Drupal::formBuilder()
                                            ->getForm('Drupal\yoast_seo_premium\Form\YoastSeoPremiumActivateLicenseForm');
        $form['yoast_seo_license'] = [
          '#type' => 'details',
          '#title' => 'Yoast SEO license',
          '#description' => 'Activate the Yoast SEO premium plugin by copy/pasting here your valid license key',
          '#markup' => render($activate_license_form),
          '#open' => TRUE,
        ];

      }
      else {
        // Add to the page the Yoast SEO form which allow the administrator
        // to deactivate the premium module
        $deactivate_license_form     = \Drupal::formBuilder()
                                            ->getForm('Drupal\yoast_seo_premium\Form\YoastSeoPremiumDeactivateLicenseForm');
        $form['yoast_seo_license'] = [
          '#type' => 'details',
          '#title' => 'Yoast SEO license',
          '#description' => 'Details of your license',
          '#markup' => render($deactivate_license_form),
          '#open' => TRUE,
        ];
      }
    }

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
            ),
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
        ['@project-page-url' => 'https://www.drupal.org/project/xmlsitemap']
      );
    }

    $form['xmlsitemap'] = [
      '#type' => 'details',
      '#title' => t('XML Sitemap'),
      '#markup' => $xmlsitemap_description,
      '#open' => TRUE,
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
          ),
        ]
      );
    }
    else {
      $metatag_description
        = t('You currently do not have the permission to administer Metatag.');
    }

    $form['metatag'] = [
      '#type' => 'details',
      '#title' => t('Configure Metatag default templates'),
      '#markup' => $metatag_description,
      '#open' => TRUE,
    ];

    // Add to the page the Yoast SEO form which allows the administrator
    // to enable/disable Yoast SEO by bundles.
    $config_form       = \Drupal::formBuilder()
                                ->getForm('Drupal\yoast_seo\Form\YoastSeoConfigForm');
    $form['yoast_seo'] = [
      '#type' => 'details',
      '#title' => 'Configure Yoast SEO by bundles',
      '#description' => 'Select the bundles Yoast SEO will be enabled for',
      '#markup' => render($config_form),
      '#open' => TRUE,
    ];

    return $form;
  }

}
