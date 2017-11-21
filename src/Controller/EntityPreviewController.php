<?php

namespace Drupal\yoast_seo\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\yoast_seo\EntityPreviewer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides methods for interacting with entity previews.
 */
class EntityPreviewController extends ControllerBase {

  /**
   * Used to create the entity needed for analysis.
   *
   * @var \Drupal\yoast_seo\EntityPreviewer
   */
  protected $entityPreviewer;

  /**
   * Method used to set-up this class with depedency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yoast_seo.entity_previewer')
    );
  }

  /**
   * EntityPreviewController constructor.
   *
   * @param \Drupal\yoast_seo\EntityPreviewer $entity_previewer
   *   An instance of the entity preview service.
   */
  public function __construct(EntityPreviewer $entity_previewer) {
    $this->entityPreviewer = $entity_previewer;
  }

  /**
   * Checks access for the EntityPreview based on the given entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The session that is trying to access this route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether access is granted or denied.
   */
  public function access(AccountInterface $account) {
    // TODO: Request should be injected but that only works when #2786941 is
    // fixed. We don't want a separate service because we want to cache the
    // created entity.
    $request = \Drupal::request();

    // If this user can't use the analysis feature then there's no reason to
    // access this route.
    if (!$account->hasPermission('use yoast seo')) {
      return AccessResult::forbidden();
    }

    // Retrieve the entity we'll be analysing.
    $entity = $this->getEntityForRequest($request);

    // We check if the user is allowed to view the entity.
    // This is safe because we don't modify any data.
    if (!$entity->access('view', $account)) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

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
   */
  public function json(Request $request) {
    // TODO: Client side check if form is valid before sending to server.
    $entity = $this->getEntityForRequest($request);

    $preview_data = $this->entityPreviewer->createEntityPreview($entity);

    // The current value of the alias field, if any,
    // takes precedence over the entity url.
    if (!empty($form_data['path'][0]['alias'])) {
      $preview_data['url'] = $form_data['path'][0]['alias'];
    }

    return new JsonResponse($preview_data);
  }

  /**
   * Returns an instantiated preview entity for the request.
   *
   * TODO: Implement per-request caching and terminate early so this isn't run
   * twice for access checks and the controller.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request that contains the POST body for this preview.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the request contains now post data at all.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the 'yoast_seo_preview' object is omitted from the post data.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the 'action', 'method', and 'path' entries are not in the
   *   'yoast_seo_preview' object.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the 'form_id' key is not set in the post data.
   *
   * @return \Drupal\Core\Entity\Entity
   *   The instantiated entity.
   */
  protected function getEntityForRequest(Request $request) {
    // Fetch all our post data.
    $content = $request->request->all();
    if (empty($content)) {
      throw new BadRequestHttpException("Missing post data");
    }

    // The context for our form is stored under the yoast_seo_preview key.
    if (empty($content['yoast_seo_preview'])) {
      throw new BadRequestHttpException("Missing preview context");
    }

    $preview_context = $content['yoast_seo_preview'];
    unset($content['yoast_seo_preview']);

    // Check if any form content was sent along with our context.
    if (empty($content)) {
      throw new BadRequestHttpException("Missing preview entity data");
    }

    // Check if we have all the context we require to recreate the form request.
    if (empty($preview_context['path']) ||
        empty($preview_context['action']) ||
        empty($preview_context['method'])) {
      throw new BadRequestHttpException("Missing preview context");
    }

    $form_data = $content;

    // Check if we know which form we are using for the analysis.
    if (empty($form_data['form_id'])) {
      throw new BadRequestHttpException("Missing form_id in preview entity data");
    }

    return $this->entityPreviewer->entityFromFormSubmission($preview_context['action'], $preview_context['method'], $form_data);
  }

}
