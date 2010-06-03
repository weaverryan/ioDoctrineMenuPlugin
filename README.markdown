ioDoctrineMenuPlugin
====================

### This plugins is fully-functional and tested, but is still missing some features.

I'll be releasing a first, finished release before June 10th. The majority
of the documentation can be found on the `ioMenuItemPlugin`
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
      $this->menu = $this->getContext()
        ->getConfiguration()
        ->getPluginConfiguration('ioDoctrineMenuPlugin')
        ->getMenuManager()
        ->getMenu('admin');
    }

Finally, the plugin comes packaged with an admin module that allows for
reordering and sorting of the menus via jQuery's Nested Sortable widget. 

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
