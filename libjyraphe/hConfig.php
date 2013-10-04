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

/*
* Configuration abstraction class.
* parses an ini file in order to get the relevant values.
*/
class hConfig {
  //! Settings array, contains the system's definitions.
  static public $handle;
  private $settings;
  private $forbiddenFiles = array('.htaccess');
  private $filteredExtensions = array('php', 'php3', 'php4', 'php5');
  private $private_settings;

  /*
  * Class constructor
  * Private - singleton class.
  */
  private function __construct() {
    // Sets the default configuration values.
    $this->initialise();
  }

  static public function create($configFile = '') {
    if(!isset(self::$handle) && !is_object(self::$handle)) {
      self::$handle = new hConfig();
      if($configFile != '') {
        self::$handle->loadConfig($configFile);
      } else {
        self::$handle->loadConfig('config.php');
      }
      self::$handle->refresh();
    }
  }

  /*
  * Initialises the settings array.
  */
  private function initialise() {
    $this->settings = array('var_root' => '',
                            'rewrite' => false,
                            'password' => '',
                            'hash_size' => 4,
                            'endl' => "\n",
                            'time_infinity' => -1,
                            'time_minute' => 60,
                            'time_hour' => 3600,
                            'time_day' => 86400,
                            'time_week' => 604800,
                            'time_month' => 2419200,
                            'jyraphe_root' => '');

    $this->private_settings = array('var_root',
                                    'password',
                                    'rewrite',
                                    'hash_size',
                                    'endl',
                                    'jyraphe_root',
                                    'smtp_host',
                                    'smtp_auth',
                                    'smtp_port',
                                    'smtp_username',
                                    'smtp_password');
  }


  /*
  * Refreshes the settings.
  */
  private function refresh() {
  }


  /*
  * Loads up a user defined configuration file.
  */
  private function loadConfig($file) {
    // Includes the array.
    $cfg = parse_ini_file($file, true);

    // Merges the user-defined array with the settings.
    $this->settings = array_merge($this->settings, $cfg['Core']);
  }


  /*
  * Returns the requested setting.
  */
  public static function getVar($name) {
    if(!isset(self::$handle) && !is_object(self::$handle)) {
      throw new hException(_("Configuration was not initialised."));
    }
    if(!array_key_exists($name, self::$handle->settings)) {
      throw new hException(sprintf(_("The setting '%s' does not exist."), $name));
    } else {
      return self::$handle->settings[$name];
    }
  }


  /*
  * Returns all the configuration as an array.
  * @param $include_private also returns the private configuration
  * members if set to true.
  * @return an associative array of configuration elements.
  */
  public static function getConf($include_private = false) {
    // TODO: filter the returned array to hide some configuration bits.
    if($include_private) {
      return self::$handle->settings;
    } else {
      $public_settings = array();
      // Regenerating an array and extracting the private settings.
      foreach(self::$handle->settings as $setting => $value) {
        if(in_array($setting, self::$handle->private_settings)) {
          continue;
        }
        $public_settings[$setting] = $value;
      }
      return $public_settings;
    }
  }


  public function getForbiddenFiles() {
    return $this->forbiddenFiles;
  }


  public function isForbiddenFile($filename) {
    return in_array($filename, $this->forbiddenFiles);
  }


  public function isValidEmail($strEmailAddress) {
    # Taken from http://code.google.com/p/php-email-address-validation/

    // If magic quotes is "on", email addresses with quote marks will
    // fail validation because of added escape characters. Uncommenting
    // the next three lines will allow for this issue.
    // if (get_magic_quotes_gpc()) {
    //   $strEmailAddress = stripslashes($strEmailAddress);
    // }

    // If email lenght is 0 just pass.
    if (strlen($strEmailAddress) == 0) {
      return true;
    }

    // Check email length - min 3 (a@a), max 256
    if (!$this->check_text_length($strEmailAddress, 3, 256)) {
      return false;
    }

    // Control characters are not allowed
    if (preg_match('/[\x00-\x1F\x7F-\xFF]/', $strEmailAddress)) {
      return false;
    }

    // Split it into sections using last instance of "@"
    $intAtSymbol = strrpos($strEmailAddress, '@');
    if ($intAtSymbol === false) {
      // No "@" symbol in email.
      return false;
    }

    $arrEmailAddress[0] = substr($strEmailAddress, 0, $intAtSymbol);
    $arrEmailAddress[1] = substr($strEmailAddress, $intAtSymbol + 1);

    // Count the "@" symbols. Only one is allowed, except where
    // contained in quote marks in the local part. Quickest way to
    // check this is to remove anything in quotes. We also remove
    // characters escaped with backslash, and the backslash
    // character.
    $arrTempAddress[0] = preg_replace('/\./'
                                     ,''
                                     ,$arrEmailAddress[0]);

    $arrTempAddress[0] = preg_replace('/"[^"]+"/'
                                     ,''
                                     ,$arrTempAddress[0]);

    $arrTempAddress[1] = $arrEmailAddress[1];
    $strTempAddress = $arrTempAddress[0] . $arrTempAddress[1];

    // Then check - should be no "@" symbols.
    if (strrpos($strTempAddress, '@') !== false) {
      // "@" symbol found
      return false;
    }

    // Check local portion
    if (!$this->check_local_portion($arrEmailAddress[0])) {
      return false;
    }

    // Check domain portion
    if (!$this->check_domain_portion($arrEmailAddress[1])) {
      return false;
    }

    // If we're still here, all checks above passed. Email is valid.
    return true;
  }


  /*
  * Checks email section before "@" symbol for validity
  * @param   strLocalPortion     Text to be checked
  * @return  True if local portion is valid, false if not
  */
  protected function check_local_portion($strLocalPortion) {
    // Local portion can only be from 1 to 64 characters, inclusive.
    // Please note that servers are encouraged to accept longer local
    // parts than 64 characters.
    if (!$this->check_text_length($strLocalPortion, 1, 64)) {
      return false;
    }

    // Local portion must be:
    // 1) a dot-atom (strings separated by periods)
    // 2) a quoted string
    // 3) an obsolete format string (combination of the above)
    $arrLocalPortion = explode('.', $strLocalPortion);
    for ($i = 0, $max = sizeof($arrLocalPortion); $i < $max; $i++) {
      if (!preg_match('.^('
                      .    '([A-Za-z0-9!#$%&\'*+/=?^_`{|}~-]'
                      .    '[A-Za-z0-9!#$%&\'*+/=?^_`{|}~-]{0,63})'
                      .'|'
                      .    '("[^\\\"]{0,62}")'
                      .')$.'
                      ,$arrLocalPortion[$i])) {
        return false;
      }
    }
    return true;
  }


  /*
  * Checks email section after "@" symbol for validity
  * @param   strDomainPortion     Text to be checked
  * @return  True if domain portion is valid, false if not
  */
  protected function check_domain_portion($strDomainPortion) {

    // Total domain can only be from 1 to 255 characters, inclusive
    if (!$this->check_text_length($strDomainPortion, 1, 255)) {
      return false;
    }

    // Check if domain is IP, possibly enclosed in square brackets.
    if (preg_match('/^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])'
       .'(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}$/'
       ,$strDomainPortion) ||
        preg_match('/^\[(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])'
       .'(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}\]$/'
       ,$strDomainPortion)) {
        return true;
    } else {
      $arrDomainPortion = explode('.', $strDomainPortion);
      if (sizeof($arrDomainPortion) < 2) {
        return false; // Not enough parts to domain
      }

      for ($i = 0, $max = sizeof($arrDomainPortion); $i < $max; $i++) {
        // Each portion must be between 1 and 63 characters, inclusive
        if (!$this->check_text_length($arrDomainPortion[$i], 1, 63)) {
          return false;
        }
        if (!preg_match('/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|'
             .'([A-Za-z0-9]+))$/', $arrDomainPortion[$i])) {
          return false;
        }

        if ($i == $max - 1) { // TLD cannot be only numbers
          if (strlen(preg_replace('/[0-9]/', '', $arrDomainPortion[$i])) <= 0) {
            return false;
          }
        }
      }
    }
    return true;
  }


  /*
  * Check given text length is between defined bounds
  * @param   strText     Text to be checked
  * @param   intMinimum  Minimum acceptable length
  * @param   intMaximum  Maximum acceptable length
  * @return  True if string is within bounds (inclusive), false if not
  */
  protected function check_text_length($strText, $intMinimum, $intMaximum) {
    // Minimum and maximum are both inclusive
    $intTextLength = strlen($strText);
    if (($intTextLength < $intMinimum) || ($intTextLength > $intMaximum)) {
      return false;
    } else {
      return true;
    }
  }


  public function addForbiddenFiles(array $forbiddenFiles) {
    if($forbiddenFiles != null) {
      array_merge($this->forbiddenFiles, $forbiddenFiles);
    }
  }

  public function getFilteredExtensions() {
    return $this->filteredExtensions;
  }

  public function isFilteredExtension($extension) {
    return in_array($extension, $this->filteredExtensions);
  }

  public function addFiteredExtensions(array $filteredExtensions) {
    if($filteredExtensions != null) {
      array_merge($this->filteredExtensions, $filteredExtensions);
    }
  }

}

?>
