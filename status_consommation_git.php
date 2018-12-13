<?php
$servername = "localhost";
$username = "place_your_db_username_here";
$password = "place_your_password_here";
$dbname = "mqtt";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$carid=1;


$sql = "select a.id as 'id', from_unixtime(a.tstamp) as 'DateHeure', a.value as 'EWs', round(a.value/3600,1) as 'EWh', round(a.value/3600/1000*b.value,3) as 'totdol', round(a.sdur/60,1) as 'sdurmn', a.carid as 'carid', a.status as 'status', b.value as 'tarif' from energySession a, tarif b where a.tarifid=b.id and a.carid=".$carid." order by a.tstamp desc limit 200";


$sql_sommaire60j = "select year(from_unixtime(a.tstamp)) as 'yr',dayofyear(from_unixtime(a.tstamp)) as 'doy', date_format(from_unixtime(a.tstamp),'%M %e') as 'day', count(*) as 'nbs',round(sum(a.value)/3600/1000,2) as 'kwh', round(sum(a.value)/3600/1000*b.value,2) as 'cout', round(sum(a.sdur)/60,1) as 'sdur' from energySession a, tarif b where a.tarifid=b.id and a.carid=".$carid." and a.tstamp>=(a.tstamp-60*24*3600) group by yr,doy order by a.tstamp desc";


$sql_sommaire12m = "select year(from_unixtime(a.tstamp)) as 'yr',month(from_unixtime(a.tstamp)) as 'mois', date_format(from_unixtime(a.tstamp),'%M %e') as 'day', count(*) as 'nbs',round(sum(a.value)/3600/1000,2) as 'kwh', round(sum(a.value)/3600/1000*b.value,2) as 'cout', round(sum(a.sdur)/60,1) as 'sdur' from energySession a, tarif b where a.tarifid=b.id and a.carid=".$carid." and a.tstamp>=(a.tstamp-365*24*3600) group by yr,mois order by a.tstamp desc";


$sql_sombalance = "select sum(montantv) as 'sumV', sum(cost) as 'sumC', (sum(montantv)-sum(cost)) as 'bal' from ( (select 0 as 'montantv', round(sum(a.value)/3600/1000*b.value,2) as 'cost' from energySession a, tarif b where a.tarifid=b.id and a.carid=".$carid.") union all (select sum(value) as 'montantv', 0 as cost from versement where carid=".$carid.") )x";


$sql_versements = "select id, date(from_unixtime(dateV)) as 'date', round(value,2) as 'valeur', status from versement where carid=".$carid." order by dateV desc limit 100";

echo <<<END
<!DOCTYPE html>
<html>
<head>
	<title>Liste des recharges de la Chevrolet Spark</title>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="style2.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js" type="text/javascript"></script>
	<script src="tabScript.js"></script>
</head>
<body>
<h1>Recharges de la Chevrolet Spark</h1>
<hr/>

<div class="tab">
  <button class="tablinks" onclick="openPage(event, 'Status')">Status</button>
  <button class="tablinks" onclick="openPage(event, 'Sommaire12m')">Sommaire 12m</button>
  <button class="tablinks" onclick="openPage(event, 'Sommaire60j')" id="defaultOpen">Sommaire 60j</button>
  <button class="tablinks" onclick="openPage(event, 'Details')">Détails</button>
  <button class="tablinks" onclick="openPage(event, 'Graph1')">Graph Energie</button>
  <button class="tablinks" onclick="openPage(event, 'Graph2')">Graph Temperature</button>
  <button class="tablinks" onclick="openPage(event, 'Paiements')">Etat du compte</button>
</div>
<div id="Status" class="tabcontent">
<h2>Status de la connectivité</h2>
<table id="openevse">
		<thead><tr><th>Msg#</th><th>Véhicule</th><th>Consommation</th><th>Temp module interne</th><th>Capacité fournie</th><th>Cumul session</th></tr></thead>
		<tbody><tr><td id="msgid"></td><td id="state"></td><td id="amp"></td><td id="temp1"></td><td id="pilot"></td><td id="ws"></td></tr></tbody>
		<tfoot id="logHD"><tr><td colspan="6">MÀJ:<div id="dateheure"></div></td></tr>
		<tr><td colspan="6">LOG:<div id="logger"></div></td></tr></tfoot>
	</table>
</div>
END;

echo "<div id=\"Sommaire12m\" class=\"tabcontent\">";
echo "<h2>Sommaire par mois (12 mois)</h2>";

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
echo "<table id=\"details\" class=\"tbl3\"><thead><tr><th>ID</th><th>Date Heure (fin)</th><th>Energie (Ws)</th><th>Energie (Wh)</th><th>Cout ($)</th><th>Tarif</th><th>Durée (mn)</th><th>Vehicule ID</th><th>Status</th></tr></thead><tbody>";

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row["id"]."</td><td>".$row["DateHeure"]."</td><td>".$row["EWs"]."</td><td>".$row["EWh"]."</td><td>".$row["totdol"]."</td><td>".$row["tarif"]."</td><td>".$row["sdurmn"]."</td><td>".$row["carid"]."</td><td>".$row["status"]."</td></tr>";
    }
      echo "</tbody><tfoot id=\"sumtbl3\"></tfoot>";
} else {
    echo "</tbody><tfoot><tr><td colspan='8'>Aucun résultat</td></tr></tfoot>";
}
echo "</table></div>";
echo <<<END
<div id="Graph1" class="tabcontent">
  <h2>Energie consommee (en WattHeure, Wh)</h2>
  <iframe style="width:800px; height:600px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" name="graph1" src="http://192.168.122.203/emoncms/vis/multigraph?mid=4&embed=1&apikey=b85587d378ed6a5093190b73694d819c"></iframe>
</div>
<div id="Graph2" class="tabcontent">
  <h2>Température Extérieure (Celcius)</h2>
  <iframe style="width:800px; height:600px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://192.168.122.203/emoncms/vis/multigraph?mid=3&embed=1&apikey=b85587d378ed6a5093190b73694d819c"></iframe>
</div>
<div id="Paiements" class="tabcontent">
<h2>Etat sommaire:</h2>
END;

echo "<table id=\"etatcompte\" class=\"tbl4\"><thead><tr><th>Total versé ($)</th><th>Total Dû ($)</th><th>Balance ($)</th></tr></thead><tbody>";
$result = $conn->query($sql_sombalance);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row["sumV"]."</td><td>".$row["sumC"]."</td><td>".$row["bal"]."</td></tr>";
    }
    echo "</tbody>";
} else {
    echo "</tbody><tfoot><tr><td colspan='3'>Aucun résultat</td></tr></tfoot>";
}
echo "</table>";

echo "<h2>Liste des paiments:</h2>";

$result = $conn->query($sql_versements);
echo "<table id=\"paiements\" class=\"tbl5\"><thead><tr><th>Id</th><th>Date</th><th>Montant ($)</th><th>Status</th></tr></thead><tbody>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row["id"]."</td><td>".$row["date"]."</td><td>".$row["valeur"]."</td><td>".$row["status"]."</td></tr>";
    }
    echo "</tbody><tfoot id=\"sumtbl5\"></tfoot>";
} else {
    echo "</tbody><tfoot><tr><td colspan='4'>Aucun résultat</td></tr><tfoot>";
}
echo "</table></div>";
echo "<script>tblUpdate(1);tblUpdate(2);tblUpdate(3);MQTTinit();document.getElementById(\"defaultOpen\").click();</script>";
echo "<hr/>Page générée le: ".date("D M j G:i:s T Y")." - Yh";
echo "</body></html>";
$conn->close();
?>
