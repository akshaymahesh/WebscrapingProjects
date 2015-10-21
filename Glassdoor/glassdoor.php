<?php

/*
Basic company info (id, name, overallRating) + featured review @ ANGELLIST
send flag 'company_id' and 'debug'

known bugs: UPDATE in sql still doesn't work

*/

class Glassdoor
{
    /*** GET FLAGS FROM URL ***/
    function getFlags()
    {         
        if (isset($_GET['company_id']))
        {
            $company_id = ($_GET['company_id']);
            if (isset($_GET['debug']))
            {
                $debug = ($_GET['debug']);
            }
            else { $debug = false; }
                          
            $this->populateTables($company_id, $debug);
        }
        else { echo "<br> (Company id invalid)"; }
    }

    function populateTables($company_id, $debug)
    {
        /*** Glassdoor API AUTHENTICATION ***/
        $t_p = "38783";
        $t_k = "f3Vsruc8NPk";

        $url = "http://api.glassdoor.com/api/api.htm?v=1&format=json&t.p=$t_p&t.k=$t_k&action=employers&q=$company_id&userip=192.168.43.52&useragent=Mozilla/%2F4.0";

        $json = $this->curl_get_contents($url);
        $data = json_decode($json, true);
        if ($data['success'] == '1')
        {
            $glassdoor_id = $data['response']['employers']['0']['id'];
            $glassdoor_name = $data['response']['employers']['0']['name'];
            $glassdoor_rating = floatval($data['response']['employers']['0']['overallRating']);
            $glassdoor_featured_review = mysql_escape_string(serialize($data['response']['employers']['0']['featuredReview']));
            
                $overview_url = $this->findOverviewPage(($data['response']['attributionURL']));
                
            $glassdoor_url = mysql_escape_string($overview_url);
            $glassdoor_numOfJobs = $this->findNumOfJobs($overview_url);
       
            $sql = "INSERT INTO gen_info (company_id, company_name, company_rating, featured_review, overview_page, num_of_jobs) VALUES ('$glassdoor_id', '$glassdoor_name', '$glassdoor_rating', '$glassdoor_featured_review', '$glassdoor_url', '$glassdoor_numOfJobs') ON DUPLICATE KEY UPDATE company_rating='$glassdoor_rating', num_of_jobs='$glassdoor_numOfJobs'";
            
            if ($this->connectToDB($sql) == true)
            {
                echo "<br><b> Successful input for startup \"$glassdoor_name\" (ID $glassdoor_id). </b> 
                <br> Glassdoor Rating: $glassdoor_rating. 
                <br> Glassdoor Overview URL: </b> $overview_url.
                <br> Glassdoor Number of Jobs: </b> $glassdoor_numOfJobs. ";

                $this->debug($debug, $glassdoor_featured_review);
            }
            else 
            {
                echo "<br><b>DB ERROR</b>";
            }

        }
    }
    
    function connectToDB($sql)
    {
        /*** MYSQL AUTHENTICATION ***/
        $servername = "localhost";
        $username = "akshay";
        $password = "akshay";
        $db = "glassdoor";

        /*** CONNECT TO MYSQL DB ***/
        $conn = mysqli_connect($servername, $username, $password, $db);
        if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }


        /*** RETURN RESULTS OF INSERTION ***/
        if (mysqli_query($conn, $sql)) {
            return true;
        } else { echo "Error: " . $sql . "<br>" . mysqli_error($conn); }
        mysqli_close($conn);
   
    }
    
    function findOverviewPage($attributionURL)
    {
        // $source = $this->curl_get_contents($attributionURL);
        $source = $this->curl_get_contents("http://localhost/scraping_test/source.html"); // for testing when glassdoor security pops up
        $before = "<div class='logo cell'> <a href='";
        $after = "' data-ajax='true' class='sqLogoLink'>";
        $overview_url = "http://www.glassdoor.com" . $this->scrape_between($source, $before, $after);
        return $overview_url;
    }
    
    function findNumOfJobs($glassdoor_url)
    {   
        // $source = $this->curl_get_contents($glassdoor_url);
        $source = $this->curl_get_contents("http://localhost/scraping_test/source_2.html");
        $before = "<span class='num h2 notranslate'> ";
        $after = "</span> <span class='subtle'> Jobs</span>";
        $numofJobs = $this->scrape_between($source, $before, $after);
        $numofJobs_sep = explode("<span class='num h2 notranslate'> ", $numofJobs);
        return $numofJobs_sep[3];
    }
    
    function scrape_between($data, $start, $end)
    {
        $data = stristr($data, $start); // Stripping all data from before $start
        $data = substr($data, strlen($start));  // Stripping $start
        $stop = stripos($data, $end);   // Getting the position of the $end of the data to scrape
        $data = substr($data, 0, $stop);    // Stripping all data from after and including the $end of the data to scrape
        return $data;   // Returning the scraped data from the function
    }
    
    /*** Debug dbString ***/
    function debug($debug, $glassdoor_featured_review)
    {
        if (($debug == "true") || ($debug == "TRUE"))
        {
                echo "<br><b><br> (DEBUG) FEATURED REVIEW ADDED: </b><pre>";
                $text = unserialize(stripslashes($glassdoor_featured_review));
                print_r(($text));
                echo "</pre>";
        }
    }
    
    function curl_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    
}

$Glassdoor = new Glassdoor();
$Glassdoor->getFlags();
?>
