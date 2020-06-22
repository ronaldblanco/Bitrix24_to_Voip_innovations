<?php
require_once (__DIR__.'/crest/crest.php');

$recipient = isset($_GET['to']) ? trim(str_replace("+","",$_GET["to"])) : '';
$message = isset($_GET['text']) ? $_GET["text"] : '';
$sender = isset($_GET['from']) ? $_GET["from"] : '';
$type = isset($_GET['messageType']) ? $_GET["messageType"] : '';
$result = ['result' => 'false'];
//var_dump($recipient);

//Only for TESTs!
//$myfile = fopen("log.txt", "a") or die("Unable to open file!");
//echo '**************************\n';
//echo date("Y.m.d G:i:s")."\n";
//var_dump($_GET);
//var_dump($_POST); //from bitrix24


if($recipient != "" && $message != "" && $sender != "" && $type === "SMS"){
	
	$user = ( CRest :: call (
    	'user.get' ,
   		[
	  		'FILTER' => ["PERSONAL_MOBILE" => $recipient],
   		])
	);
	//var_dump($user['result']);
	
	if(!isset($user['result'][0]['ID'])){
		$user = ( CRest :: call (
    		'user.get' ,
   			[
	  			'FILTER' => ["PERSONAL_MOBILE" => substr($recipient,1,10)],
   			])
		);
		//var_dump($user['result']);
	}
	
	$contact = ( CRest :: call (
    'crm.contact.list' ,
   	[
	  'FILTER' => ["PHONE" => $sender],
	  'SELECT' => ['ID','ASSIGNED_BY_ID','NAME','LAST_NAME'],
   	])
	);
	//var_dump($contact);
	
	if(!isset($contact['result'][0]['ID'])){
		$contact = ( CRest :: call (
    	'crm.contact.list' ,
   		[
	  	'FILTER' => ["PHONE" => substr($sender,1,10)],
	  	'SELECT' => ['ID','ASSIGNED_BY_ID','NAME','LAST_NAME'],
   		])
		);
		//var_dump($contact);
	}
	
	$comment = "A SMS was receibed from sender: " . $sender . ", contact: " . $contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME'] . ", with the message: " . $message . "!";
	
	$timeline = ( CRest :: call (
    'crm.timeline.comment.add' ,
   	[
		'fields' =>
           [
               "ENTITY_ID" => $contact['result'][0]['ID'],
               "ENTITY_TYPE" => "contact",
               "COMMENT" => $comment,
           ]
   	])
	);
	//var_dump($contact);
	
	if(isset($user['result'][0]['ID'])){
	
	$setmessage = ( CRest :: call (
    	'im.notify' ,
   		[
			"to" => $user['result'][0]['ID'],
         	"message" => $comment,
         	"type" => 'SYSTEM',
   		])
	);
	//var_dump($setmessage);
	$result = ['result' => 'true'];
		
	}
}

echo $result;

//Only for TESTs!
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()));
//fclose($myfile);

?>