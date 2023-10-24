<?php

namespace Drupal\next_path_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\pathauto\AliasStorageHelperInterface;
use Drupal\pathauto\AliasUniquifier as PathautoAliasUniquifier;
use Drupal\pathauto\AliasUniquifierInterface;

/**
 * Service description.
 */
class AliasUniquifier extends PathautoAliasUniquifier implements AliasUniquifierInterface {

  /**
   * The site context handler.
   *
   * @var \Drupal\next_path_alias\SiteContextHandler
   */
  protected $contextHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasStorageHelperInterface $alias_storage_helper, ModuleHandlerInterface $module_handler, RouteProviderInterface $route_provider, AliasManagerInterface $alias_manager, SiteContextHandler $context_handler) {
    parent::__construct($config_factory, $alias_storage_helper, $module_handler, $route_provider, $alias_manager);
    $this->contextHandler = $context_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function isReserved($alias, $source, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {

    if (!$this->contextHandler->getSites(FALSE)) {
      $this->contextHandler->setSitesByPath($source);
    }

    $site_ids = $this->contextHandler->getSites();

    // A specific site has been requested. Restrict to that site.
    if ($site_ids !== NULL) {

      // Check if this alias already exists.
      $existing_source = $this->aliasManager->getPathByAliasOnSites($alias, $langcode, $site_ids);

      // No alias was found on this site.
      if ($existing_source == $alias) {
        return FALSE;
      }

      // If it is an alias for the provided source, it is allowed to keep using
      // it. If not, then it is reserved.
      return $existing_source && $existing_source != $source;
    }

    // No specific site has been requested at all.
    return parent::isReserved($alias, $source, $langcode);
  }

  /**
   * Getter for the context handler.
   *
   * Necessary so the UniqueAliasChecker can pass the context to uniquifier
   * so we don't need to rewrite its entire checkAlias() method.
   *
   * @return \Drupal\next_path_alias\SiteContextHandler
   *   The site context handler.
   */
  public function getContextHandler() {
    return $this->contextHandler;
  }

}
