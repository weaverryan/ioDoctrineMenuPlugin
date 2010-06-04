<?php
/**
 * Adds methods that can be called from the actions class
 * 
 * @package     ioDoctrineMenuPlugin
 * @subpackage  actions
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */
class ioDoctrineMenuActions
{
  /**
   * @var ioDoctrineMenuPluginConfiguration
   */
  protected $_pluginConfiguration;

  /**
   * Class constructor
   *
   * @param ioDoctrineMenuPluginConfiguration $configuration
   */
  public function __construct(ioDoctrineMenuPluginConfiguration $configuration)
  {
    $this->_pluginConfiguration = $configuration;
  }

  /**
   * Returns an ioMenuItem tree loaded from the given name that corresponds
   * to a root node name in the ioDoctrineMenuItem model.
   *
   * This method cann be called directly from the actions class.
   *
   * @param  string $name The name of the root menu item to return
   * @return ioMenuItem
   */
  public function getDoctrineMenu($name)
  {
    return $this->_pluginConfiguration->getMenuManager()
      ->getMenu($name);
  }

  /**
   * Listener method for method_not_found events
   *
   * This allows any public other methods in this class to be called as
   * if they were in the actions class.
   */
  public function extend(sfEvent $event)
  {
    $this->_subject = $event->getSubject();
    $method = $event['method'];
    $arguments = $event['arguments'];

    if (method_exists($this, $method))
    {
      $result = call_user_func_array(array($this, $method), $arguments);

      $event->setReturnValue($result);

      return true;
    }
    else
    {
      return false;
    }
  }
}