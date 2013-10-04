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

class hException extends Exception {

  const GENERIC_ERROR = 0;
  const PERMISSIONS_ERROR = 1;
  const TIME_EXPIRED_ERROR = 2;
  const NEED_KEY_ERROR = 3;

  const UPLOAD_TOO_BIG_ERROR = 10;
  const UPLOAD_PARTIAL_ERROR = 11;
  const UPLOAD_INTERNAL_ERROR = 12;

  const HTTP_403_ERROR = 20;
  const HTTP_404_ERROR = 21;

  public function __construct($message, $code = self::GENERIC_ERROR) {
    parent::__construct($message, $code);
  }

  public static function getUploadException($uploadErrorCode) {
    $message = _('Unknown error.');
    $code = self::GENERIC_ERROR;

    switch($uploadErrorCode) {
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
      $message = _('Your file exceeds the maximum authorized file size.');
      $code = self::UPLOAD_TOO_BIG_ERROR;
      break;

    case UPLOAD_ERR_PARTIAL:
    case UPLOAD_ERR_NO_FILE:
      $message = _('Your file was not uploaded correctly. You may succeed in retrying.');
      $code = self::UPLOAD_PARTIAL_ERROR;
      break;

    case UPLOAD_ERR_NO_TMP_DIR:
    case UPLOAD_ERR_CANT_WRITE:
    case UPLOAD_ERR_EXTENSION:
      $message = _('Internal error. You may not succeed in retrying.');
      $code = self::UPLOAD_INTERNAL_ERROR;
      break;

    default:
      break;
    }

    return new hException($message, $code);
  }

  public static function getHttpException($httpErrorCode) {
    $message = _('Unknown error.');
    $code = self::GENERIC_ERROR;

    switch($httpErrorCode) {
    case 403;
      $message = _('Error 403: Forbidden');
      $code = self::HTTP_403_ERROR;
      break;

    case 404;
      $message = _('Error 404: Not Found');
      $code = self::HTTP_404_ERROR;
      break;

    default:
      break;
    }

    return new hException($message, $code);
  }

}

?>
