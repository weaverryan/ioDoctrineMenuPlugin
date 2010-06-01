<?php

/**
 * Plugin configuration
 *
 * @package    ioDoctrineMenuPlugin
 * @subpackage actions
 * @author     Brent Shaffer <bshafs@gmail.com>
 */
class Baseio_doctrine_menuActions extends sfActions
{
  public function executeReorder(sfWebRequest $request)
  {
    $this->name = $request->getParameter('menu');
  }

  public function executeJson(sfWebRequest $request)
  {
    $name = $request->getParameter('menu');

    $menu = Doctrine::getTable('ioDoctrineMenuItem')->findAllNestedsetJson($name);

    return $this->renderText(json_encode($menu));
  }

  public function executeSavejson(sfWebRequest $request)
  {
    $name = $request->getParameter('menu');

    if ($nestedSet = $request->getParameter('nested-sortable-widget'))
    {
      // Start with the root
      Doctrine::getTable("ioDoctrineMenuItem")->restoreTreeFromNestedArray($nestedSet['items'], $name);

      return true;
    }

    return false;
  }
}