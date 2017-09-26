<?php

namespace Drupal\yoast_seo\Controller;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Reflection\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteBuilder;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Form\NodePreviewForm;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zend\Diactoros\Response\TextResponse;

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

    /** @var \Symfony\Cmf\Component\Routing\ChainRouter $router */
    $router = \Drupal::service('router.no_access_checks');

    $form_request = Request::create($preview_context['action'], $preview_context['method'], $form_data);

    // Push our form request on the stack so it's used by the form builder.
    \Drupal::requestStack()->push($form_request);

    try {
      $match = $router->matchRequest($form_request);
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException('Could not find the entity route.');
    }

    $operation = NULL;

    // Find the first loaded entity.
    foreach ($match as $val) {
      // Existing entities will provide is with an entity of ContentEntityBase.
      if ($val instanceof ContentEntityBase || is_subclass_of($val, "ContentEntityBase")) {
        $temp_entity = $val;

        $operation = explode('.', $match['_entity_form'])[1];
        break;
      }
      // Entity creation pages provide us a parent ConfigEntityBase inheritor to
      // help us determine the entity type and bundle.
      elseif ($val instanceof ConfigEntityBase || is_subclass_of($val, "ConfigEntityBase")) {
        $config_entity_type = $val->getEntityType();
        $type = $config_entity_type->getBundleOf();

        $operation = explode('.', $match['_route'])[1];

        $storage = $this->entityTypeManager()->getStorage($type);

        // Set the bundle name where needed.
        $type_key = $storage->getEntityType()->get('entity_keys')['bundle'];

        // Create a temporary entity so we can load the form.
        $temp_entity = $storage->create([$type_key => $val->id()]);

        break;
      }
    }

    if (empty($temp_entity)) {
      throw new BadRequestHttpException('Could not construct entity from form data');
    }

    $entity = $this->getUpdatedEntity($temp_entity, $operation);

    // Pop our form request from the stack as we're done with it.
    \Drupal::requestStack()->pop();

    if (empty($entity)) {
      throw new NotFoundHttpException("Could not find the edited entity");
    }

    $entity->in_preview = TRUE;
    $preview_data = \Drupal::service('yoast_seo.entity_previewer')->createEntityPreview($entity);

    // The current value of the alias field, if any,
    // takes precedence over the entity url.
    if (!empty($form_data['path'][0]['alias'])) {
      $preview_data['url'] = $form_data['path'][0]['alias'];
    }

    return new JsonResponse($preview_data);
  }

  /**
   * Create an up to date entity from submitted form data for any entity type.
   *
   * Uses a temporary entity and a desired operation to retrieve form values
   * from the request currently on top of the requestStack and returns an entity
   * with those updated values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $temp_entity
   *   A temporary entity that is used to gather information
   *   like entity type and bundle.
   * @param null $operation
   *   The operation that the submitted form performs on the entity. Required
   *   to select the correct form display mode and map submitted fields to those
   *   available in the form.
   *
   * @return \Drupal\Core\Entity\Entity
   *   An entity that contains the values from the submitted form.
   */
  protected function getUpdatedEntity($temp_entity, $operation = NULL) {
    $form_handlers = $temp_entity->getEntityType()->get('handlers')['form'];

    if (empty($operation) || !isset($form_handlers[$operation])) {
      $operation = 'default';
    }

    $form_state = new FormState();

    $form_object = \Drupal::entityTypeManager()->getFormObject($temp_entity->getEntityTypeId(), $operation);
    $form_object->setEntity($temp_entity);

    /** @var \Drupal\Core\Form\FormBuilder $form_builder */
    $form_builder = \Drupal::service('form_builder');

    $form = $form_builder->buildForm($form_object, $form_state);

    if (!empty($form_state->getErrors())) {
      // Handle errors!
      sleep(1);
    }

    // Build our entity from the form state.
    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $form_object->buildEntity($form, $form_state);

    // Support ownable entities that might not yet have an owner.
    if ($entity instanceof EntityOwnerInterface && empty($entity->getOwner())) {
      $owner = User::load($this->currentUser()->id());
      $entity->setOwner($owner);
    }

    return $entity;
  }
}
