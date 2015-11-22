<?php

/**
 * @file
 * Contains log.page.inc..
 *
 * Page callback for Log entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Log templates.
 *
 * Default template: log.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_log(array &$variables) {
  // Fetch Log Entity Object.
  $log = $variables['elements']['#log'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
