<?php

namespace Drupal\yoast_seo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides methods for interacting with entity previews.
 */
class EntityPreviewController extends ControllerBase {

  /**
   * Returns the json representation of an EntityPreview.
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
  public function json(Request $request) {
    // TODO: Client side check if form is valid before sending to server.
    $content = $request->request->all();
    if (empty($content)) {
      throw new BadRequestHttpException("Missing post data");
    }

    $preview_context = $content['yoast_seo_preview'];
    unset($content['yoast_seo_preview']);

    if (empty($content)) {
      throw new BadRequestHttpException("Missing data");
    }

    if (empty($preview_context) || empty($preview_context['path'])
      || empty($preview_context['action']) || empty($preview_context['method'])) {
      throw new BadRequestHttpException("Missing preview context");
    }

    $form_data = $content;

    if (empty($form_data['form_id'])) {
      throw new BadRequestHttpException("Missing form_id");
    }

    $entity_previewer = \Drupal::service('yoast_seo.entity_previewer');

    $entity = $entity_previewer->entityFromFormSubmission($preview_context['action'], $preview_context['method'], $form_data);

    $preview_data = $entity_previewer->createEntityPreview($entity);

    // The current value of the alias field, if any,
    // takes precedence over the entity url.
    if (!empty($form_data['path'][0]['alias'])) {
      $preview_data['url'] = $form_data['path'][0]['alias'];
    }

    return new JsonResponse($preview_data);
  }

}
