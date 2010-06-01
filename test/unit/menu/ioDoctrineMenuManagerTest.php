<?php

require_once dirname(__FILE__).'/../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(11);

$manager = new ioDoctrineMenuManager();

$cacheDir = '/tmp/doctrine_menu';
sfToolkit::clearDirectory($cacheDir);
$cache = new sfFileCache(array('cache_dir' => $cacheDir));

$t->info('1 - Test the basic getters and setters.');
  $t->is($manager->getCacheDriver(), null, 'The cache driver is null by default.');
  $manager->setCacheDriver($cache);
  $t->is(get_class($manager->getCacheDriver()), 'sfFileCache', 'The cache driver was set correctly.');

$t->info('2 - Retrieve a menu from the manager');
  $docArr = create_doctrine_test_tree($t);
  $cacheKey = md5('Root li');

  $t->info('  2.1 - Retrieve a menu, no cache is set at first.');
  $menu = $manager->getMenu('Root li');
  $t->is(get_class($menu), 'ioMenuItem', '->getMenu() retrieves the correct ioMenuItem object');
  $t->is($menu->getName(), 'Root li', '->getMenu() retrieves the correct ioMenuItem object');

  $t->info('  2.2 - Check that the cache has now been set');
  $t->is($cache->has($cacheKey), true, 'The cache was set to the cache key.');
  $cached = unserialize($manager->getCache($cacheKey));
  $t->is($cached['name'], 'Root li', 'The proper cache was set');

  $t->info('  2.3 - Mutate the cache and see that fetching the menu retrieves from the cache.');
  $cached['route'] = 'http://www.sympalphp.org';
  $cache->set($cacheKey, serialize($cached));

  $menu = $manager->getMenu('Root li');
  $t->is($menu->getRoute(), 'http://www.sympalphp.org', 'The manager correctly retrieves from the cache.');

  $t->info('  2.4 - Retrieve a non-existent menu.');
  $t->is($manager->getMenu('fake'), null, '->getMenu() with a non-existent menu name returns null.');

$t->info('3 - Test ->clearCache() method.');
  $cache->set('cache1', 'cache1');
  $cache->set('cache2', 'cache3');
  $t->is($cache->has($cacheKey), true, 'The menu cache exists to start.');
  $manager->clearCache($docArr['rt']);
  $t->is($cache->has($cacheKey), false, '->clearCache() removes the cache entry.');

  $manager->clearCache();
  $t->is($cache->has('cache1') || $cache->has('cache1'), false, '->clearCache() with no argument cleared all of the cache.');