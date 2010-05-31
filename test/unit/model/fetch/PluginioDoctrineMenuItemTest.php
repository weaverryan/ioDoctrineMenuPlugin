<?php

require_once dirname(__FILE__).'/../../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(10);

$t->info('1 - Test getChildrenIndexedByName().');
  extract(create_doctrine_test_tree($t)); // create the tree and make its vars accessible
  print_test_tree($t);

  