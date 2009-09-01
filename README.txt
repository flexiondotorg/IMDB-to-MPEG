License

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

The fonts are distributed under the following copyright:
Copyright (c) 2003 by Bitstream, Inc. All Rights Reserved. Bitstream Vera is a 
trademark of Bitstream, Inc.

The fonts have a generous copyright, allowing derivative works (as long as 
"Bitstream" or "Vera" are not in the names), and full redistribution (so long as 
they are not *sold* by themselves). They can be be bundled, redistributed and 
sold with any software.

Introduction

I run Mediatomb DLNA server with my PS3 as the client. I am working towards 
importing my entire DVD and Blu-Ray collection into my Mediatomb server. 
However, my wife wants to know something about each film in the library without 
having to dig out the DVD case from storage. My solution is to include a MPEG 
video displaying the film summary in the Mediatomb library so it can be easily 
viewed from the PS3.

Usage

  IMDB-to-MPEG.php -f movie.mkv -t "Move Title" -i 1234567 -o m4v -r 864x480

The script accepts several arguments, one of -f, -t or -i are required.
  -f : Provide a path to a filename of the film to search for.
  -t : Provide a film title to search for.
  -i : Provide an IMDB ID to search for.
  -a : Automates execution by answering all prompts with the default response.
  -o : Set the MPEG output format: m4v (default), mp4, m2v or mpg.
  -r : Set the MPEG output resolution: 864x480 is the default.
  -d : Enable debug mode.
  -h : This help.

This scripts requires at least one argument or either filename (-f), title (-t)
or IMDB ID (-i). If you just supply the path to a file then the script will try 
to lookup the film based on that filename. In addition you can provide some 
optional arguments to help find the correct film. For example...

Search by title by passing the optional title argument -t. If the film title has 
spaces it should be wrapped in double quotes.

 ./IMDB-to-MPEG.php -f ~/Videos/The_Usual_Suspects.mpg -t "Usual Suspects" 
 
Search by IMDB ID by passing the optional IMDB ID argument -i 
 
 ./IMDB-to-MPEG.php -f ~/Videos/The_Usual_Suspects.mpg -i 0114814 

If you just want to refresh or create a film summary without having a film file 
you can omit the use of the filename argument (-f) and just use title (-t) or 
IMDB ID (-i) arguments. For example:

 ./IMDB-to-MPEG.php -t "Usual Suspects" 
 
Or 
 
 ./IMDB-to-MPEG.php -i 0114814  

When you run the script with the required search criteria the covert art, 
plotline, year of release, genres, cast list and running time for that film are 
gathered from IMDB and formatted as text. You'll be prompted to confirm that 
this is the film you are seeking. Here is an example.

---
The Usual Suspects (1995)

A boat has been destroyed, criminals are dead, and
the key to this mystery lies with the only
survivor and his twisted, convoluted story
beginning with five career crooks in a seemingly
random police lineup. (106 mins)

Starring Stephen Baldwin as Michael McManus,
Gabriel Byrne as Dean Keaton, Benicio Del Toro as
Fred Fenster, Kevin Pollak as Todd Hockney, and
Kevin Spacey as Roger 'Verbal' Kint.

Genres: Crime, Mystery, Thriller.

Rated 8.7 out of 10 from 227,964 votes.

Use this movie [Y/n]:

The first IMDB entry that matches the search string will be used, which works 
fine most of the time so long as the title string or IMDB ID are accurate. 
Should the search result not be the film you are looking for you can select an 
alternative from the list provided. Once a film has been selected the text is 
converted into a series of images and then encoded into a MPEG video. 

The default behaviour is to encode a MPEG-4 video at a resolution of 864x480.
You can optionally encode the video as MPEG-2 video. For example:

 ./IMDB-to-MPEG.php -t "Usual Suspects" -o m2v ~/Videos/The_Usual_Suspects.mpg

You can also change the resolution of the MPEG video independantly of the video 
format. For example:

 ./IMDB-to-MPEG.php -t "Usual Suspects" -r 640x480 ~/Videos/The_Usual_Suspects.mpg
 ./IMDB-to-MPEG.php -t "Usual Suspects" -r 720x576 -o m2v ~/Videos/The_Usual_Suspects.mpg

Directories for each matching genre are created and also one for the IMDB rating
(rounded down). The MPEG is stored in the 'All' folder and then sym-linked to 
the genres and rating for that film. You will be prompted if you want the film 
you supplied as input should be copied into the appropriate directory in 'All'.
You will end up with something like this. 

.
|-- All
|   `-- The_Usual_Suspects
|       `-- About_The_Usual_Suspects.mpg
|       `-- The_Usual_Suspects.mpg
|-- Genres
|   |-- Crime
|   |   `-- The_Usual_Suspects -> ../../All/The_Usual_Suspects
|   |-- Mystery
|   |   `-- The_Usual_Suspects -> ../../All/The_Usual_Suspects
|   `-- Thriller
|       `-- The_Usual_Suspects -> ../../All/The_Usual_Suspects
|-- Ratings
    `-- 8
        `-- The_Usual_Suspects -> ../../All/The_Usual_Suspects

Requirements

 - ffmpeg, php5-cli, php5-gd
 - An OS such as Linux, FreeBSD, Solaris, Mac OS X is required for 
   categorisation to work

Known Limitations

 - Categorisation doen't work on Windows as it doesn't have sym-link capability.
 - Categorisation doen't work on FAT32 file systems.

Source Code

You can checkout the current branch from my Bazaar repository. This is a 
read-only repository, get in touch if you want to contribute and require write 
access.

 bzr co http://code.flexion.org/Bazaar/IMDB-to-MPEG/

References

 - http://www.ypass.net/blog/2009/06/categorizing-your-movie-collection-with-imdb/
 - http://avalanched.wordpress.com/2008/03/17/imdb-api-beta/
 - http://projects.izzysoft.de/trac/imdbphp

v2.0 2009, 28th August.
 - Merged yet more contributions from Eric, http://www.ypass.net. Thanks Eric!
 - Added usage instructions.
 - Added MPEG-2 video encoding.
 - Improved video encoding speed by removing pre-processing with 'jpeg2yuv'.
 - Fixed spiffy animations when cover art is not available.
 - Fixed spiffy animations of platforms that may have incomplete GD.
 - Modified filename input so that an input filename is optional rather than 
   mandatory.

v1.2 2009, 17th July.
 - Merged extensive contributions from Eric, http://www.ypass.net. Thanks Eric!
 - Updated the README to reflect Eric's changes.
 - Not publically released.

v1.1 2009, 23rd April.
 - Fixed a bug where the film name has a forward slash (/) in it the files and
   directories failed to get created.
 - Documentation improvements   
 
v1.0 2009, 22nd April.
 - Initial release
