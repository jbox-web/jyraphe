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
require(JYRAPHE_ROOT . 'libjyraphe/hFunctions.php');


hConfig::create();

if (in_array($_SERVER['REQUEST_METHOD'], array('POST', 'PUT'))) {
  if (empty($_POST) && empty($_FILES)) {

    // Get maximum size and meassurement unit
    $max_str = ini_get('post_max_size');
    $max = ini_get('post_max_size');
    $unit = substr($max, -1);
    if (!is_numeric($unit)) {
      $max = substr($max, 0, -1);
    }

    // Convert to bytes
    switch (strtoupper($unit)) {
      case 'G':
        $max *= 1024;
      case 'M':
        $max *= 1024;
      case 'K':
        $max *= 1024;
    }

    try {
      // Assert the content length is within limits
      $length = $_SERVER['CONTENT_LENGTH'];
      if ($max < $length) {
        throw new hException('Error : maximum content length size (' . $max_str . ') exceeded (filesize : ' . humanReadableFilesize($length) . '), exiting...');
      }
    }

    catch(hException $e) {
      if(showHtml()) {
        echo '<div class="message error"><p>';
        echo $e->getMessage();
        echo '</p></div>';
      } else {
        echo $e->getMessage() . hConfig::getVar('endl') . hConfig::getVar('endl');
      }
    }

  } else {

    try {

      $uploader = new hUpload();

      $link = "";
      $web_root = $cfg['web_root'];

      error_log(var_export($_REQUEST, true));

      if (!empty($_POST['options'])) {
        $options = $_POST['options'];
      } else {
        $options = array(
                    'key'      => '',
                    'email'    => '',
                    'duration' => '-1',
                  );
      }

      // Checks if a file is being uploaded.
      if($uploader->has_request()) {
        $link = $uploader->upload($_FILES['file'], $options);
      }

      // Shows up the download link if a file was uploaded.
      // Why the hell would a link be within a text box???
      if($link != "") {
        if(showHtml()) {
          echo '<div class="message info">';
          echo '<p>' . _('File uploaded! Click on the following link to get it :') . '</p>';
          echo '<form>';
          echo '<input type="text" id="url" name="url" readonly="true" value="' . $web_root . 'file-' . $link . '" style="width: 98%; " onclick="javascript:this.focus(); this.select();"/>';
          echo '<br /><br /><a href="' . $web_root . 'file-' . $link . '">' . $web_root . 'file-' . $link . '</a>';
          echo '</form>';

          if($options['duration'] != hDuration::INFINITY) {
            echo '<br />' . _('This file is valid until the following date :') . '<br /><strong>' . strftime('%c' ,time() + $options['duration']) . '</strong>';
          }

          echo '</div>';
        } else {

          echo $web_root . 'file-' . $link . hConfig::getVar('endl') . hConfig::getVar('endl');

          if($options['duration'] != hDuration::INFINITY) {
            echo _('This file is valid until the following date : ') . strftime('%c' ,time() + $options['duration']) . hConfig::getVar('endl') . hConfig::getVar('endl');
          }
        }
      }
    }

    catch(hException $e) {
      if(showHtml()) {
        echo '<div class="message error"><p>';
        echo $e->getMessage();
        echo '</p></div>';
      } else {
        echo $e->getMessage() . hConfig::getVar('endl') . hConfig::getVar('endl');
      }
    }

  }
}

?>
