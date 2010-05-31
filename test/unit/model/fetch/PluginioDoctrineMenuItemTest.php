<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(11);

$t->info('1 - Test createMenu() to create a new ioMenuItem tree from the database.');
  extract(create_doctrine_test_tree($t)); // create the tree and make its vars accessible
  print_test_tree($t);

  $t->info('  1.1 - Adding some Permissions for testing');
    $c1 = new sfGuardPermission();
    $c1->name = 'c1';
    $c1->save();
    $c2= new sfGuardPermission();
    $c2->name = 'c2';
    $c2->save();
    $rt->link('Permissions', array($c1->id, $c2->id));
    $rt->save();

  $t->info('  1.2 - Creating the menu object.');
    $timer = new sfTimer();
    $menu = $rt->createMenu();
    $timer->addTime();
    $t->info(sprintf(
      '### Menu created from db in %s sec (%s nodes/min)',
      round($timer->getElapsedTime(), 4),
      floor(8 * 60 / $timer->getElapsedTime())
    ));

  $t->info('  1.3 - Running tests on the created menu object');
    $t->is(get_class($menu), 'ioMenuItem', 'The menu rt has the correct class');
    $t->is(count($menu->getChildren()), 2, 'The menu rt has 2 children');
    $t->is(array_keys($menu->getChildren()), array('Parent 1', 'Parent 2'));
    $t->is($menu->getAttributes(), array('class' => 'root'), 'The menu rt has the correct attributes array');
    $t->is($menu->getCredentials(), array('c1', 'c2'), 'The menu rt has the correct credentials array');

    $t->is(count($menu['Parent 1']->getChildren()), 3, 'pt1 has 3 children.');
    $t->is(array_keys($menu['Parent 1']->getChildren()), array('Child 1', 'Child 2', 'Child 3'), 'pt1\'s children are array(Child 1, Child 2, Child 3)');
    $t->is(count($menu['Parent 2']->getChildren()), 1, 'pt2 has 1 child.');
    $t->is(array_keys($menu['Parent 2']->getChildren()), array('Child 4'), 'pt2\'s children are array(Child 4)');
  
  $t->info('  1.4 - Compare the created meno to that of the menu from create_test_tree(). They should be identical.');
    $arr = create_test_tree($t);
    $matchingMenu = $arr['menu'];
    // update its credentials to match
    $matchingMenu->setCredentials(array('c1', 'c2'));

    // just doing in stages so its more obvious when something fails
    $t->is($menu->toArray(false), $matchingMenu->toArray(false), 'The menus match non-recursively.');
    $t->is($menu->toArray(), $matchingMenu->toArray(), 'The full menus match recursively.');