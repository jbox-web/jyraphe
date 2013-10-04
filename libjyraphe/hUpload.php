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
require_once('hJyraphe.php');
require_once('hSystem.php');
require_once('hDuration.php');
require_once('hConfig.php');
require_once('smtp.php');

class hUpload extends hJyraphe {

  public function __construct($uploadDir = NULL) {
    if($uploadDir == NULL) {
      $uploadDir = hConfig::getVar('var_root');
    }
    parent::__construct($uploadDir);
    // Initialising default hooks types.
    $this->hooks['interface'] = array ();
  }


  /*
  * Checks if there is an upload request pending.
  * @return true if an upload is pending.
  */
  public function has_request() {
    return isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != '';
  }


  /*
  * Handles an uploaded file.
  * @param $file the file struct given by $_FILE[]
  * @param $options the options struct given by the jyraphe form
  * @returns the link name of the uploaded file
  */
  public function upload(array $file, array $options) {

    if(!isset($file['tmp_name']) || $file['tmp_name'] == "") {
      throw hException::getUploadException($file['error']);
    }

    if(hConfig::$handle->isForbiddenFile($file['name'])) {
      throw new hException(_('This file is forbidden for security reasons.'));
    }

    if(isset($options['email_download']) && !hConfig::$handle->isValidEmail($options['email_download'])) {
      throw new hException(_("The email address to receive notifications on download is invalid : '" . $options['email_download'] . "'"));
    }

    if(isset($options['email_upload']) && !hConfig::$handle->isValidEmail($options['email_upload'])) {
      throw new hException(_("The email address to receive notifications on upload is invalid : '" . $options['email_upload'] . "'"));
    }

    if((isset($file['xmlrpc']) && !$file['xmlrpc']) && !is_uploaded_file($file['tmp_name'])) {
      throw hException::getUploadException($file['error']);
    }

    $re_upload = false;

    /* we check if this file is already here */
    $link = hSystem::jyrapheHash(hConfig::getVar('hash_size'));
    if(file_exists($this->getLink($link))) {
      $re_upload = true;
    }

    $mimeType = $file['type'];
    $name = trim($file['name']);

    /* we filter some extensions for security reasons */
    if(hConfig::$handle->isFilteredExtension(hSystem::getExtension($name))) {
      $mimeType = 'text/plain';
    }

    if(!$re_upload) {
      /* we check if there is a file with that name */
      $name = hSystem::detectCollision($name, $this->getFilesDir());
    }

    /* Calculate when the file should be expired. */
    $time = "-1";
    if ($options['duration'] != $time) {
      $time = time();
      $time += $options['duration'];
    }

    /* we move it to the right place and create the link */
    $move_success = false;
    if(!$re_upload) {
      if(isset($file['xmlrpc']) && $file['xmlrpc']) {
        $move_success = rename($file['tmp_name'], $this->getFile($name));
        chmod($this->getFile($name), '777');
      } else {
        $move_success = move_uploaded_file($file['tmp_name'], $this->getFile($name));
      }
    }

    if($move_success || $re_upload) {
      $handle = fopen($this->getlink($link), 'w');
      if ($handle) {
        error_log(var_export($options, true));
        error_log($time);

        fwrite($handle,
               '[Link]' . hConfig::getVar('endl')
               . 'name = ' . $name . hConfig::getVar('endl')
               . 'mimeType = ' . $mimeType . hConfig::getVar('endl')
               . 'fileSize = ' . $file['size'] . hConfig::getVar('endl')
               . 'dlOnce = ' . (isset($options['one_time_download'])? "true" : "false") . hConfig::getVar('endl')
               . 'key = ' . (isset($options['key'])? $options['key'] : "") . hConfig::getVar('endl')
               . 'time = ' . $time . hConfig::getVar('endl')
               . 'email = ' . (isset($options['email_download'])? $options['email_download'] : "") . hConfig::getVar('endl'));
        fclose($handle);

        $cfg = parse_ini_file('config.php', true);
        $cfg = $cfg['Interface'];
        $web_root = $cfg['web_root'];

        if(isset($options['email_upload']) && !empty($options['email_upload']) && hConfig::getVar('smtp_host') != "" && hConfig::getVar('from_email') != "") {
          $subject =  _("[JYRAPHE] ") . $name . _(' has been uploaded');
          $body = _("The file '") . $name . _("' has been uploaded.") . hConfig::getVar('endl') . hConfig::getVar('endl');
          $body .= _("Here's the link to download it : ") . $web_root . 'file-' . $link . hConfig::getVar('endl') . hConfig::getVar('endl');

          if (isset($options['key'])) {
            $body .= _("Here's the password to download it : '") . $options['key'] . "'" . hConfig::getVar('endl') . hConfig::getVar('endl');
          }

          $body .= _("Cheers") . hConfig::getVar('endl');

          try {
            $smtp = new SMTP(hConfig::getVar('smtp_host'), hConfig::getVar('smtp_port'), $_SERVER['SERVER_NAME'], hConfig::getVar('smtp_auth'), hConfig::getVar('smtp_username'), hConfig::getVar('smtp_password'));
            $smtp->dbug = false;
            $smtp->Connect();
            $smtp->Send(array($options['email_upload']),                   // Array of recipient addresses
                        hConfig::getVar('from_email'),                     // SMTP From address
                        array('Subject' => $subject,
                              'To'      => $options['email_upload'],
                              'From'    => hConfig::getVar('from_email')), // The emails headers
                        $body);                                            // The emails body (phwoar...)

          }

          catch (SMTPException $e) {
            echo $e->GetMessage();
          }
        }

        return $link;

      } else {
        throw new hException(_('The file can not be created in the link folder.'));
      }
    } else {
      throw new hException(_('The uploaded file can not be moved into the var folder.'));
    }
  }


  /*
  * Prints the Jyraphe upload form.
  *
  * HTML label tags are in the form:
  * <label for="thing">My thing</label><input id="thing"/>
  * NOT!!!!!
  * <label for="thing">My thing<input id="thing"/></label>
  *
  * Jesus will kill a kitten everytime it is not done properly!
  */
  public function printUploadForm($postUrl, $cfg) {
    ?>

      <div id="jyraphe_upload">
        <form enctype="multipart/form-data" action="<?php echo $postUrl; ?>" method="post">
          <fieldset id="upload_fieldset">

            <legend><?php echo _('Upload a file'); ?></legend>

            <p class="jyraphe_info"><?php printf(_('Maximum file size: %dMB'), hSystem::getMaxUploadSize()/(1024*1024)); ?></p>

            <p><input type="file" id="file" name="file" size="30" /><span id="file_size"></span></p>

            <div id="progress">
              <div class="bar" style="width: 0%;"></div>
              <div class="percentage"></div>
            </div>

            <div id="messages"></div>

            <input type="hidden" name="jyraphe" value="<?php echo parent::VERSION; ?>" />

            <div id="jyraphe_moreoptions" style="display: none;">
              <hr />

              <table>
                <tr>
                  <td class="label"><label for="jyraphe_one_time"><?php echo _('One time download :'); ?></label></td>
                  <td class="content"><input type="checkbox" id="jyraphe_one_time" name="options[one_time_download]" value="ok" /></td>
                </tr>

                <tr>
                  <td class="label"><label for="jyraphe_key"><?php echo _('Password :'); ?></label></td>
                  <td class="content"><input type="text" id="jyraphe_key" name="options[key]" /></td>
                </tr>

                <tr>
                  <td class="label"><label for="jyraphe_duration"><?php echo _('Time limit :'); ?></label></td>
                  <td class="content">
                    <select id="jyraphe_duration" name="options[duration]" >
                    <?php if(!hConfig::getVar('disable_infinity')):?>
                      <option value="<?php echo hDuration::INFINITY; ?>"<?php if(isset($cfg['validity']) && $cfg['validity'] == 'F') echo ' selected="selected"';?>><?php echo _('None'); ?></option>
                    <?php endif;?>
                      <option value="<?php echo hDuration::MINUTE; ?>"<?php if(isset($cfg['validity']) && $cfg['validity'] == '1m') echo ' selected="selected"';?>><?php echo _('One minute'); ?></option>
                      <option value="<?php echo hDuration::HOUR; ?>"<?php if(isset($cfg['validity']) && $cfg['validity'] == '1h') echo ' selected="selected"';?>><?php echo _('One hour'); ?></option>
                      <option value="<?php echo hDuration::DAY; ?>"<?php if(isset($cfg['validity']) && $cfg['validity'] == '1d') echo ' selected="selected"';?>><?php echo _('One day'); ?></option>
                      <option value="<?php echo hDuration::WEEK; ?>"<?php if(isset($cfg['validity']) && $cfg['validity'] == '1w') echo ' selected="selected"';?>><?php echo _('One week'); ?></option>
                      <option value="<?php echo hDuration::MONTH; ?>"<?php if(isset($cfg['validity']) && $cfg['validity'] == '1M') echo ' selected="selected"';?>><?php echo _('One month'); ?></option>
                    </select>
                  </td>
                </tr>

              <?php if (hConfig::getVar('from_email') != "" && hConfig::getVar('smtp_host') != "") { ?>
                <tr>
                  <td class="label"><label for="jyraphe_email_download"><?php echo _('Notify Email on Download :'); ?></label></td>
                  <td class="content"><input id="jyraphe_email_download" type="text" name="options[email_download]" /></td>
                </tr>
                <tr>
                  <td class="label"><label for="jyraphe_email_upload"><?php echo _('Notify Email on Upload :'); ?></label></td>
                  <td class="content"><input id="jyraphe_email_upload" type="text" name="options[email_upload]" /></td>
                </tr>
              <?php } ?>

              </table>
            </div>
          </fieldset>
        </form>
      </div>
    <?php
  }
}

?>
