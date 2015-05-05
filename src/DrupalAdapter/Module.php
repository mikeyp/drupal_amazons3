<?php

namespace Drupal\amazons3\DrupalAdapter;

/**
 * @class Module
 * @package Drupal\amazons3\DrupalAdapter
 * @codeCoverageIgnore
 */
trait Module {

  /**
   * @see module_invoke_all()
   * @param string $hook
   * @return array
   */
  public function module_invoke_all($hook) {
    $args = func_get_args();
    // Remove $hook from the arguments.
    unset($args[0]);
    return \Drupal::moduleHandler()->invokeAll($hook, $args);
  }

  /**
   * @see drupal_alter()
   * @param $type
   * @param $data
   * @param null $context1
   * @param null $context2
   */
  public function drupal_alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    \Drupal::moduleHandler()->alter($type, $data, $context1, $context2);
  }
}
