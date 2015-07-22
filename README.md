![](https://github.com/Roph/RMRKMon/blob/master/images/logo.png?raw=true)

RMRKMon is a minigame designed to run in conjunction with an SMF 2.0.x forum. When setup, users will encounter pokemon as they browse threads and may try catching them. They get a pokemon trainer profile, can collect and can trade their pokemon with other users. They can earn badges and complete their pokedex.

## Requirements ##

First of course, an SMF 2.0.x forum. You will need to be comfortable editing SMF's template file(s) and PHP files in general to setup and configure RMRKMon. Once this is done, menial tasks (gifting pokemon, assigning Pokemon admins) can be done from within RMRKMon or your SMF installation.

**Web server requirements:**

- PHP 5.3+ with PDO-SQLite3, JSON and GD support.
- PHP Sessions must be configured properly! Some shared webhosts break this! If every encounter pokemon always runs away or you see *Unknown: Skipping numeric key* errors under encounters, this is the culprit. The session save path should both exist and be writable by the web server.

# Installation #
## RMRKMon ##
Firstly, this repository **does not include** the pokemon images. Both for copyright concerns, but also for filesize. RMRKMon relies on an enormous collection of over 12,000 pokemon images in various types, laid out in a specific folder structure. [You can grab these images here.](https://mega.nz/#!m1p21Q6K!zcFqbHiUcpOtvgoRYjIMR48aSqea8YWsKKskDHlQNto) Extract the archive so that the "img" folder is alongside your RMRKMon root files.

----------
1. RMRKMon must be in a subfolder off your SMF installation folder named "pokemon".

2. First, you MUST open and edit **settings.php** and set at least the URL info to match where SMF/RMRKMon are or will be. If you decide to use an alternate database name, rename the included blank "pokemans.sqlite3" database accordingly.

3. Once everything is uploaded, make sure the **pokemans.sqlite3** file is writable (Typically CHMOD 775 in your FTP client).

## Enabling encounters on your forum ##

RMRKmon utilizes jQuery to display AJAX-based encounters on your forum. Ideally, you will have these encounters appear in topics alongside posts, though you could put them anywhere.

You must edit your theme's **index.template.php** and insert an extra script tag into the HTML head secion. This is found at the start of the `template_html_above()` function:

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" type="text/javascript"></script>


----------

Next, assuming you'd like pokemon to appear with posts under user information, open up **Display.template.php** and find the comment "// Done with the information about the poster... on to the post itself." Before this, insert:

    //RMRKMon encounters
		$pokemonchance = 2;
		if ($context['current_board'] == 270) $pokemonchance = ($pokemonchance + 3);
		
		if ($context['user']['is_logged'] && (mt_rand(0, 100) < $pokemonchance) && !$seen_pokemon) { //We're not showing multiple per page.
			
			$pokemonkey = md5(date('jg').$context['user']['id']);
			
			echo '<div id="pokemon" style="text-align:center;"></div>
			<script type="text/javascript">
				$( "#pokemon" ).load( "pokemon/?ajax=random_encounter&key='.$pokemonkey.'&sesc=',$context['session_id'],'" );
			</script>';
			
			$seen_pokemon = true;
		}

This code should be relatively self explanatory. We enable higher chance encounters if the current board is 270 (or any board of your choice). Typically, if you have a subforum just for RMRKMon, you may want to put its ID in here. Otherwise just put a nonsensical ID.


If you are using the Default SMF 2.0 theme (Curve) unmodified, find the files already modified in the **default theme** folder.


----------

Finally, you may wish to include a link to RMRKMon somewhere in your theme so users may easily visit it. While in Display.template.php, you might use `$message['member']['id']` in the message loop to put a link to each poster's trainer page. 