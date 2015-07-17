<?php //Yeah, this should be our last file.

function user_data($id) { //Accepts either a single or array of IDs, builds everything it needs to about all the users, returns the array keyed by ID. Gets EVERYTHING about the user. This defines from now on our user data...data. Badges are array'd only. userdata() will still exist but as a low level function. This will preformat, precalculate everything.
global $file_db, $pcfg, $pokemon;

	//So, single or multiple users?
	if (is_array($id)) {
		$id = array_unique($id);
		$userdata = multiuserdata($id);
	} else {
		$userdata = $file_db->query('SELECT * FROM trainers WHERE id='.(int)$id.'')->fetchAll();
		$userdata[$id] = $userdata[0];
	}
	
	//Add usernames to the mix.
	$smfdata = pokemon_fetchMember($member_ids = $id, $output_method = 'array');
	foreach ($userdata as $key => $value) {
		if (!empty($smfdata[ $value['id'] ]['name'])) $userdata[ $value['id'] ]['name'] = $smfdata[ $value['id'] ]['name'];
		else $userdata[ $value['id'] ]['name'] = 'ERRORNO.';
	}
		
}

?>