<?php

/*
GENERAL INFO @ ANGELIST
send flag 'startup_id'

populates company_url, angellist_url, market tags in db
*/

class Angellist
{
    /*** GET FLAGS FROM URL ***/
    function getFlags()
    { 
        $startup_id = $_GET['startup_id']; // for test, use 147930
        
        $this->PopulateTables($startup_id, $debug);
    }

    function populateTables($startup_id, $debug)
    {
        /*** ANGELIST API AUTHENTICATION ***/
        $client_id = "0bdabb752fa4e07f61a59c797aa3f05533be9cc98164cc44";
        $access_token = "1dd6555f46c5771b9cfcde241aae180c2d188f1bf2c0dab2";

        $url = "https://api.angel.co/1/startups/$startup_id?&client_id=$client_id&access_token=$access_token";

        $json = file_get_contents($url);
        $data = json_decode($json, true);
            $angellist_url = $data['angellist_url'];
            $company_url = $data['company_url'];
            $numofMarkets = count($data['markets']);
                for ($x = 0; $x < $numofMarkets; $x++) 
                {
                    $market = $data['markets'][$x]['id'];
                    $markets_array[$x] = $market;
                }

            /*** PREPARE DB FIELDS ***/
            $markets_array = array_filter($markets_array);
            $dbString = serialize($markets_array);
            $startup_name = $data['name'];
       
            $sql = "INSERT INTO gen_info (startup_id, startup_name, angellist_url, company_url, markets) VALUES('$startup_id',                     '$startup_name', '$angellist_url', '$company_url', '$dbString') ON DUPLICATE KEY UPDATE angellist_url='$angellist_url',               company_url ='$company_url', markets='$dbString'";
            
            if ($this->connectToDB($sql) == true)
            {
                echo "<br><b> Successful input for startup \"$startup_name\" (ID " . $startup_id . "). </b><br> Angellist URL:                              $angellist_url <br> Company URL: $company_url <br> Market ID's <pre>"; print_r(unserialize($dbString)); echo "                        </pre>";
            }
            else 
            {
                echo "DB ERROR";
            }
    }

    
    function connectToDB($sql)
    {
        /*** MYSQL AUTHENTICATION ***/
        $servername = "localhost";
        $username = "akshay";
        $password = "akshay";
        $db = "angelist";

        /*** CONNECT TO MYSQL DB ***/
        $conn = mysqli_connect($servername, $username, $password, $db);
        if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }


        /*** RETURN RESULTS OF INSERTION ***/
        if (mysqli_query($conn, $sql)) {
            return true;
        } else { echo "Error: " . $sql . "<br>" . mysqli_error($conn); }
        mysqli_close($conn);

        
    }
}

$Angellist = new Angellist();
$Angellist->getFlags();
?>