ioDoctrineMenuPlugin
====================

Adds a data persistence layer to `ioMenuPlugin`. Create simple object-oriented
menus and then easily persist them to and retrieve them from the database.

This plugin also comes with an admin module to edit the menu items. The
admin module is complete with draggable ordering of the menu items.

The majority of the documentation can be found on the `ioMenuItemPlugin`
[Menu Reference Manual](http://github.com/weaverryan/ioMenuPlugin/tree/master/docs/).

Introduction
------------

The core purpose of this plugin is to provide a persistence layer to the
`ioMenuItem` objects from the `ioMenuPlugin`.

### Persisting menu items

First, let's create a menu item and persist it to the database.

    $menu = new ioMenuItem('root', '@homepage');
    $menu->addChild('Sympal', 'http://www.sympalphp.org');
    $menu->addChild('Account', '@account')
      ->requiresAuth(true)
      ->setCredentials('ManageAccount');

    // persist the menu to the database
    Doctrine_Core::getTable('ioDoctrineMenuItem')->persist($menu);

There is now a root menu item called `root` in the `ioDoctrineMenuItem`
model with two children. This model uses Doctrine's nested set.

### Retrieving menu items

Next, easily retrieve the menu item from the database. The resulting
`$menu` object is alightweight, easy-to-use `ioMenuItem` menu tree.

    $menu = Doctrine_Core::getTable('ioDoctrineMenuItem')
      ->fetchMenu('root');

At this point, you can make changes to your `$menu` variable and then
re-persist to the database. The plugin will update any changes to the
nested set in the database.

### Caching

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

Installation
------------

This plugin requires the ioMenuPlugin.

### With git

    git submodule add git://github.com/weaverryan/ioDoctrineMenuPlugin.git plugins/ioDoctrineMenuPlugin
    git submodule add git://github.com/weaverryan/ioMenuPlugin.git plugins/ioMenuPlugin
    git submodule init
    git submodule update

### With subversion

    svn propedit svn:externals plugins

In the editor that's displayed, add the following entry and then save

    ioDoctrineMenuPlugin https://svn.github.com/weaverryan/ioDoctrineMenuPlugin.git
    ioMenuPlugin https://svn.github.com/weaverryan/ioMenuPlugin.git

Finally, update:

    svn up

# Setup

In your `config/ProjectConfiguration.class.php` file, make sure you have
the plugin enabled.

    $this->enablePlugins('ioMenuPlugin', 'ioDoctrineMenuPlugin');

Configuration
-------------

All configuration for this plugin can be found in the `config/app.yml`
file packaged with the plugin.

The most important configuration options are those related to i18n. To
enable internationalization, be sure to set the following in your `app.yml`
file:

    all:
      doctrine_menu:
        use_i18n: true
        i18n_cultures:
          en:   English
          fr:   Français

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
