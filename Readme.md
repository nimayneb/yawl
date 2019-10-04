YAWL - Yet another wildcard library
===================================

This is a library with classes for any wildcard implementation that finds pattern with * (asterisk) and ? (query) token.


Problem:
--------

Without regular expression extension (named `ext-pcre`) you have no wildcard support within PHP.
These simple patterns don't need complex calculating. So we need a tiny helper for this approach.

But the known wildcard behavior (see [Wildcard character (@Wikipedia)](https://en.wikipedia.org/wiki/Wildcard_character))
is a bit limited for a good implementation.


Known wildcard logic:
---------------------

    zero or many characters =   *   (0-x characters)
    one character           =   ?   (1 character)


Extended wildcard logic (missing logic):
----------------------------------------

    many of characters      =   **  (1-x characters)
    zero or one character   =   ?*  (0-1 characters)


Possible valid pattern:
-----------------------

        ??      (2 characters)
        ???*  (2-3 characters)


Invalid pattern:
----------------

        ***
        ?**
        ?*?
        *?


Escaping:
---------

        \?
        \*


Notice:
-------

Invalid wildcard pattern can not be fully recognized if a partial pattern has not already been found:

    "search phrase" => "sea??ch*****"
                             ^   ^
           pattern not found |   | invalid pattern (not recognized)


Caching:
--------

The regular expression extension has a caching strategy to improve performance. The second time the same pattern is
called, a tremendous increase in performance is achieved:

