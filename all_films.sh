#!/bin/bash

IFS=$'\n'

# Get a list of films
FILMS=`ls -1 ~/Videos/Films/All/`

# Change to the root of my movie store
cd ~/Videos/Films

# Delete the existing Generes and Rating. They might have changed.
rm -rf Ratings
rm -rf Genres

# Loop through the films and create the IMDB summary
for FILM in ${FILMS}
do
    FILM_TITLE=`echo ${FILM} | sed s'/_/ /g'`
    #echo ${FILM_TITLE}    
    php ~/Source/IMDB-to-MPEG/IMDB-to-MPEG.php -t "${FILM_TITLE}" -a
done

exit

#The old stuff
php IMDB-to-MPEG.php "Austin Powers International Man of Mystery"
php IMDB-to-MPEG.php "Austin Powers The Spy Who Shagged Me"
php IMDB-to-MPEG.php "Austin Powers in Goldmember"
php IMDB-to-MPEG.php "Behind Enemy Lines"
php IMDB-to-MPEG.php "Black Hawk Down"
php IMDB-to-MPEG.php "Charlies Angels Full Throttle"
php IMDB-to-MPEG.php "Doomsday"
php IMDB-to-MPEG.php "Ghost Rider"
php IMDB-to-MPEG.php "Hancock"
php IMDB-to-MPEG.php "Hellboy II The Golden Army"
php IMDB-to-MPEG.php "Hidalgo"
php IMDB-to-MPEG.php "Indiana Jones and the Kingdom of the Crystal Skull"
php IMDB-to-MPEG.php "Iron Man"
php IMDB-to-MPEG.php "Journey to the Center of the Earth"
php IMDB-to-MPEG.php "Jumper"
php IMDB-to-MPEG.php "Street Kings"
php IMDB-to-MPEG.php "The Dark Knight"
php IMDB-to-MPEG.php "The Day After Tomorrow"
php IMDB-to-MPEG.php "The Forbidden Kingdom"
php IMDB-to-MPEG.php "The Incredible Hulk"
php IMDB-to-MPEG.php "The Mummy Tomb of the Dragon Emperor"
php IMDB-to-MPEG.php "The Transporter"
php IMDB-to-MPEG.php "Transporter 2"
php IMDB-to-MPEG.php "10000 BC"
php IMDB-to-MPEG.php "Dogma"
php IMDB-to-MPEG.php "Harold and Kumar Escape from Guantanamo Bay"
php IMDB-to-MPEG.php "Nims Island"
php IMDB-to-MPEG.php "The Beach"
php IMDB-to-MPEG.php "The Bucket List"
php IMDB-to-MPEG.php "The Golden Compass"
php IMDB-to-MPEG.php "Film Noir"
php IMDB-to-MPEG.php "Ratatouille"
php IMDB-to-MPEG.php "Team America World Police"
php IMDB-to-MPEG.php "Ali"
php IMDB-to-MPEG.php "About Schmidt"
php IMDB-to-MPEG.php "Being John Malkovich"
php IMDB-to-MPEG.php "Burn After Reading"
php IMDB-to-MPEG.php "George Carlin Its Bad for Ya!"
php IMDB-to-MPEG.php "Just Married"
php IMDB-to-MPEG.php "Leatherheads"
php IMDB-to-MPEG.php "Michael McIntyre Live and Laughing"
php IMDB-to-MPEG.php "Strictly Ballroom"
php IMDB-to-MPEG.php "Deception"
php IMDB-to-MPEG.php "Firewall"
php IMDB-to-MPEG.php "Memento"
php IMDB-to-MPEG.php "Shooters"
php IMDB-to-MPEG.php "Sweeney Todd The Demon Barber of Fleet Street"
php IMDB-to-MPEG.php "The Bank Job"
php IMDB-to-MPEG.php "Untraceable"
php IMDB-to-MPEG.php "21"
php IMDB-to-MPEG.php "August Rush"
php IMDB-to-MPEG.php "Death Defying Acts"
php IMDB-to-MPEG.php "Pandora and the Flying Dutchman"
php IMDB-to-MPEG.php "The Duchess"
php IMDB-to-MPEG.php "Unfaithful"
php IMDB-to-MPEG.php "American Psycho"
php IMDB-to-MPEG.php "My Little Eye"
php IMDB-to-MPEG.php "Rabid"
php IMDB-to-MPEG.php "Vanilla Sky"
php IMDB-to-MPEG.php "Girl Interrupted"
php IMDB-to-MPEG.php "Mr and Mrs Smith"
php IMDB-to-MPEG.php "Wallace and Gromit A Grand Day Out"
php IMDB-to-MPEG.php "Wallace and Gromit A Matter of Load or Death"
php IMDB-to-MPEG.php "Wallace and Gromit The Curse Of The Rabbit"
php IMDB-to-MPEG.php "Windtalkers"
php IMDB-to-MPEG.php "WALL-E"
php IMDB-to-MPEG.php "The Other Boleyn Girl"
php IMDB-to-MPEG.php "Vantage Point"
php IMDB-to-MPEG.php "Chicago 10"
php IMDB-to-MPEG.php "What Lies Beneath"
php IMDB-to-MPEG.php "JCVD"
php IMDB-to-MPEG.php "One Fine Day"
php IMDB-to-MPEG.php "Batman"
php IMDB-to-MPEG.php "Batman Forever"
php IMDB-to-MPEG.php "Batman Returns"
php IMDB-to-MPEG.php "Batman and Robin"
php IMDB-to-MPEG.php "Gallipoli"
php IMDB-to-MPEG.php "Independence Day"
php IMDB-to-MPEG.php "xXx The Next Level"
php IMDB-to-MPEG.php "The Waiting Room" 0902348
php IMDB-to-MPEG.php "Herbie Fully Loaded"
php IMDB-to-MPEG.php "Hero"
php IMDB-to-MPEG.php "American Pie 2"
php IMDB-to-MPEG.php "American Wedding"
php IMDB-to-MPEG.php "Shrek The Halls"
php IMDB-to-MPEG.php "Shrek"
php IMDB-to-MPEG.php "Shrek 2"
php IMDB-to-MPEG.php "Blindness"
php IMDB-to-MPEG.php "Body of Lies"
php IMDB-to-MPEG.php "Brideshead Revisited" 0412536
php IMDB-to-MPEG.php "Hounddog"
php IMDB-to-MPEG.php "Outlander"
php IMDB-to-MPEG.php "Permanent Vacation" 0475431
php IMDB-to-MPEG.php "The Escapist"
php IMDB-to-MPEG.php "The X Files I Want To Believe"
php IMDB-to-MPEG.php "Zack and Miri Make A"
php IMDB-to-MPEG.php "City of Ember"
php IMDB-to-MPEG.php "Death Proof"
php IMDB-to-MPEG.php "Death Race"
php IMDB-to-MPEG.php "Napoleon Dynamite"
php IMDB-to-MPEG.php "Cliffhanger"
php IMDB-to-MPEG.php "Elephants Dream"
php IMDB-to-MPEG.php "The Mummy"
php IMDB-to-MPEG.php "The Mummy Returns"
php IMDB-to-MPEG.php "The Worlds Fastest Indian"
php IMDB-to-MPEG.php "Lara Croft Tomb Raider The Cradle of Life"
php IMDB-to-MPEG.php "You've Got Mail"
php IMDB-to-MPEG.php "Star Trek Nemesis" 0253754
php IMDB-to-MPEG.php "School of Rock"
php IMDB-to-MPEG.php "The Naked Gun 2"
php IMDB-to-MPEG.php "A Life Less Ordinary"
php IMDB-to-MPEG.php "A River Runs Through It"
php IMDB-to-MPEG.php "Arlington Road"
php IMDB-to-MPEG.php "Back to the Future Part II"
php IMDB-to-MPEG.php "Changeling"
php IMDB-to-MPEG.php "Crank"
php IMDB-to-MPEG.php "Seven Pounds"
php IMDB-to-MPEG.php "The Butterfly Effect"
php IMDB-to-MPEG.php "Transporter 3"
php IMDB-to-MPEG.php "Flash of Genius"
php IMDB-to-MPEG.php "God on Trial"
php IMDB-to-MPEG.php "Incendiary"
php IMDB-to-MPEG.php "Thick as Thieves"
php IMDB-to-MPEG.php "Twilight"
php IMDB-to-MPEG.php "Viva" 0393956
php IMDB-to-MPEG.php "Star Wars Holiday Special"
php IMDB-to-MPEG.php "Bear Island"
php IMDB-to-MPEG.php "Brokeback Mountain"
php IMDB-to-MPEG.php "Alive"
php IMDB-to-MPEG.php "Amélie"
php IMDB-to-MPEG.php "300"
php IMDB-to-MPEG.php "Australia"
php IMDB-to-MPEG.php "Elegy"
php IMDB-to-MPEG.php "Inkheart"
php IMDB-to-MPEG.php "Quantum Of Solace"
php IMDB-to-MPEG.php "The Spirit"
php IMDB-to-MPEG.php "Casino Royale"
php IMDB-to-MPEG.php "Five Minutes Of Heaven"
php IMDB-to-MPEG.php "Marley and Me"
php IMDB-to-MPEG.php "The Reader"
php IMDB-to-MPEG.php "Winged Creatures"
php IMDB-to-MPEG.php "Constantine"
php IMDB-to-MPEG.php "Frost/Nixon"
php IMDB-to-MPEG.php "Outlander"
php IMDB-to-MPEG.php "Yes Man"
php IMDB-to-MPEG.php "A Beautiful Mind"
php IMDB-to-MPEG.php "Airplane"
php IMDB-to-MPEG.php "Airplane II The Sequel"
php IMDB-to-MPEG.php "Alive"
php IMDB-to-MPEG.php "A walk in the clouds"
php IMDB-to-MPEG.php "Addams Family Values"
php IMDB-to-MPEG.php "A Knights Tale"
php IMDB-to-MPEG.php "An Ideal Husband"
php IMDB-to-MPEG.php "Asylum" 0348505

# Not yet done
php IMDB-to-MPEG.php "American Pie"



# BMW Shorts - Season 1
#php IMDB-to-MPEG.php "Ambush" 0283875
#php IMDB-to-MPEG.php "Chosen" 0283994
#php IMDB-to-MPEG.php "The Follow" 0283994
#php IMDB-to-MPEG.php "Star" 0286151
#php IMDB-to-MPEG.php "Powder Keg"
# BMW Shorts - Season 2
#php IMDB-to-MPEG.php "Hostage" 0338111
#php IMDB-to-MPEG.php "Ticker" 0340398

