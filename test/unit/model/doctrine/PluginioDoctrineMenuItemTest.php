<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(5);

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
  Doctrine_Query::create()->from('ioDoctrineMenuItem')->delete()->execute();
  


