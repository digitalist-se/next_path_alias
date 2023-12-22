<?php

namespace Drupal\next_path_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;

/**
 * Service description.
 */
class NextPathAliasHelper {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path.validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a NextPathAliasHelper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path.validator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->pathValidator = $path_validator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Helper function that inspects a path and extracts its corresponding entity.
   *
   * @param string|null $path
   *   The path to inspect.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity found or null.
   */
  public function getEntityFromPath(?string $path): ?EntityInterface {
    // No entity can be extracted from an invalid path.
    if (!$path || !$this->pathValidator->getUrlIfValidWithoutAccessCheck($path)) {
      return NULL;
    }

    // Only routed paths can be tied to an entity.
    $url = Url::fromUri("internal:" . $path);
    if (!$url->isRouted()) {
      return NULL;
    }

    // No route parameters -> no entity.
    $params = $url->getRouteParameters();
    if (count($params) == 0) {
      return NULL;
    }

    // Extract the entity type.
    $entity_type = key($params);

    return $this->entityTypeManager->getStorage($entity_type)->load($params[$entity_type]);
  }

}
