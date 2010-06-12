ioDoctrineMenuPlugin
====================

The majority of the documentation can be found on the `ioMenuItemPlugin`
[http://github.com/weaverryan/ioMenuPlugin/tree/master/docs/](here).

Quick documentation
-------------------

Adds a data persistence layer to `ioMenuPlugin`. Create simple object-oriented
menus and then easily persist them to and retrieve them from the database.

First, create a menu item and persist it to the database.

    $menu = new ioMenuItem('root', '@homepage');
    $menu->addChild('Sympal', 'http://www.sympalphp.org');
    $menu->addChild('Account', '@account')
      ->requiresAuth(true)
      ->setCredentials('ManageAccount');

    // persist the menu to the database
    Doctrine_Core::getTable('ioDoctrineMenuItem')->persist($menu);

Next, aasily retrieve the menu item from the database:

    $menu = Doctrine_Core::getTable('ioDoctrineMenuItem')
      ->fetchMenu('root');

Additionally, caching of the doctrine menu items is automatically handled
if you retrieve the menu items from the menu manager. For example, from
the actions:

    public function executeIndex(sfWebRequest $request)
    {
      $this->menu = $this->getDoctrineMenu('admin');
    }

You may also retrieve menus in the view, which also enables the automatic
caching:

    <?php use_helper('DoctrineMenu') ?>
    <?php $menu = get_doctrine_menu('admin') ?>

Care to Contribute?
-------------------

Please clone and improve this plugin! This plugin is by the community and
for the community and I hope it can be final solution for handling menus.

If you have any ideas, notice any bugs, or have any ideas, you can reach
me at ryan [at] thatsquality.com.

A bug tracker is available at
[http://redmine.sympalphp.org/projects/io-doctrine-menu](http://redmine.sympalphp.org/projects/io-doctrine-menu)

This plugin was taken from [sympal CMF](http://www.sympalphp.org) and was
developed by both Ryan Weaver and Jon Wage.
