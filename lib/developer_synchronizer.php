<?php

class rex_developer_synchronizer
{
  private
    $dir,
    $templatePath,
    $modulePath,
    $actionPath,
    $templatePattern,
    $moduleInputPattern,
    $moduleOutputPattern,
    $actionPreviewPattern,
    $actionPresavePattern,
    $actionPostsavePattern;

  public function __construct()
  {
    $this->dir = rex_path::addonData('developer');
    $this->templatePath = $this->dir .'templates/';
    $this->modulePath = $this->dir .'modules/';
    $this->actionPath = $this->dir .'actions/';
    $this->templatePattern = $this->templatePath .'*.*.php';
    $this->moduleInputPattern = $this->modulePath .'*.input.*.php';
    $this->moduleOutputPattern = $this->modulePath .'*.output.*.php';
    $this->actionPreviewPattern = $this->actionPath .'*.preview.*.php';
    $this->actionPresavePattern = $this->actionPath .'*.presave.*.php';
    $this->actionPostsavePattern = $this->actionPath .'*.postsave.*.php';
  }

  public function deleteTemplateFiles($deleteDir = false)
  {
    $files = $this->_getFiles($this->templatePattern);
    array_map('unlink', $files);
    if ($deleteDir)
    {
      $this->_deleteDir($this->templatePath);
    }
  }

  public function deleteModuleFiles($deleteDir = false)
  {
    $inputFiles = $this->_getFiles($this->moduleInputPattern);
    $outputFiles = $this->_getFiles($this->moduleOutputPattern);
    array_map('unlink', $inputFiles);
    array_map('unlink', $outputFiles);
    if ($deleteDir)
    {
      $this->_deleteDir($this->modulePath);
    }
  }

  public function deleteActionFiles($deleteDir = false)
  {
    $previewFiles = $this->_getFiles($this->actionPreviewPattern);
    $presaveFiles = $this->_getFiles($this->actionPresavePattern);
    $postsaveFiles = $this->_getFiles($this->actionPostsavePattern);
    array_map('unlink', $previewFiles);
    array_map('unlink', $presaveFiles);
    array_map('unlink', $postsaveFiles);
    if ($deleteDir)
    {
      $this->_deleteDir($this->actionPath);
    }
  }

  public function syncTemplates()
  {
    rex_dir::create($this->templatePath);
    $files = $this->_getFiles($this->templatePattern);
    $sql = rex_sql::factory();
    //$sql->debugsql = true;
    $sql->setQuery('SELECT id, name, content, updatedate FROM '. rex::getTable('template'));
    $rows = $sql->getRows();
    for($i = 0; $i < $rows; ++$i)
    {
      $id = $sql->getValue('id');
      $name = $sql->getValue('name');
      $dbUpdated = max(1, $sql->getValue('updatedate'));

      $file = isset($files[$id]) ? $files[$id] : null;
      $newFile = $this->templatePath . $this->_getFilename($name .'.'. $id .'.php');
      list($newUpdatedate, $newContent) = $this->_syncFile($file, $newFile, $dbUpdated, $sql->getValue('content'));

      if ($newUpdatedate)
        $this->_updateTemplateInDB($id, $newUpdatedate, $newContent);

      unset($files[$id]);
      $sql->next();
    }
    array_map('unlink', $files);
  }

  public function syncModules()
  {
    rex_dir::create($this->modulePath);
    $inputFiles = $this->_getFiles($this->moduleInputPattern);
    $outputFiles = $this->_getFiles($this->moduleOutputPattern);
    $sql = rex_sql::factory();
    //$sql->debugsql = true;
    $sql->setQuery('SELECT id, name, input, output, updatedate FROM '. rex::getTable('module'));
    $rows = $sql->getRows();
    for($i = 0; $i < $rows; ++$i)
    {
      $id = $sql->getValue('id');
      $name = $sql->getValue('name');
      $dbUpdated = max(1, $sql->getValue('updatedate'));

      $file = isset($inputFiles[$id]) ? $inputFiles[$id] : null;
      $newFile = $this->modulePath . $this->_getFilename($name .'.input.'. $id .'.php');
      list($newUpdatedate1, $newInput) = $this->_syncFile($file, $newFile, $dbUpdated, $sql->getValue('input'));

      $file = isset($outputFiles[$id]) ? $outputFiles[$id] : null;
      $newFile = $this->modulePath . $this->_getFilename($name .'.output.'. $id .'.php');
      list($newUpdatedate2, $newOutput) = $this->_syncFile($file, $newFile, $dbUpdated, $sql->getValue('output'));

      $newUpdatedate = max($newUpdatedate1, $newUpdatedate2);
      if ($newUpdatedate)
        $this->_updateModuleInDB($id, $newUpdatedate, $newInput, $newOutput);

      unset($inputFiles[$id]);
      unset($outputFiles[$id]);
      $sql->next();
    }
    array_map('unlink', $inputFiles);
    array_map('unlink', $outputFiles);
  }

  public function syncActions()
  {
    rex_dir::create($this->actionPath);
    $previewFiles = $this->_getFiles($this->actionPreviewPattern);
    $presaveFiles = $this->_getFiles($this->actionPresavePattern);
    $postsaveFiles = $this->_getFiles($this->actionPostsavePattern);
    $sql = rex_sql::factory();
    //$sql->debugsql = true;
    $sql->setQuery('SELECT id, name, preview, presave, postsave, updatedate FROM '. rex::getTable('action'));
    $rows = $sql->getRows();
    for($i = 0; $i < $rows; ++$i)
    {
      $id = $sql->getValue('id');
      $name = $sql->getValue('name');
      $dbUpdated = max(1, $sql->getValue('updatedate'));

      $file = isset($previewFiles[$id]) ? $previewFiles[$id] : null;
      $newFile = $this->actionPath . $this->_getFilename($name .'.preview.'. $id .'.php');
      list($newUpdatedate1, $newPreview) = $this->_syncFile($file, $newFile, $dbUpdated, $sql->getValue('preview'));

      $file = isset($presaveFiles[$id]) ? $presaveFiles[$id] : null;
      $newFile = $this->actionPath . $this->_getFilename($name .'.presave.'. $id .'.php');
      list($newUpdatedate2, $newPresave) = $this->_syncFile($file, $newFile, $dbUpdated, $sql->getValue('presave'));

      $file = isset($postsaveFiles[$id]) ? $postsaveFiles[$id] : null;
      $newFile = $this->actionPath . $this->_getFilename($name .'.postsave.'. $id .'.php');
      list($newUpdatedate3, $newPostsave) = $this->_syncFile($file, $newFile, $dbUpdated, $sql->getValue('postsave'));

      $newUpdatedate = max($newUpdatedate1, $newUpdatedate2, $newUpdatedate3);
      if ($newUpdatedate)
        $this->_updateActionInDB($id, $newUpdatedate, $newPreview, $newPresave, $newPostsave);

      unset($previewFiles[$id]);
      unset($presaveFiles[$id]);
      unset($postsaveFiles[$id]);
      $sql->next();
    }
    array_map('unlink', $previewFiles);
    array_map('unlink', $presaveFiles);
    array_map('unlink', $postsaveFiles);
  }

  private function _syncFile($file, $newFile, $dbUpdated, $content)
  {
    global $REX;
    $fileUpdated = file_exists($file) ? filemtime($file) : 0;
    if ($fileUpdated < $dbUpdated)
    {
      $nameChanged = false;
      if ($newFile != $file)
      {
        @unlink($file);
        $nameChanged = true;
      }
      if ($nameChanged || !file_exists($newFile) || rex_file::get($newFile) !== $content)
      {
        rex_file::put($newFile, $content);
        return array(filemtime($newFile), null);
      }
    }
    elseif ($fileUpdated > $dbUpdated)
    {
      return array($fileUpdated, addslashes(rex_file::get($file)));
    }
  }

  private function _updateTemplateInDB($id, $updatedate, $content = null)
  {
    $template = new rex_template($id);
    $template->deleteCache();
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('template'));
    $sql->setWhere(array('id' => $id));
    if ($content !== null)
      $sql->setValue('content', $content);
    $sql->setValue('updatedate', $updatedate);
    $sql->setValue('updateuser',  rex::getUser()->getValue('login'));
    return $sql->update();
  }

  private function _updateModuleInDB($id, $updatedate, $input = null, $output = null)
  {
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('module'));
    $sql->setWhere(array('id' => $id));
    if ($input !== null)
      $sql->setValue('input', $input);
    if ($output !== null)
      $sql->setValue('output', $output);
    $sql->setValue('updatedate', $updatedate);
    $sql->setValue('updateuser',  rex::getUser()->getValue('login'));
    $success = $sql->update();
    if ($input !== null || $output !== null)
    {
      $sql->setQuery('
        SELECT     DISTINCT(article.id)
        FROM       '. rex::getTable('article') .' article
        LEFT JOIN  '. rex::getTable('article_slice') .' slice
        ON         article.id = slice.article_id
        WHERE      slice.modultyp_id = ?
      ', array($id));
      $rows = $sql->getRows();
      for ($i = 0; $i < $rows; ++$i)
      {
        rex_article_cache::delete($sql->getValue('article.id'));
        $sql->next();
      }
    }
    return $success;
  }

  private function _updateActionInDB($id, $updatedate, $preview = null, $presave = null, $postsave = null)
  {
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('action'));
    $sql->setWhere(array('id' => $id));
    if ($preview !== null)
      $sql->setValue('preview', $preview);
    if ($presave !== null)
      $sql->setValue('presave', $presave);
    if ($postsave !== null)
      $sql->setValue('postsave', $postsave);
    $sql->setValue('updatedate', $updatedate);
    $sql->setValue('updateuser',  rex::getUser()->getValue('login'));
    return $sql->update();
  }

  private function _getFiles($pattern)
  {
    $glob = glob($pattern);
    $files = array();
    if (is_array($glob))
    {
      foreach($glob as $file)
      {
        $filename = basename($file);
        $parts = explode('.', basename($file));
        if (isset($parts[count($parts) - 2]))
        {
          $id = (int) $parts[count($parts) - 2];
          if ($id)
            $files[$id] = $file;
        }
      }
    }
    return $files;
  }

  private function _getFilename($filename)
  {
    $search = explode('|', rex_i18n::msg('special_chars'));
    $replace = explode('|', rex_i18n::msg('special_chars_rewrite'));
    $filename = str_replace($search, $replace, $filename);
    $filename = strtolower($filename);
    $filename = preg_replace('/[^a-zA-Z0-9.\-\+]/', '_', $filename);
    return $filename;
  }

  private function _deleteDir($dir)
  {
    $glob = glob($dir .'*');
    if (!is_array($glob) || empty($glob))
    {
      rex_dir::delete($dir);
    }
  }
}
