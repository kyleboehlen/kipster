function toggleRows(e, siteName){
	var i, classRows;

	classRows = document.getElementsByClassName(siteName + ' up-highlighting');
	toggleArray(classRows);

	classRows = document.getElementsByClassName(siteName + ' down-highlighting');
	toggleArray(classRows);

	if(e.target.innerHTML == "+"){
		e.target.innerHTML = "-";
	}else{
		e.target.innerHTML = "+";
	}
}

function toggleArray(classRows){
	for(i = 0; i < classRows.length; i++){
		if(classRows[i].style.display == "none"){
			classRows[i].style.display = "";
		}else{
			classRows[i].style.display = "none";
		}
	}
}
