#!/bin/bash
# Package for release

VER=1.0

bzr export IMDB-to-MPEG-v${VER}.tar
tar --delete -f IMDB-to-MPEG-v${VER}.tar IMDB-to-MPEG-v${VER}/all_films.sh
tar --delete -f IMDB-to-MPEG-v${VER}.tar IMDB-to-MPEG-v${VER}/release.sh
gzip IMDB-to-MPEG-v${VER}.tar
