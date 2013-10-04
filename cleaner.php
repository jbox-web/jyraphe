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

require_once(JYRAPHE_ROOT . 'libjyraphe/hConfig.php');
require_once(JYRAPHE_ROOT . 'libjyraphe/hClean.php');

// Initialising global hConfig.
hConfig::create();

// Loading cleaner config.
$cfg = parse_ini_file('config.php', true);
$cfg = $cfg['Cleaner'];

// If the cleaner is disabled, redirect to a 404 page.
if(!$cfg['enabled']) {
  echo "Error: function is disabled.";
  exit;
}

// Doing the hard work.
if ($cfg['allow_ips'] != "" ) {
  $cleaner_ip = $cfg['allow_ips'];
  $cleaner_ip = explode(',', $cleaner_ip);
} else {
  $cleaner_ip = "127.0.0.1";
}

// If the IP is unauthorised, showing a 404 page (security.)
if (isset($_SERVER['SERVER_ADDR'])
    && !in_array($_SERVER['REMOTE_ADDR'], $cleaner_ip)
    && ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR'])) {
  echo "Error: unauthorised.";
  exit;
} else {
  echo "Cleaning up the files...";
  echo "<br /><br />\n";
  $dir_path = hConfig::getVar('var_root') . "links/";
  $dir = dir($dir_path);

  echo $dir_path;
  echo "<br /><br />\n";

  while($file = $dir->read()) {
    if ($file != '.' && $file != '..') {
      echo $file ."<br />\n";
      $cleaner = new hClean();
      echo $cleaner->clean($file);
    }
  }
  $dir->close();
}

?>
