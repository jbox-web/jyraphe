<?php
/*
*  Jyraphe, your web file repository
*  Copyright (C) 2008  Julien "axolotl" BERNARD <axolotl@magieeternelle.org>
*
*  This program is free software: you can redistribute it and/or modify
*  it under the terms of the GNU Affero General Public License as
*  published by the Free Software Foundation, either version 3 of the
*  License, or (at your option) any later version.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU Affero General Public License for more details.
*
*  You should have received a copy of the GNU Affero General Public License
*  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once('hException.php');
require_once('hConfig.php');

class hJyraphe {

  const PACKAGE = 'Jyraphe';
  const VERSION = '0.6';

  private $uploadDir;

  /*
  * Array containing a list of functions to be called in various situations.
  */
  protected $hooks;

  public function __construct() {
    hConfig::create('config.php');
    if(file_exists(hConfig::getVar('jyraphe_root') . 'install.php')) {
      throw new hException(_("Installer script still present. " .
                             "Please make sure to delete the installer " .
                             "script \"install.php\""));
    }
    $uploadDir = hConfig::getVar('var_root');
    if($uploadDir[strlen($uploadDir) - 1] != "/") {
      $uploadDir .= "/";
    }
    $this->uploadDir = $uploadDir;
    // Put the default hooks here (if any).
    $this->hooks = array ();
  }


  /*
  * Function that adds hooks and affects them to a category.
  * @param $hook is the name of the hook.
  * @param $type is the type of the hook.
  * @return nothing. Throws hEsceptions when errors occur.
  */
  public function add_hook($hook, $type) {
    if(isset($this->hooks[$type]) &&
       is_array($this->hooks[$type]) &&
       !in_array($hook, $this->hooks)) {
      $this->hooks[$type][] = $hook;
    } elseif(!isset($this->hooks[$type])) {
      $this->hooks[$type] = array ($hook);
    } elseif(!is_array($this->hooks[$type])) {
      throw new hException(_('This hook type is not available.'));
    } elseif(in_array($hook, $this->hooks)) {
      throw new hException(_('This hook is already assigned.'));
    } else {
      throw new hException(_('Cannot add this hook.'));
    }
  }


  /*
  * Runs all hooks of a given type (if the type exists.)
  * @param $type is the type of the hook.
  * @return false when the hook does not exist. True otherwise.
  */
  protected function run_hooks($type) {
    // Checking that the provided hook type is valid.
    if(!isset($this->hooks[$type])
       || !is_array($this->hooks[$type])
       || empty($this->hooks[$type])) {
      return false;
    } else {
      foreach($this->hooks[$type] as $hook) {
        call_user_func($hook);
      }
    }
  }


  public function getUploadDir() {
    return $this->uploadDir;
  }

  public function getLinksDir() {
    return $this->uploadDir . 'links/';
  }

  public function getLink($filename) {
    return $this->getLinksDir() . $filename;
  }

  public function getFilesDir() {
    return $this->uploadDir . 'files/';
  }

  public function getFile($filename) {
    return $this->getFilesDir() . $filename;
  }

  public function checkPermissions() {
    if(!is_writable($this->getFilesDir())) {
      throw new hException(_('The files/ directory is not writable!'), hException::PERMISSIONS_ERROR);
    }

    if(!is_writable($this->getLinksDir())) {
      throw new hException(_('The links/ directory is not writable!'), hException::PERMISSIONS_ERROR);
    }
  }

}

?>
