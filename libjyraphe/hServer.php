<?php

/*
* xml-server.php
* This is Jyraphe's xml-rpc server interface.
*/

if (!function_exists("xmlrpc_server_create")) {
  // include necessary files.

  // Loading phpxmlrpc.
  require_once('xmlrpc/xmlrpc.inc');
  require_once('xmlrpc/xmlrpcs.inc');
  require_once('xmlrpc/xmlrpc_wrappers.inc');
  require_once('xmlrpc/xmlrpc_extension_api.inc');
}

require_once('libjyraphe/hConfig.php');
require_once('libjyraphe/hUpload.php');
require_once('libjyraphe/hDuration.php');
require_once('libjyraphe/hDownload.php');

hConfig::create();

/*
* First, we define some PHP functions to expose via
* XML-RPC. Any functions that will be called by a
* XML-RPC client need to take three parameters:
* The first parameter passed is the name of the
* XML-RPC method called, the second is an array
* Containing the parameters sent by the client, and
* The third is any data sent in the app_data
* parameter of the xmlrpc_server_call_method()
* function (see below).
*/

function isActive() {
  return "1";
}

function getInfo() {
  $retval = array();

  foreach(hConfig::getConf() as $setting => $val) {
    $retval[$setting] = $val;
  }

  $retval["maxsize"] = hSystem::getMaxUploadSize();

  if (disk_free_space(hConfig::getVar('var_root')) > hSystem::getMaxUploadSize()) {
    $retval["uploads"] = "1";
  } else {
    $retval["uploads"] = "-1";
  }

  if (hConfig::getVar('from_email') != "" && hConfig::getVar('smtp_host') != "") {
    $retval["email_settings"] = "1";
  } else {
    $retval["email_settings"] = "-1";
  }

  return $retval;
}

function upload($method_name, $params, $user_data) {
  $message = $params[0];
  $file_name = $message["name"];
  $mime_type = $message["type"];
  $file_size = $message["size"];
  $data = $message["data"];

  $cfg = parse_ini_file('config.php', true);
  $cfg = $cfg['Interface'];

  // Let's save the file in temp to begin with.
  $tmp_name = tempnam(sys_get_temp_dir(), 'jyraphe');
  file_put_contents($tmp_name, base64_decode($data));
  flush();

  // Populating a simili-$_FILE array.
  $file = array('tmp_name' => $tmp_name,
                'name' => $file_name,
                'type' => $mime_type,
                'size' => $file_size,
                'time' => time(),
                'xmlrpc' => true);

  $durations = array('1m' => hDuration::MINUTE,
                     '1h' => hDuration::HOUR,
                     '1d' => hDuration::DAY,
                     '1w' => hDuration::WEEK,
                     '1M' => hDuration::MONTH,
                     'F'  => hDuration::INFINITY);

  // Only the default duration is used at this point.
  $options = array('duration' => $durations[$cfg['validity']]);

  $link = "";
  $error = "";

  try {
    $uploader = new hUpload();
    $link = $uploader->upload($file, $options);
  }
  catch(hException $e) {
    // send back error.
    $error = $e->getMessage();
  }

  // send back the link.
  return array("error" => $error, "link" => $cfg['web_root'] . 'index.php?h=' . $link);
}


function receiveFile($method_name, $params, $user_data) {
  $message = $params[0];
  $uri = $message["uri"];
  /*
  * The uri is complete (i.e. "http://xxx/index.php?h=dsklfjsldfjaslk".
  * Of course, we only need the h parameter, or link id. A bit of
  * string manipulation will do that for us.
  */
  $link = substr($uri, strpos($uri, "h=") + 2);
  $passwd = $message["passwd"];

  if($passwd == "") {
    $passwd = NULL;
  }

  $dl = new hDownload();
  $error = "";
  $file_array;
  try{
    $file_array = $dl->download($link, $passwd, true);
  }
  catch(hException $e) {
    $error = $e->getMessage();
  }

  if($error != "") {
    return new xmlrpcresp(new xmlrpcval(array('error' => new xmlrpcval($error, "string")), "struct"));
  } else {
    /*
    return new xmlrpcresp(new xmlrpcval(array('name'  => new xmlrpcval('name', "string"),
                                              'size'  => new xmlrpcval('size', "int"),
                                              'mime'  => new xmlrpcval('mime', "string"),
                                              'data'  => new xmlrpcval('data', $GLOBALS["xmlrpcBase64"]),
                                              'error' => new xmlrpcval('', "string"),
                                        "struct"));
    */
    return array('name'  => $file_array['name'],
                 'size'  => $file_array['size'],
                 'mime'  => $file_array['mime'],
                 'data'  => $file_array['data'],
                 'error' => '');

  }
}


/*
* This creates a server and sets a handle for the
* server in the variable $xmlrpc_server
*/
$xmlrpc_server = xmlrpc_server_create();

/*
* xmlrpc_server_register_method() registers a PHP
* function as an XML-RPC method. It takes three
* parameters:
* The first is the handle of a server created with
* xmlrpc_server_create(), the second is the name to
* register the server under (this is what needs to
* be in the <methodName> of a request for this
* method), and the third is the name of the PHP
* function to register.
*/

xmlrpc_server_register_method($xmlrpc_server, "jyraphe.isActive", "isActive");
xmlrpc_server_register_method($xmlrpc_server, "jyraphe.upload", "upload");
xmlrpc_server_register_method($xmlrpc_server, "jyraphe.getInfo", "getInfo");
xmlrpc_server_register_method($xmlrpc_server, "jyraphe.receiveFile", "receiveFile");

/*
* When an XML-RPC request is sent to this script, it
* can be found in the raw post data.
*/
$request_xml = $HTTP_RAW_POST_DATA;

/*
* The xmlrpc_server_call_method() sends a request to
* the server and returns the response XML. In this case,
* it sends the raw post data we got before. It requires
* 3 arguments:
* The first is the handle of a server created with
* xmlrpc_server_create(), the second is a string containing
* an XML-RPC request, and the third is for application data.
* Whatever is passed into the third parameter of this function
* is passed as the third paramater of the PHP function that the
* request is asking for.
*/
$response = xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');

// Now we print the response for the client to read.
print $response;

/*
* This method frees the resources for the server specified
* It takes one argument, a handle of a server created with
* xmlrpc_server_create().
*/
xmlrpc_server_destroy($xmlrpc_server);

?>
