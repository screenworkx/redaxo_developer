<?php

class rex_developer_manager
{
  static public function sync()
  {
    global $REX;
    $page = rex_request('page', 'string');
    $subpage = rex_request('subpage', 'string');
    $function = rex_request('function','string','');
    $save = rex_request('save','string','');

    if ($page == 'import_export')
      rex_extension::register('A1_AFTER_DB_IMPORT', array('rex_developer_manager', 'deleteFiles'));

    if (($page == 'templates' && ((($function=='add' || $function=='edit') && $save=='ja') || $function=='delete'))
      || ($page == 'modules' && ((($function=='add' || $function=='edit') && $save=='1') || $function=='delete'))
      || ($page == 'import_export' && $subpage == 'import')
      || $page == 'developer')
    {
      rex_extension::register('OUTPUT_FILTER_CACHE', array('rex_developer_manager', '_sync'));
    }
    else
    {
      rex_developer_manager::_sync();
    }
  }

  static public function _sync()
  {
    $sync = new rex_developer_synchronizer;
    $sync->syncTemplates();
    $sync->syncModules();
    $sync->syncActions();
  }

  static public function deleteFiles()
  {
    $sync = new rex_developer_synchronizer;
    $sync->deleteTemplateFiles(false);
    $sync->deleteModuleFiles(false);
    $sync->deleteActionFiles(false);
  }
}
