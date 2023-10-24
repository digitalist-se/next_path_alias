<?php

namespace Drupal\next_path_alias;

use Drupal\unique_alias_checker\UniqueAliasChecker as OriginalUniqueAliasChecker;

/**
 * Provides a unique alias checker that takes into account the site context.
 *
 * @property \Drupal\next_path_alias\AliasUniquifier $aliasUniquifier
 */
class UniqueAliasChecker extends OriginalUniqueAliasChecker {

  /**
   * {@inheritdoc}
   */
  public function checkAlias($entity) {
    $this->aliasUniquifier->getContextHandler()->setSitesByContentEntity($entity);
    return parent::checkAlias($entity);
  }

}
