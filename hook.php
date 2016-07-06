<?php
$hookSecret = $_SERVER['SECRET'];
$mode = $_SERVER['MODE'];

function pullMaster($payload){
  if ($payload['ref'] === "refs/heads/$_SERVER[BRANCH_NAME]"){
    `$_SERVER[COMMAND]`;
    file_put_contents(
      dirname(__FILE__).'/hook.log',
      date("[Y-m-d H:i:s]")." ".$_SERVER['REMOTE_ADDR']." git pulled: ".$payload['head_commit']['message']."\n",
      FILE_APPEND|LOCK_EX
    );
  }
}

set_error_handler(function($severity, $message, $file, $line) {
	throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
	header('HTTP/1.1 500 Internal Server Error');
	echo "Error on line {$e->getLine()}: " . htmlSpecialChars($e->getMessage());
	die();
});

function checkRequest(){
  if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    throw new \Exception("Method Not Allowed");
  }
}

function checkSecret($hookSecret){
  if ($hookSecret !== NULL) {
  	if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
  		throw new \Exception("HTTP header 'X-Hub-Signature' is missing.");
  	} elseif (!extension_loaded('hash')) {
  		throw new \Exception("Missing 'hash' extension to check the secret code validity.");
  	}
  	list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
  	if (!in_array($algo, hash_algos(), TRUE)) {
  		throw new \Exception("Hash algorithm '$algo' is not supported.");
  	}
  	if ($hash !== hash_hmac($algo, file_get_contents('php://input'), $hookSecret)) {
  		throw new \Exception('Hook secret does not match.');
  	}
  };
}

function checkContentType() {
  if (!isset($_SERVER['CONTENT_TYPE'])) {
  	throw new \Exception("Missing HTTP 'Content-Type' header.");
  } elseif (!isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
  	throw new \Exception("Missing HTTP 'X-Github-Event' header.");
  }
}

function getJson(){
  switch ($_SERVER['CONTENT_TYPE']) {
  	case 'application/json':
  		$json = file_get_contents('php://input');
  		break;
  	case 'application/x-www-form-urlencoded':
  		$json = $_POST['payload'];
  		break;
  	default:
  		throw new \Exception("Unsupported content type: $_SERVER[CONTENT_TYPE]");
  }
  return $json;
}

function triggerEvent($payload){
  switch (strtolower($_SERVER['HTTP_X_GITHUB_EVENT'])) {
  	case 'ping':
  		echo 'pong';
  		break;
  	case 'push':
  		pullMaster($payload);
  		break;
  	case 'create':
  		break;
  	default:
  		header('HTTP/1.0 404 Not Found');
  		echo "Event:$_SERVER[HTTP_X_GITHUB_EVENT] Payload:\n";
  		// print_r($payload); # For debug only. Can be found in GitHub hook log.
  		die();
  }
}

switch($mode){
  case 'debug':
    echo "debug mode";
    `$_SERVER[DEBUG_COMMAND]`;
    file_put_contents(
      dirname(__FILE__).'/hook.log',
      date("[Y-m-d H:i:s]")." ".$_SERVER['REMOTE_ADDR']." git pulled: debug mode\n",
      FILE_APPEND|LOCK_EX
    );
    break;
  default:
    checkRequest();
    checkSecret($hookSecret);
    checkContentType();
    $payload = json_decode(getJson(), true);
    triggerEvent($payload);
}
