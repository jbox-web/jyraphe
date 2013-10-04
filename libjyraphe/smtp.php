<?php

/**
* o------------------------------------------------------------------------------o
* | This package is licensed under the Phpguru license. A quick summary is       |
* | that for commercial use, there is a small one-time licensing fee to pay. For |
* | registered charities and educational institutes there is a reduced license   |
* | fee available. You can read more  at:                                        |
* |                                                                              |
* |                  http://www.phpguru.org/static/license.html                  |
* o------------------------------------------------------------------------------o
*
*  Copyright 2008,2009 Richard Heyes
*/


define('SMTP_STATUS_DISCONNECTED', 1, true);
define('SMTP_STATUS_CONNECTED', 2, true);

class SMTP
{
  /**
  * Controls printing of debug information
  */
  public $dbug = false;

  /**
  * Whether or not the class is currently authenticated with the server
  */
  private $authenticated = false;

  /**
  * The SMTP socket
  */
  private $socket;

  /**
  * An array of the recipient addresses (I think...)
  */
  private $recipients;

  /**
  * An associative array of headers for the message
  * ag. array('From' => 'bob@dole.com')
  */
  private $headers = array();

  /**
  * The network timeout
  */
  private $timeout;

  /**
  * The current status
  */
  public $status = SMTP_STATUS_DISCONNECTED;

  /**
  * The email to send, excluding headers
  */
  private $body;

  /**
  * The from address to use in the MAIL FROM command
  */
  private $from;

  /**
  * The remote SMTP host to connect to
  */
  private $host;

  /**
  * The remote SMTP servers' port
  */
  private $port;

  /**
  * The host name to use in the SMTP HELO command
  */
  private $helo;

  /**
  * Whether to use authentication or not
  */
  private $auth;

  /**
  * The authentication username
  */
  private $user;

  /**
  * The authentication password
  */
  private $pass;


  /**
  * Constructor. Arguments:
  * $params - An assoc array of parameters:
  *
  *   host    - The hostname of the smtp server        Default: localhost
  *   port    - The port the smtp server runs on        Default: 25
  *   helo    - What to send as the HELO command        Default: localhost
  *             (typically the hostname of the
  *             machine this script runs on)
  *   auth    - Whether to use basic authentication    Default: FALSE
  *   user    - Username for authentication            Default: null
  *   pass    - Password for authentication            Default: null
  *   timeout - The timeout in seconds for the call    Default: 5
  *             to fsockopen()
  */
  public function __construct($host = 'localhost', $port = 25, $helo = 'localhost', $auth = false, $user = null, $pass = null, $timeout = 5) {
    if(!defined('CRLF')) {
      define('CRLF', "\r\n", true);
    }

    $this->authenticated = false;
    $this->timeout       = $timeout;
    $this->status        = SMTP_STATUS_DISCONNECTED;
    $this->host          = $host;
    $this->port          = $port;
    $this->helo          = $helo;
    $this->auth          = $auth;
    $this->user          = $user;
    $this->pass          = $pass;
  }


  /**
  * Getter
  *
  * @param $name Name of proprty
  */
  public function __get($name) {
    return $this->$name;
  }


  /**
  * Setter
  *
  * @param $name  Name of property
  * @param $value Value of property
  *
  */
  public function __set($name, $value) {
    $this->$name = $value;
  }


  /***
  * Connect function. This will connect using the parameters
  * suppllied to the constructor
  */
  public function Connect() {
    $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

    if (!$this->socket) {
      throw new SMTPException("Failed to connect to socket at: {$this->host}:{$this->port}");
    }

    /**
    * Set the timeout on the socket
    */
    stream_set_timeout($this->socket, $this->timeout);

    $greeting = $this->getData();

    return $this->auth ? $this->ehlo() : $this->helo();
  }


  /**
  * Returns true/false as to whether the object is connected or not
  *
  * @return bool
  */
  public function Connected() {
    return is_resource($this->socket);
  }


  /**
  * Returns true/false as to whether the object is authenticated or not
  */
  public function Authenticated() {
    return $this->authenticated;
  }


  /**
  * Function which handles sending the data over the socket
  *
  * @param string $recipients An array of email addresses whp will be the recipients
  * @param string $from       An email address who the email address is from
  * @param string $headers    Headers for the email
  * @param string $body       The body of the email
  */
  public function Send($recipients, $from, $headers, $body) {
    if(!$this->Connected()) {
      throw new SMTPException('Please connect first!');
    }

    // Do we auth or not? Note the distinction between the auth variable and auth() function
    if($this->auth AND !$this->Authenticated()){
      if(!$this->Auth())
        return false;
    }

    $this->mail($from);

    foreach($recipients as $v) {
      $this->rcpt($v);
    }

    $this->data();

    /**
    * Headers
    */
    foreach ($headers as $k => $v) {
      $tmp[] = "{$k}: {$v}";
    }
    $headers = implode(CRLF, $tmp);
    $headers = preg_replace('/^\.[^.]/mis', '', $headers);

    $body    = str_replace(CRLF . '.', CRLF . '..', $body);
    $body    = strlen($body) > 0 && $body{0} == '.' ? '.' . $body : $body;

    $this->SendData($headers);
    $this->SendData('');
    $this->SendData($body);
    $this->SendData('.');

    $result = substr($this->getData(), 0, 3) === '250';
    $this->rset();
    return $result;
  }


  /**
  * Sends the HELO command
  */
  public function helo() {
    if(    $this->Connected()
       AND $this->SendData('HELO ' . $this->helo)
       AND substr($error = $this->getData(), 0, 3) === '250'){
        return true;
    }

    throw new SMTPException('HELO command failed, output: ' . trim(substr(trim($error),3)));
  }


  /**
  * Implements the EHLO ESMTP command
  */
  public function ehlo() {
    if(    $this->Connected()
       AND $this->SendData('EHLO '.$this->helo)
       AND substr($error = $this->getData(), 0, 3) === '250' ){
        return true;
    }

    throw new SMTPException('EHLO command failed, output: ' . trim(substr(trim($error),3)));
  }


  /**
  * Implements the RSET command
  */
  public function rset() {
    if(    $this->Connected()
       AND $this->SendData('RSET')
       AND substr($error = $this->getData(), 0, 3) === '250' ){
        return true;
    }

    throw new SMTPException('RSET command failed, output: ' . trim(substr(trim($error),3)));
  }


  /**
  * Sends the QUIT command
  */
  public function quit() {
    if(    $this->Connected()
       AND $this->SendData('QUIT')
       AND substr($error = $this->getData(), 0, 3) === '221' ){
        fclose($this->socket);
        $this->status = SMTP_STATUS_DISCONNECTED;
        return true;
    }

    throw new SMTPException('QUIT command failed, output: ' . trim(substr(trim($error),3)));
  }


  /**
  * Function which implements the AUTH command
  */
  public function auth() {
    $error = '';

    if(    $this->Connected()
       AND $this->SendData('AUTH LOGIN')
       AND substr($error = $this->getData(), 0, 3) === '334'
       AND $this->SendData(base64_encode($this->user)) // Send username
       AND substr($error = $this->getData(),0,3) === '334'
       AND $this->SendData(base64_encode($this->pass)) // Send password
       AND substr($error = $this->getData(),0,3) === '235' ){
        $this->authenticated = true;
        return true;
    }

    throw new SMTPException('AUTH command failed: ' . trim(substr($error, 3)));
  }


  /**
  * Implements the MAIL FROM command
  *
  * @param $from The SMTP envelope from
  */
  public function mail($from) {
    if(     $this->Connected()
        AND $this->SendData("MAIL FROM:<{$from}>")
        AND substr($error = $this->getData(), 0, 3) === '250' ){
        return true;
    }

    throw new SMTPException('Failed to send SMTP data: ' . $error);
  }


  /**
  * Implements the RCPT TO command
  *
  * @param $to The recipient address
  */
  public function rcpt($to) {
    if(    $this->Connected()
       AND $this->SendData('RCPT TO:<'.$to.'>')
       AND substr($error = $this->getData(), 0, 2) === '25' ){
        return true;
    }

    throw new SMTPException(trim(substr(trim($error), 3)));
  }


  /**
  * Implements the SMTP DATA command
  */
  public function data() {
    $error = '';

    if(    $this->Connected()
       AND $this->SendData('DATA')
       AND substr($error = $this->getData(), 0, 3) === '354' ){
        return true;
    }

    throw new SMTPException(trim(substr($error, 3)));
  }


  /**
  * Sends some data. Surprising that
  */
  private function SendData($data) {
    if ($this->Connected()) {
      if ($this->dbug) {
        echo ' SENDING: ' . htmlspecialchars("{$data}") . '<br />';
      }
      return fwrite($this->socket, $data . CRLF, strlen($data)+2);
    }

    throw new SMTPException('Please connect first!');
  }


  /**
  * Fetches data from the socket
  *
  * @return string The data that has been read from the socket
  */
  private function getData() {
    $return = '';
    $line   = '';
    $loops  = 0;

    if ($this->Connected()) {
      while ((strpos($return, CRLF) === false OR substr($line,3,1) !== ' ') AND $loops < 100) {
        $line    = fgets($this->socket, 512);
        $return .= $line;
        $loops++;
      }

      if ($this->dbug) {
        echo 'RECIEVED: ' . htmlspecialchars($return);
      }

      return trim($return);
    }

    return false;
  }

}

/**
* SMTP Exception class for errors
*/
class SMTPException extends Exception
{
}
?>
