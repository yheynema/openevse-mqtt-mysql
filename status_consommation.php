<?php
$servername = "localhost";
$username = "mqttScript";
$password = "mqttScr1pt";
$dbname = "mqtt";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
$unitprice=0.103;

$sql = "select id, from_unixtime(tstamp) as 'DateHeure', value as 'EWs', round(value/3600,1) as 'EWh', round(value/3600/1000*".$unitprice.",3) as 'totdol', round(sdur/60,1) as 'sdurmn', carid, status from energySession order by id desc";

$result = $conn->query($sql);

echo <<<END
<!DOCTYPE html>
<html>
<head>
	<title>Liste des recharges de la Chevrolet Spark</title>
	<meta charset="UTF-8" />
	<style>
	#customers {
    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 80%;
}
#customers td, #customers th {
    border: 1px solid #ddd;
    padding: 8px;
}
#customers tr:nth-child(even){background-color: #f2f2f2;}
#customers tr:hover {background-color: #ddd;}
#customers th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #4CAF50;
    color: white;
}
</style>
</head>
<body>
	<h1>Liste des récentes recharges</h1><p>
END;

if ($result->num_rows > 0) {
    echo "<table id=\"customers\"><tr><th>ID</th><th>Date Heure (fin)</th><th>Energie (Ws)</th><th>Energie (Wh)</th><th>Cout ($)</th><th>Durée (mn)</th><th>Vehicule ID</th><th>Status</th></tr>";
    $nb=0;
    $totEWs=0;
    $totEWh=0;
    $tottotdol=0;
    $totdurmn=0;
    // output data of each row
    while($row = $result->fetch_assoc()) {
    	$nb++;
    	$totEWs+=$row["EWs"];
    	$totEWh+=$row["EWh"];
    	$tottotdol+=$row["totdol"];
    	$totdurmn+=$row["sdurmn"];
        echo "<tr><td>".$row["id"]."</td><td>".$row["DateHeure"]."</td><td>".$row["EWs"]."</td><td>".$row["EWh"]."</td><td>".$row["totdol"]."</td><td>".$row["sdurmn"]."</td><td>".$row["carid"]."</td><td>".$row["status"]."</td></tr>";
    }
    echo "<tr><th>".$nb."</th><th></th><th>".$totEWs."</th><th>".$totEWh."</th><th>".$tottotdol."</th><th>".$totdurmn."</th><th></th><th></th></tr>";
    echo "</table>";
} else {
    echo "<p>0 results</p>";
}
echo "</body></html>";
$conn->close();
?>