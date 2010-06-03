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
    ->info('  1.3 - check for the correct nested set javascript urls')
    ->matches('/loadUrl:\ \'\/index\.php\/test\/menu\/reorder\/json\/'.$rt->id.'/')
    ->matches('/saveUrl:\ \'\/index\.php\/test\/menu\/reorder\/save\/'.$rt->id.'/')
  ->end()
;

$browser->info('2 - Check out the json response for the menu')
  ->get('/test/menu/reorder/json/'.$rt->id)

  ->with('request')->begin()
    ->isParameter('module', 'io_doctrine_menu')
    ->isParameter('action', 'json')
    ->isParameter('sf_format', 'json')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
  ->end()
;
$response = $browser->getResponse()->getContent();
$json = json_decode($response);
$browser->test()->isnt($json, null, 'The response returns a valid json object');

$browser->info('3 - Test the save method')
  ->call('/test/menu/reorder/save/'.$rt->id, 'post', array(
    'nested-sortable-widget' => get_nested_set_save_array($arr)
  ))

  ->with('request')->begin()
    ->isParameter('module', 'io_doctrine_menu')
    ->isParameter('action', 'saveJson')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
  ->end()
;

$browser->info('  3.1 - Check the menu to see that it was updated');
root_sanity_check($browser->test(), $rt);
check_child_ordering($browser->test(), $rt, array(), array('Parent 2', 'Parent 1'));
check_child_ordering($browser->test(), $rt, array(0), array('Child 4'));
check_child_ordering($browser->test(), $rt, array(1), array('Child 3', 'Child 1', 'Child 2'));