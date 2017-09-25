<?php

namespace Drupal\yoast_seo;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\metatag\MetatagManagerInterface;

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

  public function createEntityPreview(EntityInterface $entity) {
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
//        unset($data[$source]);
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