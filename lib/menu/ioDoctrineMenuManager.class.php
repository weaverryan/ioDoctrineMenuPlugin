<?php

/**
 * Manages the Doctrine menus: db retrieval, caching, etc
 *
 * @package    ioDoctrineMenuItemPlugin
 * @subpackage menu
 * @author     Ryan Weaver <ryan.weaver@iostudio.com>
 */
class ioDoctrineMenuManager
{
  protected
    $_cacheDriver;

  public function __construct()
  {
  }

  /**
   * Retrieves a menu identified by the given name.
   *
   * The name should correspond to a name on a root doctrine node 
   *
   * @param  string $name The name of the root node to retrieve
   * @return ioMenuItem|null
   */
  public function getMenu($name)
  {
    $cacheKey = md5($name);
    if ($data = $this->getCache($cacheKey))
    {
      $data = unserialize($data);

      return ioMenuItem::createFromArray($data);
    }

    $menu = Doctrine_Core::getTable('ioDoctrineMenuItem')->fetchMenu($name);

    if ($menu)
    {
      $this->setCache($cacheKey, serialize($menu->toArray()));
    }

    return $menu;
  }  

  /**
   * Set the optional cacheDriver dependency. Menu items will be cached
   * to and from this driver
   *
   * @param sfCache $cacheDriver
   * @return void
   */
  public function setCacheDriver(sfCache $cacheDriver)
  {
    $this->_cacheDriver = $cacheDriver;
  }

  /**
   * @return sfCache
   */
  public function getCacheDriver()
  {
    return $this->_cacheDriver;
  }

  /**
   * Returns an item from cache or false if it doesn't exist
   *
   * @param  string $cacheKey The cacheKey to retrieve
   * @return string|false
   */
  public function getCache($cacheKey)
  {
    if ($this->getCacheDriver())
    {
      return $this->getCacheDriver()->get($cacheKey);
    }
  }

  /**
   * Set a value into cache
   *
   * @param  string $cacheKey The cache key for the cache 
   * @param  string $value    The value to set to cache
   * @return void
   */
  public function setCache($cacheKey, $value)
  {
    if ($this->getCacheDriver())
    {
      $this->getCacheDriver()->set($cacheKey, $value);
    }
  }

  /**
   * Clears all cache entries related to the given menu item
   *
   * If $menu is null, all the cache will be cleared.
   *
   * @param ioDoctrineMenuItem|null $menu The item for which to clear the cache
   * @return void
   */
  public function clearCache(ioDoctrineMenuItem $menu = null)
  {
    if ($menu)
    {
      // get the root, the cache is based off of it
      $menu->refreshRelated('RootMenuItem');
      $menu = $menu['RootMenuItem'];
      $cacheKey = md5($menu->getName());
      $this->getCacheDriver()->remove($cacheKey);
    }
    else
    {
      $this->getCacheDriver()->clean();
    }
  }
}