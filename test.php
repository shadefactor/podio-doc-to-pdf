<?php

require 'vendor/autoload.php';
require_once 'app_setting.inc.php';
use ZendService\LiveDocx\MailMerge;

error_log("validate triggerd");

// Setup client
Podio::setup($client_id, $client_secret);

// Turn on debugging
Podio::$debug = true;

// Authenticate the app
Podio::authenticate('app', array('app_id' => $app_id, 'app_token' => $app_token));

switch ($_POST['type']) {
    case 'hook.verify':
        // Validate the webhook
		PodioHook::validate($_POST['hook_id'], array('code' => $_POST['code']));
    case 'item.create':
		$item = PodioItem::get( $_POST['item_id'] );
		$temp_array = array();
		foreach($item->files as $fs){
			$temp_array[] = $fs;
			if($fs->mimetype == 'application/msword'){
				
				//Get file name withour ext
				$no_ext = substr($fs->name, 0, strpos($fs->name,'.'));
				
				//Upload file to our server				
				$fl = PodioFile::get($fs->file_id);				
				$fc = $fl->get_raw();
				file_put_contents($upload_path . $fs->name, $fc);
				
				//Part with convert files from doc(x) to pdf
				$mailMerge = new MailMerge();
				$mailMerge->setUsername($user)
						->setPassword($password)
						->setService (MailMerge::SERVICE_FREE);
				$mailMerge->setLocalTemplate($upload_path . $fs->name);
				$mailMerge->assign('software', 'Magic Graphical Compression Suite v1.9');
				$mailMerge->createDocument();
				$document = $mailMerge->retrieveDocument($need_ext);
				file_put_contents($upload_path . $no_ext . $ext_pdf, $document);
				unset($mailMerge);
				
				// Attached file pdf to our item
				$f = PodioFile::upload($upload_path . $no_ext . $ext_pdf, $no_ext . $ext_pdf);
				$temp_array[] = $f;
				
				// Removed temp files
				unlink($upload_path . $fs->name);
				unlink($upload_path . $no_ext . $ext_pdf);
			}
		}
		// Create a new collection for files
		$item->files = new PodioCollection($temp_array);
		// Save the item to Podio
		$item->save();

    case 'item.update':
        // Do something. item_id is available in $_POST['item_id']
    case 'item.delete':
        // Do something. item_id is available in $_POST['item_id']
}

?>