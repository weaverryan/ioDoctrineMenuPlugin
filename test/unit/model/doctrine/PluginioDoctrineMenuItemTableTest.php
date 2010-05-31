<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(54);
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

$t->info('1 - Persist an entire menu to a new root. This should have the same effect as above, but creates the root for us.');
  $tbl->createQuery()->delete()->execute();
  $tbl->persist($menu);
  $rt = $tbl->findOneByLevel(0);
  complex_root_menu_check($rt, $t, 2);
  test_total_nodes($t, array(0 => 1, 1 => 2, 2 => 4, 3 => 1));
  root_sanity_check($t, $rt);
  $t->is($rt->getName(), 'Root li', 'The created root has the correct name.');
  $t->is($rt->getAttributes(), 'class="root"', 'The created root has the correct attributes.');