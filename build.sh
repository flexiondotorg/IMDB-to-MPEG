#!/bin/bash

function build {
    RELEASE_NAME="IMDB-to-MPEG"
    RELEASE_VER=1.1

    rm ${RELEASE_NAME}-v${RELEASE_VER}.tar* 2>/dev/null
    bzr export ${RELEASE_NAME}-v${RELEASE_VER}.tar
    tar --delete -f ${RELEASE_NAME}-v${RELEASE_VER}.tar ${RELEASE_NAME}-v${RELEASE_VER}/all_films.sh
    tar --delete -f ${RELEASE_NAME}-v${RELEASE_VER}.tar ${RELEASE_NAME}-v${RELEASE_VER}/build.sh
    gzip ${RELEASE_NAME}-v${RELEASE_VER}.tar
}
