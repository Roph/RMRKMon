<?php
//Keeping this stuff seperate, and we're using SQLite for speed and laziness.
$file_db = new PDO('sqlite:'.$pcfg['sqlite_db'].'');

if ($pcfg['devmode']) {
	$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}
else {
	$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	error_reporting(0);
	ini_set('display_errors', 0);
}
$file_db->setAttribute(PDO::ATTR_TIMEOUT, 900);


function userdata($id) { //Grab all the info about a specific user's account 
	global $file_db;
	
	$userdata = $file_db->query('SELECT * FROM trainers WHERE id='.(int)$id.'')->fetchAll(PDO::FETCH_ASSOC);
	
	if (empty($userdata)) return NULL;
	else return $userdata; //newtrainer() could be called in this case!
}

function multiuserdata($ids) { //Grab all the info about a bunch of users' accounts.
	global $file_db;
	
	if (!is_array($ids)) { //Dummy.
		$userdata = $file_db->query('SELECT * FROM trainers WHERE id='.(int)$id.'')->fetchAll();
		return $userdata;
	}
	
	$impuserdatas = implode(' OR id=', $ids);
	$userdatasquery = 'SELECT * FROM trainers WHERE id='.$impuserdatas.' ORDER BY id asc';
	
	$userdatas = $file_db->query($userdatasquery)->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
	$userdatas = array_map('reset', $userdatas);
	
	return $userdatas;
	
}


function pokemondata($id) { //Grab all the info about a specific pokemon.
	global $file_db;
	
	$pokemondata = $file_db->query('SELECT * FROM pokemon WHERE id='.(int)$id.'')->fetchAll();
	
	if (empty($pokemondata)) return NULL;
	else return $pokemondata;
}


function newtrainer($id) { //When a user encounters their first pokemon, we need to create their row in the database. 
	global $file_db;
	
	$file_db->exec("INSERT INTO trainers (
		id,
		trainerpic,
		pokemon,
		seen,
		dex,
		starttime,
		lastcaught,
		trades,
		catches,
		sightings)
	VALUES (
		'".$id."',
		'1',
		'',
		'',
		'',
		'".time()."',
		'0',
		'',
		'0',
		'0');");
		
	//Increase our total trainers count.
	$file_db ->exec('UPDATE stats
							SET total_trainers = total_trainers + 1');
		
	
}

function newtrade($trainer1, $trainer2) { //Simple enough.
	global $file_db;
	
	$file_db->exec("INSERT INTO trades (
		trainer1,
		trainer2,
		stage,
		date)
	VALUES (
		'".$trainer1."',
		'".$trainer2."',
		'1',
		'".time()."');");
		
	//Pass back the rowid of this new trade so we can track it for the user.
	return $file_db->lastInsertId();
		
	
}

function see_pokemon($id, $pokemon) { //Here we'll add to a user's "seen" array. This is only ever additive, though we don't want duplicates. 
	global $file_db;
	
	//First we must read our user's info
	$userdata = $file_db->query('SELECT * FROM trainers WHERE id='.(int)$id.'')->fetchAll();
	
	$file_db->beginTransaction();
	
	//If this is your first sighting..
	if (empty($userdata[0]['seen'])) {
		$file_db->exec('UPDATE trainers
						SET seen="'.$pokemon.'"
						WHERE id="'.$id.'"');
		
	} else { //We need to figure out if you've seen this one before. Shinies get their own entry.
		$seenpokemon = explode(',',$userdata[0]['seen']);
		if (!in_array($pokemon, $seenpokemon)) {
		
			if (!empty($userdata[0]['seen'])) $new_seen = $userdata[0]['seen'].','.$pokemon; //Trainer profiles created elsewhere need this check.
			else $new_seen = $pokemon;
			
			$file_db->exec('UPDATE trainers
							SET seen="'.$new_seen.'"
							WHERE id="'.$id.'"');
		}
	}
	
	//We should also update our trainer's total seen pokemon statistic.
	$file_db ->exec('UPDATE trainers
							SET sightings = sightings + 1
							WHERE id="'.$id.'"');
	
	//Either way, increase our total seen pokemon count.
	$file_db ->exec('UPDATE stats
							SET total_encounters = total_encounters + 1');
							
	//Also increase this particular pokemon's encounter count. If this was a shiny encounter, we keep a seperate tally.
	if (is_shiny($pokemon)) {
	$file_db ->exec('UPDATE pokemon
							SET shiny_encounters = shiny_encounters + 1
							WHERE id ="'.round($pokemon).'"');
	} else {
	$file_db ->exec('UPDATE pokemon
							SET encounters = encounters + 1
							WHERE id ="'.round($pokemon).'"');
	}
	
	$file_db->commit();
	
}

function dex_pokemon($id, $pokemon) { //Very similar to seen pokemon, but the user must have owned the pokemon. Shiny status is cleared before this is called.
	global $file_db;
	
	$userdata = $file_db->query('SELECT * FROM trainers WHERE id='.(int)$id.'')->fetchAll();
	
	//If your pokedex is empty, just add it in.
	if (empty($userdata[0]['dex'])) {
		$file_db->exec('UPDATE trainers
						SET dex="'.$pokemon.'"
						WHERE id="'.$id.'"');
		
	} else { //Disallow duplicate pokedex entries
		$dexpokemon = explode(',',$userdata[0]['dex']);
		if (!in_array($pokemon, $dexpokemon)) {
		
			$new_dex = $userdata[0]['dex'].','.$pokemon;
			
			$file_db->exec('UPDATE trainers
							SET dex="'.$new_dex.'"
							WHERE id="'.$id.'"');
		}
	}
}

function capture_pokemon($id, $pokemon, $source = "organic") { //Add a pokemon to the user's current pokemon inventory. Duplicates are allowed. Also, if neccessary, add that user to the pokemon's "trainer inventory". Duplicates are not allowed.
	global $file_db;
	
	//First we must read our user's info
	$userdata = $file_db->query('SELECT * FROM trainers WHERE id='.(int)$id.'')->fetchAll();
	
	//We must also query the particular pokemon's data first to parse its current (if any) owner arrays.
	$pokemondata = $file_db->query('SELECT owners, shiny_owners FROM pokemon WHERE id='.round($pokemon).'')->fetchAll();
	
	$file_db->beginTransaction();
	
	//We still need to know if this is their first pokemon due to our comma formatting.
	if ($source == "organic") {
		if (empty($userdata[0]['pokemon'])) {
			$file_db->exec('UPDATE trainers
							SET pokemon="'.$pokemon.'",
								lastcaught="'.time().'"
							WHERE id="'.$id.'"');
			
		} else { //Just add on the end.
			$file_db->exec('UPDATE trainers
							SET pokemon="'.$userdata[0]['pokemon'].','.$pokemon.'",
								lastcaught="'.time().'"
							WHERE id="'.$id.'"');
		}
	} else {
		if (empty($userdata[0]['pokemon'])) {
			$file_db->exec('UPDATE trainers
							SET pokemon="'.$pokemon.'"
							WHERE id="'.$id.'"');
			
		} else { //Just add on the end.
			$file_db->exec('UPDATE trainers
							SET pokemon="'.$userdata[0]['pokemon'].','.$pokemon.'"
							WHERE id="'.$id.'"');
		}
	}
	
	//Increase our total captured pokemon count.
	if ($source == "organic") $file_db ->exec('UPDATE stats
							SET total_captures = total_captures + 1');
							
	//We should also update our trainer's total catches statistic.
	if ($source == "organic") $file_db ->exec('UPDATE trainers
							SET catches = catches + 1
							WHERE id="'.$id.'"');
							
	//Also increase this particular pokemon's capture count. We distinguish between shiny and normal captures.
	if (is_shiny($pokemon)) { 
		if ($source == "organic") $file_db ->exec('UPDATE pokemon
							SET shiny_captures = shiny_captures + 1
							WHERE id ="'.round($pokemon).'"');
	} else {
		if ($source == "organic") $file_db ->exec('UPDATE pokemon
							SET captures = captures + 1
							WHERE id ="'.round($pokemon).'"');
	}
							
	
	//If this is a normal pokemon...
	if (!is_shiny($pokemon)) {
		if (empty($pokemondata[0]['owners'])) {
			$file_db->exec('UPDATE pokemon
							SET owners="'.$id.'"
							WHERE id="'.round($pokemon).'"');
			
		} else { //Disallow duplicate owner entries
			$pokemon_owners = explode(',',$pokemondata[0]['owners']);
			if (!in_array($id, $pokemon_owners)) {
			
				$new_owners = $pokemondata[0]['owners'].','.$id;
				
				$file_db->exec('UPDATE pokemon
								SET owners="'.$new_owners.'"
								WHERE id="'.round($pokemon).'"');
			}
		}
		
	} else { //This is a shiny pokemon! Add our lucky owner.
	
			if (empty($pokemondata[0]['shiny_owners'])) {
			$file_db->exec('UPDATE pokemon
							SET shiny_owners="'.$id.'"
							WHERE id="'.round($pokemon).'"');
			
		} else { //Disallow duplicate owner entries
			$pokemon_owners = explode(',',$pokemondata[0]['shiny_owners']);
			if (!in_array($id, $pokemon_owners)) {
			
				$new_owners = $pokemondata[0]['shiny_owners'].','.$id;
				
				$file_db->exec('UPDATE pokemon
								SET shiny_owners="'.$new_owners.'"
								WHERE id="'.round($pokemon).'"');
			}
		}
	}
	
	$file_db->commit();
	
}

function update_trainer($id, $setting, $value) { //Intended for settings, though is a non-specific function for updating any column of any user with new data.
	global $file_db;
	
	$file_db->exec('UPDATE trainers
						SET '.$setting.'="'.$value.'"
						WHERE id="'.$id.'"');
	
}

function update_trade($id, $setting, $value) { //Blanket trade function.
	global $file_db;
	
	$file_db->exec('UPDATE trades
						SET '.$setting.'="'.$value.'"
						WHERE id="'.$id.'"');
	
}

function admin_log($type, $params, $extra) { //See schema.txt for INT types, $params is always either a single INT or string array (101,244,31 etc). $extra is the same.

	global $file_db, $context;
	
	//Log admin actions. May be useful for reference. Definitely useful for accountability.
	$file_db->exec("INSERT INTO adminlog (
				time, 
				user, 
				type, 
				params,
				extra)
		VALUES (
				".time().", 
				".$context['user']['id'].", 
				".$type.", 
				'".$params."',
				'".$extra."');
				");
	
}

function release_pokemon($id, $release) { //Accepts either a single string ID or a single / multi element array of pokemon to remove. Removes those pokemon from the trainer's inventory and, if neccessary, removes that trainer from (each) pokemon's trainer inventory. If successful, returns a results array of each pokemon deleted and each (if any) pokemon orphaned. If fails, returns a string error message.

	global $pokemon, $file_db;
	
	if (empty($id) || empty($release)) return 'Missing parameters';
	
	//It may be that a single pokemon is still submitted in an array form, for example from the release page. Fix these to be actual strings.
	if (count($release) === 1) {
		$release = $release[0];
	}
	
	//First, get our user's info. From release page to releasing this shouldn't be different, but they may have been trying silly duplication tricks such as a simultaneous trade and release.
	$userdata = $file_db->query('SELECT pokemon FROM trainers WHERE id='.$id.'')->fetchAll();
	
	if (empty($userdata)) return 'No pokemon to deal with';
	
	//What pokemon does this trainer currently own?
	$owned_pokemon = explode(',',$userdata[0]['pokemon']);
	$previous_pokemon = $owned_pokemon; //Store a copy of their current (or now, previous) pokemon for later.
	
	//First, remove the pokemon(s?) from the trainer's inventory. It may be that after our deleting is done the trainer may still own one of the pokemon in question.
	
	//If they're only deleting a single pokemon, we'll go through the user's pokemon and if we come across the one they wish to delete, drop it.
	if (!is_array($release)) {
		
		foreach ($owned_pokemon as $key => $value) { //Even if a user only has one pokemon, it's still represented as a single-entry array.
		
			if ($value == $release) {
				unset($owned_pokemon[$key]);
				$releasedp[] = $release;
				break; //If left, would delete all pokemon with that #. Just one please.
				
			}
			
		}
		
	}
	
	else { //The user wishes to delete multiple pokemon.
	
		foreach ($release as $release_pokemon) {//Largely the same, just go through each item and do the same as above
		
			foreach ($owned_pokemon as $key => $value) {
			
				if ($value == $release_pokemon) {
					unset($owned_pokemon[$key]);
					$releasedp[] = $release_pokemon;
					break;
				}
				
			}
			
		}
		
	}
	
	//Whatever we did, we need to compare their previous and new pokemon collection to find out which pokemon are not owned at all by that trainer any more.
	$orphaned_pokemon = array_diff($previous_pokemon, $owned_pokemon);
	//Deleting multiple of a certain pokemon to exhaustion will cumulatively trigger duplicate orphan entries. We don't want that.
	$orphaned_pokemon = array_unique($orphaned_pokemon);
	
	//These are our strings, ready to put back in the database.
	$previous_pokemon_string = implode(',',$previous_pokemon); //We don't actually need this one, except for debugging!
	$owned_pokemon_string = implode(',',$owned_pokemon);
	
	//We're done with the trainer, now on to the pokemon. That is, if there are any orphaned pokemon.
	if (!empty($orphaned_pokemon)) {
		
		//A shiny pokemon (eg 25.3) is stored under the key 25, so we must round our elements. If they are emptying both all their shiny and non-shiny of a pokemon though, we would end up with duplicates.
		$qorphaned_pokemon = array_map('round', $orphaned_pokemon);
		$qorphaned_pokemon = array_unique($qorphaned_pokemon);
		
		//The following mess queries all our relevant orphaned pokemon data, formatted in a nice array of [#pokemon] => [owners] / [shiny_owners], each containing our user strings.
		$imporphan = implode(' OR id=', $qorphaned_pokemon);
		$orphandataquery = 'SELECT id, owners, shiny_owners FROM pokemon WHERE id='.$imporphan.' ORDER BY ID ASC';
		$orphandata = $file_db->query($orphandataquery)->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
		$orphandata = array_map('reset', $orphandata);
		
	}
	
	//At this point, we've finished all our querying. It's time to start writing. As we're potentially making a lot of write queries, we should include them all in one transaction for speed.
	$file_db->beginTransaction();
	
	$file_db->query("UPDATE trainers SET pokemon='".$owned_pokemon_string."' WHERE id=".$id.""); //Update the trainer's pokemon inventory.
	
	if (!empty($orphaned_pokemon)) {
		
		foreach($orphaned_pokemon as $orphan) { //Loop through our orphaned pokemon and, for each one, remove our trainer from its owner list. Shiny, or not, or both.
			
			if (is_shiny($orphan)) { //shiny_owners pls.
				
				$sotrainers = explode(',', $orphandata[round($orphan)]['shiny_owners']);
				
				foreach ($sotrainers as $key => $value) {
					
					if ($value == $id) {
						unset($sotrainers[$key]);
						break; //We'll only have one trainer entry, but no need to continue looping once we've found them.
					}
			
				}
				
				$query = "UPDATE pokemon SET shiny_owners = '".implode(',',$sotrainers)."' WHERE id=".round($orphan)."";
				
				$file_db->exec($query);
				
			} else { //Normal pokemon.
			
				$ntrainers = explode(',', $orphandata[round($orphan)]['owners']);
				
				foreach ($ntrainers as $key => $value) {
					
					if ($value == $id) {
						unset($ntrainers[$key]);
						break; 
					}
			
				}
				
				$query = "UPDATE pokemon SET owners = '".implode(',',$ntrainers)."' WHERE id=".$orphan."";
				
				$file_db->exec($query);
				
			}
			
			unset($query); //Be on the safe side.
			
		}
		
	}
	
	//Now we're done, commit our writes.
	$file_db->commit();
	
	//Build our status array to return to the requesting page or function.
	if (isset($releasedp)) $status['released'] = $releasedp;
	if (!empty($orphaned_pokemon)) $status['orphaned'] = $orphaned_pokemon;
	
	return $status;
	
}


function usertradetrack($id, $tradeid) { //Takes a new trade ID and stores it in the user's row.
	global $file_db;
	
	$userdata = $file_db->query('SELECT opentrades FROM trainers WHERE id='.(int)$id.'')->fetchAll();
	
	if (empty($userdata[0]['opentrades'])) {
			$file_db->exec('UPDATE trainers
							SET opentrades="'.$tradeid.'"
							WHERE id="'.$id.'"');
			
		} else { //Disallow duplicate owner entries
			
			$new_trades = $userdata[0]['opentrades'].','.$tradeid;
			
			$file_db->exec('UPDATE trainers
							SET opentrades="'.$new_trades.'"
							WHERE id="'.$id.'"');
		}
}

function releasetrade($id, $tradeid, $reason = "cancel") { //Frees a trade slot for a user. Removes that trade from a user's active trades. If the trade is being released due to completion, log the completed trade in the trainer's history.
	global $file_db;
	
	$userdata = $file_db->query('SELECT trades, opentrades FROM trainers WHERE id='.(int)$id.'')->fetchAll();
	
	$file_db->beginTransaction();
	
	$trades = explode(',', $userdata[0]['opentrades']);
	
	foreach ($trades as $key => $value) {
		if ($value == $tradeid) {
			unset($trades[$key]);
			break;
		}
	}
	
	$new_trades = implode(',', $trades);
	
	$file_db->exec('UPDATE trainers
							SET opentrades="'.$new_trades.'"
							WHERE id="'.$id.'"');
							
	if ($reason == "complete") {
		if (empty($userdata[0]['trades'])) {
			$file_db->exec('UPDATE trainers
							SET trades="'.$tradeid.'"
							WHERE id="'.$id.'"');
		} else {
			$new_trade_history = $userdata[0]['trades'].','.$tradeid;
			
			$file_db->exec('UPDATE trainers
							SET trades="'.$new_trade_history.'"
							WHERE id="'.$id.'"');
		}
	}
	
	$file_db->commit();
}

function complete_trade($tradeid) { //Removes trainer1's pokemon and gives them to trainer 2. Then vice versa. Then marks the trade as completed. As a traded pokemon is not "caught", supply a $source other than organic to capture_pokemon().
	global $file_db;
	
	//Bring up this trade's data.
	$tradedata = $file_db->query("SELECT * FROM trades WHERE id=".$tradeid."")->fetchAll();
	
	//Build our arrays ready for the switcheroo.
	$pokemon1 = explode(',',$tradedata[0]['pokemon1']);
	$pokemon2 = explode(',',$tradedata[0]['pokemon2']);
	
	//First up, trainer1. Say goodbye to your pokemon.
	$r1 = release_pokemon($tradedata[0]['trainer1'], $pokemon1);
	//Now give these pokemon to trainer2.
	foreach ($pokemon1 as $p1poke) {
		dex_pokemon($tradedata[0]['trainer2'], round($p1poke));
		capture_pokemon($tradedata[0]['trainer2'], $p1poke, $source = "trade");
	}
	
	//Now it's trainer 2's turn.
	$r2 = release_pokemon($tradedata[0]['trainer2'], $pokemon2);
	//Now give these pokemon to trainer1.
	foreach ($pokemon2 as $p2poke) {
		dex_pokemon($tradedata[0]['trainer1'], round($p2poke));
		capture_pokemon($tradedata[0]['trainer1'], $p2poke, $source = "trade");
	}
	
	//Release the trade slots for both users.
	releasetrade($tradedata[0]['trainer1'], $tradeid, $reason = "complete");
	releasetrade($tradedata[0]['trainer2'], $tradeid, $reason = "complete");
	
	//Add this successful trade to the log.
	$file_db->exec("INSERT INTO tradelog (
				trainer1, 
				trainer2, 
				pokemon1, 
				pokemon2,
				date)
		VALUES (
				".$tradedata[0]['trainer1'].", 
				".$tradedata[0]['trainer2'].",
				'".$tradedata[0]['pokemon1']."',
				'".$tradedata[0]['pokemon2']."',
				".time().")
				");
				
	//Increase our total trades stat.
	$file_db ->exec('UPDATE stats
							SET total_trades = total_trades + 1');
	//Finally, this trade is now complete!
	$file_db->exec("UPDATE trades
							SET stage=99
							WHERE id=".$tradeid."");
}

?>