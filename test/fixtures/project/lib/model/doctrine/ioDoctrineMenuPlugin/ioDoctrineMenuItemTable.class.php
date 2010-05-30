<?php


class ioDoctrineMenuItemTable extends PluginioDoctrineMenuItemTable
{
    
    public static function getInstance()
    {
        return Doctrine_Core::getTable('ioDoctrineMenuItem');
    }
}