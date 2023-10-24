<?php

namespace Drupal\next_path_alias\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\Plugin\Validation\Constraint\UniquePathAliasConstraintValidator;
use Drupal\next_path_alias\NextPathAliasHelper;
use Drupal\next_path_alias\SiteContextHandler;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the Unique path alias per Next.js site constraint.
 */
class NextPathAliasConstraintValidator extends UniquePathAliasConstraintValidator {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The path alias manager.
   *
   * @var \Drupal\next_path_alias\PathAliasManager
   */
  protected $pathAliasManager;

  /**
   * The site context handler.
   *
   * @var \Drupal\next_path_alias\SiteContextHandler
   */
  protected $siteContextHandler;

  /**
   * The path alias helper.
   *
   * @var \Drupal\next_path_alias\NextPathAliasHelper
   */
  protected NextPathAliasHelper $pathAliasHelper;

  /**
   * Creates a new UniquePathAliasConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\path_alias\AliasManagerInterface $path_alias_manager
   *   The path alias manager.
   * @param \Drupal\next_path_alias\SiteContextHandler $context_handler
   *   The site context handler.
   * @param \Drupal\next_path_alias\NextPathAliasHelper $path_alias_helper
   *   The path alias helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, AliasManagerInterface $path_alias_manager, SiteContextHandler $context_handler, NextPathAliasHelper $path_alias_helper) {
    parent::__construct($entity_type_manager);
    $this->requestStack = $request_stack;
    $this->pathAliasManager = $path_alias_manager;
    $this->siteContextHandler = $context_handler;
    $this->pathAliasHelper = $path_alias_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('path_alias.manager'),
      $container->get('next_path_alias.site_context_handler'),
      $container->get('next_path_alias.next_path_alias_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    if ($this->requestIsEntityForm()) {
      $sites = \Drupal::request()->get($this->siteContextHandler->getSiteFieldName());
      $this->siteContextHandler->setSites(is_array($sites) ? $sites : [$sites]);
      $langcode = $this->requestStack->getCurrentRequest()->get('langcode')[0]['value'];
    }

    else {
      $this->siteContextHandler->setSitesByPath($entity->getPath());
      $langcode = $entity->language()->getId();
    }

    $sites = $this->siteContextHandler->getSites();

    if ($sites === NULL) {
      return parent::validate($entity, $constraint);
    }

    $path = $this->pathAliasManager->getPathByAliasOnSites($entity->getAlias(), $langcode, $sites);

    // Same entity, no error.
    if ($path == $entity->getPath() || $path == $entity->getAlias()) {
      return;
    }

    if ($other_entity = $this->pathAliasHelper->getEntityFromPath($path)) {
      $other_entity_sites = array_column($other_entity->get($this->siteContextHandler->getSiteFieldName())->getValue(), 'target_id');
      $found_sites = array_intersect($sites, $other_entity_sites);
    }

    else {
      $found_sites = [];
    }

    // Should never happen, but add generic message just in case.
    if (empty($found_sites)) {
      $this->context->buildViolation($constraint->messageNextSiteWithoutSite, [
        '%alias' => $entity->getAlias(),
        '@path' => $path,
      ])->addViolation();
      return;
    }

    $this->context->buildViolation($constraint->messageNextSite, [
      '%alias' => $entity->getAlias(),
      '%sites' => implode(', ', $found_sites),
      '@path' => $other_entity->toUrl()->toString(),
      '@title' => $other_entity->label(),
    ])->addViolation();
  }

  /**
   * Checks if the current request is an entity form.
   *
   * @return bool
   *   TRUE if the current request is an entity form.
   */
  protected function requestIsEntityForm() {
    $route_match = \Drupal::routeMatch();

    $route_name = $route_match->getRouteName();
    if ($route_name == 'node.add') {
      return TRUE;
    }

    if ($route_name == 'entity.node.edit_form' && $route_match->getParameter('node')->isDefaultTranslation()) {
      return TRUE;
    }
    return FALSE;
  }

}
