<?php
//BITRIX24 HEAD for application!
$auth = $_REQUEST['AUTH_ID'];
$domain = ($_REQUEST['PROTOCOL'] == 0 ? 'http' : 'https') . '://'.$_REQUEST['DOMAIN'];

$res = file_get_contents($domain.'/rest/user.current.json?auth='.$auth);
$arRes = json_decode($res, true);

require_once (__DIR__.'/crest/crest.php');
?>

<?php
//$_ENV["USER"];
//var_dump($_GET);
//properties%5Bphone_number%5D
//properties%5Bmessage_text%5D
//$recipient = '';
//$message = '';

if (isset($_POST['properties']['phone_number']) && isset($_POST['properties']['message_text'])){
	$recipient = isset($_POST['properties']['phone_number']) ? trim(str_replace("+","",$_POST["properties"]['phone_number'])) : '';
	$message = isset($_POST['properties']['message_text']) ? $_POST["properties"]['message_text'] : '';
}
if (isset($_POST['message_to']) && isset($_POST['message_body'])){
	$recipient = isset($_POST['message_to']) ? trim(str_replace("+","",$_POST["message_to"])) : '';
	$message = isset($_POST['message_body']) ? $_POST["message_body"] : '';
}
$contactid = isset($_POST['bindings'][0]['OWNER_ID']) ? $_POST['bindings'][0]['OWNER_ID'] : 0;
$auth = isset($_POST['auth']['access_token']) ? $_POST['auth']['access_token'] : '';
$domain = isset($_POST['auth']['domain']) ? $_POST['auth']['domain'] : '';
$menberid = isset($_POST['auth']['member_id']) ? $_POST['auth']['member_id'] : '';

$defaultsender = '3051234567';
$SMSuser = 'api';
$SMSpass = 'pass';

if($contactid > 0){
	$ownerid = ( CRest :: call (
    'crm.contact.list' ,
   [
	  'FILTER' => ["ID" => $contactid],
	  'SELECT' => ['ASSIGNED_BY_ID','NAME','LAST_NAME'],
   ])
);
//var_dump($ownerid);
$ownerphone = ( CRest :: call (
    'user.get' ,
   [
	  'FILTER' => ["ID" => $ownerid['result'][0]['ASSIGNED_BY_ID']],
   ])
);
//var_dump($ownerphone['result'][0]);
}

$sender = isset($ownerphone['result'][0]['PERSONAL_MOBILE']) && $ownerphone['result'][0]['PERSONAL_MOBILE'] != '' ? $ownerphone['result'][0]['PERSONAL_MOBILE'] : $defaultsender;

//var_dump($sender);

//Only for TESTs!
//$myfile = fopen("log.txt", "a") or die("Unable to open file!");
//echo '**************************\n';
//echo date("Y.m.d G:i:s")."\n";
//var_dump($_GET);
//var_dump($_POST); //from bitrix24
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()));
//fclose($myfile);


try{
$soapclient = new SoapClient('https://backoffice.voipinnovations.com/Services/APIService.asmx?wsdl');
$param=array('login'=>$SMSuser,'secret'=>$SMSpass,'sender'=>$sender,'recipient'=>$recipient,'message'=>$message);

if((isset($domain) && $domain === "bitrix24.domain.com") && (isset($auth) && $auth != "") && (isset($menberid) && $menberid != "")){
	
	$response =$soapclient->SendSMS($param);
	$result = json_encode($response);
	echo $result;
	$smsresult = $response->SendSMSResult->responseMessage;
	if($smsresult != 'Success' && $smsresult != 'Invalid sender' && $smsresult != 'Invalid TN. Sender and recipient must be valid phone numbers and include country code.') $smsresult = 'SMS Error';
	
	$comment = "The SMS Service for contact " . $ownerid['result'][0]['NAME'] . " " . $ownerid['result'][0]['LAST_NAME'] . " responded: " . $smsresult . "!";
	
	$timeline = ( CRest :: call (
    'crm.timeline.comment.add' ,
   	[
		'fields' =>
           [
               "ENTITY_ID" => $contactid,
               "ENTITY_TYPE" => "contact",
               "COMMENT" => $comment,
           ]
   	])
	);
	
	$setmessage = ( CRest :: call (
    	'im.notify' ,
   		[
			"to" => $ownerid['result'][0]['ASSIGNED_BY_ID'],
         	"message" => $comment,
         	"type" => 'SYSTEM',
   		])
	);
	//var_dump($setmessage);
	
}	
	
	
}catch(Exception $e){
	echo $e->getMessage();
}

//Only for tests!
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()));
//fclose($myfile);

?>