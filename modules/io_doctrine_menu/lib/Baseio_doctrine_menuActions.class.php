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
    $this->menu = $this->getRoute()->getObject();
  }

  /**
   * Responds to an ajax call by nested sortable and delivers a json
   * object of the given tree
   */
  public function executeJson(sfWebRequest $request)
  {
    $menu = $this->getRoute()->getObject();
    $arr = $menu->generateNestedSortableArray();

    return $this->renderText(json_encode($arr));
  }

  /**
   * Receives the ajax post to save the menu ordering
   */
  public function executeSaveJson(sfWebRequest $request)
  {
    $menu = $this->getRoute()->getObject();

    if ($nestedSet = $request->getParameter('nested-sortable-widget'))
    {
      // Start with the root
      Doctrine::getTable("ioDoctrineMenuItem")->restoreTreeFromNestedArray($nestedSet['items'], $menu);

      return true;
    }

    return false;
  }
}