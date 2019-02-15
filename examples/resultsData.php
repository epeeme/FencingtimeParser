<?php

// This example shows the output from getAllResults()

namespace fencingtime;

header('Content-Type: text/html; charset=utf-8');

require '../fencingtimeParser.php';

$fencingtimeResultsURL = "http://www.fencingscore.pl/tournaments/2018wratislavia/FTEvent327684.htm";

$e = new FencingtimeParser($fencingtimeResultsURL);

$allResults = $e->getAllResults();

// HTML TABLE used for simple display purposes only
echo "<TABLE BORDER=1 CELLPADDING=3 CELLSPACING=2>\n";
echo "<TR ALIGN=LEFT><TH>Rank</TH><TH>Surname</TH><TH>Forename</TH><TH>Club</TH><TH>Country</TH></TR>\n";

foreach ($allResults as $value) {
    echo "<TR><TD>".$value[0]."</TD><TD>".$value[1]."</TD><TD>".$value[2]."</TD><TD>".$value[3]."</TD><TD>".$value[4]."</TD></TR>\n";
}
echo "</TABLE>\n";

echo "<p>Extracted from <a href=\"".$fencingtimeResultsURL."\">".$fencingtimeResultsURL."</a></p>";