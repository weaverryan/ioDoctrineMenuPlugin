<?php

require_once dirname(__FILE__).'/../lib/io_doctrine_menuGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/io_doctrine_menuGeneratorHelper.class.php';

/**
 * Admin module base actions file
 *
 * @package    ioDoctrineMenuPlugin
 * @subpackage actions
 * @author     Brent Shaffer <bshafs@gmail.com>
 */
class Baseio_doctrine_menuActions extends autoIo_doctrine_menuActions
{
  /**
   * The main action that handles menu reordering
   */
  public function executeReorder(sfWebRequest $request)
  {
    $this->menu = $this->getRoute()->getObject();

    // if this menu item isn't a root, redirect to the root node
    if (!$this->menu->getNode()->isRoot())
    {
      $root = $this->menu->RootMenuItem;
      $this->redirect($this->generateUrl('io_doctrine_menu_reorder', array(
        'sf_subject' => $root,
      )));
    }
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

  /**
   * Overridden from the generated action so that delete() is called on
   * the nested set and not on the object directly.
   */
  public function executeDelete(sfWebRequest $request)
  {
    $request->checkCSRFProtection();

    $this->dispatcher->notify(new sfEvent($this, 'admin.delete_object', array('object' => $this->getRoute()->getObject())));

    if ($this->getRoute()->getObject()->getNode()->delete())
    {
      $this->getUser()->setFlash('notice', 'The item was deleted successfully.');
    }

    $this->redirect('@io_doctrine_menu');
  }

}