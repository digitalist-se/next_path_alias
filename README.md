# Next.js Path Alias

## Contents of this file

- Introduction
- Requirements
- Installation
- Usage
- Limitations

## Introduction

This module allows the re-use of the same path alias on multiple Drupal nodes
within a decoupled multi-site configuration, where various frontend sites are
controlled by a unique Drupal backend. Each path alias remains unique per
Next.js frontend.

## Requirements

- Drupal Core version: ≥ 9.0
- <a href="https://www.drupal.org/project/next">Next.js</a>
- <a href="https://www.drupal.org/project/decoupled_router">Decoupled Router</a>

## Installation

1. Execute the following Drush command:

   ```
   drush -y en next_path_alias
   ```

2. Navigate to `/admin/config/services/next/path-alias` and designate the field
   in your content that identifies the associated Next.js site for the frontend.


3. Head over to `/admin/config/services/next/settings` and
   choose `Simple OAuth - Aliased URL` instead of `Simple OAuth`.

## Usage

You may likely be obtaining routing information within your Next.js application
using:

```js
  const path = await drupal.translatePathFromContext(context)
```

https://next-drupal.org/docs/pages#advanced-example

This query should be replaced by:

```js
const pathFromContext = drupal.getPathFromContext(context);
const path = await drupal.translatePath(
    `${pathFromContext}?next-site=${process.env.DRUPAL_SITE_ID}`,
    {
      withAuth: true,
    },
);
```

## Limitations

This module depends on the functionality provided
by <a href="https://www.drupal.org/project/pathauto">Pathauto</a> for data
storage. Only content with
the option 'Generate automatic URL alias' enabled can share the same path alias.

One option to work around this limitation is to use an additional field for
storing the path alias, and then configure URL alias patterns based on it.
