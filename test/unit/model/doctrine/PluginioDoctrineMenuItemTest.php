<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';

$t = new lime_test(5);

$t->info('1 - Test getChildrenIndexedByName().');
  extract(create_test_tree($t)); // create the tree and make its vars accessible
  print_test_tree($t);

  $children = $rt->getChildrenIndexedByName();
  $t->is(count($children), 2, '->getChildrenIndexedByName() returns 2 for rt');
  $t->is(array_keys($children), array('pt1', 'pt2'), '->getChildrenIndexedByName() has the correct indexes');
  $t->is($children['pt1']->name, 'pt1', '->getChildrenIndexedByName() returns the correct items.');

  $t->is(count($pt1->getChildrenIndexedByName()), 1, '->getChildrenIndexedByName() returns 1 item for pt1.');
  $t->is(count($pt2->getChildrenIndexedByName()), 0, '->getChildrenIndexedByName() returns 0 items for pt2.');

// clears the menu database and then creates a test tree
function create_test_tree(lime_test $t)
{
  $t->info('### Creating test tree.');
  Doctrine_Query::create()->from('ioDoctrineMenuItem')->delete()->execute();
  $tbl = Doctrine_Core::getTable('ioDoctrineMenuItem');

  $rt = new ioDoctrineMenuItem();
  $rt->name = 'rt';
  $rt->save();
  $tbl->getTree()->createRoot($rt);

  $pt1 = new ioDoctrineMenuItem();
  $pt1->name = 'pt1';
  $pt1->save();
  $pt1->getNode()->insertAsLastChildOf($rt);
  $rt->refresh();

  $pt2 = new ioDoctrineMenuItem();
  $pt2->name = 'pt2';
  $pt2->save();
  $pt2->getNode()->insertAsLastChildOf($rt);
  $rt->refresh();

  $ch1 = new ioDoctrineMenuItem();
  $ch1->name = 'ch11';
  $ch1->save();
  $ch1->getNode()->insertAsLastChildOf($pt1);
  $pt1->refresh();

  $rt->refresh();
  $pt1->refresh();
  $pt2->refresh();
  $ch1->refresh();

  return array(
    'rt' => $rt,
    'pt1' => $pt1,
    'pt2' => $pt2,
    'pt3' => $pt3,
  );
}
function print_test_tree(lime_test $t)
{
  $t->info('      Menu Structure   ');
  $t->info('               rt      ');
  $t->info('             /    \    ');
  $t->info('          pt1      pt2 ');
  $t->info('           |           ');
  $t->info('          ch1          ');
}