<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(71);

$t->info('1 - Test getChildrenIndexedByName().');
  extract(create_doctrine_test_tree($t)); // create the tree and make its vars accessible
  print_doctrine_test_tree($t);

  $children = $rt->getChildrenIndexedByName();
  $t->is(count($children), 2, '->getChildrenIndexedByName() returns 2 for rt');
  $t->is(array_keys($children), array('pt1', 'pt2'), '->getChildrenIndexedByName() has the correct indexes');
  $t->is($children['pt1']->name, 'pt1', '->getChildrenIndexedByName() returns the correct items.');

  $t->is(count($pt1->getChildrenIndexedByName()), 1, '->getChildrenIndexedByName() returns 1 item for pt1.');
  $t->is(count($pt2->getChildrenIndexedByName()), 0, '->getChildrenIndexedByName() returns 0 items for pt2.');

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

  $t->info('  2.3 - Persist the tree again with no changes, should still be intact');
    print_test_tree($t);
    persist_menu($t, $rt, $menu);
    complex_root_menu_check($rt, $t, 3);

  $t->info('  2.4 - Make some normal property changes to the menu, re-persist');
    $menu->setRoute('http://www.sympalphp.org');
    $menu['Parent 1']->requiresAuth(true);
    $menu['Parent 2']['Child 4']['Grandchild 1']->setLabel('grandchild label');
    $menu['Parent 1']['Child 2']->setCredentials(array());
    $menu['Parent 1']['Child 3']->setCredentials('c1');

    persist_menu($t, $rt, $menu);
    $t->is($rt->getRoute(), 'http://www.sympalphp.org', 'The route of rt was updated correctly.');
    $parents = $rt->getNode()->getChildren();                // array(pt1, p2)
    $children1 = $parents[0]->getNode()->getChildren();      // array(ch1, ch2, ch3)
    $children2 = $parents[1]->getNode()->getChildren();      // array(ch4)
    $grandchildren = $children2[0]->getNode()->getChildren(); // array(gc1)

    $t->is($parents[0]->getRequiresAuth(), true, 'requires_auth of pt1 was updated correctly.');
    $t->is($grandchildren[0]->getLabel(), 'grandchild label', 'The labelof gc1 was updated correctly.');
    $t->is(count($children1[1]->Permissions), 0, 'Permissions from ch2 were removed correctly.');
    $t->is(count($children1[2]->Permissions), 1, 'Permissions from ch3 were added correctly.');

// used in 2.1.* to run a few basic checks on the root menu item
function basic_root_menu_check(ioDoctrineMenuItem $rt, lime_test $t)
{
  $t->is($rt->getName(), 'Root li', '->getName() returns the correct value from the menu.');
  $t->is($rt->getLabel(), 'sympal', '->getLabel() returns the correct value from the menu.');
  $t->is($rt->getRoute(), 'http://www.sympalphp.org', '->getRoute() returns the correct value from the menu.');
  $t->is($rt->getAttributes(), 'class="root" id="sympal_menu"', '->getAttributes() returns the string representation of the attributes.');
  $t->is($rt->getRequiresAuth(), 1, '->getRequiresAuth() returns the correct value from the menu.');
  $t->is($rt->getRequiresNoAuth(), 0, '->getRequiresNoAuth() returns the correct value from the menu.');

  $permissions = $rt->Permissions;
  $t->is(count($permissions), 2, '->Permissions matches two items');
  $t->is($permissions[0]->name, 'c1', 'The c1 permission was properly set');
  $t->is($permissions[1]->name, 'c2', 'The c2 permission was properly set');
}

// used in 2.2.* to run checks on the integrity of the whole tree
function complex_root_menu_check(ioDoctrineMenuItem $rt, lime_test $t, $count)
{
  $children = $rt->getNode()->getChildren();

  $t->info('    2.'.$count.'.1 - Test the top-level menu integrity');
    $t->is(count($children), 2, '->getNode()->getChildren() on rt returns 2 children');
    $t->is($children[0]->name, 'Parent 1', '  The first child is pt1');
    $t->is($children[1]->name, 'Parent 2', '  The second child is pt2');
    $t->is($children[0]->getAttributes(), 'class="parent1"', 'The attributes were correctly set on Parent 1');

    $parent1 = $children[0];
    $parent2 = $children[1];

  $t->info('    2.'.$count.'.2 - Test the second-level menu integrity under pt1');
    $children = $parent1->getNode()->getChildren();
    $t->is(count($children), 3, '->getNode()->getChildren() on pt1 returns 3 children');
    $t->is($children[0]->name, 'Child 1', '  The first child is ch1');
    $t->is($children[1]->name, 'Child 2', '  The second child is ch2');
    $t->is($children[2]->name, 'Child 3', '  The third child is ch3');
    $t->is(count($children[1]->Permissions), 2, '  ch2 was given the proper permissions.');

    $t->is($children[0]->getNode()->getChildren(), false, '->getNode()->getChildren() on ch1 returns false');
    $t->is($children[1]->getNode()->getChildren(), false, '->getNode()->getChildren() on ch2 returns false');
    $t->is($children[2]->getNode()->getChildren(), false, '->getNode()->getChildren() on ch3 returns false');

  $t->info('    2.'.$count.'.3 - Test the second and third-level menu integrity under pt2');
    $children = $parent2->getNode()->getChildren();
    $t->is(count($children), 1, '->getNode()->getChildren() on pt2 returns 1 child');
    $t->is($children[0]->name, 'Child 4', '  The first child is ch4');
    $children = $children[0]->getNode()->getChildren();
    $t->is(count($children), 1, '->getNode()->getChildren() on ch4 returns 1 child');
    $t->is($children[0]->name, 'Grandchild 1', '  The first child is gc1');
}

// prints a message about how long a persist operation took
function persist_menu(lime_test $t, ioDoctrineMenuItem $rt, ioMenuItem $menu)
{
  $timer = new sfTimer();
  $rt->persistFromMenuArray($menu->toArray());
  $timer->addTime();
  $rt->refresh();
  $t->info(sprintf(
    '### Menu took %s to persist (%s nodes/min)',
    round($timer->getElapsedTime(), 4),
    floor(8 * 60 / $timer->getElapsedTime())
  ));
}