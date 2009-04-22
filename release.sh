#!/bin/bash

NAME="IMDB-to-MPEG"
VER=1.0

bzr export ${NAME}-v${VER}.tar
tar --delete -f ${NAME}-v${VER}.tar ${NAME}-v${VER}/all_films.sh
tar --delete -f ${NAME}-v${VER}.tar ${NAME}-v${VER}/release.sh
gzip ${NAME}-v${VER}.tar
