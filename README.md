![](https://github.com/Roph/RMRKMon/blob/master/images/logo.png?raw=true)

RMRKMon is a minigame designed to run in conjunction with an SMF 2.0.x forum. When setup, users will encounter pokemon as they browse threads and may try catching them. They get a pokemon trainer profile, can collect and can trade their pokemon with other users. They can earn badges and complete their pokedex.

## Requirements ##

First of course, an SMF 2.0.x forum. You will need to be comfortable editing SMF's template file(s) and PHP files in general to setup and configure RMRKMon. Once this is done, menial tasks (gifting pokemon, assigning Pokemon admins) can be done from within RMRKMon or your SMF installation.

**Web server requirements:**

- PHP 5.3+ with PDO-SQLite3, JSON and GD support.
- PHP Sessions must be configured properly! Some shared webhosts break this! If every encounter pokemon always runs away, this is the culprit. The session save path should both exist and be writable by the web server.

## Installation ##
Firstly, this repository **does not include** the pokemon images. Both for copyright concerns, but also for filesize. RMRKMon relies on an enormous collection of over 12,000 pokemon images in various types, laid out in a specific folder structure. [You can grab these images here.](https://mega.nz/#!m1p21Q6K!zcFqbHiUcpOtvgoRYjIMR48aSqea8YWsKKskDHlQNto) Extract the archive so that the "img" folder is alongside your RMRKMon root files.

----------
1. RMRKMon must be in a subfolder off your SMF installation folder named "pokemon".

2. First, you MUST open and edit **settings.php** and set at least the URL info to match where SMF/RMRKMon are or will be. If you decide to use an alternate database name, rename the included blank "pokemans.sqlite3" database accordingly.

3. Once everything is uploaded, make sure the **pokemans.sqlite3** file is writable (Typically CHMOD 775 in your FTP client).

(Readme unfinished!)
