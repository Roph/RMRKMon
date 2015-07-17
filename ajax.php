<?php
require_once('../SSI.php');
require_once('lib.php');
require_once('chance.php');
require_once('db.php');
require_once('settings.php');
require_once('misc/bases.php');

if ($_REQUEST['ajax'] == "random_encounter" && isset($_REQUEST['key']) && isset($_REQUEST['sesc'])) { //Spit out a random poke.

	//Not being sneaky, are you?
	if ($_REQUEST['key'] != md5(date('jg').$context['user']['id']) || $_REQUEST['sesc'] != $context['session_id']) die('<!-- Bad Data -->');
	
	//Here we might do some extra checking - if the user is an existing trainer and has a last caught value, we could "cool down" against spammers, making it very difficult to rapidly acquire pokemon.
	$userdata = userdata($context['user']['id']);
	
	//Is this the first time you've encountered a pokemon? Welcome to the trainer club.
	if (empty($userdata)) {
		newtrainer($context['user']['id']);
		sleep(1);
		$userdata = userdata($context['user']['id']);
	}
	
	//Prevent encounters too soon after recently capturing a pokemon.
	$lastcapture = (time() - $userdata[0]['lastcaught']);
	
	//If it's been less than 30 minutes, you can't encounter any more.
	if ($lastcapture < $pcfg['encounter_cooldown_block']) die('<!-- Too soon since last capture -->');
	//If it's been under an hour, you have a 50/50 chance to not get this encounter.
	if ($lastcapture < $pcfg['encounter_cooldown_half']) {
		$test = mt_rand(0,10);
		if ($test < 5) die('<!-- Too soon since last capture, partially blocked -->');
	}
	
	//If you don't have a version yet, this is probably your first encounter. Or, I've just added the version update.
	if (empty($userdata[0]['version'])) {
		$version = mt_rand(1,2);
		update_trainer($context['user']['id'], 'version', $version);
	}
	
	if (!isset($version)) $version = $userdata[0]['version'];
	
	if ($version == 1) $blocked = 2;
	else $blocked = 1;
	
	//Now we want to remove pokemon of the opposite version from the pool.
	foreach ($affinity as $key => $value) {
		if ($value == $blocked) {
			unset($pokemon[$key]);
			unset($e_chance[$key]);
			unset($c_chance[$key]);
		}
	}
	
	//But, doing this has broken our sequential key structure. We need to make new arrays that are sequential and still relate to each other, minus the blocked pokemon.
	foreach ($pokemon as $key => $value) {
		$values[] = $key;
		$weights[] = $e_chance[$key];
	}
	
	//Now we encounter based on weights B)
	$encountered = weighted_random_simple($values, $weights);
	$encountered_shiny = false;
	
	$encountered['id'] = $encountered['name'];
	
	
	//But wait, there's more! Perhaps our user is extra lucky and this pokemon will be shiny! $shinychance is an inverse from 200. So for example, 1% is 198. 7% is 186.
	if (date('D') == "Sun") $shinychance = 186;
	else  $shinychance = 198;
	
	if (mt_rand(0, 200) > $shinychance) {
		$encountered['id'] = $encountered['id'].'.3';
		$encountered_shiny = true;
	}
	
	$_SESSION[ $context['user']['id'] ]['seen_pokemon'] = $encountered['id'];
	
	echo '<div style="width:160px; background:url('.$baseurl.'images/bases/'.$p_base[round($encountered['id'])].'.png) center 95% no-repeat;"><img src="'.$baseurl.'img/anim2/' , ( $encountered_shiny ? 'shiny/'.sprintf("%03d", $encountered['id']) : sprintf("%03d", $encountered['id']) ) , '.gif" style="cursor: pointer;" id="encountered_pokemon" title="Capture!" onclick="$(\'#pokemon\').load(\''.$baseurl.'?ajax=capture\')" /></div><span style="font-size: 10px;"><strong>',$encountered_shiny? '<span style="text-shadow: 0px 0px 3px #FFCC03;font-size: 120%;">SHINY</span><br />' : '',''.($affinity[round($encountered['id'])] != 0 ? '<img src="'.$baseurl.'images/dot'.$affinity[round($encountered['id'])].'.png" title="'.$pcfg['version'.$affinity[round($encountered['id'])]].' Exclusive">' : '').'<a href="'.$baseurl.'?pokemon='.round($encountered['id']).'" target="_blank">',$pokemon[round($encountered['id'])],'</a></strong> (#',round($encountered['id']),') Appeared!</span><br />
	';
	
	//Output some helpful images that remind the trainer if they already have seen, own or have owned this pokemon.
	if (in_array( $encountered['id'], explode(',',$userdata[0]['seen']) ) ) {
		echo '<img src="'.$baseurl.'images/a_seen_yes.png" title="You have encountered '.$pokemon[round($encountered['id'])].' before" />';
	} else echo '<img src="'.$baseurl.'images/a_seen_no.png" title="This is your first encounter for '.$pokemon[round($encountered['id'])].'!" />';
	
	if (in_array( $encountered['id'], explode(',',$userdata[0]['pokemon']) ) ) {
		echo '<img src="'.$baseurl.'images/a_owned_yes.png" title="You already own '.$pokemon[round($encountered['id'])].'" />';
	} else echo '<img src="'.$baseurl.'images/a_owned_no.png" title="You don\'t own '.$pokemon[round($encountered['id'])].'!" />';
	
	if (in_array( round($encountered['id']), explode(',',$userdata[0]['dex']) ) ) {
		echo '<img src="'.$baseurl.'images/a_dex_yes.png" title="'.$pokemon[round($encountered['id'])].' is in your Pokedex" />';
	} else echo '<img src="'.$baseurl.'images/a_dex_no.png" title="'.$pokemon[round($encountered['id'])].' is absent from your Pokedex!" />';
	
	
	//Log this sighting.
	see_pokemon($context['user']['id'], $encountered['id']);
	
	$file_db = null;
	
}

elseif ($_REQUEST['ajax'] == "capture") { //Attempt to capture the pokemon. For now, 100% success rate. Master balls for everyone!
	
	if (!isset($_SESSION[ $context['user']['id'] ]['seen_pokemon'])) exit('The pokemon ran away!');
	
	//Immediately discard the session data - otherwise ultra-fast subsequent hits can catch multiple pokemon from one encounter.
	$seenpoke = $_SESSION[ $context['user']['id'] ]['seen_pokemon'];
	unset($_SESSION[ $context['user']['id'] ]);
	session_write_close();
	
			//To deal with spamming dickheads, we're going to do some extra analysis before deciding on a final capturing chance.
			$capture_chance = $pcfg['capture_chance_default']; //Default, in percent
			$capturenotice = '! ';
			
			$capture_userdata = userdata($context['user']['id']);
			if (!empty($capture_userdata[0]['lastcaught'])) {
				//You've caught something in the past. But how far in the past?
				$lastcapture = (time() - $capture_userdata[0]['lastcaught']); //Seconds since last catch.
				if ($lastcapture < 28801) {
					if ($lastcapture < 60) $capture_chance = $pcfg['capture_chance_1min']; //1min
					elseif ($lastcapture < 120) $capture_chance = $pcfg['capture_chance_2mins']; //2min
					elseif ($lastcapture < 900) $capture_chance = $pcfg['capture_chance_15mins']; //15 mins
					elseif ($lastcapture < 1800) $capture_chance = $pcfg['capture_chance_30mins']; //30 mins
					elseif ($lastcapture < 7200) $capture_chance = $pcfg['capture_chance_2hours']; //2hrs
					elseif ($lastcapture < 14400) $capture_chance = $pcfg['capture_chance_4hours']; //4hrs
					elseif ($lastcapture < 21600) $capture_chance = $pcfg['capture_chance_6hours']; //6hrs
					elseif ($lastcapture < 28800) $capture_chance = $pcfg['capture_chance_8hours']; //8hrs
					
					$capturenotice = ' <span title="Your capture chance has been affected by cooldown. Leniency: '.$capture_chance.'">(!)</span>';
				}
			}
			
			//The following two rules overwrite any pre-calculated chance for special cases.
			if (is_shiny($seenpoke)) $capture_chance = $pcfg['capture_chance_shiny']; //Default shiny capture chance.
			//if ($context['user']['id'] == 7408) $capture_chance = 1; // TDS. Even if it's shiny.
			
			if ($context['user']['id'] == 1) {
				$capture_chance = 99;
			}
			
			

	if (mt_rand(0, 100) < $capture_chance){ //Successful catch
		
		capture_pokemon($context['user']['id'], $seenpoke);
		dex_pokemon($context['user']['id'], round($seenpoke)); //Don't have shinies in pokedexes! They count as normal.
		
		echo '<img src="'.$baseurl.'images/catch.gif" alt="pokeball" /><br /><span style="font-size: 10px; font-weight:bold;">'.(is_shiny($seenpoke) ? 'Shiny ' : '').''.$pokemon[round($seenpoke)].' was caught!</span>';
	} else echo $pokemon[round($seenpoke)].' escaped'.$capturenotice;
	
	
	$file_db = null;
}

elseif ($_REQUEST['ajax'] == "list") { //Spit out various collections of pokemon images.
	
	//We require a user ID no matter what.
	if (!isset($_REQUEST['id'])) die('ID required!');
	$id = (int)$_REQUEST['id'];
	
	//Before we go further, we need to know what type of list they want.
	if (!isset($_REQUEST['type'])) die('List type required! e.g. ?ajax=list&id=1&type=seen');
	if ($_REQUEST['type'] != 'seen' && $_REQUEST['type'] != 'owned' && $_REQUEST['type'] != 'dex') die('Valid types: seen, owned, dex');
	
	//Does this ID have any info?
	$userdata = userdata($id);
	if (empty($userdata)) die('No data for this user!');
	
	$ajaxnotice = '<div style="position:fixed; width: 100%; height: 100%; padding: 30px; text-align: left; font: 20px tahoma;z-index: 1000; background:#fff;" id="ajaxnotice">Don\'t load these URLs directly. If you\'re seeing this inside another page, please hard refresh (Ctrl+F5).<br><br>
	<a href="'.$baseurl.'?trainer='.$id.'">Back</a></div>';
	
	//Good to go. Handle list cases
	if ($_REQUEST['type'] == "seen") {
		
		//This should never be the case, but..
		if (empty($userdata[0]['seen'])) die('This user has seen no pokemon! :(');
		
		$seen_pokemon = explode(',',$userdata[0]['seen']);
		
		//To distinguish pokemon that they have only seen but not either owned or currently own, we'll compare to their pokedex.
		$dex_pokemon = explode(',',$userdata[0]['dex']);
		
		sort($seen_pokemon);
		
		echo $ajaxnotice.'<div style="height: 20px;clear:both;"></div><div style="clear:both;padding: 20px 0px; text-align: center; border-top: 5px dashed #555;">Pokemon Sightings:</div>';
		
		foreach ($seen_pokemon as $poke) {
			echo '<div class="pbox pokeborder'.(is_shiny($poke) ? ' shiny' : '').'"><div class="pokenumber">#',round($poke),'</div><a href="?pokemon='.round($poke).'"><img src="'.$baseurl.'img/'.(is_shiny($poke) ? 'shiny/' : ''.'').sprintf("%03d",round($poke)).'.png" ',in_array(round($poke), $dex_pokemon) ? '' : 'style="opacity: 0.5;"','/></a><br>
			<span>',$pokemon[round($poke)],'</span></div>';
		}
		
	} elseif ($_REQUEST['type'] == "owned") {
		
		//This could be the case.
		if (empty($userdata[0]['pokemon'])) die('This trainer has no pokemon');
		
		$owned_pokemon = explode(',',$userdata[0]['pokemon']);
		
		sort($owned_pokemon);
		
		//We only wish to show each pokemon once, but still recognise duplicates.
		$dupe_check = array_count_values($owned_pokemon);
		
		echo $ajaxnotice.'<div style="height: 20px;clear:both;"></div><div style="clear:both;padding: 20px 0px; text-align: center; border-top: 5px dashed #555;">Owned Pokemon:</div>';
		
		foreach ($owned_pokemon as $poke) {
			
			if (isset($dupe_shown)) {
				if (in_array($poke, $dupe_shown)) continue; 
			}
			
			echo '<div class="pbox pokeborder'.(is_shiny($poke) ? ' shiny' : '').'"><div class="pokenumber">#',round($poke),'</div><a href="?pokemon='.round($poke).'"><img src="'.$baseurl.'img/'.(is_shiny($poke) ? 'shiny/' : '').sprintf("%03d",round($poke)).'.png" /></a><br>
			<span'.(is_shiny($poke) ? ' class="shiny">' : '>').$pokemon[round($poke)].'</span>',$dupe_check[$poke] > 1 ? '<div class="multipoke"><img src="'.$baseurl.'images/pokeballsmall.png" />'.$dupe_check[$poke].'</div>' : '','</div>';
			
			if ($dupe_check[$poke] > 1) {
				$dupe_shown[] = $poke;
			}
		}
		
	} elseif ($_REQUEST['type'] == "dex") {
		
		//This could still be the case.
		if (empty($userdata[0]['dex'])) die('This trainer\'s pokedex is empty');
		
		$dex_pokemon = explode(',',$userdata[0]['dex']);
		
		sort($dex_pokemon);
		
		echo $ajaxnotice.'<div style="height: 20px;clear:both;"></div><div style="clear:both;padding: 20px 0px; text-align: center; border-top: 5px dashed #555;">Pokedex Entries:</div>';
		
		foreach ($dex_pokemon as $poke) {
			echo '<div class="pbox pokeborder"><div class="pokenumber">#',round($poke),'</div><a href="?pokemon='.round($poke).'"><img src="'.$baseurl.'img/'.sprintf("%03d",round($poke)).'.png" /></a><br>
			<span>',$pokemon[round($poke)],'</span></div>';
		}
		
	}
	
	$file_db = null;
}

?>