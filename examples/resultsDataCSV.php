<?php

// This example will trigger an automatic download of a CSV formatted results file

namespace fencingtime;

require '../fencingtimeParser.php';

$fencingtimeResultsURL = "https://epee.me/liveResults/2018CadetBRCCadet/FTEvent1.htm";

$e = new FencingtimeFormatter($fencingtimeResultsURL);

$e->getAllResultsCSV();