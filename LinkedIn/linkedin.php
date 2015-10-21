<?php

/*
SCRAPING @ Linkedin
send flag 'company_name' 

NOTE: company name is the http://www.linkedin.com/company/________ in the URL

known bugs: not sure about ID accuracy (not sure if the number I scrape actually respresents linkedin ID)
*/

require 'pgbrowser.php';

class Linkedin
{
    /*** GET FLAGS FROM URL ***/
    function getFlags()
    { 
        $company_name = $_GET['company_name']; // for test, use 147930
        $this->loginToLinkedin($company_name);
    }

    function loginToLinkedin($company_name)
    {
        /*** LINKEDIN AUTHENTICATION ***/
        $username = “"; // left blank for privacy
        $password = "";
        
        /*** Use PGBrowser to Log in from homepage ***/
        $b = new PGBrowser();
        $page = $b->get('https://www.linkedin.com');
        $form = $page->form();
        $form->set('session_key', "$username");
        $form->set('session_password', "$password");
        $page = $form->submit();
        
        /*** Check if Login was successful, then scrape numOfEmployees ***/
        if (strpos($page->title, 'Welcome!') !== FALSE)
        {
            $url = "https://www.linkedin.com/company/" . $company_name;
            $page = $b->get("$url");
            $source = $page->html;
            
            /*** Get numOfEmployees ***/
            $before = '<li><a class="density"';
            $after = '</a> <span>Employees on LinkedIn</span></li>';
            $numOfEmployees = $this->scrape_between($source, $before, $after);
            $numOfEmployees = substr(strstr($numOfEmployees, '>'), 1);
            $numOfEmployees = str_replace(',', '' , $numOfEmployees); 
            
            /*** Get Linkedin Company ID (from follow button) ***/
            $before = '<a class="more"';
            $after = 'See all</a>';
            $linkedin_id = $this->scrape_between($source, $before, $after);
            $linkedin_id = strstr($linkedin_id, '?');
            $linkedin_id = $this->scrape_between($linkedin_id, '=', '&'); 
            
            /*** Get Linkedin Company Name ***/
            $title = $page->title;
            $company_name = strstr($title, ':', true);
            
        }
        else { echo "LOGIN ERROR"; }
        
        /*** PREPARE DB FIELDS ***/
        $sql = "INSERT INTO gen_info (company_id, company_name, num_of_employees) VALUES ('$linkedin_id', '$company_name', '$numOfEmployees') ON DUPLICATE KEY UPDATE num_of_employees='$numOfEmployees'";
        
        if (($this->connectToDB($sql)) == true)
        {
            echo "<br><b> Successful input for startup \"$company_name\". $numOfEmployees employees found to be connected.";
        }
        else { echo "<br> DB ERROR"; }
  
    }
    
    function connectToDB($sql)
    {
        /*** MYSQL AUTHENTICATION ***/
        $servername = "localhost";
        $username = "akshay";
        $password = "akshay";
        $db = "linkedin";

        /*** CONNECT TO MYSQL DB ***/
        $conn = mysqli_connect($servername, $username, $password, $db);
        if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }


        /*** RETURN RESULTS OF INSERTION ***/
        if (mysqli_query($conn, $sql)) {
            return true;
        } else { echo "Error: " . $sql . "<br>" . mysqli_error($conn); }
        mysqli_close($conn); 
    }
    
    function scrape_between($data, $start, $end)
    {
        $data = stristr($data, $start);
        $data = substr($data, strlen($start));
        $stop = stripos($data, $end); 
        $data = substr($data, 0, $stop); 
        return $data; 
    }
}

$Linkedin = new Linkedin();
$Linkedin->getFlags();
?>