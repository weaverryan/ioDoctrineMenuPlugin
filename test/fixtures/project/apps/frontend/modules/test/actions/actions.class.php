<?php
// test actions class
class testActions extends sfActions
{
  // tests calling ->getMenu() on the actions class
  public function executeGetMenu(sfWebRequest $request)
  {
    $this->menu = $this->getDoctrineMenu('Root li');
    $this->setTemplate('render');
    $this->renderText($this->menu->render());

    $this->setLayout(false);
    return sfView::NONE;
  }

  // test for using the get_doctrine_menu() helper
  public function executeUseHelper(sfWebRequest $request)
  {
  }
}