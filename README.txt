License

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

The fonts are distributed under the following copyright:
Copyright (c) 2003 by Bitstream, Inc. All Rights Reserved. Bitstream Vera is a 
trademark of Bitstream, Inc.

The fonts have a generous copyright, allowing derivative works (as long as 
"Bitstream" or "Vera" are not in the names), and full redistribution (so long as 
they are not *sold* by themselves). They can be be bundled, redistributed and 
sold with any software.

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
and formatted as text. Here is an example.

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
---

The text is converted into an image and then encoded into a MPEG-2 video using 
the lowest possible bitrate/resolution that is acceptable to read when viewing 
on a 42" plasma from my sofa. 

If the film title has spaces it should be wrapped in double quotes.

 php5 IMDB-to-MPEG.php Batman
 php5 IMDB-to-MPEG.php "Batman Begins"

The first IMDB entry that matches the search string will be used, which works 
fine most of the time so long as the title string is accurate.

There is a possibility that the movie you are searching for get a hit for another
title first. In which case you can use 'list' mode. This will produce a list of
matching titles and includes the IMDB ID and year of relase to help you narrow 
down your selection.

 php IMDB-to-MPEG.php "The Usual Suspects" list

Once you have identified the film you are after simply provide a second argument 
which is the IMDB ID. For example...

 php5 IMDB-to-MPEG.php "The Usual Suspects" 0114814

You can also pass in 'preview' as the second arguament in which case the script 
will just displaying a text preview of the film summary.

 php5 IMDB-to-MPEG.php "The Usual Suspects" preview

Directories for each matching genre are created and also one for the IMDB rating
(rounded down). The MPEG-2 is stored in the 'All' folder and then symlinked to 
the genres and rating for that film. I then copy my video into the appropriate 
directory in 'All'. For example.

.
|-- All
|   `-- The_Usual_Suspects
|       `-- About_The_Usual_Suspects.mpg
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

 - dd, jpeg2yuv, mpeg2enc, mplex, php5-cli, php5-gd, toolame.
 - A real OS such as Linux, FreeBSD, Solaris, maybe even Mac OS X, etc.

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

v1.1 2009, 22nd April.
 - Fixed a bug where the film name has a forward slash (/) in it the files and
   directories failed to get created.
 
v1.0 2009, 22nd April.
 - Initial release
