fencingtimeParser
==========

fencingtimeParser extracts the results data from every conceivable version of 
the fencingtime software HTML output and parses into a variety of formats.

It can also extract from the fencingtimelive system using the PhantomJScloud
to produce the results that are no longer embedded in the source.

Usage
-----

``` php
<?php

namespace fencingtime;
require '../fencingtimeParser.php';

$e = new FencingtimeParser($fencingtimeResultsURL);
```

* `getfencingtimePage()`: returns the raw HTML from the fencingtimeResultsURL
* `getAllResults()`: returns a parsed array containing all the results

``` php
array('Rank', 'Surname', 'Forename', 'Club', 'Country')
```

The class can also be extended to allow for the results to be returned
in a variety of formats.

``` php
<?php

namespace fencingtime;
require '../fencingtimeParser.php';

$e = new fencingtimeFormatter($fencingtimeResultsURL);
```

* `getAllResultsCSV()`: returns a CSV formatted file of all the results
* `getAllResultsJSON()`: returns a JSON formatted representation of all the results

