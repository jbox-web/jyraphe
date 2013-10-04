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

class hSystem {

  const NL = "\n";

  /*
  * Gets the extension of a filename.
  * @param $filename the filename
  * @returns the extension, or the empty string if there is no extension
  */
  public static function getExtension($filename) {
    $pos = strrpos($filename, '.');
    if($pos === false) {
      return '';
    }
    return substr($filename, $pos + 1);
  }


  /*
  * Detects if a given filename is present in a directory and find an alternate filename.
  * @param $name the initial filename
  * @param $dir the directory to explore (finishing with a '/')
  * @returns an alternate filename, possibly the initial filename
  */
  public static function detectCollision($name, $dir) {
    if(!file_exists($dir . $name)) {
      return $name;
    }

    $dot = strpos($name, '.');
    $dot = ($dot === false) ? strlen($name) : $dot;
    $first = substr($name, 0, $dot);
    $second = substr($name, $dot);
    $i = 1;
    do {
      $new_name = $first . '-' . $i . $second;
      $i++;
    } while(file_exists($dir . $new_name));

    return $new_name;
  }


  /*
  * Transforms a php.ini string representing a value to an integer.
  * @param $value the value from php.ini
  * @returns an integer for this value
  */
  private static function iniToBytes($value) {
    $modifier = substr($value, -1);
    $bytes = substr($value, 0, -1);
    switch(strtoupper($modifier)) {
    case 'P':
      $bytes *= 1024;
    case 'T':
      $bytes *= 1024;
    case 'G':
      $bytes *= 1024;
    case 'M':
      $bytes *= 1024;
    case 'K':
      $bytes *= 1024;
    default:
      break;
    }
    return $bytes;
  }


  /*
  * Gets the maximum upload size according to php.ini.
  * @returns the maximum upload size
  */
  public static function getMaxUploadSize() {
    return min(self::iniToBytes(ini_get('post_max_size')), self::iniToBytes(ini_get('upload_max_filesize')));
  }


  /*
  * Returns the file's random hash.
  * @param file is the file to be hashed.
  * @oaram nchars is the length of the hash.
  * @return the file's hash.
  */
  public static function jyrapheHash($nchars) {
    $hash = "";
    for($i = 0; $i < $nchars; $i++) {
      $hash .= chr(rand(97,122));
    }
    return $hash;
  }


  /*
  * Tells if a mime-type is viewable in a browser.
  * @param $mime the mime type
  * @returns a boolean telling if a mime type is viewable
  */
  public static function isViewable($mime) {
    if(!empty($mime)) {
      // actually, verify if mime-type is an image or a text
      $viewable = array('image', 'text');
      $decomposed = explode('/', $mime);
      return in_array($decomposed[0], $viewable);
    }
    return false;
  }

}

?>
