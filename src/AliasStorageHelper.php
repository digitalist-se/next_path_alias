<?php

namespace Drupal\next_path_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\pathauto\AliasStorageHelper as PathauthoAliasStorageHelper;
use Drupal\pathauto\AliasStorageHelperInterface;
use Drupal\pathauto\MessengerInterface;
use Drupal\pathauto\PathautoGeneratorInterface;

/**
 * Service description.
 */
class AliasStorageHelper extends PathauthoAliasStorageHelper implements AliasStorageHelperInterface {

  /**
   * The site context handler service.
   *
   * @var \Drupal\next_path_alias\SiteContextHandler
   */
  protected $contextHandler;

  /**
   * AliasStorageHelper constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasRepositoryInterface $alias_repository, Connection $database, MessengerInterface $messenger, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager = NULL, SiteContextHandler $context_handler) {
    parent::__construct($config_factory, $alias_repository, $database, $messenger, $string_translation, $entity_type_manager);
    $this->contextHandler = $context_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $path, $existing_alias = NULL, $op = NULL) {
    $config = $this->configFactory->get('pathauto.settings');

    // Set up all the variables needed to simplify the code below.
    $source = $path['source'];
    $alias = $path['alias'];
    $langcode = $path['language'];

    // Extract the originating entity from the source path.
    // The alternative would be to pass the entity in separately, but that
    // brings a lot of code duplication:
    // - PathautoGenerator::createEntityAlias()
    // The only issue with this approach is that the site is not properly found
    // in behat tests with the DrupalContext::createNodes() method.
    $this->contextHandler->setSitesByPath($source);
    $next_sites = $this->contextHandler->getSites();

    if ($existing_alias) {
      /** @var \Drupal\path_alias\PathAliasInterface $existing_alias */
      $existing_alias = $this->entityTypeManager->getStorage('path_alias')->load($existing_alias['pid']);
    }

    // Alert users if they are trying to create an alias that is the same as the
    // internal system path.
    if ($source == $alias) {
      $this->messenger->addMessage($this->t('Ignoring alias %alias because it is the same as the internal path.', ['%alias' => $alias]));
      return NULL;
    }

    // Update the existing alias if there is one and the configuration is set to
    // replace it.
    if ($existing_alias && $config->get('update_action') == PathautoGeneratorInterface::UPDATE_ACTION_DELETE) {
      // Skip replacing the current alias with an identical alias.
      if ($existing_alias->getAlias() == $alias && !$next_sites) {
        return NULL;
      }

      $old_alias = $existing_alias->getAlias();
      $existing_alias->setAlias($alias)->set('next_sites', $next_sites)->save();

      $this->messenger->addMessage($this->t('Created new alias %alias for %source, replacing %old_alias.', [
        '%alias' => $alias,
        '%source' => $source,
        '%old_alias' => $old_alias,
      ]));

      $return = $existing_alias;
    }
    else {
      // Otherwise, create a new alias.
      $path_alias = $this->entityTypeManager->getStorage('path_alias')->create([
        'path' => $source,
        'alias' => $alias,
        'langcode' => $langcode,
        'next_sites' => $next_sites,
      ]);
      $path_alias->save();

      $this->messenger->addMessage($this->t('Created new alias %alias for %source.', [
        '%alias' => $path_alias->getAlias(),
        '%source' => $path_alias->getPath(),
      ]));

      $return = $path_alias;
    }

    return [
      'source' => $return->getPath(),
      'alias' => $return->getAlias(),
      'pid' => $return->id(),
      'langcode' => $return->language()->getId(),
      'next_sites' => $return->get('next_sites')->getValue(),
    ];
  }

}
