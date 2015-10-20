<?php

$numOfPages = ceil(($_GET["numOfPics"])/14);
$file = fopen("export.txt", w);

echo <<<CODE
<h1>capture image links from imgflip.com</h1>
<form name="input" method="GET">
Number of Links: <input type="text" name="numOfPics" id="numOfPics">
<input type="submit"> (rounds to nearest page) -- don't enter some abnormally huge number to test it out
</form>
CODE;

if ($numOfPages != 0) { echo "<a href=/imgflip/export.txt>Export as TXT</a><br><br>"; }

function get_content($URL){
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_URL, $URL);
          $data = curl_exec($ch);
          curl_close($ch);
          return $data;
      }

$page = 1; while($page<=$numOfPages){
    $source = get_content("https://imgflip.com?tgz=memes&page=$page");
    //$source = get_content('http://akshaymahesh.com/imgflip/imgflip.html');
    $level1 = explode("<div class='base-unit clearfix'>", substr($source, 4739)); //break up source by pic
    
    echo "page $page of $numOfPages <br>";
    
    $x=1; while($x<=13){ 
        $level2 = explode("src", $level1[$x]);
        $url = substr($level2[1], 4, 23);

        echo "http://$url <br>";
        
        fwrite($file, "http://$url\n"); //writes to export file
        
        $x++; 
    }
    $page++;
}

?>