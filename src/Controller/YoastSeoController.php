<?php

namespace Drupal\yoast_seo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * YoastSeoController.
 */
class YoastSeoController extends ControllerBase {

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
    /** @var MetatagToken $metatag_token */
    $metatag_token = \Drupal::service('metatag.token');
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
      $token_values[$token] = $metatag_token->replace($token, $data);
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
    $form = [];

    // Check if XML Sitemap is installed and enabled.
    if (\Drupal::moduleHandler()->moduleExists('xmlsitemap')) {
      // Inform the user about altering the XML Sitemap configuration on the
      // module configuration page if he has access to do so.
      if (\Drupal::currentUser()->hasPermission('administer xmlsitemap')) {
        $xmlsitemap_description = $this->t(
          'You can configure the XML Sitemap settings at the @url.',
          [
            '@url' => \Drupal::l(
              $this->t('XML Sitemap configuration page'),
              Url::fromRoute('xmlsitemap.admin_search')
            ),
          ]
        );
      }
      else {
        $xmlsitemap_description
          = $this->t('You do not have the permission to administer XML Sitemap.');
      }
    }
    else {
      // XML Sitemap is not enabled, inform the user he should think about
      // installing and enabling it.
      $xmlsitemap_description = $this->t(
        'You currently do not have XML Sitemap enabled. We strongly recommend you to install XML Sitemap. You can download the module from <a href="@project-page-url">@project-page-url</a>.',
        ['@project-page-url' => 'https://www.drupal.org/project/xmlsitemap']
      );
    }

    $form['xmlsitemap'] = [
      '#type' => 'details',
      '#title' => $this->t('XML Sitemap'),
      '#markup' => $xmlsitemap_description,
      '#open' => TRUE,
    ];

    // Inform the user about altering the Metatag configuration on the module
    // configuration page if he has access to do so.
    // We do not check if the module is enabled since it is our dependency.
    if (\Drupal::currentUser()->hasPermission('administer meta tags')) {
      $metatag_description = $this->t(
        'You can configure and override the Metatag title & description default settings at the @url.',
        [
          '@url' => \Drupal::l(
            $this->t('Metatag configuration page'),
            Url::fromRoute('entity.metatag_defaults.collection')
          ),
        ]
      );
    }
    else {
      $metatag_description
        = $this->t('You currently do not have the permission to administer Metatag.');
    }

    $form['metatag'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure Metatag default templates'),
      '#markup' => $metatag_description,
      '#open' => TRUE,
    ];

    // Add to the page the Yoast SEO form which allows the administrator
    // to enable/disable Yoast SEO by bundles.
    $config_form       = \Drupal::formBuilder()
                                ->getForm('Drupal\yoast_seo\Form\YoastSeoConfigForm');
    $form['yoast_seo'] = [
      '#type' => 'details',
      '#title' => 'Configure Real-time SEO by bundles',
      '#description' => 'Select the bundles Real-time SEO will be enabled for',
      '#markup' => render($config_form),
      '#open' => TRUE,
    ];

    return $form;
  }

}
