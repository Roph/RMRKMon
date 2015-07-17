<?php //Will probably never be used. But why not.
/*Error codes:

1: Missing parameters
2: Trainer does not exist
3: Email lookup not allowed

*/

require_once('settings.php');

if (!isset($_GET['html'])) header('Content-Type: application/json');

$error[1] = '{"error":{"code":1,"description":"Missing Parameters"}}';

$error[2] = '{"error":{"code":2,"description":"Trainer Does Not Exist"}}';

$error[3] = '{"error":{"code":3,"description":"Email Address Name Lookup Forbidden"}}';

//First, weed out the stupid and the curious.
if (empty($_GET)) die($error[1]);


//Include our stuff.
require_once('../SSI.php');
require_once('smf.php');
require_once('lib.php');
require_once('libextra.php');
require_once('db.php');
require_once('chance.php');
require_once('misc/bases.php');

//And now the conditionals.
if (isset($_GET['trainer']) || isset($_GET['user'])) {
	
	//Extra handling for username requests.
	if (isset($_GET['user'])) {
		
		//Before we take one step, we should block searches by email address. findMembers() is not (regular) user facing by default - simply exposing it allows reverse email lookups, which is naughty.
		if (filter_var($_GET['user'], FILTER_VALIDATE_EMAIL)) {
			die($error[3]);
		}
		
		require_once('../Sources/Subs-Auth.php');
		$possible_user = array($_GET['user']); //SMF's function we're appropriating expects an array no matter what.
		$userinfo = findMembers($possible_user);
		
		//If I don't know this name, sorry. No trainer.
		if (empty($userinfo)) die($error[2]);
		
		//SMF "helpfully" keys the result by user ID. Which is the whole point of this search: we don't know it..
		reset($userinfo);
		$id = key($userinfo);
	}

	if (!isset($_GET['user'])) $id = (int)$_GET['trainer'];
	
	$userdata = userdata($id);
	$userdata = $userdata[0];
	if (empty($userdata)) die($error[2]);
	
	$smfdata = ssi_fetchMember($member_ids = $id, $output_method = 'array');
	$smfdata = $smfdata[$id];
	
	//print_r($smfdata);
	
	$trainer = array();
	
	if (empty($userdata['lastcaught'])) $userdata['lastcaught'] = 0;
	
	//First, simple stuff like their name and details.
	$trainer['response_time'] = time();
	$trainer['name'] = $smfdata['name'];
	$trainer['id'] = $id;
	empty($smfdata['gender']['name']) ? $trainer['gender'] = null : $trainer['gender'] = strtolower($smfdata['gender']['name']);
	$trainer['member_since'] = $smfdata['registered_timestamp'];
	$trainer['trainer_since'] = (int)$userdata['starttime'];
	$trainer['last_login'] = $smfdata['last_login_timestamp'];
	$trainer['last_caught'] = (int)$userdata['lastcaught'];
	$trainer['is_online'] = $smfdata['online']['is_online'];
	$trainer['page'] = $baseurl.'?trainer='.$id;
	$trainer['profile'] = $smf_baseurl.'?action=profile;u='.$id.';sa=forumProfile';
	$trainer['picture']['id'] = (int)$userdata['trainerpic'];
	$trainer['picture']['href'] = $baseurl.'images/trainers/'.$userdata['trainerpic'].'.gif';
	empty($smfdata['avatar']['href']) ? $trainer['picture']['avatar_href'] = 'http://rmrk.net/noavatar.png': $trainer['picture']['avatar_href'] = $smfdata['avatar']['href'];
	
	//Handle faves and their team, if any.
	if (!empty($userdata['fave'])) {
		$trainer['fave'] = $userdata['fave'];
	}
	
	if (!empty($userdata['extrafave'])) {
		$extrafave = explode(',',$userdata['extrafave']);
		foreach ($extrafave as $efave) {
			$trainer['extrafave'][] = $efave;
		}
	}
	
	if (!empty($userdata['trades'])) {
		$trades = explode(',',$userdata['trades']);
	} else $trades = null;
	
	empty($trades) ? $trainer['total_trades'] = 0 : $trainer['total_trades'] = count($trades);
	
	$trainer['total_encounters'] = $userdata['sightings'];
	$trainer['total_catches'] = $userdata['catches'];
	
	$badges = badge_strip($id, $userdata, $output_method = "array", false);
	
	$trainer['badges'] = $badges;
	
	//Now onto pokemon.
	if (!empty($userdata['pokemon'])) {
		$owned_pokemon = explode(',',$userdata['pokemon']);
		sort($owned_pokemon);
	} else $owned_pokemon = null;
	if (!empty($userdata['seen'])) {
		$seen_pokemon = explode(',',$userdata['seen']);
		sort($seen_pokemon);
	} else $seen_pokemon = null;
	if (!empty($userdata['dex'])) {
		$dex_pokemon = explode(',',$userdata['dex']);
		sort($dex_pokemon);
	} else $dex_pokemon = null;
	
	
	empty($owned_pokemon) ? $trainer['total_pokemon'] = 0 : $trainer['total_pokemon'] = count($owned_pokemon);
	empty($seen_pokemon) ? $trainer['total_seen'] = 0 : $trainer['total_seen'] = count($seen_pokemon);
	empty($dex_pokemon) ? $trainer['total_dex'] = 0 : $trainer['total_dex'] = count($dex_pokemon);
	
	if (!empty($owned_pokemon)) {
		$trainer['pokemon']['string'] = $userdata['pokemon'];
		$trainer['pokemon']['raw'] = $owned_pokemon;
		foreach ($owned_pokemon as $opoke) {
			$trainer['pokemon']['named'][$opoke] = $pokemon[round($opoke)].(is_shiny($opoke) ? ' (Shiny)' : '');
		}
	}
	
	if (!empty($seen_pokemon)) {
		$trainer['seen']['string'] = $userdata['seen'];
		$trainer['seen']['raw'] = $seen_pokemon;
		foreach ($seen_pokemon as $opoke) {
			$trainer['seen']['named'][$opoke] = $pokemon[round($opoke)].(is_shiny($opoke) ? ' (Shiny)' : '');
		}
	}
	
	if (!empty($dex_pokemon)) {
		$trainer['dex']['string'] = $userdata['dex'];
		$trainer['dex']['raw'] = $dex_pokemon;
		foreach ($dex_pokemon as $opoke) {
			$trainer['dex']['named'][$opoke] = $pokemon[$opoke];
		}
		$trainer['dex']['percentage'] = round( ((count($dex_pokemon) / count($pokemon)) * 100), 2 );
	}
	
	if (isset($_GET['html'])) echo '<pre>';
	
	if (isset($_GET['raw'])) {
		echo json_encode($trainer);
	} else echo json_encode($trainer, JSON_PRETTY_PRINT);
	
	if (isset($_GET['html'])) echo '</pre>';
	
} elseif (isset($_GET['pokedump'])) {

	$p['types'] = $type;
	$p['versions'] = array(1 => array('name' => 'Tyrian', 'color' => '#66023C'), 2 => array('name' => 'Liseran', 'color' => '#7851A9'));
	
	foreach ($pokemon as $key => $poke) {
		$p['pokemon'][$key]['id'] = $key;
		$p['pokemon'][$key]['name'] = $pokemon[$key];
		$p['pokemon'][$key]['type'] = $p_type[$key];
		$p['pokemon'][$key]['base'] = $p_base[$key];
		$p['pokemon'][$key]['chance'] = $e_chance[$key];
		$p['pokemon'][$key]['version'] = $affinity[$key];
		$p['pokemon'][$key]['evolves_from'] = $evolves_from[$key];
		$p['pokemon'][$key]['evolves_to'] = $evolves_to[$key];
	}
	
	if (isset($_GET['raw'])) {
		echo json_encode($p);
	} else echo json_encode($p, JSON_PRETTY_PRINT);
}

else die($error[1]);
?>