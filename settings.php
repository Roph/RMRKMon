<?php
//SMF Membergroup IDs allowed to administrate. 1 is the default red Administrator group.
$pcfg['admin_groups'] = array(1, 57); 
//An extra password, used for potentially catastrophic actions such as wiping the database.
$admin_password = 'pokebutts';

//Please keep the database in the same folder. Nothing secret in it anyway.
$pcfg['sqlite_db'] = 'pokemans.sqlite3'; 

//URL Info. Since we're rebels, keep the trailing slashes on dirs/
$baseurl = 'http://127.0.0.1/jessi/pokemon/';
$smf_scripturl = 'http://127.0.0.1/jessi/index.php';
$smf_baseurl = 'http://127.0.0.1/jessi/';

//At the moment, only controls error reporting. You may wish to switch to false in production.
$pcfg['devmode'] = true; 

//This should be the final ID of whatever generation of pokemon you want currently enabled. So for example, if you only wish your users to catch Generation 1&2 pokemon, you'd put 251 here.
$pcfg['pokemon_highest_enabled'] = 649;



//Encountering / Capture settings.

//The time in seconds since last capturing that a user cannot encounter any more pokemon. Default is 1800, or 30 mins.
$pcfg['encounter_cooldown_block'] = 1800; 

//Time in seconds since last capture that encounters have a 50% chance to cancel. Should be higher than block.
$pcfg['encounter_cooldown_half'] = 3600; 

//A day of the week, if any, where pokemon have extra chance to be shiny. Must be a Capitalised 3 letter style day. Mon, Tue etc. Change to anything else to disable this feature.
$pcfg['shiny_day'] = "Sun";

//The default success rate on capturing pokemon, assuming no cooldown is in place.
$pcfg['capture_chance_default'] = 95;

//The following numbers should increase as you get closer to the cooldown end, which is 8 hours. Each represents the chance in that particular timeframe.
$pcfg['capture_chance_1min'] = 1;
$pcfg['capture_chance_2mins'] = 5;
$pcfg['capture_chance_15mins'] = 15;
$pcfg['capture_chance_30mins'] = 33;
$pcfg['capture_chance_2hours'] = 45;
$pcfg['capture_chance_4hours'] = 65;
$pcfg['capture_chance_6hours'] = 75;
$pcfg['capture_chance_8hours'] = 85;

//A special capture chance in the case of shiny encounters. Don't be too mean.
$pcfg['capture_chance_shiny'] = 95;



//Trading and version settings

//The maximum number of pokemon a user can offer up in a trade
$pcfg['trade_multi_limit'] = 10;

//The board (if any) on the forum dedicated to trading
$pcfg['trade_forum'] = 271;

//The maximum concurrent trades a user may have going on at once
$pcfg['trade_simultaneous_limit'] = 5;

//Versions are analagous to Red/Blue, Gold/Silver and so on. The pokemon are split the same way. A user who can encounter a bellsprout will never encounter an Oddish.
$pcfg['version1'] = 'Tyrian';
$pcfg['version2'] = 'Liseran';
$pcfg['color1'] = '#66023C';
$pcfg['color2'] = '#7851A9';


//Yeah.. just ignore these.
$warning_symbol = '<span style="color:yellow;border-bottom:2px solid yellow;letter-spacing:-5px;">/</span><span style="border-bottom: 2px solid yellow;letter-spacing:-6px;">!</span><span style="color:yellow;border-bottom:2px solid yellow;">\</span>';
$info_symbol = '<span style="color:#4DB7D4;letter-spacing:-1px;">(</span><span style="border-bottom: 2px solid #4DB7D4;border-top: 2px solid #4DB7D4;letter-spacing:-1px;">?</span><span style="color:#4DB7D4;">)</span>';

?>