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
require_once('hConfig.php');
require_once('smtp.php');

class hDownload extends hJyraphe {

  private function remove_file($file) {
    if(file_exists($file)) {
      unlink($file);
    }
  }

  /*
  * Fetches the requested file either direclty or returns it as a
  * string. If the file requires a password, it is checked before
  * fetching the file.
  *
  * @param $link is the file's link.
  * @param $userKey is the password to the file, if necessary.
  * @param $return specify whether the file must be returned as a
  * string or just downloaded (default).
  * @return the file as a string if required.
  */
  public function download($link, $userKey = null, $return = false) {

    $file = $this->getLink($link);
    if(!file_exists($file)) {
      throw hException::getHttpException(404);
    }

    // The link has the form of a .ini file. We load it into variables.
    $content = parse_ini_file($file);

    $name = $content['name'];
    $mimeType = $content['mimeType'];
    $size = $content['fileSize'];
    $oneTimeDownload = $content['dlOnce'];
    $key = $content['key'];
    $time = $content['time'];
    $email = $content['email'];

    // Checks if the file exists
    if(!file_exists($this->getFile($name))) {
      throw hException::getHttpException(404);
    }

    if($time != hConfig::getVar('time_infinity')) {
      if(time() > $time) {
        $this->remove_file($this->getLink($file));
        $this->remove_file($file);
        throw new hException(_('The time limit of this file has expired. It has been deleted.'), hException::TIME_EXPIRED_ERROR);
      }
    }

    if(!empty($key)) {
      $userKey = isset($_POST['key'])? $_POST['key'] : null;
      if($userKey == null) {
        throw new hException(_('This file is protected by a key.'), hException::NEED_KEY_ERROR);
      } else {
        if($key != $userKey) {
          throw hException::getHttpException(403);
        }
      }
    }

    if(!$return) {
      header('Content-Description: File Transfer');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      header('Content-Transfer-Encoding: binary');
      header('Content-Length: ' . $size);
      header('Content-Type: ' . $mimeType);
      if(!hSystem::isViewable($mimeType)) {
        header('Content-Disposition: attachment; filename="' . $name . '"');
      } else {
        header('Content-Disposition: inline; filename="' . $name . '"');
      }

      // Sends the file for download.
      ob_clean();
      flush();
      readfile($this->getFile($name));
    }

    if($oneTimeDownload) {
      $this->remove_file($this->remove_file($this->getFile($name)));
      $this->remove_file($this->getLink($file));
    }

    if(!empty($email) && hConfig::getVar('smtp_host') != "" && hConfig::getVar('from_email') != "") {
      $subject =  _("[JYRAPHE] ") . $name . _(' has been downloaded');
      $body = _("The file '") . $name . _("' has been downloaded by a user from ") . $_SERVER['REMOTE_ADDR'];

      try {
        $smtp = new SMTP(hConfig::getVar('smtp_host'), hConfig::getVar('smtp_port'), $_SERVER['SERVER_NAME'], hConfig::getVar('smtp_auth'), hConfig::getVar('smtp_username'), hConfig::getVar('smtp_password'));
        $smtp->dbug = false;
        $smtp->Connect();
        $smtp->Send(array($email),                                     // Array of recipient addresses
                    hConfig::getVar('from_email'),                     // SMTP From address
                    array('Subject' => $subject,
                          'To'      => $email,
                          'From'    => hConfig::getVar('from_email')), // The emails headers
                    $body);                                            // The emails body (phwoar...)

      }

      catch (SMTPException $e) {
        echo $e->GetMessage();
      }
    }

    // Returning the file as string if required.
    if($return) {
      return array('name' => $name,
                   'size' => $size,
                   'mime' => $mimeType,
                   'data' => file_get_contents($this->getFile($name)));
    }
  }

  public function printKeyForm($postUrl) {
    ?>
    <div id="jyraphe_download">
      <form action="<?php echo $postUrl; ?>" method="post">
        <input type="hidden" name="jyraphe" value="<?php echo parent::VERSION; ?>" />
        <fieldset>
          <legend><?php echo _('Key protection'); ?></legend>
          <p><label><?php echo _('Give the key of this file : '); ?></label><input type="password" name="key" /></p>
          <p><input type="submit" value="<?php echo _('Download this file'); ?>" /></p>
        </fieldset>
      </form>
    </div>
    <?php
  }
}
?>
