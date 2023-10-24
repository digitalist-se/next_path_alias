<?php

namespace Drupal\next_path_alias\EventSubscriber;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\decoupled_router\PathTranslatorEvent;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Event\EntityEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Next.js Site Path Alias event subscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * RouterPathTranslatorSubscriber constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    LanguageManagerInterface $language_manager
  ) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Needed before EntityActionEventRevalidateSubscriber::onAction().
      EntityEvents::ENTITY_ACTION => ['onAction', 100],
      // Needed before RouterPathTranslatorSubscriber::onPathTranslation().
      PathTranslatorEvent::TRANSLATE => ['onPathTranslation', 10],
    ];
  }

  /**
   * Replaces URL for entity revalidation.
   *
   * @param \Drupal\next\Event\EntityActionEvent $event
   *   The event.
   */
  public function onAction(EntityActionEvent $event) {

    // Not our business.
    if (!$event->getEntityUrl()) {
      return;
    }

    $event->setEntityUrl($event->getEntity()->toUrl('canonical', ['alias' => FALSE])->toString());
  }

  /**
   * Processes a path translation request.
   */
  public function onPathTranslation(PathTranslatorEvent $event) {

    // Add the language prefix to URL if path to translate does not contain it.
    // @see RouterPathTranslatorSubscriber::getPathFromAlias()
    if ($this->languageManager->isMultilingual()) {
      $path = $event->getPath();
      $language_negotiation_url = $this->languageManager->getNegotiator()
        ->getNegotiationMethodInstance('language-url');
      $router_request = Request::create($path);
      if (!$language_negotiation_url->getLangcode($router_request)) {
        $path = '/' . $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId() . $path;
        $event->setPath($path);
      }
    }
  }

}
