<?php

/**
 * Plugin configuration
 *
 * @package    ioDoctrineMenuItemPlugin
 * @subpackage Doctrine_Record
 * @author     Ryan Weaver <ryan.weaver@iostudio.com>
 */
class ioDoctrineMenuPluginConfiguration extends sfPluginConfiguration
{
  protected $_menuManager;

  public function initialize()
  {
  }

  /**
   * Retrieves the ioDoctrineMenuManager for this context
   *
   * @return ioDoctrineMenuManager
   */
  public function getMenuManager()
  {
    if ($this->_menuManager === null)
    {
      $manager = new ioDoctrineMenuManager();

      // Set the cache driver if caching is enabled
      $cacheConfig = sfConfig::get('app_doctrine_menu_cache');
      if ($cacheConfig['enabled'])
      {
        $class = $cacheConfig['class'];
        $options = isset($cacheConfig['options']) ? $cacheConfig['options'] : array();

        $cacheDriver = new $class($options);
        $manager->setCacheDriver($cacheDriver);
      }

      $this->_menuManager = $manager;
    }

    return $this->_menuManager;
  }
}