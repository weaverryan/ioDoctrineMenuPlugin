<?php

require_once dirname(__FILE__).'/../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(2);

$t->info('1 - Test ioDoctrineMenuToolkit::arrayReindex');
   $arr = array(
     array(
      'children' => array(
         2 => 'foo',
         0 => 'bar',
         1 => 'baz'
       )
     ),
     'not_array',
     array(
       'not_children' => array()
     )
   );

  $result = ioDoctrineMenuToolkit::arrayReindex($arr);
  $expected = array(
    array(
      'children' => array(
        0 => 'foo',
        1 => 'bar',
        2 => 'baz',
      ),
    ),
    'not_array',
    array(
     'not_children' => array()
    )
  );
  $t->is($result, $expected, 'The keys on the array are reset and reindexed correctly.');

$t->info('2 - Test ioDoctrineMenuToolkit::nestify()');

  $arr = array(
    array(
      'level' => 0,
      'name'  => 'rt',
    ),
    array(
      'level' => 1,
      'name'  => 'pt1',
    ),
    array(
      'level' => 2,
      'name'  => 'ch1',
    ),
    array(
      'level' => 2,
      'name'  => 'ch2',
    ),
    array(
      'level' => 2,
      'name'  => 'ch3',
    ),
    array(
      'level' => 1,
      'name'  => 'pt2',
    ),
    array(
      'level' => 2,
      'name'  => 'ch4',
    ),
    array(
      'level' => 3,
      'name'  => 'gc1',
    ),
  );

  $expected = array(
    array(
      'level' => 0,
      'name'  => 'rt',
      'children' => array(
        array(
          'level' => 1,
          'name'  => 'pt1',
          'children' => array(
            array(
              'level' => 2,
              'name'  => 'ch1',
            ),
            array(
              'level' => 2,
              'name'  => 'ch2',
            ),
            array(
              'level' => 2,
              'name'  => 'ch3',
            ),
          )
        ),
        array(
          'level' => 1,
          'name'  => 'pt2',
          'children' => array(
            array(
              'level' => 2,
              'name'  => 'ch4',
              'children' => array(
                array(
                  'level' => 3,
                  'name'  => 'gc1',
                ),
              )
            ),
          ),
        ),
      )
    ),
  );

  $t->is(ioDoctrineMenuToolkit::nestify($arr), $expected, 'ioDoctrineMenuToolkit::nestify() correctly nests the flat array.');