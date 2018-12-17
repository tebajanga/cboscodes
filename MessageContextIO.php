<?php

// Allow errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "include/database/PearDatabase.php";
include "include/utils/utils.php";
include_once "modules/Thread/Thread.php";
include_once "modules/GlobalVariable/GlobalVariable.php";
include_once "modules/Thread/Thread.php";
global $current_user;
$logFile='context.io.log';
$date=date('l jS \of F Y h:i:s A');
$LogContent = "Context.io Notification $date \n";
//$data = json_decode($json);
$json = file_get_contents('php://input');

$data = json_decode($json, true);

//error_log($LogContent,3,$logFile) ;
//error_log(file_get_contents('php://input'),3,$logFile);
//Getting the data from JSON
$subject = $data["message_data"]["subject"];
$subject_converted = str_replace("Re:", " ", $subject);
$sender = $data["message_data"]["addresses"]["from"]["name"];
//$current_user->id = 1;
//Get values of all recievers of the email
$sendto_email = "";
for ($x = 0; $x < count($data["message_data"]["addresses"]["to"]); $x++) {
    $sendto_email_values = $data["message_data"]["addresses"]["to"][$x]["email"];
    if (isset($sendto_email_values)) {
        $sendto_email .= ($sendto_email_values . ', ');
    }
}

//Get values of all recievers of the email on CC
$cc = "";
if (!empty($data["message_data"]["addresses"]["cc"])) {
  for ($x = 0; $x < count($data["message_data"]["addresses"]["cc"]); $x++) {
    $cc_values = $data["message_data"]["addresses"]["cc"][$x]["email"];
    if (isset($cc_values)) {
        $cc .= ($cc_values . ', ');
    }
  }
}

$current_email_account = $data["message_data"]["addresses"]["to"][0]["email"];
$sendermail = $data["message_data"]["addresses"]["from"]["email"];
$content_not_c = isset($data["message_data"]["bodies"][0]["content"]) ? $data["message_data"]["bodies"][0]["content"] : "";
$content = html_entity_decode($content_not_c);
$threadID = $data["message_data"]["gmail_thread_id"];

//Separate name and surname
$name = explode(" ", $sender);
$lastname = array_pop($name);
$firstname = implode(" ", $name);

//Check if there is a message thread already (based on gmail_thread)
$uniquethreadid = uniqid();
$checkbymessagethreadid = $adb->query("select * from vtiger_messages join vtiger_crmentity on messagesid=crmid where deleted=0 and messageuniqueid='$threadID'");
$nr = $adb->num_rows($checkbymessagethreadid);

//if there is already, dont create a new thread
$ltp = "";
if (($adb->num_rows($checkbymessagethreadid) > 0)) {
    $thid = $adb->query_result($checkbymessagethreadid, 0, 'thread');
    $ltpq = $adb->query("SELECT linktoproject FROM vtiger_messages WHERE thread= '$thid' AND linktoproject != '0' AND linktoproject != 'null'");
    $ltp = $adb->query_result($ltpq, 0, 'linktoproject');

} else {
    //Create new Thread
    $focust = new Thread();
    $focust->column_fields['assigned_user_id'] = 1;
    $focust->column_fields['sendto'] = $sendto_email;
    $focust->column_fields['carboncopy'] = $cc;
    $focust->column_fields['subject'] = $subject;
    $focust->column_fields['thrsender'] = $sendermail;
    $focust->column_fields['description'] = $content;
    $focust->column_fields['threadlink'] = $uniquethreadid;
    $focust->saveentity("Thread");
    $thid = $focust->id;
}

//Check if the email belong to a contact in crm
$q = $adb->query("select * from vtiger_contactdetails join vtiger_crmentity on crmid=contactid where deleted=0 and email='$sendermail'");
if ($adb->num_rows($q) > 0) {
    $relid = $adb->query_result($q, 0, 'contactid');
} else {
    $qacc = $adb->query("select * from vtiger_account join vtiger_crmentity on crmid=accountid where deleted=0 and email1='$sendermail'");
    if ($adb->num_rows($qacc) > 0) {
        $relaccid = $adb->query_result($qacc, 0, 'accountid');
    } else {
        $relaccid = '';
    }
    //Contact doesn't exist, so create a new one
    $focusc = new Contacts();
    $focusc->column_fields['assigned_user_id'] = 1;
    $focusc->column_fields['firstname'] = $firstname;
    $focusc->column_fields['lastname'] = $lastname;
    $focusc->column_fields['account_id'] = $relaccid;
    $focusc->column_fields['email'] = $sendermail;
    $focusc->saveentity("Contacts");
    $relid = $focusc->id;
}

//Create message
include_once 'modules/Messages/Messages.php';

// Message variables
global $log;
$log->fatal("Creating a new Message");

$focus_messages = new Messages();
$focus_messages->column_fields["messagesname"] = $subject;
$focus_messages->column_fields["assigned_user_id"] = 1;
$focus_messages->column_fields["description"] = $content;
$focus_messages->column_fields['messagestype'] = 'Email';
$focus_messages->column_fields['thread'] = $thid;
$focus_messages->column_fields['messageuniqueid'] = $threadID;
$focus_messages->column_fields['messagesrelatedto'] = $relid;
$focus_messages->column_fields['linktoproject'] = $ltp;
$focus_messages->save("Messages");
$log->fatal(
  array(
    'Focus Message' => $focus_messages
  )
);
$message_id = $focus_messages->id;

// Handling attachments
global $adb,$root_directory;
$upload_file_path = decideFilePath();

define('CONSUMER_KEY', GlobalVariable::getVariable('ContextIOKey', ''));
define('CONSUMER_SECRET', GlobalVariable::getVariable('ContextIOSecret', ''));
define('USER_ID', GlobalVariable::getVariable('ContextIOUserId', ''));
define('LABEL', '0'); 
define('FOLDER', 'Inbox');
define('MESSAGEID',$data['message_data']['message_id']);

$attachmentid = $data["message_data"]['files'][0]['attachment_id'];


$current_id = $adb->getUniqueID("vtiger_crmentity");
$file_name = $data["message_data"]['files'][0]['file_name'];
$saveasfile = $upload_file_path."$file_name";

include_once("CONTEXTIO/class.contextio.php");
$LogContent.= "Context.io Notification $date DIRNAME = $saveasfile \n";
$contextio = new ContextIO(CONSUMER_KEY,CONSUMER_SECRET);

$contextio->getFileContent(USER_ID, array('label'=>LABEL, 'folder'=>FOLDER,'message_id'=>MESSAGEID,'attachment_id'=> $attachmentid),$saveasfile);
$filename = $upload_file_path.$current_id . "_".$data["message_data"]['files'][0]['file_name'];

shell_exec("chmod 777 -R $upload_file_path");
shell_exec("mv $saveasfile $filename");
//$saveasfile = $filename;
$LogContent.= "Context.io Notification $date attchmentid  = $attachmentid \n"; 
$LogContent.= "Context.io Notification $date file_name  = $file_name \n";
$LogContent.= "Context.io Notification $date to = $sendto_email \n";
$LogContent.= "Context.io Notification $date SUBJECT = $subject \n";
$LogContent.= "Context.io Notification $date FROM = $sendermail \n";
$LogContent.= "Context.io Notification $date CC = $cc \n";
$LogContent.= "Context.io Notification $date EMAIL CONTENT = $content \n";

require_once("modules/Users/Users.php");
$current_user = new Users();
$current_user->retrieveCurrentUserInfoFromFile(1);
$LogContent.= "Context.io Notification $date USERID = $current_user->id \n";
$finfo = finfo_open(FILEINFO_MIME);
$ffn = $saveasfile;
$LogContent.= "Context.io Notification $date FILE PATH  = $ffn \n"; 
error_log($LogContent,3,$logFile);
$files = array(
    "name" => $file_name,
    "type" => finfo_file($finfo, $ffn),
    "tmp_name" => $ffn,
    "error" => 0,
    "size" => filesize($ffn)
    );

require_once("modules/Documents/Documents.php");
$document = new Documents();
$document->column_fields['notes_title'] = $file_name;
$document->column_fields['filename'] = $files["name"];
$document->column_fields['filesize'] = $files["size"];
$document->column_fields['filetype'] = $files["type"];
$document->column_fields['filestatus'] = 1;
$document->column_fields['filelocationtype'] = 'I';
$document->column_fields['assigned_user_id'] = $current_user->id;
$document->column_fields["message"]=$message_id;
$document->save('Documents');

$sql1 = "insert into vtiger_crmentity
    (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime)
    values(?, ?, ?, ?, ?, ?, ?)";
    
$params1 = array($current_id , 1, 1, " Attachment", '', date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
$adb->pquery($sql1, $params1);
$sql2="insert into vtiger_attachments(attachmentsid, name, description, type,path) values(?, ?, ?, ?,?)";
$params2 = array($current_id , $file_name, '', 'application/pdf',$upload_file_path);
$result=$adb->pquery($sql2, $params2);
$adb->pquery("Insert into vtiger_seattachmentsrel values (?,?)",array($document->id,$current_id));
