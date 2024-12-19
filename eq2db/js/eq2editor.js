// JavaScript Document
function dosub(subm) 
{ 
	if (subm != "") 
	{ 
		self.location=subm; 
	} 
}

var original_lua_script_text = "";

function getCachedScriptKey() {
	return "eq2ScriptEditor/" + document.getElementById("script_name").value;
}

function checkForCachedScript() {
	original_lua_script_text = editor.getValue();

	let text = localStorage.getItem(getCachedScriptKey());

	if (!text) return;

	let es = document.getElementById("EditorStatus");

	es.innerHTML = "<span class=\"warning\">Status: You are currently viewing a locally cached version of this script! It may be out of date.</span>";

	editor.setValue(text, 1);
}

function updateCachedScript() {
	let text = editor.getValue();
	localStorage.setItem(getCachedScriptKey(), text);
}

function clearCachedScript() {
	localStorage.removeItem(getCachedScriptKey());
	let es = document.getElementById("EditorStatus");
	es.innerHTML = "";
}

function getXmlHttpRequestObject() 
{
	if (window.XMLHttpRequest) 
	{
		return new XMLHttpRequest();
	} 
	else if(window.ActiveXObject) 
	{
		return new ActiveXObject("Microsoft.XMLHTTP");
	} 
	else 
	{
		alert("Incompatible web browser.");
	}
}

var searchReq = getXmlHttpRequestObject();

var searchSuggestDiv = null;

function handleSearchSuggest() 
{
	if (searchReq.readyState == 4) 
	{
		let ssdiv = searchSuggestDiv == null ? "search_suggest" : searchSuggestDiv;
		searchSuggestDiv = null;
		var ss = document.getElementById(ssdiv);
		ss.innerHTML = '';
		var str = searchReq.responseText.split("\n");
		for(i=0; i < str.length; i++) 
		{
			var suggest = '<div onmouseover="suggestOver(this);" ';
			suggest += 'onmouseout="suggestOut(this);" ';
			if (!str[i].includes("No matches.")) suggest += 'onclick="setSearch(this.innerHTML,\'' + ssdiv + '\');" ';
			suggest += 'class="suggest_link">' + str[i] + '</div>';
			ss.innerHTML += suggest;
			ss.className = 'suggest_shadow';
		}
	}
}

function suggestOver(div_value) 
{
	div_value.className = 'suggest_link_over';
}

function suggestOut(div_value) 
{
	div_value.className = 'suggest_link';
}

var ajaxSelectCallback = null;

var txtSearchAjaxInput = null;

function setSearch(value, suggestID = 'search_suggest') 
{
	const txtSearchID = txtSearchAjaxInput == null ? 'txtSearch' : txtSearchAjaxInput;
	txtSearchAjaxInput = null;
	document.getElementById(txtSearchID).value = value;
	document.getElementById(suggestID).innerHTML = '';

	if (ajaxSelectCallback) {
		ajaxSelectCallback();
	}
}

function createRequest() {
 var request = null;
  try {
   request = new XMLHttpRequest();
  } catch (trymicrosoft) {
   try {
     request = new ActiveXObject("Msxml2.XMLHTTP");
   } catch (othermicrosoft) {
     try {
      request = new ActiveXObject("Microsoft.XMLHTTP");
     } catch (failed) {
       request = null;
     }
   }
 }

 if (request == null) {
   alert("Error creating request object!");
 } else {
   return request;
 }
}

var request1 = createRequest();
var request2 = createRequest();
var request2A = createRequest();
var request3 = createRequest();

/* an ajax log file tailer / viewer
copyright 2007 john minnihan.
 
http://freepository.com
 
Released under these terms
1. This script, associated functions and HTML code ("the code") may be used by you ("the recipient") for any purpose.
2. This code may be modified in any way deemed useful by the recipient.
3. This code may be used in derivative works of any kind, anywhere, by the recipient.
4. Your use of the code indicates your acceptance of these terms.
5. This notice must be kept intact with any use of the code to provide attribution.
*/
 
function getLog(timer) {
	let url = location.origin + location.pathname;
	url = url.replace("_admin.php", "logtail.php");
	url += "?<?php printf(type=%s&log=%s, $_GET['type'], $_GET['log']); ?>";
	console.log(url);
	request1.open("GET", url, true);
	request1.onreadystatechange = updatePage;
	request1.send(null);
	startTail(timer);
}
 
function startTail(timer) {
	if (timer == "stop") {
		stopTail();
	} else {
		t= setTimeout("getLog()",10000);
	}
}
 
function stopTail() {
	clearTimeout(t);
	var pause = "The log viewer has been paused. To begin viewing again, click the Start Viewer button.\n";
	logDiv = document.getElementById("log");
	var newNode=document.createTextNode(pause);
	logDiv.replaceChild(newNode,logDiv.childNodes[0]);
}
 
function updatePage() {
	if (request1.readyState == 4) {
		if (request1.status == 200) {
			var currentLogValue = request1.responseText.split("\n");
			eval(currentLogValue);
			logDiv = document.getElementById("log");
			var logLine = ' ';
			for (i=0; i < currentLogValue.length - 1; i++) {
				logLine += currentLogValue[i] + "<br/>\n";
			}
		logDiv.innerHTML=logLine;
		} else
		alert("Error! Request status is " + request1.status);
	}
}

function CalculateWeaponDamageRating() {
	let low = document.getElementById("dmgBaseLow");
	let high = document.getElementById("dmgBaseHigh");
	let rating = document.getElementById("dmgRating");
	let delay = document.getElementById("dmgDelay")
	if (!low || !high || !rating || !delay) {
		return;
	}

	let l = parseInt(low.value);
	let h = parseInt(high.value);
	let d = parseInt(delay.value);
	if (isNaN(l) || isNaN(h) || isNaN(d)) {
		return;
	}
	d /= 10.0;
	rating.value = ((l + h) / d).toFixed(3);
}

function ReloadItemIcon() {
	let e = document.getElementById('itemIcon');
	let eClassic = document.getElementById('itemClassicIcon');

	let classic_icon = document.getElementsByName('items|classic_icon')[0].value;
	let icon = document.getElementsByName('items|icon')[0].value;
	let tier = document.getElementsByName('items|tier')[0].value;
	let crafted = document.getElementsByName('items|crafted')[0].checked ? '&crafted' : "";

	let link = `eq2Icon.php?type=item&id=${icon}&tier=${tier}${crafted}`;
	let linkClassic = `eq2Icon.php?type=item&id=${classic_icon}&tier=${tier}${crafted}`;
	if(linkClassic != eClassic.getAttribute("src")) {
		eClassic.setAttribute("src", linkClassic);
	}
	if (link != e.getAttribute("src")) {
		e.setAttribute("src", link);
	}
}

function UpdateItemTierTag() {
	let e = document.getElementById('tierTag');
	let eClassic = document.getElementById('tierClassicTag');
	let crafted = document.getElementsByName('items|crafted')[0].checked;
	let tier = document.getElementsByName('items|tier')[0].value;

	let color = "white";
	let text = "COMMON";
	if (crafted) {
		if (tier >= 11) {
			color = "#b266ff";
			text = "MASTERCRAFTED MYTHICAL";
		}
		else if (tier >= 9) {
			color = "#f1948a";
			text = "MASTERCRAFTED FABLED";
		}
		else if (tier >= 7) {
			color = "#f5cba7";
			text = "MASTERCRAFTED LEGENDARY";
		}
		else if (tier >= 5) {
			color = "#f5cba7";
			text = "MASTERCRAFTED";
		}
		else {
			color = "#7DCEA0";
			text = "HANDCRAFTED";
		}
	}
	else {
		if (tier >= 11) {
			color = "#b266ff";
			text = "MYTHICAL";
		}
		else if (tier >= 9) {
			color = "#f1948a";
			text = "FABLED";
		}
		else if (tier >= 7) {
			color = "#f5cba7";
			text = "LEGENDARY";
		}
		else if (tier >= 4) {
			color = "#85C1E9";
			text = "TREASURED";
		}
		else if (tier >= 3) {
			color = "#7DCEA0";
			text = "UNCOMMON";
		}
	}

	e.innerHTML = text;
	eClassic.innerHTML = text;
	e.setAttribute("style", `color:${color}`);
	eClassic.setAttribute("style", `color:${color}`);
}

function ElementToggleCheckbox(checkboxID, elementID) {
	let checkbox = document.getElementById(checkboxID);
	let element = document.getElementById(elementID);

	function DoToggle() {
		if (checkbox.checked) { 
			element.removeAttribute("disabled");
		}
		else element.setAttribute("disabled", "");
	}

	DoToggle();

	checkbox.addEventListener('change', (event) => {
		DoToggle();
	})
}

function EntityCmdLookupAJAX(textboxID, suggestID, textOutID, cmdOutID, bSingleOnly) {
	if (searchReq.readyState == 4 || searchReq.readyState == 0) {
		let search = document.getElementById(textboxID);
		let str = escape(search.value);
		if (str.length == 0) {
			let ss = document.getElementById(suggestID)
			ss.innerHTML = '';
			return;
		}
		let q = '../ajax/eq2Ajax.php?type=luEc&search=' + str;
		if (bSingleOnly) q += "&single";
		searchReq.open("GET", q, true);
		searchReq.onreadystatechange = function() {
			if (searchReq.readyState == 4) 
			{
				var ss = document.getElementById(suggestID);
				ss.innerHTML = '';
				ss.className = 'suggest_shadow';
				var str = searchReq.responseText.split("\n");
				for(i=0; i < str.length; i++) 
				{
					var div = document.createElement("div");
					div.className = "suggest_link";
					div.onmouseover = function(event) { event.target.className = "suggest_link_over"; };
					div.onmouseout = function(event) { event.target.className = "suggest_link"; };
					if (!str[i].includes("No matches."))  {
						div.onclick = function(event) { 
							let to = document.getElementById(textOutID);
							to.innerHTML = event.target.innerHTML;
							const id_pat = / \((\d+)\)$/;

							let match = event.target.innerHTML.match(id_pat);
							let idOut = document.getElementById(cmdOutID);
							idOut.value = match[1];
							const e = new Event("change");
							if (idOut.onchange) idOut.dispatchEvent(e);
							ss.innerHTML = "";
							search.setAttribute("hidden", "");
						 };
					}
					div.innerHTML = str[i];
					ss.appendChild(div);
				}
			}
		};
		searchReq.send(null);
	}
}

function HandleScrollTracking() {
	document.addEventListener('scroll', function() {
		sessionStorage.setItem("scrollTrackingTop", document.scrollingElement.scrollTop);
		sessionStorage.setItem("scrollTrackingLeft", document.scrollingElement.scrollLeft);
	});

	window.addEventListener('load', function() {
		let search = document.location.search;
		let prev = sessionStorage.getItem("scrollLoc");

		if (prev == search) {
			let top = sessionStorage.getItem("scrollTrackingTop");
			let left = sessionStorage.getItem("scrollTrackingLeft");

			if (top != null) {
				document.scrollingElement.scrollTop = Number.parseInt(top);
			}
			if (left != null) {
				document.scrollingElement.scrollLeft = Number.parseInt(left);
			}
		}

		sessionStorage.setItem("scrollLoc", search);
	});
}

function LootGlobalTypeChange(e, id) {
	let v1 = document.getElementById("v1|" + id);
	let v2 = document.getElementById("v2|" + id);
	let v3 = document.getElementById("v3|" + id);
	let v4 = document.getElementById("v4|" + id);

	let v1Tag = document.getElementById("v1Tag|" + id);
	let v2Tag = document.getElementById("v2Tag|" + id);
	let v3Tag = document.getElementById("v3Tag|" + id);
	let v4Tag = document.getElementById("v4Tag|" + id);

	if (e.value == "Level") {
		v1Tag.innerHTML = "min lvl:";
		v1.removeAttribute("disabled");
		v2Tag.innerHTML = "max lvl:";
		v2.removeAttribute("disabled");
		v3Tag.innerHTML = "unused:";
		v3.setAttribute("disabled", "");
		v4Tag.innerHTML = "loot tier:";
		v4.removeAttribute("disabled");
	}
	else if (e.value == "Zone") {
		v1Tag.innerHTML = "zone id:";
		v1.removeAttribute("disabled");
		v2Tag.innerHTML = "min lvl:";
		v2.removeAttribute("disabled");
		v3Tag.innerHTML = "max lvl:";
		v3.removeAttribute("disabled");
		v4Tag.innerHTML = "loot tier:";
		v4.removeAttribute("disabled");
	}
	else if (e.value == "Racial") {
		v1Tag.innerHTML = "race id:";
		v1.removeAttribute("disabled");
		v2Tag.innerHTML = "min lvl:";
		v2.removeAttribute("disabled");
		v3Tag.innerHTML = "max lvl:";
		v3.removeAttribute("disabled");
		v4Tag.innerHTML = "loot tier:";
		v4.removeAttribute("disabled");
	}
}

function LootTableAjaxGetID() {
	let e = document.getElementById("lt_txtSearch");
	//Find the selected item type and id via regex
	const pat = /.*\((\d+)\)$/;
	const m = e.value.match(pat);
	return m[1];
}

function LootTableAjaxSelect() {
	window.location.search = "?page=loot_table&id=" + LootTableAjaxGetID();
}

function LootTableAjaxSelectSpawnLoot() {
	let e = document.getElementById("lt_txtSearch");
	e.value = LootTableAjaxGetID();
}

function LootTableLookupAJAX(bSpawnLoot = false) {
	if (searchReq.readyState == 4 || searchReq.readyState == 0) {
		let str = escape(document.getElementById('lt_txtSearch').value);
		if (str.length < 3) {
			let ss = document.getElementById('lt_search_suggest')
			ss.innerHTML = '';
			return;
		}

		searchReq.open("GET", '../ajax/eq2Ajax.php?type=luLt&search=' + str, true);
		searchSuggestDiv = "lt_search_suggest";
		txtSearchAjaxInput = "lt_txtSearch";
		searchReq.onreadystatechange = handleSearchSuggest;
		if (bSpawnLoot) {
			ajaxSelectCallback = LootTableAjaxSelectSpawnLoot;;
		}
		else {
			ajaxSelectCallback = LootTableAjaxSelect;
		}
		searchReq.send(null);
	}		
}