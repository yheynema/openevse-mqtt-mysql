/* Ref tbl is structured as:
     level 0 index=table number (ie:tblX);
     1st element: number of columns in the table
     2nd element an array= 1st value = column to consider
                           2nd value = 0=report sum value; 1=report count value
*/
var refTblData = [ [0,[0,0]], [6,[3,0],[4,0],[5,0],[6,0]], [6,[2,1],[3,0],[4,0],[5,0],[6,0]], [9,[1,1],[3,0],[4,0],[5,0],[7,0]]];

var state=0;
var amp=0;
var temp1=0;
var pilot=0;
var wh=0;
var userid=0;
var mqttConnectStatus=0;
var nbmsg=0;
var etats=["Inconnu","Déconnecté","Connecté","Charge en cours","Erreur","Erreur"];

var client = new Paho.MQTT.Client("192.168.122.253",Number(9001),"clientjs");
client.onMessageArrived = onMessageArrived;
client.onConnectionLost = onConnectLost;

function tblUpdate(tbl) {
  var cellRef=[0,0,0,0];
  for (var j in refTblData[tbl]) {
    //console.log("tbl:"+tbl+"; j="+j+";");
    if (j!=0) {
      var cells = document.querySelectorAll(".tbl"+tbl+" tbody tr td:nth-of-type("+refTblData[tbl][j][0]+")");
      sum=0;
      count=0;
      for (var i=0; i<cells.length; i++) {
        sum+= parseFloat(cells[i].firstChild.data);
        count++;
      }
      if (refTblData[tbl][j][1]==0) {
          cellRef[refTblData[tbl][j][0]-1].outerHTML="<th>"+sum.toFixed(2)+"</th>";
      } else {
          cellRef[refTblData[tbl][j][0]-1].outerHTML="<th>"+count+"</th>";
      }
    } else {
      tblObj = document.getElementById("sumtbl"+tbl);
      rowTbl = tblObj.insertRow(-1);
      for (var nbRow=0; nbRow<refTblData[tbl][0]; nbRow++) {
        cellRef[nbRow]=rowTbl.insertCell(nbRow);
      }
    }
  }
}

function openPage(evt, pageName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(pageName).style.display = "block";
    evt.currentTarget.className += " active";
}
function onMessageArrived(message) {
    
    //log('Received operation "' + message.payloadString + '", nbmsg:'+nbmsg);
    if (message.destinationName == "openevse/amp") {
        amp = Number(message.payloadString)/1000;
        //console.log("amp msg: "+amp);
        document.getElementById("amp").innerHTML = amp+" A";
    }
    if (message.destinationName == "openevse/pilot") {
        pilot = Number(message.payloadString);
        //console.log("pilot msg: "+pilot);
        document.getElementById("pilot").innerHTML = pilot+" A";
    }
    if (message.destinationName == "openevse/state") {
        nbmsg++;
        state = Number(message.payloadString);
        //console.log("state msg: "+state);
        var doc = document.getElementById("state")
        if (state < 6 && state > 0) {
            doc.innerHTML = etats[state];
        } else {
            doc.innerHTML = "code:"+state;
        }
        document.getElementById("msgid").innerHTML = nbmsg;
        logDateHeure();
    }
    if (message.destinationName == "openevse/temp1") {
        temp1 = Number(message.payloadString);
        //console.log("temp1 msg: "+temp1);
        document.getElementById("temp1").innerHTML = temp1/10+" &deg;C";
    }
    if (message.destinationName == "openevse/wh") {
        wh = Number(message.payloadString)/3600;
        //console.log("wh msg: "+wh);
        document.getElementById("ws").innerHTML = wh.toFixed(1)+" Wh";
    }
    if (message.destinationName == "openevse/userid") {
        var msgdata=message.payloadString.split(',');
        //console.log("userid msg: at "+ msgdata[0]+" user="+msgdata[1]);
        document.getElementById("userid").innerHTML = "tag" + msgdata[1]+"@"+msgdata[0];
    }
    
}

function onConnect() {
    mqttConnectStatus=1;
    log("MQTT connected!");
    var subscribeOptions = {qos: 0}
    client.subscribe("openevse/amp",subscribeOptions);
    client.subscribe("openevse/pilot",subscribeOptions);
    client.subscribe("openevse/state",subscribeOptions);
    client.subscribe("openevse/temp1",subscribeOptions);
    client.subscribe("openevse/wh",subscribeOptions);
    client.subscribe("openevse/userid",subscribeOptions);
}

function log(message) {
    document.getElementById('logger').insertAdjacentHTML('beforeend', '<div>' + message + '</div>');
}
function logDateHeure() {
    //var options = {weekday: "narrow", day: "numeric", month: "long", hour:"numeric",minute:"numeric",second:"numeric"};
    var d = new Date();
    document.getElementById("dateheure").innerHTML = d.toLocaleString("fr-CA");
}
function onConnectLost(message) {
    log("OnConnectionLost! errnum="+message.errorCode+"; errmsg="+message.errorMessage);
}

function MQTTinit() {
    client.connect({
        onSuccess: onConnect,
        mqttVersion: 3
    });
    logDateHeure();
}