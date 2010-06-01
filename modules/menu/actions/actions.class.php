<?php

/**
 * menu actions.
 *
 * @package    ioDoctrineMenuPlugin
 * @subpackage menu
 */
class menuActions extends sfActions
{
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

  public function executeIndex(sfWebRequest $request)
  {
    $this->name = $request->getParameter('menu');
  }
}
