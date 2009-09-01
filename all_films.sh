#!/bin/bash

IFS=$'\n'

FILM_STORE="${HOME}/Videos/Films"

# Get a list of All films
FILMS=`ls -1 ${FILM_STORE}/All/`

# Change to the root of my movie store
cd ${FILM_STORE}

# Delete the existing Generes and Rating. They might have changed.
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
        
        # See if the is a cache imdbid for accurate film summary retrieval
        if [ -e ${FILM_STORE}/All/${FILM}/.imdbid* ]; then
            FILM_ID=`ls -1 ${FILM_STORE}/All/${FILM}/.imdbid* | head -n1 | cut -f2 -d'.' | cut -f2 -d'_'`            
            #echo ${FILM_TITLE} : ${FILM_ID}                    
            php ~/Source/IMDB-to-MPEG/IMDB-to-MPEG.php -i "${FILM_ID}" -a -c UK
        else
            FILM_ID=""        
            #echo ${FILM_TITLE}
            php ~/Source/IMDB-to-MPEG/IMDB-to-MPEG.php -t "${FILM_TITLE}" -a -c UK           
        fi
    fi

done

exit

#The old stuff
php IMDB-to-MPEG.php "The Waiting Room" 0902348
php IMDB-to-MPEG.php "Brideshead Revisited" 0412536
php IMDB-to-MPEG.php "Permanent Vacation" 0475431
php IMDB-to-MPEG.php "Star Trek Nemesis" 0253754
php IMDB-to-MPEG.php "Viva" 0393956
php IMDB-to-MPEG.php "Asylum" 0348505

# BMW Shorts - Season 1
#php IMDB-to-MPEG.php "Ambush" 0283875
#php IMDB-to-MPEG.php "Chosen" 0283994
#php IMDB-to-MPEG.php "The Follow" 0283994
#php IMDB-to-MPEG.php "Star" 0286151
#php IMDB-to-MPEG.php "Powder Keg"
# BMW Shorts - Season 2
#php IMDB-to-MPEG.php "Hostage" 0338111
#php IMDB-to-MPEG.php "Ticker" 0340398
