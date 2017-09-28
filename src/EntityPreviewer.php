<?php

namespace Drupal\yoast_seo;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a preview renderer for entities.
 *
 * @package Drupal\yoast_seo
 */
class EntityPreviewer {

  protected $entityTypeManager;
  protected $renderer;
  protected $metatagManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, MetatagManagerInterface $metatag_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->metatagManager = $metatag_manager;
  }

  public function entityFromFormSubmission($action, $method, $form_data) {
    /** @var \Symfony\Cmf\Component\Routing\ChainRouter $router */
    $router = \Drupal::service('router.no_access_checks');

    $form_request = Request::create($action, $method, $form_data);

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

    return $entity;
  }

  public function createEntityPreview(EntityInterface $entity) {
    $entity->in_preview = TRUE;

    $html = $this->renderEntity($entity);

    $tags = $this->metatagManager->tagsFromEntityWithDefaults($entity);
    $data = $this->metatagManager->generateRawElements($tags, $entity);

    // Turn our tag renderable into a key => value array.
    foreach ($data as $name => $tag) {
      $data[$name] = $tag['#attributes']['content'];
    }
    // Translate some fields that have different names between metatag module
    // and the Yoast library.
    foreach ($this->getFieldMappings() as $source => $target) {
      if (isset($data[$source])) {
        $data[$target] = $data[$source];
        unset($data[$source]);
      }
    }

    // Add some other fields.
    $data['title'] = $entity->label();
    $data['url'] = '';

    // An entity must be saved before it has a URL.
    if (!$entity->isNew()) {
      $data['url'] = $entity->toUrl()->toString();
    }

    // Add our HTML as analyzable text (Yoast will sanitize).
    $data['text'] = $html->__toString();

    return $data;
  }

  public function renderEntity(EntityInterface $entity) {
    $type = $entity->getEntityTypeId();
    $view_builder = $this->entityTypeManager->getViewBuilder($type);
    // TODO: Make the view mode configurable in Yoast SEO settings.
    $render_array = $view_builder->view($entity, 'full');
    return $this->renderer->renderRoot($render_array);
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
      // TODO: Handle errors!
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

  /**
   * Returns an array of mappings where metatag field names differ from Yoast
   * expected names.
   *
   * @return array
   */
  protected function getFieldMappings() {
    return [
      'title' => 'metaTitle',
      'description' => 'meta',
    ];
  }
}