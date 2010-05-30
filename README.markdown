ioDoctrineMenuPlugin
====================

### This plugin is still in development, check back tomorrow :)

Adds a Doctrine menu model with admin area. The menu model, ioDoctrineMenuItem,
is used as a datasource for ioMenuItem objects from the ioMenuPlugin.

Easily retrieve menu trees that are stored in the database:

    $menu = Doctrine_Core::getTable('ioDoctrineMenuItem')->retrieveMenu('root-slug');

Or create menu items and persist them to the database.

    $menu = new ioMenuItem('root', '@homepage');
    $menu->addChild('Sympal', 'http://www.sympalphp.org');
    $menu->addChild('Account', '@account')
      ->requiresAuth(true)
      ->setCredentials('ManageAccount');

    // persist the menu to the database
    Doctrine_Core::getTable('ioDoctrineMenuItem')->persistMenu($menu);
