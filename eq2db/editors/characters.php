<?php 
define('IN_EDITOR', true);
include("header.php");
//SECURITY
if ( !$eq2->CheckAccess(M_CHARACTERS) )
	die("Access denied!");
include("../class/eq2.zones.php");
include("../class/eq2.characters.php");

//GLOBALS
$charClass = new eq2Characters;
$id = (isset($_GET['id'])?$_GET['id']:"");

//??
if( $id != "") 
{
	if( isset($_POST['swapSpells']) ) $eq2->swapPlayerSpellSet();
	if( isset($_POST['csDelete']) ) $eq2->deletePlayerSpellSet($_POST['char_id']);
}

//FORM PROCESSING
switch(strtolower($_POST['cmd'] ?? "")) {
	case "insert": $eq2->ProcessInserts(); break;
	case "update": $eq2->ProcessUpdates(); break;
	case "delete": $eq2->ProcessDeletes(); break;
}

//PAGE START
pageSetup();
include("footer.php");
exit; // end of page

//PAGE FUNCTIONS
function pageSetup() {
	global $eq2, $charName, $charClass, $id;
	$link = sprintf("%s?id=%d",$_SERVER['SCRIPT_NAME'],$id);
	$page = (isset($_GET['page']) ? $_GET['page'] : "");
	
	$strHTML = "";
	$strHTML .= "<div id='Editor'>\n";
	$strHTML .= "  <table class='SubPanel' cellpadding='0' width='100%'>\n";
	$strHTML .= "    <tr>\n";
	$strHTML .= "      <td class='Title' colspan='2'>Character Data</td>\n";
	$strHTML .= "    </tr>\n";
	$strHTML .= "    <tr>\n";
	$strHTML .= "      <td valign='top'>\n";
	if($id != "" AND $page != "search")
	{
		$strHTML .= $charClass->GenerateNavigationMenu();
	}
	$strHTML .= "      </td>\n";
	$strHTML .= "      <td width='100%' valign='top'>\n";
	$strHTML .= "        <table class='SectionMainFloat' cellspacing='0' border='0' style='width:100%'>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td class='SectionBody'>\n";
	if($id == "")
	{
		$strHTML .= search();
	}else{
		switch($page) 
		{
			case "character_mail":
				$strHTML .= character_mail($_GET['id']);
				break;
			case "character_buyback":
				$strHTML .= character_buyback($_GET['id']);
				break;
			case "character_template":
				$strHTML .= character_template($_GET['id']);
				break;
			case "character_macros":
				$strHTML .= character_macros($_GET['id']);
				break;
			case "character_spells":
				$strHTML .= character_spells();
				break;
			case "character_skills":
				$strHTML .= character_skills();
				break;
			case "character_skillbar":
				$strHTML .= character_skillbar();
				break;
			case "character_quest_progress":
				$strHTML .= character_quest_progress();
				break;
			case "character_quests":
				$strHTML .= character_quests();
				break;
			case "character_items":
				$strHTML .= character_items();
				break;
			case "character_factions":
				$strHTML .= CharacterFactions($id); 
				break;
			case "char_colors":
				$strHTML .= character_colors();
				break;
			case "character_details":
				$strHTML .= character_details();
				break;
			case "characters":
				$strHTML .= character_main();
				break;
			case "character_overview":
				$strHTML .= character_overview();
				break;
			case "ni":
				$strHTML .= not_implimented();
				break;
			case "search":
				$strHTML .= search();
				break;
			default:
				$strHTML .= character_overview();
				break;
		}
	}
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "        </table>\n";
	$strHTML .= "      </td>\n";
	$strHTML .= "    </tr>\n";
	$strHTML .= "  </table>\n";
	$strHTML .= "</div>\n";
	print($strHTML);
}

function ajaxHTML()
{
	$eq2AjaxType = "luCh";

	$strHTML = "";
	$strHTML .= "<script>\n";
	$strHTML .= "  function CharAjaxSelect() {\n";
	$strHTML .= "    let e = document.getElementById(\"txtSearch\");\n";
	$strHTML .= "    //Find the selected character id via regex\n";
	$strHTML .= "    const id_pat = / \((\d+)\)$/;";
	$strHTML .= "    const m = e.value.match(id_pat);\n";
	$strHTML .= "    window.location.search = \"?id=\" + m[1];\n";
	$strHTML .= "  }\n";
	$strHTML .= "  \n";
	$strHTML .= "  function CharLookupAJAX() {\n";
	$strHTML .= "    if (searchReq.readyState == 4 || searchReq.readyState == 0) {\n";
	$strHTML .= "      var str = escape(document.getElementById('txtSearch').value);\n";
	$strHTML .= "      if (str.length == 0) {\n";
	$strHTML .= "        let ss = document.getElementById('search_suggest')\n";
	$strHTML .= "        ss.innerHTML = '';\n";
	$strHTML .= "        return;\n";
	$strHTML .= "      }\n";
	$strHTML .= "      searchReq.open(\"GET\", '../ajax/eq2Ajax.php?type=" . $eq2AjaxType . "&search=' + str, true);\n";
	$strHTML .= "      searchReq.onreadystatechange = handleSearchSuggest;\n";
	$strHTML .= "      ajaxSelectCallback = CharAjaxSelect;\n";
	$strHTML .= "      searchReq.send(null);\n";
	$strHTML .= "    }\n";
	$strHTML .= "  }\n";
	$strHTML .= "</script>\n";
	return($strHTML);
}

function search()
{
	global $eq2;
	$search_data = array();
	$searchForm = (isset($_GET['form'])?$_GET['form']:"");
	$searchType = (isset($_GET['type'])?$_GET['type']:"");
	$searchAction = (isset($_GET['action'])?$_GET['action']:"");
	$searchID = (isset($_GET['id'])?$_GET['id']:"");

	switch($searchType)
	{
		case "acct":
			$account_query = "SELECT id, name FROM `" . LOGIN_DB . "`.`account` WHERE id IN (SELECT DISTINCT(account_id) FROM `" . ACTIVE_DB . "`.`characters`) ORDER BY name ASC"; 
			$search_data = $eq2->RunQueryMulti($account_query);
			break;

		default:
			break;
	}

	$strHTML = "";
	$strHTML .= "<fieldset width='100%'>\n";
	$strHTML .= "  <legend>Search/List By:</legend>\n";
	$strHTML .= "  <table width='100%'>\n";
	$strHTML .= "    <tr>\n";
	$strHTML .= "      <td>\n";
	$strHTML .= "        <button onclick=\"location.href='characters.php?page=search&action=search&type=name&form=txt'\">Name</button>\n";
	$strHTML .= "        <button onclick=\"location.href='characters.php?page=search&action=search&type=acct&form=sel'\">Acount</button>\n";
	$strHTML .= "        <button onclick=\"location.href='characters.php?page=search&action=search&type=online&form=sel'\">Online</button>\n";
	$strHTML .= "        <button onclick=\"location.href='characters.php?page=search&action=list&type=all&form=sel'\">All</button>\n";
	$strHTML .= "      </td>\n";
	$strHTML .= "      <td align='right'>\n";
	$strHTML .= "        <button onclick=\"location.href='characters.php'\">New</button>\n";
	$strHTML .= "      </td>\n";
	$strHTML .= "    </tr>\n";
	switch($searchForm)
	{
		case "txt":
			$strHTML .= ajaxHTML();
			$strHTML .= "    <tr>\n";
			$strHTML .= "      <td colspan='2'>\n";
			$charName = $eq2->GetCharacterNameByID($_GET['id'] ?? 0);
			$strHTML .= "              <input type='text' id='txtSearch' name='txtSearch' onkeyup='CharLookupAJAX();' autocomplete='off' class='medium' value='" . $charName . "'/>\n";
			$strHTML .= "              <div id='search_suggest'></div>\n";
			$strHTML .= "      </td>\n";
			$strHTML .= "    </tr>\n";
			break;
		case "sel":
			$strHTML .= "    <tr>\n";
			$strHTML .= "      <td colspan='2'>\n";
			$strHTML .= "            <select name='zoneID' onchange='dosub(this.options[this.selectedIndex].value)'>\n";
			$strHTML .= "              <option>Make Selection</option>\n";
			if(is_array($search_data))
			{
				foreach($search_data as $option)
				{
					$isSelected = ($option['id']==$searchID?"selected":"");
					$strHTML .= "      <option value='characters.php?page=search&action=ListBy" . ucfirst($searchType) . "&type=" . $searchType . "&form=sel&id=" . $option['id'] . "' " . $isSelected . ">" . $option['name'] ." (" . $option['id'] . ")</option>\n";
				}
			}
			$strHTML .= "            <select>\n";
			$strHTML .= "      </td>\n";
			$strHTML .= "    </tr>\n";
			break;
		default:
			break;
	}
	switch($searchAction)
	{
		case "list":
			if($searchType == "all")
			{
				$list_query = "SELECT id, name, race, class, level FROM `" . ACTIVE_DB . "`.characters ORDER BY name ASC";
				$list_data = $eq2->RunQueryMulti($list_query);
				if(!is_array($list_data))
					$list_data = array();

				$strHTML .= "    <tr>\n";
				$strHTML .= "      <td>\n";
				$strHTML .= "        <table class='ContrastTable'>\n";
				$strHTML .= "          <tr>\n";
				$strHTML .= "            <th>ID</th>\n";
				$strHTML .= "            <th>Name</th>\n";
				$strHTML .= "            <th>Race</th>\n";
				$strHTML .= "            <th>Class</th>\n";
				$strHTML .= "            <th>Level</th>\n";
				$strHTML .= "            <th></th>\n";
				$strHTML .= "          </tr>\n";
				foreach($list_data as $list)
				{
					$strHTML .= "          <tr>\n";
					$strHTML .= "            <td>\n";
					$strHTML .= "              " . $list['id'] . "\n";
					$strHTML .= "            </td>\n";
					$strHTML .= "            <td>\n";
					$strHTML .= "              " . $list['name'] . "\n";
					$strHTML .= "            </td>\n";
					$strHTML .= "            <td>\n";
					$strHTML .= "              " . $list['race'] . "\n";
					$strHTML .= "            </td>\n";
					$strHTML .= "            <td>\n";
					$strHTML .= "              " . $list['class'] . "\n";
					$strHTML .= "            </td>\n";
					$strHTML .= "            <td>\n";
					$strHTML .= "              " . $list['level'] . "\n";
					$strHTML .= "            </td>\n";
					$strHTML .= "            <td>\n";
					$strHTML .= "              <button onClick=\"location.href='characters.php?page=overview&id=" . $list['id'] . "'\">View</button>\n";
					$strHTML .= "            </td>\n";
					$strHTML .= "          </tr>\n";
				}
				$strHTML .= "        </table>\n";
				$strHTML .= "      </td>\n";
				$strHTML .= "    </tr>\n";
			}
			break;

		case "ListByAcct":
			$list_query = "SELECT id, name, race, class, level FROM `" . ACTIVE_DB . "`.characters WHERE account_id = " . $searchID;
			$list_data = $eq2->RunQueryMulti($list_query);

			$strHTML .= "    <tr>\n";
			$strHTML .= "      <td>\n";
			$strHTML .= "        <table class='ContrastTable'>\n";
			$strHTML .= "          <tr>\n";
			$strHTML .= "            <th>ID</th>\n";
			$strHTML .= "            <th>Name</th>\n";
			$strHTML .= "            <th>Race</th>\n";
			$strHTML .= "            <th>Class</th>\n";
			$strHTML .= "            <th>Level</th>\n";
			$strHTML .= "            <th></th>\n";
			$strHTML .= "          </tr>\n";
			foreach($list_data as $list)
			{
				$strHTML .= "          <tr>\n";
				$strHTML .= "            <td>\n";
				$strHTML .= "              " . $list['id'] . "\n";
				$strHTML .= "            </td>\n";
				$strHTML .= "            <td>\n";
				$strHTML .= "              " . $list['name'] . "\n";
				$strHTML .= "            </td>\n";
				$strHTML .= "            <td>\n";
				$strHTML .= "              " . $list['race'] . "\n";
				$strHTML .= "            </td>\n";
				$strHTML .= "            <td>\n";
				$strHTML .= "              " . $list['class'] . "\n";
				$strHTML .= "            </td>\n";
				$strHTML .= "            <td>\n";
				$strHTML .= "              " . $list['level'] . "\n";
				$strHTML .= "            </td>\n";
				$strHTML .= "            <td>\n";
				$strHTML .= "              <button onClick=\"location.href='characters.php?page=overview&id=" . $list['id'] . "'\">View</button>\n";
				$strHTML .= "            </td>\n";
				$strHTML .= "          </tr>\n";
			}
			$strHTML .= "        </table>\n";
			$strHTML .= "      </td>\n";
			$strHTML .= "    </tr>\n";
			break;
	}
	$strHTML .= "  </table>\n";
	$strHTML .= "</fieldset>\n";
	return($strHTML);
}




function character_overview()
{
	global $eq2, $charClass, $link;
	$charID = (isset($_GET['id'])?$_GET['id']:"");
	$char_query = "SELECT * FROM `" . ACTIVE_DB . "`.`characters` WHERE id = " . $charID;
	$char_data = $eq2->RunQuerySingle($char_query);
	$detail_query = "SELECT * FROM `" . ACTIVE_DB . "`.`character_details` WHERE char_id = " . $charID;
	$detail_data = $eq2->RunQuerySingle($detail_query);
	$guild_query = "SELECT g.name as name,";
	$guild_query .= "       g.level as level";
	$guild_query .= " FROM `" . ACTIVE_DB . "`.`guilds` as g";
	$guild_query .= " JOIN `" . ACTIVE_DB . "`.`guild_members` as gm";
	$guild_query .= " ON g.id = gm.guild_id";
	$guild_query .= " WHERE gm.char_id = " . $charID;
	$guild_data = $eq2->RunQuerySingle($guild_query);
	$quest_query = "SELECT COUNT(id) as count FROM `" . ACTIVE_DB . "`.`character_quests` WHERE char_id = " . $charID . " AND completed_date IS NOT NULL";
	$quest_data = $eq2->RunQuerySingle($quest_query);
	$collection_query = "SELECT COUNT(id) as count FROM `" . ACTIVE_DB . "`.`character_collections` WHERE char_id = " . $charID . " AND completed = 1";
	$collection_data = $eq2->RunQuerySingle($collection_query);

	$strHTML = "";
	$strHTML .= "      <fieldset>\n";
	$strHTML .= "        <legend>Overview</legend>\n";
	$strHTML .= "        <table border='0'>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td colspan='2'>\n";
	$strHTML .= $eq2->ReturnTabGenerator($_GET['tab'] ?? 'characters', $charClass->GetTabArray(), $link);
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td style='width:300px'>\n";
	$strHTML .= "            <div style='width:300px;height:640px;overflow-y:scroll;overflow-x:scroll'>\n";
	//START LEFT COLUMN
	$strHTML .= "              <table border='0' class='ContrastTable'>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>" . $char_data['name'] . "<br><i>" . (is_array($guild_data)?$guild_data['name']:'') . "</i></th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Advanture:(Lvl - " . $char_data['level'] . ")</td>\n";
	$strHTML .= "                  <td align='center'>\n";
	$strHTML .= "                    <progress id='xp' value='" . $detail_data['xp'] ."' min='0' max='" . $detail_data['xp_needed'] . "'></progress><br>[" . $detail_data['xp'] ."/" . $detail_data['xp_needed'] . "]\n";
	$strHTML .= "                  </td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Tradeskill:(Lvl - " . $char_data['tradeskill_level'] . ")</td>\n";
	$strHTML .= "                  <td align='center'>\n";
	$strHTML .= "                    <progress id='xp' value='" . $detail_data['tradeskill_xp'] ."' min='0' max='" . $detail_data['tradeskill_xp_needed'] . "'>test</progress><br>[" . $detail_data['tradeskill_xp'] ."/" . $detail_data['tradeskill_xp_needed'] . "]\n";
	$strHTML .= "                  </td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>AAs Data</td>\n";
	$strHTML .= "                </tr>\n";
	//GENERAL  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2' >General</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2'  align='center'>Level: (" . $char_data['level'] . ") " . $charClass->getAdventureClassNameByID($char_data['class']) . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>Level: (" . $char_data['tradeskill_level'] . ") " . $charClass->getTradeskillClassNameByID($char_data['tradeskill_class']) . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Health:</td>\n";
	$strHTML .= "                  <td align='right'>" . $detail_data['hp'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td align='right'>OoC Regen: </td>\n";
	$strHTML .= "                  <td align='right'>(hp)</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Power:</td>\n";
	$strHTML .= "                  <td align='right'>" . $detail_data['power'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td align='right'>OoC Regen: </td>\n";
	$strHTML .= "                  <td align='right'>(pwr)</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Run Speed:</td>\n";
	$strHTML .= "                  <td align='right'>(speed)</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Status:</td>\n";
	$strHTML .= "                  <td align='right'>" . $detail_data['status_points'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	//ATTRIBUITES  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>Attributes</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Stringth:</td>\n";
	$strHTML .= "                  <td align='right'>" . $detail_data['str'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Agility:</td>\n";
	$strHTML .= "                  <td align='right'>" . $detail_data['agi'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Intelligence:</td>\n";
	$strHTML .= "                  <td align='right'>" . $detail_data['intel'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Wisdom:</td>\n";
	$strHTML .= "                  <td align='right'>" . $detail_data['wis'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Stamina:</td>\n";
	$strHTML .= "                  <td align='right'>" . $detail_data['sta'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	//DEFENCE  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>Defense</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Elemental:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Noxious:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Arcane:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Mitigation (vs Phy):</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Avoidance:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Uncontested Block:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Block Chance:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	//OFFENSE  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>Offense</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Chit Chance:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Crit Bonus:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Potency:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Fervor:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Resolve:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Ability Mod:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Hate Mod:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                  <td>Reuse Speed:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Casting Speed:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Recovery Speed:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Spell Reuse Speed:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Spell Doublecast:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Ability Doublecast:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	//PVP STATS  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>PVP Stats</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Toughness:</td>\n";
	$strHTML .= "                  <td align='right'>[??]</td>\n";
	$strHTML .= "                </tr>\n";
	//AUTOATTACK  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>Auto Attack</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Attack Speed (Haste):</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>AOE Auto Attack:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Accuracy:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>StrikeThrough:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>MultiAttack:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>DPS Modifier:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Flurry:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>(P) Weapon Damage Bonus:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>(S) Weapon Damage Bonus:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>Primary</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], 0)) . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Damage:</td>\n";
	$strHTML .= "                  <td>(0,000,000-0,000,000)</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Delay:</td>\n";
	$strHTML .= "                  <td>(0.0 sec)</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>Secondary</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], 1)) . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>Ranged</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], 16)) . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Damage:</td>\n";
	$strHTML .= "                  <td>(0,000,000-0,000,000)</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Delay:</td>\n";
	$strHTML .= "                  <td>(0.0 sec)</td>\n";
	$strHTML .= "                </tr>\n";
	//TRADESKILLS  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>TradeSkills</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Rare Harvest Chances:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	//PvE Rankings  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>PVE Rankings</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Kills:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Deaths:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>K:D Ratio:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Max Malee Hit</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Max Magic Hit:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	//Housing  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>Housing</th>\n";
	$strHTML .= "                </tr>\n";
	$houses = $charClass->getHousesByCharID($charID);
	foreach($houses as $house)
	{
		$strHTML .= "                   <td colspan='2'>" . $charClass->getHouseNamebyID($house['id']) . "</td>";
	}
	$strHTML .= "                </tr>\n";
	//Misc.  
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <th colspan='2'>Misc</th>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td colspan='2' align='center'>" . $char_data['name'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Birthdate:</td>\n";
	$strHTML .= "                  <td align='right'>" . $char_data['created_date']. "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Played:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Server:</td>\n";
	$strHTML .= "                  <td align='right'>" . $char_data['server_id']. "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Gender:</td>\n";
	$strHTML .= "                  <td align='right'>" . $char_data['gender']. "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Race:</td>\n";
	$strHTML .= "                  <td align='right'>" . $char_data['race']. "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Deity:</td>\n";
	$strHTML .= "                  <td align='right'>" . $char_data['deity']. "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Starting City:</td>\n";
	$strHTML .= "                  <td align='right'>" . $char_data['starting_city']. "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>CtH:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Guild:</td>\n";
	$strHTML .= "                  <td align='right'>" . (is_array($guild_data)?$guild_data['name']:"") . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Guild Level:</td>\n";
	$strHTML .= "                  <td align='right'>" . (is_array($guild_data)?$guild_data['level']:"") . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Current Zone:</td>\n";
	$strHTML .= "                  <td align='right'>" . $eq2->getZoneNameByID($char_data['current_zone_id']) . "(" . $char_data['current_zone_id'] . ")</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Quests Completed:</td>\n";
	$strHTML .= "                  <td align='right'>" . $quest_data['count'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Collections Completed:</td>\n";
	$strHTML .= "                  <td align='right'>" . $collection_data['count'] . "</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Items Crafted:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Rare Harvests:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$strHTML .= "                  <td>Updated:</td>\n";
	$strHTML .= "                  <td align='right'>()</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "              </div>\n";
	$strHTML .= "            </table>\n";
	//END LEFT COLUMN
	$strHTML .= "            </td>\n";
	$strHTML .= "            <td width='*'>\n";
	//START RIGHT COLUMN
	$strHTML .= "            <div style='height:640px;overflow-y:scroll;overflow-x:scroll'>\n";
	$strHTML .= "              <table border='0' class='ContrastTable' width='100%'>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 0; //PRIMARY
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 1;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 2;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 3;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 4;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 5;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 6;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 7;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 8;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 9;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 10;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 11;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 12;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 13;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 14;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 15;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 16;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 17;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 18;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 19;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 20;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 21;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "                <tr>\n";
	$slot_id = 22;
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"left") . "</td>\n";
	$strHTML .= "                  <td>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td>\n";
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$slot_id = 23;
	$strHTML .= "                  <th>" . $charClass->getSlotNameByNum($slot_id) . "</th>\n";
	$strHTML .= "                  <td align='right'>" . ucwords($charClass->getItemNameBySlot($char_data['id'], $slot_id)) . "</td></td>\n";
	$strHTML .= "                  <td>" . $charClass->getItemIconBySlot($char_data['id'], $slot_id,"right") . "</td>\n";
	$strHTML .= "                  <td>*<br>*</td>\n";
	$strHTML .= "                </tr>\n";
	$strHTML .= "              </table>\n";
	$strHTML .= "            </div>\n";
	//END RIGHT COLUMN
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "        </table>\n";
	$strHTML .= "      </fieldset>\n";
	return($strHTML);
}

function character_spells()
{
	global $eq2, $id, $objectName, $charClass, $charName;
	$query=sprintf("select c.id as cid,
							c.class,
							c.name as cname,
							cs.* 
					from `".ACTIVE_DB."`.characters c 
					left join `".ACTIVE_DB."`.character_spells 
					cs on c.id = cs.char_id where c.id = %d",$id);
	$result=$eq2->db->sql_query($query);
	$data1=$eq2->db->sql_fetchrow($result);
	$class_id = $data1['class'];
	$char_id = $data1['cid'];
	$objectName = $data1['cname'];
	$table = 'character_spells';

	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "  <tr>\n";
	$strHTML .= "    <td width='680' valign='top'>\n";
	$strHTML .= "      <fieldset>\n";
	$strHTML .= "        <legend>Spells</legend>\n";
	$strHTML .= "        <table width='100%' border='0'>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td colspan='6'>\n";
	$strHTML .= "              <span class='heading'>Editing:" . $objectName . "</span><br />\n";
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td>id</td>\n";
	$strHTML .= "            <td>char_id</td>\n";
	$strHTML .= "            <td>spell_id</td>\n";
	$strHTML .= "            <td>tier</td>\n";
	$strHTML .= "            <td>knowledge_slot</td>\n";
	$strHTML .= "            <td colspan='2'>&nbsp;</td>\n";
	$strHTML .= "          </tr>\n";

	$char_spells_query = "SELECT * FROM `" . ACTIVE_DB . "`.`" . $table . "` WHERE char_id = " . $id;
	$char_spells_data = $eq2->RunQueryMulti($char_spells_query);
	//$query=sprintf("select * from %s where char_id = %d", $table, $id);
	//$result=$eq2->db->sql_query($query);
	foreach ($char_spells_data as $data)
	{
		$strHTML .= "          <form method='post' name='multiForm|" . $data['id'] . "' />\n";
		$strHTML .= "            <tr>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_spells|id' value='" . $data['id'] . "'  style='width:40px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "                <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "                <input type='hidden' name='orig_object' value='" .  $charName . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_spells|char_id' value='" . $data['char_id'] . "'  style='width:40px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_spells|spell_id' value='" . $data['spell_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                  " . $charClass->getSpellNameByID($data['spell_id']) . "\n";;
		$strHTML .= "                <input type='hidden' name='orig_spell_id' value='" . $data['spell_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_spells|tier' value='" . $data['tier'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_tier' value='" . $data['tier'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_spells|knowledge_slot' value='" . $data['knowledge_slot'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_knowledge_slot' value='" . $data['knowledge_slot'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		if($eq2->CheckAccess(G_DEVELOPER)) {
			$strHTML .= "                <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= "                <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
		}
		$strHTML .= "              </td>\n";
		$strHTML .= "            </tr>\n";
		$strHTML .= "            <input type='hidden' name='orig_object' value='" .  $objectName . "' />\n";
		$strHTML .= "            <input type='hidden' name='table_name' value='" .  $table . "' />\n";
		$strHTML .= "          </form>\n";
	}
	if( $eq2->CheckAccess(G_SUPERADMIN) )
	{
		$strHTML .= "          <form method='post' name='swapForm' />\n";
		$strHTML .= "            <tr>\n";
		$strHTML .= "              <td colspan='6' valign='bottom'>\n";
		$strHTML .= "                <select name='class_id'>\n";
		$eq2->GetClasses($class_id);
		$strHTML .= "                </select>&nbsp;\n";
		$strHTML .= "                <input type='checkbox' name='is-live' value='1' checked='checked' title='Only set spells that have been validated.' />\n";
		$strHTML .= "                <input type='submit' name='swapSpells' value='Set' title='This sets the character Knowledge entries to the selected class.' style='width:100px;' />&nbsp;\n";
		$strHTML .= "                <input type='submit' name='csDelete' value='Purge Book' title='This will erase all Knowledge entries from this character.' style='width:100px;' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "            </tr>\n";
		$strHTML .= "            <input type='hidden' name='char_id' value='" . $char_id . "' />\n";
		$strHTML .= "          </form>\n";
	}
	$strHTML .= "        </table>\n";
	$strHTML .= "      </fieldset>\n";
	$strHTML .= "    </td>\n";
	$strHTML .= "  </tr>\n";
	$strHTML .= "  <tr>\n";
	$strHTML .= "    <td>\n";
	$strHTML .= "      <strong>Usage:</strong><br />\n";
	$strHTML .= "      <ul>\n";
	$strHTML .= "        <li>Select the Class of spells to push to the players Knowledge book.</li>\n";
	$strHTML .= "        <li>The checkmark active will only load spell data that has been validated for general availability (\"Release\").</li>\n";
	$strHTML .= "        <li>Clear the checkmark to load every spell for that classes archetype into the Knowledge book of the current player.</li>\n";
	$strHTML .= "        <li>The Set button performs a Knowledge book purge, then fill with the selected class data.</li>\n";
	$strHTML .= "        <li>Purge will only delete all the players Knowledge data.</li>\n";
	$strHTML .= "      </ul>\n";
	$strHTML .= "    </td>\n";
	$strHTML .= "  </tr>\n";
	$strHTML .= "</table>\n";

	return($strHTML);
}


function character_skills()
{
	global $eq2, $id, $charClass, $charName;
	$strHTML = "";

	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "  <tr>\n";
	$strHTML .= "    <td width='100%' valign='top'>\n";
	$strHTML .= "      <fieldset>\n";
	$strHTML .= "        <legend>Skills</legend> \n";
	$strHTML .= "        <table width='100%' border='0' class='ContrastTable'>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td colspan='7'>\n";
	$strHTML .= "              <span class='heading'>Editing:" . $charName  ."</span><br />\n";
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td>ID</td>\n";
	$strHTML .= "            <td>skill_id</td>\n";
	$strHTML .= "            <td>current_val</td>\n";
	$strHTML .= "            <td>max_val</td>\n";
	$strHTML .= "            <td>progress</td>\n";
	$strHTML .= "            <td colspan='2'>&nbsp;</td>\n";
	$strHTML .= "          </tr>\n";

	$skills_query = "SELECT cs.*, ";
	$skills_query .= "        s.skill_type as skill_type ";
	$skills_query .= " FROM `".ACTIVE_DB."`.`character_skills` AS cs ";
	$skills_query .= " JOIN `".ACTIVE_DB."`.`skills` AS s ";
	$skills_query .= " ON cs.skill_id = s.id ";
	$skills_query .= " WHERE char_id = " . $id;
	$skills_query .= " ORDER BY s.skill_type, s.id";
	$skills_data=$eq2->RunQueryMulti($skills_query);
	$lastType = "";
	foreach($skills_data as $skill)
	{
		if($lastType != $skill['skill_type'])
		{
			$strHTML .= "          <tr>\n";
			$strHTML .= "            <th colspan='7'>" . $charClass->getSkillTypeNameByID($skill['skill_type']) . "(" . $skill['skill_type'] . ")</th>\n";
			$strHTML .= "          </tr>\n";
			$lastType = $skill['skill_type'];
		}
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <form method='post' name='multiForm|" . $skill['id'] . "' />\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skills|id' value='" . $skill['id'] . "'  style='width:50px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "                <input type='hidden' name='orig_id' value='" . $skill['id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skills|skill_id' value='" . $skill['skill_id'] . "' style='width:100px;'>\n";
		$strHTML .= "                " . $charClass->getSkillNameByID($skill['skill_id']) . "\n";
		$strHTML .= "                <input type='hidden' name='orig_skill_id' value='" . $skill['skill_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skills|current_val' value='" . $skill['current_val'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_current_val' value='" . $skill['current_val'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skills|max_val' value='" . $skill['max_val'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_max_val' value='" . $skill['max_val'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <progress id='skill_progress' value='" . $skill['current_val'] . "' min='0' max='" . $skill['max_val'] . "'></progress>\n";
		//$strHTML .= "                <input type='text' name='character_skills|progress' value='" . $skill['progress'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_progress' value='" . $skill['progress'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		if($eq2->CheckAccess(G_DEVELOPER))
		{  
			$strHTML .= "                <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= "                <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
		}  
		$strHTML .= "                <input type='hidden' name='orig_char_id' value='" . $skill['char_id'] . "' />\n";
		$strHTML .= "                <input type='hidden' name='orig_object' value='" . $charName . "' />\n";
		$strHTML .= "                <input type='hidden' name='table_name' value='character_skills' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "            </form>\n";
		$strHTML .= "          </tr>\n";
			}
	$strHTML .= "        </table>\n";
	$strHTML .= "      </fieldset>\n";
	$strHTML .= "    </td>\n";
	$strHTML .= "  </tr>\n";
	$strHTML .= "</table>\n";

	return($strHTML);
}

function character_skillbar()
{
	global $eq2, $id, $charName;
	$query=sprintf('select * from `'.ACTIVE_DB.'`.character_skillbar where character_id = %d',$id);
	$result=$eq2->db->sql_query($query);
	$data=$eq2->db->sql_fetchrow($result);

	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "  <tr>\n";
	$strHTML .= "    <td width='680' valign='top'>\n";
	$strHTML .= "      <fieldset style='width:675px;'>\n";
	$strHTML .= "        <legend>Skill Bar</legend>\n";
	$strHTML .= "        <table width='100%' border='0'>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td colspan='6'>\n";
	$strHTML .= "              <span class='heading'>Editing:" . $charName . "</span><br />\n";
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td>id</td>\n";
	$strHTML .= "            <td>char_id</td>\n";
	$strHTML .= "            <td>type</td>\n";
	$strHTML .= "            <td>hotbar</td>\n";
	$strHTML .= "            <td>spell_id</td>\n";
	$strHTML .= "            <td>slot</td>\n";
	$strHTML .= "            <td>text_val</td>\n";
	$strHTML .= "            <td colspan='2'>&nbsp;</td>\n";
	$strHTML .= "          </tr>\n";

	$query=sprintf('select * from `'.ACTIVE_DB.'`.character_skillbar where char_id = %d',$id);
	$result=$eq2->db->sql_query($query);
	while($data=$eq2->db->sql_fetchrow($result)) 
	{
		$strHTML .= "          <form method='post' name='multiForm|" . $data['id'] . "' />\n";
		$strHTML .= "            <tr>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skillbar|id' value='" . $data['id'] . "'  style='width:50px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "                <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "                <input type='hidden' name='orig_object' value='" . $charName . " />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skillbar|char_id' value='" . $data['char_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skillbar|type' value='" . $data['type'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_type' value='" . $data['type'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skillbar|hotbar' value='" . $data['hotbar'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_hotbar' value='" . $data['hotbar'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skillbar|spell_id' value='" . $data['spell_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_spell_id' value='" . $data['spell_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skillbar|slot' value='" . $data['slot'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_slot' value='" . $data['slot'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_skillbar|text_val' value='" . $data['text_val'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_text_val' value='" . $data['text_val'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		if($eq2->CheckAccess(G_DEVELOPER))
		{
			$strHTML .= "                <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= "                <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
		}
		$strHTML .= "              </td>\n";
		$strHTML .= "            </tr>\n";
		$strHTML .= "          </form>\n";
	}
	$strHTML .= "</table>\n";
	$strHTML .= "</fieldset>\n";
	$strHTML .= "</td>\n";
	$strHTML .= "</tr>\n";
	$strHTML .= "</table>\n";

	return($strHTML);
}


function character_quest_progress()
{
	global $eq2, $id, $charName;
	$table = 'character_quest_progress';
	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "  <tr>\n";
	$strHTML .= "    <td width='680' valign='top'>\n";
	$strHTML .= "      <fieldset style='width:675px;'>\n";
	$strHTML .= "        <legend>Quest Progress</legend>\n";
	$strHTML .= "        <table width='100%' border='0'>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td colspan='6'>\n";
	$strHTML .= "              <span class='heading'>Editing:" . $eq2->GetCharacterNameByID($_GET['id']) . "</span><br />\n";
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td>id</td>\n";
	$strHTML .= "            <td>char_id</td>\n";
	$strHTML .= "            <td>quest_id</td>\n";
	$strHTML .= "            <td>step_id</td>\n";
	$strHTML .= "            <td>progress</td>\n";
	$strHTML .= "            <td colspan='2'>&nbsp;</td>\n";
	$strHTML .= "          </tr>\n";

	$query=sprintf('select * from `%s`.`%s` where char_id = %d',ACTIVE_DB,$table,$id);
	$result = $eq2->RunQueryMulti($query);
	foreach ($result as $data)
	{
		$strHTML .= "          <form method='post' name='multiForm|" . $data['id'] . "'>\n";
		$strHTML .= "            <tr>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quest_progress|id' value='" . $data['id'] . "'  style='width:50px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "                <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quest_progress|char_id' value='" . $data['char_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quest_progress|quest_id' value='" . $data['quest_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_quest_id' value='" . $data['quest_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quest_progress|step_id' value='" . $data['step_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_step_id' value='" . $data['step_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quest_progress|progress' value='" . $data['progress'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_progress' value='" . $data['progress'] . "' />\n";
		$strHTML .= "              </td>\n";
		if($eq2->CheckAccess(G_DEVELOPER))
		{
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= "                <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
			$strHTML .= "              </td>\n";
		}
		$strHTML .= "            </tr>\n";
		$strHTML .= "            <input type='hidden' name='orig_object' value='' />\n";
		$strHTML .= "            <input type='hidden' name='table_name' value='" . $table . "' />\n";
		$strHTML .= "          </form>\n";
	}
	$strHTML .= "        </table>\n";
	$strHTML .= "      </fieldset>\n";
	$strHTML .= "    </td>\n";
	$strHTML .= "  </tr>\n";
	$strHTML .= "</table>\n";

	return($strHTML);
}

function character_quests()
{
	global $eq2, $id, $charName, $charClass;
	$table= 'character_quests';
	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "  <tr>\n";
	$strHTML .= "    <td width='680' valign='top'>\n";
	$strHTML .= "      <fieldset style='width:675px;'>\n";
	$strHTML .= "        <legend>Quests</legend>\n";
	$strHTML .= "        <table width='100%' border='0' class='ContrastTable'>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td colspan='7'>\n";
	$strHTML .= "              <span class='heading'>Editing:" . $eq2->GetCharacterNameByID($_GET['id']) ."</span><br />\n";
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <th>id</th>\n";
	$strHTML .= "            <th>char_id</th>\n";
	$strHTML .= "            <th>quest_id</th>\n";
	$strHTML .= "            <th>Quest Name</th>\n";
	$strHTML .= "            <th>completed_date</th>\n";
	$strHTML .= "            <th colspan='2'>&nbsp;</th>\n";
	$strHTML .= "          </tr>\n";

	$query=sprintf('select * from `%s`.%s where char_id = %d',ACTIVE_DB,$table,$id);
	$result = $eq2->RunQueryMulti($query);
	foreach ($result as $data)
	{
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <form method='post' name='multiForm|" . $data['id'] . "'>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quests|id' value='" . $data['id'] . "'  style='width:50px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "                <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quests|char_id' value='" . $data['char_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quests|quest_id' value='" . $data['quest_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_quest_id' value='" . $data['quest_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                " . $charClass->getQuestNameByID($data['quest_id']) . "\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_quests|completed_date' value='" . $data['completed_date'] . "'  style='width:50px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_completed_date' value='" . $data['completed_date'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		if($eq2->CheckAccess(G_DEVELOPER))
		{
			//$strHTML .= "                <input type='hidden' name='orig_object' value='" .  $objectName . "' />\n";
			$strHTML .= "                <input type='hidden' name='table_name' value='" .  $table . "' />\n";
			$strHTML .= "                <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= "                <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
		}
		$strHTML .= "              </td>\n";
		$strHTML .= "            </form>\n";
		$strHTML .= "          </tr>\n";
	}
	$strHTML .= "        </table>\n";
	$strHTML .= "      </fieldset>\n";
	$strHTML .= "    </td>\n";
	$strHTML .= "  </tr>\n";
	$strHTML .= "</table>\n";

	return($strHTML);
}


function character_items()
{
	global $eq2, $id, $charName;
	$query=sprintf('select * from `'.ACTIVE_DB.'`.character_items where char_id = %d',$id);
	$result=$eq2->db->sql_query($query);
	$data=$eq2->db->sql_fetchrow($result);
	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "<tr>\n";
	$strHTML .= "<td valign='top'>\n";
	$strHTML .= "<fieldset>\n";
	$strHTML .= "<legend>Items [<b>" . $charName . "</b> (" . $id . ")]</legend>\n";
	$strHTML .= "<table width='100%' border='0'  class='ContrastTable'>\n";
	$strHTML .= "<tr>\n";
	$strHTML .= "  <th>unique_id</th>\n";
	$strHTML .= "  <th>type</th>\n";
	$strHTML .= "  <th>char_id</th>\n";
	$strHTML .= "  <th>bag_slot</th>\n";
	$strHTML .= "  <th>slot</th>\n";
	$strHTML .= "  <th>item_id</th>\n";
	$strHTML .= "  <th>creator</th>\n";
	$strHTML .= "  <th>condition_</th>\n";
	$strHTML .= "  <th>attuned</th>\n";
	$strHTML .= "  <th>bag_id</th>\n";
	$strHTML .= "  <th>count</th>\n";
	$strHTML .= "  <th colspan='2'>&nbsp;</th>\n";
	$strHTML .= "</tr>\n";

	$query=sprintf('select * from `'.ACTIVE_DB.'`.character_items where char_id = %d',$id);
	$result=$eq2->db->sql_query($query);
	while($data=$eq2->db->sql_fetchrow($result))
	{
		$strHTML .= "<form method='post' name='multiForm|" . $data['id'] . "' />\n";
		$strHTML .= "<tr>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "  <input type='text' name='character_items|id' value='" .  $data['id'] . "' readonly style='width:50px; background-color:#ddd;' />\n";
		$strHTML .= "  <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "  <input type='hidden' name='orig_object' value='" .  $charName . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "  <input type='text' name='character_items|type' value='" . $data['type'] . "'  style='width:50px;' />\n";
		$strHTML .= "  <input type='hidden' name='orig_type' value='" . $data['type'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|char_id' value='" . $data['char_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|bag_slot' value='" . $data['bag_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_bag_slot' value='" . $data['bag_id'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|slot' value='" . $data['slot'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_slot' value='" . $data['slot'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|item_id' value='" . $data['item_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_item_id' value='" . $data['item_id'] . "' />\n";
		$strHTML .= $eq2->GenerateItemHover($data['item_id']);
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|creator' value='" . $data['creator'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_creator' value='" . $data['creator'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|condition_' value='" . $data['condition_'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_condition_' value='" . $data['condition_'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|attuned' value='" . $data['attuned'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_attuned' value='" . $data['attuned'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|bag_id' value='" . $data['bag_id'] . "'  style='width:75px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_bag_id' value='" . $data['bag_id'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_items|count' value='" . $data['count'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_count' value='" . $data['count'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		if($eq2->CheckAccess(G_DEVELOPER)) 
		{
			$strHTML .= "<input type='submit' name='ciUpdate' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= "<input type='submit' name='ciDelete' value='Delete' style='font-size:10px; width:60px' />\n";
		}
		$strHTML .= "</td>\n";
		$strHTML .= "</tr>\n";
		$strHTML .= "</form>\n";
	}
	$strHTML .= "</table>\n";
	$strHTML .= "</fieldset>\n";
	$strHTML .= "</td>\n";
	$strHTML .= "</tr>\n";
	$strHTML .= "</table>\n";

	return($strHTML);
}

function character_colors()
{
	global $eq2, $id, $charName;
	$query=sprintf('select * from '. ACTIVE_DB . '.char_colors where character_id = %d',$id);
	$result=$eq2->db->sql_query($query);
	$data=$eq2->db->sql_fetchrow($result);
	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "<tr>\n";
	$strHTML .= "<td width='680' valign='top'>\n";
	$strHTML .= "<fieldset style='width:675px;'>\n";
	$strHTML .= "<legend>Colors</legend>\n";
	$strHTML .= "<table width='100%' border='0'>\n";
	$strHTML .= "<tr>\n";
	$strHTML .= "<td colspan='6'>\n";
	$strHTML .= "<span class='heading'>Editing: " .  $charName . "</span><br />\n";
	$strHTML .= "</td>\n";
	$strHTML .= "</tr>\n";
	$strHTML .= "<tr>\n";
	$strHTML .= "<td>id</td>\n";
	$strHTML .= "<td>char_id</td>\n";
	$strHTML .= "<td>signed_value</td>\n";
	$strHTML .= "<td>type</td>\n";
	$strHTML .= "<td>red</td>\n";
	$strHTML .= "<td>green</td>\n";
	$strHTML .= "<td>blue</td>\n";
	$strHTML .= "<td colspan='2'>&nbsp;</td>\n";
	$strHTML .= "</tr>\n";

	$query=sprintf('select * from char_colors where char_id = %d',$id);
	$result=$eq2->db->sql_query($query);
	while($data=$eq2->db->sql_fetchrow($result))
	{
		$strHTML .= "<form method='post' name='multiForm|" . $data['id'] . "' />\n";
		$strHTML .= "<tr>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='char_colors|id' value='" . $data['id'] . "'  style='width:50px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "<input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "<input type='hidden' name='orig_object' value='" .  $charName . " />\n";
		$strHTML .= "<input type='hidden' name='table_name' value='char_colors' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='char_colors|char_id' value='" . $data['char_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='char_colors|signed_value' value='" . $data['signed_value'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_signed_value' value='" . $data['signed_value'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='char_colors|type' value='" . $data['type'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_type' value='" . $data['type'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='char_colors|red' value='" . $data['red'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_red' value='" . $data['red'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='char_colors|green' value='" . $data['green'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_green' value='" . $data['green'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='char_colors|blue' value='" . $data['blue'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_blue' value='" . $data['blue'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		if($eq2->CheckAccess(G_DEVELOPER))
		{
			$strHTML .= "<input type='submit' name='ccUpdate' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= "<input type='submit' name='ccDelete' value='Delete' style='font-size:10px; width:60px' />\n";
		}
		$strHTML .= "</td>\n";
		$strHTML .= "</tr>\n";
		$strHTML .= "</form>\n";
	}
	$strHTML .= "</table>\n";
	$strHTML .= "</fieldset>\n";
	$strHTML .= "</td>\n";
	$strHTML .= "</tr>\n";
	$strHTML .= "</table>\n";

	return($strHTML);
}

function character_macros()
{
	global $eq2, $id, $charName;
	$query=sprintf('select * from `'.ACTIVE_DB.'`.character_macros where character_id = %d',$id);
	$result=$eq2->db->sql_query($query);
	$data=$eq2->db->sql_fetchrow($result);
	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "  <tr>\n";
	$strHTML .= "    <td width='680' valign='top'>\n";
	$strHTML .= "      <fieldset style='width:675px;'>\n";
	$strHTML .= "        <legend>Macros</legend>\n";
	$strHTML .= "          <table width='100%' border='0'>\n";
	$strHTML .= "            <tr>\n";
	$strHTML .= "              <td colspan='6'>\n";
	$strHTML .= "                <span class='heading'>Editing: " .  $charName . "</span><br />\n";
	$strHTML .= "              </td>\n";
	$strHTML .= "            </tr>\n";
	$strHTML .= "            <tr>\n";
	$strHTML .= "              <td>id</td>\n";
	$strHTML .= "              <td>char_id</td>\n";
	$strHTML .= "              <td>macro_number</td>\n";
	$strHTML .= "              <td>macro_icon</td>\n";
	$strHTML .= "              <td>macro_name</td>\n";
	$strHTML .= "              <td>macro_text</td>\n";
	$strHTML .= "              <td colspan='2'>&nbsp;</td>\n";
	$strHTML .= "            </tr>\n";

	$query=sprintf('select * from `'.ACTIVE_DB.'`.character_macros where char_id = %d',$id);
	$result=$eq2->db->sql_query($query);
	while($data=$eq2->db->sql_fetchrow($result))
	{
		$strHTML .= "            <form method='post' name='multiForm|" . $data['id'] . "' />\n";
		$strHTML .= "              <tr>\n";
		$strHTML .= "                <td>\n";
		$strHTML .= "                  <input type='text' name='character_macros|id' value='" . $data['id'] . "' style='width:50px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "                  <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "                  <input type='hidden' name='orig_object' value='" . $charName . " />\n";
		$strHTML .= "                </td>\n";
		$strHTML .= "                <td>\n";
		$strHTML .= "                  <input type='text' name='character_macros|char_id' value='" . $data['char_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "                  <input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "                </td>\n";
		$strHTML .= "                <td>\n";
		$strHTML .= "                  <input type='text' name='character_macros|macro_number' value='" . $data['macro_number'] . "'  style='width:50px;' />\n";
		$strHTML .= "                  <input type='hidden' name='orig_macro_number' value='" . $data['macro_number'] . "' />\n";
		$strHTML .= "                </td>\n";
		$strHTML .= "                <td>\n";
		$strHTML .= "                  <input type='text' name='character_macros|macro_icon' value='" . $data['macro_icon'] . "'  style='width:50px;' />\n";
		$strHTML .= "                  <input type='hidden' name='orig_macro_icon' value='" . $data['macro_icon'] . "' />\n";
		$strHTML .= "                </td>\n";
		$strHTML .= "                <td>\n";
		$strHTML .= "                  <input type='text' name='character_macros|macro_name' value='" . $data['macro_name'] . "'  style='width:50px;' />\n";
		$strHTML .= "                  <input type='hidden' name='orig_macro_name' value='" . $data['macro_name'] . "' />\n";
		$strHTML .= "                </td>\n";
		$strHTML .= "                <td>\n";
		$strHTML .= "                  <input type='text' name='character_macros|macro_text' value='" . $data['macro_text'] . "'  style='width:50px;' />\n";
		$strHTML .= "                  <input type='hidden' name='orig_macro_text' value='" . $data['macro_text'] . "' />\n";
		$strHTML .= "                </td>\n";
		$strHTML .= "                <td>\n";
		$strHTML .= "                  <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
		$strHTML .= "                  <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
		$strHTML .= "                </td>\n";
		$strHTML .= "              </tr>\n";
		$strHTML .= "            </form>\n";
	}
	$strHTML .= "</table>\n";
	$strHTML .= "</fieldset>\n";
	$strHTML .= "</td>\n";
	$strHTML .= "</tr>\n";
	$strHTML .= "</table>\n";

	return ($strHTML);
}

function character_main()
{
	global $eq2, $id, $charName;
	$query=sprintf("select * from `".ACTIVE_DB."`.characters where id = %d",$id);
	$result=$eq2->db->sql_query($query);
	$data=$eq2->db->sql_fetchrow($result);

	$strHTML = "";
	$strHTML .= "<form method='post' name='CharEdit'>\n";
	$strHTML .= "  <table border='0' cellpadding='5'>\n";
	$strHTML .= "    <tr>\n";
	$strHTML .= "      <td width='680' valign='top'>\n";
	$strHTML .= "        <fieldset style='height:350px; width:675px;'>\n";
	$strHTML .= "          <legend>General</legend>\n";
	$strHTML .= "            <table width='100%' border='0'>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td colspan='6'>\n";
	$strHTML .= "                  <span class='heading'>Editing: " .  $charName . "</span><br />\n";
	$strHTML .= "                  <input type='hidden' name='orig_object' value='" .  $charName . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>id:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|id' value='" .  $data['id']  . "' readonly style='width:50px; background-color:#ddd;' />\n";
	$strHTML .= "                <input type='hidden' name='orig_id' value='" .  $data['id']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>name:</td>\n";
	$strHTML .= "                <td colspan='3'>\n";
	$strHTML .= "                <input type='text' name='characters|name' value='" . $data['name'] . "' style='width:300px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_name' value='" .  $data['name']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>account_id:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|account_id' value='" . $data['account_id'] . "' style='width:50px;' />\n";
	$strHTML .= "                <input type='hidden' name='orig_account_id' value='" .  $data['account_id']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>admin_status:</td>\n";
	$strHTML .= "                <td>\n";
	$isDisabled = (!$eq2->CheckAccess(G_SUPERADMIN)?"disabled":"");
	$strHTML .= "                <input type='text' name='characters|admin_status' value='" . $data['admin_status']  . "' " . $isDisabled . " />\n";
	$strHTML .= "                <input type='hidden' name='orig_admin_status' value='" .  $data['admin_status']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>deleted:</td>\n";
	$strHTML .= "                <td>\n";
	$isSelected = ($data['deleted']?"checked":"");
	$strHTML .= "                <input type='checkbox' name='characters|deleted' value='1'" . $isSelected . " />\n";
	$strHTML .= "                <input type='hidden' name='orig_deleted' value='" .  $data['deleted']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>server_id:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|server_id' value='" . $data['server_id'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_server_id' value='" .  $data['server_id']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>Alignment:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                  <select name='characters|alignment'>\n";
	$strHTML .= "                    <option value ='0' " . ($data['alignment']==0?"selected":"") . ">Evil</option>\n";
	$strHTML .= "                    <option value ='1' " . ($data['alignment']==1?"selected":"") . ">Good</option>\n";
	$strHTML .= "                    <option value ='2' " . ($data['alignment']==2?"selected":"") . ">Exile</option>\n";
	$strHTML .= "                  </select>\n";
	$strHTML .= "                  <input type='hidden' name='orig_server_alignment' value='" .  $data['alignment']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>level:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|level' value='" . $data['level'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_level' value='" .  $data['level']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>class:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|class' value='" . $data['class'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_class' value='" .  $data['class']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>race:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|race' value='" . $data['race'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_race' value='" .  $data['race']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>gender:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|gender' value='" . $data['gender'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_gender' value='" .  $data['gender']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>body_size:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|body_size' value='" . $data['body_size'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_body_size' value='" .  $data['body_size']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>body_age:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|body_age' value='" . $data['body_age'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_body_age' value='" .  $data['body_age']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>deity:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|deity' value='" . $data['deity'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_deity' value='" .  $data['deity']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>current_zone_id:</td>\n";
	$strHTML .= "                <td colspan='3'>\n";
	$strHTML .= "                  <select name='characters|current_zone_id' style='width:350px'>\n";
	(new eq2Zones)->getZoneOptionsByID($data['current_zone_id']);
	$strHTML .= "                  </select>\n";
	$strHTML .= "                  <input type='hidden' name='orig_current_zone' value='" .  $data['current_zone_id']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>x:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|x' value='" . $data['x']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_x' value='" . $data['x']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>y:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|y' value='" . $data['y']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_y' value='" .  $data['y'] . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>z:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|z' value='" . $data['z']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_z' value='" .  $data['z']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>heading:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|heading' value='" . $data['heading']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_heading' value='" .  $data['heading']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td align='right'>unix_timestamp:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|unix_timestamp' value='" . $data['unix_timestamp']  . "' style='width:80px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_unix_timestamp' value='" .  $data['unix_timestamp']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>created_date:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|created_date' value='" . $data['created_date']  . "' style='width:120px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_created_date' value='" .  $data['created_date']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "                <td align='right'>last_played:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|last_played' value='" . $data['last_played']  . "' style='width:120px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_last_played' value='" .  $data['last_played']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "            </table>\n";
	$strHTML .= "          </fieldset>\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td valign='top'>\n";
	$strHTML .= "          <fieldset style='height:350px; width:180px;'>\n";
	$strHTML .= "            <legend>Appearance</legend> \n";
	$strHTML .= "            <table>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>soga_wing_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                  <input type='text' name='characters|soga_wing_type' value='" . $data['soga_wing_type']  . "' style='width:50px' />\n";
	$strHTML .= "                  <input type='hidden' name='orig_soga_wing_type' value='" . $data['soga_wing_type']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>soga_chest_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|soga_chest_type' value='" . $data['soga_chest_type']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_soga_chest_type' value='" . $data['soga_chest_type']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>soga_legs_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|soga_legs_type' value='" . $data['soga_legs_type']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_soga_legs_type' value='" . $data['soga_legs_type']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>soga_hair_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|soga_hair_type' value='" . $data['soga_hair_type']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_soga_hair_type' value='" . $data['soga_hair_type']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>soga_model_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|soga_model_type' value='" . $data['soga_model_type']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_soga_model_type' value='" . $data['soga_model_type']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>legs_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|legs_type' value='" . $data['legs_type']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_legs_type' value='" . $data['legs_type']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>chest_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|chest_type' value='" . $data['chest_type']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_chest_type' value='" . $data['chest_type']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>wing_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|wing_type' value='" . $data['wing_type']  . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_wing_type' value='" . $data['wing_type']  . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "              <tr>\n";
	$strHTML .= "                <td>hair_type:</td>\n";
	$strHTML .= "                <td>\n";
	$strHTML .= "                <input type='text' name='characters|hair_type' value='" . $data['hair_type'] . "' style='width:50px' />\n";
	$strHTML .= "                <input type='hidden' name='orig_hair_type' value='" . $data['hair_type'] . "' />\n";
	$strHTML .= "                </td>\n";
	$strHTML .= "              </tr>\n";
	$strHTML .= "            </table>\n";
	$strHTML .= "          </fieldset>\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	if($eq2->CheckAccess(G_DEVELOPER) )
	{
		$strHTML .= "      <tr>\n";
		$strHTML .= "        <td colspan='2' align='center'>\n";
		$strHTML .= "          <input type='submit' name='cmd' value='Update' style='width:100px;' />&nbsp;\n";
		$strHTML .= "          <input type='hidden' name='table_name' value='characters' />\n";
		$strHTML .= "          <input type='button' value='Help' style='width:100px' onclick=\"javascript:window.open('help.php#spawns','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');\" />\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "      </tr>\n";
	}
	$strHTML .= "      <tr>\n";
	$strHTML .= "      <td colspan='2'>\n";
	$strHTML .= "        <p>\n";
	$strHTML .= "          <strong>Note:</strong> Due to the number of race options, I have chosen to not to list them in a combo box at this time because it would slow the page performance tremendously.<br />\n";
	$strHTML .= "          Please keep a race and model_type reference handy.\n";
	$strHTML .= "        </p>\n";
	$strHTML .= "      </td>\n";
	$strHTML .= "    </tr>\n";
	$strHTML .= "  </table>\n";
	$strHTML .= "</form>\n";
	return($strHTML);
}

function character_template($id)
{
	global $eq2;
	$query=sprintf("select * from `".ACTIVE_DB."`.character_details where character_id = %d",$id);
	$result=$eq2->db->sql_query($query);
	$data=$eq2->db->sql_fetchrow($result);
	$strHTML="";

	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "  <form method='post' name='CharEdit' />\n";
	$strHTML .= "    <tr>\n";
	$strHTML .= "      <td width='680' valign='top'>\n";
	$strHTML .= "        <fieldset style='width:675px;'>\n";
	$strHTML .= "          <legend>Template</legend>\n";
	$strHTML .= "          <table width='100%' border='0'>\n";
	$strHTML .= "            <tr>\n";
	$strHTML .= "              <td colspan='6'>\n";
	$strHTML .= "                <span class='heading'>Editing: " .  $charName  . "</span><br />\n";
	$strHTML .= "                <input type='hidden' name='orig_object' value='" .  $charName  . "' />\n";
	$strHTML .= "              </td>\n";
	$strHTML .= "            </tr>\n";
	$strHTML .= "          </table>\n";
	$strHTML .= "        </fieldset>\n";
	$strHTML .= "      </td>\n";
	$strHTML .= "    </tr>\n";
	if($eq2->CheckAccess(G_GUIDE))
	{
		$strHTML .= "    <tr>\n";
		$strHTML .= "      <td colspan='2' align='center'>\n";
		$strHTML .= "        <input type='submit' name='sUpdate' value='Update' style='width:100px;' />&nbsp;\n";
		$strHTML .= "        <input type='button' value='Help' style='width:100px' onclick=\"javascript:window.open('help.php#spawns','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');\" />\n";
		$strHTML .= "      </td>\n";
		$strHTML .= "    </tr>\n";
	}
	$strHTML .= "  </form>\n";
	$strHTML .= "</table>\n";
}

function character_mail($id) {
	global $eq2,$objectName,$link;

	$table= ".character_mail";
	$query=sprintf("select * from %s where player_to_id = %d",$table,$id);
	$result=$eq2->db->sql_query($query);
	$strHTML = "";

	if($eq2->db->sql_numrows($result) > 0)
	{
		$data=$eq2->db->sql_fetchrow($result);
		$strHTML .= "<table border='0' cellpadding='5'>\n";
		$strHTML .= "  <form method='post' name='Form1' />\n";
		$strHTML .= "    <tr>\n";
		$strHTML .= "      <td width='880' valign='top'>\n";
		$strHTML .= "        <fieldset><legend>General</legend>\n";
		$strHTML .= "        <table width='100%' cellpadding='0' border='0'>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td colspan='3'>\n";
		$strHTML .= "              <span class='heading'>Editing:" . $objectName . "</span><br />&nbsp;\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>id:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|id' value='" . $data['id'] . "' style='width:45px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "            <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>player_to_id:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|player_to_id' value='" . $data['player_to_id'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_player_to_id' value='" . $data['player_to_id'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>player_from:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|player_from' value='" . $data['player_from'] . "' style='width:150px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_player_from' value='" . $data['player_from'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>subject:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|subject' value='" . $data['subject'] . "' style='width:200px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_subject' value='" . $data['subject'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>mail_body:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <textarea name='character_mail|mail_body' style='width:600px; height:75px;'>" . $data['mail_body'] . "</textarea>\n";
		$strHTML .= "            <input type='hidden' name='orig_mail_body' value='" . $data['mail_body'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>already_read:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|already_read' value='" . $data['already_read'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_already_read' value='" . $data['already_read'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>mail_type:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|mail_type' value='" . $data['mail_type'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_mail_type' value='" . $data['mail_type'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>coin_copper:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|coin_copper' value='" . $data['coin_copper'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_coin_copper' value='" . $data['coin_copper'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>coin_silver:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|coin_silver' value='" . $data['coin_silver'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_coin_silver' value='" . $data['coin_silver'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>coin_gold:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|coin_gold' value='" . $data['coin_gold'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_coin_gold' value='" . $data['coin_gold'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>coin_plat:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|coin_plat' value='" . $data['coin_plat'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_coin_plat' value='" . $data['coin_plat'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>stack:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|stack' value='" . $data['stack'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_stack' value='" . $data['stack'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>postage_cost:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|postage_cost' value='" . $data['postage_cost'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_postage_cost' value='" . $data['postage_cost'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>attachment_cost:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|attachment_cost' value='" . $data['attachment_cost'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_attachment_cost' value='" . $data['attachment_cost'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>char_item_id:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|char_item_id' value='" . $data['char_item_id'] . "' style='width:45px;' />\n";
		$strHTML .= "            <input type='hidden' name='orig_char_item_id' value='" . $data['char_item_id'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>time_sent:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|time_sent' value='" . $data['time_sent'] . "' style='width:75px;' />&nbsp;" . date('Y/m/d h:n:s', $data['time_sent']) . "\n";
		$strHTML .= "            <input type='hidden' name='orig_time_sent' value='" . $data['time_sent'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "          <tr>\n";
		$strHTML .= "            <td align='right'>expire_time:</td>\n";
		$strHTML .= "            <td>\n";
		$strHTML .= "            <input type='text' name='character_mail|expire_time' value='" . $data['expire_time'] . "' style='width:75px;' />&nbsp;" . date('Y/m/d h:n:s', $data['expire_time']) . "\n";
		$strHTML .= "            <input type='hidden' name='orig_expire_time' value='" . $data['expire_time'] . "' />\n";
		$strHTML .= "            </td>\n";
		$strHTML .= "          </tr>\n";
		$strHTML .= "        </table>\n";
		$strHTML .= "      </fieldset>\n";
		$strHTML .= "    </td>\n";
		$strHTML .= "  </tr>\n";
		if($eq2->CheckAccess(G_DEVELOPER))
		{
			$strHTML .= "  <tr>\n";
			$strHTML .= "    <td colspan='4' align='center'>\n";
			$strHTML .= "      <input type='submit' name='iUpdate' value='Update' style='width:100px;' />&nbsp;\n";
			$strHTML .= "      <input type='button' value='Help' style='width:100px' onclick='javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');' />\n";
			$strHTML .= "      <input type='hidden' name='cmd' value='update' />\n";
			$strHTML .= "      <input type='hidden' name='orig_object' value='" .  $objectName  . "' />\n";
			$strHTML .= "      <input type='hidden' name='table_name' value='" .  $table  . "' />\n";
			$strHTML .= "    </td>\n";
			$strHTML .= "  </tr>\n";
		}
		$strHTML .= "</table>\n";
	} else {
		if( $eq2->CheckAccess(G_DEVELOPER) )
		{
			$strHTML .= "<table border='0' cellpadding='5'>\n";
			$strHTML .= "  <form method='post' name='Form1|new' />\n";
			$strHTML .= "    <tr>\n";
			$strHTML .= "      <td width='680' valign='top'>\n";
			$strHTML .= "        <fieldset><legend>General</legend>\n";
			$strHTML .= "          <table width='100%' cellpadding='0' border='0'>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td colspan='4'>\n";
			$strHTML .= "                <span class='heading'>Editing: " .  $objectName  . "</span><br />&nbsp;\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td colspan='4'>No data found for this item. You may insert a new record if necessary.</td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>id:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|id|new' value='0' style='width:45px;  background-color:#ddd;' readonly />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>player_to_id:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|player_to_id|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>player_from:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|player_from|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>subject:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|subject|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>mail_body:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|mail_body|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>already_read:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|already_read|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>mail_type:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|mail_type|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>coin_copper:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|coin_copper|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>coin_silver:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|coin_silver|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>coin_gold:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|coin_gold|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>coin_plat:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|coin_plat|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>stack:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|stack|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>postage_cost:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|postage_cost|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>attachment_cost:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|attachment_cost|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>char_item_id:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|char_item_id|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>time_sent:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|time_sent|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "            <tr>\n";
			$strHTML .= "              <td align='right'>expire_time:</td>\n";
			$strHTML .= "              <td>\n";
			$strHTML .= "                <input type='text' name='character_mail|expire_time|new' value='0' style='width:45px;' />\n";
			$strHTML .= "              </td>\n";
			$strHTML .= "            </tr>\n";
			$strHTML .= "          </table>\n";
			$strHTML .= "        </fieldset>\n";
			$strHTML .= "      </td>\n";
			$strHTML .= "    </tr>\n";
			$strHTML .= "    <tr>\n";
			$strHTML .= "      <td colspan='4' align='center'>\n";
			$strHTML .= "        <input type='submit' name='iInsert' value='Insert' style='width:100px;' />&nbsp;\n";
			$strHTML .= "        <input type='button' value='Help' style='width:100px' onclick=\"javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');\" />\n";
			$strHTML .= "        <input type='hidden' name='cmd' value='insert' />\n";
			$strHTML .= "        <input type='hidden' name='orig_object' value='" .  $objectName  . "' />\n";
			$strHTML .= "        <input type='hidden' name='table_name' value='" .  $table  . "' />\n";
			$strHTML .= "      </td>\n";
			$strHTML .= "    </tr>\n";
			$strHTML .= "  </form>\n";
			$strHTML .= "</table>\n";
		}
	}
	return($strHTML);
}


function character_buyback($id) {
	global $eq2,$objectName,$link;
	$table= ".character_buyback";

	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "  <tr>\n";
	$strHTML .= "    <td width='680' valign='top'>\n";
	$strHTML .= "      <fieldset><legend>Buy Back</legend>\n";
	$strHTML .= "        <table width='100%' cellpadding='0' border='0'>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td colspan='3'>\n";
	$strHTML .= "              <span class='heading'>Editing: " . $objectName . "</span><br />&nbsp;\n";
	$strHTML .= "            </td>\n";
	$strHTML .= "          </tr>\n";
	$strHTML .= "          <tr>\n";
	$strHTML .= "            <td width='55'>id</td>\n";
	$strHTML .= "            <td width='75'>char_id</td>\n";
	$strHTML .= "            <td width='75'>item_id</td>\n";
	$strHTML .= "            <td width='55'>quantity</td>\n";
	$strHTML .= "            <td width='105'>price</td>\n";
	$strHTML .= "            <td colspan='2'>&nbsp;</td>\n";
	$strHTML .= "          </tr>\n";
	$query=sprintf("select * from %s where char_id = %s",$table, $id);
	$result=$eq2->db->sql_query($query);
	while($data=$eq2->db->sql_fetchrow($result)) 
	{
		$strHTML .= "          <form method='post' name='multiForm|" . $data['id'] . "' />\n";
		$strHTML .= "            <tr>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|id' value='" . $data['id'] . "' style='width:45px;  background-color:#ddd;' readonly />\n";
		$strHTML .= "                <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|char_id' value='" . $data['char_id'] . "' style='width:70px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|item_id' value='" . $data['item_id'] . "' style='width:70px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_item_id' value='" . $data['item_id'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|quantity' value='" . $data['quantity'] . "' style='width:45px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_quantity' value='" . $data['quantity'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|price' value='" . $data['price'] . "' style='width:100px;' />\n";
		$strHTML .= "                <input type='hidden' name='orig_price' value='" . $data['price'] . "' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "                <td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' /><?php } ?></td>\n";
		$strHTML .= "                <td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' /><?php } ?></td>\n";
		$strHTML .= "            </tr>\n";
		$strHTML .= "          <input type='hidden' name='objectName' value='" .  $objectName  . "' />\n";
		$strHTML .= "          <input type='hidden' name='table_name' value='" .  $table  . "' />\n";
		$strHTML .= "          </form>\n";
	}
	if($eq2->CheckAccess(G_DEVELOPER))
	{
		$strHTML .= "          <form method='post' name='sdForm|new' />\n";
		$strHTML .= "            <tr>\n";
		$strHTML .= "              <td align='center'><strong>new</strong></td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|char_id|new' value='' style='width:70px;' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|item_id|new' value='' style='width:70px;' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|quantity|new' value='' style='width:45px;' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='text' name='character_buyback|price|new' value='' style='width:100px;' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "              <td>\n";
		$strHTML .= "                <input type='submit' name='cmd' value='Insert' style='font-size:10px; width:60px' />\n";
		$strHTML .= "              </td>\n";
		$strHTML .= "            </tr>\n";
		$strHTML .= "          <input type='hidden' name='table_name' value='" .  $table  . "' />\n";
		$strHTML .= "          </form>\n";
	}
	$strHTML .= "        </table>\n";
	$strHTML .= "      </fieldset>\n";
	$strHTML .= "    </td>\n";
	$strHTML .= "  </tr>\n";
	$strHTML .= "</table>\n";
	
	return($strHTML);
}

function character_details()
{
	global $eq2, $line, $id, $charName;
	$char_details_query = "SELECT * FROM `". ACTIVE_DB ."`.character_details where char_id = " . $id;
	$char_details_data = $eq2->RunQuerySingle($char_details_query);

	$strHTML = "";
	$strHTML .= "<form method='post' name='CharEdit' />\n";
	$strHTML .= "  <fieldset style='width:675px;'>\n";
	$strHTML .= "    <legend>Details [<b>" . $charName . "</b> (" . $char_details_data['char_id'] . ")]</legend> \n";
	$strHTML .= "    <table width='100%' border='1' class='ContrastTable'>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th colspan='9' align='center'>Stats A</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th>hp:</th>\n";
	$strHTML .= "        <th>power:</th>\n";
	$strHTML .= "        <th>max_concentration:</th>\n";
	$strHTML .= "        <th>attack:</th>\n";
	$strHTML .= "        <th>mitigation:</th>\n";
	$strHTML .= "        <th>avoidance:</th>\n";
	$strHTML .= "        <th>parry:</th>\n";
	$strHTML .= "        <th>deflection:</th>\n";
	$strHTML .= "        <th>block:</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|hp' value='" . $char_details_data['hp'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_hp' value='" . $char_details_data['hp'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|power' value='" . $char_details_data['power'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_power' value='" . $char_details_data['power'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|max_concentration' value='" . $char_details_data['max_concentration'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_max_concentration' value='" . $char_details_data['max_concentration'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|attack' value='" . $char_details_data['attack'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_attack' value='" . $char_details_data['attack'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|mitigation' value='" . $char_details_data['mitigation'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_mitigation' value='" . $char_details_data['mitigation'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|avoidance' value='" . $char_details_data['avoidance'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_avoidance' value='" . $char_details_data['avoidance'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|parry' value='" . $char_details_data['parry'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_parry' value='" . $char_details_data['parry'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|deflection' value='" . $char_details_data['deflection'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_deflection' value='" . $char_details_data['deflection'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|block' value='" . $char_details_data['block'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_block' value='" . $char_details_data['block'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th colspan='9' align='center'>Stats B</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th>str:</th>\n";
	$strHTML .= "        <th>sta:</th>\n";
	$strHTML .= "        <th>agi:</th>\n";
	$strHTML .= "        <th>wis:</th>\n";
	$strHTML .= "        <th>intel:</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|str' value='" . $char_details_data['str'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_str' value='" . $char_details_data['str'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|sta' value='" . $char_details_data['sta'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_sta' value='" . $char_details_data['sta'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|agi' value='" . $char_details_data['agi'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_agi' value='" . $char_details_data['agi'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|wis' value='" . $char_details_data['wis'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_wis' value='" . $char_details_data['wis'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|intel' value='" . $char_details_data['intel'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_intel' value='" . $char_details_data['intel'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th colspan='9' align='center'>Stats C</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th>heat:</th>\n";
	$strHTML .= "        <th>cold:</th>\n";
	$strHTML .= "        <th>magic:</th>\n";
	$strHTML .= "        <th>mental:</th>\n";
	$strHTML .= "        <th>divine:</th>\n";
	$strHTML .= "        <th>disease:</th>\n";
	$strHTML .= "        <th>poison:</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|heat' value='" . $char_details_data['heat'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_heat' value='" . $char_details_data['heat'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|cold' value='" . $char_details_data['cold'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_cold' value='" . $char_details_data['cold'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|magic' value='" . $char_details_data['magic'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_magic' value='" . $char_details_data['magic'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|mental' value='" . $char_details_data['mental'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_mental' value='" . $char_details_data['mental'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|divine' value='" . $char_details_data['divine'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_divine' value='" . $char_details_data['divine'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|disease' value='" . $char_details_data['disease'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_disease' value='" . $char_details_data['disease'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|poison' value='" . $char_details_data['poison'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_poison' value='" . $char_details_data['poison'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th colspan='9' align='center'>Coin</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th>coin_copper:</th>\n";
	$strHTML .= "        <th>coin_silver:</th>\n";
	$strHTML .= "        <th>coin_gold:</th>\n";
	$strHTML .= "        <th>coin_plat:</th>\n";
	$strHTML .= "        <th>bank_copper:</th>\n";
	$strHTML .= "        <th>bank_silver:</th>\n";
	$strHTML .= "        <th>bank_gold:</th>\n";
	$strHTML .= "        <th>bank_plat:</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|coin_copper' value='" . $char_details_data['coin_copper'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_coin_copper' value='" . $char_details_data['coin_copper'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|coin_silver' value='" . $char_details_data['coin_silver'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_coin_silver' value='" . $char_details_data['coin_silver'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|coin_gold' value='" . $char_details_data['coin_gold'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_coin_gold' value='" . $char_details_data['coin_gold'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|coin_plat' value='" . $char_details_data['coin_plat'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_coin_plat' value='" . $char_details_data['coin_plat'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|bank_copper' value='" . $char_details_data['bank_copper'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_bank_copper' value='" . $char_details_data['bank_copper'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|bank_silver' value='" . $char_details_data['bank_silver'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_bank_silver' value='" . $char_details_data['bank_silver'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|bank_gold' value='" . $char_details_data['bank_gold'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_bank_gold' value='" . $char_details_data['bank_gold'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|bank_plat' value='" . $char_details_data['bank_plat'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_bank_plat' value='" . $char_details_data['bank_plat'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th colspan='9' align='center'>Pet</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th>pet_name:</th>\n";
	$strHTML .= "        <th>status_points:</th>\n";
	$strHTML .= "        <th>max_power:</th>\n";
	$strHTML .= "        <th>max_hp:</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|pet_name' value='" . $char_details_data['pet_name'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_pet_name' value='" . $char_details_data['pet_name'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|status_points' value='" . $char_details_data['status_points'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_status_points' value='" . $char_details_data['status_points'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "          <td>\n";
	$strHTML .= "            <input type='text' name='character_details|max_power' value='" . $char_details_data['max_power'] . "' style='width:50px' />\n";
	$strHTML .= "            <input type='hidden' name='orig_max_power' value='" . $char_details_data['max_power'] . "' />\n";
	$strHTML .= "          </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|max_hp' value='" . $char_details_data['max_hp'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_max_hp' value='" . $char_details_data['max_hp'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th colspan='9' align='center'>XP</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th>XP:</th>\n";
	$strHTML .= "        <th>XP Needed:</th>\n";
	$strHTML .= "        <th>XP Debt:</th>\n";
	$strHTML .= "        <th>XP Vitality:</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|xp' value='" . $char_details_data['xp'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_xp' value='" . $char_details_data['xp'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|xp_needed' value='" . $char_details_data['xp_needed'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_xp_needed' value='" . $char_details_data['xp_needed'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|xp_debt' value='" . $char_details_data['xp_debt'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_xp_debt' value='" . $char_details_data['xp_debt'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|xp_vitality' value='" . $char_details_data['xp_vitality'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_xp_vitality' value='" . $char_details_data['xp_vitality'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th colspan='9' align='center'>Bind Location Data</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th>bind_zone:</th>\n";
	$strHTML .= "        <th>bind_x:</th>\n";
	$strHTML .= "        <th>bind_y:</th>\n";
	$strHTML .= "        <th>bind_z:</th>\n";
	$strHTML .= "        <th>house_zone_id:</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|bind_zone_id' value='" . $char_details_data['bind_zone_id'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_bind_zone_id' value='" . $char_details_data['bind_zone_id'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|bind_x' value='" . $char_details_data['bind_x'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_bind_x' value='" . $char_details_data['bind_x'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|bind_y' value='" . $char_details_data['bind_y'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_bind_y' value='" . $char_details_data['bind_y'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|bind_z' value='" . $char_details_data['bind_z'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_bind_z' value='" . $char_details_data['bind_z'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|house_zone_id' value='" . $char_details_data['house_zone_id'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_house_zone_id' value='" . $char_details_data['house_zone_id'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <th colspan='9' align='center'>Voice Settings</th>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td align='right'>combat_voice:</td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|combat_voice' value='" . $char_details_data['combat_voice'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_combat_voice' value='" . $char_details_data['combat_voice'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	$strHTML .= "      <tr>\n";
	$strHTML .= "        <td align='right'>emote_voice:</td>\n";
	$strHTML .= "        <td>\n";
	$strHTML .= "          <input type='text' name='character_details|emote_voice' value='" . $char_details_data['emote_voice'] . "' style='width:50px' />\n";
	$strHTML .= "          <input type='hidden' name='orig_emote_voice' value='" . $char_details_data['emote_voice'] . "' />\n";
	$strHTML .= "        </td>\n";
	$strHTML .= "      </tr>\n";
	if($eq2->CheckAccess(G_GUIDE))
	{
		$strHTML .= "      <tr>\n";
		$strHTML .= "        <td colspan='9' align='center'>\n";
		$strHTML .= "          <input type='hidden' name='orig_character_id' value='" . $char_details_data['char_id'] . "' />\n";
		$strHTML .= "          <input type='submit' name='cdUpdate' value='Update' style='width:100px;' />&nbsp;\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "      </tr>\n";
	}

	$strHTML .= "    </table>\n";
	$strHTML .= "  </fieldset>\n";
	$strHTML .= "</form>\n";
	return($strHTML);

}

function CharacterFactions($id) {
	global $eq2, $charName;
	$query=sprintf("select * from `".ACTIVE_DB."`.character_factions where character_id = %d",$id);
	$result=$eq2->db->sql_query($query);
	$data=$eq2->db->sql_fetchrow($result);
	$strHTML = "";
	$strHTML .= "<table border='0' cellpadding='5'>\n";
	$strHTML .= "<tr>\n";
	$strHTML .= "<td valign='top'>\n";
	$strHTML .= "<fieldset style='width:550px'>\n";
	$strHTML .= "<legend>Factions</legend>\n";
	$strHTML .= "<table width='100%' border='0'>\n";
	$strHTML .= "<tr>\n";
	$strHTML .= "<td colspan='6'>\n";
	$strHTML .= "<span class='heading'>Editing: <?= $charName ?></span><br />\n";
	$strHTML .= "</td>\n";
	$strHTML .= "</tr>\n";
	$strHTML .= "<tr align='center'>\n";
	$strHTML .= "<td>char_id</td>\n";
	$strHTML .= "<td>name</td>\n";
	$strHTML .= "<td>faction_id</td>\n";
	$strHTML .= "<td>faction_level</td>\n";
	$strHTML .= "<td colspan='2'>&nbsp;</td>\n";
	$strHTML .= "</tr>\n";

	$query=sprintf("select cf.*, f.`name` as faction_name from `".ACTIVE_DB."`.character_factions cf INNER JOIN `".ACTIVE_DB."`.factions f ON f.id = cf.faction_id WHERE cf.char_id = %d",$id);
	$result = $eq2->RunQueryMulti($query);
	foreach ($result as $data)
	{
		$strHTML .= "<form method='post' name='multiForm|" . $data['id'] . "'>\n";
		$strHTML .= "<tr align='center'>\n";
		$strHTML .= "<td>" . $data['char_id'] . "\n";
		$strHTML .= "<input type='hidden' name='orig_char_id' value='" . $data['char_id'] . "' />\n";
		$strHTML .= "<input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= "<input type='hidden' name='orig_object' value='" . $charName . "' />\n";
		$strHTML .= "<input type='hidden' name='table_name' value='character_factions' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>" . $data['faction_name'] . "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_factions|faction_id' value='" . $data['faction_id'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_faction_id' value='" . $data['faction_id'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_factions|faction_level' value='" . $data['faction_level'] . "'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='orig_faction_level' value='" . $data['faction_level'] . "' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		if($eq2->CheckAccess(G_DEVELOPER))
		{
			$strHTML .= "<input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= "<input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
		}
		$strHTML .= "</td>\n";
		$strHTML .= "</tr>\n";
		$strHTML .= "</form>\n";
	}
	if ($eq2->CheckAccess(G_DEVELOPER))
	{
		$strHTML .= "<form method='post' name='newFactionRow'>\n";
		$strHTML .= "<tr align='center'>\n";
		$strHTML .= "<td>" . $id ."\n";
		$strHTML .= "<input type='hidden' name='character_factions|char_id|new' value='<?php echo $id ?>'  style='width:50px;' />\n";
		$strHTML .= "<input type='hidden' name='table_name' value='character_factions' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td></td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_factions|faction_id|new' value=''  style='width:50px;' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td>\n";
		$strHTML .= "<input type='text' name='character_factions|faction_level|new' value=''  style='width:50px;' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "<td colspan='2'>\n";
		$strHTML .= "<input type='submit' name='cmd' value='Insert' style='font-size:10px; width:60px' />\n";
		$strHTML .= "</td>\n";
		$strHTML .= "</tr>\n";
	}
	$strHTML .= "</table>\n";
	$strHTML .= "</fieldset>\n";
	$strHTML .= "</td>\n";
	$strHTML .= "</tr>\n";
	$strHTML .= "</table>\n";

	return($strHTML);
}

function not_implimented()
{
	$strHTML = "Not Implimented Yet.";
	return($strHTML);
}



include("footer.php");

?>
