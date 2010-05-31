<?php

/**
 * Represents a menu item persisted to the database.
 *
 * Doctrine has problems when manipulating nested sets. While everything
 * gets saved to the database properly, the PHP objects representing the
 * data are not updated correctly: http://trac.doctrine-project.org/ticket/1907
 *
 * In this class, we'll need to get around this, preferably without making
 * a lot of refresh() calls through the code. In some places in Doctrine_Node_NestedSet,
 * you can see commented out code that we use in places here.
 * 
 * @package    ioDoctrineMenuItemPliugin
 * @subpackage Doctrine_Record
 * @author     Ryan Weaver <ryan.weaver@iostudio.com>
 */
abstract class PluginioDoctrineMenuItem extends BaseioDoctrineMenuItem
{
  /**
   * Merges in the values from the given menu item and saves the object.
   *
   * If ->fromArray() includes the children key, the changes will persist
   * down the tree recursively.
   *
   * @param array $data The array of data from ioMenuItem::fromArray()
   * @return void
   */
  public function persistFromMenuArray(array $data)
  {
    // save the children and unset it from the data
    $children = isset($data['children']) ? $data['children'] : false;
    unset($data['children']);

    // save the credentials and turn them into sfGuardPermission objects
    $permissions = $this->_fetchPermissionsArray($data['credentials']);
    unset($data['credentials']);

    // import in the raw data
    $data = $this->_convertMenuData($data);
    $this->fromArray($data);

    // sync the Permissions
    $this->_syncPermissions($permissions);

    // finally save the changes (
    $this->save();

    // handle the children, if children were specified
    if ($children !== false)
    {
      $currentChildren = $this->getChildrenIndexedByName();

      foreach ($children as $name => $childArr)
      {
        if (isset($currentChildren[$name]))
        {
          $doctrineChild = $currentChildren[$name];
          unset($currentChildren['name']);

          // move/insert each item last, we should have correct order when finished
          $doctrineChild->getNode()->moveAsLastChildOf($this);
        }
        else
        {
          $doctrineChild = new ioDoctrineMenuItem();
          $doctrineChild->name = $name;

          // move/insert each item last, we should have correct order when finished
          $doctrineChild->getNode()->insertAsLastChildOf($this);

          // see class note about updating node values
          $this->getNode()->setRightValue($this->getNode()->getRightValue() + 2);
        }

        // call the persist recursively onto this item and its children
        $doctrineChild->persistFromMenuArray($childArr);
      }
    }
  }

  /**
   * Returns an array of the children ioDoctrineMenuItem objects indexed
   * by the name of each menu item
   *
   * @return array
   */
  public function getChildrenIndexedByName()
  {
    $children = $this->getNode()->getChildren();

    // ->getChildren() returns false (it probably shouldn't) if there are no children 
    if (!$children)
    {
      return array();
    }

    $arr = array();
    foreach ($children as $child)
    {
      $arr[$child->getName()] = $child;
    }

    return $arr;
  }

  /**
   * Converts the array from ioMenuItem::fromArray() to a format suitable
   * for importing into Doctrine_Record::fromArray().
   *
   * @param  array $data The data array from an ioMenuItem object 
   * @return array
   */
  protected function _convertMenuData(array $data)
  {
    // turn the attributes array into a class="name" styled string
    sfApplicationConfiguration::getActive()->loadHelpers('Tag');
    $data['attributes'] = trim(_tag_options($data['attributes']));

    return $data;
  }

  /**
   * Returns an array of sfGuardPermissions objects corresponding to the
   * given array of permission names.
   *
   * This will create a new sfGuardPermission object if it does not exist.
   *
   * @param  array $permissions The array of string permissions names to retrieve
   * @return array
   * @throws sfException
   */
  protected function _fetchPermissionsArray($permissions)
  {
    foreach ($permissions as $permission)
    {
      if (is_array($permission))
      {
        // The credentials are multi-level, implying the use of "or" logic.
        // This is not supported in the flat Permissions relation 
        throw new sfException('Persisting credentials with "or" logic is not supported.');
      }
    }

    // fetch the current permissions
    $guardPermissions = Doctrine_Query::create()
      ->from('sfGuardPermission p INDEXBY p.name')
      ->execute();

    // loop through the permissions and create any permissions that don't exist
    $permissionsArray = array();
    foreach ($permissions as $permission)
    {
      if (isset($guardPermissions[$permission]))
      {
        $permissionsArray[$permission] = $guardPermissions[$permission];
      }
      else
      {
        // create a new permission object
        $guardPermission = new sfGuardPermission();
        $guardPermission->name = $permission;
        $guardPermission->save();
        $permissionsArray[$permission] = $guardPermission;
      }
    }

    return $permissionsArray;
  }

  /**
   * Sets the given array of Permissions on this object and removes any
   * other permissions not in the array.
   *
   * @todo Between this and _fetchPermissionsArray, there's duplicate work done
   * @param  array $permissions An array of sfGuardPermission objects
   * @return void
   */
  protected function _syncPermissions(array $permissions)
  {
    $unlinks = array();
    foreach ($this['Permissions'] as $permission)
    {
      if (isset($permissions[$permission->getName()]))
      {
        // the permission should remain, remove it, no need to re-link
        unset($permissions[$permission]);
      }
      else
      {
        // the permission should not remain, add it to the unlinks
        $unlinks[] = $permission['id'];
      }
    }

    $links = array();
    foreach ($permissions as $permission)
    {
      $links[] = $permission['id'];
    }

    $this->unlink('Permissions', $unlinks);
    $this->link('Permissions', $links);
  }
}