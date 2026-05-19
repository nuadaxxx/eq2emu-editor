<?php 
define('IN_EDITOR', true);
include("header.php"); 

// Instantiate the eq2Spawn class, which also instantiates the eq2Zones class as $spawns->zones
include("../class/eq2.quests.php");

$q = new eq2Quests(); // shorthand
$q->SetQuestEditorBasePage('quest_ai.php');

// Quest-AI uses the existing Quest Fix Assistant backend without touching the original Quests tab.
include("../class/eq2.admin.php");
$admin = new eq2Admin();

?>
<div id="sub-menu1">
	<a href="quest_ai.php">Quest-AI Script Editor</a> | 	
	<a href="quest_ai.php?cl=history">Quest-AI Scripts Changelog</a> |
	<a href="quest_ai.php?zone=ZONENAMEHERE&id=add&tab=register">Add Quest</a>
</div>
<?php
if( isset($_GET['cl']) ) 
{
	$eq2->DisplayChangeLogPicker($q->eq2QuestTables);
	include("footer.php");
	exit;
}

/*
 * Process commands here
 */
switch(strtolower($_POST['cmd'] ?? "")) 
{
	case "save"		: $eq2->SaveLUAScript(); break;
	case "create"	: $eq2->SaveLUAScript(); break;
	case "update"	: $eq2->ProcessUpdates(); break;
	case "insert"	: 
		$eq2->ProcessInserts();
		
		if( $_GET['id'] == "add" )
			$eq2->Redir($_POST['redir']); // redir to new quest ID if adding
			
		break;
		
	case "delete"	: 
		if( $_GET['tab'] == "register" )
		{
			$q->DeleteQuest(); // this will delete quests, details and script if Delete is selected on the Register tab
			
			if( $GLOBALS['config']['readonly'] == 0 )
				$eq2->Redir($_POST['redir']);
		}
		else
			$eq2->ProcessDeletes(); 
		break;
}


/*
 * Build zone filter
 */
$zoneOptions = $q->GetQuestZoneOptions();

/*
 * Build quest filter
 */
if( isset($_GET['zone']) ) 
	$questOptions = $q->GetQuestOptionsByZone();


/*
 * Display Filters
 */
?>
<form action="quest_ai.php" id="frmSearch" method="post">
<table width="1000" border="0">
	<tr>
		<td class="filter_labels">Filters:</td>
		<td width="300">
			<select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)" style="min-width:300px;" />
			<option value="quest_ai.php">Pick a Zone</option>
			<?= $zoneOptions ?>
			</select>
		</td>
		<?php if ( isset($_GET['zone']) ) { ?>
		<td width="500">
			<select name="questID" onchange="dosub(this.options[this.selectedIndex].value)" style="min-width:300px;" />
				<option value="quest_ai.php?zone=<?= $_GET['zone'] ?>">Pick a Quest</option>
				<option value="quest_ai.php?zone=<?= $_GET['zone'] ?>&id=add&tab=register"<?php if( $q->quest_id == 'add' ) echo " selected" ?>>Add a Quest</option>
				<?= $questOptions ?>
			</select> <a href="quest_ai.php?<?= $_SERVER['QUERY_STRING'] ?>">Reload Page</a>
		</td>
		<?php } ?>
		<td>&nbsp;</td>
	</tr>
	<?php
	if( $q->quest_id == 0 )
	{
	?>
	<script>
	function QuestLookupAJAX() {
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			let str = escape(document.getElementById('txtSearch').value);
			searchReq.open("GET", '../ajax/eq2Ajax.php?type=luQ&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest; 
			searchReq.send(null);
		}		
	}
	</script>
	
	<tr>
		<td class="filter_labels">Lookup:</td>
		<td colspan="3">
				<input type="text" id="txtSearch" name="txtSearch" alt="Search Criteria" onkeyup="QuestLookupAJAX();" autocomplete="off" class="box" style="width:295px;" value="<?= $_POST['txtSearch'] ?? ""; ?>" /><!--onclick="this.value='';"-->
				<input type="submit" id="cmdSearch" name="cmdSearch" value="Search" alt="Run Search" class="submit" />
				<input type="button" value="Clear" class="submit" onclick="dosub('quest_ai.php');" />
				<div id="search_suggest"></div>
		</td>
	</tr>
	<?php 
	} 
	?>
</table>
</form>
<?php

// once the filters are set, show the spell selector grid
if( isset($_POST['cmdSearch']) && $_POST['cmdSearch'] == 'Search' )
{
	$data = $q->GetQuestsMatching();
	DisplayQuestSelectionGrid($data);
	include("footer.php");
	exit; // end page here, since actions requires none of the code below
}

if ( $q->quest_id > 0 || $q->quest_id == "add" )
{
?>
	<table>
		<tr>
			<td>
				<strong>IMPORTANT:</strong> A few things about this editor.<br />
				<ul style="margin-top:2px; margin-bottom:0px; list-style:outside">
					<li>Pick the zone the Quest Script is to be created in. Then either pick an existing quest, or the Add a Quest option.
					<li>When adding a quest, you must enter the full relative path to the quest script (ie. Quests/ZoneName/script_name.lua)
					<li>Creating a new quest reads in a generic &quot;template&quot; to get you started.
				</ul>
			</td>
		</tr>
	</table>
<?php

	$querystring = sprintf("quest_ai.php?");

	if( strlen($_GET['zone']) > 0 )
		$querystring = sprintf("%szone=%s&", $querystring, $_GET['zone']);

	// this needs to check the GET due to number or "add"
	if( !empty($_GET['id']) )
		$querystring = sprintf("%sid=%s", $querystring, $q->quest_id);

	// Build the Tab menu
	$current_tab_idx = ( isset($_GET['tab']) ) ? $_GET['tab'] : 'Register';
	
	// only show Register tab if adding new quest
	$tab_array = array(
		'register'							=> 'Register'
	);

	$tab_array2 = array(
		'quest_details'					=> 'Details',
		'quest_script'					=> 'Script',
		'quest_ai_assistant'			=> 'Quest-AI'
	);

	if( $_GET['id'] != "add" ) 
		$tab_array = array_merge($tab_array, $tab_array2);

	$eq2->TabGenerator($current_tab_idx, $tab_array, $querystring, false);

	/*
	 * Display Page HTML
	 */
	switch($_GET['tab']) 
	{
		case "quest_details": Details(); break;
		case "quest_script"	: script_editor(); break;
		case "quest_ai_assistant": ShowSOEQuestFixAssistant(0); break;
		default							: RegisterQuest(); break;
	}	
	
	include("footer.php");
	exit; // end of page
}

/*
 * Functions
 */
function DisplayQuestSelectionGrid($data)
{
	global $eq2, $q;
	
	//print_r($spell_data);
	
	if( is_array($data) )
	{
	?>
	<table width="100%" cellpadding="4" cellspacing="0" border="0">
		<tr bgcolor="#cccccc">
			<td width="50"><strong>Quest ID</strong></td>
			<td width="120"><strong>Name</strong></td>
			<td><strong>Description</strong></td>
			<td width="20"><strong>Level</strong></td>
			<td width="100"><strong>Type</strong></td>
			<td width="100"><strong>Zone</strong></td>
		</tr>
		<?php 
		$i = 0;
		foreach($data as $row)
		{
			$row_class = ( $i % 2 ) ? " bgcolor=\"#eeeeee\"" : "";
			//$description = ( strlen($row['description']) > 90 ) ? substr($row['description'],0,90).'...' : $row['description'];
			$description = $row['description']; // use above to truncate descriptions
			
			// having a problem switching classes once in the editor
			$querystring = sprintf("quest_ai.php?zone=%s", $row['zone']);
			
		?>
		<tr<?= $row_class ?> valign="top">
			<td><a href="<?= $querystring ?>&id=<?= $row['quest_id'] ?>"><?= $row['quest_id'] ?></a></td>
			<td nowrap>
				<a href="http://census.daybreakgames.com/xml/get/eq2/quest/?category=<?= trim($row['type']) ?>&level=<?= trim($row['level']) ?>&c:limit=100&c:sort=tier" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
				<a href="http://eq2.wikia.com/wiki/<?= $row['name'] ?>" target="_blank"><img src="../images/wikia.png" border="0" align="top" title="Wikia" alt="Wikia" height="20" /></a>
				<a href="http://eq2.zam.com/search.html?q=<?= $row['name'] ?>" target="_blank"><img src="../images/zam.png" border="0" align="top" title="Zam" alt="Zam" height="20" /></a>
				<?= $row['name'] ?>
			</td>
			<td><?= $description ?></td>
			<td><?= $row['level'] ?></td>
			<td nowrap><?= $row['type'] ?></td>
			<td nowrap><?= $row['zone'] ?></td>
		</tr>
		<?php
		$i++;
		}
		?>
		<tr bgcolor="#CCCCCC">
			<td colspan="10"><?= $i ?> rows returned...</td>
		</tr>
	</table>
	<?php
	}
	else
		print("&nbsp;No data found for set filters. Lookup the quest by name.");
}

function RegisterQuest()
{
	global $eq2, $q;
	$strOffset = str_repeat("\x20",22);
	$strHTML = "\n";
	
	$strHTML .= $strOffset . "<div id='Editor'>\n";
	$strHTML .= $strOffset . "<table class='SubPanel' cellspacing='0' border='0'>\n";
	$strHTML .= $strOffset . "		<tr>\n";
	$strHTML .= $strOffset . "			<td id='EditorStatus' colspan='2'>\n";
	if(!empty($eq2->Status))
	{
		$strHTML .= $eq2->DisplayStatus();
	}
	$strHTML .= $strOffset . "</td>\n";
	$strHTML .= $strOffset . "		</tr>\n";

		if( $q->quest_id != 'add' )
		{
			$data = $q->GetQuestData();
			$script_full_name = $data['lua_script'];
			$tmp = explode('/', $script_full_name);
			if( count($tmp) == 3 )
			{
				$script_path = sprintf('/%s/%s/', $tmp[0], $tmp[1]);
				$script_name = $tmp[2];
			}

			$strHTML .= $strOffset . "<tr>\n";
			$strHTML .= $strOffset . "  <td class='Title' colspan='2'>Editing: " . $q->quest_name . " (" . $q->quest_id . ") (" . $script_full_name  . ") " . $q->PrintOffsiteLinks() . "</td>\n";
			$strHTML .= $strOffset . "</tr>\n";
			$registerAiState = QuestAIGetAssistantState(true);
			$strHTML .= $strOffset . "<tr>\n";
			$strHTML .= $strOffset . "  <td colspan='2' align='center' style='padding:0 18px 8px 18px;'>\n";
			$strHTML .= QuestAIRenderRegisterCensusSearchHtml($registerAiState);
			$strHTML .= $strOffset . "  </td>\n";
			$strHTML .= $strOffset . "</tr>\n";
			$strHTML .= $strOffset . "<tr>\n";
			$strHTML .= $strOffset . "  <td valign='top'>\n";
			$strHTML .= $strOffset . "    <form method='post' name='QuestForm' />\n";
			$strHTML .= $strOffset . "      <table class='SectionMain' cellspacing='0' border='0'>\n";
			$strHTML .= $strOffset . "        <tr>\n";
			$strHTML .= $strOffset . "          <td class='SectionTitle'>Register Quest</td>\n";
			$strHTML .= $strOffset . "        </tr>\n";
			$strHTML .= $strOffset . "        <tr>\n";
			$strHTML .= $strOffset . "          <td class='SectionBody'>\n";
			$strHTML .= $strOffset . "            <table cellspacing='0'>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' class='Label'>quest_id:</td>\n";
			$strHTML .= $strOffset . "                <td>" . $data['quest_id'] . "<input type='hidden' name='orig_id' value='" . $data['quest_id'] . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>name:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|name' value=\"" . $data['name'] . "\" style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_name' value=\"" . $data['name'] . "\" />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>type:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|type' value='" . $data['type'] . "' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_type' value='" . $data['type'] . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>zone:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|zone' value='" . $data['zone']  . "' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_zone' value='" . $data['zone']  . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>level:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|level' value='" . $data['level'] . "' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_level' value='" . $data['level'] . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>difficulty:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|enc_level' value='" . $data['enc_level'] . "' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_enc_level' value='" . $data['enc_level'] . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label' valign='top'>description:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <textarea name='quests|description' style='width:400px; height:150px;'>" . $data['description'] . "</textarea>\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_description' value=\"" . $data['description'] . "\" />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label' valign='top'>completed_text:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <textarea name='quests|completed_text' style='width:400px; height:150px;'>" . $data['completed_text'] . "</textarea>\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_completed_text' value=\"" . $data['completed_text'] . "\" />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>spawn_id:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|spawn_id' value='" . $data['spawn_id'] . "' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_spawn_id' value='" . $data['spawn_id'] . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>lua_script:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|lua_script' value='" . $data['lua_script'] . "' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_lua_script' value='" . $data['lua_script'] . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";

			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>Minimum Earned Status:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|status_to_earn_min' value='" . $data['status_to_earn_min'] . "' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_status_to_earn_min' value='" . $data['status_to_earn_min'] . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td class='Label'>Maximum Earned Status:</td>\n";
			$strHTML .= $strOffset . "                <td colspan='2'>\n";
			$strHTML .= $strOffset . "                  <input type='text' name='quests|status_to_earn_max' value='" . $data['status_to_earn_max'] . "' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='orig_status_to_earn_max' value='" . $data['status_to_earn_max'] . "' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			
			$strHTML .= $strOffset . "                <script>\n";
			$strHTML .= $strOffset . "                  function updateChkBox(){\n";
			$strHTML .= $strOffset . "                    if(document.getElementById('isDeleteable').checked == true){\n";
			$strHTML .= $strOffset . "                      document.getElementById('isDeleteable_holder').value = 1;\n";
			$strHTML .= $strOffset . "                    }else{\n";
			$strHTML .= $strOffset . "                      document.getElementById('isDeleteable_holder').value = 0;\n";
			$strHTML .= $strOffset . "                    }\n";
			$strHTML .= $strOffset . "                    if(document.getElementById('isShareable').checked == true){\n";
			$strHTML .= $strOffset . "                      document.getElementById('isShareable_holder').value = 1;\n";
			$strHTML .= $strOffset . "                    }else{\n";
			$strHTML .= $strOffset . "                      document.getElementById('hideReward_holder').value = 0;\n";
			$strHTML .= $strOffset . "                    }\n";
			$strHTML .= $strOffset . "                    if(document.getElementById('hideReward').checked == true){\n";
			$strHTML .= $strOffset . "                      document.getElementById('hideReward_holder').value = 1;\n";
			$strHTML .= $strOffset . "                    }else{\n";
			$strHTML .= $strOffset . "                      document.getElementById('hideReward_holder').value = 0;\n";
			$strHTML .= $strOffset . "                    }\n";
			$strHTML .= $strOffset . "                  }\n";
			$strHTML .= $strOffset . "                </script>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' class='Label' >Deleteable:<input id='isDeleteable' name='isDeleteable' type='checkbox' " . ($data['deleteable'] == 1?'checked':'') ." onclick=\"updateChkBox();\">\n";
			$strHTML .= $strOffset . "                <input type='hidden' id='isDeleteable_holder'  name='quests|deleteable' value=\"" . $data['deleteable'] . "\" />\n";
			$strHTML .= $strOffset . "                <input type='hidden' id='orig_deleteable' name='orig_deleteable' value=\"" . $data['deleteable'] . "\" />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "                <td width='100' class='Label' >Sharable:<input id='isShareable' name='isShareable' type='checkbox' " . ($data['shareable_flag'] == 1?'checked':'') ." onclick=\"updateChkBox();\">\n";
			$strHTML .= $strOffset . "                <input type='hidden' id='isShareable_holder'  name='quests|shareable_flag' value=\"" . $data['shareable_flag'] . "\" />\n";
			$strHTML .= $strOffset . "                <input type='hidden' id='orig_Shareable' name='orig_shareable_flag' value=\"" . $data['shareable_flag'] . "\" />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "                <td width='100' class='Label' >Hide Reward:<input id='hideReward' name='hideReward' type='checkbox' " . ($data['hide_reward'] == 1?'checked':'') ." onclick=\"updateChkBox();\">\n";
			$strHTML .= $strOffset . "                <input type='hidden' id='hideReward_holder'  name='quests|hide_reward' value=\"" . $data['hide_reward'] . "\" />\n";
			$strHTML .= $strOffset . "                <input type='hidden' id='orig_hideReward' name='orig_hide_reward' value=\"" . $data['hide_reward'] . "\" />\n";
			$strHTML .= $strOffset . "                </td>\n";

			$strHTML .= $strOffset . "              </tr>\n";


			
			if($eq2->CheckAccess(G_DEVELOPER)) 
			{
				$strHTML .= $strOffset . "              <tr>\n";
				$strHTML .= $strOffset . "                <td colspan='3' align='center'>\n";
				$strHTML .= $strOffset . "                  <input type='submit' name='cmd' value='Update' class='submit' />\n";
				$strHTML .= $strOffset . "                  <input type='submit' name='cmd' value='Delete' class='submit' />\n";
				$strHTML .= $strOffset . "                  <input type='hidden' name='object_id' value='" . $q->quest_name . "|" . $q->quest_id . "' />\n";
				$strHTML .= $strOffset . "                  <input type='hidden' name='table_name' value='quests' />\n";
				$strHTML .= $strOffset . "                  <input type='hidden' name='idx_name' value='quest_id' />\n";
				$strHTML .= $strOffset . "                  <input type='hidden' name='script_path' value='" . $data['lua_script'] . "' />\n";
				$strHTML .= $strOffset . "                  <input type='hidden' name='redir' value='quest_ai.php?zone=" . $_GET['zone'] . "' />\n";
				$strHTML .= $strOffset . "                </td>\n";
				$strHTML .= $strOffset . "              </tr>\n";
				$strHTML .= $strOffset . "              <tr>\n";
				$strHTML .= $strOffset . "                <td colspan='3' align='center' valign='bottom' height='30'>Note: <strong>Delete</strong> on this tab will delete Quest, Details and Script!</td>\n";
				$strHTML .= $strOffset . "              </tr>\n";
			}
			$strHTML .= $strOffset . "              </table>\n";
			$strHTML .= $strOffset . "            </td>\n";
			$strHTML .= $strOffset . "          </tr>\n";
			$strHTML .= $strOffset . "        </table>\n";
			$strHTML .= $strOffset . "      </form>\n";
			$strHTML .= $strOffset . "    </td>\n";
			$strHTML .= $strOffset . "    <td valign='top' style='padding-left:18px;'>\n";
			$strHTML .= QuestAIRenderRegisterRecommendationHtml($registerAiState, $data);
			$strHTML .= $strOffset . "    </td>\n";
			$strHTML .= $strOffset . "  </tr>\n";
		}
		else if( $q->quest_id == 'add' )
		{
			$next_quest_id = $eq2->GetNextIDX('quests', 'quest_id');

			$strHTML .= $strOffset . "  <tr>\n";
			$strHTML .= $strOffset . "    <td class='Title' colspan='2'>Editing: *NEW*</td>\n";
			$strHTML .= $strOffset . "  </tr>\n";
			$strHTML .= $strOffset . "  <tr>\n";
			$strHTML .= $strOffset . "    <td>\n";
			$strHTML .= $strOffset . "      <form method='post' name='QuestFormNew' />\n";
			$strHTML .= $strOffset . "      <table class='SectionMain' cellspacing='0' border='0'>\n";
			$strHTML .= $strOffset . "        <tr>\n";
			$strHTML .= $strOffset . "        	<td class='SectionTitle'>Register *NEW* Quest</td>\n";
			$strHTML .= $strOffset . "        </tr>\n";
			$strHTML .= $strOffset . "        <tr>\n";
			$strHTML .= $strOffset . "          <td class='SectionBody'>\n";
			$strHTML .= $strOffset . "            <table cellspacing='0'>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td colspan='6'>\n";
			$strHTML .= $strOffset . "                  <span class='heading'>Adding: new quest</span><br />&nbsp;\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>id:</td>\n";
			$strHTML .= $strOffset . "                <td width='100'><strong>new</strong></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>name:</td>\n";
			$strHTML .= $strOffset . "                <td><input type='text' id='questNameText' name='quests|name|new' value='' onkeyup='UpdateQuestScriptPath()' style='width:400px;' /></td>\n";
			$strHTML .= $strOffset . "                <script>\n";
			$strHTML .= $strOffset . "                  function UpdateQuestScriptPath() {\n";
			$strHTML .= $strOffset . "                    let sp = document.getElementById('questScriptPath');\n";
			$strHTML .= $strOffset . "                    let qn = document.getElementById('questNameText').value;\n";
			$strHTML .= $strOffset . "                    let zone = document.getElementById('zoneNameText').value;\n";
			$strHTML .= $strOffset . "                    qn = qn.replaceAll(/\s+/g, '_');\n";
			$strHTML .= $strOffset . "                    qn = qn.toLowerCase();\n";
			$strHTML .= $strOffset . "                    let path = 'Quests/' + zone + '/' + qn;\n";
			$strHTML .= $strOffset . "                    sp.value = path.replaceAll(/[^\w\/]+/g, '') + '.lua';\n";
			$strHTML .= $strOffset . "                  }\n";
			$strHTML .= $strOffset . "                </script>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>type:</td>\n";
			$strHTML .= $strOffset . "                <td><input type='text' name='quests|type|new' value='' style='width:400px;' /></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>zone:</td>\n";
			$strHTML .= $strOffset . "                <td><input type='text' id='zoneNameText' name='quests|zone|new' value='" . $_GET['zone'] . "' onkeyup='UpdateQuestScriptPath()' style='width:400px;' /></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>level:</td>\n";
			$strHTML .= $strOffset . "                <td><input type='text' name='quests|level|new' value='1' style='width:400px;' /></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>difficulty:</td>\n";
			$strHTML .= $strOffset . "                <td><input type='text' name='quests|enc_level|new' value='1' style='width:400px;' /></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right' valign='top'>description:</td>\n";
			$strHTML .= $strOffset . "                <td>\n";
			$strHTML .= $strOffset . "                  <textarea name='quests|description|new' style='width:400px; height:150px;'></textarea>\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right' valign='top'>completed_text:</td>\n";
			$strHTML .= $strOffset . "                <td><textarea name='quests|completed_text|new' style='width:400px; height:150px;'></textarea></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>spawn_id:</td>\n";
			$strHTML .= $strOffset . "                <td><input type='text' name='quests|spawn_id|new' value='0' style='width:400px;' /></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td align='right' valign='bottom'>lua_script:</td>\n";
			$strHTML .= $strOffset . "                <td>\n";
			$strHTML .= $strOffset . "                  <strong>* RELATIVE PATH!</strong> ie. Quests/ZoneName/script_name.lua<br />\n";
			$strHTML .= $strOffset . "                  <input type='text' id='questScriptPath' name='quests|lua_script|new' value='' style='width:400px;' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>Minimum Earned Status:</td>\n";
			$strHTML .= $strOffset . "                <td><input type='text' name='quests|status_to_earn_min|new' value='0' style='width:400px;' /></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td width='100' align='right'>Maximum Earned Status:</td>\n";
			$strHTML .= $strOffset . "                <td><input type='text' name='quests|status_to_earn_max|new' value='0' style='width:400px;' /></td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "              <tr>\n";
			$strHTML .= $strOffset . "                <td colspan='2' align='center'>\n";
			$strHTML .= $strOffset . "                  <input type='submit' name='cmd' value='Insert' class='submit' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='object_id' value='new quest' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='table_name' value='quests' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='idx_name' value='quest_id' />\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='redir' value='quest_ai.php?zone=" . $_GET['zone'] . "&id=" . $next_quest_id . "&tab=register' />\n";
			$strHTML .= $strOffset . "                </td>\n";
			$strHTML .= $strOffset . "              </tr>\n";
			$strHTML .= $strOffset . "          	</table>\n";
			$strHTML .= $strOffset . "          </td>\n";
			$strHTML .= $strOffset . "        </tr>\n";
			$strHTML .= $strOffset . "      </table>\n";
			$strHTML .= $strOffset . "      </form>\n";
			$strHTML .= $strOffset . "    </td>\n";
			$strHTML .= $strOffset . "  </tr>\n";
	}
	$strHTML .= $strOffset . "  </table>\n";
	$strHTML .= $strOffset . "</div>\n";
	
	print($strHTML);
}

/////////////////////////////////////////////////////END OF FUNCTION//////////////////////////////////////////////////

function Details() 
{
	global $eq2, $q;
	
	$results = $q->GetQuestDetails();
	
?>
<div id="Editor">
	<table class="SubPanel" cellspacing="0" border="0">
		<tr>
			<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
		</tr>
		<tr>
			<td class="Title" colspan="2">
				Editing: <?= $q->quest_name ?> (<?= $q->quest_id ?>)
				<?php $q->PrintOffsiteLinks(); ?>
			</td>
		</tr>
		<tr>
			<td>
				<table class="SectionMain" cellspacing="0" cellpadding="4" border="0">
					<tr>
						<td colspan="9" class="SectionTitle">Quest Details</td>
					</tr>
					<tr bgcolor="#dddddd">
						<td width="55">id</td>
						<td width="55">quest_id</td>
						<td width="55">type</td>
						<td width="55">subtype</td>
						<td width="95">value</td>
						<td width="55">faction_id</td>
						<td width="55">quantity</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					<?php
					if( is_array($results) )
					{
						foreach($results as $data) 
						{
					?>
					<form method="post" name="multiForm|<?php print($data['id']); ?>" />
					<tr>
						<td>
							<input type="text" name="quest_details|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
						<td>
							<input type="text" name="quest_details|quest_id" value="<?php print($data['quest_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_quest_id" value="<?php print($data['quest_id']) ?>" />
						</td>
						<td>
							<?php $typeOptions = $q->GetOptionsQuestDetailTypes($data['type']) ?>
							<select name="quest_details|type" style="min-width:130px;">
							<?= $typeOptions ?>
							</select>
							<input type="hidden" name="orig_type" value="<?php print($data['type']) ?>" />
						</td>
						<td>
							<?php $subtypeOptions = $q->GetOptionsQuestDetailSubTypes($data['subtype']) ?>
							<select name="quest_details|subtype" style="min-width:130px;">
							<?= $subtypeOptions ?>
							</select>
							<input type="hidden" name="orig_subtype" value="<?php print($data['subtype']) ?>" />
						</td>
						<td>
							<input type="text" name="quest_details|value" value="<?php print($data['value']) ?>" style="width:55px;" />
							<input type="hidden" name="orig_value" value="<?php print($data['value']) ?>" />
							<?php
								if($data['subtype'] == 'Item' OR $data['subtype'] == 'Selectable'){ 
									//icon stuff
									//$htmlAttribute .= "        <td rowspan='2'><img src='eq2Icon.php?type=item&id=".$questItemName['icon']."&tier=".$questItemName['tier']."'>\n<br>";
									//$htmlAttribute .= $questItemName['bPvpDesc'] == 1 ? 'PVP' : 'NON-PVP';

									//$questItemName = $q->GetQuestRewardItemDescription($data['value']);

									//$htmlAttribute = "\n<div class='tooltip'>\n";
									//$htmlAttribute .= "  <img src='../images/nav_plain_green.png'>\n";
									//$htmlAttribute .= "  <span class='tooltiptext'>\n";

									//NEW STATS
									print($eq2->GenerateItemHover($data['value']));


									//THIS SECTION CAN GO AWAY ONCE TESTING OF THE NEW ITEM STAT BLOCK IS COMPLETE
									/*
									$htmlAttribute .= "    <div id='tooltipTitle'><a href='./items.php?id=".$data['value']."' target='_blank'>".ucwords($questItemName['name'])."(" . $questItemName['item_type'] . ")</a><img src='eq2Icon.php?type=item&id=".$questItemName['icon']."&tier=".$questItemName['tier']."'></div>";
									if($q->GetQuestRewardItemTierName($data['value']) > 0){
										$htmlAttribute .= "    <div id='tooltipTier'>".$q->GetQuestRewardItemTierName($data['value'])."</div>\n";
									}
									$htmlAttribute .= "    <div id='tooltipToggles'>".$q->GetQuestRewardItemToggleList($data['value'])."</div>\n";
									$htmlAttribute .= "    <div id='tooltipStats'>".$q->GetQuestRewardItemStats($data['value'])."</div>\n";
									$htmlAttribute .= "    <div id='tooltipEffects'>".$q->GetQuestRewardItemEffects($data['value'])."</div>\n";
									if($questItemName['item_type'] == 'Armor'){
										$htmlAttribute .= "    <div id='tooltipSkills'>".$q->GetQuestRewardItemSkills($questItemName['skill_id_req']) . "(" .$q->GetQuestRewardItemSlots($data['value']).")</div>\n";
									}
									$htmlAttribute .= "    <div id='tooltipDetails'>". $q->GetQuestRewardItemDetails($data['value'],$questItemName['item_type'])."</dev>\n";
									$htmlAttribute .= "    <div id='tooltipLevel'>".$q->GetQuestRewardItemLevel($data['value'])."</dev>\n";
									$htmlAttribute .= "    <div id='tooltipAdvClass'>".$q->GetQuestRewardItemAdventureClass($questItemName['adventure_classes'])."</dev>\n";
									$htmlAttribute .= "</div>\n";
									*/
									
									print($htmlAttribute);
								}
								
							?>
						</td>
						<td>
							<input type="text" name="quest_details|faction_id" value="<?php print($data['faction_id']) ?>" style="width:55px;" />
							<input type="hidden" name="orig_faction_id" value="<?php print($data['faction_id']) ?>" />
						</td>
						<td>
							<input type="text" name="quest_details|quantity" value="<?php print($data['quantity']) ?>" style="width:55px;" />
							<input type="hidden" name="orig_quantity" value="<?php print($data['quantity']) ?>" />
						</td>
						<td width="70"><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Update" class="submit" /><?php } ?></td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Delete" class="submit" /><?php } ?></td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $q->quest_name ?>" />
					<input type="hidden" name="table_name" value="quest_details" />
					</form>
					<?php
						}
					}
	
					if($eq2->CheckAccess(G_DEVELOPER)) 
					{
					?>
					<form method="post" name="sdForm|new" />
					<tr>
						<td align="center"><strong>new</strong></td>
						<td><input type="text" name="quest_details|quest_id|new" value="<?php print($q->quest_id) ?>" style="width:45px;  background-color:#ddd;" readonly /></td>
						<td>
							<?php $typeOptions = $q->GetOptionsQuestDetailTypes() ?>
							<select name="quest_details|type" style="min-width:130px;">
							<?= $typeOptions ?>
							</select>
						</td>
						<td>
							<?php $subtypeOptions = $q->GetOptionsQuestDetailSubTypes() ?>
							<select name="quest_details|subtype" style="min-width:130px;">
							<?= $subtypeOptions ?>
							</select>
						</td>
						<td><input type="text" name="quest_details|value|new" value="" style="width:55px;" /></td>
						<td><input type="text" name="quest_details|faction_id|new" value="" style="width:55px;" /></td>
						<td><input type="text" name="quest_details|quantity|new" value="" style="width:55px;" /></td>
						<td colspan="2"><input type="submit" name="cmd" value="Insert" class="submit" /></td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $q->quest_name ?>|<?= $q->quest_id ?>|New Detail" />
					<input type="hidden" name="table_name" value="quest_details" />
					</form>
				<?php } ?>
				</table>
			</td>
		</tr>
	</table>
	</div>
<?php
}


function script_editor() 
{
	global $eq2, $q;	

	// get Quest script info
	$row = $q->GetQuestScriptName($_GET['id']);
	
	// disassemble the script name to get the path
	$tmp = explode("/", $row['lua_script']);
	if( count($tmp) == 3 )
	{
		// if path is valid (quest + zone + name), proceed.
		$script_path = sprintf("%s/%s", $tmp[0], $tmp[1]);
		$script_full_name			= ( strlen($row['lua_script']) > 0 ) ? $row['lua_script'] : sprintf("%s/%s", $script_path, $q->GetCleanQuestScriptName());
	}
	else
		die("Invalid LUA Script name!");
	$objectID = sprintf("%s|%s", $q->quest_name, $q->quest_id);
	print($eq2->DisplayScriptEditor($script_full_name, $q->quest_name, $objectID, "quests"));
}

include("footer.php")
?>

<?php
/*
 * Quest-AI renderer stack.
 * This file is the isolated cloned quest workspace. The original editors/quests.php is not modified.
 * FIX26 distributes the assistant into the cloned Register / Details / Script tabs while keeping
 * the full Quest-AI diagnostics tab available.
 */
function QuestAIGetAssistantState($autoLoadSelectedQuest = true)
{
	global $admin, $q;

	$formAction = 'quest_ai.php?' . ($_SERVER['QUERY_STRING'] ?? '');
	$stepTypes = $admin->QuestFixStepTypes();
	$sessionKey = 'quest_' . (string)($q->quest_id ?? 0);
	if( !isset($_SESSION['quest_ai_state']) || !is_array($_SESSION['quest_ai_state']) )
		$_SESSION['quest_ai_state'] = array();
	$storedState = isset($_SESSION['quest_ai_state'][$sessionKey]) && is_array($_SESSION['quest_ai_state'][$sessionKey]) ? $_SESSION['quest_ai_state'][$sessionKey] : array();
	$defaultQuestName = !empty($q->quest_name) ? (string)$q->quest_name : 'Hunting the Huntresses';
	$questName = $_POST['quest_fix_census_name'] ?? ($storedState['questName'] ?? $defaultQuestName);
	$questOption = $_POST['quest_fix_census_option'] ?? ($storedState['questOption'] ?? '');
	$census = null;
	$model = null;
	$lua = '';
	$selectedQuestJson = '';
	$autoLoaded = false;
	$typeOverrides = isset($_POST['quest_fix_step_type']) && is_array($_POST['quest_fix_step_type']) ? $_POST['quest_fix_step_type'] : ($storedState['typeOverrides'] ?? array());
	$locationOverrides = isset($_POST['quest_fix_location_candidate']) && is_array($_POST['quest_fix_location_candidate']) ? $_POST['quest_fix_location_candidate'] : ($storedState['locationOverrides'] ?? array());
	$spawnCandidateOverrides = isset($_POST['quest_fix_spawn_candidate']) && is_array($_POST['quest_fix_spawn_candidate']) ? $_POST['quest_fix_spawn_candidate'] : ($storedState['spawnCandidateOverrides'] ?? array());

	if( isset($_POST['quest_fix_spawn_candidate_text']) && is_array($_POST['quest_fix_spawn_candidate_text']) )
	{
		foreach($_POST['quest_fix_spawn_candidate_text'] as $stepNo => $rawSpawnIds)
		{
			$ids = array();
			if( preg_match_all('/\b(\d+)\b/', (string)$rawSpawnIds, $mm) )
			{
				foreach($mm[1] as $idText)
				{
					$id = (int)$idText;
					if( $id > 0 && !in_array($id, $ids, true) )
						$ids[] = $id;
				}
			}
			if( count($ids) > 0 )
				$spawnCandidateOverrides[(int)$stepNo] = $ids;
		}
	}

	if( isset($_POST['quest_fix_census_parse']) )
	{
		// A fresh search resets the per-quest Quest-AI snapshot until a single match is selected.
		unset($_SESSION['quest_ai_state'][$sessionKey]);
		$storedState = array();
		$typeOverrides = array();
		$locationOverrides = array();
		$spawnCandidateOverrides = array();
		$census = $admin->QuestFixCensusSearchByName($questName, $questOption);
		if( !empty($census['ok']) && isset($census['quests']) && count($census['quests']) === 1 )
			$selectedQuestJson = $census['quests'][0]['json'];
	}
	elseif( isset($_POST['quest_fix_census_accept']) && !empty($_POST['quest_fix_census_json']) )
	{
		$decodedJson = base64_decode((string)$_POST['quest_fix_census_json'], true);
		if( $decodedJson !== false )
			$selectedQuestJson = $decodedJson;
		else
			$census = array('ok' => false, 'error' => 'The selected Census JSON payload could not be decoded.');
	}
	elseif( isset($_POST['quest_fix_update_lua']) && !empty($_POST['quest_fix_selected_json']) )
	{
		$decodedJson = base64_decode((string)$_POST['quest_fix_selected_json'], true);
		if( $decodedJson !== false )
			$selectedQuestJson = $decodedJson;
		else
			$census = array('ok' => false, 'error' => 'The selected Census JSON payload could not be decoded for Lua update.');
	}
	elseif( !empty($storedState['selectedQuestJson']) )
	{
		$selectedQuestJson = (string)$storedState['selectedQuestJson'];
	}
	elseif( $autoLoadSelectedQuest && !empty($q->quest_name) )
	{
		// In Quest-AI only: use the selected local quest name as a non-destructive AI preload.
		// Exact/single Census matches become immediately available in Register / Details / Script.
		$census = $admin->QuestFixCensusSearchByName($questName, $questOption);
		if( !empty($census['ok']) && isset($census['quests']) && count($census['quests']) === 1 )
		{
			$selectedQuestJson = $census['quests'][0]['json'];
			$autoLoaded = true;
		}
	}

	if( $selectedQuestJson !== '' )
	{
		$decoded = $admin->QuestFixCensusDecodeQuestJson($selectedQuestJson);
		if( !empty($decoded['ok']) )
		{
			$model = $admin->QuestFixCensusBuildQuestModel($decoded['quest_data']);
			if( (is_array($typeOverrides) && count($typeOverrides) > 0) || (is_array($locationOverrides) && count($locationOverrides) > 0) || (is_array($spawnCandidateOverrides) && count($spawnCandidateOverrides) > 0) )
				$model = $admin->QuestFixApplyTypeOverrides($model, $typeOverrides, $locationOverrides, $spawnCandidateOverrides);
			$lua = $admin->QuestFixBuildLuaFromCensusModel($model);
		}
		else
			$census = $decoded;
	}

	if( $selectedQuestJson !== '' && is_array($model) && !empty($model['ok']) )
	{
		$_SESSION['quest_ai_state'][$sessionKey] = array(
			'questName' => $questName,
			'questOption' => $questOption,
			'selectedQuestJson' => $selectedQuestJson,
			'typeOverrides' => is_array($typeOverrides) ? $typeOverrides : array(),
			'locationOverrides' => is_array($locationOverrides) ? $locationOverrides : array(),
			'spawnCandidateOverrides' => is_array($spawnCandidateOverrides) ? $spawnCandidateOverrides : array()
		);
	}

	return array(
		'formAction' => $formAction,
		'stepTypes' => $stepTypes,
		'questName' => $questName,
		'questOption' => $questOption,
		'census' => $census,
		'model' => $model,
		'lua' => $lua,
		'selectedQuestJson' => $selectedQuestJson,
		'autoLoaded' => $autoLoaded
	);
}

function QuestAIRenderSharedScripts()
{
	static $printed = false;
	if( $printed )
		return;
	$printed = true;
	?>
	<script>
	function QuestFixLocalQuestLookupAJAX() {
		var input = document.getElementById('quest_fix_census_name');
		var suggest = document.getElementById('quest_fix_census_suggest');
		if (!input || !suggest) return;
		if (input.value.length < 1) {
			suggest.innerHTML = '';
			return;
		}
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			var str = escape(input.value);
			txtSearchAjaxInput = 'quest_fix_census_name';
			searchSuggestDiv = 'quest_fix_census_suggest';
			ajaxSelectCallback = null;
			searchReq.open('GET', '../ajax/eq2Ajax.php?type=luQ&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest;
			searchReq.send(null);
		}
	}
	function QuestFixSpawnLookupAJAX(stepNo) {
		var input = document.getElementById('quest_fix_spawn_lookup_' + stepNo);
		if (!input || input.value.length < 1) return;
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			var str = escape(input.value);
			txtSearchAjaxInput = 'quest_fix_spawn_lookup_' + stepNo;
			searchSuggestDiv = 'quest_fix_spawn_suggest_' + stepNo;
			ajaxSelectCallback = function() { QuestFixAddSpawnOverride(stepNo); };
			searchReq.open('GET', '../ajax/eq2Ajax.php?type=questFixSpawn&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest;
			searchReq.send(null);
		}
	}
	function QuestFixAddSpawnOverride(stepNo) {
		var lookup = document.getElementById('quest_fix_spawn_lookup_' + stepNo);
		var hidden = document.getElementById('quest_fix_spawn_ids_' + stepNo);
		var label = document.getElementById('quest_fix_spawn_manual_' + stepNo);
		if (!lookup || !hidden || !label) return;
		var match = lookup.value.match(/\((\d+)\)\s*$/);
		if (!match) return;
		var id = match[1];
		var ids = hidden.value ? hidden.value.split(/\s*,\s*/) : [];
		if (ids.indexOf(id) === -1) ids.push(id);
		hidden.value = ids.join(', ');
		label.innerHTML = '<strong>Manual ID override:</strong> ' + hidden.value;
		lookup.value = '';
		ajaxSelectCallback = null;
	}
	function QuestFixClearSpawnOverride(stepNo) {
		var hidden = document.getElementById('quest_fix_spawn_ids_' + stepNo);
		var label = document.getElementById('quest_fix_spawn_manual_' + stepNo);
		var suggest = document.getElementById('quest_fix_spawn_suggest_' + stepNo);
		if (hidden) hidden.value = '';
		if (label) label.innerHTML = '<strong>Manual ID override:</strong> none / auto ranking active';
		if (suggest) suggest.innerHTML = '';
	}
	</script>
	<?php
}

function QuestAIRenderAssistantNotice($state, $title)
{
	$census = $state['census'];
	if( is_array($census) && empty($census['ok']) )
	{
		?>
		<table width="100%" cellpadding="4" cellspacing="0" border="0" style="margin-top:6px;">
			<tr bgcolor="#f5cccc"><td><strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($census['error'] ?? 'Unknown Census error.', ENT_QUOTES, 'UTF-8') ?></td></tr>
		</table>
		<?php
	}
	elseif( is_array($census) && !empty($census['ok']) && isset($census['quests']) && count($census['quests']) === 0 )
	{
		?>
		<table width="100%" cellpadding="4" cellspacing="0" border="0" style="margin-top:6px;">
			<tr bgcolor="#fff0cc"><td><strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>:</strong> No Census quest matched this name/filter.</td></tr>
		</table>
		<?php
	}
}

function QuestAIRenderQuestPicker($state, $compact = false)
{
	$census = $state['census'];
	$formAction = $state['formAction'];
	$questName = $state['questName'];
	$questOption = $state['questOption'];
	?>
	<form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
		<table width="100%" cellpadding="4" cellspacing="0" border="0">
			<tr>
				<td class="Label" width="180">Census Quest Name:</td>
				<td class="Detail">
					<input type="text" id="quest_fix_census_name" name="quest_fix_census_name" value="<?= htmlspecialchars($questName, ENT_QUOTES, 'UTF-8') ?>" onkeyup="QuestFixLocalQuestLookupAJAX();" autocomplete="off" style="width:520px;" />
					<div id="quest_fix_census_suggest"></div>
					<?php if(!$compact) { ?><span style="font-size:11px;">Ghosted matches reuse the active-world Quest lookup. Click one, then run Census search.</span><?php } ?>
				</td>
			</tr>
			<tr>
				<td class="Label">Optional Census Filter:</td>
				<td class="Detail"><input type="text" name="quest_fix_census_option" value="<?= htmlspecialchars($questOption, ENT_QUOTES, 'UTF-8') ?>" style="width:320px;" /> Example: <code>crc=2378636280</code></td>
			</tr>
			<tr>
				<td colspan="2" align="center"><input type="submit" name="quest_fix_census_parse" value="Census" title="Census suchen + parsen" class="submit" /></td>
			</tr>
		</table>
	</form>
	<?php
	QuestAIRenderAssistantNotice($state, 'Census');

	if( is_array($census) && !empty($census['ok']) && isset($census['quests']) && count($census['quests']) > 1 )
	{
		?>
		<table width="100%" cellpadding="4" cellspacing="0" border="0" style="margin-top:8px;">
			<tr bgcolor="#cccccc"><td colspan="7"><strong>Multiple Census quests found — choose one with “Übernehmen” to keep it active in Quest-AI</strong></td></tr>
			<tr bgcolor="#dddddd"><td><strong>Name</strong></td><td><strong>ID</strong></td><td><strong>CRC</strong></td><td><strong>Category</strong></td><td><strong>Level</strong></td><td><strong>Tier</strong></td><td><strong>Action</strong></td></tr>
			<?php foreach($census['quests'] as $questRow) { ?>
			<tr>
				<td><?= htmlspecialchars($questRow['name'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['id'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['crc'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['category'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['level'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['tier'], ENT_QUOTES, 'UTF-8') ?></td>
				<td>
					<form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" style="display:inline;">
						<input type="hidden" name="quest_fix_census_name" value="<?= htmlspecialchars($questName, ENT_QUOTES, 'UTF-8') ?>" />
						<input type="hidden" name="quest_fix_census_option" value="<?= htmlspecialchars($questOption, ENT_QUOTES, 'UTF-8') ?>" />
						<input type="hidden" name="quest_fix_census_json" value="<?= htmlspecialchars(base64_encode($questRow['json']), ENT_QUOTES, 'UTF-8') ?>" />
						<input type="submit" name="quest_fix_census_accept" value="Übernehmen" class="submit" />
					</form>
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
	}
}

function QuestAIRenderSelectedQuestSummary($state, $showAutoLoadHint = true)
{
	$model = $state['model'];
	if( !is_array($model) || empty($model['ok']) )
		return;
	?>
	<table width="100%" cellpadding="4" cellspacing="0" border="0" style="margin-top:8px;">
		<tr bgcolor="#cccccc"><td colspan="2"><strong>Quest-AI Reference Snapshot</strong></td></tr>
		<tr><td class="Label" width="180">Name:</td><td class="Detail"><?= htmlspecialchars($model['summary']['name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
		<tr><td class="Label">ID / CRC:</td><td class="Detail"><?= htmlspecialchars($model['summary']['id'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($model['summary']['crc'], ENT_QUOTES, 'UTF-8') ?></td></tr>
		<tr><td class="Label">Category / Level / Tier:</td><td class="Detail"><?= htmlspecialchars($model['summary']['category'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($model['summary']['level'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($model['summary']['tier'], ENT_QUOTES, 'UTF-8') ?></td></tr>
		<tr><td class="Label">Quest Branches:</td><td class="Detail"><?= (int)count($model['branches']) ?></td></tr>
		<tr><td class="Label">Assistant Source:</td><td class="Detail">Direct Census fallback + active world DB + Wiki Assist + SpawnScripts</td></tr>
		<tr><td class="Label">Local EQ2Emu Quest:</td><td class="Detail"><?php if(!empty($model['local_quest'])) { ?>ID <?= (int)$model['local_quest']['id'] ?> — <?= htmlspecialchars($model['local_quest']['name'], ENT_QUOTES, 'UTF-8') ?><?php } else { ?>Not found by exact name in active world DB<?php } ?></td></tr>
		<tr><td class="Label">EQ2 Wiki Assist:</td><td class="Detail"><?php if(!empty($model['wiki']['ok'])) { ?>Loaded<?= !empty($model['wiki']['source']) ? ' [' . htmlspecialchars($model['wiki']['source'], ENT_QUOTES, 'UTF-8') . ']' : '' ?> — <?= (int)count($model['wiki']['coordinates'] ?? array()) ?> waypoint coordinate(s) parsed<?php } else { ?>Not available<?php if(!empty($model['wiki']['error'])) { ?> — <?= htmlspecialchars($model['wiki']['error'], ENT_QUOTES, 'UTF-8') ?><?php } ?><?php } ?></td></tr>
		<?php if( $showAutoLoadHint && !empty($state['autoLoaded']) ) { ?><tr><td class="Label">Quest-AI preload:</td><td class="Detail">Auto-loaded from the selected local quest name in the cloned Quest-AI workspace.</td></tr><?php } ?>
	</table>
	<?php
}

function QuestAIGetPendingCensusChoiceText($state)
{
	$census = $state['census'] ?? null;
	if( is_array($census) && !empty($census['ok']) && isset($census['quests']) && count($census['quests']) > 1 )
		return 'Multiple Census quests were found. Choose the intended entry above with “Übernehmen”; the selected quest will then stay active across all Quest-AI tabs.';
	return 'No single Census quest is loaded yet. Use the Quest-AI Register panel or the full Quest-AI tab first.';
}

function QuestAIRenderStepsForm($state, $title = 'Quest-AI Step Resolver')
{
	$model = $state['model'];
	if( !is_array($model) || empty($model['ok']) )
	{
		?>
		<table width="100%" cellpadding="4" cellspacing="0" border="0" style="margin-top:8px;"><tr bgcolor="#fff0cc"><td><strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars(QuestAIGetPendingCensusChoiceText($state), ENT_QUOTES, 'UTF-8') ?></td></tr></table>
		<?php
		return;
	}
	$formAction = $state['formAction'];
	$questName = $state['questName'];
	$questOption = $state['questOption'];
	$selectedQuestJson = $state['selectedQuestJson'];
	$stepTypes = $state['stepTypes'];
	?>
	<form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
		<input type="hidden" name="quest_fix_census_name" value="<?= htmlspecialchars($questName, ENT_QUOTES, 'UTF-8') ?>" />
		<input type="hidden" name="quest_fix_census_option" value="<?= htmlspecialchars($questOption, ENT_QUOTES, 'UTF-8') ?>" />
		<input type="hidden" name="quest_fix_selected_json" value="<?= htmlspecialchars(base64_encode($selectedQuestJson), ENT_QUOTES, 'UTF-8') ?>" />
		<table width="100%" cellpadding="4" cellspacing="0" border="0" style="margin-top:8px;">
			<tr bgcolor="#cccccc"><td colspan="13"><strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — Type can be changed, then press “Update Lua Preview”</strong></td></tr>
			<tr bgcolor="#dddddd"><td><strong>Step</strong></td><td><strong>Reference Text</strong></td><td><strong>Auto Type</strong></td><td><strong>Selected Type</strong></td><td><strong>Count</strong></td><td><strong>Target</strong></td><td><strong>Zone</strong></td><td><strong>Best ID</strong></td><td><strong>Best Match</strong></td><td><strong>Score</strong></td><td><strong>Script Evidence</strong></td><td><strong>Candidate / Location Override</strong></td><td><strong>Override?</strong></td></tr>
			<?php foreach($model['branches'] as $branch) { $best = count($branch['candidates']) > 0 ? $branch['candidates'][0] : null; $selectedType = $branch['analysis']['step_type']; $autoType = $branch['analysis']['auto_type'] ?? $selectedType; $bestId = $selectedType === 'Location' ? 0 : (int)$branch['best_spawn_id']; $scriptEvidence = $best !== null && !empty($best['script_evidence']) ? $best['script_evidence'] : array(); $locationText = $selectedType === 'Location' && !empty($branch['best_location']) ? (($branch['best_location']['source_zone_name'] ?? '') . ' @ ' . ($branch['best_location']['x'] ?? 0) . ', ' . ($branch['best_location']['y'] ?? 0) . ', ' . ($branch['best_location']['z'] ?? 0)) : 'Unresolved'; $selectedLocationCandidateId = (int)($branch['best_location']['id'] ?? 0); $selectedSpawnCandidateIds = isset($branch['selected_spawn_candidate_ids']) && is_array($branch['selected_spawn_candidate_ids']) ? array_values(array_unique(array_map('intval', $branch['selected_spawn_candidate_ids']))) : array(); ?>
			<tr<?= $best !== null ? ' bgcolor="#d8f0d8"' : '' ?>>
				<td><?= (int)$branch['step_number'] ?></td>
				<td><?= htmlspecialchars($branch['step_text'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($autoType, ENT_QUOTES, 'UTF-8') ?></td>
				<td><select name="quest_fix_step_type[<?= (int)$branch['step_number'] ?>]"><?php foreach($stepTypes as $stepType) { ?><option value="<?= htmlspecialchars($stepType, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedType === $stepType ? ' selected="selected"' : '' ?>><?= htmlspecialchars($stepType, ENT_QUOTES, 'UTF-8') ?></option><?php } ?></select></td>
				<td><?= (int)$branch['analysis']['count'] ?></td>
				<td><?= htmlspecialchars($branch['analysis']['target'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($branch['analysis']['zone'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= (int)$bestId ?></td>
				<td><?= $best !== null ? htmlspecialchars($best['name'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
				<td><?= $best !== null ? (int)$best['score'] : 0 ?></td>
				<td><?php if(!empty($scriptEvidence['has_set_step_complete'])) { ?>Exact step complete<?php } elseif(!empty($scriptEvidence['has_get_step'])) { ?>Quest step check<?php } elseif(!empty($scriptEvidence['has_script'])) { ?>Script present<?php } else { ?>—<?php } ?></td>
				<td>
					<?php if( $selectedType === 'Location' ) { ?>
						<div><strong><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></strong><?= !empty($branch['location_auto_selected']) ? ' <em>(auto)</em>' : '' ?></div>
						<select name="quest_fix_location_candidate[<?= (int)$branch['step_number'] ?>]" style="width:520px; max-width:100%; margin-top:4px;"><option value="0"<?= $selectedLocationCandidateId === 0 ? ' selected="selected"' : '' ?>>unresolved / choose later</option><?php foreach(array_slice($branch['candidates'], 0, 25) as $locationCandidate) { $candidateId = (int)($locationCandidate['id'] ?? 0); $candidateSource = $locationCandidate['source'] ?? 'DB'; $candidateLabel = '[' . $candidateSource . '] ' . ($locationCandidate['name'] ?? 'Location') . ' | ' . ($locationCandidate['source_zone_name'] ?? '') . ' @ ' . ($locationCandidate['x'] ?? 0) . ', ' . ($locationCandidate['y'] ?? 0) . ', ' . ($locationCandidate['z'] ?? 0) . ' | score ' . (int)($locationCandidate['score'] ?? 0); ?><option value="<?= $candidateId ?>"<?= $selectedLocationCandidateId === $candidateId ? ' selected="selected"' : '' ?>><?= htmlspecialchars($candidateLabel, ENT_QUOTES, 'UTF-8') ?></option><?php } ?></select>
					<?php } elseif( $selectedType === 'Kill' || $selectedType === 'Chat' ) { $resolvedSpawnIds = !empty($branch['best_spawn_ids']) ? implode(', ', array_map('intval', $branch['best_spawn_ids'])) : ((int)($branch['best_spawn_id'] ?? 0) > 0 ? (string)(int)$branch['best_spawn_id'] : 'unresolved'); $manualSpawnText = count($selectedSpawnCandidateIds) > 0 ? implode(', ', $selectedSpawnCandidateIds) : ''; $stepNo = (int)$branch['step_number']; ?>
						<div><strong>Resolved spawn ID(s): <?= htmlspecialchars($resolvedSpawnIds, ENT_QUOTES, 'UTF-8') ?></strong><?= !empty($branch['spawn_candidate_manual_selected']) ? ' <em>(manual)</em>' : ' <em>(auto)</em>' ?></div>
						<input type="hidden" id="quest_fix_spawn_ids_<?= $stepNo ?>" name="quest_fix_spawn_candidate_text[<?= $stepNo ?>]" value="<?= htmlspecialchars($manualSpawnText, ENT_QUOTES, 'UTF-8') ?>" />
						<div id="quest_fix_spawn_manual_<?= $stepNo ?>" style="font-size:11px; margin-top:2px;"><strong>Manual ID override:</strong> <?= $manualSpawnText !== '' ? htmlspecialchars($manualSpawnText, ENT_QUOTES, 'UTF-8') : 'none / auto ranking active' ?></div>
						<div style="margin-top:4px;"><input type="text" id="quest_fix_spawn_lookup_<?= $stepNo ?>" autocomplete="off" class="box" style="width:420px;" placeholder="Search spawn name or ID; click a ghosted result to add it" onkeyup="QuestFixSpawnLookupAJAX(<?= $stepNo ?>);" /><input type="button" value="Clear manual IDs" class="submit" onclick="QuestFixClearSpawnOverride(<?= $stepNo ?>);" /><div id="quest_fix_spawn_suggest_<?= $stepNo ?>"></div></div>
						<?php if( count($branch['candidates']) > 0 ) { ?><div style="font-size:11px; margin-top:4px;"><strong>Top ranked auto candidates:</strong> <?php $preview = array(); foreach(array_slice($branch['candidates'], 0, 5) as $spawnCandidate) { $preview[] = '#' . (int)($spawnCandidate['id'] ?? 0) . ' ' . ($spawnCandidate['name'] ?? 'Spawn'); } echo htmlspecialchars(implode(' | ', $preview), ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
					<?php } else { ?>—<?php } ?>
				</td>
				<td><?= !empty($branch['analysis']['manual_override']) ? 'Yes' : 'No' ?></td>
			</tr>
			<?php } ?>
		</table>
		<p><input type="submit" name="quest_fix_update_lua" value="Update Lua Preview" class="submit" /></p>
	</form>
	<?php
}

function QuestAIRenderLuaPreview($state, $title = 'Quest-AI Generated Lua Preview')
{
	$model = $state['model'];
	$lua = $state['lua'];
	if( !is_array($model) || empty($model['ok']) )
	{
		?>
		<table width="100%" cellpadding="4" cellspacing="0" border="0" style="margin-top:8px;"><tr bgcolor="#fff0cc"><td><strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars(QuestAIGetPendingCensusChoiceText($state), ENT_QUOTES, 'UTF-8') ?></td></tr></table>
		<?php
		return;
	}
	?>
	<table width="100%" cellpadding="4" cellspacing="0" border="0" style="margin-top:8px;">
		<tr bgcolor="#cccccc"><td><strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong></td></tr>
		<tr><td><textarea rows="36" style="width:99%; font-family:monospace;"><?= htmlspecialchars($lua, ENT_QUOTES, 'UTF-8') ?></textarea></td></tr>
	</table>
	<?php
}


function QuestAIHtmlCapture($callable)
{
	ob_start();
	$callable();
	return ob_get_clean();
}

function QuestAICompareBadge($same, $leftValue = '', $rightValue = '')
{
	if( $rightValue === '' || $rightValue === null )
		return '<span style="color:#777;">—</span>';
	if( $same )
		return '<span style="display:inline-block; padding:1px 5px; background:#d8f0d8; border:1px solid #9bc59b;">gleich</span>';
	return '<span style="display:inline-block; padding:1px 5px; background:#fff0cc; border:1px solid #d4b76d;">abweichend</span>';
}

function QuestAIRenderRegisterCensusSearchHtml($state)
{
	$html = "<table class='SectionMain' cellspacing='0' cellpadding='4' border='0' style='width:900px; margin:0 auto 8px auto;'>\n";
	$html .= "<tr><td class='SectionTitle' style='text-align:center;'>Census Suche / Referenz neu laden</td></tr>\n";
	$html .= "<tr><td class='SectionBody' style='text-align:center;'>\n";
	$html .= QuestAIHtmlCapture(function() use ($state) { QuestAIRenderQuestPicker($state, true); });
	$html .= QuestAIHtmlCapture(function() use ($state) { QuestAIRenderAssistantNotice($state, 'Census'); });
	$html .= "</td></tr></table>\n";
	return $html;
}

function QuestAIRenderRegisterRecommendationHtml($state, $localData)
{
	$model = $state['model'] ?? null;
	$summary = is_array($model) && !empty($model['ok']) ? ($model['summary'] ?? array()) : array();
	$localData = is_array($localData) ? $localData : array();
	$questName = (string)($summary['name'] ?? '');
	$category = (string)($summary['category'] ?? '');
	$level = (string)($summary['level'] ?? '');
	$questId = (string)($summary['id'] ?? '');
	$crc = (string)($summary['crc'] ?? '');
	$wiki = is_array($model) && isset($model['wiki']) && is_array($model['wiki']) ? $model['wiki'] : array();
	$wikiCoordinateCount = !empty($wiki['coordinates']) && is_array($wiki['coordinates']) ? count($wiki['coordinates']) : 0;
	if( !empty($wiki['ok']) )
	{
		$wikiMode = trim((string)($wiki['mode'] ?? 'loaded'));
		$wikiStatus = $wikiCoordinateCount > 0
			? ('Seite gefunden über ' . $wikiMode . ' — ' . $wikiCoordinateCount . ' Waypoint-Koordinate(n)')
			: 'Seite gefunden — keine Waypoint-Koordinaten auf dieser Questseite';
	}
	else
	{
		$wikiStatus = 'nicht geladen';
	}
	$completionTexts = is_array($model) ? ($model['quest_completion_texts'] ?? array()) : array();
	$completionText = isset($completionTexts[0]) ? (string)$completionTexts[0] : '';
	$branches = is_array($model) && isset($model['branches']) && is_array($model['branches']) ? count($model['branches']) : 0;
	$typeRecommendation = $category;
	$zoneRecommendation = '';
	if( $category !== '' && strcasecmp((string)($localData['zone'] ?? ''), $category) === 0 )
		$zoneRecommendation = $category;

	$selectedQuestRaw = array();
	if( !empty($state['selectedQuestJson']) )
	{
		$tmp = json_decode((string)$state['selectedQuestJson'], true);
		if( is_array($tmp) )
			$selectedQuestRaw = $tmp;
	}
	$description = '';
	foreach(array('description', 'quest_description', 'text') as $descriptionKey)
	{
		if( isset($selectedQuestRaw[$descriptionKey]) && !is_array($selectedQuestRaw[$descriptionKey]) )
		{
			$description = trim((string)$selectedQuestRaw[$descriptionKey]);
			break;
		}
	}

	$readonlyInput = function($value) {
		return "<input type='text' readonly='readonly' value='" . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . "' style='width:400px; background:#f7f7f7;' />";
	};
	$readonlyText = function($value) {
		return "<textarea readonly='readonly' style='width:400px; height:150px; background:#f7f7f7;'>" . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . "</textarea>";
	};
	$dashInput = $readonlyInput('—');
	$dashText = $readonlyText('—');
	$notFromCensus = "<span style='color:#777;'>nicht aus Census gesetzt</span>";
	$notUnambiguous = "<span style='color:#777;'>nicht aus Census eindeutig gesetzt</span>";

	$html = "<table class='SectionMain' cellspacing='0' cellpadding='4' border='0' style='width:670px; max-width:670px;'>\n";
	$html .= "<tr><td class='SectionTitle'>Register Quest — AI-/Census-Vergleich</td></tr>\n";
	$html .= "<tr><td class='SectionBody'>\n";

	if( !is_array($model) || empty($model['ok']) )
	{
		$html .= "<table width='100%' cellpadding='4' cellspacing='0' border='0' style='margin-top:8px;'><tr bgcolor='#fff0cc'><td><strong>AI-Empfehlungen:</strong> " . htmlspecialchars(QuestAIGetPendingCensusChoiceText($state), ENT_QUOTES, 'UTF-8') . "</td></tr></table>\n";
		$html .= "<table width='100%' cellpadding='4' cellspacing='0' border='0' style='margin-top:8px;'>\n";
		$html .= "<tr><td class='Label' style='width:165px;'>quest_id:</td><td>—</td><td><span style='color:#777;'>Census hat keine lokale DB-ID</span></td></tr>\n";
		$html .= "<tr><td class='Label'>name:</td><td>" . $readonlyInput($questName !== '' ? $questName : '—') . "</td><td>" . ($questName !== '' ? QuestAICompareBadge(strcasecmp((string)($localData['name'] ?? ''), $questName) === 0, (string)($localData['name'] ?? ''), $questName) : $notFromCensus) . "</td></tr>\n";
		$html .= "<tr><td class='Label'>type:</td><td>" . $dashInput . "</td><td>" . $notUnambiguous . "</td></tr>\n";
		$html .= "<tr><td class='Label'>zone:</td><td>" . $dashInput . "</td><td>" . $notUnambiguous . "</td></tr>\n";
		$html .= "<tr><td class='Label'>level:</td><td>" . $dashInput . "</td><td>" . $notFromCensus . "</td></tr>\n";
		$html .= "<tr><td class='Label'>difficulty:</td><td>" . $dashInput . "</td><td>" . $notFromCensus . "</td></tr>\n";
		$html .= "<tr><td class='Label' valign='top'>description:</td><td>" . $dashText . "</td><td valign='top'>" . $notFromCensus . "</td></tr>\n";
		$html .= "<tr><td class='Label' valign='top'>completed_text:</td><td>" . $dashText . "</td><td valign='top'>" . $notFromCensus . "</td></tr>\n";
		$html .= "<tr><td class='Label'>spawn_id:</td><td>" . $dashInput . "</td><td><span style='color:#777;'>später aus AI-Questgeberanalyse</span></td></tr>\n";
		$html .= "<tr><td class='Label'>lua_script:</td><td>" . $dashInput . "</td><td><span style='color:#777;'>später aus Lua-Pfadprüfung</span></td></tr>\n";
		$html .= "<tr><td class='Label'>Minimum Earned Status:</td><td>" . $dashInput . "</td><td>" . $notFromCensus . "</td></tr>\n";
		$html .= "<tr><td class='Label'>Maximum Earned Status:</td><td>" . $dashInput . "</td><td>" . $notFromCensus . "</td></tr>\n";
		$html .= "</table>\n";

		$html .= "</td></tr></table>\n";
		return $html;
	}

	$html .= "<table width='100%' cellpadding='4' cellspacing='0' border='0' style='margin-top:8px;'>\n";
	$html .= "<tr><td class='Label' style='width:165px;'>quest_id:</td><td>—</td><td><span style='color:#777;'>Census hat keine lokale DB-ID</span></td></tr>\n";
	$html .= "<tr><td class='Label'>name:</td><td>" . $readonlyInput($questName !== '' ? $questName : '—') . "</td><td>" . QuestAICompareBadge(strcasecmp((string)($localData['name'] ?? ''), $questName) === 0, (string)($localData['name'] ?? ''), $questName) . "</td></tr>\n";
	$html .= "<tr><td class='Label'>type:</td><td>" . $readonlyInput($typeRecommendation !== '' ? $typeRecommendation : '—') . "</td><td>" . ($typeRecommendation !== '' ? QuestAICompareBadge(strcasecmp((string)($localData['type'] ?? ''), $typeRecommendation) === 0, (string)($localData['type'] ?? ''), $typeRecommendation) : $notUnambiguous) . "</td></tr>\n";
	$html .= "<tr><td class='Label'>zone:</td><td>" . $readonlyInput($zoneRecommendation !== '' ? $zoneRecommendation : '—') . "</td><td>" . ($zoneRecommendation !== '' ? QuestAICompareBadge(strcasecmp((string)($localData['zone'] ?? ''), $zoneRecommendation) === 0, (string)($localData['zone'] ?? ''), $zoneRecommendation) : $notUnambiguous) . "</td></tr>\n";
	$html .= "<tr><td class='Label'>level:</td><td>" . $readonlyInput($level !== '' ? $level : '—') . "</td><td>" . QuestAICompareBadge((string)($localData['level'] ?? '') === $level, (string)($localData['level'] ?? ''), $level) . "</td></tr>\n";
	$html .= "<tr><td class='Label'>difficulty:</td><td>" . $dashInput . "</td><td>" . $notFromCensus . "</td></tr>\n";
	$html .= "<tr><td class='Label' valign='top'>description:</td><td>" . ($description !== '' ? $readonlyText($description) : $dashText) . "</td><td valign='top'>" . ($description !== '' ? QuestAICompareBadge(trim((string)($localData['description'] ?? '')) === trim($description), (string)($localData['description'] ?? ''), $description) : $notFromCensus) . "</td></tr>\n";
	$html .= "<tr><td class='Label' valign='top'>completed_text:</td><td>" . ($completionText !== '' ? $readonlyText($completionText) : $dashText) . "</td><td valign='top'>" . ($completionText !== '' ? QuestAICompareBadge(trim((string)($localData['completed_text'] ?? '')) === trim($completionText), (string)($localData['completed_text'] ?? ''), $completionText) : $notFromCensus) . "</td></tr>\n";
	$html .= "<tr><td class='Label'>spawn_id:</td><td>" . $dashInput . "</td><td><span style='color:#777;'>später aus AI-Questgeberanalyse</span></td></tr>\n";
	$html .= "<tr><td class='Label'>lua_script:</td><td>" . $dashInput . "</td><td><span style='color:#777;'>später aus Lua-Pfadprüfung</span></td></tr>\n";
	$html .= "<tr><td class='Label'>Minimum Earned Status:</td><td>" . $dashInput . "</td><td>" . $notFromCensus . "</td></tr>\n";
	$html .= "<tr><td class='Label'>Maximum Earned Status:</td><td>" . $dashInput . "</td><td>" . $notFromCensus . "</td></tr>\n";
	$html .= "<tr><td colspan='3' style='padding-top:5px;'>\n";
	$html .= "  <table width='100%' cellspacing='0' cellpadding='2' border='0'>\n";
	$html .= "    <tr>\n";
	$html .= "      <td class='Label' style='text-align:center; width:33%;'>Deleteable: —</td>\n";
	$html .= "      <td class='Label' style='text-align:center; width:33%;'>Shareable: —</td>\n";
	$html .= "      <td class='Label' style='text-align:center; width:33%;'>Hide Reward: —</td>\n";
	$html .= "    </tr>\n";
	$html .= "    <tr><td colspan='3' style='text-align:center;'>" . $notFromCensus . "</td></tr>\n";
	$html .= "  </table>\n";
	$html .= "</td></tr>\n";
	$html .= "</table>\n";

	$html .= "<table width='100%' cellpadding='4' cellspacing='0' border='0' style='margin-top:8px;'>\n";
	$html .= "<tr bgcolor='#cccccc'><td colspan='2'><strong>Quest-AI Referenz</strong></td></tr>\n";
	$html .= "<tr><td class='Label' style='width:165px;'>Census ID / CRC:</td><td>" . htmlspecialchars($questId . ' / ' . $crc, ENT_QUOTES, 'UTF-8') . "</td></tr>\n";
	$html .= "<tr><td class='Label'>Quest Branches:</td><td>" . (int)$branches . "</td></tr>\n";
	$html .= "<tr><td class='Label'>EQ2 Wiki Assist:</td><td>" . htmlspecialchars($wikiStatus, ENT_QUOTES, 'UTF-8') . "</td></tr>\n";
	$html .= "<tr><td class='Label' valign='top'>Hinweis:</td><td>Diese rechte Box macht nur den Vergleich sichtbar. Sie überschreibt die linke DB-Quest nicht automatisch.</td></tr>\n";
	$html .= "</table>\n";

	$html .= "</td></tr></table>\n";
	return $html;
}

function QuestAIRenderSubPanelStart($sectionTitle)
{
	?>
	<div id="Editor" class="QuestAIIntegration" style="margin-top:12px;">
		<table class="SubPanel" cellspacing="0" border="0">
			<tr><td class="Title" colspan="2">Quest-AI: <?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></td></tr>
			<tr><td><table class="SectionMain" cellspacing="0" cellpadding="4" border="0" style="width:100%;"><tr><td class="SectionTitle"><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></td></tr><tr><td class="SectionBody">
	<?php
}

function QuestAIRenderSubPanelEnd()
{
	?>
			</td></tr></table></td></tr>
		</table>
	</div>
	<?php
}

function QuestAIRenderRegisterIntegration()
{
	QuestAIRenderSharedScripts();
	$state = QuestAIGetAssistantState(true);
	QuestAIRenderSubPanelStart('Register Companion');
	?>
	<p style="margin-top:0;"><strong>AI role in this cloned Register tab:</strong> select or confirm the matching Census quest, then use the snapshot to compare the local quest registration against Census / Wiki / DB context. The original Quests tab remains untouched.</p>
	<?php
	QuestAIRenderQuestPicker($state, false);
	QuestAIRenderSelectedQuestSummary($state, true);
	QuestAIRenderSubPanelEnd();
}

function QuestAIRenderDetailsIntegration()
{
	QuestAIRenderSharedScripts();
	$state = QuestAIGetAssistantState(true);
	QuestAIRenderSubPanelStart('Details Companion');
	?>
	<p style="margin-top:0;"><strong>AI role in this cloned Details tab:</strong> inspect and correct parsed branch types, Kill/Chat spawn candidates, Location picks, counts, and manual overrides before the Lua preview is regenerated.</p>
	<?php
	QuestAIRenderSelectedQuestSummary($state, true);
	QuestAIRenderStepsForm($state, 'AI Step / Candidate Resolver');
	QuestAIRenderSubPanelEnd();
}

function QuestAIRenderScriptIntegration()
{
	QuestAIRenderSharedScripts();
	$state = QuestAIGetAssistantState(true);
	QuestAIRenderSubPanelStart('Script Companion');
	?>
	<p style="margin-top:0;"><strong>AI role in this cloned Script tab:</strong> view the generated Lua beside the normal Quest-AI script editor workflow. Step overrides from the Details companion are reflected after “Update Lua Preview”.</p>
	<?php
	QuestAIRenderSelectedQuestSummary($state, true);
	QuestAIRenderLuaPreview($state, 'Generated Lua Preview');
	QuestAIRenderSubPanelEnd();
}

function ShowSOEQuestFixAssistant($soeQuestId = 0)
{
	QuestAIRenderSharedScripts();
	$state = QuestAIGetAssistantState(true);
	?>
	<fieldset>
		<legend>Quest-AI Assistant - BUILD6 FIX26: full diagnostics view for the isolated cloned Quest-AI workspace</legend>
		<?php QuestAIRenderQuestPicker($state, false); ?>
		<?php QuestAIRenderSelectedQuestSummary($state, true); ?>
		<?php QuestAIRenderStepsForm($state, 'Auto Parsed Quest Steps'); ?>
		<?php QuestAIRenderLuaPreview($state, 'Generated Lua Preview'); ?>
	</fieldset>
	<br />
	<?php
}
