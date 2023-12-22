<?php

namespace Drupal\next_path_alias;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for the Next.js Site Path Alias module.
 */
class NextPathAliasServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['pathauto'])) {
      $container->getDefinition('pathauto.alias_uniquifier')
        ->setClass('Drupal\next_path_alias\AliasUniquifier')
        ->addArgument($container->getDefinition('next_path_alias.site_context_handler'));
    }
    if (isset($modules['unique_alias_checker'])) {
      $container->getDefinition('unique_alias_checker')
        ->setClass('Drupal\next_path_alias\UniqueAliasChecker');
    }
  }

}
