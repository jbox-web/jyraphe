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

define('JYRAPHE_ROOT', dirname(__FILE__) . '/');

// Preparing the configuration.
$cfg = parse_ini_file('config.php', true);
$cfg = $cfg['Interface'];

// Preparing the language settings.
setlocale(LC_ALL, $cfg['lang']);
bindtextdomain($cfg['jyraphe_package'], JYRAPHE_ROOT . 'lib/locale');
textdomain($cfg['jyraphe_package']);

require(JYRAPHE_ROOT . 'libjyraphe/hConfig.php');
require(JYRAPHE_ROOT . 'libjyraphe/hUpload.php');
require(JYRAPHE_ROOT . 'libjyraphe/hDownload.php');

hConfig::create();

if(isset($_GET['h'])) {

  try {
    $download = new hDownload();
    $download->download($_GET['h']);
  }

  catch(hException $e) {
    require(JYRAPHE_ROOT . 'template/header.php');
    echo '<p class="error">';
    echo '<img src="/media/images/error.png"/>&nbsp;';
    echo $e->getMessage();
    echo '</p>';
    if ($e->getCode() == "3" || $e->getCode() == "20") {
      $download->printKeyForm('index.php?h=' . $_GET['h']);
    }
    require(JYRAPHE_ROOT . 'template/footer.php');
  }

} else {

  require(JYRAPHE_ROOT . 'template/header.php');

  try {
    $uploader = new hUpload();

    // Bail out if low on space!
    if(disk_free_space(hConfig::getVar('var_root')) > hSystem::getMaxUploadSize()) {
      // Displays the upload form.
      $uploader->printUploadForm('upload.php', $cfg);
    } else {
      throw new hException(_('The server is low on disk space, please try again later.'));
    }
  }

  catch(hException $e) {
    echo '<p class="error">';
    echo '<img src="/media/images/error.png" />&nbsp;';
    echo $e->getMessage();
    echo '</p>';
  }

  require(JYRAPHE_ROOT . 'template/footer.php');
}

?>
