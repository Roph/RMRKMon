<?php //Though SMF's SSI is great for our needs, sometimes it's almost TOO good. Fetchmembers queries and spits out a TON of data, 99% of which we don't need. This is bad for our poor database. Hence, this helper file which simply duplicates some SMF functions but only gets what we want.

function pokemon_fetchMember($member_ids = array(), $output_method = 'array')
{
	if (empty($member_ids))
		return;

	// Can have more than one member if you really want...
	$member_ids = is_array($member_ids) ? $member_ids : array($member_ids);

	// Restrict it right!
	$query_where = '
		id_member IN ({array_int:member_list})';

	$query_where_params = array(
		'member_list' => $member_ids,
	);

	// Then make the query and dump the data.
	return pokemon_queryMembers($query_where, $query_where_params, '', 'id_member', $output_method);
}

function pokemon_queryMembers($query_where = null, $query_where_params = array(), $query_limit = '', $query_order = 'id_member DESC', $output_method = 'array')
{
	global $context, $settings, $scripturl, $txt, $db_prefix, $user_info;
	global $modSettings, $smcFunc, $memberContext;

	if ($query_where === null)
		return;

	// Fetch the members in question.
	$request = $smcFunc['db_query']('', '
		SELECT id_member
		FROM {db_prefix}members
		WHERE ' . $query_where . '
		ORDER BY ' . $query_order . '
		' . ($query_limit == '' ? '' : 'LIMIT ' . $query_limit),
		array_merge($query_where_params, array(
		))
	);
	$members = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$members[] = $row['id_member'];
	$smcFunc['db_free_result']($request);

	if (empty($members))
		return array();

	// Load the members.
	pokemon_loadMemberData($members);


	$query_members = array();
	foreach ($members as $member)
	{
		// Load their context data.
		if (!pokemon_loadMemberContext($member))
			continue;

		// Store this member's information.
		$query_members[$member] = $memberContext[$member];
	}
	// Send back the data.
	return $query_members;
}

function pokemon_loadMemberData($users, $is_name = false, $set = 'minimal')
{
	global $user_profile, $modSettings, $board_info, $smcFunc;

	// Can't just look for no users :P.
	if (empty($users))
		return false;

	// Make sure it's an array.
	$users = !is_array($users) ? array($users) : array_unique($users);
	$loaded_ids = array();

	if (!$is_name && !empty($modSettings['cache_enable']) && $modSettings['cache_enable'] >= 3)
	{
		$users = array_values($users);
		for ($i = 0, $n = count($users); $i < $n; $i++)
		{
			$data = cache_get_data('member_data-' . $set . '-' . $users[$i], 240);
			if ($data == null)
				continue;

			$loaded_ids[] = $data['id_member'];
			$user_profile[$data['id_member']] = $data;
			unset($users[$i]);
		}
	}

	if ($set == 'normal')
	{
		$select_columns = '
			IFNULL(lo.log_time, 0) AS is_online, IFNULL(a.id_attach, 0) AS id_attach, a.filename, a.attachment_type,
			mem.signature, mem.personal_text, mem.location, mem.gender, mem.avatar, mem.id_member, mem.member_name,
			mem.real_name, mem.email_address, mem.hide_email, mem.date_registered, mem.website_title, mem.website_url,
			mem.birthdate, mem.member_ip, mem.member_ip2, mem.icq, mem.aim, mem.yim, mem.msn, mem.posts, mem.last_login,
			mem.karma_good, mem.id_post_group, mem.karma_bad, mem.lngfile, mem.id_group, mem.time_offset, mem.show_online,
			mem.buddy_list, mg.online_color AS member_group_color, IFNULL(mg.group_name, {string:blank_string}) AS member_group,
			pg.online_color AS post_group_color, IFNULL(pg.group_name, {string:blank_string}) AS post_group, mem.is_activated, mem.warning,
			CASE WHEN mem.id_group = 0 OR mg.stars = {string:blank_string} THEN pg.stars ELSE mg.stars END AS stars' . (!empty($modSettings['titlesEnable']) ? ',
			mem.usertitle' : '');
		$select_tables = '
			LEFT JOIN {db_prefix}log_online AS lo ON (lo.id_member = mem.id_member)
			LEFT JOIN {db_prefix}attachments AS a ON (a.id_member = mem.id_member)
			LEFT JOIN {db_prefix}membergroups AS pg ON (pg.id_group = mem.id_post_group)
			LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = mem.id_group)';
	}
	elseif ($set == 'profile')
	{
		$select_columns = '
			IFNULL(lo.log_time, 0) AS is_online, IFNULL(a.id_attach, 0) AS id_attach, a.filename, a.attachment_type,
			mem.signature, mem.personal_text, mem.location, mem.gender, mem.avatar, mem.id_member, mem.member_name,
			mem.real_name, mem.email_address, mem.hide_email, mem.date_registered, mem.website_title, mem.website_url,
			mem.openid_uri, mem.birthdate, mem.icq, mem.aim, mem.yim, mem.msn, mem.posts, mem.last_login, mem.karma_good,
			mem.karma_bad, mem.member_ip, mem.member_ip2, mem.lngfile, mem.id_group, mem.id_theme, mem.buddy_list,
			mem.pm_ignore_list, mem.pm_email_notify, mem.pm_receive_from, mem.time_offset' . (!empty($modSettings['titlesEnable']) ? ', mem.usertitle' : '') . ',
			mem.time_format, mem.secret_question, mem.is_activated, mem.additional_groups, mem.smiley_set, mem.show_online,
			mem.total_time_logged_in, mem.id_post_group, mem.notify_announcements, mem.notify_regularity, mem.notify_send_body,
			mem.notify_types, lo.url, mg.online_color AS member_group_color, IFNULL(mg.group_name, {string:blank_string}) AS member_group,
			pg.online_color AS post_group_color, IFNULL(pg.group_name, {string:blank_string}) AS post_group, mem.ignore_boards, mem.warning,
			CASE WHEN mem.id_group = 0 OR mg.stars = {string:blank_string} THEN pg.stars ELSE mg.stars END AS stars, mem.password_salt, mem.pm_prefs';
		$select_tables = '
			LEFT JOIN {db_prefix}log_online AS lo ON (lo.id_member = mem.id_member)
			LEFT JOIN {db_prefix}attachments AS a ON (a.id_member = mem.id_member)
			LEFT JOIN {db_prefix}membergroups AS pg ON (pg.id_group = mem.id_post_group)
			LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = mem.id_group)';
	}
	elseif ($set == 'minimal')
	{
		$select_columns = '
			mem.id_member, mem.member_name, mem.real_name, mem.buddy_list';
		$select_tables = '';
	}
	else
		trigger_error('loadMemberData(): Invalid member data set \'' . $set . '\'', E_USER_WARNING);

	if (!empty($users))
	{
		// Load the member's data.
		$request = $smcFunc['db_query']('', '
			SELECT' . $select_columns . '
			FROM {db_prefix}members AS mem' . $select_tables . '
			WHERE mem.' . ($is_name ? 'member_name' : 'id_member') . (count($users) == 1 ? ' = {' . ($is_name ? 'string' : 'int') . ':users}' : ' IN ({' . ($is_name ? 'array_string' : 'array_int') . ':users})'),
			array(
				'blank_string' => '',
				'users' => count($users) == 1 ? current($users) : $users,
			)
		);
		$new_loaded_ids = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$new_loaded_ids[] = $row['id_member'];
			$loaded_ids[] = $row['id_member'];
			$row['options'] = array();
			$user_profile[$row['id_member']] = $row;
		}
		$smcFunc['db_free_result']($request);
	}

	if (!empty($new_loaded_ids) && $set !== 'minimal')
	{
		$request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}themes
			WHERE id_member' . (count($new_loaded_ids) == 1 ? ' = {int:loaded_ids}' : ' IN ({array_int:loaded_ids})'),
			array(
				'loaded_ids' => count($new_loaded_ids) == 1 ? $new_loaded_ids[0] : $new_loaded_ids,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$user_profile[$row['id_member']]['options'][$row['variable']] = $row['value'];
		$smcFunc['db_free_result']($request);
	}

	if (!empty($new_loaded_ids) && !empty($modSettings['cache_enable']) && $modSettings['cache_enable'] >= 3)
	{
		for ($i = 0, $n = count($new_loaded_ids); $i < $n; $i++)
			cache_put_data('member_data-' . $set . '-' . $new_loaded_ids[$i], $user_profile[$new_loaded_ids[$i]], 240);
	}

	return empty($loaded_ids) ? false : $loaded_ids;
}

function pokemon_loadMemberContext($user, $display_custom_fields = false)
{
	global $memberContext, $user_profile, $txt, $scripturl, $user_info;
	global $context, $modSettings, $board_info, $settings;
	global $smcFunc;
	static $dataLoaded = array();

	// If this person's data is already loaded, skip it.
	if (isset($dataLoaded[$user]))
		return true;

	// We can't load guests or members not loaded by loadMemberData()!
	if ($user == 0)
		return false;
	if (!isset($user_profile[$user]))
	{
		trigger_error('loadMemberContext(): member id ' . $user . ' not previously loaded by loadMemberData()', E_USER_WARNING);
		return false;
	}

	// Well, it's loaded now anyhow.
	$dataLoaded[$user] = true;
	$profile = $user_profile[$user];
	$profile['buddy'] = in_array($profile['id_member'], $user_info['buddies']);
	$buddy_list = !empty($profile['buddy_list']) ? explode(',', $profile['buddy_list']) : array();

	$memberContext[$user] = array(
		'username' => $profile['member_name'],
		'name' => $profile['real_name'],
		'id' => $profile['id_member'],
		'buddies' => $buddy_list,
	);

	return true;
}

?>