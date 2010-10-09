<?php

/**
 * Helper class for retrieving ioMenuItems sourced from ioDoctrineMenuItem
 * 
 * @package     ioDoctrineMenuItemPlugin
 * @subpackage  helper
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */

/**
 * Returns an ioMenuItem tree loaded from the given name that corresponds
 * to a root node name in the ioDoctrineMenuItem model.
 *
 * @param  string $name The name of the root menu item to return
 * @return ioMenuItem
 */
function get_doctrine_menu($name)
{
  return sfApplicationConfiguration::getActive()
    ->getPluginConfiguration('ioDoctrineMenuPlugin')
    ->getMenuManager()
    ->getMenu($name);
}
