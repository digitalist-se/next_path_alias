<?php

namespace Drupal\next_path_alias\Plugin\Validation\Constraint;

use Drupal\Core\Path\Plugin\Validation\Constraint\UniquePathAliasConstraint;

/**
 * Provides an unique path alias per Next.js site constraint.
 */
class NextPathAliasConstraint extends UniquePathAliasConstraint {

  /**
   * The Next.js site violation message when no specific site found.
   *
   * (should not actually happen)
   *
   * @var string
   */
  public $messageNextSiteWithoutSite = 'The alias %alias is already in use in this Next.js site: <a href="@path">@path</a>.';

  /**
   * The Next.js site violation message.
   *
   * @var string
   */
  public $messageNextSite = 'The alias %alias is already in use in this Next.js site (%sites): <a href="@path">@title</a>.';

}
