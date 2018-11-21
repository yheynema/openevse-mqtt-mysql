var refTblData = [ [0,0], [6,3,4,5,6], [6,3,4,5,6], [8,3,4,5,6]];

function tblUpdate(tbl) {
  var cellRef=[0,0,0,0];
  for (var j in refTblData[tbl]) {
    //console.log("tbl:"+tbl+"; j="+j+";");
    if (j!=0) {
      var cells = document.querySelectorAll(".tbl"+tbl+" tbody tr td:nth-of-type("+refTblData[tbl][j]+")");
      sum=0;
      for (var i=0; i<cells.length; i++) {
        sum+= parseFloat(cells[i].firstChild.data);
      }
      cellRef[refTblData[tbl][j]-1].outerHTML="<th>"+sum.toFixed(2)+"</th>";
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