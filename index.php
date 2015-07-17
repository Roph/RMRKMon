<?php //RMRK Pokemon System. Yay.
require_once('settings.php');

if (!file_exists($pcfg['sqlite_db'])) die('Database not found!');

if (isset($_REQUEST['api'])) {
	require_once('api.php');
	die();
}


//First off, perhaps a lucky user has discovered a wild pokemon? Or maybe we're just loading up lists.
if (isset($_REQUEST['ajax'])) {
	require_once('ajax.php');
	exit;
}


require_once('../SSI.php');
require_once('smf.php');
require_once('lib.php');
require_once('libextra.php');
require_once('db.php');
require_once('chance.php');
require_once('misc/bases.php');

//Figure out if this user can administrate.
$pcfg['is_admin'] = false;
	
foreach ($pcfg['admin_groups'] as $allowed) {
	if (in_array($allowed, $user_info['groups'])) {
		$pcfg['is_admin'] = true;
		break;
	}
}

if (isset($_GET['regenerate']) && $context['user']['is_admin']) {
	if (isset($_GET['trainers'])) { $file_db->exec("CREATE TABLE IF NOT EXISTS trainers (
						id INTEGER PRIMARY KEY, 
						trainerpic INTEGER, 
						pokemon TEXT, 
						seen TEXT, 
						dex TEXT, 
						starttime INTEGER, 
						lastcaught INTEGER, 
						trades TEXT, 
						fave INTEGER, 
						badges TEXT, 
						catches INTEGER, 
						sightings INTEGER)");				
		$notice = 'Trainers table (re)built successfully.';
		
	} elseif (isset($_GET['pokemon'])) { $file_db->exec("CREATE TABLE IF NOT EXISTS pokemon (
						id INTEGER PRIMARY KEY, 
						owners TEXT, 
						shiny_owners TEXT, 
						encounters INTEGER, 
						shiny_encounters INTEGER, 
						captures INTEGER, 
						shiny_captures INTEGER
						)");
		$notice = 'Pokemon table (re)built successfully.';
		
	} elseif (isset($_GET['stats'])) { $file_db->exec("CREATE TABLE IF NOT EXISTS stats (
						total_trainers INTEGER, 
						total_encounters INTEGER,
						total_captures INTEGER
						)");
		$notice = 'Stats table (re)built successfully.';
		
	} elseif (isset($_GET['adminlog'])) { $file_db->exec("CREATE TABLE IF NOT EXISTS adminlog (
						id INTEGER PRIMARY KEY, 
						time INTEGER,
						user INTEGER,
						type INTEGER,
						params TEXT,
						extra TEXT
						)");
		$notice = 'Adminlog table (re)built successfully.';

		
	} elseif (isset($_GET['trades'])) { $file_db->exec("CREATE TABLE IF NOT EXISTS trades (
						id INTEGER PRIMARY KEY, 
						trainer1 INT, 
						pokemon1 TEXT, 
						trainer2 INT, 
						pokemon2 TEXT, 
						stage INT, 
						date INT, 
						code INT
						)");
		$notice = 'Trades table (re)built successfully.';
		
	} elseif (isset($_GET['tradelog'])) { $file_db->exec("CREATE TABLE IF NOT EXISTS tradelog (
						id INTEGER PRIMARY KEY, 
						trainer1 INT, 
						trainer2 INT,
						pokemon1 TEXT,
						pokemon2 TEXT,
						date INT
						)");
		$notice = 'Tradelog table (re)built successfully.';
		
	}
}

function layout_above($pagetitle, $pageheader) {
	global $context, $baseurl, $smf_baseurl, $smf_scripturl, $notice;
	echo '<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="chrome=1">
	<link href="style.css" rel="stylesheet" type="text/css" />
	<link href="sprite.css" rel="stylesheet" type="text/css" />
	<link href=\'http://fonts.googleapis.com/css?family=Press+Start+2P\' rel=\'stylesheet\' type=\'text/css\'>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<script src="script.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>';
	if (isset($_GET['trainer']) || isset($_GET['trainer2'])) { echo'
	<script>
		$(function() {
			$( "#maincontent" ).tabs();
		});
	</script>';
	}
	
	echo '
	<script>
		 $(document).ready(function() { $("#pokemonselect").select2(); });
	</script>
	';
	
	echo '
	<script>
		 $(function() {
			$( document ).tooltip({
				position: {
					my: "center bottom-20",
					at: "center top",
					using: function( position, feedback ) {
						$( this ).css( position );
						$( "<div>" )
						.addClass( "arrow" )
						.addClass( feedback.vertical )
						.addClass( feedback.horizontal )
						.appendTo( this );
					}
				}
			});
		});
	</script>';
	
	if (isset($_GET['admin'])) {
		echo '<script type="text/javascript"><!-- // --><![CDATA[
		var smf_theme_url = "'.$smf_baseurl.'Themes/rmrk7";
		var smf_default_theme_url = "'.$smf_baseurl.'Themes/default";
		var smf_images_url = "'.$smf_baseurl.'Themes/rmrk7/images";
		var smf_scripturl = "'.$smf_scripturl.'";
		var smf_iso_case_folding = false;
		var smf_charset = "UTF-8";
		var ajax_notification_text = "Loading...";
		var ajax_notification_cancel_text = "Cancel";
	// ]]></script>';
	}
	
	echo'
	<title>',$pagetitle,'</title>
</head>
<body>
	<div id="wrapper">',isset($notice)? $notice : '','
		<a id="logo" href="."><img src="images/logo',( date('D') == "Sun" ? '_ss' : '' ),'.png" ',( date('D') == "Sun" ? 'title="Shiny Sunday! Encounters have 7% chance to be shiny!"' : '' ),'/></a><h1 id="pagetitle">',isset($pageheader)? $pageheader : '','</h1>
		',$context['user']['is_logged'] ? '<a href="?trainer='.$context['user']['id'].'" style="float:right;clear:right;margin: -20px 10px 5px 5px;" title="Your Trainer Page"><img src="images/captured.png" /></a><a href="?pc" style="float:right;margin: -15px 10px 5px 5px;" title="Your Settings"><img src="images/pc.png" /></a><a href="?trade" style="float:right;margin: -20px 10px 5px 5px;" title="Trade Center"><img src="images/tradebag.png" /></a>' : '','
		<div id="main">';
}

function layout_below() {
		global $context, $pcfg;
		
		echo '
		</div>';
		
		echo '
		<div style="text-align: center; margin-top: 30px; border-top: 5px dashed #555; padding-top: 30px; font-size: 8px; line-height: 14px;">
		
			<a href="?help" style="float:right;margin-right:20px;"><img src="images/help.png" title="RMRKMon Help" /></a>
			<a href="http://rmrk.net/?board=270">RMRKMon Board</a> | RMRKMon is an <a href="http://rmrk.net/">RMRK</a> feature.<br>
			Pokémon &copy;1995-2014 Nintendo, Creatures and GAME FREAK&trade;</div>';
		
		if ($pcfg['is_admin']) echo '<div style="text-align: center; margin-top: 30px; border-top: 5px dashed #555; padding-top: 30px; padding-bottom: 30px;"><a href="?admin"><img src="images/key.png" title="Administrate"></a></div>';
		
	echo'
	</div>
</body>
</html>';
}

function trainer_box($id) {
	global $file_db, $userdata, $context, $smf_userdata, $admin_users, $baseurl, $pcfg, $pokemon;
	
	if (isset($userdata)) {
		if ($id != $userdata[0]['id']) {
			$userdata = userdata($id);
			echo '<!-- ID mismatch, reloaded userdata for trainerbox -->';
		}
	}
	
	$smf_userdata = pokemon_fetchMember($member_ids = $id, $output_method = 'array');
	
	echo '
	<div class="trainer_box pokeborder">
		'.( empty($userdata[0]['version']) ? '' : '<span style="color:'.$pcfg[ 'color'.$userdata[0]['version'] ].';" title="'.$pcfg[ 'version'.$userdata[0]['version'] ].'">&bull;</span>').'',$smf_userdata[$id]['name'],' <a href="http://rmrk.net/?action=profile;u='.$id.'"><img src="images/rmrk_link.png" alt="RMRK Profile" /></a><hr>
		',isset($userdata[0]['fave']) ? '<p class="trainerfave">' : '','
		<img src="images/trainers/',$userdata[0]['trainerpic'],'.gif" class="trainerpic"/>';
		
		if (isset($userdata[0]['fave'])) {
			echo '<img class="trainer_fave_pokemon" src="'.$baseurl.'img/anim2/'.(is_shiny($userdata[0]['fave']) ? 'shiny/' : '').sprintf("%03d",round($userdata[0]['fave'])).'.gif" />';
		}
		
		
		echo'
		',isset($userdata[0]['fave']) ? '</p>' : '','';
		
		if (!empty($userdata[0]['extrafave'])) {
			echo '<br>';
			$userfaves = explode(',',$userdata[0]['extrafave']);
			foreach ($userfaves as $ufave) {
				echo '<img src="'.$baseurl.'img/small/'.(is_shiny($ufave) ? 'shiny/' : '').sprintf("%03d",round($ufave)).'.png" style="background:url(images/team_ball.png) center center no-repeat;" title="'.$pokemon[round($ufave)].(is_shiny($ufave) ? ' (Shiny)' : '').'">';
			}
		}
		
		echo'
		<hr>';
		badge_strip($id, $userdata);
		echo'
		',empty($userdata[0]['lastcaught']) ? '' : '<div>Last Caught:<span> '.date('M jS, Y', $userdata[0]['lastcaught']).'</span></div>','
		<div>Trainer Since:<span>',date('M jS, Y', $userdata[0]['starttime']),'</span></div><br><br><hr>
		',empty($userdata[0]['seen']) ? '' : '<div>Pokemon Seen:<span> '.count(explode(',',$userdata[0]['seen'])).'</span></div>','
		',empty($userdata[0]['pokemon']) ? '' : '<div>Pokemon Owned:<span> '.count(explode(',',$userdata[0]['pokemon'])).'</span></div>','
		',empty($userdata[0]['dex']) ? '' : '<div>Pokedex Entries:<span> '.count(explode(',',$userdata[0]['dex'])).'</span></div>','';
		
		if( ($context['user']['id'] == $id || $pcfg['is_admin'] == true) || ($userdata[0]['opentrade'] == 1 || in_array($context['user']['id'], $smf_userdata[$id]['buddies'])) && ($context['user']['is_logged']) ) {
			echo '<br><br><br><hr>';
			
			if ($context['user']['id'] == $id || $pcfg['is_admin'] == true) echo'<a style="margin: 0px auto;" href="?release='.$id.'"><img src="images/releasep.png" title="Release Pokemon" /></a> ';
			if ( ($userdata[0]['opentrade'] == 1 || in_array($context['user']['id'], $smf_userdata[$id]['buddies']) ) && $id != $context['user']['id']) echo' <a style="margin: 0px auto;" href="?trade;open='.$id.'"><img src="images/tradeball.png" title="Trade with ',$smf_userdata[$id]['name'],'" /></a>';
			if (!empty($userdata[0]['pokemon']) && $context['user']['id'] != $id) echo ' <a href="?compare='.$id.'"><img src="images/poke_diff.png" title="Compare Pokemon" /></a>';
		}
		
	echo'</div>';
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

if (isset($_GET['trainer2'])) {
	
	$id = (int)$_GET['trainer2'];
	
	//Who is this user?
	$smf_userdata = ssi_fetchMember($member_ids = $id, $output_method = 'array');
	if (empty($smf_userdata)) die('That user doesn\'t exist!');
	
	//Does this user's pokemon trainer alter-ego exist?
	$userdata = userdata($id);
	
	if (empty($userdata)) {
		layout_above('Trainer Profile: '.$smf_userdata[$id]['name'], $smf_userdata[$id]['name']);
		echo 'This user hasn\'t seen or caught any pokemon yet, sorry!';
		layout_below();
	} else { //We have something to deal with
		layout_above('Trainer Profile: '.$smf_userdata[$id]['name'], 'Trainer info: '.$smf_userdata[$id]['name']);
		
		if (!empty($userdata[0]['fave'])) {
			echo '<div id="faveback" style="background:url(img/global900/',sprintf("%03d",round($userdata[0]['fave'])),'.png) 100% 0% no-repeat;"></div>';
		}
		
		echo '<div style="text-align:center; margin-bottom: 10px; overflow:auto;" id="maincontent">';
		
		trainer_box($id);
		
		echo '<ul id="sectlinks">';
		
		if (!empty($userdata[0]['pokemon'])) echo '<li><a style="background:#3890D8 url(images/bg_blue.png) bottom left repeat-x;color:#fff;" class="sectionlink pokeborder" href="#pokemon">Pokemon<img src="images/captured.png" /></a></li>
		';
		if (!empty($userdata[0]['seen'])) echo '<li><a style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;color:#fff;" class="sectionlink pokeborder" href="#seen">Sightings<img src="images/seen.png" /></a></li>
		';
		if (!empty($userdata[0]['dex'])) echo '<li><a style="background:#D86D37 url(images/bg_red.png) bottom left repeat-x;color:#fff;" class="sectionlink pokeborder" href="#dex">Pokedex<img src="images/pokedex.png" /></a></li>
		';
		
		echo '<div style="clear:both;"></div></ul>
		<script type=\'text/javascript\'>//<![CDATA[ 
			$(window).load(function(){
				$(\'input\').keyup(function() {
					filter(this); 
				});

				function filter(element) {
					var value = $(element).val();
					$(".pbox").each(function () {
						if ($(this).text().indexOf(value) > -1) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});
				}
			});//]]>  

		</script>
		
		<div style="text-align:center; font-size: 8px; float:left; clear:both;">Filter: <input type="text"></div>';
		
		if (!empty($userdata[0]['pokemon'])) {
			echo '
			<div id="pokemon">';
			
			echo '
			<div style="height: 20px;clear:both;"></div><div style="clear:both;padding: 20px 0px; text-align: center; border-top: 5px dashed #555;">Owned Pokemon:</div>
			
			
			';
				
				$owned_pokemon = explode(',',$userdata[0]['pokemon']);
				
				sort($owned_pokemon);
				
				//We only wish to show each pokemon once, but still recognise duplicates.
				$dupe_check = array_count_values($owned_pokemon);
				
				foreach ($owned_pokemon as $poke) {
					
					if (isset($dupe_shown)) {
						if (in_array($poke, $dupe_shown)) continue; 
					}
					
					echo '
					<div class="pbox pokeborder',(is_shiny($poke) ? ' shiny' : ''),'"><div class="pokenumber">#',round($poke),'</div>',cprite($poke, true),'<br>
					<span',(is_shiny($poke) ? ' class="shiny flc"><span style="font-size:0;">!</span>' : ' class="flc">').strtolower($pokemon[round($poke)]),'</span>',$dupe_check[$poke] > 1 ? '<div class="multipoke"><img src="'.$baseurl.'images/pokeballsmall.png" />'.$dupe_check[$poke].'</div>' : '','</div>';
					
					if ($dupe_check[$poke] > 1) {
						$dupe_shown[] = $poke;
					}
				}
			echo '
			</div>';
		}
		
		if (!empty($userdata[0]['seen'])) {
			echo '
			<div id="seen">';
			
			echo '
			<div style="height: 20px;clear:both;"></div><div style="clear:both;padding: 20px 0px; text-align: center; border-top: 5px dashed #555;">Seen Pokemon:</div>';
				
			$seen_pokemon = explode(',',$userdata[0]['seen']);
			
			//To distinguish pokemon that they have only seen but not either owned or currently own, we'll compare to their pokedex.
			$dex_pokemon = explode(',',$userdata[0]['dex']);
			
			sort($seen_pokemon);
			
			foreach ($seen_pokemon as $poke) {
				echo '
				<div class="pbox pokeborder',(is_shiny($poke) ? ' shiny' : ''),'"><div class="pokenumber">#',round($poke),'</div>',cprite($poke, true),'<br>
				<span class="flc">',(is_shiny($poke) ? '<span style="font-size:0;">!</span>' : ''),'',strtolower($pokemon[round($poke)]),'</span></div>';
			}
				echo '
				</div>';
		}
		
		if (!empty($userdata[0]['dex'])) {
			echo '
			<div id="dex">';
			
			echo '
			<div style="height: 20px;clear:both;"></div><div style="clear:both;padding: 20px 0px; text-align: center; border-top: 5px dashed #555;">Pokedex:</div>';
		
			$dex_pokemon = explode(',',$userdata[0]['dex']);
			
			sort($dex_pokemon);
			
			foreach ($dex_pokemon as $poke) {
				echo '
				<div class="pbox pokeborder"><div class="pokenumber">#',round($poke),'</div>',cprite($poke, true),'<br>
				<span class="flc">',strtolower($pokemon[round($poke)]),'</span></div>';
			}
		}
		
		echo '</div>';
		layout_below();
	}
	
	exit;
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

if (isset($_GET['trainer'])) {
	
	$id = (int)$_GET['trainer'];
	
	//Who is this user?
	$smf_userdata = ssi_fetchMember($member_ids = $id, $output_method = 'array');
	if (empty($smf_userdata)) die('That user doesn\'t exist!');
	
	//Does this user's pokemon trainer alter-ego exist?
	$userdata = userdata($id);
	
	if (empty($userdata)) {
		layout_above('Trainer Profile: '.$smf_userdata[$id]['name'], $smf_userdata[$id]['name']);
		echo 'This user hasn\'t seen or caught any pokemon yet, sorry!';
		layout_below();
	} else { //We have something to deal with
	
		layout_above('Trainer Profile: '.$smf_userdata[$id]['name'], 'Trainer info: '.$smf_userdata[$id]['name']);
		
		if (!empty($userdata[0]['fave'])) {
			echo '<div id="faveback" style="background:url(img/global900/',sprintf("%03d",round($userdata[0]['fave'])),'.png) 100% 0% no-repeat;"></div>';
		}
		
		echo '<div style="text-align:center; margin-bottom: 10px; overflow:auto;" id="maincontent">';
		
		//Trainer stuff goes here.
		if (!empty($userdata[0]['pokemon'])) {
			$owned_pokemon = explode(',',$userdata[0]['pokemon']);
			sort($owned_pokemon);
			$total_owned = count($owned_pokemon);
		} else $total_owned = 0;
		
		if (!empty($userdata[0]['seen'])) {
			$seen_pokemon = explode(',',$userdata[0]['seen']);
			sort($seen_pokemon);
			$total_seen = count($seen_pokemon);
		} else $total_seen = 0;
		
		if (!empty($userdata[0]['dex'])) {
			$dex_pokemon = explode(',',$userdata[0]['dex']);
			sort($dex_pokemon);
			$total_dex = count($dex_pokemon);
			$percent_dex = round( ((count($dex_pokemon) / count($pokemon)) * 100), 2 );
		} else {
			$total_dex = 0;
			$percent_dex = 0;
		}
		
		if (empty($userdata[0]['trades'])) $total_trades = 0;
		else {
			$trades = explode(',', $userdata[0]['trades']);
			$total_trades = count($trades);
		}
		
		//echo '<!-- ',print_r($userdata),' -->';
		
		echo '<h1 style="text-align:left;font-family: \'pkmn\';font-size:22px;border-bottom: 2px dotted #555;padding-bottom: 2px;">',( empty($userdata[0]['version']) ? '' : '<span style="color:'.$pcfg[ 'color'.$userdata[0]['version'] ].'; font-size:28px;line-height:1px;text-shadow: 0px 0px 1px #fff;" title="'.$pcfg[ 'version'.$userdata[0]['version'] ].'">&bull;</span>'),' ',$smf_userdata[$id]['name'],' ',badge_strip($id, $userdata, $output_method = "echo_nocruft"),'</h1><h1 style="font-family: \'pkmn\';font-size:22px;border-bottom: 2px dotted #555;padding: 2px 0; overflow:auto;text-align:left;"><div class="trainer2headericon">
		<img src="images/captured1x.png" title="Number of pokemon owned" />',$total_owned,'
		<img src="images/seen1x.png" title="Number of different pokemon seen" />',$total_seen,'
		<img src="images/pokedex1x.png" title="Pokedex completion" />',$percent_dex,'%
		<img src="images/sightings1x.png" title="Encounters" />',number_format($userdata[0]['sightings']),'
		<img src="images/catches1x.png" title="Catches" />',number_format($userdata[0]['catches']),'
		<img src="images/trades1x.png" title="Trades Performed" />',number_format($total_trades),'</div>';
		
		if( ($context['user']['id'] == $id || $pcfg['is_admin'] == true) || ($userdata[0]['opentrade'] == 1 || in_array($context['user']['id'], $smf_userdata[$id]['buddies'])) && ($context['user']['is_logged']) ) {
			if ($context['user']['id'] == $id || $pcfg['is_admin'] == true) echo'<a href="?release='.$id.'"><img src="images/releasep.png" title="Release Pokemon" /></a> ';
			if ( ($userdata[0]['opentrade'] == 1 || in_array($context['user']['id'], $smf_userdata[$id]['buddies']) ) && $id != $context['user']['id']) echo' <a href="?trade;open='.$id.'"><img src="images/tradeball.png" title="Trade with ',$smf_userdata[$id]['name'],'" /></a>';
			if (!empty($userdata[0]['pokemon']) && $context['user']['id'] != $id) echo ' <a href="?compare='.$id.'"><img src="images/poke_diff.png" title="Compare Pokemon" /></a>';
		}
		
		
		echo'
		</h1>';
		
		//Perhaps our trainer has chosen a favourite type? Else just default to normal (13)
		if (empty($userdata[0]['favetype'])) $userdata[0]['favetype'] = 13;
		
		echo '
		<div id="trainerstage',isset($_GET['xy']) ? '-xy' : '','" style="background: url(',$baseurl,'images/stagebg/',$userdata[0]['favetype'],'.png) no-repeat center center;"><span class="valignhelper"></span><img src="images/trainers/',$userdata[0]['trainerpic'],'.gif" class="stagetrainer"/>';
		
		if (isset($_GET['xy'])) $giftype = '';
		else $giftype = '2';
			
			//While testing, no checking.
			if (!empty($userdata[0]['fave'])) echo '<img src="'.$baseurl.'img/anim',$giftype,'/'.(is_shiny($userdata[0]['fave']) ? 'shiny/' : '').sprintf("%03d",round($userdata[0]['fave'])).'.gif" />';
			
			if (!empty($userdata[0]['extrafave'])) {
			
				$efaves = explode(',',$userdata[0]['extrafave']);
				foreach ($efaves as $ef) {
					echo '<img src="'.$baseurl.'img/anim',$giftype,'/'.(is_shiny($ef) ? 'shiny/' : '').sprintf("%03d",round($ef)).'.gif" />';
				}
				
			}
			
			echo'
		</div>';
		
		
		
		//print_r($userdata);
		
		echo '<hr style="clear:both;">';
		
		
		
		echo'
		<ul id="sectlinks2">';
		
		if (!empty($userdata[0]['pokemon'])) echo '<li style="background:#3890D8 url(images/bg_blue.png) bottom left repeat-x;color:#fff;" class="pokeborder"><a class="sectionlink2" href="#pokemon">Pokemon<img src="images/captured.png" /></a></li>
		';
		if (!empty($userdata[0]['seen'])) echo '<li style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;color:#fff;" class="pokeborder"><a class="sectionlink2" href="#seen">Sightings<img src="images/seen.png" /></a></li>
		';
		if (!empty($userdata[0]['dex'])) echo '<li style="background:#D86D37 url(images/bg_red.png) bottom left repeat-x;color:#fff;" class="pokeborder"><a class="sectionlink2" href="#dex">Pokedex<img src="images/pokedex.png" /></a></li>
		';
		
		echo '</ul>
		<script type=\'text/javascript\'>//<![CDATA[ 
			$(window).load(function(){
				$(\'input\').keyup(function() {
					filter(this); 
				});

				function filter(element) {
					var value = $(element).val();
					$(".pbox").each(function () {
						if ($(this).text().indexOf(value) > -1) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});
				}
			});//]]>  

		</script>
		
		<div style="text-align:center; font-size: 8px; float:left; clear:both;">Filter: <input type="text"></div>';
		
		if (!empty($userdata[0]['pokemon'])) {
			echo '
			<div id="pokemon">';
			
			echo '
			<div style="clear:both;text-align: center;">Owned Pokemon:</div><br>
			';

				//We only wish to show each pokemon once, but still recognise duplicates.
				$dupe_check = array_count_values($owned_pokemon);
				
				foreach ($owned_pokemon as $poke) {
					
					if (isset($dupe_shown)) {
						if (in_array($poke, $dupe_shown)) continue; 
					}
					
					echo '
					<div class="pbox pokeborder',(is_shiny($poke) ? ' shiny' : ''),'"><div class="pokenumber">#',round($poke),'</div>',cprite($poke, true),'<br>
					<span',(is_shiny($poke) ? ' class="shiny flc"><span style="font-size:0;">!</span>' : ' class="flc">').strtolower($pokemon[round($poke)]),'</span>',$dupe_check[$poke] > 1 ? '<div class="multipoke"><img src="'.$baseurl.'images/pokeballsmall.png" />'.$dupe_check[$poke].'</div>' : '','</div>';
					
					if ($dupe_check[$poke] > 1) {
						$dupe_shown[] = $poke;
					}
				}
			echo '
			</div>';
		}
		
		if (!empty($userdata[0]['seen'])) {
			echo '
			<div id="seen">
			<div style="clear:both;text-align: center;">Seen Pokemon:</div><br>';

			//To distinguish pokemon that they have only seen but not either owned or currently own, we'll compare to their pokedex.
			foreach ($seen_pokemon as $poke) {
				echo '
				<div class="pbox pokeborder',(is_shiny($poke) ? ' shiny' : ''),'"><div class="pokenumber">#',round($poke),'</div>',cprite($poke, true),'<br>
				<span class="flc">',(is_shiny($poke) ? '<span style="font-size:0;">!</span>' : ''),'',strtolower($pokemon[round($poke)]),'</span></div>';
			}
			echo '
			</div>';
		}
		
		if (!empty($userdata[0]['dex'])) {
			echo '
			<div id="dex">
			<div style="clear:both;text-align: center;">Pokedex:</div><br>';
			
			foreach ($dex_pokemon as $poke) {
				echo '
				<div class="pbox pokeborder"><div class="pokenumber">#',round($poke),'</div>',cprite($poke, true),'<br>
				<span class="flc">',strtolower($pokemon[round($poke)]),'</span></div>';
			}
		}
		
			echo '
			</div>';
			
		layout_below();
		
	}
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['pokemon'])) { //Let trainers see who owns what pokemon, how many times this pokemon has been caught / encountered and so on.
	
	$poke = (int)$_GET['pokemon'];
	
	if(!array_key_exists($poke, $pokemon)) {
		layout_above('Error','No Pokemon');
		echo 'No pokemon with that number! It may be that the system has not added that generation of pokemon yet.';
		layout_below();
		exit;
	}
	
	$pokedata = pokemondata($poke);
	$has_trainers = false;
	
	//Before we go further, if there are any owners or shiny owners we want to hit SMF to get their usernames.
	$user_ids = array();
	
	if (!empty($pokedata[0]['owners'])) {
		
		//Our pokemon's owner and shiny_owner strings need to be arrays before we can deal with them.
		$poke_owners = explode(',',$pokedata[0]['owners']);

		if (!empty($poke_owners)) {
			foreach ($poke_owners as $poke_owner) {
				if (!in_array($poke_owner, $user_ids)) $user_ids[] = $poke_owner;
			}
			$has_trainers = true;
		}
	}
	
	if (!empty($pokedata[0]['shiny_owners'])) {
	
		$poke_shiny_owners = explode(',',$pokedata[0]['shiny_owners']);
		
		if (!empty($poke_shiny_owners)) {
			foreach ($poke_shiny_owners as $poke_shiny_owner) {
				if (!in_array($poke_shiny_owner, $user_ids)) $user_ids[] = $poke_shiny_owner;
			}
			$has_trainers = true;
		}
	}
		
		//Good to go. Assuming the pokemon is popular, this can unavoidably become a VERY! large query. Consider a custom SSI-style function that only returns what we need; ['name']!
		if ($has_trainers == true) {
			$smf_userdata = pokemon_fetchMember($member_ids = $user_ids, $output_method = 'array'); 
		}
	
	
	layout_above('Pokemon Data for '.$pokemon[$poke], '<img src="img/small/'.sprintf("%03d",$poke).'.png" /> '.$pokemon[$poke]);
	
	echo '<div id="faveback" style="background:url(img/global900/'.sprintf("%03d",round($poke)).'.png) 90% 0% no-repeat;"></div>';
	
	//If this is from a yet-to-be-enabled gen, say so.
	if ($poke > $pcfg['pokemon_highest_enabled']) echo '<div class="pokeborder standard" style="line-height: 20px;">'.$info_symbol.' <span style="font-size:8px;">This pokemon is uncatchable until its generation is enabled.</span></div>';
	
	//Show a fancy animated sprite of our pokemon, freshly ripped from X&Y!
	echo '<img style="display:block; margin: 0 auto;" src="img/anim/'.sprintf("%03d",$poke).'.gif" />';
	
	echo '<div style="text-align:center;padding: 5px 0px;">';
	foreach ($p_type[$poke] as $ptype) {
		echo '<a href="?type='.$type[$ptype].'"><img src="'.$baseurl.'images/types/'.$ptype.'.gif" /></a> ';
	}
	echo '</div>';
	
	//Show a little box to allow quick navigation to the previous / next pokemon.
	echo '
	<div style="font-size: 8px; padding: 10px; margin: 0 auto;width: 70%; text-align: center; border: 2px dotted #555; margin-bottom: 10px;line-height: 26px;max-width: 600px;overflow:auto;">
		', array_key_exists(($poke - 1), $pokemon) ? '<a style="float:left;text-decoration:none;" href="?pokemon='.($poke - 1).'">&lt;-#'.sprintf("%03d",($poke - 1)).' '.$pokemon[($poke - 1)].'</a>' : '' ,'';
		
		echo '
		<form method="get" action="'.$baseurl.'" style="display:inline;margin-left: -15px;"><select id="pokemonselect" class="s2select" name="pokemon" data-placeholder="Select Pokemon">';
		
	foreach ($pokemon as $key => $selpoke) {
		echo '
		<option'. ($key == $poke ? ' selected="selected"' : '') .' value="'.$key.'">#'.sprintf("%03d",$key).' '.$selpoke.'</option>';
	}
		
	echo'
	</select> <input type="submit" value="Go"></form>';
		
		
		echo'
		', array_key_exists(($poke + 1), $pokemon) ? '<a style="float:right;text-decoration:none;" href="?pokemon='.($poke + 1).'">#'.sprintf("%03d",($poke + 1)).' '.$pokemon[($poke + 1)].'-&gt;</a>' : '' ,'';
		
		
		if (!empty($evolves_from[$poke]) or !empty($evolves_to[$poke])) {
			echo '<hr>';
			
			if (!empty($evolves_from[$poke])) echo '<div style="float:left;width:49%;border-right: 2px dotted #555;">
				Evolves from: <a href="?pokemon='.$evolves_from[$poke].'">'.$pokemon[$evolves_from[$poke]].'</a>
			</div>';
			
			if (!empty($evolves_to[$poke])) {
				echo '<div style="float:right;width:49%;border-left:2px dotted #555;">Evolves to:';
				foreach ($evolves_to[$poke] as $epoke) {
					echo ' <a href="?pokemon='.$epoke.'">'.$pokemon[$epoke].'</a>';
				}
				echo '</div>';
				
			}
			
		}
		
	echo'
	</div>
	
	<span style="display:block; clear:both; font-size: 8px; text-align:center; padding: 10px 0px;line-height: 20px;"><img src="images/bulba.png" style="margin-right: 5px;" alt="Bulbapedia Article" /><a href="http://bulbapedia.bulbagarden.net/wiki/'.$pokemon[$poke].'" target="_blank">'.$pokemon[$poke].'</a><br>';
	
	echo'</span>';
	
	//Show two boxes, one for normal and one for shiny.
	echo '
	<div class="pokeborder pokeinfo" style="float:left;">
		<span class="pokename">',( $affinity[$poke] == 1 ? '<span style="color:'.$pcfg['color1'].';" title="Only '.$pcfg['version1'].' trainers encounter this pokemon">&bull;</span>' : '' ),'',( $affinity[$poke] == 2 ? '<span style="color:'.$pcfg['color2'].';" title="Only '.$pcfg['version2'].' trainers encounter this pokemon">&bull;</span>' : '' ),''.$pokemon[$poke].'</span><hr>
		<div style="text-align: center; background:url('.$baseurl.'images/bases/'.$p_base[$poke].'.png) center 95% no-repeat;"><img src="img/anim2/'.sprintf("%03d",$poke).'.gif" /></div><hr style="clear:both;">
		<textarea wrap="off" id="poken" style="width: 80%; height: 12px; padding: 2px; font-size: 8px;overflow:hidden;font-family: \'Press Start 2P\', sans-serif;" onclick="SelectAll(\'poken\')">[url='.$baseurl.'?pokemon='.$poke.';'.$pokemon[$poke].'][img]'.$baseurl.'img/named/'.sprintf("%03d",$poke).'.png[/img][/url]</textarea><hr>
		<div>Times Encountered:<span>',$pokedata[0]['encounters'],'</span></div>
		<div>Times Captured:<span>',$pokedata[0]['captures'],'</span></div>
		<div>Encounter Chance:<span>',$e_chance[$poke],'%</span></div>
		<br style="clear:both;">';
		if (!empty($poke_owners)) {
			echo'
			<hr>
			Trainers who own this pokemon: 
			<div style="line-height: 16px;">';
			foreach ($poke_owners as $owner) {
				echo '<a href="?trainer='.$owner.'">'.$smf_userdata[$owner]['name'].'</a> ';
			}
			echo'
			</div>';
		}
	echo'
	</div>';
	
	//And now the shiny one.
	echo '
	<div class="pokeborder pokeinfo" style="float:right;">
		<span class="pokename">',( $affinity[$poke] == 1 ? '<span style="color:'.$pcfg['color1'].';" title="Only '.$pcfg['version1'].' trainers encounter this pokemon">&bull;</span>' : '' ),'',( $affinity[$poke] == 2 ? '<span style="color:'.$pcfg['color2'].';" title="Only '.$pcfg['version2'].' trainers encounter this pokemon">&bull;</span>' : '' ),'Shiny '.$pokemon[$poke].'</span><hr>
		<div style="text-align: center; background:url('.$baseurl.'images/bases/'.$p_base[$poke].'.png) center 95% no-repeat;"><img src="img/anim2/shiny/'.sprintf("%03d",$poke).'.gif" /></div><hr>
		<textarea wrap="off" id="pokens" style="width: 80%; height: 12px; padding: 2px; font-size: 8px;overflow:hidden;font-family: \'Press Start 2P\', sans-serif;" onclick="SelectAll(\'pokens\')">[url='.$baseurl.'?pokemon='.$poke.';'.$pokemon[$poke].'(Shiny)][img]'.$baseurl.'img/named/shiny/'.sprintf("%03d",$poke).'.png[/img][/url]</textarea><hr>
		<div>Times Encountered:<span>',$pokedata[0]['shiny_encounters'],'</span></div>
		<div>Times Captured:<span>',$pokedata[0]['shiny_captures'],'</span></div>
		<div>Encounter Chance:<span>1% of ',$e_chance[$poke],'%</span></div><br style="clear:both;">';
		if (!empty($poke_shiny_owners)) {
			echo'
			<hr>
			Trainers who own this pokemon: 
			<div style="line-height: 16px;">';
			foreach ($poke_shiny_owners as $shiny_owner) {
				echo '<a href="?trainer='.$shiny_owner.'">'.$smf_userdata[$shiny_owner]['name'].'</a> ';
			}
			echo'
			</div>';
		}
	echo'
	</div>';
	
	echo '<div style="clear:both;"></div>';
	
	layout_below();
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['type'])) { //Just list pokemon according to a certain type.
	
	//To be user friendly, we're linking type via text. Do a reverse lookup to convert it back to the ID.
	$vtype = array_search($_GET['type'], $type);
	
	if (empty($vtype)) {
		layout_above('Error','Error');
		echo 'No such type';
		layout_below();
		exit;
	}
	
	//We'll want to let the user check off their pokemon against this list. If we have a user viewing this page, at least.
	if ($context['user']['is_logged']) {
		$userdata = userdata($context['user']['id']);
		$seen = explode(',',$userdata[0]['seen']);
		$seen = array_map('round', $seen); //We don't care about shinies here.
		
		$owned = explode(',',$userdata[0]['pokemon']);
		$owned = array_map('round', $owned); //Or here!
		
		$dex = explode(',',$userdata[0]['dex']);
	}
	
	layout_above($type[$vtype].' type Pokemon',$type[$vtype].' type Pokemon');
	
	//First, let users quickly choose other types.
	echo '
	<div class="pokeborder standard" style="text-align:center;margin-bottom: 30px;"><h3>All Types</h3>
		';
		foreach ($type as $tkey => $ttype) {
			echo '<a href="?type='.$ttype.'"><img src="'.$baseurl.'images/types/'.$tkey.'.gif" style="padding:5px;" /></a>';
		}
		echo'
	</div>';
	
	echo '<h2 style="text-align:center;font-size:24px;">'.$type[$vtype].' type Pokemon</h2>';
	
	//We want a 2 column list here. Pokes that have $vtype as a main, then as a secondary type.
	echo '
	<div id="submain" style="background:url(images/vertical5px.png) top center repeat-y;overflow:auto;padding-bottom: 20px;">';
	
	//First, pokemon who have this type as their main type.
	echo '
	<div class="trade" style="float:left;">
		<h2 style="margin-bottom:8px;border-bottom:none;">Main Type</h2>';
		
	foreach ($pokemon as $key => $poke) {
		if ($p_type[$key][0] == $vtype) {
			echo '<img src="'.$baseurl.'img/anim2/'.sprintf("%03d",$key).'.gif" style="float:left;border-top:2px dotted #555;"><h3 style="margin-left:96px;border-top:2px dotted #555;padding-top:4px;"><a href="?pokemon='.$key.'">#'.sprintf("%03d",$key).' '.$pokemon[$key].'</a>';
			
			//As we're floating these, we need to reverse the order so they appear in the correct order again. If that makes sense. Uh
			$ftype = array_reverse($p_type[$key]);
			foreach ($ftype as $ptype) {
			echo '<img src="'.$baseurl.'images/types/'.$ptype.'.gif" style="float:right;padding-top:6px;" />';
			}
			echo '</h3>';
			
			if (isset($userdata)) { //If our viewer is logged in, let them check ownership.
				
				echo '<div style="text-align:center;">';
				
				if (in_array($key, $seen)) echo '<img src="'.$baseurl.'images/l_seen_yes.png" /> ';
					else echo '<img src="'.$baseurl.'images/l_seen_no.png" /> ';
					
				if (in_array($key, $owned)) echo '<img src="'.$baseurl.'images/l_owned_yes.png" /> ';
					else echo '<img src="'.$baseurl.'images/l_owned_no.png" /> ';
					
				if (in_array($key, $dex)) echo '<img src="'.$baseurl.'images/l_dex_yes.png" />';
					else echo '<img src="'.$baseurl.'images/l_dex_no.png" />';
					
				echo '</div>';
			}
			
			echo'
			<div style="clear:both;padding:5px 0px;"></div>';
		}
	}
		
		echo'
	</div>';
	
	//Now, pokemon who have this type as their secondary type.
	echo '
	<div class="trade" style="float:right;">
		<h2 style="margin-bottom:8px;border-bottom:none;">Secondary Type</h2>';
		
	foreach ($pokemon as $key => $poke) { //Not all pokes have secondary types.
		if (array_key_exists(1, $p_type[$key])) {
			if ($p_type[$key][1] == $vtype) {
				echo '<img src="'.$baseurl.'img/anim2/'.sprintf("%03d",$key).'.gif" style="float:left;border-top:2px dotted #555;"><h3 style="margin-left:96px;border-top:2px dotted #555;padding-top:4px;"><a href="?pokemon='.$key.'">#'.sprintf("%03d",$key).' '.$pokemon[$key].'</a>';
				
				//As we're floating these, we need to reverse the order so they appear in the correct order again. If that makes sense. Uh
				$ftype = array_reverse($p_type[$key]);
				foreach ($ftype as $ptype) {
				echo '<img src="'.$baseurl.'images/types/'.$ptype.'.gif" style="float:right;padding-top:6px;" />';
				}
				echo '</h3>';
				
				if (isset($userdata)) { //If our viewer is logged in, let them check ownership.
					
					echo '<div style="text-align:center;">';
					
					if (in_array($key, $seen)) echo '<img src="'.$baseurl.'images/l_seen_yes.png" /> ';
						else echo '<img src="'.$baseurl.'images/l_seen_no.png" /> ';
						
					if (in_array($key, $owned)) echo '<img src="'.$baseurl.'images/l_owned_yes.png" /> ';
						else echo '<img src="'.$baseurl.'images/l_owned_no.png" /> ';
						
					if (in_array($key, $dex)) echo '<img src="'.$baseurl.'images/l_dex_yes.png" />';
						else echo '<img src="'.$baseurl.'images/l_dex_no.png" />';
						
					echo '</div>';
				}
				
				echo'
				<div style="clear:both;padding:5px 0px;"></div>';
			}
		}
	}	
		
		echo'
	</div>';
	
	
		echo'
	</div>';
	
	layout_below();
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['pc'])) { //Geddit? This is basically a control panel, manage our account, release pokemon and the like.
	
	//We're not doing squat if you're not logged in.
	if ($context['user']['is_guest']) {
		layout_above('Error','Not Logged in');
		layout_below();
		die();
	}
	
	//You also need to have a trainer profile to be able to manage it.
	$userdata = userdata($context['user']['id']);
	if (empty($userdata)) {
		layout_above('Error','No trainer profile');
		echo 'You\'re not a pokemon trainer if you\'ve never encountered or caught any pokemon!';
		layout_below();
		die();
	}
	
	if (isset($_GET['team'])) {
		
		if (isset($_POST['poke_pick'])) {
		
			if ($_POST['sesc'] != $context['session_id']) { //We're still checking sessions though.
				layout_above('Error','Error');
				echo 'Something went wrong. Try logging out and back in.';
				layout_below();
				exit;
			}
			
			if (empty($_POST['poke_pick'])) { //Submitted an empty form? Dolt.
				layout_above('Error','Error');
				echo 'No Pokemon Selected!';
				layout_below();
				exit;
			}
			
			$userpokemon = explode(',', $userdata[0]['pokemon']);
			
			//We should still validate that they own the pokemon. This also weeds out malicious submissions.
			foreach ($_POST['poke_pick'] as $picked_poke) {
				if (in_array($picked_poke, $userpokemon)) {
					foreach ($userpokemon as $key => $value) {
						if ($value == $picked_poke) {
							unset($userpokemon[$key]);
							break;
						}
					}
				} else {
					layout_above('Error','Error');
					echo 'Unowned pokemon submitted';
					layout_below();
					exit;
				}
			}
			
			//All safe, all confirmed, good to go.
			$submitted_pokemon = implode(',', $_POST['poke_pick']);
			
			update_trainer($context['user']['id'], 'extrafave', $submitted_pokemon);
			
			//Now confirm for them their new team.
			layout_above('Team Updated','Team Updated');
			
			echo 'Here is your new team:<br><br>
			<div style="text-align:center;">';
			
			echo '<img src="'.$baseurl.'images/trainers/'.$userdata[0]['trainerpic'].'.gif"/><img src="'.$baseurl.'img/anim2/'.(is_shiny($userdata[0]['fave']) ? 'shiny/' : '' ).''.sprintf("%03d", $userdata[0]['fave']).'.gif"/>';
			
			foreach ($_POST['poke_pick'] as $poke) {
				echo '<img src="'.$baseurl.'img/anim2/'.(is_shiny($poke) ? 'shiny/' : '' ).''.sprintf("%03d", $poke).'.gif" style="margin-left:-10px;" />';
			}
			
			echo '</div>';
			
			layout_below();
			exit;
		
		}
		
		layout_above('Choose Team','Choose Team');
		echo 'Choose 5 Pokemon from your collection to join your '.$pokemon[round($userdata[0]['fave'])].'.<br><br>';
		
		echo '
			<script type="text/javascript">
				jQuery(function(){
					var max = 5;
					var checkboxes = $(\'input[type="checkbox"]\');

					checkboxes.change(function(){
						var current = checkboxes.filter(\':checked\').length;
						checkboxes.filter(\':not(:checked)\').prop(\'disabled\', current >= max);
					});
				});
			</script>
		<form method="post" action="?pc;team" id="release">';
			
		$pokecounter = 0;
		
		$owned_pokemon = explode(',',$userdata[0]['pokemon']);
		
		sort($owned_pokemon);
			
		foreach ($owned_pokemon as $poke) {
				
				echo '<div class="pbox pokeborder'.(is_shiny($poke) ? ' shiny' : '').'"><div class="pokenumber">#',round($poke),'</div>
			
			<input type="checkbox" id="'.$poke.'_'.$pokecounter.'" class="regular-checkbox big-checkbox tpickp" name="poke_pick[]" value="'.$poke.'" /><label for="'.$poke.'_'.$pokecounter.'"></label>
			
			<img src="'.$baseurl.'img/'.(is_shiny($poke) ? 'shiny/' : '').sprintf("%03d",round($poke)).'.png" /><br>
			<span'.(is_shiny($poke) ? ' class="shiny">' : '>').$pokemon[round($poke)].'</span></div>';
			
			$pokecounter++;
		}
		
		echo '<div class="tradeclear"></div>
			<input type="hidden" name="sesc" value="'.$context['session_id'].'">
			<input type="submit" value="Select" class="pokeborder update"></form>';
		
		
		layout_below();
		exit;
	}
	
	if (isset($_REQUEST['do'])) {
	
		//Before we proceed, Are you really who I think you are?
		if (!isset($_REQUEST['sesc'])) die('<span id="response" style="color:#f00;">Failed!</span>');
		if ($_REQUEST['sesc'] != $context['session_id']) die('<span id="response" style="color:#f00;">Failed!</span>');
		
		if (isset($_REQUEST['trainerpic'])) {
			$trainerpics = glob('images/trainers/*.gif');
			$newpic = (int)$_REQUEST['trainerpic'];
			
			if ($newpic > count($trainerpics)) die('<span id="response" style="color:#f00;">Failed!</span>');
			if ($_REQUEST['sesc'] != $context['session_id']) die('<span id="response" style="color:#f00;">Failed!</span>');
			
			//Fair enough, I trust your request.
			update_trainer($context['user']['id'], 'trainerpic', $newpic);
			
			echo '<span id="response" style="color:#0f0;">Updated!</span>';
			 
		} elseif (isset($_REQUEST['favepokemon'])) {
			
			//A lucky user might have a shiny pokemon to be their favourite..
			if (is_shiny($_REQUEST['favepokemon'])) {
			
				//At the moment the input may still be dangerous.
				if (!is_numeric($_REQUEST['favepokemon'])) die('<span id="response" style="color:#f00;">Failed!</span>');
				
				$owned_pokemon = explode(',',$userdata[0]['pokemon']);
				if (!in_array($_REQUEST['favepokemon'], $owned_pokemon)) die('<span id="response" style="color:#f00;">Failed!</span>');
				
				//Ok, shiny for you.
				update_trainer($context['user']['id'], 'fave', $_REQUEST['favepokemon']);
				echo '<span id="response" style="color:#0f0;">Updated!</span>';
				
			} else {
			
				$newfave = (int)$_REQUEST['favepokemon'];
				
				$dex_pokemon = explode(',',$userdata[0]['dex']);
				
				if (!in_array($newfave, $dex_pokemon)) die('<span id="response" style="color:#f00;">Failed!</span>');
				if ($_REQUEST['sesc'] != $context['session_id']) die('<span id="response" style="color:#f00;">Failed!</span>');
				
				//Fair enough, I trust your request.
				update_trainer($context['user']['id'], 'fave', $newfave);
				
				echo '<span id="response" style="color:#0f0;">Updated!</span>';
			
			}
			 
		} elseif (isset($_REQUEST['otrade'])) {
			
			if (isset($_POST['opentrade']) && $_POST['opentrade'] == 'da') {
				update_trainer($context['user']['id'], 'opentrade', 1);
			} else update_trainer($context['user']['id'], 'opentrade', 0);
			
			echo '<span id="response" style="color:#0f0;">Updated!</span>';
			
			
		}
		
		elseif (isset($_REQUEST['favetype'])) {
		
			
			if (!is_numeric($_REQUEST['favetype'])) die('<span id="response" style="color:#f00;">Failed!</span>');
			
			if (($_REQUEST['favetype'] > 18) || ($_REQUEST['favetype'] < 1)) die('<span id="response" style="color:#f00;">Failed!</span>');
			
			update_trainer($context['user']['id'], 'favetype', (int)$_REQUEST['favetype']);
			
			echo '<span id="response" style="color:#0f0;">Updated!</span>';
			
			
		}
		
		//Whatever we responded to, we don't want to output anything but ajax responses.
		exit;
	}
	
	layout_above('PC',$context['user']['name'].'\'s PC');
	
	//Now that's done and dusted, on to functions... At this point, we know the very least that you can choose a trainer picture.
	echo '<style type="text/css">
	h2 {
		color: #FDCE00;
		text-shadow: -2px -2px 0 #385CA8, 2px -2px 0 #385CA8, -2px 2px 0 #385CA8, -2px 0px 0 #385CA8, 2px 0px 0 #385CA8, 2px 2px 0 #385CA8;
	 
	margin-top: 25px;
	}</style>
	<h2>Trainer Picture <input type="submit" form="trainerpic" value="Update" /><span id="result" style="float:right;"></span></h2>';
	
	$trainerpics = glob('images/trainers/*.gif');
	natsort($trainerpics);
	$trainerpics = array_values($trainerpics);
	array_unshift($trainerpics,"");
	unset($trainerpics[0]);
	
	echo '<form action="?pc;do" method="post" id="trainerpic">';
	
	foreach ($trainerpics as $pic => $url) {
	 echo '
	<label for="',$pic,'" class="tp">
		<input id="',$pic,'" type="radio" name="trainerpic" value="',$pic,'">
		<img src="',$url,'" class="trainer_gif',$userdata[0]['trainerpic'] == $pic ? ' current':'','" />
	</label>';
	}
	
	echo '<br><br><input type="submit" value="Update" class="update pokeborder">';
	
	echo '</form>
	
	<script>
	$( "#trainerpic" ).submit(function( event ) {
		event.preventDefault();
		var $form = $( this ),
			pic = $form.find( "input[name=\'trainerpic\']:checked").val(),
			url = $form.attr( "action" );
		var posting = $.post( url, { trainerpic: pic, sesc: "'.$context['session_id'].'" } );
		posting.done(function( data ) {
			var content = $( data );
			$( "#result" ).empty().delay(900).append( content );
		});
	});
	</script>';
	
	if (!empty($userdata[0]['dex'])) { //Let a user set a favourite pokemon from their pokedex.
	
		//Do you already have a favourite?
		if (empty($userdata[0]['fave'])) $fave = 9999;
		else $fave = $userdata[0]['fave'];
		
		echo '<h2>Favorite Pokemon <input type="submit" form="favepokemon" value="Update" /><span id="favepokemon_result" style="float:right;"></span></h2>';
		
		echo '<form action="?pc;do" method="post" id="favepokemon">';
		
		$dex_pokemon = explode(',',$userdata[0]['dex']);
		sort($dex_pokemon);
		
		//We also want to allow the user to choose a shiny pokemon as their fave.
		if (!empty($userdata[0]['pokemon'])) {
			$owned_pokemon = explode(',',$userdata[0]['pokemon']);
			
			foreach ($owned_pokemon as $omon) {
				if (is_shiny($omon)) {
					$shinies[] = $omon;
					$has_shiny = true;
				}
			}
			
			if (!empty($shinies)) $shinies = array_unique($shinies);
		}
		
		//print_r($shinies);
		if (isset($shinies)) {
			foreach ($shinies as $shiny) {
				echo '
				<label for="fave_',$shiny,'" class="tps">
					<input id="fave_',$shiny,'" type="radio" name="favepokemon" value="',$shiny,'">
					<div ',$shiny == $fave ? 'class="current" ' : '','><img src="img/small/',sprintf("%03d",$shiny),'.png" class="favepokeicon"/><span style="color: yellow; text-shadow: 0px 0px 4px white;">',$pokemon[round($shiny)],'</span></div>
				</label>';
			}
		}
		
		foreach ($dex_pokemon as $dexmon) {
		 echo '
		<label for="fave_',$dexmon,'" class="tps">
			<input id="fave_',$dexmon,'" type="radio" name="favepokemon" value="',$dexmon,'">
			<div ',$dexmon == $fave ? 'class="current" ' : '','><img src="img/small/',sprintf("%03d",$dexmon),'.png" class="favepokeicon"/>',$pokemon[round($dexmon)],'</div>
		</label>';
		}
		
		echo '<br><br><input type="submit" value="Update" class="update pokeborder" style="float:left; clear:both; margin-top: 20px; margin-left: 5px;">';
		
		echo '</form>
		
		<script>
		$( "#favepokemon" ).submit(function( event ) {
		event.preventDefault();
		var $form = $( this ),
			fave = $form.find( "input[name=\'favepokemon\']:checked").val(),
			url = $form.attr( "action" );
		var posting = $.post( url, { favepokemon: fave, sesc: "'.$context['session_id'].'" } );
		posting.done(function( data ) {
			var content = $( data );
			$( "#favepokemon_result" ).empty().delay(900).append( content );
		});
	});
	</script>';
		
	}
	
	if (!empty($userdata[0]['fave'])) {
		echo '<h2>Additional Favorites</h2><br>
		<a href="?pc;team">'.(empty($userdata[0]['extrafave']) ? 'Choose Team' : 'Update Team' ).'</a>';
		
		if (!empty($userdata[0]['extrafave'])) {
			$faves = explode(',',$userdata[0]['extrafave']);
			echo '<div style="text-align:center;">';
			foreach ($faves as $fave) {
				echo '<img src="'.$baseurl.'img/anim2/'.(is_shiny($fave) ? 'shiny/' : '' ).''.sprintf("%03d", $fave).'.gif" style="margin-left:-10px;" />';
			}
			echo '</div>';
		}
		
		echo '<br><br><br>';
	}
	
	echo '
	<h2>Favorite Type <input type="submit" form="favetypef" value="Update" /><span id="favetype_result" style="float:right;"></span></h2>
	<form action="?pc;do" method="post" id="favetypef">';
		
		//For now, choosing a favourite type defines which background you get in the new design trainer pages.
		foreach ($type as $id => $typename) {
			echo '<span style="white-space:nowrap;line-height: 29px;"><input type="radio" name="favetype" value="',$id,'" id="favetype_',$id,'"><label for="favetype_',$id,'"><img src="',$baseurl,'images/types/',$id,'.png" alt="',$typename,'" ',$userdata[0]['favetype'] == $id ? 'style="box-shadow:0px 0px 10px white;"' : '',' /></label></span> ';
		}
		
	echo'
		<input type="hidden" name="favetypes" value="otrade">
	</form>
	
	<script>
		$( "#favetypef" ).submit(function( event ) {
		event.preventDefault();
		var $form = $( this ),
			oon = $form.find( "input[name=\'favetype\']:checked").val(),
			url = $form.attr( "action" );
		var posting = $.post( url, { favetype: oon, sesc: "'.$context['session_id'].'", favetypes: "otrade" } );
		posting.done(function( data ) {
			var content = $( data );
			$( "#favetype_result" ).empty().delay(900).append( content );
		});
	});
	</script>';
	
	echo '
	<h2>Open Trading <input type="submit" form="opentradef" value="Update" /><span id="opentrade_result" style="float:right;"></span></h2>
	<form action="?pc;do" method="post" id="opentradef">
		<br><input type="checkbox" ',$userdata[0]['opentrade'] == 1 ? 'checked="checked" ' : '',' value="da" name="opentrade"> Allow trainers not on my RMRK buddy list to trade me
		<input type="hidden" name="otrade" value="otrade">
	</form>
	
	<script>
		$( "#opentradef" ).submit(function( event ) {
		event.preventDefault();
		var $form = $( this ),
			oon = $form.find( "input[name=\'opentrade\']:checked").val(),
			url = $form.attr( "action" );
		var posting = $.post( url, { opentrade: oon, sesc: "'.$context['session_id'].'", otrade: "otrade" } );
		posting.done(function( data ) {
			var content = $( data );
			$( "#opentrade_result" ).empty().delay(900).append( content );
		});
	});
	</script>';
	
	if (isset($owned_pokemon)) {
		
		echo '<br><br><h2>BBcodes</h2><br>';
		
		echo '<div style="padding: 10px 0px 5px 0px; margin-bottom:15px; border-bottom: 2px dotted #555;"><span><a href="'.$baseurl.'?code">Generate Custom BBCode</a></span></div>';
		
		//Now onto various pre-made bbcode lists. First, dupe pokemon.
		$pokedupes = array_count_values($owned_pokemon);
		
		foreach ($pokedupes as $key => $dupe) {
			$pokedupes[$key]--;
			if ($pokedupes[$key] == 0) unset($pokedupes[$key]);
		}
		
		//After this, we know if they actually have any dupes.
		if (!empty($pokedupes)) {
			ksort($pokedupes);
			
			echo '<div style="padding: 10px 0px 5px 0px; margin-bottom:15px; border-bottom: 2px dotted #555;"><span title="Will list ONLY your duplicate pokemon. For example, if you owned 4 Pikachus, this list would contain 3 of them">'.$info_symbol.'Duplicate Pokemon:</span> <textarea wrap="off" id="poken" style="width: 50%; height: 12px; padding: 2px; font-size: 8px;overflow:hidden;font-family: \'Press Start 2P\', sans-serif;float:right;" onclick="SelectAll(\'poken\')">';
			
			foreach ($pokedupes as $key => $dupe) {
				while ($dupe !== 0) {
					echo '[url='.$baseurl.'?pokemon='.round($key).';'. ( is_shiny($key) ? $pokemon[round($key)].'(Shiny)' : $pokemon[$key] ) .'][img]'.$baseurl.'img/named/'. ( is_shiny($key) ? 'shiny/' : '' ) .''.sprintf("%03d",$key).'.png[/img][/url]';
					$dupe--;
				}
			}
			
			echo '</textarea></div>';
			
			//print_r($pokedupes);
		
		}
		
		echo '<div style="padding: 10px 0px 5px 0px; margin-bottom:15px; border-bottom: 2px dotted #555;"><span title="This special image will always show your current trainer picture">'.$info_symbol.'Trainer Picture:</span> <textarea wrap="off" id="trainerpic_bbc" style="width: 50%; height: 12px; padding: 2px; font-size: 8px;overflow:hidden;font-family: \'Press Start 2P\', sans-serif;float:right;" onclick="SelectAll(\'trainerpic_bbc\')">[url='.$baseurl.'?trainer='.$context['user']['id'].'][img]'.$baseurl.'image.php?trainer='.$context['user']['id'].'[/img][/url]</textarea></div>';
		
		if (!empty($userdata[0]['fave'])) {
			echo '<div style="padding: 10px 0px 5px 0px; margin-bottom:15px; border-bottom: 2px dotted #555;"><span title="This special image will always show your current favorite Pokemon">'.$info_symbol.'Fave Pokemon (Front):</span> <textarea wrap="off" id="fave_front" style="width: 50%; height: 12px; padding: 2px; font-size: 8px;overflow:hidden;font-family: \'Press Start 2P\', sans-serif;float:right;" onclick="SelectAll(\'fave_front\')">[url='.$baseurl.'?trainer='.$context['user']['id'].'][img]'.$baseurl.'image.php?fave='.$context['user']['id'].'[/img][/url]</textarea></div>';
			echo '<div style="padding: 10px 0px 5px 0px; margin-bottom:15px; border-bottom: 2px dotted #555;"><span title="This special image will always show your current favorite Pokemon">'.$info_symbol.'Fave Pokemon (Back):</span> <textarea wrap="off" id="fave_back" style="width: 50%; height: 12px; padding: 2px; font-size: 8px;overflow:hidden;font-family: \'Press Start 2P\', sans-serif;float:right;" onclick="SelectAll(\'fave_back\')">[url='.$baseurl.'?trainer='.$context['user']['id'].'][img]'.$baseurl.'image.php?faveback='.$context['user']['id'].'[/img][/url]</textarea></div>';
		}
	
	}
	
	layout_below();
	
	$file_db = null;
	
	
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['admin'])) { //Fancy, abuseable and potentially dangerous operations that we only want very trusted users to be capable of
	
	//We're not doing squat if you're not logged in.
	if ($context['user']['is_guest']) {
		layout_above('Error','Not Logged in');
		layout_below();
		die();
	}
	
	//You also need to be a trusted user ID.
	if (!$pcfg['is_admin']) {
		layout_above('Error','No Permission');
		layout_below();
		die();
	}
	
	if (isset($_GET['log'])) {
			
			layout_above('RMRKMon Administration - Log','Admin Log');
			
			//Read our log, sort it newest first.
			$logdata = $file_db->query('SELECT id, time, user, type, params, extra FROM adminlog ORDER BY id DESC LIMIT 500')->fetchAll();
			
			if (empty($logdata)) {
				echo 'No log to show!';
				layout_below();
				exit;
			}
			
			echo '<div id="submain">
			';
			
			//First, for easy reading we want to drump up some IDs for grabbing usernames.
			$user_ids = array();
			
			foreach ($logdata as $logitem) {
				if (!in_array($logitem['user'], $user_ids)) $user_ids[] = $logitem['user'];
				if (!in_array($logitem['extra'], $user_ids)) $user_ids[] = $logitem['extra'];
			}
			
			$smf_userdata = ssi_fetchMember($member_ids = $user_ids, $output_method = 'array'); //Again, as the list of affected usernames grows this can become a very heavy query indeed.
			
			echo '<h2>Gifting</h2><div style="max-height: 350px; overflow: auto; font-size: 8px;">';
			
			echo '<table style="width: 100%;"><tr><td style="border-bottom: 3px dashed #555; width: 270px;">Time</td><td style="border-bottom: 3px dashed #555;">Action</td></tr>';
			
			foreach ($logdata as $logitem) {
				if($logitem['type'] != 1) continue;
				echo '<tr><td style="border-bottom: 2px dotted #555;"><span title="',date('g:i a', $logitem['time']),'">',date('jS M Y', $logitem['time']),'</span></td><td style="border-bottom: 2px dotted #555;padding: 5px 0px;"><a href="',$baseurl,'?trainer=',$logitem['user'],'">',$smf_userdata[ $logitem['user'] ]['name'],'</a> Gifted ',$pokemon[round($logitem['params'])],'', is_shiny($logitem['params'])? '(Shiny)':'' ,' to ',($logitem['user'] == $logitem['extra'] ? 'themself' : '<a href="'.$baseurl.'?trainer='.$logitem['extra'].'">'.$smf_userdata[ $logitem['extra'] ]['name'].'</a>'  ),'</td></tr>';
			}
			
			
			echo '
			</table></div>';
			
			echo '<h2 style="margin-top: 30px;">Releasing / Deleting</h2><div style="max-height: 350px; overflow: auto; font-size: 8px;">';
			
			echo '<table style="width: 100%;"><tr><td style="border-bottom: 3px dashed #555; width: 270px;">Time</td><td style="border-bottom: 3px dashed #555;">Action</td></tr>';
			
			foreach ($logdata as $logitem) {
				if($logitem['type'] != 2) continue;
				echo '<tr><td style="border-bottom: 2px dotted #555;"><span title="',date('g:i a', $logitem['time']),'">',date('jS M Y', $logitem['time']),'</span></td><td style="border-bottom: 2px dotted #555;padding: 5px 0px;">
					<a href="',$baseurl,'?trainer=',$logitem['user'],'">',$smf_userdata[ $logitem['user'] ]['name'],'</a> Removed ( '; 
					
					$deletedpokemon = explode(',',$logitem['params']);
					foreach ($deletedpokemon as $gonepoke) {
						echo $pokemon[round($gonepoke)],'', is_shiny($gonepoke)? '(Shiny) ':' ';
					}
					
					echo') from ',($logitem['user'] == $logitem['extra'] ? 'themself' : '<a href="'.$baseurl.'?trainer='.$logitem['extra'].'">'.$smf_userdata[ $logitem['extra'] ]['name'].'</a>'  ),'
				</td></tr>';
			}
			
			
			echo '
			</table></div>';
			
			echo'
			</div>';
			layout_below();
			exit;
			
	}
	
	
	if (isset($_REQUEST['do'])) {
	
		//Session check pls.
		if (!isset($_REQUEST['sesc']) || $_REQUEST['sesc'] != $context['session_id']) {
			layout_above('Error','Invalid Session');
			layout_below();
			die();
		}
		
		if (isset($_REQUEST['pokemon_to_give']) && isset($_REQUEST['trainer_list'])) {
		
			layout_above('RMRKMon Administration - Gift Pokemon','Gift Pokemon');
			
			//These things can't be empty.
			if (empty($_POST['trainer_list'])) {
					echo 'No trainers selected!';
					layout_below();
					die();
			}
			
			//SLOW DOWN PARDNER. Perhaps we're gifting an egg here?
			if ($_REQUEST['pokemon_to_give'] == 'EGG') {
				
				
				
				exit;
			}
		
			//Is the pokemon we're gifting going to be a shiny one?
			if (isset($_POST['is_shiny']) && $_POST['is_shiny'] == "affirmative") $_POST['pokemon_to_give'] = $_POST['pokemon_to_give'].'.3';
			
			$smf_userdata = ssi_fetchMember($member_ids = $_POST['trainer_list'], $output_method = 'array');
		
			//First thing's first, if you're gifting to a user who does not have a trainer profile yet, we gotta create it for them.
			foreach ($_POST['trainer_list'] as $recipient) {
				
				$recipient = (int)$recipient;
				
				$recipientdata = userdata($recipient);
				if (empty($recipientdata)) { //This user is a virgin!
					newtrainer($recipient);
					echo '<br>'.$warning_symbol.' No trainer profile for '.$smf_userdata[$recipient]['name'].', creating one.<br>';
				}
				
				//Now we know they have a trainer profile, bypass encounters and chances and just give them the pokemon.
				see_pokemon($recipient, $_POST['pokemon_to_give']);
				capture_pokemon($recipient, $_POST['pokemon_to_give']);
				dex_pokemon($recipient, round($_POST['pokemon_to_give']));
				
				echo '<br>'.(is_shiny($_POST['pokemon_to_give']) ? 'Shiny ' : '').$pokemon[round($_POST['pokemon_to_give'])].' was gifted to User '.$smf_userdata[$recipient]['name'];
				
				admin_log(1, $_POST['pokemon_to_give'], $recipient); //$type, $params(pokemon), $extra(receiving user(s))
				
				
			}
			 
		} elseif (isset($_GET['dbbackup'])) { //Just spit out our DB, with a nice timestamp
			
			//To help avoid locking, explicitly close our database before proceeding. We're ending execution in a few lines so this is fine.
			$file_db = null;
			
			header('Content-Type: application/octet-stream');
			header('Content-disposition: attachment; filename=RMRKMon.'.date('Y-m-d--H-i-s').'.sqlite3');
			header('Content-Length: ' . filesize($pcfg['sqlite_db']));
			readfile($pcfg['sqlite_db']);
			
			exit;
			
		} elseif (isset($_POST['pokemonwipe'])) {
			
			layout_above('RMRKMon Administration - Wipe Pokemon','Wipe Pokemon');
		
			//Before we go nuking, you better have a password.
			if (!isset($_POST['resetpassword'])) {
				echo 'No password!';
				layout_below();
				exit;
			} elseif ($_POST['resetpassword'] != $admin_password) { //You better have the right password too.
				echo 'Wrong password!';
				layout_below();
				exit;
			}
			
			//We're authenticated. Let's get to destroying
			//$file_db ->exec("UPDATE pokemon
			//				SET owners = '', shiny_owners = '', encounters = 0, shiny_encounters = 0, captures = 0, shiny_captures = 0");
			//				
			//$file_db ->exec("UPDATE trainers
			//				SET pokemon = '', seen = '', dex = '', lastcaught = NULL, catches = 0, sightings = 0");
			//				
			//$file_db ->exec('UPDATE stats
			//				SET total_encounters = 0, total_captures = 0');
			//				
			echo 'Wipe not successful, this feature is currently disabled. HA! Use install.php if you require a clean/complete wipe.';
			
			
		} 
		
		//Whatever we responded to, we don't want to go any further.
		layout_below();
		exit;
	}
	
	layout_above('RMRKMon Administration','Administration');
	
	echo '<div id="submain">';
	
	//Now that's done and dusted, on to functions...
	echo '<h2>Gift Pokemon</h2>
	<form id="giftpokemon" action="?admin;do" method="post">
		Give <select id="pokemonselect"  class="s2select" name="pokemon_to_give">
		';
		
	foreach ($pokemon as $key => $poke) {
		echo '<option value="'.$key.'">#'.sprintf("%03d",$key).' '.$poke.'</option>
		';
	}
		
	echo'
	</select> | Shiny? <input type="checkbox" name="is_shiny" value="affirmative"> <br>
	<br>To <input type="text" name="trainer_recipients" value="" id="trainer_recipient" style="width: 33%;
	margin-bottom: 10px;"><div id="trainer_container"></div>
	
	<script type="text/javascript" src="'.$smf_baseurl.'Themes/default/scripts/script.js?fin20"></script>
	<script type="text/javascript" src="'.$smf_baseurl.'Themes/default/scripts/suggest.js?fin20"></script>
	<script type="text/javascript">
	var oTrainerSuggest = new smc_AutoSuggest({
		sSelf: \'oTrainerSuggest\',
		sSessionId: \''.$context['session_id'].'\',
		sSessionVar: \''.$context['session_var'].'\',
		sSuggestId: \'trainer_recipient\',
		sControlId: \'trainer_recipient\',
		sSearchType: \'member\',
		bItemList: true,
		sPostName: \'trainer_list\',
		sURLMask: \'action=profile;u=%item_id%\',
		sTextDeleteItem: \'Delete Item\',
		sItemListContainerId: \'trainer_container\'
	});
	</script>
	<input type="hidden" name="sesc" value="'.$context['session_id'].'">
	
	<br><input type="submit" value="Gift!" class="update pokeborder"></form>
	
	<br><br><h2>Database Backup</h2>
	<a href="?admin;do;dbbackup;sesc='.$context['session_id'].'" class="pokeborder" style="background:#fff; color: #000; padding: 10px;text-shadow: 1px 1px 0px #ccc;float:left;margin-top: 15px;">Download</a><br><br><br><br>
	
	<br><h2>Admin Log</h2>
	<a href="?admin;log" class="pokeborder" style="background:#fff; color: #000; padding: 10px;text-shadow: 1px 1px 0px #ccc;float:left;margin-top: 15px;">Open Log</a><br><br><br><br>
	
	';
	
	echo '</div>';
	layout_below();
	
	$file_db = null;
	
	
} 

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['release'])) { //The UI that both users and admins will use to release or delete pokemon.

	$_GET['release'] = (int)$_GET['release'];
	
	if ($context['user']['is_guest']) { //Go away.
		header('Location: '.$baseurl.'');
		exit;
	}
	
	if (empty($_GET['release'])) { //Redirect to your own release page.
		header('Location: '.$baseurl.'?release='.$context['user']['id'].'');
		exit;
	}
	
	//We're not doing squat if you're not logged in.
	if ($context['user']['is_guest']) {
		layout_above('Error','Not Logged in');
		layout_below();
		die();
	}
	
	//If you're trying to delete someone else's pokemon and you're not an admin, no dice.
	if ($context['user']['id'] != $_GET['release'] && !$pcfg['is_admin']) {
		layout_above('Error','Error');
		echo 'This isn\'t the release page you\'re looking for.';
		layout_below();
		exit;
	}
	
	//Now we've weeded out morons, get our data on.
	$userdata = userdata($_GET['release']);
	
	if (empty($userdata[0]['pokemon'])) {
		layout_above('Error','No data');
		echo 'This trainer has no pokemon, or does not exist!';
		layout_below();
		exit;
	}
	
	//If a form is being submitted, handle it.
	if (isset($_GET['do'])) {
	
		if ($_POST['sesc'] != $context['session_id']) { //We're still checking sessions though.
			layout_above('Error','Error');
			echo 'Something went wrong. Try logging out and back in.';
			layout_below();
			exit;
		}
	
		layout_above('Release Pokemon','Release Pokemon');
	
		if (empty($_POST['poke_release'])) {
			echo 'No pokemon selected!';
			layout_below();
			exit;
		}
		
		$release = release_pokemon($_GET['release'], $_POST['poke_release']);
		
		if (is_array($release)) {
			echo '
			<div id="submain" style="min-height: 610px;"><div style="width: 303px; height: 600px; padding:0; margin-left: 10px; margin-bottom: 10px; background: url(images/releasebg.png) top right no-repeat; float:right"></div>
				<h2>Pokemon Released</h2>';
				
			foreach ($release['released'] as $rp) {
				echo '<div style="border-bottom: 2px dotted #555; line-height: 101px;"><img src="'.$baseurl.'img/'.(is_shiny($rp) ? 'shiny/' : '').sprintf("%03d",round($rp)).'.png" style="float:left;" />
				',$pokemon[round($rp)],'', is_shiny($rp)? '(Shiny)':'' ,'</div>';
			}
			
			if (!empty($release['orphaned'])) {
				echo '<br><br><h2>Orphaned Pokemon <span title="Pokemon that you no longer own at all">'.$info_symbol.'</span></h2>';
				foreach ($release['orphaned'] as $op) {
					echo '<div style="border-bottom: 2px dotted #555; line-height: 101px;"><img src="'.$baseurl.'img/'.(is_shiny($op) ? 'shiny/' : '').sprintf("%03d",round($op)).'.png" style="float:left;" />
					',$pokemon[round($op)],'', is_shiny($op)? '(Shiny)':'' ,'</div>';
				}
			}
			
			//If an admin is responsible for this release and they released another user's pokemon, we should log it. Or for now, log ALL releases.
			$logpokemon = implode(',', $release['released']);
			admin_log(2, $logpokemon, $_GET['release']);
			
			echo '	
			</div>';
			layout_below();
			exit;

		} else  {
			echo $release;
			layout_below();
			exit;
			
		}
			
	}
	
	
	//Otherwise, they're looking to release some pokemon.
	
	$smf_userdata = ssi_fetchMember($member_ids = $_GET['release'], $output_method = 'array');
	
	layout_above('Release Pokemon','Release Pokemon: '.$smf_userdata[ $_GET['release'] ]['name'].'');
	echo '<div id="submain">';
	
	//Perhaps a warning notice?
	echo '<div class="pokeborder warning" style="width: 70%;margin: 0 auto;">'.$warning_symbol.' Releasing pokemon cannot be undone! Please be certain you have chosen the right pokemon!</div><br>';
	
	if ($_GET['release'] != $context['user']['id']) {
		echo '<div class="pokeborder warning" style="width: 70%;margin: 0 auto;">'.$warning_symbol.' WARNING! This is the release page of another user. Be sure of what you are doing. All releases are logged!</div><br>';
	}
	
	echo'
	<h2>',$_GET['release'] == $context['user']['id'] ? 'Your' : $smf_userdata[ $_GET['release'] ]['name'].'\'s',' Pokemon</h2>';
	
	//We're going to spit out cards similar to a trainer's page, though each card will house a giant checkbox.
	
	$owned_pokemon = explode(',',$userdata[0]['pokemon']);
	sort($owned_pokemon);
	
	echo '</div><form method="post" action="?release='.$_GET['release'].';do" id="release" onsubmit="return confirm(\'Are you ABSOLUTELY sure? Please check your entries!\')">';
	
	
	//This is the only instance where we actually want to show duplicates. We still must uniquely identify each pokemon to allow deleting multiple of the same pokemon though.
	$pokecounter = 0;
	
	foreach ($owned_pokemon as $poke) {
			
		echo '<div class="pbox pokeborder'.(is_shiny($poke) ? ' shiny' : '').'"><div class="pokenumber">#',round($poke),'</div>
		
		<input type="checkbox" id="'.$poke.'_'.$pokecounter.'" class="regular-checkbox big-checkbox" name="poke_release[]" value="'.$poke.'" /><label for="'.$poke.'_'.$pokecounter.'"></label>
		
		<img src="'.$baseurl.'img/'.(is_shiny($poke) ? 'shiny/' : '').sprintf("%03d",round($poke)).'.png" /><br>
		<span'.(is_shiny($poke) ? ' class="shiny">' : '>').$pokemon[round($poke)].'</span></div>';
		
		$pokecounter++;
		
	}
	
	echo '
		<div style="clear:both;border-top: 3px dashed #555;height: 10px;"></div><div style="text-align: center;width: 500px;margin: 0 auto;padding-left: 200px;"><input type="submit" value="Release.." class="pokeborder update" style="clear:both;float:left;position: relative;top: 70px;margin-right: -40px;"><img src="images/sadpikadelete.png" /></div>
		<input type="hidden" name="sesc" value="',$context['session_id'],'">
	</form><div style="clear:both;">';
	
	
	echo'</div>';
	
	layout_below();
	
	$file_db = null;
	
	
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['trade'])) { //In what will likely be the biggest function, allow users to trade pokemon. Schema.txt goes into great detail of how trades should pan out.
	
	//We always want certain info available.
	$userdata = userdata($context['user']['id']);
	
	if (!empty($userdata['opentrades'])) {
		$opentrades = count(explode(',',$userdata['opentrades']));
	} else $opentrades = 0;
	
	//On to..stuff.
	
	if (isset($_GET['help'])) {
		layout_above('RMRKMon - Trade Help','Trading Help');
		
		echo '
		<div class="pokeborder standard" style="overflow:auto; line-height: 24px;">
			<img src="images/oak.png" style="float: left; padding: 2px 10px 10px 2px;"/>
			<h2 style="margin-bottom: 10px;">RMRKMon Trading Explained</h2>
			Hello ',$context['user']['name'],'!<br><br>
			Trading Pokemon with other users takes place over multiple different steps. Here\'s a rundown of a successful trade taking place:<br><hr><br>
			
			1) You can start a trade with any trainer who has you on their RMRK buddy list. Some trainers are also open for trade requests from anyone. If you are trade-compatible with a trainer, you\'ll see a trade link on their trainer profile.<br><br>
			
			2) The other trainer accepts your trade request.<br><br>
			
			3) You may now offer pokemon for the trade. You can offer multiple pokemon here, up to ',$pcfg['trade_multi_limit'],'!<br><br>
			
			4) The other trainer offers their pokemon in return.<br><br>
			
			5) If you\'re happy with their offer, confirm the trade. This is your final step!<br><br>
			
			6) The other trainer confirms and the trade is completed. Enjoy your new pokemon!<br><hr><br>
			
			&bull; You may have multiple trades open! However if you no longer have an offered pokemon when a trade is confirmed, it will fail. Be careful!<br><br>
			
			&bull; Use the <a href="',$smf_baseurl,'?board=',$pcfg['trade_forum'],'">Trading Forum</a>, PMs, IRC or other means to plan out, seek and discuss your trades.<br><br>
			
			&bull; Trades are open for all to see!
		</div>';
		
		layout_below();
		exit;
		
	} elseif (isset($_GET['open'])) { //Opening a trade. 'Open' also contains the id.
		
		if (!is_numeric($_GET['open'])) {
			layout_above('Error','Error');
			echo 'Invalid';
			layout_below();
			exit;
		}
		
		if (!$context['user']['is_logged']) die();
		if ($context['user']['id'] == $_GET['open']) die();
		
		if (empty($userdata[0]['pokemon'])) {
			layout_above('Warning','Error');
			echo $warning_symbol.' You have no pokemon to trade!';
			layout_below();
			exit;
		}
		
		if ($opentrades >= $pcfg['trade_simultaneous_limit']) {
			layout_above('Warning','Error');
			echo $warning_symbol.' Sorry, you have too many trades already open!<br><br>Please finish up or cancel some older trades.';
			layout_below();
			exit;
		}
		
		//Get our information about the recipient, checking along the way.
		
		$id = (int)$_GET['open'];
		
		$smf_userdata = ssi_fetchMember($member_ids = $id, $output_method = 'array');
		
		if (empty($smf_userdata[$id]['name'])) {
			layout_above('Error','Error');
			echo 'That user doesn\'t exist!';
			layout_below();
			exit;
		}
		
		$receiverdata = userdata($id);
		
		if (empty($receiverdata[0]['pokemon'])) {
			layout_above('Error','Error');
			echo 'That user is not a pokemon trainer, or they have no pokemon!';
			layout_below();
			exit;
		}
		
		//If this user doesn't want to trade.. with you or anyone..
		if ($receiverdata[0]['opentrade'] != 1 && !in_array($context['user']['id'], $smf_userdata[$id]['buddies'])) {
			layout_above('Error','Error');
			echo '<div id="submain">'.$info_symbol.'This user only allows trainers on their RMRK buddy list to trade with them.</div>';
			layout_below();
			exit;
		}
		
		layout_above('Trade with '.$smf_userdata[$id]['name'].'','Trade with '.$smf_userdata[$id]['name'].'');
		
		//Perform a check first to prevent duplicate same-user trades.
		$traderinfo = $file_db->query('SELECT * from trades WHERE trainer1 = '.$context['user']['id'].' AND trainer2 = '.$id.' AND stage < 95 AND stage > 0')->fetchAll();
		
		if (!empty($traderinfo)) {
			echo 'You already have <a href="?trade;view='.$traderinfo[0]['id'].'">a trade open with '.$smf_userdata[$id]['name'].'</a>!';
			layout_below();
			die();
		}
		
		if (isset($_POST['sesc'])) { //The user is going ahead with creating their trade.

			$newtrade = newtrade($context['user']['id'], $id);
			
			//Add this trade to our users' active trades. If it's empty, just put it in.
			usertradetrack($context['user']['id'], $newtrade);
			
			echo '<div id="submain" style="overflow:auto;">Your trade with '.$smf_userdata[$id]['name'].' was created! Share this link with them: <br><br><a href="'.$baseurl.'?trade;view='.$newtrade.'">'.$baseurl.'?trade;view='.$newtrade.'</a></div>';
			
			layout_below();
			exit;
		}
		
		echo '
		<div id="submain" style="overflow:auto;">
			Create a trade with '.$smf_userdata[$id]['name'].'? This will occupy one of your ',$pcfg['trade_simultaneous_limit'],' trade slots.<br>
			
			<form action="?trade;open='.$id.'" method="post" style="width: 150px; margin: 10px auto;">
				<input type="hidden" name="sesc" value="',$context['session_id'],'">
				<input type="submit" class="update pokeborder" value="Create" style="margin: 10px auto;">
			</form>
		</div>';
		
		layout_below();
		exit;
		
	} elseif (isset($_GET['view'])) { //Where the brunt of our stuff will occur.
		
		$tradeid = (int)$_GET['view'];
		
		//Investigate this trade. If it even exists.
		$tradedata = $file_db->query("SELECT * FROM trades WHERE id='".$tradeid."'")->fetchAll();
		
		if (empty($tradedata)) {
			layout_above('Error','Error');
			echo 'No trade found';
			layout_below();
			exit;
		}
		
		if ($context['user']['id'] != $tradedata[0]['trainer1'] && $context['user']['id'] != $tradedata[0]['trainer2']) {
			layout_above('Trade Center', 'Error');
			echo 'You are not part of this trade.';
			layout_below();
			exit;
		}
		
		if ($tradedata[0]['stage'] == 99) {
			layout_above('Completed Trade','Completed Trade');
			echo 'This trade has been finished!';
			layout_below();
			exit;
		}
		
		//Ok, now we know that our viewer is one of the users in the trade. Trainer1 is the initiator of the trade, trainer2 is the receiver. 1 is always on the left, 2 is always on the right. But we must contextualise the page for both users.
		
		if ($context['user']['id'] == $tradedata[0]['trainer1']) {
			$trainer1data = $userdata;
			$trainer1['name'] = $context['user']['name'];
			$trainer1['id'] = $context['user']['id'];
			$is_owner = true;
		} else {
			$trainer1data = userdata($tradedata[0]['trainer1']);
			$trainer1smfdata = ssi_fetchMember($member_ids = $tradedata[0]['trainer1'], $output_method = 'array');
			$trainer1['name'] = $trainer1smfdata[$tradedata[0]['trainer1']]['name'];
			$trainer1['id'] = $tradedata[0]['trainer1'];
		}
		
		if ($context['user']['id'] == $tradedata[0]['trainer2']) {
			$trainer2data = $userdata;
			$trainer2['name'] = $context['user']['name'];
			$trainer2['id'] = $context['user']['id'];
			$is_owner = false;
		} else {
			$trainer2data = userdata($tradedata[0]['trainer2']);
			$trainer2smfdata = ssi_fetchMember($member_ids = $tradedata[0]['trainer2'], $output_method = 'array');
			$trainer2['name'] = $trainer2smfdata[$tradedata[0]['trainer2']]['name'];
			$trainer2['id'] = $tradedata[0]['trainer2'];
		}
		
		$trainer1_owned = count(explode(',',$trainer1data[0]['pokemon']));
		$trainer1_seen = count(explode(',',$trainer1data[0]['seen']));
		
		$trainer2_owned = count(explode(',',$trainer2data[0]['pokemon']));
		$trainer2_seen = count(explode(',',$trainer2data[0]['seen']));
		
		//Not much use trading if each user can't pick a pokemon.
		if (isset($_GET['pick'])) {
			
			//Who are you?
			if ($context['user']['id'] == $trainer1['id']) {
				$owned_pokemon = explode(',',$trainer1data[0]['pokemon']);
			} elseif ($context['user']['id'] == $trainer2['id']) {
				$owned_pokemon = explode(',',$trainer2data[0]['pokemon']);
			} else die();
			
			sort($owned_pokemon);
			
			layout_above('Pick Pokemon','Pick Pokemon');
			
			echo '
			<script type="text/javascript">
				jQuery(function(){
					var max = '.$pcfg['trade_multi_limit'].';
					var checkboxes = $(\'input[type="checkbox"]\');

					checkboxes.change(function(){
						var current = checkboxes.filter(\':checked\').length;
						checkboxes.filter(\':not(:checked)\').prop(\'disabled\', current >= max);
					});
				});
			</script>
			
			<h2>Pick up to '.$pcfg['trade_multi_limit'].' Pokemon</h2>
			
			<br><div style="text-align:center; font-size: 8px;">Filter: <input type="text"></div><br>
			<script type=\'text/javascript\'>//<![CDATA[ 
			$(window).load(function(){
				$(\'input\').keyup(function() {
					filter(this); 
				});

				function filter(element) {
					var value = $(element).val();
					$(".pbox").each(function () {
						if ($(this).text().indexOf(value) > -1) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});
				}
			});//]]>  

		</script>';
			
			echo '<br><form method="post" action="?trade;view='.$tradeid.'" id="release">';
			
			$pokecounter = 0;
			
			foreach ($owned_pokemon as $poke) {
					
				echo '<div class="pbox pokeborder'.(is_shiny($poke) ? ' shiny' : '').'"><div class="pokenumber">#',round($poke),'</div>
				
				<input type="checkbox" id="'.$poke.'_'.$pokecounter.'" class="regular-checkbox big-checkbox tpickp" name="poke_pick[]" value="'.$poke.'" /><label for="'.$poke.'_'.$pokecounter.'"></label>
				
				',cprite($poke, false),'<br>
				<span'.(is_shiny($poke) ? ' class="shiny flc">' : ' class="flc">').strtolower($pokemon[round($poke)]).'</span></div>';
				
				$pokecounter++;
				
			}
			
			echo '<div class="tradeclear"></div>
			<input type="hidden" name="sesc" value="'.$context['session_id'].'">
			<input type="submit" value="Select" class="pokeborder update"></form>';
			
			
			layout_below();
			exit;
		}
		
		//Now some conditionals. Handling form submissions such as trade acceptance, confirmation, adding pokemon and so on.
		if (isset($_POST['sesc'])) {
			
			if ($_POST['sesc'] != $context['session_id']) { //We're still checking sessions though.
				layout_above('Error','Error');
				echo 'Something went wrong. Try logging out and back in.';
				layout_below();
				exit;
			}
			
			if ($tradedata[0]['stage'] == 0) { //Don't let outdated pages perform stuff.
				layout_above('Cancelled Trade','Cancelled');
				echo 'This trade was cancelled!';
				layout_below();
				exit;
			}
			
			if (isset($_POST['bstage']) && !empty($_POST['bstage'])) { //To prevent any surprises, final-stage form submissions include which stage they "think" they're in. This stops a user confirming while in the background the other user has changed their offer and re-confirmed.
				if ($_POST['bstage'] != $tradedata[0]['stage']) {
					layout_above('Trade Modified', 'Notice');
					echo '<div id="submain">'.$warning_symbol.' The trade has been changed while you were getting ready!<br>
					<a href="'.$baseurl.'?trade;view='.$tradeid.'">Check Changes</a></div>';
					layout_below();
					exit;
				}
				
				//We'll also check that the pokemon offerings have not changed between the last page load and confirmation.
				
				if ($is_owner) {
					if ($_POST['pcheck'] != $tradedata[0]['pokemon2']) {
						layout_above('Trade Modified', 'Notice');
						echo '<div id="submain">'.$warning_symbol.' The trade has been changed while you were getting ready!<br>
						<a href="'.$baseurl.'?trade;view='.$tradeid.'">Check Changes</a></div>';
						layout_below();
						exit;
					}
				} elseif ($_POST['pcheck'] != $tradedata[0]['pokemon1']) {
						layout_above('Trade Modified', 'Notice');
						echo '<div id="submain">'.$warning_symbol.' The trade has been changed while you were getting ready!<br>
						<a href="'.$baseurl.'?trade;view='.$tradeid.'">Check Changes</a></div>';
						layout_below();
						exit;
				}
			}
			
			if (isset($_POST['tradeaccept']) && $_POST['tradeaccept'] == "accept") { //Our trainer2 wants to accept this trade.
			
				if ($tradedata[0]['stage'] == 1 && $context['user']['id'] == $trainer2['id']) { //Well, you're not submitting an old form and you're the right trainer.
					
					if (!empty($trainer2data['opentrades'])) {
						$t2opentrades = count(explode(',',$userdata['opentrades']));
					} else $t2opentrades = 0;
					
					if ($t2opentrades >= $pcfg['trade_simultaneous_limit']) {
						layout_above('Warning','Error');
						echo $warning_symbol.' Sorry, you have too many trades already open!<br><br>Please finish up or cancel some older trades.';
						layout_below();
						exit;
					}
					
					//Ok, let's do this. Add this trade to our receiver's active trades.
					usertradetrack($trainer2['id'], $tradeid);
					update_trade($tradeid, 'stage', 2);
					
					layout_above('Trade Accepted', 'Trade Accepted');
					echo '
					<div id="submain">
						A trade with '.$trainer1['name'].' is now active. This trade now occupies one of your '.$pcfg['trade_simultaneous_limit'].' trade slots.<br><br>
						<a href="'.$baseurl.'?trade;view='.$tradeid.'">Back to your trade</a>
					</div>';
					layout_below();
					exit;
					
				} else {
					layout_above('Errr','Error');
					echo 'You already accepted this trade!';
					layout_below();
					exit;
				}
				
			} elseif (isset($_POST['poke_pick'])) { //A user is submitting their pokemon choices for trades.
				
				if (empty($_POST['poke_pick'])) {
					layout_above('Error','Error');
					echo 'No Pokemon Selected!';
					layout_below();
					exit;
				}
				
				//First off, do you own the pokemon you're submitting? The games get stick for trade hacks - you're not doing it here.
				$userpokemon = explode(',', $userdata[0]['pokemon']);
				
				//Go through our user's pokemon and "check off" each one they're submitting. This is more more complicated than it seems, a simple in_array would let them through trading 5 charmanders even if they only owned one.
				foreach ($_POST['poke_pick'] as $picked_poke) {
					if (in_array($picked_poke, $userpokemon)) { // They own this pokemon. But now remove one of that pokemon from their list.
						foreach ($userpokemon as $key => $value) {
							if ($value == $picked_poke) {
								unset($userpokemon[$key]);
								break;
							}
						}
					} else {
						layout_above('Error','Error');
						echo 'Unowned pokemon submitted';
						layout_below();
						exit;
					}
				}
				
				//Ok, from hereon I trust you.
				layout_above('Pokemon put up for trade','Success');
				
				//This is our string, ready to put in the trade field. But which one?
				$submitted_pokemon = implode(',', $_POST['poke_pick']);
				
				if ($is_owner) $field = 'pokemon1';
				else $field = 'pokemon2';
				
				update_trade($tradeid, $field, $submitted_pokemon);
				
				//We're not done just yet, we need to figure out which stage this takes us to.
				if ($is_owner && empty($tradedata[0]['pokemon1']) && empty($tradedata[0]['pokemon2'])) $stage = 3; //Trainer1 is adding their pokemon, they're the first to add.
				elseif (!$is_owner && empty($tradedata[0]['pokemon1']) && empty($tradedata[0]['pokemon2'])) $stage = 4; //Trainer2 is adding their pokemon, they're the first to add.
				elseif ($is_owner && empty($tradedata[0]['pokemon1']) && !empty($tradedata[0]['pokemon2'])) $stage = 8; //Trainer1 just added and made both have offered pokemon
				elseif (!$is_owner && !empty($tradedata[0]['pokemon1']) && empty($tradedata[0]['pokemon2'])) $stage = 8; //Trainer2 just added and made both have offered pokemon
				else $stage = 8;
				
				update_trade($tradeid, 'stage', $stage);

				header('Location: '.$baseurl.'?trade;view='.$tradeid.'');
				exit;
				
			} elseif (isset($_GET['ready'])) { //Update whichever trainer's ready status. If the other trainer has already picked ready, this will complete the trade.
				
				if (empty($tradedata[0]['pokemon2']) || empty($tradedata[0]['pokemon1'])) die('Nobody should ever see this message! BUTTS');
				
				if ($tradedata[0]['stage'] == 8 && $is_owner) { //Trainer 1 is setting ready status, from empty.
					update_trade($tradeid, 'stage', 9);
					header('Location: '.$baseurl.'?trade;view='.$tradeid.'');
					exit;
				}
				
				if ($tradedata[0]['stage'] == 8 && !$is_owner) { //Trainer 2 is setting ready status, from empty.
					update_trade($tradeid, 'stage', 10);
					header('Location: '.$baseurl.'?trade;view='.$tradeid.'');
					exit;
				}
				
				if ( ($is_owner && $tradedata[0]['stage'] == 10) || (!$is_owner && $tradedata[0]['stage'] == 9) ) { //Our final step! Complete the trade!
					
					//Well, if the pokemon still exist.
					$trainer1realpokemon = explode(',',$trainer1data[0]['pokemon']);
					$trainer2realpokemon = explode(',',$trainer2data[0]['pokemon']);
					
					$trainer1offeredpokemon = explode(',',$tradedata[0]['pokemon1']);
					$trainer2offeredpokemon = explode(',',$tradedata[0]['pokemon2']);
					
					//We'll do this seperate so we know who to blame.
					foreach ($trainer1offeredpokemon as $t1op) {
						if (!in_array($t1op, $trainer1realpokemon)) {
							layout_above('Error','Error');
							echo $trainer1['name'].' no longer owns all of the pokemon they have offered!';
							layout_below();
							exit;
						}
					}
					
					foreach ($trainer2offeredpokemon as $t2op) {
						if (!in_array($t2op, $trainer2realpokemon)) {
							layout_above('Error','Error');
							echo $trainer2['name'].' no longer owns all of the pokemon they have offered!';
							layout_below();
							exit;
						}
					}
					
					complete_trade($tradeid);
					header('Location: '.$baseurl.'?trade;view='.$tradeid.';completed');
					exit;
				}
				
			} elseif (isset($_GET['notready'])) {
				
				update_trade($tradeid, 'stage', 8);
				header('Location: '.$baseurl.'?trade;view='.$tradeid.'');
				exit;
				
			} elseif (isset($_GET['cancel'])) { //Destroy this trade.
				
				update_trade($tradeid, 'stage', 0);

				releasetrade($trainer1['id'], $tradeid);
				releasetrade($trainer2['id'], $tradeid);
				
				
				header('Location: '.$baseurl.'?trade');
				exit;
				
			}
			
		}
		
		if ($tradedata[0]['stage'] == 0) {
			layout_above('Cancelled Trade','Cancelled');
			echo 'This trade was cancelled!';
			layout_below();
			exit;
		}
		
		if ($is_owner) layout_above('Trade with '.$trainer2['name'], 'Trade with '.$trainer2['name']);
		else echo layout_above('Trade with '.$trainer1['name'], 'Trade with '.$trainer1['name']);
		
		echo '
		<div style="width:100%; text-align:center; font-size: 8px;margin-bottom: 10px;"><img src="images/pokenav.png" style="vertical-align:center;"> <a href="http://rmrk.net/?action=chat" action="_blank">RMRK IRC Chat #rmrk @ irc.rmrk.net</a></div>
		<div id="submain" style="background:url(images/vertical5px.png) top center repeat-y;overflow:auto;padding-bottom: 20px;">';
		
		//Now for each new step made, we show our status.
		echo '
		<div class="trade" style="float:left;">
			<h2><img src="'.$baseurl.'images/trainers/',$trainer1data[0]['trainerpic'],'.gif" class="trainerpic" style="float:right;"><a href="'.$baseurl.'?trainer='.$trainer1['id'].'">',$trainer1['name'],'</a> <span style="font-size:8px;line-height: 16px;"><img src="images/t_owned.png">'.$trainer1_owned.' <img src="images/t_seen.png">'.$trainer1_seen.'</span></h2>
			',badge_strip($trainer1['id'], $trainer1data, $output_method = "echo_nocruft"),'
		</div>
		<div class="trade" style="float:right;">
			<h2><img src="'.$baseurl.'images/trainers/',$trainer2data[0]['trainerpic'],'.gif" class="trainerpic" style="float:right;"><a href="'.$baseurl.'?trainer='.$trainer2['id'].'">',$trainer2['name'],'</a> <span style="font-size:8px;line-height: 16px;"><img src="images/t_owned.png">'.$trainer2_owned.' <img src="images/t_seen.png">'.$trainer2_seen.'</span></h2>
			',badge_strip($trainer2['id'], $trainer2data, $output_method = "echo_nocruft"),'
		</div>';
		
		
		if ($tradedata[0]['stage'] < 8) { //Once each party starts offering pokemon, we don't need this.
			echo '
			<div class="pokeborder standard trade2" style="float:left;">
				',$context['user']['id'] == $trainer1['id'] ? 'You opened a trade with '.$trainer2['name'].'' : ''.$trainer1['name'].' opened a trade with you' ,'
			</div>
			<div class="pokeborder standard trade2" style="float:right;">';
				if ($tradedata[0]['stage'] >= 2) {
					if ($context['user']['id'] == $trainer2['id']) { 
					
						echo 'You accepted the trade.';
						
					} else echo $trainer2['name'].' accepted your trade request';
					
				} else {
					if ($context['user']['id'] == $trainer2['id']) { //Accept the trade.
					
						echo '
						<form method="post" action="'.$baseurl.'?trade;view=',$tradeid,'">
							<input type="submit" value="Accept trade?" class="update pokeborder" style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;">
							<input type="hidden" name="tradeaccept" value="accept">
							<input type="hidden" name="sesc" value="'.$context['session_id'].'">
						</form>';
						
					} else echo '<span class="pinactive">',$trainer2['name'],' must accept the trade!</span>';
					
				}
			echo'</div>
			';
		
		}
		
		if ($tradedata[0]['stage'] >= 2) { //The next stages appear once trainer2 has accepted the trade.
			
			echo '<div class="tradeclear"></div>';
			
			echo '
			<div class="pokeborder standard trade2" style="float:left;">';
				if ($context['user']['id'] == $trainer1['id'] && empty($tradedata[0]['pokemon1'])) {
					echo '
				<form method="get" action="'.$baseurl.'?trade;view=',$tradeid,';pick">
					<input type="submit" value="Pick Pokemon" class="update pokeborder" style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;">
					<input type="hidden" name="trade">
					<input type="hidden" name="view" value="'.$tradeid.'">
					<input type="hidden" name="pick">
				</form>';
				} elseif ($context['user']['id'] == $trainer2['id'] && empty($tradedata[0]['pokemon1'])) {
					echo '<span class="pinactive">',$trainer1['name'],' has not chosen any Pokemon yet</span>';
				} else { //Trainer 1 has chosen some pokemon
					$t1pkmn = explode(',',$tradedata[0]['pokemon1']);
					echo '<div style="line-height:normal;"><h3>', $is_owner ? 'Your offer: <span style="font-size:16px;float:right;"><a href="'.$baseurl.'?trade;view='.$tradeid.';pick">(edit)</a></span><br>' : $trainer1['name'].' is offering:' ,'</h3>';
					$t1score = 0;
					foreach ($t1pkmn as $pkmn) {
						$score = (100-$e_chance[round($pkmn)])*2;
						if ($affinity[round($pkmn)] == 1 || $affinity[round($pkmn)] == 2) $score = $score+50;
						if (is_shiny($pkmn)) $score = $score*12.5;
						echo '
						<div class="pbox pokeborder'.(is_shiny($pkmn) ? ' shiny' : '').'"><div class="pokenumber">#',round($pkmn),'</div>
						<a href="?pokemon='.round($pkmn).'"><img src="'.$baseurl.'img/'.(is_shiny($pkmn) ? 'shiny/' : '').sprintf("%03d",round($pkmn)).'.png" /></a><br>
						<span title="Score: '.$score.'" '.(is_shiny($pkmn) ? ' class="shiny">' : '>').$pokemon[round($pkmn)].'</span></div>';
						$t1score = $t1score+$score;
						
					}
					unset($score);
					unset($pkmn);
					echo '<span title="A simple statistical based estimate of the &quot;value&quot; of the trade. " style="display:block;clear:both;font-size:8px;color:#333;">Score: '.number_format($t1score).'</span></div>';
				}
			echo '
			</div>
			<div class="pokeborder standard trade2" style="float:right;">';
				if ($context['user']['id'] == $trainer2['id'] && empty($tradedata[0]['pokemon2'])) {
					echo '
				<form method="get" action="'.$baseurl.'?trade;view=',$tradeid,';pick">
					<input type="submit" value="Pick Pokemon" class="update pokeborder" style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;">
					<input type="hidden" name="trade">
					<input type="hidden" name="view" value="'.$tradeid.'">
					<input type="hidden" name="pick">
				</form>';
				} elseif ($context['user']['id'] == $trainer1['id'] && empty($tradedata[0]['pokemon2'])) {
					echo '<span class="pinactive">',$trainer2['name'],' has not chosen any Pokemon yet</span>';
				} else { //Trainer 2 has chosen some pokemon
					$t2pkmn = explode(',',$tradedata[0]['pokemon2']);
					echo '<div style="line-height:normal;"><h3>', !$is_owner ? 'Your offer: <span style="font-size:16px;float:right;"><a href="'.$baseurl.'?trade;view='.$tradeid.';pick">(edit)</a></span><br>' : $trainer2['name'].' is offering:' ,'</h3>';
					$t2score = 0;
					foreach ($t2pkmn as $pkmn) {
						$score = (100-$e_chance[round($pkmn)])*2;
						if ($affinity[round($pkmn)] == 1 || $affinity[round($pkmn)] == 2) $score = $score+50;
						if (is_shiny($pkmn)) $score = $score*12.5;
						echo '
						<div class="pbox pokeborder'.(is_shiny($pkmn) ? ' shiny' : '').'"><div class="pokenumber">#',round($pkmn),'</div>
						<a href="?pokemon='.round($pkmn).'"><img src="'.$baseurl.'img/'.(is_shiny($pkmn) ? 'shiny/' : '').sprintf("%03d",round($pkmn)).'.png" /></a><br>
						<span title="Score: '.$score.'" '.(is_shiny($pkmn) ? ' class="shiny">' : '>').$pokemon[round($pkmn)].'</span></div>';
						$t2score = $t2score+$score;
					}
					unset($score);
					unset($pkmn);
					echo '<span title="A simple statistical based estimate of the &quot;value&quot; of the trade. " style="display:block;clear:both;font-size:8px;color:#333;">Score: '.number_format($t2score).'</span></div>';
				}
			
			echo'	
			</div>';
			
		}
		
		if (!empty($tradedata[0]['pokemon1']) && !empty($tradedata[0]['pokemon2']) ) { //Both trainers have added pokemon, time to show some confirmation buttons.
			
			echo '<div class="tradeclear"></div>';
			
			echo '
		<div class="pokeborder standard trade2" style="float:left;">';
		if ($tradedata[0]['stage'] >= 8 && $is_owner) {
			if ($tradedata[0]['stage'] != 9 && $tradedata[0]['stage'] != 10) echo'
			<form action="'.$baseurl.'?trade;view='.$tradeid.';ready" method="post" onsubmit="return confirm(\'This will allow the other trainer to complete the trade. Sure?\')">
				<input type="hidden" name="sesc" value="',$context['session_id'],'">
				<input type="hidden" name="bstage" value="8">
				<input type="hidden" name="pcheck" value="'.$tradedata[0]['pokemon2'].'">
				<input type="submit" value="Ready to trade!" class="update pokeborder" style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;">
			</form>';
			elseif ($tradedata[0]['stage'] == 10) echo'
			<form action="'.$baseurl.'?trade;view='.$tradeid.';ready" method="post">
				<input type="hidden" name="sesc" value="',$context['session_id'],'">
				<input type="hidden" name="bstage" value="10">
				<input type="hidden" name="pcheck" value="'.$tradedata[0]['pokemon2'].'">
				<input type="submit" value="Complete Trade!" class="update pokeborder" style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;">
			</form>';
			else echo'
			<form action="'.$baseurl.'?trade;view='.$tradeid.';notready" method="post">
				<input type="hidden" name="sesc" value="',$context['session_id'],'">
				<input type="submit" value="Undo Ready" class="update pokeborder" style="background:#D86D37 url(images/bg_red.png) bottom left repeat-x;">
			</form>';
		} else {
			if ($tradedata[0]['stage'] == 9) echo $trainer1['name'].' is ready!';
			else echo '<span class="pinactive">'.$trainer1['name'].' isn\'t ready yet</span>';
		}
			echo'
		</div>';
		
			echo '
		<div class="pokeborder standard trade2" style="float:right;">';
		if ($tradedata[0]['stage'] >= 8 && !$is_owner) {
			if ($tradedata[0]['stage'] != 10 && $tradedata[0]['stage'] != 9) echo'
			<form action="'.$baseurl.'?trade;view='.$tradeid.';ready" method="post" onsubmit="return confirm(\'This will allow the other trainer to complete the trade. Sure?\')">
				<input type="hidden" name="sesc" value="',$context['session_id'],'">
				<input type="hidden" name="bstage" value="8">
				<input type="hidden" name="pcheck" value="'.$tradedata[0]['pokemon1'].'">
				<input type="submit" value="Ready to trade!" class="update pokeborder" style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;">
			</form>';
			elseif ($tradedata[0]['stage'] == 9) echo'
			<form action="'.$baseurl.'?trade;view='.$tradeid.';ready" method="post">
				<input type="hidden" name="sesc" value="',$context['session_id'],'">
				<input type="hidden" name="bstage" value="9">
				<input type="hidden" name="pcheck" value="'.$tradedata[0]['pokemon1'].'">
				<input type="submit" value="Complete Trade!" class="update pokeborder" style="background:#4BD08B url(images/bg_green.png) bottom left repeat-x;">
			</form>';
			else echo'
			<form action="'.$baseurl.'?trade;view='.$tradeid.';notready" method="post">
				<input type="hidden" name="sesc" value="',$context['session_id'],'">
				<input type="submit" value="Undo Ready" class="update pokeborder" style="background:#D86D37 url(images/bg_red.png) bottom left repeat-x;">
			</form>';
		} else {
			if ($tradedata[0]['stage'] == 10) echo $trainer2['name'].' is ready!';
			else echo '<span class="pinactive">'.$trainer2['name'].' isn\'t ready yet</span>';
		}
			echo'
		</div>';
		
		
	}
	
	if ($is_owner && $tradedata[0]['stage'] >= 1) {
		echo '
		<div class="tradeclear"></div>
		<form action="'.$baseurl.'?trade;view='.$tradeid.';cancel" method="post" onsubmit="return confirm(\'This cannot be undone! Really cancel this trade?\')" style="text-align: center;">
			<input type="hidden" name="sesc" value="'.$context['session_id'].'">
			<input type="submit" value="Cancel Trade" class="update pokeborder" style="background:#D86D37 url(images/bg_red.png) bottom left repeat-x;">
		</form>';
	} elseif (!$is_owner && $tradedata[0]['stage'] >= 2) {
		echo '
		<div class="tradeclear"></div>
		<form action="'.$baseurl.'?trade;view='.$tradeid.';cancel" method="post" onsubmit="return confirm(\'This cannot be undone! Really cancel this trade?\')" style="text-align: center;">
			<input type="hidden" name="sesc" value="'.$context['session_id'].'">
			<input type="submit" value="Cancel Trade" class="update pokeborder" style="background:#D86D37 url(images/bg_red.png) bottom left repeat-x;">
		</form>';
	}
		
		
		
		
		
		
		
		echo'	
		</div>';
		
		layout_below();
		exit;
		
	}
	
	
	layout_above('RMRKMon Trade Center','Trade Center');
	
	echo '
	<div id="submain">';
	if (!empty($userdata[0]['opentrades'])) { //You currently have ongoing trades, look at you.
		echo'
		<div style="padding-left: 90px; background:url(images/tradecenter1x.png) no-repeat 0% 0%;min-height: 100px;margin-bottom: 20px;">
			<h2>Current trades</h2>
			<div class="pokeborder standard currenttrades" style="margin-top: 5px;">';
			
			//Clusterf... well, this gets everything we need.
			$opentrades = explode(',',$userdata[0]['opentrades']);
			$impopentrades = implode(' OR id=', $opentrades);
			$opentradesquery = 'SELECT id, trainer1, pokemon1, trainer2, pokemon2, stage, date FROM trades WHERE id='.$impopentrades.' ORDER BY date DESC';
			
			$otradedata = $file_db->query($opentradesquery)->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
			$otradedata = array_map('reset', $otradedata);
			
			//Except all relevant parties' usernames, those are stored by SMF instead!
			$smf_users = array();
			foreach ($otradedata as $trainers) {
				if (!in_array($trainers['trainer1'], $smf_users)) $smf_users[] = $trainers['trainer1'];
				if (!in_array($trainers['trainer2'], $smf_users)) $smf_users[] = $trainers['trainer2'];
			}
			
			$smf_userdata = pokemon_fetchMember($member_ids = $smf_users, $output_method = 'array');
			
			//Now we're good to go.
			foreach ($otradedata as $key => $trade) {
				echo '
				<span><a href="'.$baseurl.'?trade;view='.$key.'"><img src="images/tradeball.png" title="View trade" alt="View trade" /></a> ', $trade['trainer1'] == $context['user']['id'] ? 'You' : '<a href="'.$baseurl.'?trainer='.$trade['trainer1'].'">'.$smf_userdata[ $trade['trainer1'] ]['name'].'</a>' ,' and ', $trade['trainer2'] == $context['user']['id'] ? 'You' : '<a href="'.$baseurl.'?trainer='.$trade['trainer2'].'">'.$smf_userdata[ $trade['trainer2'] ]['name'].'</a>' ,' | Stage '.$trade['stage'].'</span>';
			}
			
			
			echo'	
			</div>
		</div>';
	}
	
	$recenttrades = $file_db->query("SELECT id, trainer1, trainer2, pokemon1, pokemon2, date FROM tradelog ORDER BY date DESC LIMIT 20")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
	$recenttrades = array_map('reset', $recenttrades);
	
	if (empty($recenttrades)) {
		echo 'No recent trades!';
		layout_below();
		exit;
	}
	
	//Again, SMF usernames please.
	$smf_users = array();
	foreach ($recenttrades as $rtrade) {
		if (!in_array($rtrade['trainer1'], $smf_users)) $smf_users[] = $rtrade['trainer1'];
		if (!in_array($rtrade['trainer2'], $smf_users)) $smf_users[] = $rtrade['trainer2'];
	}
	
	$smf_userdata = pokemon_fetchMember($member_ids = $smf_users, $output_method = 'array');
	
	echo '
	<h2 style="margin-bottom: 10px;">Recent Trades</h2>';
	
	foreach ($recenttrades as $trade) {
		echo'
		<div style="text-align:center; padding: 5px 15px;"><span style="float:left;"><a href="'.$baseurl.'?trainer='.$trade['trainer1'].'">'.$smf_userdata[ $trade['trainer1'] ]['name'].'</a></span>'.date('M jS, g:i a', $trade['date']).'<span style="float:right;"><a href="'.$baseurl.'?trainer='.$trade['trainer2'].'">'.$smf_userdata[ $trade['trainer2'] ]['name'].'</a></span></div>
		<div style="background:url(images/recenttradebar.png) 50% 50% no-repeat;overflow:auto;clear:left;">
			<div class="pokeborder standard trade2" style="float:left;line-height:normal;">
				';
				$pokemon1 = explode(',', $trade['pokemon1']);
				foreach ($pokemon1 as $p1) {
					echo '<div style="float:right;" class="pbox pokeborder'.(is_shiny($p1) ? ' shiny' : '').'"><div class="pokenumber">#',round($p1),'</div><a href="?pokemon='.round($p1).'"><img src="'.$baseurl.'img/'.(is_shiny($p1) ? 'shiny/' : ''.'').sprintf("%03d",round($p1)).'.png" /></a><br>
			<span>',$pokemon[round($p1)],'</span></div>';
				}
			echo '
			</div>
			<div class="pokeborder standard trade2" style="float:right;line-height:normal;">';
				$pokemon2 = explode(',', $trade['pokemon2']);
				foreach ($pokemon2 as $p2) {
					echo '<div style="float:left;" class="pbox pokeborder'.(is_shiny($p2) ? ' shiny' : '').'"><div class="pokenumber">#',round($p2),'</div><a href="?pokemon='.round($p2).'"><img src="'.$baseurl.'img/'.(is_shiny($p2) ? 'shiny/' : ''.'').sprintf("%03d",round($p2)).'.png" /></a><br>
			<span>',$pokemon[round($p2)],'</span></div>';
				}
			echo '
			</div>
		</div>
		<div class="tradeclear"></div>
		';
	}
	
	//$smfdata = pokemon_fetchmember(array(1,3));
	
	//print_r($smfdata);
	
	echo'
	</div>';
	
	layout_below();
	$file_db = null;
	
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['list'])) { //Spit out various lists of all the trainers. We may have to restrict or paginate this in the future, but for now, s'cool.

	//Cautious about performance here. Let's benchmark.
	$startScriptTime=microtime(TRUE);
	
	//Default list. Output all trainers by order of newest trainer first.
	$ids = $file_db->query('SELECT id FROM trainers ORDER BY starttime DESC')->fetchAll(PDO::FETCH_COLUMN, 0);
	$usersdata = multiuserdata($ids);
	$usersdatab = $usersdata;
	$smf_usersdata = pokemon_fetchMember($ids);
	
	//jesus
	function starttimeDESC($a,$b) {
		return $a['starttime']<$b['starttime'];
	}
	function starttimeASC($a,$b) {
		return $a['starttime']>$b['starttime'];
	}
	function pokemonDESC($a,$b) {
		return $a['total_pokemon']<$b['total_pokemon'];
	}
	function pokemonASC($a,$b) {
		return $a['total_pokemon']>$b['total_pokemon'];
	}
	function seenDESC($a,$b) {
		return $a['total_seen']<$b['total_seen'];
	}
	function seenASC($a,$b) {
		return $a['total_seen']>$b['total_seen'];
	}
	function dexDESC($a,$b) {
		return $a['total_dex']<$b['total_dex'];
	}
	function dexASC($a,$b) {
		return $a['total_dex']>$b['total_dex'];
	}
	function tradesDESC($a,$b) {
		return $a['total_trades']<$b['total_trades'];
	}
	function tradesASC($a,$b) {
		return $a['total_trades']>$b['total_trades'];
	}
	function catchesDESC($a,$b) {
		return $a['catches']<$b['catches'];
	}
	function catchesASC($a,$b) {
		return $a['catches']>$b['catches'];
	}
	function sightingsDESC($a,$b) {
		return $a['sightings']<$b['sightings'];
	}
	function sightingsASC($a,$b) {
		return $a['sightings']>$b['sightings'];
	}
	function lastcaughtDESC($a,$b) {
		return $a['lastcaught']<$b['lastcaught'];
	}
	function lastcaughtASC($a,$b) {
		return $a['lastcaught']>$b['lastcaught'];
	}
	
	if (empty($_GET['list']) || $_GET['list'] == "pokemonDESC") {
		if (empty($_GET['list'])) $_GET['list'] = "pokemonDESC";
		$pagetitle = "Most Pokemon"; //Default
	}
	elseif ($_GET['list'] == "starttimeASC") $pagetitle = "Trainers - Newest First";
	elseif ($_GET['list'] == "starttimeDESC") $pagetitle = "Trainers - Oldest First";
	elseif ($_GET['list'] == "pokemonASC") $pagetitle = "Trainers - Fewest Pokemon";
	elseif ($_GET['list'] == "seenDESC") $pagetitle = "Trainers - Seen Most Pokemon"; //Default
	elseif ($_GET['list'] == "seenASC") $pagetitle = "Trainers - Seen Fewest Pokemon";
	elseif ($_GET['list'] == "dexDESC") $pagetitle = "Trainers - Most Complete Pokedex"; //Default
	elseif ($_GET['list'] == "dexASC") $pagetitle = "Trainers - Smallest Pokedex";
	elseif ($_GET['list'] == "tradesDESC") $pagetitle = "Trainers - Most Trades"; //Default
	elseif ($_GET['list'] == "tradesASC") $pagetitle = "Trainers - Fewest Trades";
	elseif ($_GET['list'] == "catchesDESC") $pagetitle = "Trainers - Most Caught"; //Default
	elseif ($_GET['list'] == "catchesASC") $pagetitle = "Trainers - Fewest Caught";
	elseif ($_GET['list'] == "sightingsDESC") $pagetitle = "Trainers - Most Sightings"; //Default
	elseif ($_GET['list'] == "sightingsASC") $pagetitle = "Trainers - Fewest Sightings";
	elseif ($_GET['list'] == "lastcaughtDESC") $pagetitle = "Trainers - Most Recently Caught"; //Default
	elseif ($_GET['list'] == "lastcaughtASC") $pagetitle = "Trainers - Longest Time Since Catching";
	else die();
	
	foreach ($usersdata as $key => $value) {
		$usersdata[$key]['pokemon'] = explode(',',$value['pokemon']);
		$usersdata[$key]['dex'] = explode(',',$value['dex']);
		$usersdata[$key]['seen'] = explode(',',$value['seen']);
		$usersdata[$key]['trades'] = explode(',',$value['trades']);
		$usersdata[$key]['name'] = $smf_usersdata[$key]['name'];
		$usersdata[$key]['id'] = $key;
		$usersdata[$key]['buddies'] = $smf_usersdata[$key]['buddies'];
		sort($usersdata[$key]['pokemon']);
		sort($usersdata[$key]['dex']);
		sort($usersdata[$key]['seen']);
		sort($usersdata[$key]['trades']);
		$usersdata[$key]['total_pokemon'] = count($usersdata[$key]['pokemon']);
		$usersdata[$key]['total_dex'] = count($usersdata[$key]['dex']);
		$usersdata[$key]['total_seen'] = count($usersdata[$key]['seen']);
		$usersdata[$key]['total_trades'] = count($usersdata[$key]['trades']);
	}
	
	$total_pokemon = count($pokemon);
	
	uasort($usersdata, $_GET['list']);
	
	
	layout_above($pagetitle, str_replace('Trainers - ', '', $pagetitle));
	
	echo '
	<div id="submain">';
	
	echo '
	<table class="listtrainers">
		<tr>
			<td>';
			if ($_GET['list'] == "pokemonDESC" || $_GET['list'] == "pokemonASC") echo'
				',$_GET['list'] == "pokemonDESC" ? '<img src="images/arrow_downs.png" /><a href="?list=pokemonASC">Pokemon</a>' : '<img src="images/arrow_ups.png" /><a href="?list=pokemonDESC">Pokemon</a>','';
				else echo '
				<a href="?list=pokemonDESC">Pokemon</a>';
			echo'
			</td>
			<td>';
			if ($_GET['list'] == "starttimeDESC" || $_GET['list'] == "starttimeASC") echo'
				',$_GET['list'] == "starttimeDESC" ? '<img src="images/arrow_downs.png" /><a href="?list=starttimeASC">Start Time</a>' : '<img src="images/arrow_ups.png" /><a href="?list=starttimeDESC">Start Time</a>','';
				else echo '
				<a href="?list=starttimeDESC">Start Time</a>';
			echo'
			</td>
			<td>';
			if ($_GET['list'] == "seenDESC" || $_GET['list'] == "seenASC") echo'
				',$_GET['list'] == "seenDESC" ? '<img src="images/arrow_downs.png" /><a href="?list=seenASC">Seen</a>' : '<img src="images/arrow_ups.png" /><a href="?list=seenDESC">Seen</a>','';
				else echo '
				<a href="?list=seenDESC">Seen</a>';
			echo'
			</td>
			<td>';
			if ($_GET['list'] == "dexDESC" || $_GET['list'] == "dexASC") echo'
				',$_GET['list'] == "dexDESC" ? '<img src="images/arrow_downs.png" /><a href="?list=dexASC">Pokedex</a>' : '<img src="images/arrow_ups.png" /><a href="?list=dexDESC">Pokedex</a>','';
				else echo '
				<a href="?list=dexDESC">Pokedex</a>';
			echo'
			</td>
			<td>';
			if ($_GET['list'] == "tradesDESC" || $_GET['list'] == "tradesASC") echo'
				',$_GET['list'] == "tradesDESC" ? '<img src="images/arrow_downs.png" /><a href="?list=tradesASC">Trades</a>' : '<img src="images/arrow_ups.png" /><a href="?list=tradesDESC">Trades</a>','';
				else echo '
				<a href="?list=tradesDESC">Trades</a>';
			echo'
			</td>
			<td>';
			if ($_GET['list'] == "catchesDESC" || $_GET['list'] == "catchesASC") echo'
				',$_GET['list'] == "catchesDESC" ? '<img src="images/arrow_downs.png" /><a href="?list=catchesASC">Catches</a>' : '<img src="images/arrow_ups.png" /><a href="?list=catchesDESC">Catches</a>','';
				else echo '
				<a href="?list=catchesDESC">Catches</a>';
			echo'
			</td>
			<td>';
			if ($_GET['list'] == "sightingsDESC" || $_GET['list'] == "sightingsASC") echo'
				',$_GET['list'] == "sightingsDESC" ? '<img src="images/arrow_downs.png" /><a href="?list=sightingsASC">Sightings</a>' : '<img src="images/arrow_ups.png" /><a href="?list=sightingsDESC">Sightings</a>','';
				else echo '
				<a href="?list=sightingsDESC">Sightings</a>';
			echo'
			</td>
			<td>';
			if ($_GET['list'] == "lastcaughtDESC" || $_GET['list'] == "lastcaughtASC") echo'
				',$_GET['list'] == "lastcaughtDESC" ? '<img src="images/arrow_downs.png" /><a href="?list=lastcaughtASC">Last Caught</a>' : '<img src="images/arrow_ups.png" /><a href="?list=lastcaughtDESC">Last Caught</a>','';
				else echo '
				<a href="?list=lastcaughtDESC">Last Caught</a>';
			echo'
			</td>
		</tr>
	</table>';
	
	echo '<!--';
	//print_r($usersdata);
	echo '-->';
	
	//Alright, now a giant foreach for all possible userdata.
	foreach ($usersdata as $user) {
		echo '
	<div class="pokeborder standard trainerlist">
		',isset($user['fave']) ? '<p class="trainerfave">' : '','
		<img src="images/trainers/',$user['trainerpic'],'.gif" class="trainerpic"/>';
		if (isset($user['fave'])) {
		echo '<img class="trainer_fave_pokemon" src="'.$baseurl.'img/anim2/'.(is_shiny($user['fave']) ? 'shiny/' : '').sprintf("%03d",round($user['fave'])).'.gif" />';
		}
		echo'
		',isset($user['fave']) ? '</p>' : '','
		<div style="margin-left: 180px;">
			<h3 style="margin-bottom: 5px;">'.( empty($user['version']) ? '' : '<span style="color:'.$pcfg[ 'color'.$user['version'] ].';" title="'.$pcfg[ 'version'.$user['version'] ].'">&bull;</span>').'<a href="?trainer='.$user['id'].'">',$user['name'],'</a></h3>
			<img src="images/t_owned.png" title="Owned Pok&eacute;mon" style="padding-right: 2px;" />',$user['total_pokemon'],' <img src="images/t_seen.png" title="Seen Pok&eacute;mon" style="padding-right: 2px;" />',$user['total_seen'],'
			',badge_strip($user['id'], $usersdatab[$user['id']], $output_method = "echo", true),'<br>
			Pokedex: ', round( ((count($user['dex']) / $total_pokemon) * 100), 2 ) ,'%
			Catches: ', $user['catches'] ,'
			Sightings: ', $user['sightings'] ,'
		</div>
	</div>';
	}
	
	$endScriptTime=microtime(TRUE);
	$totalScriptTime=$endScriptTime-$startScriptTime;
	echo '<div style="font-size:8px;text-align:center;"><img src="images/slow_search.png">'.number_format($totalScriptTime, 4).' seconds</div>';
	
	echo '
	</div>';
	
	layout_below();
	exit;
	
} 

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['compare'])) { //Allows our viewer to compare their pokeon collection against that of another user. Simple.
	
	$id = (int)$_GET['compare'];
	
	if (!$context['user']['is_logged']) {
		layout_above('Error','Error');
		echo 'You must be logged in!';
		layout_below();
		exit;
	}
	
	//Who is this user?
	$smf_userdata = pokemon_fetchMember($member_ids = $id, $output_method = 'array');
	if (empty($smf_userdata)) die('That user doesn\'t exist!');
	
	if (empty($smf_userdata)) {
		layout_above('Error','Error');
		echo 'This user doesn\'t exist!';
		layout_below();
		exit;
	}
	
	//Does this user's pokemon trainer alter-ego exist?
	$userdata = userdata($id);
	
	if (empty($userdata[0]['pokemon'])) {
		layout_above('Error','Error');
		echo 'This user owns no Pokemon!';
		layout_below();
		exit;
	}
	
	if ($id == $context['user']['id']) {
		layout_above('Error','Error');
		echo '<img src="images/oak.png" />'.$context['user']['name'].'! You can\'t compare against yourself!';
		layout_below();
		exit;
	}
	
	$vuserdata = userdata($context['user']['id']);
	
	$vowned = count(explode(',',$vuserdata[0]['pokemon']));
	$vseen = count(explode(',',$vuserdata[0]['seen']));
	$owned = count(explode(',',$userdata[0]['pokemon']));
	$seen = count(explode(',',$userdata[0]['seen']));
	
	$pokemon1 = explode(',',$vuserdata[0]['pokemon']);
	$pokemon2 = explode(',',$userdata[0]['pokemon']);
	$dex1 = explode(',',$vuserdata[0]['dex']);
	$dex2 = explode(',',$userdata[0]['dex']);
	
	$pokemon1c = array_count_values($pokemon1);
	$pokemon2c = array_count_values($pokemon2);
	
	ksort($pokemon1c);
	ksort($pokemon2c);
	
	layout_above('Comparison with '.$smf_userdata[$id]['name'],'Comparison with '.$smf_userdata[$id]['name']);
	
	echo '
	<div id="submain" style="background:url(images/vertical5px.png) top center repeat-y;overflow:auto;padding-bottom: 20px;">
	';
	
	$vtrainerpic = $vuserdata[0]['trainerpic'];
	$trainerpic = $userdata[0]['trainerpic'];
	
	//Now for each new step made, we show our status.
		echo '
		<div class="trade" style="float:left;">
			<h2><img src="'.$baseurl.'images/trainers/',$vuserdata[0]['trainerpic'],'.gif" class="trainerpic" style="float:right;"><a href="'.$baseurl.'?trainer='.$context['user']['id'].'">',$context['user']['name'],'</a> <span style="font-size:8px;line-height: 16px;"><img src="images/t_owned.png">'.$vowned.' <img src="images/t_seen.png">'.$vseen.'</span></h2>
			',badge_strip($context['user']['id'], $vuserdata, $output_method = "echo_nocruft"),'
		</div>
		<div class="trade" style="float:right;">
			<h2><img src="'.$baseurl.'images/trainers/'.$trainerpic.'.gif" class="trainerpic" style="float:right;"><a href="'.$baseurl.'?trainer='.$id.'">',$smf_userdata[$id]['name'],'</a> <span style="font-size:8px;line-height: 16px;"><img src="images/t_owned.png">'.$owned.' <img src="images/t_seen.png">'.$seen.'</span></h2>
			',badge_strip($id, $userdata, $output_method = "echo_nocruft"),'
		</div>';
		
		foreach ($pokemon as $key => $poke) {
		
			//Now we need to know whether to proceed with this particular poke. For each pokemon, check if either trainer owns it. We also need to consider shinies.
			if (in_array($key, $pokemon1) || in_array($key.'.3', $pokemon1) || in_array($key, $pokemon2) || in_array($key.'.3', $pokemon2)) {
				
				echo '
				<h3 style="text-align:center;clear:both;background:#0C162C;"><img src="img/small/'.sprintf("%03d",round($key)).'.png" alt="'.$pokemon[$key].'" /><a href="?pokemon='.$key.'">#'.sprintf("%03d",round($key)).' '.$pokemon[$key].'</a></h3>';
				
				//First, the comparer.
				echo '
				<div class="trade" style="float:left; text-align:right; '. (in_array($key, $dex1) ? 'background:url(images/in_dex.png) top left no-repeat;' : '').'">';
					
					if (array_key_exists($key, $pokemon1c)) {
						echo '<span style="font-size:48px;">'.$pokemon1c[$key].' </span>';
					} elseif (!array_key_exists($key.'.3', $pokemon1c)) echo '<span style="font-size:48px;color:#333;">0 </span>';
					
					if (array_key_exists($key.'.3', $pokemon1c)) {
						echo '<span style="font-size:48px;color:#FFCC03;">'.$pokemon1c[$key.'.3'].'</span>';
					}
					
					echo'
				</div>';
				
				//Next, the "comparee"
				echo '
				<div class="trade" style="float:right; text-align:left;  '. (in_array($key, $dex2) ? 'background:url(images/in_dex.png) top right no-repeat;' : '').'">';
					
					if (array_key_exists($key, $pokemon2c)) {
						echo '<span style="font-size:48px;">'.$pokemon2c[$key].' </span>';
					} elseif (!array_key_exists($key.'.3', $pokemon2c)) echo '<span style="font-size:48px;color:#333;">0 </span>';
					
					if (array_key_exists($key.'.3', $pokemon2c)) {
						echo '<span style="font-size:48px;color:#FFCC03;">'.$pokemon2c[$key.'.3'].'</span>';
					}
					
					echo'
				</div>';
				
				
			} else {
				echo '
				<!--<h3 style="text-align:center;">'.$pokemon[$key].'</h3>-->';
			}
		}
		
		echo'
	</div>';
	
	
	layout_below();
	$file_db = null;
	exit;
	
	
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['help'])) { //Simple enough. Mostly static help.
	
	layout_above('RMRKMon Help','Information');
	
	echo '
	<div id="submain">
		<div style="text-align:center;"><img src="images/logo_large.png" style="margin-bottom: 15px;"/></div>
		<div class="pokeborder standard" style="overflow:auto;margin-bottom: 15px;line-height: 29px;">
			<img src="images/oak.png" style="float:left;padding:10px;" /><h2 style="text-align: center;">Welcome to RMRKMon!</h2>
			<br>Hello '.$context['user']['name'].'!<br>
			<br>Before you awaits a wonderful world of catching and trading Pok&eacute;mon! Let me guide you through the basics!
		</div>
		
		<div class="pokeborder standard" style="overflow:auto;line-height: 29px;margin-bottom: 15px;">
			<h2 style="text-align: center;">Becoming A Trainer</h2><br>
			&bull;You receive your trainer license upon encountering your first pokemon. Hunt in the RMRKMon boards and you should encounter one in no time!<br><br>
			<div style="text-align:center;"><img src="images/regions.png" style="margin-bottom: 15px;"/></div>
			&bull;RMRKMon trainers are based in 2 regions - <span style="color:#66023C;">Tyria</span> and <span style="color:#7851A9;">Lisera</span>. Some pokemon are exclusive to each region! You must trade with each other to complete your Pokedex!<br><br>
			
			&bull;3 records are kept for you - Pokemon that you\'ve seen, a Pokedex, and your current inventory.<br><br>
			
			&bull;Once you have your trainer license, access your PC to choose your trainer picture. You can also choose your favourite Pokemon to stay by your side!
		</div>
		
		<div class="pokeborder standard" style="overflow:auto;line-height: 29px;margin-bottom: 15px;">
			<h2 style="text-align: center;">Encountering Pokemon</h2><br>
			<div style="text-align:center;"><img src="images/help_encountering.png" style="margin-bottom: 15px;"/></div>
			&bull;As long as you are logged in and browsing forum threads, Pokemon have a <span style="border-bottom: 2px dotted #999;" title="Higher chance in the RMRKMon boards!">chance to appear</span> alongside posts on the RMRK forums.<br><br>
			
			&bull;Some Pokemon are more common than others. You will encounter more Zubats and Taillows than the elusive Articuno or Registeel. Some mythical, ultra-legendary pokemon <span style="border-bottom: 2px dotted #999;" title="Sometimes, they may be offered as prizes by RMRKMon\'s Poke Authority">cannot be encountered at all</span>!<br><br>
			
			&bull;Ultra-rare, alternate-coloured shiny versions of all pokemon exist. You are incredibly lucky if you manage to find a shiny pokemon!
		</div>
		
		<div class="pokeborder standard" style="overflow:auto;line-height: 29px;margin-bottom: 15px;">
			<h2 style="text-align: center;">Catching Pokemon</h2><br>
			<div style="text-align:center;"><img src="images/help_catching.png" style="margin-bottom: 15px;"/></div>
			&bull;Click an encountered pokemon to throw your Pokeball and try catching it!<br><br>
			
			&bull;Your Pokeball must recharge over time. If you\'ve very recently caught a Pokemon it will be <span style="border-bottom: 2px dotted #999;" title="After an hour or two it will be quite easy again. A few hours more, and good as new!">quite difficult to catch another again so soon</span>.
		</div>
		
		<div class="pokeborder standard" style="overflow:auto;line-height: 29px;margin-bottom: 15px;">
			<h2 style="text-align: center;">Trading</h2><br>
			<div style="text-align:center;"><img src="images/help_trading.png" style="margin-bottom: 15px;"/></div>
			&bull;Trainer licenses automatically include trading privileges!<br><br>
			
			&bull;If a trainer is open to trades or has you as a friend, <img src="images/tradeball.png" style="vertical-align:middle;padding-right:5px;" />Open a trade with them from their profile.<br><br>
			
			&bull;You can trade up to '.$pcfg['trade_multi_limit'].' Pokemon at once in a single trade, and participate in '.$pcfg['trade_simultaneous_limit'].' trades at the same time!<br><br>
			
			&bull;All completed trades are added to the public trade register, for all to see.
		</div>
	</div>';
	
	layout_below();
	exit;
	
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['filecheck'])) { //Let trainers see who owns what pokemon, how many times this pokemon has been caught / encountered and so on.
	
	layout_above('Image File Check','Image File Check');
	
	echo '<div id="submain" style="font-size:8px;">
	<div style="width: 270px; float:left;clear:left;">Image examples:</div> <a href="'.$baseurl.'img/001.png">Base</a> <a href="'.$baseurl.'img/shiny/001.png">Base</a><span style="color:yellow;">*</span> <a href="'.$baseurl.'img/anim/001.gif">/anim</a> <a href="'.$baseurl.'img/anim2/001.gif">/anim2</a> <a href="'.$baseurl.'img/anim2/shiny/001.gif">/anim2</a><span style="color:yellow;">*</span> <a href="'.$baseurl.'img/back/001.png">/back</a> <a href="'.$baseurl.'img/back/shiny/001.png">/back</a><span style="color:yellow;">*</span> <a href="'.$baseurl.'img/full/001.png">/full</a> <a href="'.$baseurl.'img/global/001.png">/global</a> <a href="'.$baseurl.'img/global900/001.png">/global900</a> <a href="'.$baseurl.'img/named/001.png">/named</a> <a href="'.$baseurl.'img/named/shiny/001.png">/named</a><span style="color:yellow;">*</span> <a href="'.$baseurl.'img/small/001.png">/small</a></span><br>';
	
	foreach($pokemon as $key => $mon) {
		echo '<div style="border-bottom: 2px dotted #555;"><div style="width: 270px; float:left;clear:left;font-size: 16px;">#'.sprintf("%03d",$key).' '.$pokemon[$key].'</div>
		'.(file_exists('img/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">Base</span>' : '<span style="color:red;">Base</span>').'
		'.(file_exists('img/shiny/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">Base<span style="color:yellow;">*</span></span>' : '<span style="color:red;">Base<span style="color:yellow;">*</span></span>').'
		
		'.(file_exists('img/anim/'.sprintf("%03d",$key).'.gif') ? '<span style="color:green;">/anim</span>' : '<span style="color:red;">/anim</span>').'
		
		'.(file_exists('img/anim2/'.sprintf("%03d",$key).'.gif') ? '<span style="color:green;">/anim2</span>' : '<span style="color:red;">/anim2</span>').'
		'.(file_exists('img/anim2/shiny/'.sprintf("%03d",$key).'.gif') ? '<span style="color:green;">/anim2<span style="color:yellow;">*</span></span>' : '<span style="color:red;">/anim2<span style="color:yellow;">*</span></span>').'
		
		'.(file_exists('img/back/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">/back</span>' : '<span style="color:red;">/back</span>').'
		'.(file_exists('img/back/shiny/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">/back<span style="color:yellow;">*</span></span>' : '<span style="color:red;">/back<span style="color:yellow;">*</span></span>').'
		
		'.(file_exists('img/full/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">/full</span>' : '<span style="color:red;">/full</span>').'
		
		'.(file_exists('img/global/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">/global</span>' : '<span style="color:red;">/global</span>').'
		'.(file_exists('img/global900/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">/global900</span>' : '<span style="color:red;">/global900</span>').'
		
		'.(file_exists('img/named/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">/named</span>' : '<span style="color:red;">/named</span>').'
		'.(file_exists('img/named/shiny/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">/named<span style="color:yellow;">*</span></span>' : '<span style="color:red;">/named<span style="color:yellow;">*</span></span>').'
		
		'.(file_exists('img/small/'.sprintf("%03d",$key).'.png') ? '<span style="color:green;">/small</span>' : '<span style="color:red;">/small</span>').'
		</div>';
	}
	
	echo '<div style="clear:both;"></div></div>';
	layout_below();
	exit;
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

elseif (isset($_GET['gamecorner'])) { //Gambling!
	
	layout_above('Slots Test','Slots Test');
	
	echo '
	<ul class="pokeslot">';
	
		foreach ($pokemon as $key => $poke) {
			echo '<li><img src="'.$baseurl.'img/named/'.sprintf("%03d",$key).'.png" /></li>';
		}
		
		echo'
	</ul>
	
	<input type="button" id="playBtn" value="play">
	<script type="text/javascript" charset="utf-8">

    $(\'.pokeslot\').jSlots({
        spinner : \'#playBtn\',
        winnerNumber : 7,
		easing : \'easeOutSine\',
    });

</script>';
	
	layout_below();
	exit;
	
}

elseif (isset($_GET['sprite'])) {
	
		layout_above('Sprite Test','Sprite Test');
		
		echo '<div id="submain" style="font-size:8px; overflow:auto;">The following 1,440 pokemon images are loaded via a technique known as <a href="http://css-tricks.com/css-sprites/" target="_blank">CSS Spriting</a>. Instead of loading 1,440 image files, only 6 images and 1.1MB of data are actually being loaded. The performance in your browser should be greatly increased.<br><hr>';
		
		echo '<h2>Normal Sprites</h2>';
		foreach ($pokemon as $key => $poke) {
			cprite($key, true);
		}
		
		echo '<h2 style="clear:both;">Shiny Sprites</h2>';
		
		foreach ($pokemon as $key => $poke) {
			cprite($key.'.3');
		}

		echo '</div>';
		layout_below();
		exit;
		
} 

elseif (isset($_GET['code'])) { //BBCode generator. Also takes strings and generates a list from them. Eventually. Even more eventually, allows pre-loading of a particular trainer's inventory.

	if ($context['user']['is_logged']) {
		$userdata = userdata($context['user']['id']);
		
		if (!empty($userdata[0]['pokemon'])) {
			$active = true;
			$o = explode(',', $userdata[0]['pokemon']);
			$s = explode(',', $userdata[0]['seen']);
			$d = explode(',', $userdata[0]['dex']);
		}
		
	}
	
	if (isset($_POST['poke_pick'])) {
		
		//Ok, we need to validate the entries. This is code that should never run.
		$chosen = $_POST['poke_pick'];
		$nchosen = array_map('round', $chosen);
		
		//Make sure each pokemon submitted is actually a pokemon in the system.
		foreach ($nchosen as $npoke) {
			//echo $cpoke.'<br>';
			if (!isset($pokemon[$npoke])) die('Unknown Pokemon Submitted');
		}
		
		//All shinies, if any, come last from the form. Put them back in order with their numerical counterparts.
		sort($chosen);
		
		layout_above('BBCode Generator','BBCode List Generator');
		echo '
	<div id="submain">';
		
		echo '<h2 style="text-align:center;">Generated List</h2><br>
		<div style="font-size:8px; text-align: center;">Paste on the forums - the [x] code will be hidden.</div>
		<textarea wrap="off" id="poken" style="width: 50%; height: 12px; padding: 2px; font-size: 8px;overflow:hidden;font-family: \'Press Start 2P\', sans-serif;margin: 0 auto; display:block; margin: 0 auto;" onclick="SelectAll(\'poken\')">';
		//print_r($_POST['poke_pick']);
		echo '[x]Code for this list: ',base64_encode(implode(',',$chosen)),'[/x][size=8pt]List Made: ',date('D jS M Y, g:i a'),'[/size]
';
		
		foreach ($chosen as $cpoke) {
			echo '[url='.$baseurl.'?pokemon='.round($cpoke).';'. ( is_shiny($cpoke) ? $pokemon[round($cpoke)].'(Shiny)' : $pokemon[$cpoke] ) .'][img]'.$baseurl.'img/named/'. ( is_shiny($cpoke) ? 'shiny/' : '' ) .''.sprintf("%03d",$cpoke).'.png[/img][/url]';
		}
		
		echo '</textarea><br>
		<div style="font-size:8px; text-align: center;"><a href="',$baseurl,'?code;precode=',base64_encode(implode(',',$chosen)),'">Link to this code</a></div><br>
		<h2 style="text-align:center;">Will Appear As:</h2><br>';
		
		foreach ($chosen as $cpoke) {
			echo '<a href="'.$baseurl.'?pokemon='.round($cpoke).';'. ( is_shiny($cpoke) ? $pokemon[round($cpoke)].'(Shiny)' : $pokemon[$cpoke] ) .'"><img src="'.$baseurl.'img/named/'. ( is_shiny($cpoke) ? 'shiny/' : '' ) .''.sprintf("%03d",$cpoke).'.png" /></a>';
		}
		
		echo '</div>';
		layout_below();
		exit;
		
	}
	
	//Perhaps an existing code has been submitted. In that case, check off pokemon from that code. We need to decode it first.
	if (isset($_REQUEST['precode'])) {
		$pcode = base64_decode(trim($_REQUEST['precode']));
		$pc = explode(',',$pcode);
		//print_r($pc);
		
		//We should also validate this input.
		$npc = array_map('round', $pc);
		foreach ($npc as $npoke) {
			//echo $cpoke.'<br>';
			if (!isset($pokemon[$npoke])) {
				layout_above('BBCode Generator','BBCode List Generator');
				echo 'There was a problem with the code you submitted. Are you sure you copied it correctly?';
				layout_below();
				exit;
			}
			
			//If people put in bullshit, it'll only run true for JUST missingno.
			if (count($pc) == 1 && $pc[0] == 0) {
				layout_above('BBCode Generator','BBCode List Generator');
				echo 'There was a problem with the code you submitted :( Are you sure you copied it correctly?';
				layout_below();
				exit;
			}
			
		}
		
	}
	
	layout_above('BBCode Generator','BBCode List Generator');
	
	echo '
	<div id="submain">';
		
		echo '<h2 style="text-align:center">Paste Existing Code</h2><br>
		<div style="font-size:8px;text-align:center; display:none;">Just the numbers</div>
		<form method="post" action="?code" style="text-align:center;">
			<textarea style="width: 30%; height: 16px;margin-left: -50px;" name="precode"></textarea><input type="submit" value="Process" style="position: absolute;">
		</form><br>';
		
		if (isset($pc)) {
			echo '<div style="text-align:center;font-size:8px;">(Using existing code: <a href="',$baseurl,'?code;precode=',$_REQUEST['precode'],'">',substr($_REQUEST['precode'], 0, 24),'...</a> <a href="'.$baseurl.'?code"><img src="images/cross.png" style="vertical-align: middle;" /></a>)</div><br>';
		}
		
		echo'
		<h2 style="text-align:center;">Choose Pokemon to build a BBCode List</h2><br>
		
		<div style="text-align:center; font-size: 8px;">Filter: <input type="text">
		',( $active ? '<br>Keywords: !isseen/!notseen, !isowned/!notowned, !isdex/!notdex' : '' ),'
		</div><br>
		
		<script type=\'text/javascript\'>//<![CDATA[ 
			$(window).load(function(){
				$(\'input\').keyup(function() {
					filter(this); 
				});

				function filter(element) {
					var value = $(element).val();
					$(".pbox").each(function () {
						if ($(this).text().indexOf(value) > -1) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});
				}
			});//]]>  

		</script>
		
		
		<form method="post" action="?code" id="release" style="background:url(images/vertical5px.png) top center repeat-y;overflow:auto;padding-bottom: 20px;line-height: normal;">';
		
		//Now we'll show all pokemon. Normal and shiny.
		echo'
		<div class="trade" style="float:left;">
			<h2>Normal</h2>';
			
			$pokecounter = 0;
			
			foreach ($pokemon as $poke => $name) {
				echo '
				<div class="pbox pokeborder" ', $active ? 'style="padding-bottom: 0px;"' : '' ,'><div class="pokenumber">#',$poke,'</div>
			
				<input type="checkbox" id="n'.$poke.'_'.$pokecounter.'" class="regular-checkbox big-checkbox tpickp" name="poke_pick[]" value="'.$poke.'"';
				
				if (isset($pc)) {
					if (in_array($poke, $pc)) echo ' checked';
				}
				
				echo'><label for="n'.$poke.'_'.$pokecounter.'"></label>',cprite($poke, false),'<br>
				<span class="flc">',strtolower($name),'</span>';
				
				//If the user is logged in, represent whether this pokemon is in one of their inventories.
				if ($active) {
					echo '<br>';
					if (in_array($poke,$s)) {
						echo '<span style="font-size: 0;">!isseen</span><img src="'.$baseurl.'images/a_seen_yes.png" />';
					} else echo '<span style="font-size: 0;">!notseen</span><img src="'.$baseurl.'images/a_seen_no.png" />';
					if (in_array($poke,$o)) {
						echo '<span style="font-size: 0;">!isowned</span><img src="'.$baseurl.'images/a_owned_yes.png" />';
					} else echo '<span style="font-size: 0;">!notowned</span><img src="'.$baseurl.'images/a_owned_no.png" />';
					if (in_array($poke,$d)) {
						echo '<span style="font-size: 0;">!isdex</span><img src="'.$baseurl.'images/a_dex_yes.png" />';
					} else echo '<span style="font-size: 0;">!notdex</span><img src="'.$baseurl.'images/a_dex_no.png" />';
				}
				
				echo'</div>';
				
				$pokecounter++;
			}
		
		echo '</div>';
		
		//Shiny time.
		echo'
		<div class="trade" style="float:right;">
			<h2>Shiny</h2>';
			
			$pokecounter = 0;
			
			foreach ($pokemon as $poke => $name) {
				echo '
				<div class="pbox pokeborder shiny" style="padding-bottom: 0px;"><div class="pokenumber">#',$poke,'</div>
				
				<input type="checkbox" id="s'.$poke.'_'.$pokecounter.'" class="regular-checkbox big-checkbox tpickp" name="poke_pick[]" value="'.$poke.'.3"';
				
				if (isset($pc)) {
					if (in_array($poke.'.3', $pc)) echo ' checked';
				}
				
				echo'><label for="s'.$poke.'_'.$pokecounter.'"></label>',cprite($poke.'.3', false),'<br>
				<span class="flc">',strtolower($name),'</span>';
				
				//If the user is logged in, represent whether this pokemon is in one of their inventories.
				if ($active) {
					echo '<br>';
					if (in_array($poke.'.3',$s)) {
						echo '<span style="font-size: 0;">!isseen</span><img src="'.$baseurl.'images/a_seen_yes.png" />';
					} else echo '<span style="font-size: 0;">!notseen</span><img src="'.$baseurl.'images/a_seen_no.png" />';
					if (in_array($poke.'.3',$o)) {
						echo '<span style="font-size: 0;">!isowned</span><img src="'.$baseurl.'images/a_owned_yes.png" />';
					} else echo '<span style="font-size: 0;">!notowned</span><img src="'.$baseurl.'images/a_owned_no.png" />';
					if (in_array($poke,$d)) {
						echo '<span style="font-size: 0;">!isdex</span><img src="'.$baseurl.'images/a_dex_yes.png" />';
					} else echo '<span style="font-size: 0;">!notdex</span><img src="'.$baseurl.'images/a_dex_no.png" />';
				}
				
				echo'</div>';
				
				$pokecounter++;
			}
		
		echo '</div>';
		
		echo '<input type="submit" value="Select" class="pokeborder update" style="display: block; margin: 0 auto;">';
		
		echo'
		</form>';
		
		echo'
	</div>';
	
	layout_below();
	exit;
	
}

/////////////////////////////////////////////////////////////////////

elseif (isset($_GET['test'])) {
	
	layout_above('Anim test','anim test');
	echo '
		
		
		<style type="text/css">
			
		</style>
		
		<script type="text/javascript">
		
		</script>
		
		
		When all is said and done, hovering over the below poké cards should result in animated sprites. <br>
		
		<div class="pbox pokeborder"><div class="pokenumber">#100</div>
		',cprite(100, false),'
		<br>
		<span class="flc">voltorb</span></div>
	<div style="clear:both;"></div>';
	layout_below();
	exit;
}

else { //Default frontpage
	global $context, $file_db, $pokemon;
	
	layout_above('RMRKMon','RMRKMon');
	
	if (!$context['user']['is_guest']) {$userdata = userdata($context['user']['id']);}
	//else {echo 'You should login!'; layout_below(); die(); }
	
	//Sow a list of trainers, ordered by most recent catch
	$recents = $file_db->query("SELECT * FROM trainers WHERE lastcaught BETWEEN 1363713205 AND 9999999999 ORDER BY lastcaught DESC LIMIT 20")->fetchAll();
	$stats = $file_db->query('SELECT total_trainers, total_encounters, total_captures FROM stats')->fetchAll();
	
	//We'd like to show links to trainers from our user's buddy lists.
	if ($context['user']['is_logged']) {
		if (!empty($user_info['buddies'])) {
			
			//We don't want to go overboard here. A user's buddy list can be unlimited, so we'll limit it ourselves. To prevent users past our limit never appearing, we'll shuffle their buddy list first.
			shuffle($user_info['buddies']);
			$buddycount = 0;
			
			foreach ($user_info['buddies'] as $buddy) {
				if ($buddycount > 20) break;
				$recentcatchers[] =	$buddy;
				$loadedbuddies[] = $buddy; //Later we can foreach() through $loadedbuddies.
				$buddycount++;
			}
		}
	}
	
	//Before we go further, we want to get their usernames from SMF.
	foreach ($recents as $recent) {
		$recentcatchers[] = $recent['id'];
	}
	
	//It may be that a recent catcher is also on the viewer's buddy list. We don't want duplicates.
	if (isset($recentcatchers)) $recentcatchers = array_unique($recentcatchers);
	
	if (isset($recentcatchers)) $smf_recents = pokemon_fetchMember($member_ids = $recentcatchers, $output_method = 'array');
	
	
	echo '
	<div class="pokeborder standard" style="text-align:center; overflow: auto;"><h3>Statistics</h3>
		<span style="float:left;">Trainers: '. number_format($stats[0]['total_trainers']) .'</span>
		<span style="float:right;">Seen: '. number_format($stats[0]['total_encounters']) .'</span>
		Caught: '. number_format($stats[0]['total_captures']) .'';
		
		echo'
	</div><br>';
	
	if (empty($recents)) { //Should never be the case. Perhaps only after a wipe.
		echo 'No recent trainer activity! Get out there and catch some Pokemon!';
		layout_below();
		exit;
	}

	
	echo '<div style="text-align: center;">Recently Active Trainers:<br><br>';
	
	foreach ($recents as $recent) {
	
		if ($recent['lastcaught'] == "0" || empty($recent['lastcaught'])) continue;
		
		if (empty($recent['favetype'])) $recent['favetype'] = 13;
	
		echo '<div class="recent_trainer pokeborder">',isset($recent['fave']) ? '<p class="trainerfave" style="background: url('.$baseurl.'images/bases/typed/'.$recent['favetype'].'.png) center center no-repeat;">' : '','<img class="trainerpic" src="images/trainers/'.$recent['trainerpic'].'.gif" style="z-index: 20;" />',isset($recent['fave']) ? '<img class="trainer_fave_pokemon" src="'.$baseurl.'img/anim2/'.(is_shiny($recent['fave']) ? 'shiny/' : '').sprintf("%03d",round($recent['fave'])).'.gif" style="float:left;" />':'','',isset($recent['fave']) ? '</p>' : '','<a href="?trainer='.$recent['id'].'">'.$smf_recents[ $recent['id'] ]['name'].'</a> ',(badge_strip($recent['id'], $recent, $output_method = "echo_nocruft")),'<hr>
		<div>Last caught: ',date('M jS, g:i a', $recent['lastcaught']),'<br><br><br>
		Trainer Since: ',date('M jS, g:i a', $recent['starttime']),'</div></div>';
	}
	
	echo '</div>';
	
	if (isset($loadedbuddies)) {
		sort($loadedbuddies);
			echo '<div class="pokeborder standard" style="text-align:center; overflow: auto; margin-top: 30px; line-height: 20px;"><h3>Friends List Trainers</h3><div style="font-size: 8px;">';
			foreach ($loadedbuddies as $buddy) {
				echo '<a href="?trainer='.$buddy.'">'.$smf_recents[ $buddy ]['name'].'</a> ';
			}
			echo '</div>';
	}
	
	layout_below();
	
	$file_db = null;
}