<?php

/**
 * io_doctrine_menu module configuration.
 *
 * @package    ioDoctrineMenuPlugin
 * @subpackage generator
 * @author     Your name here
 * @version    SVN: $Id: configuration.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class io_doctrine_menuGeneratorConfiguration extends BaseIo_doctrine_menuGeneratorConfiguration
{
  public function getTableMethod()
  {
    return 'getOrderedTreeQuery';
  }
}
