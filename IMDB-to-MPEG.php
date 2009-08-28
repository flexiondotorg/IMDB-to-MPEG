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
*/

$VER="1.1";

echo("IMDB-to-MPG v$VER - Uses IMDB to create a MPEG2 video summary of a film.\n");
echo("Copyright (c) 2009 Flexion.Org, http://flexion.org. GPLv2 License.\n");
echo("\n");

require('imdbphp-1.1.4/imdb.class.php');
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
	echo('  php5 IMDB-to-MPEG.php "The Usual Suspects"' . " 0114814\n");
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
//print_r($results);

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

// If 'list' was passed in as the second agument, list a summary of the seach 
// matches and exit.
if ( $imdb_id === 'list' )
{
    for ($loop = 0; $loop < count($movies); $loop++ )
    {
        //print($movies[$loop] . "\n");
        $temp_movieid = $movies[$loop];
        $temp_movie = new imdb($temp_movieid);
        $temp_movie->setid($temp_movieid);        
        
        $temp_movie_title = str_cleaner($temp_movie->title(), true); 
        $temp_movie_text = $temp_movieid . ' ' . $temp_movie_title . ' (' . str_cleaner($temp_movie->year()) . ")\n";
        print($temp_movie_text);
    }
    exit();    
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
$point_pos = strpos($movie_rating, '.');
$basic_rating = substr($movie_rating, 0, $point_pos);

$movie_votes  = $movie->votes();
if (!empty($movie_rating) && !empty($movie_votes)) 
{ 
    $movie_text .= "\nRated " . $movie_rating . " out of 10 from " . $movie_votes . " votes.\n"; 
}

// Word wrap the text, this needs manual tweaking to work with the font size in 
// the makeImageF() method and the JPEG resolution.  
$movie_text = wordwrap($movie_text, 50, "\n", true);

print("---\n");
print($movie_text);
print("---\n");

// If 'preview' was passed in as the second agument, exit now.
if ( $imdb_id === 'preview' )
{
    exit();
}

//Format the image/video filename to remove spaces and such.
$out_filename = str_replace(' ', '_', $movie_title);
$out_filename = str_replace('&', 'and', $out_filename);
$out_filename = str_replace(array("'", ".", ",", ":"), '', $out_filename);
$out_filename = str_replace(array("·", "/"), '-', $out_filename);

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
// stream. 
$movie_length = 20;
$audio_freq = 16;
$audio_loop = ( ($movie_length - 0.1) * 1000) * $audio_freq;

$movie_audio_cmd = 'dd if=/dev/zero bs=4 count=' . $audio_loop . ' 2>/dev/null | toolame -f -m m -b 8 -a -s ' . $audio_freq . ' /dev/stdin ' . $mp2_filename . ' 2>/dev/null';
$movie_mux_cmd = 'mplex -v 0 -f 8 -o ' . $mpg_filename . ' ' . $m2v_filename . ' ' . $mp2_filename;

if ($video_format == "PAL")
{
    $_im->makeImageF($movie_text, './Vera.ttf', 704, 576, 0, 0, 20, array(0xFF, 0xFF, 0xFF), array(0x0, 0x0, 0x0));    
    $frame_loop = (25 * $movie_length);        
    $movie_mpeg_cmd = 'jpeg2yuv -v 0 -j ' . $out_filename . '_%03d.jpg -l ' . $frame_loop . ' -f 25 -I p | mpeg2enc -v 0 -b 512 -f 8 -a 1 -n n -F 3 -o ' . $m2v_filename;
}
else //make NTSC by default
{
    $_im->makeImageF($movie_text, './Vera.ttf', 704, 480, 0, 0, 20, array(0xFF, 0xFF, 0xFF), array(0x0, 0x0, 0x0));
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
            @mkdir('All/' . $out_filename, 0777, true);                            
            @mkdir('Ratings/' . $basic_rating, 0777, true);                            
            copy($mpg_filename, 'All/' . $out_filename . '/' . $mpg_filename);
            @symlink('../../All/' . $out_filename, 'Ratings/' . $basic_rating . '/' . $out_filename);                                             
        }        

        @mkdir('Genres/' . str_cleaner($movie_genres[$i]), 0777, true);                                                    
        @symlink('../../All/' . $out_filename, 'Genres/' . str_cleaner($movie_genres[$i]) . '/' . $out_filename);                        
    }            
}

//Clean up.
unlink($jpeg_filename . '.jpg');
unlink($m2v_filename);
unlink($mp2_filename);
unlink($mpg_filename);

print("All Done!\n");
?>
