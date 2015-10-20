<h1>Dynamically captures image links from imgflip.com</h1>
<form name="input" method="GET">
Number of Links: <input type="text" name="numOfPics" id="numOfPics">
<input type="submit">
</form>

<?php

echo $_GET["numOfPics"];
echo "hi";

?>