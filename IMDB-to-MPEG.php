#!/usr/bin/env php
<?php
/*
Uses IMDB to create a MPEG2 video summary of a film.

Copyright (c) 2009 Flexion.Org, http://flexion.org/

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

vim: sw=4 sts=4 cindent
*/

define("VERSION", "1.2");
define("DEFAULT_OUTPUT_TYPE", "m4v");
define("DEFAULT_RESOLUTION", "848x480");

// change this to "_" if you don't want spaces in your filenames
define("FILENAME_WORD_SPACE_CHAR", " ");

define("TEXT_FONTSIZE", "15");
$supported_output = array(
	'm4v' => 'h264 video, AAC audio',
	'h264' => 'h264 video, AAC audio',
	'mp2'  => 'no clue what this is, this is for you martin',
	'mpeg2' => 'no clue what this is, this is for you martin',
	);

echo("IMDB-to-MPG v" . VERSION . " - Uses IMDB to create a MPEG2 video summary of a film.\n");
echo("Copyright (c) 2009 Flexion.Org, http://flexion.org. GPLv2 License.\n");
echo("\n");

require('imdbphp-1.1.4/imdb.class.php');
require('texttoimage-1.0/TextToImage.class.php');

/*
 * Make sure we can function
 */

// We can't do sym-links and /dev/zero on Windows. 
if (PHP_OS == 'WINNT')
{
    echo("ERROR: Sorry, this script doesn't work on Windows\n");
    exit;
}

// Is this the correct PHP version?
if (substr(PHP_VERSION, 0 ,1) < '5')
{
    echo("ERROR: PHP5 or better is required, you are running " . PHP_VERSION ."\n");
    exit;
}

/*
 * Parse Command Line Options
 */

// The last option needs to be a file name... pop that off first and check it
$options['filename'] = array_pop($argv);
if (! @stat($options['filename'])) {
    echo "error: no such file: $options[filename]\n";
    exit(1);
}

// getopt is somewhat broken in php < 5.3.0, but let's try
$longopts = array(
	'title:',
	'id:',
	'output-type:',
	'resolution:',
	'debug',
	);
if (version_compare(phpversion(), "5.3.0") >= 0) {
    $optlist = getopt("dt:i:o:r:", $longopts);
} else {
    $optlist = getopt("dt:i:o:r:");
}
foreach ($optlist as $optind => $optarg) {
    switch ($optind) {
	case 'debug':
	case 'd':
	    $options['debug'] = TRUE;
	    break;
	case 'title':
	case 't':
	    $options['title'] = $optarg;
	    break;
	case 'id':
	case 'i':
	    $options['id'] = $optarg;
	    break;
	case 'output-type':
	case 'o':
	    $options['output_type'] = $optarg;
	    break;
	case 'resolution':
	case 'r':
	    $options['resolution'] = $optarg;
	    break;
	default:
	    echo "error: unknown option '$optind'\n";
	    exit(1);
	    break;
    }
}

/*
 * Perform some default configuration and create a sane environment
 */

// if the title isn't specified, let's build one from the filename
if (! isset($options['title'])) {
    // strip off any directory and extension (yes, this could all be one
    // line... readability and all that
    $title = basename($options['filename']);
    $title = preg_replace('/^(.*)\..*$/', '$1', $title);
    $title = str_replace("_", " ", $title);
    $options['title'] = $title;
}

if (! isset($options['id'])) {
    $options['id'] = null;
} else {
    // Make sure it at least looks like an IMDB ID
    if (preg_match('/^tt(\d{7})$/', $options['id'], $regs)) {
	$options['id'] = $regs[1];
    }
    if (! IMDB_to_MPEG::validId($options['id'])) {
	echo "error: '$options[id]' does not appear to be a valid IMDB ID\n";
	exit(1);
    }
}

if (! isset($options['output_type'])) {
    $options['output_type'] = DEFAULT_OUTPUT_TYPE;
}
// make sure we know what we're supposed to be spitting out
if (! array_key_exists($options['output_type'], $supported_output)) {
    echo "error: '" . $options['output_type'] . "' is not a supported output type.  Supported types are:\n";
    foreach ($supported_output as $format => $description) {
	printf("           %-5.5s    $description\n", $format, $description);
    }
    exit(1);
}

if (! isset($options['resolution'])) {
    $options['resolution'] = DEFAULT_RESOLUTION;
}
// parse the resolution option to width and height
if (! preg_match('/^(\d+)x(\d+)$/', $options['resolution'], $regs)) {
    echo "error: '$options[resolution]' does not appear to be a resultion.  Use format of: 424x240\n";
    exit(1);
}
$options['resolution'] = array('width' => $regs[1], 'height' => $regs[2]);

/*
 * This class will do all of the work, but leave the user interface as procedural 
 */
class IMDB_to_MPEG {
    // string: An IMDB movie ID
    public $id = null;

    // string: A movie title to search for
    public $title = null;

    // string: what are we going to spit out
    public $output_type = null;

    // array: width, height
    public $resolution;

    // array: array of IMDB search result objects
    public $searchResult = array();

    // object: An IMDB search result object that matches a specific ID
    public $imdbMovie = null;

    public $debug = null;

    public $videoInfo = array();

    public function __construct($options)
    {
	$this->id = $options['id'];
	$this->title = $options['title'];
	$this->output_type = $options['output_type'];
	$this->resolution = $options['resolution'];
	$this->filename = $options['filename'];
	$this->debug = $options['debug'];
    }

    public function search()
    {
	$this->imdbMovie = null;
	$search = new imdbsearch();
	$search->setsearchname($this->title);
	$this->searchResults = $search->results();
	foreach ($this->searchResults as $index => $result) {
	    if ($result->imdbID() == $this->id) {
		$this->imdbMovie = $result;
	    }
	}
	if (! $this->imdbMovie) {
	    $this->imdbMovie = $this->searchResults[1];
	}
    }

    public function getMovieById($id)
    {
	$this->id = $id;
	$this->imdbMovie = new imdb($this->id);
    }

    public function setVideoInfo() {
	$this->videoInfo['title']       = str_cleaner($this->imdbMovie->title());
	$this->videoInfo['year']        = str_cleaner($this->imdbMovie->year()) ;
	$this->videoInfo['tagline']     = str_cleaner($this->imdbMovie->tagline());
	$this->videoInfo['plotoutline'] = str_cleaner($this->imdbMovie->plotoutline());
	$this->videoInfo['cast']        = ""; // build this string later
	$this->videoInfo['genrestext']  = ""; // build this string later
	$this->videoInfo['genres']      = $this->imdbMovie->genres();
	$this->videoInfo['ratingtext']  = ""; // build this string later
	$this->videoInfo['rating']      = $this->imdbMovie->rating();
	$this->videoInfo['votes']       = $this->imdbMovie->votes();

	$cleanTitle = str_replace(' ', FILENAME_WORD_SPACE_CHAR, $this->videoInfo['title']);
	$cleanTitle = str_replace('&', 'and', $cleanTitle);
	$cleanTitle = str_replace(array("'"), '', $cleanTitle);
	$this->videoInfo['cleanTitle'] = str_replace(array("·", "/"), '-', $cleanTitle);

	if (!empty($this->videoInfo['genres'])) {
	    $this->videoInfo['genrestext'] = "Genres: ";
	    for ($i = 0; $i + 1 < count($this->videoInfo['genres']); $i++) {
		$this->videoInfo['genrestext'] .= str_cleaner($this->videoInfo['genres'][$i]) . ', ';
	    }
	    $this->videoInfo['genrestext'] .= str_cleaner($this->videoInfo['genres'][$i]) . ".";
	}

	$cast = $this->imdbMovie->cast();
	if (count($cast) > 5) {
	    $cast_count = 5;
	} else {
	    $cast_count = count($cast);
	}
	if (!empty($cast)) {
	    for ($i = 0; $i + 1 < $cast_count; $i++) {
		$this->videoInfo['cast'] .= str_cleaner($cast[$i]["name"]) . ' as ' . str_cleaner($cast[$i]["role"]) . ', ';
	    }
	    $this->videoInfo['cast'] .= 'and ' . str_cleaner($cast[$i]["name"]) . ' as ' . str_cleaner($cast[$i]["role"]) . ".";    
	}

	if (!empty($this->videoInfo['rating']) && !empty($this->videoInfo['votes'])) 
	{ 
	    $this->videoInfo['ratingtext'] .= "Rated " . $this->videoInfo['rating'] . " out of 10 from " . $this->videoInfo['votes'] . " votes."; 
	}
    }

    public function displayMovie($wraplen = 75) {
	$this->setVideoInfo();
	echo $this->getVideoText($wraplen);
    }

    public function getVideoText($wraplen)
    {
	$text .= $this->videoInfo['title'] . " (" . $this->videoInfo['year'] . ")\n";
	for ($ctr = 0; $ctr < $wraplen; $ctr++) {
	    $text .= "-";
	}
	$text .= "\n";
	if (strlen($this->videoInfo['tagline'])) {
	    $text .= wordwrap($this->videoInfo['tagline'], $wraplen) . "\n\n";
	}
	if (strlen($this->videoInfo['plotoutline'])) {
	    $text .= wordwrap($this->videoInfo['plotoutline'], $wraplen) . "\n\n";
	} else {
	    $text .= wordwrap("Not plot information was available for this movie", $wraplen) . "\n\n";
	}
	if (strlen($this->videoInfo['cast'])) {
	    $text .= wordwrap($this->videoInfo['cast'], $wraplen) . "\n\n";
	}
	if (strlen($this->videoInfo['genrestext'])) {
	    $text .= wordwrap($this->videoInfo['genrestext'], $wraplen) . "\n\n";
	}
	if (strlen($this->videoInfo['ratingtext'])) {
	    $text .= wordwrap($this->videoInfo['ratingtext'], $wraplen) . "";
	}
	return($text);
    }

    public function listResults()
    {
	echo "Search Results:\n";
	echo "IMDB ID    Movie Title\n";
	echo "--------------------------------------------------------------------------\n";
	foreach ($this->searchResults as $index => $result) {
	    printf("%-8.8s   %s (%4s)\n", $result->imdbID(), str_cleaner($result->title()), $result->year());
	}
    }

    static public function validId($id)
    {
	if (! preg_match('/^\d{7}$/', $id)) {
	    return false;
	}
	return true;
    }

    public function createImage() {
	$fontSize = 15;
	$fontName = "Vera.ttf";
	$fontPath = dirname(__FILE__) . '/' . $fontName;
	$this->imageFile = $tmpfile = "/tmp/image-to-mpeg-temp-image";

	// what should our wrap length be
	$wrapLength = (int)($fontSize*5*.90);

	// the line spacing appears to be 40% of the line size
	$lineHeight = $fontSize + (int)($fontSize * 0.4);

	// Create a right margin of 10% of the total image size
	$xOffset  = (int)($this->resolution['width'] * 0.10);

	// Calculate the number of lines in the text
	$numLines = count(explode("\n", $this->getVideoText($wrapLength)));

	// Calculate the maximum possible number of lines
	$yMaxLines = (int)($this->resolution['height'] / $lineHeight);

	if ($numLines > $yMaxLines) {
	    // uh oh.... maybe dynamically change the font size?  I dunno.
	}

	// How many pixels high is our block of text
	$totalTextHeight = $numLines * $lineHeight;

	// Now center it vertically
	$yOffset = ($this->resolution['height'] / 2) - ($totalTextHeight / 2) + $fontSize;

	$_im = new TextToImage();
	$_im->makeImageF($this->getVideoText($wrapLength), $fontPath, $this->resolution['width'], $this->resolution['height'], $xOffset, $yOffset, $fontSize, array(0xFF, 0xFF, 0xFF), array(0x0, 0x0, 0x0));
	$_im->saveAsJpg($tmpfile . "_000");
    }

    public function encodeVideoM4V() {
	$frameRate = 24;
	$movieLength = 20;

	$this->infoMovieFile = $outputFileName = "About" . FILENAME_WORD_SPACE_CHAR . $this->videoInfo['cleanTitle'] . '.m4v';

	$cmd = 'jpeg2yuv -v 0 -j "' . $this->imageFile . '_%03d.jpg" -l ' . ($frameRate * $movieLength) . ' -f ' . $frameRate . ' -I p | ffmpeg -threads 2 -y -i - -vcodec libx264 -b 50k -acodec libfaac -ab 48k -ar 48000 -ac 2 -s ' . $this->resolution['width'] . 'x' . $this->resolution['height'] . ' -f mp4 "All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '"'; 
	if ($this->debug) {
	    echo "Running: $cmd" . "\n";
	    `$cmd`;
	} else {
	    echo "Encoding Info Movie File...";
	    `$cmd > /dev/null 2>&1`;
	    echo " done\n";
	}
    }

    public function encodeVideo() {
	$this->createImage();
	switch ($this->output_type) {
	    case 'm4v':
	    case 'mp4':
	    case 'ps3':
		$this->encodeVideoM4V();
		break;
	}
    }

    public function createSymLinkTree() {
	if (!empty($this->videoInfo['genres']))
	{
	    @mkdir('All/' . $this->videoInfo['cleanTitle'], 0777, true);
	    @touch('All/' . $this->videoInfo['cleanTitle'] . "/.imdbid_" . $this->imdbMovie->imdbID(), 0777, true);
	    for ($i = 0; $i < count($this->videoInfo['genres']); $i++)
	    {
		echo "Adding this movie into Genres/" . str_cleaner($this->videoInfo['genres'][$i]) . "...\n";
		@mkdir('Genres/' . str_cleaner($this->videoInfo['genres'][$i]), 0777, true);
		@symlink('../../All/' . $this->videoInfo['cleanTitle'], 'Genres/' . str_cleaner($this->videoInfo['genres'][$i]) . '/' . $this->videoInfo['cleanTitle']);
	    }
	}
	if (!empty($this->videoInfo['rating'])) {
	    echo "Adding this movie into Ratings/" . (int)$this->videoInfo['rating'] . "...\n";
	    @mkdir('Ratings/' . (int)$this->videoInfo['rating'], 0777, true);
	    @symlink('../../All/' . $this->videoInfo['cleanTitle'], 'Ratings/' . (int)$this->videoInfo['rating'] . '/' . $this->videoInfo['cleanTitle']);
	}
    }

}

// Compat Functions
function str_cleaner($string_in, $strip_double_quotes = false)
{   
    $string_tmp = trim($string_in);
    $string_tmp = strip_tags($string_tmp);    
    $string_tmp = html_entity_decode($string_tmp, ENT_COMPAT, 'UTF-8');
    if (strip_double_quotes)
    {
        $string_tmp = str_replace('"', '', $string_tmp);
    }
    return($string_tmp);
}

$i2m = new IMDB_to_MPEG($options);

/*
 * Step 1: Get the user to identify the movie
 */
do {
    echo "Searching IMDB for: " . $i2m->title . "... ";
    $i2m->search();
    if ($i2m->imdbMovie && $i2m->imdbMovie->imdbID == $options['id']) {
	echo "found " . str_cleaner($i2m->imdbMovie->title()) . " (" . str_cleaner($i2m->imdbMovie->year()) . ")\n\n";
	$i2m->displayMovie();
	break;
    } else {
	echo "found " . count($i2m->searchResults) . " possible matches\n";
	echo "Displaying first result:\n\n";
	$i2m->displayMovie();
	echo "\n\n";
	$yn = readline("Use this movie [Y/n]: ");
	if (strtolower($yn) == "y" || $yn == '') {
	    $i2m->imdbMovie = $i2m->searchResults[1];
	    break;
	} else {
	    $i2m->listResults();
	    echo "\n";
	    $id = null;
	    while (! $i2m->validId($id)) {
		$id = readline("Enter an IMDB ID for this movie: ");
	    }
	    $i2m->getMovieById($id);
	    echo "\n";
	    $i2m->displayMovie();
	    echo "\n\n";
	    $yn = readline("Use this movie [Y/n]: ");
	    if (strtolower($yn) == "y" || $yn == '') {
		$i2m->imdbMovie = $i2m->searchResults[1];
		break;
	    }
	    $i2m->id = null;
	}
    }
} while (true);

/*
 * Step 2: Create our symlinks
 */
$i2m->createSymLinkTree();

/*
 * Step 3: Make a our video
 */
$i2m->encodeVideo();

/*
 * Step 4: Move our video file?
 */
echo "--------------------------------------------------------------------------\n";
echo "Would you like to move the actual movie into the directory as well?\n\n";
echo "Source File: " . $i2m->filename . "\n";
echo "Destination: " . 'All/' . $i2m->videoInfo['cleanTitle'] . "/\n\n";

$yn = readline("Move this file? [Y/n]: ");
if (strtolower($yn) == "y" || $yn == "") {
    rename($i2m->filename, 'All/' . $i2m->videoInfo['cleanTitle'] . "/" . basename($i2m->filename));
}

exit(0);
