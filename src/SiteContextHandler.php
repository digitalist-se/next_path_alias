<?php

namespace Drupal\next_path_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;

/**
 * Service description.
 */
class SiteContextHandler {

  /**
   * The config.factory service.
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
   * Stores the context.
   *
   * @var string[]
   */
  protected $contextSites;

  /**
   * The next path alias helper service.
   *
   * @var \Drupal\next_path_alias\NextPathAliasHelper
   */
  protected NextPathAliasHelper $pathAliasHelper;

  /**
   * Constructs a SiteContextHandler object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\next_path_alias\NextPathAliasHelper $path_alias_helper
   *   The next path alias helper service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, EntityTypeManagerInterface $entity_type_manager, NextPathAliasHelper $path_alias_helper) {
    $this->configFactory = $config_factory;
    $this->pathValidator = $path_validator;
    $this->entityTypeManager = $entity_type_manager;
    $this->pathAliasHelper = $path_alias_helper;
  }

  /**
   * Sets the sites by context.
   *
   * @param string[] $site_ids
   *   The site ids.
   */
  public function setSites(array $site_ids) {
    $this->contextSites = $site_ids;
  }

  /**
   * Assigns the context sites from a path.
   *
   * An entity is extracted from that path, and the sites are extracted from
   * the entity.
   *
   * @param string $path
   *   The path to extract the entity from.
   */
  public function setSitesByPath($path) {

    // Load the entity and return its sites.
    if ($entity = $this->pathAliasHelper->getEntityFromPath($path)) {
      $this->setSitesByContentEntity($entity);
    }
  }

  /**
   * Assigns the context sites extracting them from a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to extract the sites from.
   */
  public function setSitesByContentEntity(ContentEntityInterface $entity) {
    if ($entity->hasField($this->getSiteFieldName())) {
      $this->setSites(array_column($entity->get($this->getSiteFieldName())->getValue(), 'target_id'));
    }
  }

  /**
   * Unsets the sites by context.
   */
  public function unsetSites() {
    $this->contextSites = NULL;
  }

  /**
   * Return the sites by context and optionally reset the context.
   *
   * @param bool $reset
   *   Whether to reset the context once returned.
   *
   * @return string[]|null
   *   The sites by context or null if context is not set.
   */
  public function getSites($reset = TRUE) {
    $sites = $this->contextSites;
    if ($reset) {
      $this->unsetSites();
    }
    return $sites;
  }

  /**
   * Returns the site field name from config.
   *
   * @return string
   *   The field name as stored in configuration.
   */
  public function getSiteFieldName() {
    return $this->configFactory->get('next_path_alias.settings')->get('sites_field_name');
  }

}
