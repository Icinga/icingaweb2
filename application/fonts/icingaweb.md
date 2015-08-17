# fontello-ifont font files moved

New target is: public/font

The font directory has been moved to the public structure because of
Internet Explorer version 8 compatibility. The common way for browsers is to
include the binary embeded font type in the javascript. IE8 falls back and
include one of the provided font sources. Therefore it is important to have
the font files available public and exported by the HTTP server.
