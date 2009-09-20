#!/bin/bash

function build {
    RELEASE_NAME="IMDB-to-MPEG"
    RELEASE_VER="2.0"
    RELEASE_DESC="Create a MPEG video summarising a movie using data from IMDB"
    RELEASE_KEYWORDS="IMDB, MPEG, video, summary, MP4, MPG, Mediatomb, tag, categorisation, ffmpeg"

    rm ${RELEASE_NAME}-v${RELEASE_VER}.tar* 2>/dev/null
    bzr export ${RELEASE_NAME}-v${RELEASE_VER}.tar
    tar --delete -f ${RELEASE_NAME}-v${RELEASE_VER}.tar ${RELEASE_NAME}-v${RELEASE_VER}/build.sh
    gzip ${RELEASE_NAME}-v${RELEASE_VER}.tar
}
