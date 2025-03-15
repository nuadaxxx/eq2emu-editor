<?php 
define('IN_EDITOR', true);
include("header.php"); 

// Instantiate the eq2Spawn class, which also instantiates the eq2Zones class as $spawns->zones
include("../class/eq2.quests.php");

$q = new eq2Quests(); // shorthand

?>
<div id="sub-menu1">
	<a href="quests.php">Quest Script Editor</a> | 	
	<a href="quests.php?cl=history">Quest Scripts Changelog</a> |
	<a href="quests.php?zone=ZONENAMEHERE&id=add&tab=register">Add Quest</a>
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
<form action="quests.php" id="frmSearch" method="post">
<table width="1000" border="0">
	<tr>
		<td class="filter_labels">Filters:</td>
		<td width="300">
			<select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)" style="min-width:300px;" />
			<option value="quests.php">Pick a Zone</option>
			<?= $zoneOptions ?>
			</select>
		</td>
		<?php if ( isset($_GET['zone']) ) { ?>
		<td width="500">
			<select name="questID" onchange="dosub(this.options[this.selectedIndex].value)" style="min-width:300px;" />
				<option value="quests.php?zone=<?= $_GET['zone'] ?>">Pick a Quest</option>
				<option value="quests.php?zone=<?= $_GET['zone'] ?>&id=add&tab=register"<?php if( $q->quest_id == 'add' ) echo " selected" ?>>Add a Quest</option>
				<?= $questOptions ?>
			</select> <a href="quests.php?<?= $_SERVER['QUERY_STRING'] ?>">Reload Page</a>
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
				<input type="button" value="Clear" class="submit" onclick="dosub('quests.php');" />
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

	$querystring = sprintf("quests.php?");

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
		'quest_script'					=> 'Script'
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
			$querystring = sprintf("quests.php?zone=%s", $row['zone']);
			
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
			$strHTML .= $strOffset . "<tr>\n";
			$strHTML .= $strOffset . "  <td>\n";
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
				$strHTML .= $strOffset . "                  <input type='hidden' name='redir' value='quests.php?zone=" . $_GET['zone'] . "' />\n";
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
			$strHTML .= $strOffset . "                  <input type='hidden' name='redir' value='quests.php?zone=" . $_GET['zone'] . "&id=" . $next_quest_id . "&tab=register' />\n";
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