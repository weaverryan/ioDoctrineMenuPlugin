<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(34);

$t->info('1 - Test getChildrenIndexedByName().');
  extract(create_doctrine_test_tree($t)); // create the tree and make its vars accessible
  print_test_tree($t);

  $children = $rt->getChildrenIndexedByName();
  $t->is(count($children), 2, '->getChildrenIndexedByName() returns 2 for rt');
  $t->is(array_keys($children), array('pt1', 'pt2'), '->getChildrenIndexedByName() has the correct indexes');
  $t->is($children['pt1']->name, 'pt1', '->getChildrenIndexedByName() returns the correct items.');

  $t->is(count($pt1->getChildrenIndexedByName()), 1, '->getChildrenIndexedByName() returns 1 item for pt1.');
  $t->is(count($pt2->getChildrenIndexedByName()), 0, '->getChildrenIndexedByName() returns 0 items for pt2.');

$t->info('2 - Test persistFromMenuArray() in a varierty of situations.');
  $arr = create_test_tree($t);
  $menu = $arr['menu'];

  print_test_tree($t);

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