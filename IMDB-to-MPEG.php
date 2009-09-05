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

// change this to " " if you want spaces in your filenames
define("FILENAME_WORD_SPACE_CHAR", "_");

$supported_output = array(
    'm4v' => 'H.264 video and AAC audio',
    'mp4' => 'H.264 video and AAC audio',
    'm2v' => 'MPEG-2 video and MPEG layer 2 audio',
    'mpg' => 'MPEG-2 video and MPEG layer 2 audio',
    );

// Disable debug mode by default
$debug = false;

// Disable auto mode by default
$auto = false;

echo("IMDB-to-MPG v" . VERSION . " - Create a MPEG video summarising a movie using data from IMDB.\n");
echo("Copyright (c) 2009 Flexion.Org, http://flexion.org. GPLv2 License.\n");
echo("\n");

require(dirname(__FILE__) . '/imdbphp-1.1.4/imdb.class.php');

// Debug output function
function debug($text) {
    if ($GLOBALS['debug']) {
        echo "DEBUG: $text\n";
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

// Usage information
function usage() {
    echo ("Usage\n");
    echo ("  IMDB-to-MPEG.php -f movie.mkv -t \"Move Title\" -i 1234567 -o m4v -r 864x480 -c UK\n");
    echo ("\n");
    echo ("The script accepts several arguments. One of -f, -t or -i are required.\n");
    echo ("  -f : Provide a path to a filename of the film. The move to search for\n");
    echo ("       will be derived from the filename. The filename will be moved to\n");
    echo ("       categorised directory structure.\n");    
    echo ("  -t : Provide a film title to search for.\n");
    echo ("  -i : Provide an IMDB ID to search for. Providing an IMDB ID will override\n");
    echo ("       the title, if supplied, or a title derived from a filename\n");    
    echo ("  -a : Automates execution by accepting the first search result.\n");    
    echo ("  -c : Set the country you want to use for getting the movie certificate.\n");
    echo ("       For example: Australia, Canada, Germany, UK, USA, etc.\n");                
    echo ("  -o : Set the MPEG output format, one of 'm4v', 'mp4', 'm2v' or 'mpg'.\n");
    echo ("  -r : Set the MPEG output resolution in the format 852x480. Examples:\n");        
    echo ("       Resolution  Aspect ratio\n");
    echo ("       720x480     4:3\n");
    echo ("       852x480     16:9\n");
    echo ("       1280x720    16:9\n");
    echo ("       1365x768    16:9\n");
    echo ("       1920x1080   16:9\n");
    echo ("  -d : Enable debug mode.\n");    
    echo ("  -h : This help.\n");
}

//  Make sure we can function
// We can't do sym-links or /dev/null on Windows. 
if (PHP_OS == 'WINNT') {
    echo("ERROR: Sorry, this script doesn't work on Windows\\nn");
    exit(1);
}

// Is this the correct PHP version?
if (substr(PHP_VERSION, 0 ,1) < '5') {
    echo("ERROR: PHP5 or better is required, you are running " . PHP_VERSION ."\n\n");
    exit(1);
}

// Have we got enough arguments?
if ($argc == 1) {
    echo "ERROR: As a minimum requirement you must specifiy a filename, title or IMDB ID.\n\n";
    usage();
    exit(1);
}

// Parse Command Line Options
// getopt is somewhat broken in php < 5.3.0, but let's try
$longopts = array(
    'title:',
    'id:',
    'filename:',
    'country:',
    'output-type:',
    'resolution:',
    'auto',
    'debug',
    'help',
    );
    
if (version_compare(phpversion(), "5.3.0") >= 0) {
    $optlist = getopt("dat:i:f:c:o:r:h:", $longopts);
} else {
    $optlist = getopt("dat:i:f:c:o:r:h:");
}

foreach ($optlist as $optind => $optarg) {
    switch ($optind) {
    case 'debug':
    case 'd':
        $debug = true;
        debug("WARNING: Debugging enabled.... STAND BACK!\n");
        break;
    case 'auto':
    case 'a':
        $auto = true;
        debug("Automatic mode enabled\n");
        break;      
    case 'title':
    case 't':
        $options['title'] = $optarg;
        break;
    case 'id':
    case 'i':
        $options['id'] = $optarg;
        break;
    case 'filename':
    case 'f':
        $options['filename'] = $optarg;
        if (! @stat($options['filename'])) {
            echo "ERROR: No such file: $options[filename]\n\n";
            exit(1);
        }       
        break;                      
    case 'country':
    case 'c':
        $options['country'] = $optarg;
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

// Have we got the essential arguments?
if ( !isset($options['filename']) && !isset($options['title']) && !isset($options['id'])) {
    echo "ERROR: As a minimum requirement you must specifiy a filename, title or IMDB ID.\n\n";
    usage();
    exit(1);
}

// if the title isn't specified, let's build one from the filename
if ( !isset($options['title']) && isset($options['filename']) ) {
    // strip off any directory and extension (yes, this could all be one
    // line... readability and all that
    $title = basename($options['filename']);
    $title = preg_replace('/^(.*)\..*$/', '$1', $title);
    $title = str_replace("_", " ", $title);
    $options['title'] = $title;
} elseif (!isset($options['title'])) {
    $options['title'] = '';
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

// Check the output type is valid
if (! isset($options['output_type'])) {
    $options['output_type'] = 'mp4';
}

if (! array_key_exists($options['output_type'], $supported_output)) {
    echo "ERROR: Unsupported output type : '" . $options['output_type'] . "' Supported types are:\n";
    
    foreach ($supported_output as $format => $description) {
        printf("           %-5.5s    $description\n", $format, $description);
    }
    echo("\n");
    exit(1);
}

// Check that the output resolution is valid
if (! isset($options['resolution'])) {
    $options['resolution'] = '852x480';
}

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
        
        if ( isset($options['filename']) ) {
            $this->filename = $options['filename'];
        } else {
            $this->filename = '';        
        }           
        
        if ( isset($options['country']) ) {
            $this->country = $options['country'];
        } else {
            $this->country = '';        
        }                   
        
        $this->output_type = $options['output_type'];
        $this->resolution = $options['resolution'];        
        
        $this->fadetime = 0.5;
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
        
        if (is_array($this->imdbMovie->mpaa()) && isset($this->country)) {    
            $certificate_array = $this->imdbMovie->mpaa();
            $this->videoInfo['certificate'] = $certificate_array[$this->country];           
        } else {
            $this->videoInfo['certificate'] = '';
        }

        // Remove special chars from the "clean" title.
        $cleanTitle = str_replace(' ', FILENAME_WORD_SPACE_CHAR, $this->videoInfo['title']);
        $cleanTitle = str_replace('&', 'and', $cleanTitle);        
        $cleanTitle = str_replace(array("'", ".", ",", ":"), '', $cleanTitle);                
        $this->videoInfo['cleanTitle'] = str_replace(array("·", "/"), '_', $cleanTitle);                

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

    public function underline($wraplen = 75) {
    
        $underline = "\n";
        
        for ($loop = 0; $loop < $wraplen; $loop++) {
            $underline .= "-";
        }    
        return $underline . "\n";
    }

    public function displayMovie($wraplen = 75) {
        $this->setVideoInfo();      
        
        echo $this->underline($wraplen);
        echo $this->getVideoText($wraplen);
        echo $this->underline($wraplen);
    }

    public function getVideoText($wraplen) {
        $text = $this->videoInfo['title'] . " (" . $this->videoInfo['year'] . ")\n";            
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
        
        if (strlen($this->videoInfo['certificate'])) {
            $text .= 'Certificate: ' . wordwrap($this->videoInfo['certificate'], $wraplen) . "\n\n";        
        }
        
        if (strlen($this->videoInfo['ratingtext'])) {
            $text .= wordwrap($this->videoInfo['ratingtext'], $wraplen) . "";
        }
    
        return($text);
    }

    public function listResults() {
        echo $this->underline(75);
        echo "IMDB ID    Movie Title";
        echo $this->underline(75);        
        foreach ($this->searchResults as $index => $result) {
            printf("%-8.8s   %s (%4s)\n", $result->imdbID(), str_cleaner($result->title()), $result->year());
        }
        echo $this->underline(75);
    }

    static public function validId($id) {
        if (! preg_match('/^\d{7}$/', $id)) {
            return false;
        }
        return true;
    }

    public function createTextFrame($width, $height, $fontSize = 14) {
        $fontName = "Vera.ttf";
        $fontPath = dirname(__FILE__) . '/' . $fontName;
        $frameWidth = $width;
        $frameHeight = $height;
        $wrapLength = 58;
        $rightMargin = 2;  // the right margin needs to be handled by the caller
        $vertialMargin = 2;

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
        echo("Downloading covert art...");
        if (! ($img = @file_get_contents($this->videoInfo['coverPhotoURL']))) {
            $this->videoInfo['coverPhoto'] = null;
            $this->coverDisplayTime = 0;
            $this->textDisplayTime = 19;                    
            echo(" not found\n");
            return;
        }
        $this->videoInfo['coverPhoto'] = $img;
        $this->coverDisplayTime = 2;
        $this->textDisplayTime = 17;        
        echo(" done\n");
        return;
    }

    public function prepareTemp() {
    
        // Create temporary files in the temporary files directory using 
        // sys_get_temp_dir()
        $this->basedir = sys_get_temp_dir() . '/i2m/';                

        // make our temp dir if it doesn't exist
        @mkdir($this->basedir);

        // make sure we can write to it (isn't there an "is writable function or something?)
        if (! @touch($this->basedir . "test")) {
            echo "ERROR: Temp directory is not writable: $this->basedir\n\n";
            exit(1);
        }

        // delete anything in there
        if (! $d = opendir($this->basedir)) {
            echo "ERROR: Could not open temp directory: $this->basedir\n\n";
            exit(1);
        }

        while ($dirent = readdir($d)) {
            if ($dirent == "." || $dirent == "..") {
                continue;
            }
            unlink($this->basedir . $dirent);
        }
        closedir($d);                        
    }

    public function buildAudioFrames() {
        // Setup our audio sample parameters
        $this->NrChannels=2;
        $this->SampleRate=48000;
        $this->NrSeconds=20;

        echo("Generating audio frames...");
        // Calcualte the sample size.
        $sample_size = $this->SampleRate * $this->NrChannels * $this->NrSeconds;

        // Create some silence.
        $silence = '';
        while(strlen($silence) < $sample_size) $silence .= chr(0);

        // Write our silence to a file.
        $fp = fopen($this->basedir . 'silence.raw', 'w');
        fwrite($fp, $silence);
        fclose($fp);
        echo " done\n";            
    }        

    public function buildVideoFrames($frameRate = 24) {
            
        $width = $this->resolution['width'];
        $height = $this->resolution['height'];
        $fadetime = $this->fadetime;
        $coverDisplayTime = $this->coverDisplayTime;
        $textDisplayTime = $this->textDisplayTime;
        $fadeframes = $fadetime * $frameRate;      
        
        echo "Generating video frames...";
        debug("Fader is set to $fadetime seconds.  FrameRate is $frameRate seconds... fades last $fadeframes frames");
    
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

                $framefilename = sprintf("%s/frame_%04d.jpg", $this->basedir, $frameCount);
                imagejpeg($newframe, $framefilename);
            }

            // now let it just sit on the screen for the defined time
            debug("displaying the cover");
            $coverDisplayFrames = $coverDisplayTime * $frameRate;
            for ($ctr = 0; $ctr < $coverDisplayFrames; $ctr++, $frameCount++) {
                $framefilename = sprintf("%s/frame_%04d.jpg", $this->basedir, $frameCount);
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
                $framefilename = sprintf("%s/frame_%04d.jpg", $this->basedir, $frameCount);
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
            $moveToX = (int)($width * 0.05);
            $moveToY = (int)($height * 0.02);

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
            
                $curX -= $stepX;
                $curY -= $stepY;
                $curW -= $stepWidth;
                $curH = ($curW * $aspectRatio);
                debug("new size is $curW x $curH");
                imagecopyresampled($newframe, $coverPhoto, $curX, $curY, 0, 0, $curW, $curH, $cX, $cY);
                $framefilename = sprintf("%s/frame_%04d.jpg", $this->basedir, $frameCount);
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

        // get ourselves the partial frame accounting for the cover image                       
        $subframeW = imagesx($frame) * 0.85;
        $subframeH = imagesy($frame);

        /*
        * Same for the text... fade it in, wait a the lenght, but DO NOT fade it out
        */
                       
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
            $framefilename = sprintf("%s/frame_%04d.jpg", $this->basedir, $frameCount);
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
            $framefilename = sprintf("%s/frame_%04d.jpg", $this->basedir, $frameCount);
            imagejpeg($frame, $framefilename);
        }

        // don't need this anymore
        imagedestroy($frame);
        echo " done\n";                
    }

    public function makeMPEG($frameRate, $videoFormat = "MPEG-4") {
        
        $frameDir = $this->basedir;
        
        $this->infoMovieFile = $outputFileName = "About" . FILENAME_WORD_SPACE_CHAR . $this->videoInfo['cleanTitle'];  
    
        //Remove any previous, and possible old, summary clips.
        if (file_exists('All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '.mpg')) {        
            unlink('All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '.mpg');
        }                   
        if (file_exists('All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '.mp4')) {                                    
            unlink('All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '.mp4');               
        }             
        
        // Default to MPEG-4 is the passed videoFormat is invalid
        if ($videoFormat != "MPEG-2" || $videoFormat != "MPEG-4") {
            $videoFormat = "MPEG-4";
        }
         
        // This is a pretty rough calculation but suffcient for most purposes.        
        if ($this->resolution['width'] > 999) {            
            $videoBitrate= ($this->resolution['width'] - 100) * 100;        
        } else {
            $videoBitrate= ($this->resolution['width'] - 100) * 1000;        
        }
            
        echo "Encoding " . $videoFormat . "...";                            
        // Make the appropriate video clip
        if ($videoFormat = "MPEG-4") {
            
            $cmd = 'ffmpeg -v -1 -y -r ' . $frameRate . ' -f image2 -i "' . $frameDir . '/frame_%04d.jpg" -f s16le -i "' . $frameDir . 'silence.raw" -vcodec libxvid -b ' . $videoBitrate . ' -acodec libfaac -ab 48k -ar 48000 -ac 2 -s ' . $this->resolution['width'] . 'x' . $this->resolution['height'] . ' -f mp4 "All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '.mp4" 2>' . $frameDir . '/ffmpeg.log'; 
        } elseif ($videoFormat = "MPEG-2") {
            $cmd = 'ffmpeg -v -1 -y -r ' . $frameRate . ' -f image2 -i "' . $frameDir . '/frame_%04d.jpg" -f s16le -i "' . $frameDir . 'silence.raw" -vcodec mpeg2video -b ' . $videoBitrate . '  -acodec mp2 -ab 48k -ar 48000 -ac 2 -s ' . $this->resolution['width'] . 'x' . $this->resolution['height'] . ' -f dvd "All/' . $this->videoInfo['cleanTitle'] . '/' . $outputFileName . '.mpg" 2>' . $frameDir . '/ffmpeg.log'; 
        }
                                
        `$cmd`;
        echo " done\n";        
    }
  
    public function encodeVideo() {
        // maybe these should be dynamic?
        $frameRate = 24;

        $this->prepareTemp();
        $this->getCoverPhoto();
        $this->buildAudioFrames();
        $this->buildVideoFrames($frameRate);        
        switch ($this->output_type) {
            case 'm4v':
            case 'mp4':
                $this->makeMPEG($frameRate, 'MPEG-4');
                break;
            case 'm2v':
            case 'mpg':                             
                $this->makeMPEG($frameRate, 'MPEG-2');
                break;          
        }
    }

    public function createSymLinkTree() {
        echo("Categorising...\n");
        if (!empty($this->videoInfo['genres'])) {
            @mkdir('All/' . $this->videoInfo['cleanTitle'], 0777, true);
            @touch('All/' . $this->videoInfo['cleanTitle'] . "/.imdbid_" . $this->imdbMovie->imdbID(), 0777, true);
            for ($i = 0; $i < count($this->videoInfo['genres']); $i++)
            {
                echo " - Adding to Genre/" . str_cleaner($this->videoInfo['genres'][$i]) . "\n";
                @mkdir('Genre/' . str_cleaner($this->videoInfo['genres'][$i]), 0777, true);
                @symlink('../../All/' . $this->videoInfo['cleanTitle'], 'Genre/' . str_cleaner($this->videoInfo['genres'][$i]) . '/' . $this->videoInfo['cleanTitle']);
            }
        }
        
        if (!empty($this->videoInfo['rating'])) {
            echo " - Adding to Rating/" . (int)$this->videoInfo['rating'] . "\n";
            @mkdir('Rating/' . (int)$this->videoInfo['rating'], 0777, true);
            @symlink('../../All/' . $this->videoInfo['cleanTitle'], 'Rating/' . (int)$this->videoInfo['rating'] . '/' . $this->videoInfo['cleanTitle']);
        }
        
        if (!empty($this->videoInfo['certificate'])) {
            echo " - Adding to Certificate/" . $this->videoInfo['certificate'] . "\n";
            @mkdir('Certificate/' . $this->videoInfo['certificate'], 0777, true);
            @symlink('../../All/' . $this->videoInfo['cleanTitle'], 'Certificate/' . $this->videoInfo['certificate'] . '/' . $this->videoInfo['cleanTitle']);
        }    
    }
}

$i2m = new IMDB_to_MPEG($options);

// If searching by IMDB ID just go a fetch the appropriate movie.
if (isset($i2m->id)) {
    $i2m->getMovieById($i2m->id);
    
    // Make sure we found something
    if ($i2m->imdbMovie->title()) {
        echo("Found : " . str_cleaner($i2m->imdbMovie->title()) . " (" . str_cleaner($i2m->imdbMovie->year()) . ")");
        $i2m->displayMovie();    
    } else {
        echo("ERROR: No matching film found for IMDB ID : $i2m->id\n");
        exit(1);
    }
} else {    
    // We must be search by title if we get here
    echo "Searching for \"" . $i2m->title . "\"";
    $i2m->search();            
        
    // Make sure we found something
    if (count($i2m->searchResults)) {
        echo " : " . count($i2m->searchResults) . " possible matches.\n";        
    } else {
        echo("ERROR: No matching film found for title : $i2m->title\n");
        exit(1);
    }                

    echo("Found : " . str_cleaner($i2m->imdbMovie->title()) . " (" . str_cleaner($i2m->imdbMovie->year()) . ")");        
    $i2m->displayMovie();           

    // If auto mode is enabled use the first hit
    if ($auto) {
        $i2m->imdbMovie = $i2m->searchResults[1];
    } else {            
        // Ask the user to confirm/identify the movie. Loop until happy :-)
        $movie_selected = False;
        do 
        {       
            $yn = readline("Use this movie [Y/n]: ");
            if (strtolower($yn) == "y" || $yn == '') {
                $i2m->imdbMovie = $i2m->searchResults[1];
                $movie_selected = True;
            } else {
                $i2m->listResults();        
                $id = null;
                while (! $i2m->validId($id)) {
                    $id = readline("Enter an IMDB ID for this movie: ");
                }
                $i2m->getMovieById($id);
                echo("Found : " . str_cleaner($i2m->imdbMovie->title()) . " (" . str_cleaner($i2m->imdbMovie->year()) . ")");                
                $i2m->displayMovie();
            }        
        } while (! $movie_selected);
    }    
}

// Create the symlinks
$i2m->createSymLinkTree();

// Make a our video synopsis clip
$i2m->encodeVideo();

// If we specified a video file move it to the movie store
if ( isset($options['filename']) ) { 
    debug("Moving " . $i2m->filename . " to All/" . $i2m->videoInfo['cleanTitle'] . "/");
    echo "Moving " . $i2m->filename . " to All/" . $i2m->videoInfo['cleanTitle'] . "/\n";    
    rename($i2m->filename, 'All/' . $i2m->videoInfo['cleanTitle'] . '/' . basename($i2m->filename)); 
}

echo("All Done!\n");
exit(0);
