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
  /**
   * The main action that handles menu reordering
   */
  public function executeReorder(sfWebRequest $request)
  {
    $menu = $this->getRoute()->getObject();
    $this->name = $menu['name'];
  }

  /**
   * Responds to an ajax call by nested sortable and delivers a json
   * object of the given tree
   */
  public function executeJson(sfWebRequest $request)
  {
    $name = $request->getParameter('name');
    $menu = Doctrine::getTable('ioDoctrineMenuItem')->findAllNestedsetJson($name);

    return $this->renderText(json_encode($menu));
  }

  /**
   * Receives the ajax post to save the menu ordering
   */
  public function executeSavejson(sfWebRequest $request)
  {
    $name = $request->getParameter('name');

    if ($nestedSet = $request->getParameter('nested-sortable-widget'))
    {
      // Start with the root
      Doctrine::getTable("ioDoctrineMenuItem")->restoreTreeFromNestedArray($nestedSet['items'], $name);

      return true;
    }

    return false;
  }
}