<?php

if (!isset($_SERVER['SYMFONY']))
{
  throw new RuntimeException('Could not find symfony core libraries.');
}

require_once $_SERVER['SYMFONY'].'/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    // enable the plugin and its dependent plugins.
    $this->setPlugins(array('ioDoctrineMenuPlugin', 'sfDoctrinePlugin', 'ioMenuPlugin'));
    $this->setPluginPath('ioDoctrineMenuPlugin', dirname(__FILE__).'/../../../..');
  }
}
