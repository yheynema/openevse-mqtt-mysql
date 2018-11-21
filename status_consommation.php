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

$sql = "select id, from_unixtime(tstamp) as 'DateHeure', value as 'EWs', round(value/3600,1) as 'EWh', round(value/3600/1000*".$unitprice.",3) as 'totdol', round(sdur/60,1) as 'sdurmn', carid, status from energySession where carid=1 order by tstamp desc";

$sql_sommaire60j = "select year(from_unixtime(tstamp)) as 'yr',dayofyear(from_unixtime(tstamp)) as 'doy', date_format(from_unixtime(tstamp),'%M %e') as 'day', count(*) as 'nbs',round(sum(value)/3600/1000,2) as 'kwh', round(sum(value)/3600/1000*".$unitprice.",2) as 'cout', round(sum(sdur)/60,1) as 'sdur' from energySession where carid=1 and tstamp>=(tstamp-60*24*3600) group by yr,doy order by tstamp desc";

$sql_sommaire12m = "select year(from_unixtime(tstamp)) as 'yr',month(from_unixtime(tstamp)) as 'mois', date_format(from_unixtime(tstamp),'%M %e') as 'day', count(*) as 'nbs',round(sum(value)/3600/1000,2) as 'kwh', round(sum(value)/3600/1000*".$unitprice.",2) as 'cout', round(sum(sdur)/60,1) as 'sdur' from energySession where carid=1 and tstamp>=(tstamp-365*24*3600) group by yr,mois order by tstamp desc";

echo <<<END
<!DOCTYPE html>
<html>
<head>
	<title>Liste des recharges de la Chevrolet Spark</title>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="style2.css">
	<script src="tabScript.js"></script>
</head>
<body>
<h1>Recharges de la Chevrolet Spark</h1>
<hr/>

<div class="tab">
  <button class="tablinks" onclick="openPage(event, 'Sommaire12m')">Sommaire 12m</button>
  <button class="tablinks" onclick="openPage(event, 'Sommaire60j')" id="defaultOpen">Sommaire 60j</button>
  <button class="tablinks" onclick="openPage(event, 'Details')">Détails</button>
  <button class="tablinks" onclick="openPage(event, 'Graph1')">Graph Energie</button>
  <button class="tablinks" onclick="openPage(event, 'Graph2')">Graph Temperature</button>
  <button class="tablinks" onclick="openPage(event, 'Paiements')">Etat du compte</button>
</div>
<div id="Sommaire12m" class="tabcontent">
<h2>Sommaire par mois (12 mois)</h2>
END;

$result = $conn->query($sql_sommaire12m);
echo "<table id=\"som12m\" class=\"tbl1\"><thead><tr><th>Année</th><th>Mois</th><th>Nb sessions</th><th>Energie (kWh)</th><th>Total ($)</th><th>Durée (mn)</th></tr></thead><tbody>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row["yr"]."</td><td>".$row["mois"]."</td><td>".$row["nbs"]."</td><td>".$row["kwh"]."</td><td>".$row["cout"]."</td><td>".$row["sdur"]."</td></tr>";
    }

} else {
    echo "<tr><td colspan='5'>Aucun résultat</td></tr>";
}
echo "</tbody><tfoot id=\"sumtbl1\"></tfoot></table></div>";

echo "<div id=\"Sommaire60j\" class=\"tabcontent\">";
echo "<h2>Sommaire par jour (60 jours)</h2>";

$result = $conn->query($sql_sommaire60j);
echo "<table id=\"som60j\" class=\"tbl2\"><thead><tr><th>Année</th><th>Mois / Jour</th><th>Nb sessions</th><th>Energie (kWh)</th><th>Total ($)</th><th>Durée (mn)</th></tr></thead><tbody>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row["yr"]."</td><td>".$row["day"]."</td><td>".$row["nbs"]."</td><td>".$row["kwh"]."</td><td>".$row["cout"]."</td><td>".$row["sdur"]."</td></tr>";
    }
} else {
    echo "<tr><td colspan='5'>Aucun résultat</td></tr>";
}
echo "</tbody><tfoot id=\"sumtbl2\"></tfoot></table></div>";

$result = $conn->query($sql);

echo "<div id=\"Details\" class=\"tabcontent\">";
echo "<h2>Liste des récentes recharges</h2>";
echo "<p>Note: cette liste est limitée aux 150 dernières recharges seulement.</p>";
echo "<table id=\"details\" class=\"tbl3\"><thead><tr><th>ID</th><th>Date Heure (fin)</th><th>Energie (Ws)</th><th>Energie (Wh)</th><th>Cout ($)</th><th>Durée (mn)</th><th>Vehicule ID</th><th>Status</th></tr></thead><tbody>";

if ($result->num_rows > 0) {
//    $nb=0;
//    $totEWs=0;
//    $totEWh=0;
//    $tottotdol=0;
//    $totdurmn=0;
    // output data of each row
    while($row = $result->fetch_assoc()) {
//    	$nb++;
//    	$totEWs+=$row["EWs"];
//    	$totEWh+=$row["EWh"];
//    	$tottotdol+=$row["totdol"];
//    	$totdurmn+=$row["sdurmn"];
        echo "<tr><td>".$row["id"]."</td><td>".$row["DateHeure"]."</td><td>".$row["EWs"]."</td><td>".$row["EWh"]."</td><td>".$row["totdol"]."</td><td>".$row["sdurmn"]."</td><td>".$row["carid"]."</td><td>".$row["status"]."</td></tr>";
    }
//    echo "</tbody><tfoot id=\"sumtbl3\"><tr><th>".$nb."</th><th></th><th>".$totEWs."</th><th>".$totEWh."</th><th>".$tottotdol."</th><th>".$totdurmn."</th><th></th><th></th></tr></tfoot>";
      echo "</tbody><tfoot id=\"sumtbl3\"></tfoot>";
} else {
    echo "</tbody><tfoot><tr><td colspan='8'>Aucun résultat</td></tr></tfoot>";
}
echo "</table></div>";
echo <<<END
<div id="Graph1" class="tabcontent">
  <h2>Energie consommee (en WattHeure, Wh)</h2>
  <iframe style="width:800px; height:600px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" name="graph1" src="http://192.168.122.203/emoncms/vis/multigraph?mid=2&embed=1&apikey=b85587d378ed6a5093190b73694d819c"></iframe>
</div>
<div id="Graph2" class="tabcontent">
  <h2>Température Extérieure (Celcius)</h2>
  <iframe style="width:800px; height:600px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://192.168.122.203/emoncms/vis/multigraph?mid=3&embed=1&apikey=b85587d378ed6a5093190b73694d819c"></iframe>
</div>
<div id="Paiements" class="tabcontent">
<h2>Etat sommaire:</h2>
<p>(À venir)</p>
<h2>Liste des paiments:</h2>
<p>(À venir / À développer)</p>
</div>
<script>tblUpdate(1);tblUpdate(2);tblUpdate(3);document.getElementById("defaultOpen").click();</script>
END;
echo "<hr/>Page générée le: ".date("D M j G:i:s T Y")." - Yh";
echo "</body></html>";
$conn->close();
?>
