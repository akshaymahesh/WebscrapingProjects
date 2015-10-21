<?php

/*
ROLES @ ANGELLIST
send flag 'startup_id' and 'role' in url

known bugs: * SQL update doesn't seem to work properly.
            * array_filter on line 71 doesn't seem to apply at all (no impact on operation)
*/

class Angellist
{
    /*** GET FLAGS FROM URL ***/
    function getFlags()
    { 
        $startup_id = $_GET['startup_id']; // for test, use 147930
        $role = $_GET['role']; // for test, use past_investor
        $debug = $_GET['debug'];
        
        $this->startPopulateTables($startup_id, $role, $debug);
    }

    /*** ROLE INPUT VALIDATION ***/
    function startPopulateTables($startup_id, $role, $debug)
    {
        if (($role == "past_investor") || ($role == "employee") || 
            ($role == "incubator") || ($role == "founder") || 
            ($role == "customer")) // supported & tested roles so far
        {
            $this->populateTables($role, $startup_id, $debug);
        }

        elseif ($role == "all")
        {
            $this->populateTables("employee", $startup_id, $debug);
            $this->populateTables("incubator", $startup_id, $debug);
            $this->populateTables("founder", $startup_id, $debug);
            $this->populateTables("customer", $startup_id, $debug);
            $this->populateTables("past_investor", $startup_id, $debug);
        }

        else 
        { 
            echo "Invalid role. You passed in " . $role; 
        }
    }

    function populateTables($role, $startup_id, $debug)
    {
        /*** ANGELlIST API AUTHENTICATION ***/
        $client_id = "0bdabb752fa4e07f61a59c797aa3f05533be9cc98164cc44";
        $access_token = "1dd6555f46c5771b9cfcde241aae180c2d188f1bf2c0dab2";

        $url = "https://api.angel.co/1/startup_roles/?v=1&client_id=$client_id&access_token=$access_token&startup_id=$startup_id";

        $json = file_get_contents($url);
        $data = json_decode($json, true);
        $total = $data['total'];
        if ($total > 0)
        {
            for ($x = 0; $x < $total; $x++) 
            {
                //$name_array = array();
                if ($data['startup_roles'][$x]['role'] == $role)
                {
                    $name = $data['startup_roles'][($x)]['tagged']['name'];
                    $name_array[$x] = $name;
                }
            }

            /*** PREPARE DB FIELDS ***/
            $name_array = array_filter($name_array);
            $dbString = serialize($name_array);
            $startup_name = $data['startup_roles'][0]['startup']['name'];
       
            $sql = "INSERT INTO $role (startup_id, startup_name, data) VALUES('$startup_id', '$startup_name', '$dbString') ON                     DUPLICATE KEY UPDATE data='$dbString'";
            
            if (($this->connectToDB($sql, $startup_id, $startup_name, $role, $name_array)) == true)
            {
                echo "<br><b> Successful input for startup \"$startup_name\" (ID " . $startup_id . "). " . count($name_array) . "                     entries of type \"$role\" added. $total total roles found. </b><br>";
                $this->debug($debug, $dbString);
            }
            else 
            {
                echo "DB ERROR";
            }

        }
    }
    
    function connectToDB($sql, $startup_id, $startup_name, $role, $name_array)
    {
        /*** MYSQL AUTHENTICATION ***/
        $servername = "localhost";
        $username = "akshay";
        $password = "akshay";
        $db = "angellist";

        /*** CONNECT TO MYSQL DB ***/
        $conn = mysqli_connect($servername, $username, $password, $db);
        if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }


        /*** RETURN RESULTS OF INSERTION ***/
        if (mysqli_query($conn, $sql)) {
            return true;
        } else { echo "Error: " . $sql . "<br>" . mysqli_error($conn); }
        mysqli_close($conn);

        
    }
    
    /*** Debug dbString ***/
    function debug($debug, $dbString)
    {
        if (($debug == "true") || ($debug == "TRUE"))
        {
                echo "<br> (DEBUG) ENTRIES ADDED: <pre>";
                print_r(unserialize($dbString));
                echo "</pre>";
        }
    }  
}

$Angellist = new Angellist();
$Angellist->getFlags();
?>