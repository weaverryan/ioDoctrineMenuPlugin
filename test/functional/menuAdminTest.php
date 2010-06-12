<?php
// functional test for a few special things in the admin module
require_once dirname(__FILE__).'/../bootstrap/functional.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$browser = new sfTestFunctional(new sfBrowser());
$browser->setTester('doctrine', 'sfTesterDoctrine');

$arr = create_doctrine_test_tree($browser->test());
$root = $arr['rt'];
$rootForm = new ioDoctrineMenuItemForm($root);

$browser->info('1 - Edit an existing, you cannot place it via parent_id')
  ->get(sprintf('/test/menu/%d/edit', $root->id))

  ->with('request')->begin()
    ->isParameter('module', 'io_doctrine_menu')
    ->isParameter('action', 'edit')
  ->end()

  ->with('response')->begin()
    ->checkForm($rootForm)
    ->info('  1.1 - The root menu has no parent_id field')
    ->checkElement('#io_doctrine_menu_item_parent_id', 0)
  ->end()

  ->click('Save', array('io_doctrine_menu_item' => array('parent_id' => $root->id)))

  ->with('form')->begin()
    ->hasErrors(1)
    ->hasGlobalError('extra_fields')
  ->end()
;

$browser->info('2 - Create a new menu item, set it as a child of something')
  ->get('/test/menu/new')

  ->with('request')->begin()
    ->isParameter('module', 'io_doctrine_menu')
    ->isParameter('action', 'new')
  ->end()

  ->with('response')->begin()
    ->checkForm('ioDoctrineMenuItemForm')
    ->info('  2.1 - The root menu has a parent_id field')
    ->checkElement('#io_doctrine_menu_item_parent_id', 1)
  ->end()

  ->click('Save', array('io_doctrine_menu_item' => array('parent_id' => $root->id, 'name' => 'new child')))

  ->with('form')->begin()
    ->hasErrors(0)
  ->end()

  ->with('doctrine')->begin()
    ->check('ioDoctrineMenuItem', array(
      'name' => 'new child',
      'root_id' => $root->id,
      'lft' => 16,
      'rgt' => 17,
      'level' => 1,
    ))
  ->end()
;die;

$browser->info('3 - Create a new menu item, make it root')
  ->get('/test/menu/new')

  ->with('request')->begin()
    ->isParameter('module', 'io_doctrine_menu')
    ->isParameter('action', 'new')
  ->end()

  ->click('Save', array('io_doctrine_menu_item' => array('name' => 'new root')))

  ->with('form')->begin()
    ->hasErrors(0)
  ->end()

  ->with('doctrine')->begin()
    ->check('ioDoctrineMenuItem', array(
      'name' => 'new root',
      'lft' => 18,
      'rgt' => 19,
      'level' => 0,
    ))
  ->end()
;