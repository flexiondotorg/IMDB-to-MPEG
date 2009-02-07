<?php
/*
What does it do?

This scripts takes one parameter as input, a film title. The plotline, year of 
release, genres, cast list and running time for that film are gathered from IMDB 
and formatted as text. That text is converted into an image and then encoded 
into a MPEG-2 video using the lowest possible bitrate/resolution that is 
acceptable to read when viewing on a 42" plasma from my sofa.

Why did I write this?

I run Mediatomb DLNA server with my PS3 as the client. I am working towards 
importing my entire DVD collection into my Mediatomb server. However, my wife 
wants to know something about each film in the library without having to dig out 
the DVD case from storage. My solution is to include a MPEG-2 video displaying 
the film summary in the Mediatomb library for each DVD I have imported so it can
be easily viewed from the PS3.

Usage: 

If the film title has spaces it should be wrapped in double quotes.

php5 imdb2mpeg Jumper
php5 imdb2mpeg "Batman Begins"

The first film which matches the search string will be used, which works fine 
for me so long as I am fairly accurate with my title string. Very occasionally I
the first search string match is not the right film and for those cases you can
provide a second argument of the IMDB ID. For example...

php imdb2mpeg.php "The Waiting Room" 0902348

You can also pass in 'test' as the IMDB ID in which case the script will match
on title only and exit after displaying a text preview of the film summary.

Directories for each matching genre are created (not Windows). The MPEG-2 is 
stored in the 'All' genre and then symlinked to all the genres for that film. 
This creates a genre categorisation that I use to store the imported DVD into.

Requirements:

This script requires the PHP5 cli. PHP4 will not work
This script requires the GD module for PHP
This script requires the imdbphp and texttoimage libraries (included)
This script requires 'jpeg2yuv' and 'mpeg2enc' to create the MPEG-2 videos
This script requires a Unix like OS such as Linux, FreeBSD, etc.

This code was lashed up in a few hours, it ain't pretty but it works for me on 
my Ubuntu Linux systems, maybe it'll work for you too ;-)

References:

 - http://avalanched.wordpress.com/2008/03/17/imdb-api-beta/
 - http://projects.izzysoft.de/trac/imdbphp
*/

require('imdbphp-1.0.8/imdb.class.php');
require('texttoimage-1.0/TextToImage.class.php');

$video_format = "PAL";  // can also be "NTSC"

// We can't do sym-links and /dev/zero on Windows. 
if (PHP_OS == 'WINNT')
{
	echo("ERROR! Sorry, this script doesn't work on Windows\n");
    exit;
}

// Is this the correct PHP version?
if (substr(PHP_VERSION, 0 ,1) < '5')
{
	echo("ERROR! PHP5 or better is required, you are running " . PHP_VERSION ."\n");
    exit;
}

// Have we got enough parameters?
if ($argc < 2)
{
	echo("ERROR! You must supply a movie title to lookup and an option IMDB ID.\n");
    echo("For example:\n");
	echo("\n");
	echo('  php5 imdb2mpeg.php "The Usual Suspects"' . " 0114814\n");
	exit;
}
else
{
    // First param is the search string, second is the option IMDB ID.
    // No validation, we work on trust here ;-)
	$movie_title = $argv[1];
	if ($argv[2])
	{
	    $imdb_id = $argv[2];
	}
	else
	{
	    $imdb_id = 0;
	}
}

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

print('Searching for ' . $movie_title . "\n");

$search = new imdbsearch();
$search->setsearchname($movie_title);
$results = $search->results();
#print_r($results);

$movies = array();

foreach ($results as $result) 
{    
    // If an IMDB ID was not specified, then grab all the movies.
    // If an IMDB ID was specified, then just get that one movie.   
    if ($imdb_id == 0)
    {
        // If this movie has a title and has not been found before, add it to the list.
        if (!in_array($result->imdbID, $movies))
        {
            $movies[] = $result->imdbID;        
        }    
    }
    else
    {
        if ($result->imdbID == $imdb_id)
        {
            $movies[] = $result->imdbID;                
        }            
    }        
}
//print_r($movies);

if (empty($movies)) 
{
    echo("No results found at IMDB for $movie_title\n");
    exit;
}
else
{
    print("Match found\n");
}

print("Formatting text\n");
$movieid = $movies[0];
$movie = new imdb($movieid);
$movie->setid($movieid);

$movie_title = str_cleaner($movie->title(), true); 

$movie_text = $movie_title . ' (' . str_cleaner($movie->year()) . ")\n\n";

if ( strlen($movie->plotoutline()) )
{
    $movie_plot = str_cleaner($movie->plotoutline());    
}    
else
{
    $plots = $movie->plot();
    $movie_plot = str_cleaner($plots[0]);    
}

// Strip the "Written by" stuff from the plot. 
$written_by = strpos($movie_plot, 'Written by');
if ($written_by)
{
    $movie_plot = trim(substr($movie_plot, 0, $written_by));
}

$movie_text .= $movie_plot;
$movie_text .= ' (' . str_cleaner($movie->runtime()) . " mins)\n\n";

$movie_text .= "Starring ";
$cast = $movie->cast();

if (count($cast) > 5)
{
	$cast_count = 5;
}
else
{
	$cast_count = count($cast);
}
if (!empty($cast)) 
{
    for ($i = 0; $i + 1 < $cast_count; $i++) 
    {
        $movie_text .= str_cleaner($cast[$i]["name"]) . ' as ' . str_cleaner($cast[$i]["role"]) . ', ';
    }
    $movie_text .= 'and ' . str_cleaner($cast[$i]["name"]) . ' as ' . str_cleaner($cast[$i]["role"]) . ".\n\n";    
}

$movie_genres = $movie->genres();
if (!empty($movie_genres)) 
{
    $movie_text .= "Genres: ";
    for ($i = 0; $i + 1 < count($movie_genres); $i++) 
    {
        $movie_text .= str_cleaner($movie_genres[$i]) . ', ';
    }
    $movie_text .= str_cleaner($movie_genres[$i]) . ".\n";
}

// Rating and Votes
$movie_rating = $movie->rating();
$movie_votes  = $movie->votes();
if (!empty($movie_rating) && !empty($movie_votes)) 
{ 
    $movie_text .= "\nRated " . $movie_rating . " out of 10 from " . $movie_votes . " votes.\n"; 
}

// Word wrap the text, this needs manual tweaking to work with the font size in 
// the makeImageF() method and the JPEG resolution.  
$movie_text = wordwrap($movie_text, 52, "\n", true);

print("---\n");
print($movie_text);
print("---\n");

// If 'test' was passed in as the second agument, exit now.
if ( $imdb_id == "test")
{
    exit();
}

//Format the image/video filename to remove spaces and such.
$out_filename = str_replace(' ', '_', $movie_title);
$out_filename = str_replace('&', 'and', $out_filename);
$out_filename = str_replace(array("'", ".", ",", ":"), '', $out_filename);
$out_filename = str_replace(array("·"), '-', $out_filename);

$jpeg_filename = $out_filename . '_000';
$m2v_filename = $out_filename . '.m2v';
$mp2_filename = $out_filename . '.mp2';
$mpg_filename = 'About_' . $out_filename . '.mpg';
//ini_set("display_errors",1);

print("Creating JPEG\n");
// Create a single JPEG then encode it as a 20 second DVD compliant MPEG-2 file.
// Aspect is hard coded to 1:1
$_im = new TextToImage();

// Yes, we do need to reduce the audio length by 0.1?! Failing to do so will 
// result in a good deal of the muxing to fail due a bit mismatch in the audio 
// stream. No idea why the fudge works and why things don't work without it.
$movie_length = 20;
$audio_freq = 16;
$audio_loop = ( ($movie_length - 0.1) * 1000) * $audio_freq;

$movie_audio_cmd = 'dd if=/dev/zero bs=4 count=' . $audio_loop . ' 2>/dev/null | toolame -f -m m -b 8 -a -s ' . $audio_freq . ' /dev/stdin ' . $mp2_filename . ' 2>/dev/null';
$movie_mux_cmd = 'mplex -v 0 -f 8 -o ' . $mpg_filename . ' ' . $m2v_filename . ' ' . $mp2_filename;

if ($video_format == "PAL")
{
    $_im->makeImageF($movie_text, './Arial.ttf', 704, 576, 0, 0, 20, array(0xFF, 0xFF, 0xFF), array(0x0, 0x0, 0x0));    
    $frame_loop = (25 * $movie_length);        
    $movie_mpeg_cmd = 'jpeg2yuv -v 0 -j ' . $out_filename . '_%03d.jpg -l ' . $frame_loop . ' -f 25 -I p | mpeg2enc -v 0 -b 512 -f 8 -a 1 -n n -F 3 -o ' . $m2v_filename;
}
else //make NTSC by default
{
    $_im->makeImageF($movie_text, './Arial.ttf', 704, 480, 0, 0, 20, array(0xFF, 0xFF, 0xFF), array(0x0, 0x0, 0x0));
    $frame_loop = (24 * $movie_length);    
    $movie_mpeg_cmd = 'jpeg2yuv -v 0 -j ' . $out_filename . '_%03d.jpg -l ' . $frame_loop . ' -f 24 -I p | mpeg2enc -v 0 -b 512 -f 8 -a 1 -n n -F 2 -o ' . $m2v_filename;    
}

$_im->saveAsJpg($jpeg_filename);
print("Encoding MPEG-2 video\n");
exec($movie_mpeg_cmd);
print("Encoding MPEG-2 audio\n");
exec($movie_audio_cmd);
print("Muxing MPEG-2\n");
exec($movie_mux_cmd);

if (!empty($movie_genres)) 
{
    for ($i = 0; $i < count($movie_genres); $i++) 
    {                           
        if ($i == 0)
        {
            @mkdir('Genres/All/' . $out_filename, 0777, true);                            
            copy($mpg_filename, 'Genres/All/' . $out_filename . '/' . $mpg_filename);         
        }        

        @mkdir('Genres/' . str_cleaner($movie_genres[$i]), 0777, true);                                                    
        @symlink('../All/' . $out_filename, 'Genres/' . str_cleaner($movie_genres[$i]) . '/' . $out_filename);                        
    }        
}

//Clean up.
unlink($jpeg_filename . '.jpg');
unlink($m2v_filename);
unlink($mp2_filename);
unlink($mpg_filename);

print("Done.\n");
?>
