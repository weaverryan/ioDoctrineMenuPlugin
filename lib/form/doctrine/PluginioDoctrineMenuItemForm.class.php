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
    $this->widgetSchema['attributes']->setAttribute('size', 72);

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


    /*
     * Add the parent_id parent node functionality if this is a new item
     */
    if ($this->isNew())
    {
      $q = $this->getObject()->getTable()->getParentIdQuery();
      if (!$this->getObject()->isNew())
      {
        $q->andWhere('m.id != ?', $this->object->id);
      }

      $model = get_class($this->getObject());
      // if new, blank means new root, else is just blank and we require the field
      $this->widgetSchema['parent_id'] = new sfWidgetFormDoctrineChoice(array(
        'model' => $model,
        'add_empty' => 'New root menu item',
        'order_by' => array('root_id, lft', ''),
        'query' => $q,
        'method' => 'getIndentedName',
        ));
      $this->validatorSchema['parent_id'] = new sfValidatorDoctrineChoice(array(
        'required' => false,
        'model' => $model,
        ));
      $this->setDefault('parent_id', $this->object->getParentId());
      $this->widgetSchema->setLabel('parent_id', 'Child of');
      $this->widgetSchema->setHelp('parent_id', 'Choose the parent menu item for this new menu or create it as a root. After saving, you can continue to reorder the menu item.');
    }
  }

  /**
   * Overridden to move or position the menu item if necessary
   */
  protected function doSave($con = null)
  {
    parent::doSave($con);

    $node = $this->object->getNode();
    $parentId = $this->getValue('parent_id');

    if ($this->isNew())
    {
      if (!$parentId)
      {
        // we're new and we have no parent id, so save as root
        $this->getObject()->getTable()->getTree()->createRoot($this->getObject()); //calls $this->object->save internally
      }
      else
      {
        //form validation ensures an existing ID for $this->parentId
        $parent = $this->object->getTable()->find($parentId);
        $node->insertAsLastChildOf($parent); //calls $this->object->save internally
      }
    }
  }
}
