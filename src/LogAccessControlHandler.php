<?php

/**
 * @file
 * Contains \Drupal\log\LogAccessControlHandler.
 */

namespace Drupal\log;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Log entity.
 *
 * @see \Drupal\log\Entity\Log.
 */
class LogAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view log entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit log entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete log entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add log entities');
  }

}
