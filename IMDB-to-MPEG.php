#!/usr/bin/env php
<?php
/*
Create a MPEG video summarising a movie using data from IMDB

Copyright (c) 2009 Flexion.Org, http://flexion.org/
Copyright (c) 2009 yPass.net, http://ypass.net/

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

define("VERSION", "2.0");
define("DEFAULT_OUTPUT_TYPE", "m4v");
define("DEFAULT_RESOLUTION", "864x480");

// the number of seconds a fade in/out should take
define("FADE_TIME", "0.5");

// the number of seconds to display the movie cover photo
define("COVER_DISPLAY_TIME", "2");
define("TEXT_DISPLAY_TIME", "15");

// change this to "_" if you don't want spaces in your filenames
define("FILENAME_WORD_SPACE_CHAR", " ");

define("TEXT_FONTSIZE", "15");
$supported_output = array(
	'm4v' => 'H.264 video and AAC audio',
	'mp4' => 'H.264 video and AAC audio',
	'm2v' => 'MPEG-2 video and MPEG layer 2 audio',
	'mpg' => 'MPEG-2 video and MPEG layer 2 audio',
	);

echo("IMDB-to-MPG v" . VERSION . " - Create a MPEG video summarising a movie using data from IMDB.\n");
echo("Copyright (c) 2009 Flexion.Org, http://flexion.org. GPLv2 License.\n");
echo("\n");

require(dirname(__FILE__) . '/imdbphp-1.1.4/imdb.class.php');

/*
 *  Give ourselves a debug output function
 */
$debug = false;
function debug($text) {
    if ($GLOBALS['debug']) {
    	echo "DEBUG: $text\n";
    }
}

function usage() {
    echo ("Usage\n");
    echo ("  IMDB-to-MPEG.php -t \"Move Title\" -i 1234567 -o m4v -r 864x480 movie.mkv\n");
    echo ("\n");
    echo ("You can also pass several optional arguments\n");
    echo ("  -a : Automates execution by answering all prompts with the default response.\n");
    echo ("  -t : Provide a film title to search for.\n");
    echo ("  -i : Provide an IMDB ID to search for.\n");
    echo ("  -o : Set the MPEG output format: m4v (default), mp4, m2v or mpg.\n");
    echo ("  -r : Set the MPEG output resolution: 864x480 is the default.\n");        
    echo ("  -d : Enable debug mode.\n");    
    echo ("  -h : This help.\n");
}

/*
 * Make sure we can function
 */

// We can't do sym-links and /dev/zero on Windows. 
if (PHP_OS == 'WINNT')
{
    echo("ERROR: Sorry, this script doesn't work on Windows\\nn");
    exit(1);
}

// Is this the correct PHP version?
if (substr(PHP_VERSION, 0 ,1) < '5')
{
    echo("ERROR: PHP5 or better is required, you are running " . PHP_VERSION ."\n\n");
    exit(1);
}

if ($argc == 1) {
    echo("ERROR: You must supply a filename in the argument list\n\n");
    usage();
    exit(1);
}

/*
 * Parse Command Line Options
 */

// The last option needs to be a file name... pop that off first and check it
$options['filename'] = array_pop($argv);
if (! @stat($options['filename'])) {
    echo "ERROR: No such file: $options[filename]\\n";
    exit(1);
}

// getopt is somewhat broken in php < 5.3.0, but let's try
$longopts = array(
	'title:',
	'id:',
	'output-type:',
	'resolution:',
	'debug',
	'help',
	);
if (version_compare(phpversion(), "5.3.0") >= 0) {
    $optlist = getopt("dt:i:o:r:h:", $longopts);
} else {
    $optlist = getopt("dt:i:o:r:h:");
}
foreach ($optlist as $optind => $optarg) {
    switch ($optind) {
	case 'debug':
	case 'd':
	    $debug = true;
	    debug("WARNING: Debugging enabled.... STAND BACK!\n");
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
	case 'help':
	case 'h':
	    usage();
	    exit(0);	    
	    break;	    
	default:
	    echo "ERROR: Unknown option: '$optind'\n";
	    usage();
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
	echo "ERROR: '$options[id]' does not appear to be a valid IMDB ID\n\n";
	usage();
	exit(1);
    }
}

if (! isset($options['output_type'])) {
    $options['output_type'] = DEFAULT_OUTPUT_TYPE;
}

// make sure we know what we're supposed to be spitting out
if (! array_key_exists($options['output_type'], $supported_output)) {
    echo "ERROR: Unsupported output type : '" . $options['output_type'] . "' Supported types are:\n";
    
    foreach ($supported_output as $format => $description) {
	    printf("           %-5.5s    $description\n", $format, $description);
    }
    echo("\n");
    exit(1);
}

if (! isset($options['resolution'])) {
    $options['resolution'] = DEFAULT_RESOLUTION;
}

// parse the resolution option to width and height
if (! preg_match('/^(\d+)x(\d+)$/', $options['resolution'], $regs)) {
    echo "ERROR: '$options[resolution]' does not appear to be a resultion.  Use format of: 424x240\n\n";
    usage();
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

    public $videoInfo = array();

    public function __construct($options)
    {
	    $this->id = $options['id'];
	    $this->title = $options['title'];
    	$this->output_type = $options['output_type'];
	    $this->resolution = $options['resolution'];
    	$this->filename = $options['filename'];
	    $this->fadetime = FADE_TIME;
    	$this->coverDisplayTime = COVER_DISPLAY_TIME;
	    $this->textDisplayTime = TEXT_DISPLAY_TIME;
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
	
	    if (count($this->searchResults) == 1)  {
	        $this->searchResults[1] = $this->searchResults[0];
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
    	$this->videoInfo['coverPhotoURL'] = $this->imdbMovie->photo(false);

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

    public function getVideoText($wraplen) {
    	$text = $this->videoInfo['title'] . " (" . $this->videoInfo['year'] . ")\n";
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

    public function listResults() {
    	echo "Search Results:\n";
	    echo "IMDB ID    Movie Title\n";
	    echo "--------------------------------------------------------------------------\n";
	    foreach ($this->searchResults as $index => $result) {
	        printf("%-8.8s   %s (%4s)\n", $result->imdbID(), str_cleaner($result->title()), $result->year());
	    }
    }

    static public function validId($id) {
	    if (! preg_match('/^\d{7}$/', $id)) {
	        return false;
	    }
	    return true;
    }

    public function createTextFrame($width, $height, $fontSize = 16) {
	    $fontName = "Vera.ttf";
    	$fontPath = dirname(__FILE__) . '/' . $fontName;
	    $frameWidth = $width;
	    $frameHeight = $height;
    	$wrapLength = 70;
	    $rightMargin = 20;  // the right margin needs to be handled by the caller
	    $vertialMargin = 20;

	    debug("Creating the text frame with font size $fontSize");
	    // how big is our text going to be?
	    $textBoxSize = imageftbbox($fontSize, 0, $fontPath, $this->getVideoText($wrapLength));
	    $textHeight = $textBoxSize[1];
	    $textWidth = $textBoxSize[2];
	    debug("Generated text will be $textWidth x $textHeight");

	    debug("[" . $this->getVideoText($wrapLength) . "]");
	    // decrease the font size until we get something that fits in Y
	    if ($textHeight > $frameHeight - ($verticalMargin*2)) {
	        debug("that's too big... let's try again");
	        return($this->createTextFrame($width, $height, $fontSize-1));
	    }

	    // decrease the font size until we get something that fits in X
	    if ($textWidth > $frameWidth - ($rightMargin*2)) {
	        debug("that's too big... let's try again");
	        return($this->createTextFrame($width, $height, $fontSize-1));
	    }

	    // Now center it vertically
	    $yOffset = ($height / 2) - ($textHeight / 2);
	    $xOffset = ($width/ 2) - ($textWidth / 2);

	    $textFrame = imagecreatetruecolor($width, $height);
	    imagefttext($textFrame, $fontSize, 0, $xOffset, $yOffset-$fontsize, imagecolorallocate($textFrame, 255, 255, 255), $fontPath, $this->getVideoText($wrapLength));
	    return($textFrame);
    }

    public function getCoverPhoto() {
	    if (! ($img = @file_get_contents($this->videoInfo['coverPhotoURL']))) {
	        $this->videoInfo['coverPhoto'] = null;
	        return;
	    }
    	$this->videoInfo['coverPhoto'] = $img;
	    return;
    }

    public function buildVideoFrames($frameRate = 30) {
    	$width = $this->resolution['width'];
	    $height = $this->resolution['height'];
	    $fadetime = $this->fadetime;
	    $coverDisplayTime = $this->coverDisplayTime;
	    $textDisplayTime = $this->textDisplayTime;
	    $fadeframes = $fadetime * $frameRate;
	    
	    // Create temporary files in the temporary files directory using 
	    // sys_get_temp_dir()
        $basedir = sys_get_temp_dir() . '/i2m/';	    

	    echo "Generating video frames...";
	    debug("Fader is set to $fadetime seconds.  FrameRate is $frameRate seconds... fades last $fadeframes frames");

	    // make our temp dir if it doesn't exist
	    @mkdir($basedir);

	    // make sure we can write to it (isn't there an "is writable function or something?)
	    if (! @touch($basedir . "test")) {
	        echo "ERROR: Temp directory is not writable: $basedir\n\n";
	        exit(1);
	    }

	    // delete anything in there
	    if (! $d = opendir($basedir)) {
	        echo "ERROR: Could not open: $basedir\n\n";
	        exit(1);
	    }

	    while ($dirent = readdir($d)) {
	        if ($dirent == "." || $dirent == "..") {
		        continue;
	        }
	        unlink($basedir . $dirent);
	    }
	    closedir($d);
	
	    // set the frame count to 0.  Increment by 1 each time we create a new frame image
	    $frameCount = 0;

	    /*
	    * If we have a cover photo, fade it in, wait a the lenght, fade it out
	    */
	    if ($this->videoInfo['coverPhoto'] != null) {
	        // first few frames should fade in the cover photo

	        // First, make sure the image is true color
	        debug("creating image from imdb");
	        $tmpimg = imagecreatefromstring($this->videoInfo['coverPhoto']);
	        $cX = imagesx($tmpimg);
	        $cY = imagesy($tmpimg);
	        debug("making it true color");
	        $coverPhoto = imagecreatetruecolor($cX, $cY);
	        imagecopy($coverPhoto, $tmpimg, 0, 0, 0, 0, $cX, $cY);
	        debug("destroying the tmp");
	        imagedestroy($tmpimg);

	        // now make sure the size is acceptable
	        // check Y first, because that's more likely to not fit
	        $newX = $newY = 0;
	        if ($cY > $height) {
		        debug("resizing it to fit Y");
		        $ydiff = $height / $cY;
		        $newY = (int)($cY * $ydiff);
        		$newX = (int)($cX * $ydiff);
        		debug("going from $cX x $cY to $newX x $newY");
        		$tmpimg = imagecreatetruecolor($newX, $newY);
        		imagecopyresampled($tmpimg, $coverPhoto, 0, 0, 0, 0, $newX, $newY, $cX, $cY);
        		debug("destroy coverphoto");
        		imagedestroy($coverPhoto);
        		debug("copy coverphoto");
        		$coverPhoto = $tmpimg;
        		debug("what do we have here? " . imagesx($coverPhoto) . "x" . imagesy($coverPhoto) . "");
        		$cX = $newX;
        		$cY = $newY;
	        }

	        // now see if X is still a problem
    	    if ($cX > $width) {
	        	debug("resizing it to fit X");
	        	$xdiff = $width / $cX;
	        	$newY = (int)($cY * $xdiff);
	        	$newX = (int)($cX * $xdiff);
	        	debug("going from $cX x $cY to $newX x $newY");
        		$tmpimg = imagecreatetruecolor($newX, $newY);
        		imagecopyresampled($tmpimg, $coverPhoto, 0, 0, 0, 0, $newX, $newY, $cX, $cY);
		        debug("destroy coverphoto");
        		imagedestroy($coverPhoto);
		        debug("copy coverphoto");
		        $coverPhoto = $tmpimg;
        		debug("what do we have here? " . imagesx($coverPhoto) . "x" . imagesy($coverPhoto) . "");
        		$cX = $newX;
		        $cY = $newY;
    	    }

	        //alrighty, now create a full sized frame with the cover centered
	        debug("creating our frame");
    	    $frame = imagecreatetruecolor($width, $height);
	        $xpos = ($width / 2) - ($cX / 2);
    	    $ypos = ($height / 2) - ($cY / 2);
	        imagecopy($frame, $coverPhoto, $xpos, $ypos, 0, 0, $cX, $cY);

	        // now let's create a lot of frames out of it to fade in the cover
	        $brightChangePerFrame = (float)(255 / $fadeframes);
	        debug("fading in the cover");
	        for ($curlevel = -255, $ctr = 0; $ctr < $fadeframes; $ctr++, $frameCount++) {
	        	$newframe = imagecreatetruecolor($width, $height);
	        	imagecopy($newframe, $frame, 0, 0, 0, 0, $width, $height);
		
		        // GD distributed with Ubuntu is missing some functionality.
		        if (function_exists('imagefilter')) {
    		        imagefilter($newframe, IMG_FILTER_BRIGHTNESS, (int)($curlevel += $brightChangePerFrame));
                }    		

        		$framefilename = sprintf("%s/frame_%04d.jpg", $basedir, $frameCount);
		        imagejpeg($newframe, $framefilename);
	        }

	        // now let it just sit on the screen for the defined time
	        debug("displaying the cover");
	        $coverDisplayFrames = $coverDisplayTime * $frameRate;
	        for ($ctr = 0; $ctr < $coverDisplayFrames; $ctr++, $frameCount++) {
        		$framefilename = sprintf("%s/frame_%04d.jpg", $basedir, $frameCount);
		        imagejpeg($frame, $framefilename);
	        }

	        /*
	        * if you want o fade out the image completely, you could uncomment
	        * this block...  but I wanted to play with GD some more
            */
            
            /*
    	    debug("fading out the cover");
	        for ($curlevel = 0, $ctr = 0; $ctr < $fadeframes; $ctr++, $frameCount++) {
        		$newframe = imagecreatetruecolor($width, $height);
		        imagecopy($newframe, $frame, 0, 0, 0, 0, $width, $height);
		        // GD distributed with Ubuntu is missing some functionality.
		        if (function_exists('imagefilter')) {		        
    		        imagefilter($newframe, IMG_FILTER_BRIGHTNESS, (int)($curlevel -= $brightChangePerFrame));
    		    }
		        $framefilename = sprintf("%s/frame_%04d.jpg", $basedir, $frameCount);
		        imagejpeg($newframe, $framefilename);
		        imagedestroy($newframe);
	        }
	        */

	        // now let's shrink it and move it to the left of the frame until
	        // the width takes up 15% of the frame while maintaining the aspect
	        // ratio
	        $shrinkToWidth = $width * 0.15;
	        $shrinkToHeight = $height * (float)($shrinkToWidth / $width);

	        // where is the upper left endpoint for the image... 2% sounds good..
	        $moveToX = (int)($width * 0.02);
	        $moveToY = (int)($height * 0.10);

	        // now we have $fadeframes seconds to deal with it.. oh boy... i
	        // was told there would be no math
    	    $curX = $xpos;
	        $curY = $ypos;

    	    // figure out how far we have to go in each step to make it there
	        $distanceX = $curX - $moveToX;
	        $distanceY = $curY - $moveToY;
	        debug("$distanceX");
	        $stepX = $distanceX / $fadeframes;
	        $stepY = $distanceY / $fadeframes;

    	    // that handles moving it... now about the resizing... we don't
	        // have to worry about aspect ratio here because it's handled based
	        // on the math
    	    $curW = $cX;
	        $widthDifference = $cX - $shrinkToWidth;
	        $stepWidth = $widthDifference / $fadeframes;
    	    $aspectRatio = $cY / $cX;
	        debug("Aspect ratio is $aspectRatio");

    	    // well let's see what that does!
	        debug("Moving from $curX x $curY to $moveToX x $moveToY in $stepX x $stepY increments over $fadeframes frames");
	        for ($ctr = 0; $ctr < $fadeframes; $ctr++) {
    	    	$newframe = imagecreatetruecolor($width, $height);
	
    	    	// stick a dim version halfway between the steps.. we need to
	        	// create some motion blur so it doesn't look as choppy.  but
	        	// not on the first and last frames....
	    	    /*
	    	    //ok... this didn't work.....
	    	    if ($ctr >= 1 && $ctr != $fadeframes-1) {
	    	        imagecopyresampled($newframe, $coverPhoto, $curX - $stepX/2, $curY - $stepY/2, 0, 0, $curW, $curH, $cX, $cY);
        		    imagefilter($newframe, IMG_FILTER_BRIGHTNESS, -224);
	        	    imagecopyresampled($newframe, $coverPhoto, $curX - $stepX/3, $curY - $stepY/3, 0, 0, $curW, $curH, $cX, $cY);
	        	    imagefilter($newframe, IMG_FILTER_BRIGHTNESS, -192);
	        	    imagecopyresampled($newframe, $coverPhoto, $curX - $stepX/4, $curY - $stepY/4, 0, 0, $curW, $curH, $cX, $cY);
	        	    imagefilter($newframe, IMG_FILTER_BRIGHTNESS, -128);
	        	}
	        	*/
		
    		    $curX -= $stepX;
	    	    $curY -= $stepY;
        		$curW -= $stepWidth;
	        	$curH = ($curW * $aspectRatio);
	        	debug("new size is $curW x $curH");
        		imagecopyresampled($newframe, $coverPhoto, $curX, $curY, 0, 0, $curW, $curH, $cX, $cY);
	        	$framefilename = sprintf("%s/frame_%04d.jpg", $basedir, $frameCount);
	        	$frameCount++;
	        	imagejpeg($newframe, $framefilename);
        		// need to save the last guy as a reference for the next frame
	        	if ($ctr == $fadeframes-1) {
	        	    $frame = $newframe;
	        	} else {
	        	    imagedestroy($newframe);
	        	}
	        }

    	    debug("Arrived at $curX x $curY");
    	    debug("We're doing moving and resizing");
	    }// $frame is set to the last frame in that sequence
	    else
	    {
	        // We didn't find any cover art so just make a frame.
            $frame = imagecreatetruecolor($width, $height);	    	    	    
	    }	    

	    /*
	    * Same for the text... fade it in, wait a the lenght, but DO NOT fade it out
	    */
        // get ourselves the partial frame accounting for the cover image	    	    	    
       	$subframeW = imagesx($frame) * 0.85 - 50;
        $subframeH = imagesy($frame);
	    	   
    	debug("We have $subframeW x $subframeH to do this thing....");
	    $textsubframe = $this->createTextFrame($subframeW, $subframeH);

    	debug("fading in the text");
	    $brightChangePerFrame = (float)(255 / $fadeframes);
    	for ($curlevel = -255, $ctr = 0; $ctr < $fadeframes; $ctr++, $frameCount++) {
	        $newframe = imagecreatetruecolor($width, $height);
	        $newsubframe = imagecreatetruecolor($subframeW, $subframeH);
	        imagecopy($newframe, $frame, 0, 0, 0, 0, $width, $height);
	        imagecopy($newsubframe, $textsubframe, 0, 0, 0, 0, $subframeW, $subframeH);
	   	    
	        // GD distributed with Ubuntu is missing some functionality.
	    	if (function_exists('imagefilter')) {
	            imagefilter($newsubframe, IMG_FILTER_BRIGHTNESS, (int)($curlevel += $brightChangePerFrame));
	        }
	        
	        // now copy the new subframe onto our ref frame
	        imagecopy($newframe, $newsubframe, $width * 0.15 + 50, 0, 0, 0, $subframeW, $subframeH);
	        $framefilename = sprintf("%s/frame_%04d.jpg", $basedir, $frameCount);
	        imagejpeg($newframe, $framefilename);
    	    if ($ctr == $fadeframes-1) {
	        	$frame = $newframe;
	        } else {
	        	imagedestroy($newframe);
	        }
    	    imagedestroy($newsubframe);
	    }

    	// now let it just sit on the screen for the defined time
	    debug("displaying the text");
	    $textDisplayFrames = $textDisplayTime * $frameRate;
	    for ($ctr = 0; $ctr < $textDisplayFrames; $ctr++, $frameCount++) {
	        $framefilename = sprintf("%s/frame_%04d.jpg", $basedir, $frameCount);
	        imagejpeg($frame, $framefilename);
	    }

    	// don't need this anymore
	    imagedestroy($frame);
	    echo " done\n";
	    return($basedir);
    }

    public function encodeVideoM4V($frameRate, $frameDir) {
	    $this->infoMovieFile = $outputFileName = "About" . FILENAME_WORD_SPACE_CHAR . $this->videoInfo['cleanTitle'] . '.mp4';

	    $cmd = 'jpeg2yuv -v 0 -j "' . $frameDir . '/frame_%04d.jpg" -f ' . $frameRate . ' -I p | ffmpeg -y -i - -vcodec libx264 -b 50k -acodec libfaac -ab 48k -ar 48000 -ac 2 -s ' . $this->resolution['width'] . 'x' . $this->resolution['height'] . ' -f mp4 "All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '"'; 
	    if ($GLOBALS['debug']) {
	        echo "Running: $cmd" . "\n";
	        `$cmd`;
    	} else {
	        echo "Encoding Info Movie File...";
	        `$cmd > /dev/null 2>&1`;
	        echo " done\n";
	    }
    }
    
    public function encodeVideoM2V($frameRate, $frameDir) {
	    $this->infoMovieFile = $outputFileName = "About" . FILENAME_WORD_SPACE_CHAR . $this->videoInfo['cleanTitle'] . '.mpg';

	    $cmd = 'jpeg2yuv -v 0 -j "' . $frameDir . '/frame_%04d.jpg" -f ' . $frameRate . ' -I p | ffmpeg -y -i - -vcodec mpeg2video -b 640000 -acodec mp2 -ab 48k -ar 48000 -ac 2 -s ' . $this->resolution['width'] . 'x' . $this->resolution['height'] . ' -f dvd "All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '"'; 
	    if ($GLOBALS['debug']) {
	        echo "Running: $cmd" . "\n";
	        `$cmd`;
    	} else {
	        echo "Encoding Info Movie File...";
	        `$cmd > /dev/null 2>&1`;
	        echo " done\n";
	    }
    }    

    public function encodeVideo() {
	    // maybe these should be dynamic?
	    $frameRate = 30;

	    $this->getCoverPhoto();
	    $framedir = $this->buildVideoFrames($frameRate);
	    switch ($this->output_type) {
	        case 'm4v':
	        case 'mp4':
		        $this->encodeVideoM4V($frameRate, $framedir);
		        break;
		    case 'm2v':
		    case 'mpg':		        		        
		        $this->encodeVideoM2V($frameRate, $framedir);
		        break;		    
	    }
    }

    public function createSymLinkTree() {
	    if (!empty($this->videoInfo['genres'])) {
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

$yn = readline("Move this file? [y/N]: ");
if (strtolower($yn) == "y") {
    $cmd = 'mv "' . $i2m->filename . '" "All/' . $i2m->videoInfo['cleanTitle'] . "/" . basename($i2m->filename) . '"';
    `$cmd`;
    
    ##rename("/tmp/tmp_file.txt", "/home/user/login/docs/my_file.txt");
}

exit(0);
