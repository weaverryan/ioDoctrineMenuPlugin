<?php

/**
 * Plugin configuration
 *
 * @package    ioDoctrineMenuPlugin
 * @subpackage Doctrine_Record
 * @author     Ryan Weaver <ryan.weaver@iostudio.com>
 */
class ioDoctrineMenuPluginConfiguration extends sfPluginConfiguration
{
  protected $_menuManager;

  public function initialize()
  {
    if (in_array('io_doctrine_menu', sfConfig::get('sf_enabled_modules', array())))
    {
      $this->dispatcher->connect('routing.load_configuration', array($this, 'loadRoutes'));
    }
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

  /**
   * Listens to routing.load_configuration
   *
   * @param sfEvent $event The routing.load_configuration event
   * @return void
   */
  public function loadRoutes(sfEvent $event)
  {
    $prefix = sfConfig::get('app_doctrine_menu_module_prefix', '/admin/menu');

    $event->getSubject()->prependRoute('io_doctrine_menu', new sfDoctrineRouteCollection(array(
      'name'                  => 'io_doctrine_menu',
      'model'                 => 'ioDoctrineMenuItem',
      'module'                => 'io_doctrine_menu',
      'prefix_path'           => $prefix,
      'with_wildcard_routes'  => true,
      'collection_actions'    => array(),
      'requirements'          => array(),
    )));

    $event->getSubject()->prependRoute('io_doctrine_menu_reorder', new sfRoute(
      $prefix.'/reorder/:name',
      array(
        'module'  => 'io_doctrine_menu',
        'action'  => 'reorder',
      ),
      array(
        'sf_method' => array('get'),
      )
    ));

    $event->getSubject()->prependRoute('io_doctrine_menu_reorder_json', new sfRoute(
      $prefix.'/reorder/json/:name',
      array(
        'module'  => 'io_doctrine_menu',
        'action'  => 'json',
      ),
      array(
        'sf_method' => array('get'),
      )
    ));

    $event->getSubject()->prependRoute('io_doctrine_menu_reorder_save', new sfRoute(
      $prefix.'/reorder/save/:name',
      array(
        'module'  => 'io_doctrine_menu',
        'action'  => 'saveJson',
      ),
      array(
        'sf_method' => array('post'),
      )
    ));
  }
}