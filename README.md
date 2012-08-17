WLM-ISO-3166-2
==============

This repo contains ISO-3166-2 translations and translation scripts for the
Wiki Loves Monuments (WLM) API. This is a project specifically to provide
translations to ISO-3166-2 data for the WLM mobile app (wikimedia/WLMMobile)

How it works
------------
ISO-3166-2 data is taken from a CSV dump from commondatahub.com. The csv
gets processed into a file containing a PHP array in this format:

``` php
$subdivisions[<country iso code>][<subdivision iso code>] = array(
'name' => <subdivision name>,
'level' => <subdivision level>
);
```

The CSV is stored in this repo and the file can be generated with
parseIso3166-2Dump.php. This file can optionally check subdivision
names against English Wikipedia article titles, using redirects
from the ISO-3166-2 subdivision name in the CSV to the 'standard'
Wikipedia article title to choose what name to use for the
subdivision.

The iso3166-2-l10n.php script will take the mappings generated
above, and foreach subdivision name, attempt to find a translation
using English Wikipedia's interwiki language links. That is, it will
query Wiipedia's API for a title (mapped to a subdivision name) and
look for article titles mapped in other languages, and use those
titles as subdivision names for that language. It will generate
language-specific files containing the same PHP array as above, with
the subdivision name localized.

Caveats
-------
This is far from perfect. At the moment, it only attempts to perform
translations for countries/languages that are being used by the WLM
app. Also, as you can imagine, there are many potential problems with
relying on Wikipedia page titles for translatons. But it's a cheap,
quick, and dirty way to get translated ISO-3166-2 data in a free,
open, and mashed up manner!
