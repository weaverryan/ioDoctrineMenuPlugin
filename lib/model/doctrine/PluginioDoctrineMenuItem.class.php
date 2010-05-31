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

    // save the credentials and turn them into a permissions array
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

      // if the list of children is the same and in the same order, don't force a rearrange
      $reorder = (array_keys($children) !== array_keys($currentChildren));

      /**
       * Due in part to problems with lft,rgt values not being updated as
       * nodes are added, reversing the children array and then using
       * insert/moveAsFirstChildOf (emphasis on first) works whereas
       * keeping the order and using insert/moveAsLastChildOf renders
       * unexpected results.
       */
      $children = array_reverse($children);
      foreach ($children as $name => $childArr)
      {
        if (isset($currentChildren[$name]))
        {
          $doctrineChild = $currentChildren[$name];
          unset($currentChildren['name']);

          // if the children have been changed at all, reorder
          if ($reorder)
          {
            // move/insert each item last, we should have correct order when finished
            $doctrineChild->getNode()->moveAsFirstChildOf($this);
          }
        }
        else
        {
          $doctrineChild = new ioDoctrineMenuItem();
          $doctrineChild->name = $name;

          // move/insert each item last, we should have correct order when finished
          $doctrineChild->getNode()->insertAsFirstChildOf($this);

          // see class note about updating node values
          //$this->getNode()->setRightValue($this->getNode()->getRightValue() + 2);
          $doctrineChild->refresh();
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
   * Returns an array of name => id pairs representing the sfGuardPermission
   * objects that should be added to this menu item.
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
    $currentPermissions = Doctrine_Query::create()
      ->from('sfGuardPermission p INDEXBY p.name')
      ->select('p.name, p.id')
      ->whereIn('p.name', $permissions)
      ->execute(null, Doctrine_Core::HYDRATE_ARRAY);

    // loop through the permissions and create any permissions that don't exist
    $permissionsArray = array();
    foreach ($permissions as $permission)
    {
      if (isset($currentPermissions[$permission]))
      {
        $permissionsArray[$permission] = $currentPermissions[$permission]['id'];
      }
      else
      {
        // create a new permission object
        $guardPermission = new sfGuardPermission();
        $guardPermission->name = $permission;
        $guardPermission->save();
        $permissionsArray[$permission] = $guardPermission->id;
      }
    }

    return $permissionsArray;
  }

  /**
   * Sets the given array of name => id Permission pairs on this menu item
   *
   * @todo Between this and _fetchPermissionsArray, there's duplicate work done
   * @param  array $permissions An array of name => id sfGuardPermission pairs
   * @return void
   */
  protected function _syncPermissions(array $permissions)
  {
    // small optimization
    if (count($permissions) == 0 && count($this['Permissions']) == 0)
    {
      return;
    }

    $unlinks = array();
    foreach ($this['Permissions'] as $permission)
    {
      if (isset($permissions[$permission->getName()]))
      {
        // the permission should remain, remove it, no need to re-link
        unset($permissions[$permission->getName()]);
      }
      else
      {
        // the permission should not remain, add it to the unlinks
        $unlinks[] = $permission['id'];
      }
    }

    if (count($unlinks))
    {
      $this->unlink('Permissions', $unlinks);
    }

    if (count($permissions))
    {
      $this->link('Permissions', array_values($permissions));
    }
  }
}