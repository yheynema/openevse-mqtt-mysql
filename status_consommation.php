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

$sql = "select id, from_unixtime(tstamp) as 'DateHeure', value as 'EWs', round(value/3600,1) as 'EWh', round(value/3600/1000*".$unitprice.",3) as 'totdol', round(sdur/60,1) as 'sdurmn', carid, status from energySession where carid=1 order by id desc";

$sql_sommaire = "select year(from_unixtime(tstamp)) as 'yr',dayofyear(from_unixtime(tstamp)) as 'doy', date_format(from_unixtime(tstamp),'%M %e') as 'day', count(*) as 'nbs',round(sum(value)/3600/1000,2) as 'kwh', round(sum(sdur)/60,1) as 'sdur' from energySession where carid=1 group by yr,doy order by tstamp desc";

echo <<<END
<!DOCTYPE html>
<html>
<head>
	<title>Liste des recharges de la Chevrolet Spark</title>
	<meta charset="UTF-8" />
	<link rel="stylesheet" type="text/css" href="style2.css">
</head>
<body>
<h1>Recharges de la Chevrolet Spark</h1>
<hr/>
<h2>Sommaire par jour</h2>
END;

$result = $conn->query($sql_sommaire);
echo "<table id=\"customers2\"><tr><th>Année</th><th>Mois / Jour</th><th>Nb sessions</th><th>Energie (kWh)</th><th>Durée (mn)</th></tr>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row["yr"]."</td><td>".$row["day"]."</td><td>".$row["nbs"]."</td><td>".$row["kwh"]."</td><td>".$row["sdur"]."</td></tr>";
    }
} else {
    echo "<tr><td colspan='5'>Aucun résultat</td></tr>";
}
echo "</table>";

$result = $conn->query($sql);

echo "<hr/><h2>Liste des récentes recharges</h2>";

echo "<table id=\"customers\"><tr><th>ID</th><th>Date Heure (fin)</th><th>Energie (Ws)</th><th>Energie (Wh)</th><th>Cout ($)</th><th>Durée (mn)</th><th>Vehicule ID</th><th>Status</th></tr>";

if ($result->num_rows > 0) {
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
} else {
    echo "<tr><td colspan='8'>Aucun résultat</td></tr>";
}
echo "</table>";

echo "</body></html>";
$conn->close();
?>
