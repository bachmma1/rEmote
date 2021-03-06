function getNameFromHash(hash) {
	var atag = document.getElementById(hash);
	return atag.innerHTML;
}

function getQueryVariable(variable, query)
{
       var params = query.split("?")[1];
       var vars = params.split("&");
       for (var i=0;i<vars.length;i++) {
               var pair = vars[i].split("=");
               if(pair[0] == variable){return pair[1];}
       }
       return(false);
}

function linkToDirName(link) {
	var vars = link.split("%2F");
	return vars[vars.length-1];
}

function fade( obj, opacity )
{
	obj.style.opacity = opacity;
	obj.style.filter  = "alpha(opacity="+parseInt(opacity*100)+")";
}

function keyDownFun(e)
{
	if(!e)
		e = window.event;

	if(e.keyCode == 27)
		hideConfirm();
}

function showConfirm( link , action )
{
	var text, name;
	var istorrent = false;
	var confadd = '&confirm=true';
	var con, bgObj;

	if(action == "del") {
		istorrent = true;
		text = lngdelconfirm;
		name = getNameFromHash(getQueryVariable("hash", link));
	}
	else if(action == 'fdel') {
		istorrent = false;
		text = lngfbdelconf;
		if(getQueryVariable("action", link) == "deldir")
			name = linkToDirName(getQueryVariable("dir", link));
		else if (getQueryVariable("action", link) == "delfile")
			name = linkToDirName(getQueryVariable("file", link));
	}
	
	if(!(con = document.getElementById('confirm')))
	{
		con = document.createElement('div');
		con.id = "confirm";
		con.className = "framebox";
		var body = document.getElementsByTagName('BODY');
		body[0].insertBefore( con, document.getElementById("main"));
	}
	else
		con.style.display = "block";
	
	if(!(bgObj = document.getElementById('bgObj')))
	{
		bgObj = document.createElement('div');
		bgObj.id = "bgObj";
		bgObj.className = "framebox";
		var body = document.getElementsByTagName('BODY');
		body[0].insertBefore( bgObj, document.getElementById("main"));
	}
	else
		bgObj.style.display = "block";

	if (!istorrent) {
		form  = '<input type="submit" value="' + lngyes + '" class="yes" onclick="location.href = \'' + link + confadd + '\';" />';
		form += '<input type="submit" value="' + lngno  + '" class="no" onclick="hideConfirm();" />';
	}
	else {
		var onlytorrent = link.split("&path");
		form = '<input type="submit" value="' + lngonlytorrent + '" class="yes" onclick="location.href = \'' + onlytorrent[0] + confadd + '\';" />';
		form += '<input type="submit" value="' + lngtorrentwithdata + '" class="yes" onclick="location.href = \'' + link + confadd + '\';" />';
		form += '<input type="submit" value="' + lngnothing + '" class="no" onclick="hideConfirm();" />';
	}

   con.style.zIndex = 999;
   bgObj.style.zIndex = 998;
   bgObj.style.backgroundColor = 'black';
	con.innerHTML = "<div><div>" + "<p><b>" + name + "</b></p>" + "<p>" + text + "</p><p id=\"confbuttons\">"+ form + "</p></div></div>";
	var max = 0.7;

	fade(bgObj, 0.0);
	setTimeout(function(){ fade(bgObj, max/12 *  1); }, 1000/12 *  1);
	setTimeout(function(){ fade(bgObj, max/12 *  2); }, 1000/12 *  2);
	setTimeout(function(){ fade(bgObj, max/12 *  3); }, 1000/12 *  3);
	setTimeout(function(){ fade(bgObj, max/12 *  4); }, 1000/12 *  4);
	setTimeout(function(){ fade(bgObj, max/12 *  5); }, 1000/12 *  5);
	setTimeout(function(){ fade(bgObj, max/12 *  6); }, 1000/12 *  6);
	setTimeout(function(){ fade(bgObj, max/12 *  7); }, 1000/12 *  7);
	setTimeout(function(){ fade(bgObj, max/12 *  8); }, 1000/12 *  8);
	setTimeout(function(){ fade(bgObj, max/12 *  9); }, 1000/12 *  9);
	setTimeout(function(){ fade(bgObj, max/12 * 10); }, 1000/12 * 10);
	setTimeout(function(){ fade(bgObj, max/12 * 11); }, 1000/12 * 11);
	setTimeout(function(){ fade(bgObj, max/12 * 12); }, 1000/12 * 12);

	return false;
}

function hideConfirm()
{
	var con, bgObj;
	
	if(con = document.getElementById('confirm'))
		con.style.display = "none";
	
	if(bgObj = document.getElementById('bgObj'))
		bgObj.style.display = "none";
}
window.addEventListener('keypress', keyDownFun, false);
