<?php

namespace Drupal\next_path_alias;

use Drupal\Component\Utility\UrlHelper;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Decorates core path alias service to provide site context.
 *
 * Adds a new getPathByAliasOnSites method and slightly modifies getPathByAlias
 * to take "next-site" query arg into account.
 */
class NextSitePathAliasManager implements AliasManagerInterface {

  /***
   * The inner alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManagerInner;

  /**
   * Constructs a new PathAliasManager object.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManagerInner
   *   The inner alias manager.
   */
  public function __construct(AliasManagerInterface $aliasManagerInner) {
    $this->aliasManagerInner = $aliasManagerInner;
  }

  /**
   * Not in the interface but provided by AliasManager.
   */
  public function setCacheKey($key) {
    $this->aliasManagerInner->setCacheKey($key);
  }

  /**
   * Not in the interface but provided by AliasManager.
   */
  public function writeCache() {
    $this->aliasManagerInner->writeCache();
  }

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL) {

    $url_parts = UrlHelper::parse($alias);
    if (!empty($url_parts['query']['next-site'])) {
      // Query on specified site and return the path.
      // getPathByAliasOnSites already returns the alias if no path was found.
      return $this->getPathByAliasOnSites($url_parts['path'], $langcode, [$url_parts['query']['next-site']], FALSE);
    }

    return $this->aliasManagerInner->getPathByAlias($alias, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL) {
    return $this->aliasManagerInner->getAliasByPath($path, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL) {
    return $this->aliasManagerInner->cacheClear($source);
  }

  /**
   * Given the alias and site ids, return the path it represents.
   *
   * Will return the alias if no path was found, which is the same behavior as
   * the core getPathByAlias method.
   *
   * @param string $alias
   *   An alias.
   * @param string $langcode
   *   An optional language code to look up the path in.
   * @param array $site_ids
   *   An array of site ids to look up the path in.
   * @param bool $strict
   *   Whether to return the path of an alias without site if none with
   *   the requested site was found.
   *
   * @return string
   *   The path represented by alias, or the alias if no path was found.
   */
  public function getPathByAliasOnSites($alias, $langcode, $site_ids, $strict = TRUE) {

    $entity_ids = \Drupal::entityQuery('path_alias')
      ->accessCheck(FALSE)
      ->condition('alias', $alias)
      ->condition('langcode', $langcode)
      ->execute();

    $without_site_match = NULL;
    foreach (PathAlias::loadMultiple($entity_ids) as $entity) {
      $entity_site_ids = array_column($entity->get('next_sites')->getValue(), 'value');
      if (empty($entity_site_ids)) {
        $without_site_match = $entity->getPath();
      }
      if (!empty(array_intersect($entity_site_ids, $site_ids))) {
        return $entity->getPath();
      }
    }

    // Return the path without site if none with the requested site was found
    // and passed argument says so.
    if ($without_site_match && !$strict) {
      return $without_site_match;
    }

    return $alias;
  }

}
