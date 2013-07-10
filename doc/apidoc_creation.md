# Create API documentation

## Prerequisites

You need phpDocumentor 2 installed on your system to create the api
documentation. Please visit [phpdoc's website](http://phpdoc.org/) for more
information. Additionally, the graphviz package is required to be installed. 

## Configuration

phpDocumentator is configured with xml configuration reside in doc/phpdoc.xml.
In there you'll find the target path where the documentation is created as
html. Default location is doc/api/. Just point to index.html in this directory
with a browser.

If you generated the documentation already, you can follow [this link](apidoc/idnex.html).

## Generation

Change to Icinga 2 Web root directory (source tree) and run:

    bin/createapidoc.sh

## Options for createapidoc.sh

    --build    Optional, silent build mode
    --help     Displays help message
