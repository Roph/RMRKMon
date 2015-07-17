<?php
require_once('lib.php');
require_once('settings.php');
require_once('db.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
if (isset($_GET['randompoke'])) {
	$totalpokes = count($pokemon);
	$poke = mt_rand(0, $totalpokes);
	if ($_GET['randompoke'] == 'small') {
		header('Content-Type: image/png');
		readfile('img/small/'.sprintf("%03d",$poke).'.png');
	} elseif ($_GET['randompoke'] == 'shiny') {
		header('Content-Type: image/gif');
		readfile('img/anim2/shiny/'.sprintf("%03d",$poke).'.gif');
	} elseif ($_GET['randompoke'] == 'big') {
		header('Content-Type: image/gif');
		readfile('img/anim/'.sprintf("%03d",$poke).'.gif');
	} elseif ($_GET['randompoke'] == 'huge') {
		header('Content-Type: image/png');
		readfile('img/full/'.sprintf("%03d",$poke).'.png');
	} elseif ($_GET['randompoke'] == 'global') {
		header('Content-Type: image/png');
		readfile('img/global/'.sprintf("%03d",$poke).'.png');
	} else {
		header('Content-Type: image/gif');
		readfile('img/anim2/'.sprintf("%03d",$poke).'.gif');
	}
}
elseif (isset($_GET['trainer'])) {
	if ( empty($_GET['trainer']) || !is_numeric($_GET['trainer']) ) {
		header('Content-Type: image/png');
		readfile('images/nopoke.png');
		exit;
	}
	$trainer = (int)$_GET['trainer'];
	$userdata = userdata($trainer);
	if (empty($userdata)) {
		header('Content-Type: image/png');
		readfile('images/nopoke.png');
		exit;
	}
	header('Content-Type: image/gif');
	readfile('images/trainers/'.$userdata[0]['trainerpic'].'.gif');
}
elseif (isset($_GET['fave'])) {
	if ( empty($_GET['fave']) || !is_numeric($_GET['fave']) ) {
		header('Content-Type: image/png');
		readfile('images/frown.png');
		exit;
	}
	$trainer = (int)$_GET['fave'];
	$userdata = userdata($trainer);
	if (empty($userdata[0]['fave'])) {
			header('Content-Type: image/png');
			readfile('images/nopoke.png');
		} else {
			header('Content-Type: image/gif');
			readfile('img/anim2/'.(is_shiny($userdata[0]['fave']) ? 'shiny/' : '').sprintf("%03d",round($userdata[0]['fave'])).'.gif');
		}
}

elseif (isset($_GET['faveback'])) {
	if ( empty($_GET['faveback']) || !is_numeric($_GET['faveback']) ) {
		header('Content-Type: image/png');
		readfile('images/frown.png');
		exit;
	}
	$trainer = (int)$_GET['faveback'];
	$userdata = userdata($trainer);
	if (empty($userdata[0]['fave'])) {
			header('Content-Type: image/png');
			readfile('images/nopoke.png');
		} else {
			header('Content-Type: image/png');
			readfile('img/back/'.(is_shiny($userdata[0]['fave']) ? 'shiny/' : '').sprintf("%03d",round($userdata[0]['fave'])).'.png');
		}
}
?>