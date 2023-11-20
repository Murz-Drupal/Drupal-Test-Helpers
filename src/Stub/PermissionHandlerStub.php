<?php

namespace Drupal\test_helpers\Stub;

use Drupal\user\PermissionHandler;

/**
 * A stub for the request_stack service.
 */
class PermissionHandlerStub extends PermissionHandler {

  /**
   * A static storage for permissions.
   *
   * @var array
   */
  protected array $stubAllPermissions = [];

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->stubAllPermissions;
  }

  /**
   * Sets the exact list of permissions.
   *
   * @param array $permissions
   *   An associative array with the permission name as the key, and permission
   *   data: title, dependencies, description, provider.
   */
  public function stubSetPermissions(array $permissions) {
    $this->stubAllPermissions = $permissions;
  }

  /**
   * Adds new permissions to the list.
   *
   * @param array $permissions
   *   An associative array with the permission name as the key, and permission
   *   data: title, dependencies, description, provider.
   */
  public function stubAddPermissions(array $permissions) {
    $this->stubAllPermissions = array_merge($permissions, $this->stubAllPermissions);
  }

  /**
   * Deletes permissions from the list by a permission name.
   *
   * @param array $permissions
   *   A list of permission names to delete.
   */
  public function stubDeletePermissions(array $permissions) {
    foreach ($permissions as $permission) {
      unset($this->stubAllPermissions[$permission]);
    }
  }

}
