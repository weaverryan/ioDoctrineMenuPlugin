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


    // add the parent_id positioning function
    $q = $this->getObject()->getTable()->getParentIdQuery();
    if (!$this->getObject()->isNew())
    {
      $q->andWhere('m.id != ?', $this->object->id);
    }

    $this->widgetSchema['parent_id'] = new sfWidgetFormDoctrineChoice(array(
      'model' => 'ioDoctrineMenuItem',
      'add_empty' => '',
      'order_by' => array('root_id, lft', ''),
      'query' => $q,
      'method' => 'getIndentedName'
      ));
    $this->validatorSchema['parent_id'] = new sfValidatorDoctrineChoice(array(
      'required' => true,
      'model' => 'ioDoctrineMenuItem'
      ));
    $this->setDefault('parent_id', $this->object->getParentId());
    $this->widgetSchema->setLabel('parent_id', 'Child of');
  }

  /**
   * Overridden to move or position the menu item if necessary
   */
  protected function doSave($con = null)
  {
    parent::doSave($con);

    $node = $this->object->getNode();
    $parentId = $this->getValue('parent_id');

    if ($parentId != $this->object->getParentId() || !$node->isValidNode())
    {
      //form validation ensures an existing ID for $this->parentId
      $parent = $this->object->getTable()->find($parentId);
      $method = ($node->isValidNode() ? 'move' : 'insert') . 'AsLastChildOf';
      $node->$method($parent); //calls $this->object->save internally
    }
  }
}
