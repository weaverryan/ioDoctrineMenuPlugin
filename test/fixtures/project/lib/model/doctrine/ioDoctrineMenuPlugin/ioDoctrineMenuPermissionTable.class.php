<?php


class ioDoctrineMenuPermissionTable extends PluginioDoctrineMenuPermissionTable
{
    
    public static function getInstance()
    {
        return Doctrine_Core::getTable('ioDoctrineMenuPermission');
    }
}