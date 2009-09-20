#!/bin/bash
#
# Reprocess a film store creating new summary videos and categorisation.
# 
# Copyright (c) 2009 Flexion.Org, http://flexion.org/
# 
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
# 

IFS=$'\n'

# Adjust this to point to where your films are stored.
FILM_STORE="${HOME}/Videos/Films"

# Adjust this with your country so that film certificate details are relevant to
# your localtion.
FILM_CERTIFICATE_COUNTRY="UK"

if [ ! -d ${FILM_STORE} ]; then
    echo "ERROR! Couldn't find your film store, edit ${0} and adjust FILM_STORE accordingly."
    exit 1
fi

# Get a list of All films
FILMS=`ls -1 ${FILM_STORE}/All/`

# Change to the root of my movie store
cd ${FILM_STORE}

# Delete the existing Generes, Ratings and Certificates directories. There 
# contents may well need changing and this avoids duplicates.
rm -rf Ratings
rm -rf Rating
rm -rf Genres
rm -rf Genre
rm -rf Certificates
rm -rf Certificate

# Loop through the films and create the IMDB summary
for FILM in ${FILMS}
do
    # Determine the film title from the directory name.
    if [ -d ${FILM_STORE}/All/${FILM} ]; then            
        FILM_TITLE=`echo ${FILM} | sed s'/_/ /g'`   
        
        # See if there is a cached imdbid file for accurate film summary retrieval
        if [ -e ${FILM_STORE}/All/${FILM}/.imdbid* ]; then
            FILM_ID=`ls -1 ${FILM_STORE}/All/${FILM}/.imdbid* | head -n1 | cut -f2 -d'.' | cut -f2 -d'_'`            
            php ~/Source/IMDB-to-MPEG/IMDB-to-MPEG.php -i "${FILM_ID}" -a -c ${FILM_CERTIFICATE_COUNTRY}
        else
            FILM_ID=""        
            php ~/Source/IMDB-to-MPEG/IMDB-to-MPEG.php -t "${FILM_TITLE}" -a -c ${FILM_CERTIFICATE_COUNTRY}
        fi
    fi
done
