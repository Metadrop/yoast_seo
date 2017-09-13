<?php

namespace Drupal\yoast_seo\Entity;

use Drupal\Core\Entity\EntityInterface;

class EntityPagePreview implements EntityPreviewInterface {

  protected $entity;
  protected $language;

  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
    $this->language = $entity->language();
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    return $this->language;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }
}