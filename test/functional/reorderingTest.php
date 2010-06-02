<?php

require_once dirname(__FILE__).'/../bootstrap/functional.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$browser = new sfTestFunctional(new sfBrowser());
$arr = create_doctrine_test_tree($browser->test());
$rt = $arr['rt'];

$browser->info('1 - Goto the reorder page and look around')
  ->info('  1.1 - Goto the reorder page with a fake name sends to a 404')
  ->get('/test/menu/reorder/fake')

  ->with('request')->begin()
    ->isParameter('module', 'io_doctrine_menu')
    ->isParameter('action', 'reorder')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(404)
  ->end()

  ->info('  1.2 - Goto a real menu reordering page')
  ->get('/test/menu/reorder/'.$rt->id)
  
  ->with('request')->begin()
    ->isParameter('module', 'io_doctrine_menu')
    ->isParameter('action', 'reorder')
  ->end()
  
  ->with('response')->begin()
    ->isStatusCode(200)
    ->checkElement('h1', '/Reorder Menu "Root li"/')
  ->end()
;
