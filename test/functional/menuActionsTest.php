<?php

require_once dirname(__FILE__).'/../bootstrap/functional.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$browser = new sfTestFunctional(new sfBrowser());

create_doctrine_test_tree($browser->test());
$browser->info('1 - Goto a url that uses ioDoctrineMenuActions->getMenu()')
  ->get('/actions/get-menu')

  ->with('request')->begin()
    ->isParameter('module', 'test')
    ->isParameter('action', 'getMenu')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
    ->info('  1.1 - Check for the root ul and its 7 descendants.')
    ->checkElement('ul.root', true)
    ->checkElement('ul.root li', 7)
  ->end()
;