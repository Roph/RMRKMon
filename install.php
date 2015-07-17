<?php

require_once('settings.php');
require_once('../SSI.php');

//Figure out if this user can administrate.
$pcfg['is_admin'] = false;
	
foreach ($pcfg['admin_groups'] as $allowed) {
	if (in_array($allowed, $user_info['groups'])) {
		$pcfg['is_admin'] = true;
		break;
	}
}

if (!$pcfg['is_admin']) die('No admin, no dice');


if (isset($_GET['proceed'])) { //SCRUB

	//Get our DB handle setup
	$file_db = new PDO('sqlite:'.$pcfg['sqlite_db'].'');
	$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	$file_db ->exec("UPDATE pokemon
					SET owners = '', shiny_owners = '', encounters = 0, shiny_encounters = 0, captures = 0, shiny_captures = 0");
					
	$file_db ->exec('UPDATE stats
					SET total_encounters = 0, total_captures = 0, total_trainers = 0, total_trades = 0');
					
	$file_db ->exec('DELETE FROM trades');
	
	$file_db ->exec('DELETE FROM adminlog');
	
	$file_db ->exec('DELETE FROM tradelog');
	
	$file_db ->exec('DELETE FROM trainers');
	
	$file_db = null;
	
	echo 'Success!';
}

else echo 'This will wipe / reset the database <strong>'.$pcfg['sqlite_db'].'</strong>. <a href="?proceed">Proceed.</a>';