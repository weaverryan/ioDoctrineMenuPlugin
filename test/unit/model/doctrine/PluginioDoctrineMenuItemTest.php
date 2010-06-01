<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(182);

$t->info('1 - Test getChildrenIndexedByName().');
  extract(create_doctrine_test_tree($t)); // create the tree and make its vars accessible
  print_test_tree($t);

  $children = $rt->getChildrenIndexedByName();
  $t->is(count($children), 2, '->getChildrenIndexedByName() returns 2 for rt');
  $t->is(array_keys($children), array('Parent 1', 'Parent 2'), '->getChildrenIndexedByName() has the correct indexes');
  $t->is($children['Parent 1']->name, 'Parent 1', '->getChildrenIndexedByName() returns the correct items.');

  $t->is(count($pt1->getChildrenIndexedByName()), 3, '->getChildrenIndexedByName() returns 3 item for pt1.');
  $t->is(count($pt2->getChildrenIndexedByName()), 1, '->getChildrenIndexedByName() returns 1 item for pt2.');

$t->info('2 - Test persistFromMenuArray() in a varierty of situations.');
  $menu = new ioMenuItem('Root li');

  $t->info('  2.1 - First try it without any children - should just update root values.');

    $t->info('    2.1.1 - Persist a menu with mostly blank fields.');
    $rt = create_root('rt');
    $menu->setAttributes(array()); // clear the default "root" class attribute
    $rt->persistFromMenuArray($menu->toArray(false));
    $t->is($rt->getName(), 'Root li', '->getName() returns "Root li".');
    $t->is($rt->getLabel(), null, '->getLabel() returns null.');
    $t->is($rt->getRoute(), null, '->getRoute() returns null.');
    $t->is($rt->getAttributes(), '', '->getAttributes() returns an empty string.');
    $t->is($rt->getRequiresAuth(), false, '->getRequiresAuth() returns false.');
    $t->is($rt->getRequiresNoAuth(), false, '->getRequiresNoAuth() returns false.');
    $t->is(count($rt->Permissions), 0, '->Permissions matches 0 items');

    // setup some interesting values to persist
    $menu->setLabel('sympal');
    $menu->setRoute('http://www.sympalphp.org');
    $menu->setAttributes(array('class' => 'root', 'id' => 'sympal_menu'));
    $menu->requiresAuth(true);
    $menu->requiresNoAuth(false);
    $menu->setCredentials(array(array('c1', 'c2')));

    $t->info('    2.1.2 - Persisting a menu with multi-level credentials is not supported - an exception is thrown.');
    $rt = create_root('rt');
    try
    {
      $rt->persistFromMenuArray($menu->toArray(false));
      $t->fail('Exception not thrown');
    }
    catch (sfException $e)
    {
      $t->pass('Exception thrown: '.$e->getMessage());
    }

    $t->info('    2.1.3 - Persist a valid menu item with several different fields filled in (no refresh).');
    $rt = create_root('rt');
    $menu->setCredentials(array('c1', 'c2'));
    $rt->persistFromMenuArray($menu->toArray(false));
    basic_root_menu_check($rt, $t);

    $t->info('    2.1.4 - Run the same checks after a full refresh on rt. See that the values were actually persisted.');
    $rt->refresh(true);
    basic_root_menu_check($rt, $t);

    $t->info('    2.1.5 - Give rt a c3 credential then merge in c1,c2 credentials from the menu.');
    $rt = create_root('rt');
    $c3 = new sfGuardPermission();
    $c3->name = 'c3';
    $c3->save();
    $rt->link('Permissions', array($c3['id']));
    $rt->save();
    $rt->persistFromMenuArray($menu->toArray(false));
    $rt->refresh(true);
    $t->is(count($rt->Permissions), 2, '->Permissions only matches two items - c3 was removed');

    $t->info('    2.1.6 - Give rt a c2 credential then merge in c1,c2 credentials from the menu.');
    $rt = create_root('rt');
    $c2 = Doctrine_Core::getTable('sfGuardGroup')->findOneByName('c2');
    $rt->link('Permissions', array($c2['id']));
    $rt->save();
    $rt->persistFromMenuArray($menu->toArray(false));
    $rt->refresh(true);
    $t->is(count($rt->Permissions), 2, '->Permissions only matches two items - c2 was not duplicated.');

    $t->info('    2.1.7 - Give rt c1 and c2 credentials then merge in a menu with no credentials.');
    $rt = create_root('rt');
    $c1 = Doctrine_Core::getTable('sfGuardGroup')->findOneByName('c1');
    $rt->link('Permissions', array($c1['id'], $c2['id']));
    $rt->save();
    $menu->setCredentials(array());
    $rt->persistFromMenuArray($menu->toArray(false));
    $rt->refresh(true);
    $t->is(count($rt->Permissions), 0, '->Permissions matches 0 items - c1 and c2 were removed.');

  $t->info('  2.2 - Test persisting with children');
    $arr = create_test_tree($t);
    $menu = $arr['menu'];
    $menu['Parent 1']->setAttributes(array('class' => 'parent1'));
    $menu['Parent 1']['Child 2']->setCredentials(array('c2', 'c3'));
    print_test_tree($t);

    $rt = create_root('rt');
    persist_menu($t, $rt, $menu);

    $t->info('  Check the integrity of the tree.');
    complex_root_menu_check($rt, $t, 2);
    test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 4, 3 => 1));
    root_sanity_check($t, $rt);

  $t->info('  2.3 - Persist the tree again with no changes, should still be intact');
    print_test_tree($t);
    persist_menu($t, $rt, $menu);

    $t->info('  Check the integrity of the tree.');
    complex_root_menu_check($rt, $t, 3);
    test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 4, 3 => 1));
    root_sanity_check($t, $rt);

  $t->info('  2.4 - Make some normal property changes to the menu, re-persist');
    $menu->setRoute('http://www.sympalphp.org');
    $menu['Parent 1']->requiresAuth(true);
    $menu['Parent 2']['Child 4']['Grandchild 1']->setLabel('grandchild label');
    $menu['Parent 1']['Child 2']->setCredentials(array());
    $menu['Parent 1']['Child 3']->setCredentials(array('c1'));

    $t->info('  Check the integrity of the tree.');
    persist_menu($t, $rt, $menu);
    test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 4, 3 => 1));
    root_sanity_check($t, $rt);

    $t->is($rt->getRoute(), 'http://www.sympalphp.org', 'The route of rt was updated correctly.');
    $parents = $rt->getNode()->getChildren();                // array(pt1, p2)
    $children1 = $parents[0]->getNode()->getChildren();      // array(ch1, ch2, ch3)
    $children2 = $parents[1]->getNode()->getChildren();      // array(ch4)
    $grandchildren = $children2[0]->getNode()->getChildren(); // array(gc1)

    $t->is($parents[0]->getRequiresAuth(), true, 'requires_auth of pt1 was updated correctly.');
    $t->is($grandchildren[0]->getLabel(), 'grandchild label', 'The labelof gc1 was updated correctly.');
    $t->is(count($children1[1]->Permissions), 0, 'Permissions from ch2 were removed correctly.');
    $t->is(count($children1[2]->Permissions), 1, 'Permissions from ch3 were added correctly.');

  $t->info('  2.5 - Add, delete and remove some menu elements');

    $t->info('    2.5.1 - Add ch5 under pt1');
      $menu['Parent 1']->addChild('Child 5');
      persist_menu($t, $rt, $menu);
      $t->info('  Check the integrity of the tree.');
      test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 5, 3 => 1));
      root_sanity_check($t, $rt);
      check_child_ordering($t, $rt, array(0), array('Child 1', 'Child 2', 'Child 3', 'Child 5'));

    $t->info('    2.5.2 - Move ch2 after ch5');
      $ch2 = $menu['Parent 1']['Child 2'];
      $menu['Parent 1']->removeChild($ch2); // remove ch2
      $menu['Parent 1']->addChild($ch2); // add it back after ch5
      persist_menu($t, $rt, $menu);
      $t->info('  Check the integrity of the tree.');
      test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 5, 3 => 1));
      root_sanity_check($t, $rt);
      check_child_ordering($t, $rt, array(0), array('Child 1', 'Child 3', 'Child 5', 'Child 2'));

    $t->info('    2.5.3 - Remove ch3 (which has no children)');
      $menu['Parent 1']->removeChild('Child 3');
      persist_menu($t, $rt, $menu);
      $t->info('  Check the integrity of the tree.');
      test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 4, 3 => 1));
      root_sanity_check($t, $rt);
      check_child_ordering($t, $rt, array(0), array('Child 1', 'Child 5', 'Child 2'));
      $ch3Count = Doctrine_Query::create()->from('ioDoctrineMenuItem m')->where('m.name = ?', 'Child 3')->count();
      $t->is($ch3Count, 0, 'The ch3 menu item was deleted entirely.');

    $t->info('    2.5.4 - Remove ch4 (which has gc1 child)');
      $menu['Parent 2']->removeChild('Child 4');
      persist_menu($t, $rt, $menu);
      $t->info('  Check the integrity of the tree.');
      test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 3, 3 => 0));
      root_sanity_check($t, $rt);
      check_child_ordering($t, $rt, array(1), array());
      $ch4Count = Doctrine_Query::create()->from('ioDoctrineMenuItem m')->where('m.name = ?', 'Child 4')->count();
      $gc1Count = Doctrine_Query::create()->from('ioDoctrineMenuItem m')->where('m.name = ?', 'Grandchild 1')->count();
      $t->is($ch4Count, null, 'The ch4 menu item was deleted entirely.');
      $t->is($gc1Count, null, 'The gc1 menu item was deleted entirely.');

    $t->info('    2.5.5 - Add a new child (ch6) to pt2.');
      $menu['Parent 2']->addChild('Child 6');
      persist_menu($t, $rt, $menu);
      $t->info('  Check the integrity of the tree.');
      test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 4, 3 => 0));
      root_sanity_check($t, $rt);
      check_child_ordering($t, $rt, array(1), array('Child 6'));

    $t->info('    2.5.6 - Add a pt3 under root after pt2');
      $menu->addChild('Parent 3', 'http://www.doctrine-project.org');

      persist_menu($t, $rt, $menu);
      $t->info('  Check the integrity of the tree.');
      test_total_nodes($t, array(0 => 1, 1 => 3, 2 => 4, 3 => 0));
      root_sanity_check($t, $rt);
      check_child_ordering($t, $rt, array(), array('Parent 1', 'Parent 2', 'Parent 3'));

$t->info('3 - Test createMenu() to create a new ioMenuItem tree from the database.');
  // clear out the data
  Doctrine_Query::create()->from('ioDoctrineMenuItem')->delete()->execute();
  Doctrine_Query::create()->from('sfGuardPermission')->delete()->execute();

  // create the tree and make its vars accessible
  extract(create_doctrine_test_tree($t));
  print_test_tree($t);

  $t->info('  3.1 - Adding some Permissions for testing');
    $c1 = new sfGuardPermission();
    $c1->name = 'c1';
    $c1->save();
    $c2= new sfGuardPermission();
    $c2->name = 'c2';
    $c2->save();
    $rt->link('Permissions', array($c1->id, $c2->id));
    $rt->save();

  $t->info('  3.2 - Creating the menu object.');
    $timer = new sfTimer();
    $menu = $rt->createMenu();
    $timer->addTime();
    $t->info(sprintf(
      '### Menu created from db in %s sec (%s nodes/min)',
      round($timer->getElapsedTime(), 4),
      floor(8 * 60 / $timer->getElapsedTime())
    ));

  $t->info('  3.3 - Running tests on the created menu object');
    $t->is(get_class($menu), 'ioMenuItem', 'The menu rt has the correct class');
    $t->is(count($menu->getChildren()), 2, 'The menu rt has 2 children');
    $t->is(array_keys($menu->getChildren()), array('Parent 1', 'Parent 2'));
    $t->is($menu->getAttributes(), array('class' => 'root'), 'The menu rt has the correct attributes array');
    $t->is($menu->getCredentials(), array('c1', 'c2'), 'The menu rt has the correct credentials array');

    $t->is(count($menu['Parent 1']->getChildren()), 3, 'pt1 has 3 children.');
    $t->is(array_keys($menu['Parent 1']->getChildren()), array('Child 1', 'Child 2', 'Child 3'), 'pt1\'s children are array(Child 1, Child 2, Child 3)');
    $t->is(count($menu['Parent 2']->getChildren()), 1, 'pt2 has 1 child.');
    $t->is(array_keys($menu['Parent 2']->getChildren()), array('Child 4'), 'pt2\'s children are array(Child 4)');

  $t->info('  3.4 - Compare the created meno to that of the menu from create_test_tree(). They should be identical.');
    $arr = create_test_tree($t);
    $matchingMenu = $arr['menu'];
    // update its credentials to match
    $matchingMenu->setCredentials(array('c1', 'c2'));

    // just doing in stages so its more obvious when something fails
    $t->is($menu->toArray(false), $matchingMenu->toArray(false), 'The menus match non-recursively.');
    $t->is($menu->toArray(), $matchingMenu->toArray(), 'The full menus match recursively.');
