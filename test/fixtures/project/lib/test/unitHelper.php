<?php
/**
 * Utility class for helper unit test functions
 */

$menuItemHelper = dirname(__FILE__).'/../../plugins/ioMenuPlugin/test/fixtures/project/lib/test/unitHelper.php';
if (!file_exists($menuItemHelper))
{
  throw new Exception('Cannot find ioMenuPlugin unit helper. Are submodules updated?');
}
require_once ($menuItemHelper);


// clears the menu database and then creates a test tree
function create_doctrine_test_tree(lime_test $t)
{
  $t->info('### Creating test tree.');

  // create the root
  $rt = create_root('rt');

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

// prints the test doctrine tree to the view
function print_doctrine_test_tree(lime_test $t)
{
  $t->info('      Menu Structure   ');
  $t->info('               rt      ');
  $t->info('             /    \    ');
  $t->info('          pt1      pt2 ');
  $t->info('           |           ');
  $t->info('          ch1          ');
}

// creates a root menu entry and optionally clears all of the menu records
function create_root($name, $clearData = true)
{
  if ($clearData)
  {
    Doctrine_Core::getTable('ioDoctrineMenuItem')->createQuery()->delete()->execute();
  }

  $rt = new ioDoctrineMenuItem();
  $rt->name = 'rt';
  $rt->save();
  Doctrine_Core::getTable('ioDoctrineMenuItem')->getTree()->createRoot($rt);

  return $rt;
}