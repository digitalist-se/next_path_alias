<?php

namespace Drupal\next_path_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessor implements OutboundPathProcessorInterface {

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a AliasPathProcessor object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleableMetadata = NULL) {

    if ($request && $request->getRequestFormat() == 'html' && !isset($options['alias'])) {
      // Just trick core AliasPathProcessor to not search for an alias.
      if (isset($options['entity']) && $options['entity'] instanceof FieldableEntityInterface && $options['entity']->hasField($this->getSiteFieldName())) {
        $options['alias'] = TRUE;
      }
      elseif (isset($options['route']) && $options['route']->getPath() == '/node/{node}') {
        $options['alias'] = TRUE;
      }
    }
    return $path;
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
