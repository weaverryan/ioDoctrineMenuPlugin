<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(68);
$tbl = Doctrine_Core::getTable('ioDoctrineMenuItem');

$t->info('1 - Add a tree to an existing node. This should have the same effect as using ioDoctrineMenuItem::persistFromMenuArray()');
  $rt = create_root('rt');
  $arr = create_test_tree($t);
  $menu = $arr['menu'];
  $menu['Parent 1']->setAttributes(array('class' => 'parent1'));
  $menu['Parent 1']['Child 2']->setCredentials(array('c2', 'c3'));
  print_test_tree($t);

  $tbl->persist($menu, $rt);
  complex_root_menu_check($rt, $t, 2);
  test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 4, 3 => 1));
  root_sanity_check($t, $rt);

$t->info('2 - Persist an entire menu to a new root. This should have the same effect as above, but creates the root for us.');
  $tbl->createQuery()->delete()->execute();
  $tbl->persist($menu);
  $rt = $tbl->findOneByLevel(0);
  complex_root_menu_check($rt, $t, 2);
  test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 4, 3 => 1));
  root_sanity_check($t, $rt);
  $t->is($rt->getName(), 'Root li', 'The created root has the correct name.');
  $t->is($rt->getAttributes(), 'class="root"', 'The created root has the correct attributes.');

$t->info('3 - Test ->fetchMenu()');
  $t->info('  3.1 - Test a string name to the function.');
  $tbl->createQuery()->delete()->execute();
  create_doctrine_test_tree($t);
  $fromDbMenu = $tbl->fetchMenu('Root li');
  $arr = create_test_tree($t);
  $menu = $arr['menu'];
  $t->is($menu->toArray(), $fromDbMenu->toArray(), '->fetchMenu() retrieves the full, correct menu.');

$t->info('4 - Test the whole process. Persist a menu to the database and fetch it back out.');
  Doctrine_Query::create()->from('ioDoctrineMenuItem')->delete()->execute();
  $arr = create_test_tree($t);
  $menu = $arr['menu'];
  $tbl->persist($menu);
  $fromDbMenu = $tbl->fetchMenu('Root li');

  $t->info('  4.1 - Compare the original menu with the one stored and then fetched from the db');
  $t->is($menu->toArray(), $fromDbMenu->toArray(), 'They are equivalent.');

$t->info('5 - Test the cache invalidation');
  $manager = $configuration
    ->getPluginConfiguration('ioDoctrineMenuPlugin')
    ->getMenuManager();
  $cacheKey = md5('Root li');
  Doctrine_Query::create()->from('ioDoctrineMenuItem')->delete()->execute();
  $arr = create_doctrine_test_tree($t);
  $rt = $arr['rt'];

  $t->info('  5.1 - Retrieve the menu through the menu manager, it should set the cache.');
  $menu = $manager->getMenu('Root li');
  $t->is($manager->getCacheDriver()->has($cacheKey), true, 'Retrieving the menu sets the cache on the manager');

  $t->info('  5.2 - Change the root node and save, see that the cache cleared.');
  $rt->setLabel('Changed label');
  $rt->save();
  $t->is($manager->getCacheDriver()->has($cacheKey), false, 'The cache is now unset.');

  $t->info('  5.3 - Re-put the cache, modify a child element, and see that the cache clears.');
  $menu = $manager->getMenu('Root li');
  $t->is($manager->getCacheDriver()->has($cacheKey), true, 'Retrieving the menu sets the cache on the manager');
  $children = $rt->getNode()->getChildren();
  $children[0]->setRoute('http://www.doctrine-project.org');
  $children[0]->save();
  $t->is($manager->getCacheDriver()->has($cacheKey), false, 'The cache is now unset.');

$t->info('6 - Test restoreTreeFromNestedArray()');
  $tbl->createQuery()->delete()->execute();
  extract(create_doctrine_test_tree($t));

  $newOrder = array(
    array(
      'id' => $pt2->id,
      'children' => array(
        array(
          'id' => $ch4->id,
          'children' => array(
            array('id' => $gc1->id),
          )
        ),
      )
    ),
    array(
      'id' => $pt1->id,
      'children' => array(
        array('id' => $ch3->id),
        array('id' => $ch1->id),
        array('id' => $ch2->id),
      )
    )
  );

  $tbl->restoreTreeFromNestedArray($newOrder, $rt);
  root_sanity_check($t, $rt);    
  check_child_ordering($t, $rt, array(), array('Parent 2', 'Parent 1'));
  check_child_ordering($t, $rt, array(0), array('Child 4'));
  check_child_ordering($t, $rt, array(1), array('Child 3', 'Child 1', 'Child 2'));
