<?php

/**
 * PluginioDoctrineMenuItem form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage form
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginioDoctrineMenuItemForm extends BaseioDoctrineMenuItemForm
{
  public function setup()
  {
    parent::setup();

    $validFields = array(
      'name',
      'route',
      'attributes',
      'requires_auth',
      'requires_no_auth',
      'permissions_list',
    );

    // if not i18n, add the label field as legit
    if (!$this->getObject()->getTable()->isI18n())
    {
      $validFields[] = 'label';
    }
    $this->useFields($validFields);

    // setup some labels and helps
    $this->widgetSchema->setHelp('name', 'Used to identify this menu. Use label to change the label for this menu item.');

    $this->widgetSchema->setLabel('attributes', 'HTML attributes');
    $this->widgetSchema->setHelp('attributes', '(e.g. title="my menu" class="homepage"');

    $this->widgetSchema->setLabel('route', 'Route/Url');
    $this->widgetSchema->setHelp('route', '(e.g. @homepage or http://www.google.com');

    $this->widgetSchema->setLabel('requires_auth', 'Require auth?');
    $this->widgetSchema->setHelp('requires_auth', 'If checked, this menu item will not show unless the user is authenticated');
    $this->widgetSchema->setLabel('requires_no_auth', 'Require no auth?');
    $this->widgetSchema->setHelp('requires_no_auth', 'If checked, this menu item will not show unless the user is NOT authenticated');

    $this->widgetSchema->setHelp('permissions_list', 'The menu item will not show unless the user has all of the selected permissions.');

    // add the i18n label stuff
    if ($this->getObject()->getTable()->isI18n())
    {
      $cultures = sfConfig::get('app_doctrine_menu_i18n_cultures', array());
      if (isset($cultures[0]))
      {
        throw new sfException('Invalid i18n_cultures format in app.yml. Use the format:
        i18n_cultures:
          en:   English
          fr:   Français');
      }

      $this->embedI18n(array_keys($cultures));
      foreach ($cultures as $culture => $name)
      {
        $this->widgetSchema->setLabel($culture, $name);
      }
    }
  }
}
