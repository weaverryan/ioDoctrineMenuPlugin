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
 * @package    ioDoctrineMenuItemPlugin
 * @subpackage Doctrine_Record
 * @author     Ryan Weaver <ryan.weaver@iostudio.com>
 */
abstract class PluginioDoctrineMenuItem extends BaseioDoctrineMenuItem
{

  /**
   * Creates an ioMenuItem tree where this object is the root
   *
   * @return ioMenuItem
   */
  public function createMenu()
  {
    $hier = $this->_getMenuHierarchy();
    $data = self::_convertHierarchyToMenuArray($hier);

    return ioMenuItem::createFromArray($data);
  }

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

    /**
     * The === is important. There is a qualitative difference between not
     * being passed the children key at all ($children === false) and being
     * passed an empty children key ($children === array()). In the first
     * case, we don't care about children, we want to ignore them. In the
     * second case, we're making the statement that the children should
     * be empty.
     */
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
        // find or create the ioDoctrineMenuItem, position it, update it, save it
        if (isset($currentChildren[$name]))
        {
          // get the ioDoctrineMenuItem and unset it from the array
          $doctrineChild = $currentChildren[$name];
          unset($currentChildren[$name]);

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

          // see class note about updating node values
          $doctrineChild->getNode()->insertAsFirstChildOf($this);
          $this->getNode()->setRightValue($this->getNode()->getRightValue() + 2);
        }

        /**
         * For an unknown reason, the order of the children may become
         * corrupt unless a single (hydrating to object) query to
         * ioDoctrineMenuItem is made. Removing this will cause failures
         * starting at 2.5 of the unit test.
         *
         * @todo Figure out what's really going on deeper in Doctrine
         */
        if ($reorder)
        {
          Doctrine_Query::create()->from('ioDoctrineMenuItem')->fetchOne();
        }

        // call the persist recursively onto this item and its children
        $doctrineChild->persistFromMenuArray($childArr);
      }

      // any items still in $currentChildren are old, need to be deleted
      foreach ($currentChildren as $oldDoctrineMenu)
      {
        $oldDoctrineMenu->getNode()->delete();
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

    // convert labels into i18n format
    if ($this->getTable()->isI18n())
    {
      $data['Translation'] = array();

      // if a label is set on the menu, set it on the Translation for the default culture
      if (isset($data['label']))
      {
        $defaultCulture = sfConfig::get('sf_default_culture');
        $data['Translation'][$defaultCulture]['label'] = $data['label'];
        unset($data['label']);
      }

      // process any actual i18n labels if any are set
      if (isset($data['i18n_labels']))
      {
        foreach ($data['i18n_labels'] as $culture => $label)
        {
          $data['Translation'][$culture]['label'] = $label;
        }

        unset($data['i18n_labels']);
      }
    }

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

  /**
   * Returns data matching this object in an array, but hydrated into
   * a menu hierarchy
   *
   * @return array
   */
  protected function _getMenuHierarchy()
  {
    $q = Doctrine_Query::create()
      ->from('ioDoctrineMenuItem m INDEXBY m.name')
      ->select('m.name, m.class, m.route, m.attributes, m.requires_auth, m.requires_no_auth, m.level, c.name')
      ->leftJoin('m.Permissions c')
      ->where('m.root_id = ?', $this['id']);

    if ($this->getTable()->isI18n())
    {
      $q->leftJoin('m.Translation t');
      $q->addSelect('t.label');
    }
    else
    {
      $q->addSelect('m.label');
    }

    /**
     * The HYDRATE_ARRAY_HIERARCHY method assumes that you're nested set
     * is returned in a certain order:
     *  --rt
     *    --pt1
     *      --ch1
     *    --pt2
     * The above nested set must be returned in this order: rt, pt1, ch1, pt2.
     * This is accomplished by ordering everything by lft ASC.
     */
    $q->orderBy('m.lft ASC');

    return $q->fetchOne(null, Doctrine_Core::HYDRATE_ARRAY_HIERARCHY);
  }

  /**
   * Converts the data from getMenuHierarchy into a form usable for import
   * by ioMenuItem.
   *
   * This is roughly opposite to the transformations done when persisting
   * data in persistFromMenuArray() and _convertMenuData
   *
   * @param  array $data The tree array data from getMenuHierarchy()
   * @return array
   */
  protected static function _convertHierarchyToMenuArray($data)
  {
    unset($data['level'], $data['id']);

    if (isset($data['Permissions']))
    {
      $credentials = array();
      foreach ($data['Permissions'] as $permission)
      {
        $credentials[] = $permission['name'];
      }

      $data['credentials'] = $credentials;
      unset($data['Permissions']);
    }

    // handle i18n
    if (isset($data['Translation']))
    {
      // we have i18n, so create an array of i18n labels
      $i18nLabels = array();
      foreach ($data['Translation'] as $culture => $i18nData)
      {
        if ($i18nData['label'])
        {
          $i18nLabels[$culture] = $i18nData['label'];
        }
      }
      $data['i18n_labels'] = $i18nLabels;
      unset($data['Translation']);

      // try to set the default label from the default culture
      $defaultCulture = sfConfig::get('sf_default_culture');
      if (isset($i18nLabels[$defaultCulture]))
      {
        $data['label'] = $i18nLabels[$defaultCulture];
      }
    }

    // convert the attributes back into an array
    $data['attributes'] = sfToolkit::stringToArray($data['attributes']);

    // handle the children data
    if (isset($data['__children']))
    {
      $childrenData = array();
      foreach ($data['__children'] as $childData)
      {
        // recurse the children data onto this method
        $childrenData[$childData['name']] = self::_convertHierarchyToMenuArray($childData);
      }

      $data['children'] = $childrenData;
      unset($data['__children']);
    }

    return $data;
  }

  /**
   * Post save to attempt to clear the menu cache.
   *
   * If using different cache driver between multiple applications, this
   * will only clear the cache for the current application.
   *
   * @return void
   */
  public function postSave($event)
  {
    if (sfProjectConfiguration::hasActive())
    {
      sfProjectConfiguration::getActive()
        ->getPluginConfiguration('ioDoctrineMenuPlugin')
        ->getMenuManager()
        ->clearCache($this);
    }
  }

  /**
   * Return a JSON-encoded nested set array of this menu
   *
   * This assists in the json response expected by jQuery Nested Sortable. 
   *
   * @return array|false  A nested array ready to be converted to json
   * @author Brent Shaffer
   */
  public function generateNestedSortableArray()
  {
    if (!$this->getNode()->isRoot())
    {
      throw new sfException('ioDoctrineMenuItem::findAllNestedsetJson() can only be called on root nodes.');
    }

    $children = $this->getNode()->getDescendants();

    // Generate a JSON-encoded Nested Set Array
    if ($children->count() > 0)
    {
      $childrenArr = $children->toArray();

      $itemArray = array();
      $itemArray['requestFirstIndex'] = 0;
      $itemArray['firstIndex'] = 0;
      $itemArray['count'] = count($childrenArr);
      $itemArray['columns'] = array('&ldquo;'.$this->name.'&rdquo;');

      $items = array();
      foreach ($childrenArr as $childArr)
      {
        $jsonItem = array(
          'id'    => $childArr['id'],
          'level' => $childArr['level'],
          'info'  => array('<strong>'.$childArr['name'].'</strong>')
        );
        $items[] = $jsonItem;
      }

      // Set Nest Level
      $itemArray['items'] = array_values(ioDoctrineMenuToolkit::nestify($items, 1));

      return $itemArray;
    }

    return false;
  }

  /**
   * Used in the admin generator to space out the names so that they
   * have a hierarchy feel
   *
   * @return string
   */
  public function getIndentedName()
  {
    return str_repeat('-', $this->getLevel()) . ' ' . $this->getName().($this->getLevel() == 0 ? ' ('.$this->getName().')':null);
  }
}