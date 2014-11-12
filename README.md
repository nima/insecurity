jestercuriosity
===============

A repository of utilities, nicely bundled into a toolbox for the general
infosec jesting purposes

# Current Caveats

## Installation
* Edit insecurity.conf, and set the TARBALLZ and DATAZ
* TARBALLZ must point to a directory with contents from this link:

    https://www.dropbox.com/sh/oiw5ywupnykckol/AABq5f0KsfmN1VHTsg9nwy_ba?dl=0

* DATAZ - TODO

## LibDASM
make -f make.d/Makefile.libdasm build
make -f make.d/Makefile.libdasm install

## Hardcoded Installation Target
The `/opt/autonomy/insecurity` hardcoded target has yet to be softened up.

## Trouble-shooting Builds
Look at the build target in `make.d/Makefile.footer`, and echo out
interesting info, make's debug flag is mostly a big jest.
