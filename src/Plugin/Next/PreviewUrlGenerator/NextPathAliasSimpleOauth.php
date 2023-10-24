<?php

namespace Drupal\next_path_alias\Plugin\Next\PreviewUrlGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\next\Entity\NextSiteInterface;
use Drupal\next\Plugin\Next\PreviewUrlGenerator\SimpleOauth;

/**
 * Provides the preview_url_generator plugin based on simple_oauth.
 *
 * @PreviewUrlGenerator(
 *  id = "next_path_alias_simple_oauth",
 *  label = "Simple OAuth - Aliased URL",
 *  description = "Extends Simple OAuth to generate aliased URLs."
 * )
 */
class NextPathAliasSimpleOauth extends SimpleOauth {

  /**
   * {@inheritdoc}
   *
   * Add "alias" to the query string so URL is generated with alias.
   * Needs to rewrite parent method because $query['secret'] requires the exact
   * URL.
   */
  public function generate(NextSiteInterface $next_site, EntityInterface $entity, string $resource_version = NULL): ?Url {
    $query = [];
    $query['slug'] = $slug = $entity->toUrl('canonical', ['alias' => FALSE])->toString();

    // Send the current user roles as scope.
    $scopes = $this->getScopesForCurrentUser();

    if (!count($scopes)) {
      return NULL;
    }

    $query['scope'] = $scope = implode(' ', $scopes);

    // Create a secret based on the timestamp, slug, scope and resource version.
    $query['timestamp'] = $timestamp = $this->time->getRequestTime();
    $query['secret'] = $this->previewSecretGenerator->generate($timestamp . $slug . $scope . $resource_version);

    return Url::fromUri($next_site->getPreviewUrl(), [
      'query' => $query,
    ]);
  }

}
