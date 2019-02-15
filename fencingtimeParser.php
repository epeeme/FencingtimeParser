<?php

/**
 * Fencingtime Parser
 * 
 * Extract the results data from every conceivable version of Fencingtime and outputs in a 
 * variety of formats.
 * 
 * @author  Dan Kew <dan@epee.me>
 * @license http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version v1.0.0
 */

namespace fencingtime;

class FencingtimeParser
{
    // The URL of the fencingtime results page
    private $_urlInFull;

    // Fencingtimelive check as different processes needed for that
    private $_isFencingtimeLive = 0;

    // The raw HTML of the fencingtime results page
    private $_dataBody;

    // The header info for the results table
    private $_resultsHeader;

    // The raw HTML of just the results table
    private $_resultsBody;

    // Indexes for the results table
    private $_rankingIndex;
    private $_fullnameIndex;
    private $_clubIndex;
    private $_countryIndex;

    // All the values used to identify the text in the engarde table header
    private $_rankArray = array("Rank", "Ranking", "Rnk", "Rg", "Cl.", "Place");
    private $_fullnameArray = array("Name", "Surname", "Nom", "Apellido-nom");
    private $_clubArray = array("Club", "Egyesület", "Club(s)");
    private $_countryArray = array("Country");

    private $_olderVersion = 0;
    /**
     * Initialize the class and set its properties.
     * 
     * @param string $inUrl The URL of the engarde results
     * 
     * @since 1.0.0
     */
    public function __construct($inUrl) 
    {        
        // So there is no need to pass the URL around, we fix it here
        $this->_urlInFull = $inUrl;

        // Is is the new fencingtimelive result set?
        $this->_isLive();

        // Fetch the page data ready for manipulation
        $this->getFencingtimePage();
    }

    /**
     * Checks for a URL that contains fencingtimelive - this is the
     * new system that uses dynamic page generation and needs to be 
     * handled differently to the old single page of css formatted
     * results
     *
     * @since 1.0.0
     * 
     * @return none / updates the $_isFencingtimeLive variable accordingly
     */
    private function _isLive()
    {
        if (strpos($this->_urlInFull, 'fencingtimelive') !== false) {
            $this->_isFencingtimeLive = 1;
        }
   }

    /**
     * Checks that the supplied URL exists and is accessible
     *
     * @since 1.0.0
     * 
     * @return boolean $exists
     */
    private function _URLexists()
    {
        $urlHeaders = get_headers($this->_urlInFull);
        if (!$urlHeaders || $urlHeaders[0] == 'HTTP/1.1 404 Not Found') {
            $exists = false;
        } else {
            $exists = true;
        }      
        return $exists;
    }

    /**
     * Ready the HTML table of results for parsing and load into _resultsBody
     *
     * @since 1.0.0
     * 
     * @return none
     */
    private function _prepareResultsData()
    {
        $this->_resultsBody = $this->_dataBody;

        // To accomodate hostoric data, we need to take into account
        // data being extracted from waybackmachine
        if (strpos($this->_dataBody, '<!-- END WAYBACK TOOLBAR INSERT -->') !== false) {
            $this->_resultsBody = stristr($this->_dataBody, '<!-- END WAYBACK TOOLBAR INSERT -->');
        }

        // Now there should be just the one <table tag which signifies
        // the start of the results data - but this can vary depending on
        // fencingtime version
        if ($this->_isFencingtimeLive === 1) {
            $parseString = '<table id="resultList"'; 
        } elseif (strpos($this->_resultsBody, 'id="finalResults">') !== false) { 
            // Tabbed CSS version
            $parseString = 'id="finalResults">'; 
        } elseif (strpos($this->_resultsBody, '<table class="dataTable"') !== false) { 
            // Single page coloured version with no tabs
            $parseString = '<table class="dataTable"'; 
        } elseif (strpos($this->_resultsBody, '<table class="reporttable"') !== false) { 
            // White background w/ green header version
            $parseString = '<table class="reporttable"'; 
        } 
       
        $this->_resultsBody = stristr(stristr($this->_resultsBody, $parseString), '</table>', true);
    }

    /**
     * Extract out the the header information for the results table. These can
     * vary depending on what the competition organiser has chosen to include.
     * Ordinarily it'll be something like;
     * 
     * Rank | Name | Club | Country
     * 
     * Column headings should be in the first row, but if some spurious results 
     * start to appear then this will need to be looked at.
     *
     * @since 1.0.0
     * 
     * @return none
     */
    private function _prepareResultsTableHeader()
    {
        if (!(isset($this->_resultsBody))) {
            $this->_prepareResultsData();
        }

        // Use regx as explode does not handle case sensitivity
        $tableRows = preg_split("/<tr/i", $this->_resultsBody);

        $this->_resultsHeader = $this->_myArrayFilter($tableRows[1]);
    }

    /**
     * Scans the returned HTML results table header and returns the correct 
     * position of the passed column label
     *
     * @param array $colArray predefined array of known column text labels
     * 
     * @return int index into the table header for the desired column
     */
    private function _setIndexColumn($colArray)
    {
        if (!(isset($this->_resultsHeader))) {
            $this->_prepareResultsTableHeader();
        }

        $i=0;        
        while ($i<count($this->_resultsHeader)) {
            if (array_search($this->_resultsHeader[$i], $colArray) !== false) {
                break;
            }
            $i++;
        }

        return $i<count($this->_resultsHeader) ? $i : -1;
    }

    /**
     * Loads all the column indexes from the HTML results table header so
     * we can be sure we are grabbing the right data from the right columns
     *
     * @return none
     */
    private function _prepareResultsTableHeaderIndexes()
    {
        $this->_rankingIndex = $this->_setIndexColumn($this->_rankArray);
        $this->_fullnameIndex = $this->_setIndexColumn($this->_fullnameArray);
        $this->_clubIndex = $this->_setIndexColumn($this->_clubArray);
        $this->_countryIndex = $this->_setIndexColumn($this->_countryArray);
    }

    /**
     * Extracts all the results from the HTML results table, cleans and loads 
     * them into an array ready for manipulation
     *
     * @return array
     */
    public function getAllResults()
    {
        $allResults = array();

        if (!(isset($this->_resultsBody))) {
            $this->_prepareResultsData();
        }
        
        $this->_prepareResultsTableHeaderIndexes();

        $tableRows = array_slice(preg_split("/<tr/i", $this->_resultsBody), 2);

        for ($i=0; $i < count($tableRows); $i++ ) {
            $tempArray = $this->_myArrayFilter($tableRows[$i]);
          
            $rank = $this->_rankingIndex <> -1 ? $tempArray[$this->_rankingIndex] : '';
            $newNames = $this->_normalizeName($this->_fullnameIndex <> -1 ? $tempArray[$this->_fullnameIndex] : '');
            $club = $this->_clubIndex <> -1 ? $tempArray[$this->_clubIndex] : '';
            $country = $this->_countryIndex <> -1 ? $tempArray[$this->_countryIndex] : '';
          
            array_push($allResults, array($rank, $newNames[0], $newNames[1], $club, $country));
        }
        
        return $allResults;
    }

    /**
     * Name format is commonly (UPERCASESURNAME Forename) or 
     * (UPERCASESURNAME, Forename) other random words can also appear 
     * in the text when competition organisers over-ride the standard 
     * name protocol.  However, older versions of fencingtime do not 
     * follow this approach.
     *      
     * @param string $inName raw fencer name
     *
     * @return array parsed forename & surname strings
     */
    private function _normalizeName($inName)
    {
        $forename = $surname = '';

        $removeThisText = array("(None)", "(V)", "(C)", "(J)");
             
        // Remove anything that's not needed

        $inNameParse = preg_replace('/[\s]+/mu', ' ', str_ireplace($removeThisText, '', $inName));
        if (strpos($inNameParse, ',') !== false) {
            $inNameParse = explode(",", $inNameParse);
            $surname = $inNameParse[0];
            $forename = $inNameParse[1];
        } else {         
            $inNameParse = explode(" ", $inNameParse);
            for ($i=0; $i < count($inNameParse); $i++) {
                mb_strtoupper($inNameParse[$i], 'utf-8') == $inNameParse[$i] ? $surname .= " ".$inNameParse[$i] :  $forename .=  " ".$inNameParse[$i];
            }
        }    
        return array(trim($surname), trim($forename));
    }

    /**
     * Gets the HTML from the source URL and loads it in its raw form 
     * into _dataBody
     * 
     * Can be called directly
     * 
     * @return string $this->_dataBody
     */
    public function getFencingtimePage()
    {
        if ($this->_URLexists($this->_urlInFull)) {

            // This portion of code came from
            // https://guymclean.co.uk/web-scraping-after-javascript-finished/
            // and is worth its weight in gold! 

            // Scrape data from fencingtime after Javascript/AJAX requests have run.

            $requestContent = json_encode(array( "url" => $this->_urlInFull, "renderType" => "html"));

            // Can only make 100 requests a day with the demo key
             
            $url = 'http://PhantomJScloud.com/api/browser/v2/a-demo-key-with-low-quota-per-ip-address/';
             
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => $requestContent
                )
            );
             
            $context  = stream_context_create($options);
            
            if ($this->_isFencingtimeLive == 1) {
                $this->_dataBody = file_get_contents($url, false, $context);
            } else {
                $this->_dataBody = file_get_contents($this->_urlInFull);
            }
            
            // here ends the great portion of code

            if ($this->_dataBody === false) {
                $this->_handleError('Unable to read data from supplied URL.');
            }
        } else {
            $this->_handleError('Unable to locate supplied URL');
        }

        return $this->_dataBody;
    } 

    /**
     * Simple error handler to display message when something doesn't
     * go to plan and stop executing the script!
     * 
     * @param string $inError message indicating the error
     * 
     * @return none
     */
    private function _handleError($inError)
    {
        die($inError);
    }

    /**
     * Handles the parsing of the results table HTML
     * 
     * @param string $inString chunkc of HTML to parse containing the result
     * 
     * @return array
     */
    private function _myArrayFilter($inString)
    {   
        // remove any hard coded NBSP
        $inString = str_replace("&nbsp;", "", $inString);
        
        // and prevent empty TD cells from affecting the array index        
        $inString = preg_replace('~\></td>~', '>-</td>', $inString);
        
        // and now remove any spurious unicode hardspaces that ft includes
        $inString = str_replace(chr(194).chr(160), '', $inString);

        return array_slice(array_values(array_filter(explode("|", strip_tags(str_replace(">", ">|", $inString))), 'trim')), 1);
    }

}

class FencingtimeFormatter extends FencingtimeParser
{
    public function getAllResultsCSV()
    {
        $allResultsArray = $this->getAllResults();

        // Handles names and clubs with accents correctly
        header('Content-Type: text/html; charset=utf-8');

        // If UTF8 encoding being used, you may have to manually select
        // that when opening the CSV file in your CSV reader
        header("Content-Disposition: attachment; filename=fencingtime.csv");
        header("Content-Type: application/vnd.ms-excel");
            
        $fp = fopen('php://output', 'w');
        fputcsv($fp, array('Rank', 'Surname', 'Forename', 'Club', 'Country'));
        foreach ($allResultsArray as $value) {
            fputcsv($fp, $value);
        }
        fclose($fp);
    }

    public function getAllResultsJSON()
    {
        $allResultsArray = $this->getAllResults();

        // Handles names and clubs with accents correctly
        header('Content-Type: text/html; charset=utf-8');
        
        header('Content-Type: application/json');
        
        echo json_encode($allResultsArray);
    }
}