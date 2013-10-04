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

require_once('hJyraphe.php');
require_once('hDuration.php');
require_once('hSystem.php');
require_once('hConfig.php');

class hClean extends hJyraphe {
  public function clean($link) {
    hConfig::create(); // Makes sure hConfig is loaded.

    $file = $this->getLink($link);
    $content = parse_ini_file($file);

    $name = $content['name'];
    $time = $content['time'];

    if ($time != hDuration::INFINITY) {
      if (!file_exists($this->getFile($name))) {
        unlink($file);
        echo "we have deleted " . $file . "\n";
      } else {
        if (time() > $time) {
          unlink($file);
          echo "we have deleted " . $file . "\n";
        }
      }
    }
  }
}
?>
