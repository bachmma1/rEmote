
function openclose(group) {
	var tbodys = document.getElementById(group.innerHTML);
	var trs = tbodys.getElementsByTagName('tr');
	for(var i = 1; i < trs.length-1; i++) {
		var tr = trs[i];
		tr.style.display = tr.style.display == 'none' ? '' : 'none';
	}
	return false;
}
