<pre>
<?php

require_once('libjyraphe/xmlrpc/xmlrpc.inc');
require_once('libjyraphe/xmlrpc/xmlrpcs.inc');

// Config.
$upload_t = false;
$settings_t = true;
$active_t = true;
$download_t = true;
$server_uri = "http://zeus/jyraphe/jyrapheOO/pub/xmlrpc.php";
$file_uri = "http://www.zeus.loc/jyraphe/jyrapheOO/pub/?h=d41d8cd98f00b204e9800998ecf8427e";

$c = new xmlrpc_client($server_uri);
//$c->setdebug(1);

if($active_t) {
  // Checking if the server's active.
  $m = new xmlrpcmsg('jyraphe.isActive');
  $r = $c->send($m);
  $v = $r->value();
  if($v->scalarval()) {
    echo "Server is active and listening.\n\n";
  } else {
    echo "Server is inactive.\n\n";
  }
}

if($settings_t) {
  // Fetching the server's settings.
  $m = new xmlrpcmsg('jyraphe.getInfo');
  $r = $c->send($m);
  //echo '<pre>'; var_dump($r); echo '</pre>';
  $v = $r->value();
  $v->structreset();
  while(list($key, $value) = $v->structEach()) {
    echo '"' . $key . '" => "' . $value->scalarval() . '"' . "\n";
  }
}

if($upload_t) {
  // Sending file.
  $file_name = isset($_GET['file'])? $_GET['file'] : 'test.ps';
  $mime_type = isset($_GET['mime'])? $_GET['mime'] : 'ps';

  $file = file_get_contents($file_name);

  $m = new xmlrpcmsg('jyraphe.upload',
                     array(new xmlrpcval(array(
                                           "name" => new xmlrpcval($file_name, $xmlrpcString),
                                           "type" => new xmlrpcval($mime_type, $xmlrpcString),
                                           "size" => new xmlrpcval(filesize($file_name), $xmlrpcString),
                                           "data" => new xmlrpcval($file, $xmlrpcBase64)),
                                         "struct")));
  $r = $c->send($m);
  if (!$r->faultCode()) {
    $v = $r->value();
    $err = $v->structMem('error');
    $link = $v->structMem('link');
    if($err->scalarval() != "") {
      throw new Exception($err->scalarval());
    } else {
      print '<a href="' . htmlentities($link->scalarval()) . '">'
        . htmlentities($link->scalarval()) . '</a>';
    }
  } else {
    print "Fault <BR>";
    print "Code: " . htmlentities($r->faultCode()) . "<BR>" .
      "Reason: '" . htmlentities($r->faultString()) . "'<BR>";
  }
}

if($download_t) {
  $m = new xmlrpcmsg('jyraphe.receiveFile',
                     array(new xmlrpcval($file_uri, $xmlrpcString),
                           new xmlrpcval("", $xmlrpcString)));
  $r = $c->send($m);
  $v = $r->value();
  if($v->structMem('error')->scalarval() != "") {
    echo $v->structMem('error')->scalarval();
  } else {
    echo "\nSaved ./" . $v->structMem('name')->scalarval() . "\n";
    file_put_contents($v->structMem('name')->scalarval(),
                      $v->structMem('data')->scalarval());
  }
}

?>
</pre>
