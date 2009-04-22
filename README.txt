Creates a MPEG2 video summarising a film using IMDB data.

Copyright (c) 2009 Flexion.Org, http://flexion.org/

License

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

Introduction

I run Mediatomb DLNA server with my PS3 as the client. I am working towards 
importing my entire DVD collection into my Mediatomb server. However, my wife 
wants to know something about each film in the library without having to dig out 
the DVD case from storage. My solution is to include a MPEG-2 video displaying 
the film summary in the Mediatomb library for each DVD I have imported so it can
be easily viewed from the PS3.

Usage

This scripts takes one parameter as input, a film title. The plotline, year of 
release, genres, cast list and running time for that film are gathered from IMDB 
and formatted as text. That text is converted into an image and then encoded 
into a MPEG-2 video using the lowest possible bitrate/resolution that is 
acceptable to read when viewing on a 42" plasma from my sofa.

If the film title has spaces it should be wrapped in double quotes.

 php5 IMDB-to-MPEG.php Jumper
 php5 IMDB-to-MPEG.php "Batman Begins"

The first IMDB entry that matches the search string will be used, which works 
fine most of the time so long as the title string is accurate.

There is a possibility that the movie you are searching for get a hit for another
title first. In which case you can use 'list' mode. This will produce a list of
matching titles and includes the IMDB ID and year of relase to help you narrow 
down your selection.

 php IMDB-to-MPEG.php "The Waiting Room" list

Once you have identified the film you are after simply provide a second argument 
which is the IMDB ID. For example...

 php5 IMDB-to-MPEG.php "The Waiting Room" 0902348

You can also pass in 'preview' as the second arguament in which case the script 
will just displaying a text preview of the film summary.

 php5 IMDB-to-MPEG.php "The Waiting Room" preview

Directories for each matching genre are created and also one for the IMDB rating
(rounded down). The MPEG-2 is stored in the 'All' folder and then symlinked to 
the genres and rating for that film. I then copy my video into the appropriate 
directory in 'All'.

For example.

.
|-- All
|   `-- The_Waiting_Room
|       |-- About_The_Waiting_Room.mpg
|       `-- The_Waiting_Room.mpg
|-- Genres
|   `-- Drama
|       `-- The_Waiting_Room -> ../../All/The_Waiting_Room
|-- Ratings
    `-- 6
        `-- The_Waiting_Room -> ../../All/The_Waiting_Room

Requirements

 - This script requires the PHP5 cli. PHP4 will not work
 - This script requires the GD module for PHP
 - This script requires the imdbphp and texttoimage libraries (included)
 - This script requires 'jpeg2yuv' and 'mpeg2enc' to create the MPEG-2 videos
 - This script requires a Unix like OS such as Linux, FreeBSD, etc.

Known Limitations

This code was lashed up in a few hours, it ain't pretty but it works for me on 
my Ubuntu Linux systems, maybe it'll work for you too ;-)

Source Code

You can checkout the current branch from my Bazaar repository. This is a 
read-only repository, get in touch if you want to contribute and require write 
access.

 bzr co http://code.flexion.org/Bazaar/IMDB-to-MPEG/

References

 - http://avalanched.wordpress.com/2008/03/17/imdb-api-beta/
 - http://projects.izzysoft.de/trac/imdbphp
 
v1.0 2009, 22nd April.
 - Initial release
