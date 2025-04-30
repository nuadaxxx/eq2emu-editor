<?php
//Used to avoid a missing script id when switching from one spawn to another while on the script page
if ( ($_GET['tab'] ?? "") == "edit" && !isset($_GET['sid'])) {
	$tabRedir = "spawns.php?tab=spawn_scripts";
	foreach ($_GET as $k=>$v) {
		if ($k == 'tab') continue;
		$tabRedir .= '&'.$k.'='.$v;
	}
	header("Location: ".$tabRedir);
	exit;
}
if( ($_POST['cmdSearch'] ?? "") == 'Search' )
{
	header("Location: spawns.php?search=".$_POST['txtSearch']);
	exit; // end page here, since actions requires none of the code below
}
define('IN_EDITOR', true);
include("header.php"); 

if ( !$eq2->CheckAccess(M_SPAWNS) )
	die("Access denied!");

// Instantiate the eq2Spawn class, which also instantiates the eq2Zones class as $spawns->zones
include("../class/eq2.spawns.php");
$spawns = new eq2Spawns();

?>
<div id="sub-menu1">
	<a href="spawns.php">Spawn Editor</a> | <a href="spawns.php?cl=history">Spawns Changelog</a>
	<?php
	if ($eq2->CheckAccess(G_DEVELOPER)) 
		printf(' | <a href="%s">Create New Spawn</a>', $spawns->GenerateCreateNewSpawnLink());
	?>
</div>
<?php
if( isset($_GET['cl']) ) 
{
	$eq2->DisplayChangeLogPicker($spawns->eq2SpawnTables);
	include("footer.php");
	exit;
}

if ($eq2->CheckAccess(G_DEVELOPER) && isset($_POST['DeleteSpawn'])) 
{
	if($_GET['adv'] == 1)
	{
		$eq2->ProcessBulkDeletes();
	}else{
		$eq2->ProcessDeletes();
	}
}

// do updates/deletes here
switch(strtolower($_POST['cmd'] ?? "")) 
{
	case "insertspawnloc" 		: $spawns->CreateNewSpawnLocation(); break;
	case "insert"				: $eq2->ProcessInserts(); break;
	case "delete"				: $eq2->ProcessDeletes(); break;
	case "bulkDel"				: $eq2->ProcessBulkDeletes(); break;
	case "update"				:
		if ($_GET['tab'] == "spawn_npcs") {
			$new_random = 0;
			foreach($_POST as $k=>$v) {
				$vals = explode('|', $k);
				if (count($vals) < 3) continue;

				if ($vals[1] == "randomize") {
					$new_random |= intval($vals[2]);
					$_POST[$k] = NULL;
				}
			}
			$_POST['spawn_npcs|randomize'] = $new_random;
		}
		else if (($_GET['tab'] ?? "spawn") == "spawn") {
			foreach ($spawns->eq2SpawnToggles as $toggle) {
				$key = "spawn|" . $toggle;
				$_POST[$key] = isset($_POST[$key]) ? 1 : 0;
			}

			$new_holiday_flag = 0;
			$new_expan_flag = 0;
			foreach($_POST as $k=>$v) {
				$vals = explode('|', $k);
				if (count($vals) < 3) continue;

				if ($vals[1] == "holiday_flag") {
					$new_holiday_flag |= intval($vals[2]);
					$_POST[$k] = NULL;
				}
				else if ($vals[1] == "expansion_flag") {
					$new_expan_flag |= intval($vals[2]);
					$_POST[$k] = NULL;
				}
			}
			$_POST['spawn|holiday_flag'] = $new_holiday_flag;
			$_POST['spawn|expansion_flag'] = $new_expan_flag;
		}
		else if ($_GET['tab'] == "spawnlocation" && $_POST['table_name'] == "spawn_location_entry") {
			$spawns->FixSpawnLocationEntryPostData();
		}
		else if ($_GET['tab'] == "spawn_ground") {
			$_POST['spawn_ground|randomize_heading'] = isset($_POST['spawn_ground|randomize_heading']) ? 1 : 0;
		}
		$eq2->ProcessUpdates(); 
		break;
	
	case "save"					: $eq2->SaveLUAScript(); break;
	case "rebuild"			: $eq2->RebuildLUAScript(); break;
	case "create"				: 
		if( $_POST['table_name'] == "spawn_scripts" )
			$eq2->SaveLUAScript();
		else if( $_POST['table_name'] == "spawn_loot" ) {
			if (!$eq2->CheckAccess(G_DEVELOPER)) die('denied');
			$spawns->CreateNewLootTable(); 
		}
		else if (isset($_GET['new']) ) {
			if (!$eq2->CheckAccess(G_DEVELOPER)) die('lolno');
			$spawns->CreateNewSpawn();
		}
		break;
	case "sml (5px)"		: $spawns->SetXZOffsets(5); break;
	case "med (10px)"		: $spawns->SetXZOffsets(10); break;
	case "lrg (20px)"		: $spawns->SetXZOffsets(20); break;
	case "none (0px)"		: $spawns->SetXZOffsets(0); break;
}

?>
<table width="1000" border="0">
	<?php if( !empty($eq2->Status) ) : ?>
		<tr align="center">
			<td id="EditorStatus" colspan="4"><?php $eq2->DisplayStatus(); ?></td>
		</tr>
	<?php endif; ?>
	<tr>
		<td class="filter_labels">Filters:</td>
		<td valign="top">
		<?php
		$populated = 1; // cheating!
		if( $populated )
			$eq2->SQLQuery = sprintf("SELECT id, name, description FROM `".ACTIVE_DB."`.zones WHERE id IN (SELECT DISTINCT zone_id FROM `".ACTIVE_DB."`.spawn_location_placement) ORDER BY description");
		else
			$eq2->SQLQuery = sprintf("SELECT id,name,description FROM `".ACTIVE_DB."`.zones ORDER BY description");
		
		$zoneOptions = "";
		foreach($eq2->RunQueryMulti() as $data) 
			$zoneOptions .= sprintf('<option value="spawns.php?zone=%s"%s>%s (%s, %s)</option>', 
															 $data['id'], 
															 ( $spawns->zone_id == $data['id'] ) ? " selected" : "", 
															 $data['description'], 
															 $data['name'], 
															 $data['id']);
		?>
		<select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)" class="combo">
		<option value="spawns.php">Pick a Zone</option>
		<?= $zoneOptions ?>
		</select>
		<?php 

		if( $spawns->zone_id > 0 ) 
		{
			$typeOptions = "";
			foreach($spawns->eq2SpawnTypes as $type)
			{
				$typeOptions .= sprintf('<option value="spawns.php?zone=%s&type=%s"%s>%s</option>', 
																$spawns->zone_id,
																strtolower($type),
																( strtolower($spawns->spawn_type) == strtolower($type) ) ? " selected" : "",
																$type);
			}
			?>
			<select name="spawnType" onchange="dosub(this.options[this.selectedIndex].value)" class="combo">
			<option value="spawns.php?zone=<?= $spawns->zone_id ?>">Pick a Type</option>
			<?= $typeOptions ?>
			</select>
			<?php
		}
	
		if( strlen($spawns->spawn_type) > 0 ) 
		{
			$result = $spawns->GetSpawnsByZone();
			
			if( is_array($result) )
			{
				$spawnOptions = "";
				foreach($result as $data)
				{
					$spawnOptions .= sprintf('<option value="spawns.php?zone=%s&type=%s&id=%s%s"%s>%s (%d)</option>', 
																			 $spawns->zone_id,
																			 $spawns->spawn_type,
																			 $data['id'],
																			 isset($_GET['tab']) ? "&tab=".$_GET['tab'] : "",
																			 ( $spawns->spawn_id == $data['id'] ) ? " selected" : "",
																			 $data['name'],
																			 //preg_replace("/<(.*)>/"," - &lt;$1&gt;", $data['sub_title']),
																			 $data['id']);
				}
			}
			?> 
			<select name="spawnID" onchange="dosub(this.options[this.selectedIndex].value)" class="combo">
			<option value="spawns.php?zone=<?= $spawns->zone_id ?>&type=<?= $spawns->spawn_type ?>">Pick a Spawn</option>
			<option value="spawns.php?zone=<?= $spawns->zone_id ?>&type=<?= $spawns->spawn_type ?>&new"<?php if (isset($_GET['new'])) echo " selected";?>>Create New</option>
			<?php echo $spawnOptions; ?>
			</select> <a href="spawns.php?<?= $_SERVER['QUERY_STRING'] ?>">Reload Page</a>
			<?php
		}

		?>
		</td>
	</tr>
	<script>
	function SpawnLookupAJAX() {
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			let str = escape(document.getElementById('txtSearch').value);
			if (str.length == 0) {
				let ss = document.getElementById('search_suggest')
				ss.innerHTML = '';
				return;
			}
			searchReq.open("GET", '../ajax/eq2Ajax.php?type=luSpawn&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest; 
			searchReq.send(null);
		}		
	}
	</script>
	
	<tr>
		<td class="filter_labels">Lookup:</td>
		<td>
			<form action="spawns.php" id="frmSearch" method="post">
				<input type="text" id="txtSearch" name="txtSearch" alt="Search Criteria" onkeyup="SpawnLookupAJAX();" autocomplete="off" class="box" value="<?= $_GET['search'] ?? "" ?>" /><!--onclick="this.value='';"-->
				<input type="submit" id="cmdSearch" name="cmdSearch" value="Search" alt="Run Search" class="submit" />
				<input type="button" value="Clear" class="submit" onclick="dosub('spawns.php');" />
				<div id="search_suggest">
				</div>
			</form>
		</td>
	</tr>
</table>
<?php

if ( isset($_GET['new']) ) {
	$spawns->DisplayAddNewSpawnPage();
	include("footer.php");
	exit;
}

// once the filters are set, show the spawn selector grid
if( ($_GET['search'] ?? "") != "" )
{
	$data = $spawns->GetSpawnsMatching();
	$displayType = (isset($_GET['adv'])?"advanced":"basic");
	DisplaySpawnSelectionGrid($data, $displayType);
	include("footer.php");
	exit; // end page here, since actions requires none of the code below
}
else if( strlen($spawns->spawn_type) > 0 && $spawns->spawn_id == 0 )
{
	$select = "SELECT DISTINCT s1.id, name";
	
	switch($spawns->spawn_type)
	{
		case "npcs": 
			$sql = sprintf("%s, sub_title, race, model_type, min_level, max_level, enc_level, class_, gender", $select);
			break;
			
		case "objects": 
			$sql = sprintf("%s, race, model_type, device_id", $select);
			break;
		
		case "signs": 
			$sql = sprintf("%s, model_type, type, slp.zone_id, title, description", $select);
			break;
		
		case "widgets": 
			$sql = sprintf("%s, model_type, type", $select);
			break;
		
		case "ground": 
			$sql = sprintf("%s, model_type, groundspawn_id, collection_skill", $select);
			break;
		
	}
	
	$eq2->SQLQuery = sprintf("%s FROM `".ACTIVE_DB."`.spawn s1 " . 
													 "JOIN `".ACTIVE_DB."`.spawn_%s s2 ON s1.id = s2.spawn_id " . 
													 "LEFT JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON s1.id = sle.spawn_id " . 
													 "LEFT JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON sle.spawn_location_id = slp.spawn_location_id " .
													 "WHERE slp.zone_id = %s or s1.`id` BETWEEN %s0000 and %s9999 ", 
													 $sql, 
													 /*implode(",", $spawns->GridColumnArray),*/ 
													 $spawns->spawn_type, 
													 $spawns->zone_id,
													 $spawns->zone_id,
													 $spawns->zone_id);
	
	$spawn_data = $eq2->RunQueryMulti();
	$displayType = (isset($_GET['adv'])?"advanced":"basic");
	DisplaySpawnSelectionGrid($spawn_data, $displayType);
}
elseif( $spawns->spawn_id > 0 ) // once a spawn is picked, display all it's data for editing
{
	// still not sure how to build this link
	$querystring = $_SERVER['SCRIPT_NAME'];
	if( $spawns->zone_id > 0 )
		$querystring .= sprintf("?zone=%s", $spawns->zone_id);
	if( strlen($spawns->spawn_type) > 0 )
		$querystring .= sprintf("&type=%s", $spawns->spawn_type);
	if( $spawns->spawn_id > 0 )
		$querystring .= sprintf("&id=%s", $spawns->spawn_id);
		
	$merchant_id = $spawns->is_merchant($spawns->spawn_id);
	
	?>
	<table id="sub-menu1">
		<tr>
			<td>&nbsp;</td>
		</tr>
	</table>
	<?php

	$current_tab_idx = ( isset($_GET['tab']) ) ? $_GET['tab'] : 'spawn';
	
	// always start with Spawn tab
	$tab_array1 = array(
		'spawn'								=> 'Spawn'
	);
	
	// add spawn specific tabs
	if( $spawns->spawn_type == "npcs" )
		$tab_array2 = array('spawn_npcs' => "NPC",'appearance' => "Appearance",'spawnlocation' => "Location",'spawngroup' => "Group",'spawn_loot' => "Loot");
	elseif( $spawns->spawn_type == "objects" )
		$tab_array2 = array('spawn_objects' => "Objects",'spawnlocation' => "Location");
	elseif( $spawns->spawn_type == "signs" )
		$tab_array2 = array('spawn_signs' => "Signs",'spawnlocation' => "Location");
	elseif( $spawns->spawn_type == "widgets" )
		$tab_array2 = array('spawn_widgets' => "Widgets",'spawnlocation' => "Location");
	elseif( $spawns->spawn_type == "ground" )
		$tab_array2 = array('spawn_ground' => "Ground",'spawnlocation' => "Location",'spawngroup' => "Group");
	
	// add all remaining common tabs
	if( $merchant_id )
		$tab_array3 = array('spawn_scripts' => "Script",'merchant' => "Merchant");
	else if ( ($_GET['tab'] ?? "") == 'merchant') {
		?>
		<script>
		location.search = location.search.replace('tab=merchant', 'tab=spawn')
		</script>
		<?php
		exit;
	}
	else
		$tab_array3 = array('spawn_scripts' => "Script");
	
	// advanced menu, if you choose to add spawn_npc_* table maintenance to spawns?
	// $tab_array3 = array('spawn_scripts' => "Script",'advanced' => "Advanced");

	// now merge all the tabs together
	$tab_array = array_merge($tab_array1, $tab_array2, $tab_array3);
	
	// and build the tab menu
	$eq2->TabGenerator($current_tab_idx, $tab_array, $querystring, false);
	
	switch($_GET['tab'] ?? "") 
	{
		case "spawn"            : spawn(); break;
		case "spawn_npcs"       : spawn_npcs(); break;
		case "spawn_objects"	: spawn_objects(); break;
		case "appearance"       : appearance(); break;
		case "spawnlocation"    : spawnlocation(); break;
		case "spawngroup"       : spawngroup(); break;
		case "spawn_loot"       : spawn_loot(); break;
		case "advanced"         : advanced(); break;
		
		case "spawn_ground"     : spawn_ground(); break;
		case "spawn_signs"      : spawn_signs(); break;
		case "spawn_widgets"    : spawn_widgets(); break;
		case "spawn_scripts"    : spawn_scripts(); break;
		case "merchant"         : edit_merchant_list($merchant_id); break;
		case "edit"             : script_editor(); break;
		default                 : spawn(); break;
	}	
}

include("footer.php"); // debugging
exit;


/*
 * Functions
 */

function DisplaySpawnSelectionGrid($spawn_data,$dispType='basic')
{
	global $eq2, $spawns;
	if( is_array($spawn_data) )
	{
		$strHTML = "";
		$strHTML .= "<table class='ContrastTable'>\n";
		if($dispType == "advanced")
		{
			$strHTML .= "<form method='post' name='DeleteMulti|Spawns'>\n";
			$strHTML .= "<input type='hidden' name='table_name' value='spawn'>\n";
			$strHTML .= "<input type='hidden' name='DeleteSpawn' value='true'>\n";
			$strHTML .= "<tr>\n";
			$strHTML .= "  <td colspan='4'>\n";
			$strHTML .= "    <input type='submit' name='cmd' value='bulkDel'> (Advanced form allows for bulk delete)\n";
			$strHTML .= "  </td>\n";
			$strHTML .= "  <td colspan='5' align='right'><a href='spawns.php?zone=" . $_GET['zone'] . "&type=" . $_GET['type'] . "'>Show Basic Form</a></td>\n";
			$strHTML .= "</tr>\n";
		}else{
			$strHTML .= "<tr>\n";
			$strHTML .= "  <td colspan='9' align='right'><a href='spawns.php?zone=" . $_GET['zone'] . "&type=" . $_GET['type'] . "&adv=1'>Show Advanced Form</a></td>\n";
			$strHTML .= "</tr>\n";

		}
		$strHTML .= "  <tr>\n";
		$strHTML .= "    <th width='96'><strong>Controls</strong></th>\n";
		$strHTML .= "    <th width='80'><strong>Spawn ID</strong></th>\n";
		$strHTML .= "    <th width='230'><strong>Name</strong></th>\n";

		switch($spawns->spawn_type)
		{
			case "npcs":
				$strHTML .= "    <th width='350'><strong>SubTitle</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>RaceID</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Model</strong></th>\n";
				$strHTML .= "    <th width='100'><strong>Min/Max/Enc</strong></th>\n";
				$strHTML .= "    <th width='120'><strong>Class</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Gender</strong></th>\n";
				break;

			case "objects":
				$strHTML .= "    <th width='70'><strong>RaceID</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Model</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>DeviceID</strong></th>\n";
				break;

			case "signs":
				$strHTML .= "    <th width='70'><strong>Type</strong></th>\n";
				$strHTML .= "    <th width='250'><strong>Title</strong></th>\n";
				$strHTML .= "    <th width='350'><strong>Description</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Model</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>ZoneID</strong></th>\n";
				break;

			case "widgets":
				$strHTML .= "    <th width='70'><strong>Model</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Type</strong></th>\n";
				break;

			case "ground":
				$strHTML .= "    <th width='70'><strong>GroundspawnID</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Skill</strong></th>\n";
				break;

			default:
				$strHTML .= "    <th width='350'><strong>SubTitle</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Model</strong></th>\n";
				$strHTML .= "    <th width='100'><strong>Min/Max/Enc</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Sign Type</strong></th>\n";
				$strHTML .= "    <th width='150'><strong>Sign Title</strong></th>\n";
				$strHTML .= "    <th width='50'><strong>Sign Zone</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>GroundspawnID</strong></th>\n";
				$strHTML .= "    <th width='70'><strong>Skill</strong></th>\n";
				break;
		}

		$strHTML .= "    </tr>\n";

		$i = 0;

		foreach($spawn_data as $row)
		{
			$zone_code = ($spawns->zone_id > 0 ? $spawns->zone_id : $row['loc_zone']);
			$type_code = (strlen($spawns->spawn_type) > 0 ? $spawns->spawn_type : $row['spawn_type']);
			$id_code = ($spawns->spawn_id > 0 ? $spawns->spawn_id : $row['id']);
			$querystring = "spawns.php?zone=" . $zone_code . "&type=" . $type_code . "&id=" . $id_code;

			$strHTML .= "<tr align='center'>\n";
			$strHTML .= "  <td>\n";
			if($dispType != "advanced")
			{
				$strHTML .= "    <form method='post' name='DeleteRow|" . $i . "'>\n";
			}
			$strHTML .= "      <table>\n";
			$strHTML .= "        <tr>\n";
			$strHTML .= "          <td><a href='http://eq2.wikia.com/wiki/" . $row['name'] . "' target='_blank'><img src='../images/wikia.png' border='0' align='top' title='Wikia' alt='Wikia' height='16' /></a></td>\n";
			$strHTML .= "          <td><a href='http://eq2.zam.com/search.html?q=" . $row['name'] . "' target='_blank'><img src='../images/zam.png' border='0' align='top' title='Zam' alt='Zam' height='16' /></a></td>\n";
			if( $eq2->CheckAccess(G_DEVELOPER) )
			{
				if($dispType != "advanced")
				{
					$strHTML .= "          <td>\n";
					$strHTML .= "            <input id='Delete|" . $i . "' value='Delete' name='cmd' type='image' class='SpawnDelete' src='../images/cross.png' border='0' align='top' title='Delete' alt='Delete' height='16' onclick='return confirm('Are you sure you want to delete this?');'/></input>\n";
					$strHTML .= "            <input type='hidden' name='orig_id' value='" . $row['id'] . "' />\n";
					$strHTML .= "            <input type='hidden' name='table_name' value='spawn' />\n";
					$strHTML .= "          </td>\n";
					$strHTML .= "          <td>" . $eq2->ReturnBlueCheckbox("DeleteSpawn", false, "DeleteConfirm|" . $i) . "</td>\n";
				}else{
					$strHTML .= "          <td>" . $eq2->ReturnBlueCheckbox("DeleteSpawns|" . $row['id'], false, "DeleteConfirm|" . $i) . "</td>\n";
				}
			}
			$strHTML .= "        </tr>\n";
			$strHTML .= "      </table>\n";
			if($dispType != "advanced")
			{
				$strHTML .= "    </form>\n";
			}
			$strHTML .= "    <script>ElementToggleCheckbox('DeleteConfirm|" . $i . "','Delete|" . $i ."')</script>\n";
			$strHTML .= "  </td>\n";
			$strHTML .= "  <td><a href='" . $querystring . "'>" . $row['id'] . "</a></td>\n";
			$strHTML .= "  <td nowrap='nowrap'>" .  $row['name'] . "</td>\n";

			switch($spawns->spawn_type)
			{
				case "npcs":
					$strHTML .= "  <td nowrap='nowrap'>" . preg_replace("/<(.*)>/","&lt;$1&gt;", $row['sub_title']) . "</td>\n";
					$strHTML .= "  <td>" . $row['race'] . "</td>\n";
					$strHTML .= "  <td>" . $row['model_type'] . "</td>\n";
					$strHTML .= "  <td>" . $row['min_level'] . "/" . $row['max_level'] . "/" . $row['enc_level'] . "</td>\n";
					$strHTML .= "  <td nowrap='nowrap'>" . $eq2->eq2Classes[$row['class_']] . "</td>\n";
					$strHTML .= "  <td>" . $eq2->eq2Genders[$row['gender']] . "</td>\n";
					break;

				case "objects":
					$strHTML .= "  <td>" . ($row['race'] ? $row['race'] : "") . "</td>\n";
					$strHTML .= "  <td>" . $row['model_type'] . "</td>\n";
					$strHTML .= "  <td>" . ($row['device_id'] ? $row['device_id'] : "") . "</td>\n";
					break;

				case "signs":
					$strHTML .= "  <td>" . $row['type'] . "</td>\n";
					$strHTML .= "  <td nowrap='nowrap'>" . $row['title'] . "</td>\n";
					$strHTML .= "  <td>" . $row['description'] . "</td>\n";
					$strHTML .= "  <td>" . $row['model_type'] . "</td>\n";
					$strHTML .= "  <td>" . $row['zone_id'] . "</td>\n";
					break;

				case "widgets":
					$strHTML .= "  <td>" . $row['model_type'] . "</td>\n";
					$strHTML .= "  <td>" . $row['type'] . "</td>\n";
					break;

				case "ground":
					$strHTML .= "  <td>" . $row['groundspawn_id'] . "</td>\n";
					$strHTML .= "  <td>" . $row['collection_skill'] . "</td>\n";
					break;
				
				default:
					$strHTML .= "  <td nowrap='nowrap'>" . preg_replace("/<(.*)>/","&lt;$1&gt;", $row['sub_title']) . "</td>\n";
					$strHTML .= "  <td>" . $row['model_type']. "</td>\n";
					$strHTML .= "  <td>" . ($row['min_level']?$row['min_level'] . "/" . $row['max_level'] . "/" .  $row['enc_level']:"") . "</td>\n";
					$strHTML .= "  <td>" . $row['type'] . "</td>\n";
					$strHTML .= "  <td nowrap='nowrap'>" . $row['title'] . "</td>\n";
					$strHTML .= "  <td>" . ($row['zone_id'] ? $row['zone_id'] : "") . "</td>\n";
					$strHTML .= "  <td>" . ($row['groundspawn_id'] ? $row['groundspawn_id'] : "") . "</td>\n";
					$strHTML .= "  <td>" . $row['collection_skill'] . "</td>\n";
					break;
			}
			$strHTML .= "  </tr>\n";
			$i++;
		}
		$strHTML .= "  <tr bgcolor='#CCCCCC'>\n";
		$strHTML .= "    <td colspan='10'>" . $i . " rows returned...</td>\n";
		$strHTML .= "  </tr>\n";
		$strHTML .= "</table>\n";
		if($dispType == "advanced")
		{
			$strHTML .= "    <form>\n";
		}

	}else{
		$strHTML .= "&nbsp;No data found for set filters. Try looking up the spawn by name.\n";
	}
	print($strHTML);
}


function spawn() 
{
	global $eq2, $spawns;

	$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spawn WHERE id = %s", $spawns->spawn_id);
	$data = $eq2->RunQuerySingle();
	?>
	<br />
	<table border="0" cellpadding="5">
	<form method="post" name="SpawnForm" />
		<tr>
			<td>
				<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span><br />
			</td>
		</tr>
		<tr>
			<td valign="top">
				<table>
					<tr>
						<td>
							<?php $spawns->PrintSpawnGeneralFields($data) ?>
						<td>
					</tr>
					<tr>
						<td>
							<?php $spawns->PrintSpawnCommands($data) ?>
						</td>
					</tr>
				</table>
			</td>
			<td valign="top">
				<table>
					<tr>
						<td>
							<?php $spawns->PrintSpawnToggles($data); ?>
						</td>
						<td>
							<?php $spawns->PrintHolidayFlags($data); ?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<?php $spawns->PrintExpansionFlags($data) ?>
						</td>
					</tr>
				</table>		
			</td>
		</tr>
		<?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
		<tr>
			<td colspan="2" align="center">
				<input type="submit" name="cmd" value="Update" class="submit" />&nbsp;
				<input type="button" value="Clone" class="submit" onclick="window.open('spawn_func.php?<?= $_SERVER['QUERY_STRING'] ?>&func=clone','clone','width=800,height=600,left=10,top=75,scrollbars=yes');" />&nbsp;
				<input type="button" value="Export" class="submit" disabled onclick="window.open('export.php?<?= $_SERVER['QUERY_STRING'] ?>&func=export','export','width=800,height=600,left=10,top=75,scrollbars=yes');" />&nbsp;
				<input type="submit" name="cmd" value="Delete" class="submit" />&nbsp;
				<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
				<input type="hidden" name="table_name" value="spawn" />
			</td>
		</tr>
		<?php } ?>
		<tr>
			<td colspan="2">
				<p>
				<strong>Note:</strong> Due to the number of race options, I have chosen to not to list them in a combo box at this time because it would slow the page performance tremendously.<br />
				Please keep a race and model_type reference handy.
				</p>
			</td>
		</tr>
	</form>
	</table>
	<?php	
}

function spawn_npcs() 
{
	global $eq2, $spawns;

	$data = $spawns->GetSpawnNPCData();
	?>
	<br />
	<table border="0" cellpadding="5">
	<form method="post" name="snForm">
		<tr>
			<td colspan="2">
				<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span><br />
			</td>
		</tr>
		<tr>
			<td width="480" valign="top">
				<fieldset style="height:248px; width:600px;"><legend>General</legend> 
				<table border="0">
					<tr>
						<td align="right" width="70">id:</td>
						<td width="100">
							<?= $data['id'] ?>
							<input type="hidden" name="orig_id" value="<?= $data['id'] ?>" />
						</td>
						<td align="right" width="100">spawn_id:</td>
						<td width="70">
							<input type="text" name="spawn_npcs|spawn_id" value="<?= $data['spawn_id'] ?>" readonly style="width:70px; background-color:#ddd;" />
							<input type="hidden" name="orig_spawn_id" value="<?= $data['spawn_id'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td align="right" width="100">class:</td>
						<td>
							<select name="spawn_npcs|class_" class="combo">
								<?php 
								foreach($eq2->eq2Classes as $key=>$val) {
									$selected = ( $key == $data['class_'] ) ? " selected" : "";
									echo "<option value='$key'$selected>$val</option>";
								}
								?>
							</select>
							<input type="hidden" name="orig_class_" value="<?php print($data['class_']); ?>" />
						</td>
						<td align="right" width="100">gender:</td>
						<td>
							<select name="spawn_npcs|gender" class="combo">
								<option value="1"<?php if( $data['gender'] == 1 ) echo " selected" ?>>Male</option>
								<option value="2"<?php if( $data['gender'] == 2 ) echo " selected" ?>>Female</option>
							</select>
							<input type="hidden" name="orig_gender" value="<?= $data['gender'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">min_level:</td>
						<td>
							<input type="text" name="spawn_npcs|min_level" value="<?php print($data['min_level']); ?>" style="width:50px" />
							<input type="hidden" name="orig_min_level" value="<?= $data['min_level'] ?>" />
						</td>
						<td align="right">max_level:</td>
						<td>
							<input type="text" name="spawn_npcs|max_level" value="<?php print($data['max_level']); ?>" style="width:50px" />
							<input type="hidden" name="orig_max_level" value="<?= $data['max_level'] ?>" />
						</td>
						<td align="right">enc_level:</td>
						<td>
							<input type="text" name="spawn_npcs|enc_level" value="<?php print($data['enc_level']); ?>" style="width:50px" />
							<input type="hidden" name="orig_enc_level" value="<?= $data['enc_level'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">heroic_flag:</td>
						<td>
							<select name="spawn_npcs|heroic_flag" class="combo">
								<?php $spawns->PrintHeroicFlagOptions($data); ?>
							</select>
							<input type="hidden" name="orig_heroic_flag" value="<?= $data['heroic_flag'] ?>" />
						</td>
						<td align="right">hide_hood:</td>
						<td>
							<select name="spawn_npcs|hide_hood" class="combo">
								<option value="0"<?php if( $data['hide_hood'] == 0 ) echo " selected" ?>>Off</option>
								<option value="1"<?php if( $data['hide_hood'] == 1 ) echo " selected" ?>>On</option>
							</select>
							<input type="hidden" name="orig_hide_hood" value="<?= $data['hide_hood'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td align="right">min_group_size:</td>
						<td>
							<input type="text" name="spawn_npcs|min_group_size" value="<?php print($data['min_group_size']); ?>" style="width:50px" />
							<input type="hidden" name="orig_min_group_size" value="<?= $data['min_group_size'] ?>" />
						</td>
						<td align="right">max_group_size:</td>
						<td>
							<input type="text" name="spawn_npcs|max_group_size" value="<?php print($data['max_group_size']); ?>" style="width:50px" />
							<input type="hidden" name="orig_max_group_size" value="<?= $data['max_group_size'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td align="right" width="100">action_state:</td>
						<td>
							<input type="text" name="spawn_npcs|action_state" value="<?php print($data['action_state']); ?>" style="width:50px" />
							<input type="hidden" name="orig_action_state" value="<?= $data['action_state'] ?>" />
						</td>
						<td align="right" width="100">mood_state:</td>
						<td>
							<input type="text" name="spawn_npcs|mood_state" value="<?php print($data['mood_state']); ?>" style="width:50px" />
							<input type="hidden" name="orig_mood_state" value="<?= $data['mood_state'] ?>" />
						</td>
						<td align="right">emote_state:</td>
						<td>
							<input type="text" name="spawn_npcs|emote_state" value="<?php print($data['emote_state']); ?>" style="width:50px" />
							<input type="hidden" name="orig_emote_state" value="<?= $data['emote_state'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">initial_state:</td>
						<td>
							<input type="text" name="spawn_npcs|initial_state" value="<?php print($data['initial_state']); ?>" style="width:50px" />
							<input type="hidden" name="orig_initial_state" value="<?= $data['initial_state'] ?>" />
						</td>
						<td align="right">activity_status:</td>
						<td>
							<input type="text" name="spawn_npcs|activity_status" value="<?php print($data['activity_status']); ?>" style="width:50px" />
							<input type="hidden" name="orig_activity_status" value="<?= $data['activity_status'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td align="right">alignment:</td>
						<td>
							<select name="spawn_npcs|alignment" class="combo">
								<option value="0"<?php if( $data['alignment'] == 0 ) echo " selected" ?>>Neutral</option>
								<option value="1"<?php if( $data['alignment'] == 1 ) echo " selected" ?>>Good</option>
								<option value="2"<?php if( $data['alignment'] == 2 ) echo " selected" ?>>Evil</option>
							</select>
							<input type="hidden" name="orig_alignment" value="<?= $data['alignment'] ?>" />
						</td>
						<td align="right">water_type:</td>
						<td>
							<select name="spawn_npcs|water_type" class="combo">
								<option value="0"<?php if( $data['water_type'] ) print(" selected") ?>>No</option>
								<option value="1"<?php if( $data['water_type'] ) print(" selected") ?>>Yes</option>
							</select>
							<input type="hidden" name="orig_water_type" value="<?= $data['water_type'] ?>" />
						</td>
						<td align="right">flying_type:</td>
						<td>
							<select name="spawn_npcs|flying_type" class="combo">
								<option value="0"<?php if( $data['flying_type'] ) print(" selected") ?>>No</option>
								<option value="1"<?php if( $data['flying_type'] ) print(" selected") ?>>Yes</option>
							</select>
							<input type="hidden" name="orig_flying_type" value="<?= $data['flying_type'] ?>" />
						</td>

					</tr>
				</table>
				</fieldset>
			</td>
			<td valign="top">
				<fieldset style="width:220px;"><legend>Appearances</legend> 
				<table>
					<tr>
						<td width="150">hair_type_id:</td>
						<td width="30">
							<input type="text" name="spawn_npcs|hair_type_id" value="<?php print($data['hair_type_id']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_hair_type_id" value="<?= $data['hair_type_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td width="100">facial_hair_type_id:</td>
						<td width="20">
							<input type="text" name="spawn_npcs|facial_hair_type_id" value="<?php print($data['facial_hair_type_id']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_facial_hair_type_id" value="<?= $data['facial_hair_type_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td width="100">chest_type_id:</td>
						<td width="20">

							<input type="text" name="spawn_npcs|chest_type_id" value="<?php print($data['chest_type_id']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_chest_type_id" value="<?= $data['chest_type_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td width="100">wing_type_id:</td>
						<td width="20">
							<input type="text" name="spawn_npcs|wing_type_id" value="<?php print($data['wing_type_id']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_wing_type_id" value="<?= $data['wing_type_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td width="100">legs_type_id:</td>
						<td width="20">
							<input type="text" name="spawn_npcs|legs_type_id" value="<?php print($data['legs_type_id']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_legs_type_id" value="<?= $data['legs_type_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td width="100">soga_hair_type_id:</td>
						<td width="20">
							<input type="text" name="spawn_npcs|soga_hair_type_id" value="<?php print($data['soga_hair_type_id']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_soga_hair_type_id" value="<?= $data['soga_hair_type_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td width="100">soga_facial_hair_type_id:</td>
						<td width="20">
							<input type="text" name="spawn_npcs|soga_facial_hair_type_id" value="<?php print($data['soga_facial_hair_type_id']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_soga_facial_hair_type_id" value="<?= $data['soga_facial_hair_type_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td width="100">soga_model_type:</td>
						<td width="20">
							<input type="text" name="spawn_npcs|soga_model_type" value="<?php print($data['soga_model_type']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_soga_model_type" value="<?= $data['soga_model_type'] ?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2" align="center">
							<input type="button" value="Lookup Model" class="submit" onclick="javascript:window.open('spawn_func.php?func=model','lookup','width=1024,height=768,left=10,top=75,scrollbars=yes');" style="width:100px;" />
						</td>
					</tr>
				</table>
				</fieldset>
			</td>
		</tr>
    <tr>
			<td width="480" height="130" valign="top">
				<fieldset style="height:100px; width:600px;"><legend>Stats</legend> 
				<table>
					<tr>
						<td width="60" align="right">str:</td>
						<td width="30">
							<input type="text" name="spawn_npcs|str" value="<?php print($data['str']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_str" value="<?= $data['str'] ?>" />
						</td>
						<td width="60" align="right">intel:</td>
						<td width="30">
							<input type="text" name="spawn_npcs|intel" value="<?php print($data['intel']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_intel" value="<?= $data['intel'] ?>" />
						</td>
						<td width="60" align="right">heat:</td>
						<td width="30">
							<input type="text" name="spawn_npcs|heat" value="<?php print($data['heat']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_heat" value="<?= $data['heat'] ?>" />
						</td>
						<td width="60" align="right">mental:</td>
						<td width="30">
							<input type="text" name="spawn_npcs|mental" value="<?php print($data['mental']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_mental" value="<?= $data['mental'] ?>" />
						</td>
						<td width="60" align="right">poison:</td>
						<td width="30">
							<input type="text" name="spawn_npcs|poison" value="<?php print($data['poison']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_poison" value="<?= $data['poison'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">sta:</td>
						<td>
							<input type="text" name="spawn_npcs|sta" value="<?php print($data['sta']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_sta" value="<?= $data['sta'] ?>" />
						</td>
						<td align="right">agi:</td>
						<td>
							<input type="text" name="spawn_npcs|agi" value="<?php print($data['agi']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_agi" value="<?= $data['agi'] ?>" />
						</td>
						<td align="right">cold:</td>
						<td>
							<input type="text" name="spawn_npcs|cold" value="<?php print($data['cold']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_cold" value="<?= $data['cold'] ?>" />
						</td>
						<td width="60" align="right">divine:</td>
						<td width="30">
							<input type="text" name="spawn_npcs|divine" value="<?php print($data['divine']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_divine" value="<?= $data['divine'] ?>" />
						</td>
            <td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td align="right">wis:</td>
						<td>
							<input type="text" name="spawn_npcs|wis" value="<?php print($data['wis']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_wis" value="<?= $data['wis'] ?>" />
						</td>
            <td colspan="2">&nbsp;</td>
						<td align="right">magic:</td>
						<td>
							<input type="text" name="spawn_npcs|magic" value="<?php print($data['magic']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_magic" value="<?= $data['magic'] ?>" />
						</td>
						<td align="right">disease:</td>
						<td>
							<input type="text" name="spawn_npcs|disease" value="<?php print($data['disease']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_disease" value="<?= $data['disease'] ?>" />
						</td>
            <td colspan="2">&nbsp;</td>
					</tr>
				</table>
				</fieldset>
			</td>
			<td rowspan="2" valign="top">
				<fieldset><legend>Randomize</legend>
				<table>
				<?php	
        $i = 0;
        foreach($eq2->eq2Randomize as $value=>$randomize)
        {
          if (($i % 2) == 0) echo "<tr>";
			printf('<td width="40%%" align="right" nowrap="nowrap">%s:</td>', $randomize);
			echo '<td width="10%" align="left">';
			$eq2->GenerateBlueCheckbox(sprintf('spawn_npcs|randomize|%s', $value), $value & $data['randomize']);
			echo '</td>';
          if ($i % 2) echo '</tr>';
          $i++;
        }
        ?>
        </table>
        </fieldset>
      </td>
    </tr>
		<tr>
			<td width="480" valign="top">
				<fieldset style="width:600px;"><legend>NPC_AI</legend> 
				<table border="0">
					<tr>
						<td align="right">auto_attack_dmg:</td>
						<td>
							<?php $spawns->PrintNPCAttackTypes($data['attack_type']) ?>
							<input type="hidden" name="orig_attack_type" value="<?= $data['attack_type'] ?>" />
						</td>
						<td align="right" width="100">ai_strategy:</td>
						<td>
							<select name="spawn_npcs|ai_strategy" class="combo">
								<option<?php if( $data['ai_strategy']=="BALANCED" ) echo " selected" ?>>BALANCED</option>
								<option<?php if( $data['ai_strategy']=="OFFENSIVE" ) echo " selected" ?>>OFFENSIVE</option>
								<option<?php if( $data['ai_strategy']=="DEFENSIVE" ) echo " selected" ?>>DEFENSIVE</option>
							</select>
							<input type="hidden" name="orig_ai_strategy" value="<?php print($data['ai_strategy']); ?>" />
						</td>
						<td align="right">aggro_radius:</td>
						<td>
							<input type="text" name="spawn_npcs|aggro_radius" value="<?php print($data['aggro_radius']); ?>" style="width:50px" />
							<input type="hidden" name="orig_aggro_radius" value="<?= $data['aggro_radius'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">equipment_list_id:</td>
						<td colspan="5">
							<?php $equipOptions = $spawns->GetSpawnEquipmentList($data['equipment_list_id']) ?>
							<select name="spawn_npcs|equipment_list_id" class="combo" style="min-width:200px">
								<option value="0">None</option>
								<?= $equipOptions ?>
							</select>
							<input type="hidden" name="orig_equipment_list_id" value="<?= $data['equipment_list_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">skill_list_id:</td>
						<td colspan="5">
							<?php $skillOptions = $spawns->GetSpawnSkillList($data['skill_list_id']) ?>
							<select name="spawn_npcs|skill_list_id" class="combo" style="min-width:200px">
								<option value="0">None</option>
								<?= $skillOptions ?>
							</select>
							<input type="hidden" name="orig_skill_list_id" value="<?= $data['skill_list_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">secondary_skill_list_id:</td>
						<td colspan="5">
							<?php $skillOptions = $spawns->GetSpawnSkillList($data['secondary_skill_list_id']) ?>
							<select name="spawn_npcs|secondary_skill_list_id" class="combo" style="min-width:200px">
								<option value="0">None</option>
								<?= $skillOptions ?>
							</select>
							<input type="hidden" name="orig_secondary_skill_list_id" value="<?= $data['secondary_skill_list_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">spell_list_id:</td>
						<td colspan="5">
							<?php $spellOptions = $spawns->GetSpawnSpellList($data['spell_list_id']) ?>
							<select name="spawn_npcs|spell_list_id" class="combo" style="min-width:200px">
								<option value="0">None</option>
								<?= $spellOptions ?>
							</select>
							<input type="hidden" name="orig_spell_list_id" value="<?= $data['spell_list_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">secondary_spell_list_id:</td>
						<td colspan="5">
							<?php $spellOptions = $spawns->GetSpawnSpellList($data['secondary_spell_list_id']) ?>
							<select name="spawn_npcs|secondary_spell_list_id" class="combo" style="min-width:200px">
								<option value="0">None</option>
								<?= $spellOptions ?>
							</select>
							<input type="hidden" name="orig_secondary_spell_list_id" value="<?= $data['secondary_spell_list_id'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">cast_percentage:</td>
						<td>
							<input type="text" name="spawn_npcs|cast_percentage" value="<?php print($data['cast_percentage']); ?>" style="width:50px" />
							<input type="hidden" name="orig_cast_percentage" value="<?= $data['cast_percentage'] ?>" />
						</td>
					</tr>
				</table>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
			<?php if( $eq2->CheckAccess(G_DEVELOPER) ) : ?>
				<input type="submit" name="cmd" value="Update" style="width:100px;" />
				<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#spawn_npcs','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
			<?php endif; ?>
				<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
				<input type="hidden" name="table_name" value="spawn_npcs" />
				<input type="hidden" name="orig_randomize" value="<?= $data['randomize'] ?>" />
			</td>
		</tr>
	</form>
	</table>
	<?php
}

function spawn_objects() 
{
	global $eq2, $spawns;
	
	$data = $spawns->GetSpawnObjectData();
	?>
	<br />
	<table border="0" cellpadding="5">
	<form method="post" name="snForm" />
		<tr>
			<td width="480" valign="top">
				<fieldset style="height:250px; width:600px;"><legend>General</legend> 
				<table border="0">
					<tr>
						<td colspan="6">
							<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span><br />
						</td>
					</tr>
					<tr>
						<td align="right">id:</td>
						<td>
							<input type="text" name="spawn_objects|id" value="<?php print($data['id']) ?>" style="width:70px" />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">spawn_id:</td>
						<td>
							<input type="text" name="spawn_objects|spawn_id" value="<?php print($data['spawn_id']) ?>" style="width:70px" />
							<input type="hidden" name="orig_spawn_id" value="<?php print($data['spawn_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">device_id:</td>
						<td>
							<input type="text" name="spawn_objects|device_id" value="<?php print($data['device_id']) ?>" style="width:70px" />
							<input type="hidden" name="orig_device_id" value="<?php print($data['device_id']) ?>" />
						</td>
					</tr>
				</table>
				</fieldset>
			</td>
		</tr>
		<?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
		<tr>
			<td colspan="2" align="center">
				<input type="submit" name="cmd" value="Update" class="submit" />
				<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
				<input type="hidden" name="table_name" value="spawn_objects" />
			</td>
		</tr>
		<?php } ?>
	</form>
	</table>
	<?php
}

function spawn_signs() 
{
	global $eq2, $spawns;

	$data = $spawns->GetSpawnSignData();
	?>
	<br />
	<table width="1000" border="0" cellpadding="5">
	<form method="post" name="snForm" />
		<tr>
			<td colspan="6">
				<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span><br />
			</td>
		</tr>
		<tr>
			<td valign="top">
				<fieldset><legend>General</legend> 
				<table border="0">
					<tr>
						<td align="right">id:</td>
						<td>
							<input type="text" name="spawn_signs|id" value="<?php print($data['id']) ?>" style="width:50px" />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">spawn_id:</td>
						<td>
							<input type="text" name="spawn_signs|spawn_id" value="<?php print($data['spawn_id']) ?>" style="width:50px" />
							<input type="hidden" name="orig_spawn_id" value="<?php print($data['spawn_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">type:</td>
						<td>
							<select name="spawn_signs|type" class="combo">
								<option<?php if( $data['type']=="Generic" ) echo " selected" ?>>Generic</option>
								<option<?php if( $data['type']=="Zone" ) echo " selected" ?>>Zone</option>
							<input type="hidden" name="orig_type" value="<?php print($data['type']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">zone_id:</td>
						<td>
						<?php $zoneOptions = $spawns->zones->GetZoneOptionsByID($data['zone_id']) ?>
							<select name="spawn_signs|zone_id" class="combo" style="min-width:200px;">
								<option value="0">---</option>
							<?= $zoneOptions ?>
							</select>
							<input type="hidden" name="orig_zone_id" value="<?php print($data['zone_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">widget_id:</td>
						<td>
							<input type="text" name="spawn_signs|widget_id" value="<?php print($data['widget_id']) ?>" style="width:100px" />
							<input type="hidden" name="orig_widget_id" value="<?php print($data['widget_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">title:</td>
						<td>
							<input type="text" name="spawn_signs|title" value="<?php print($data['title']) ?>" style="width:300px" />
							<input type="hidden" name="orig_title" value="<?php print($data['title']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">widget_x:</td>
						<td>
							<input type="text" name="spawn_signs|widget_x" value="<?php print($data['widget_x']) ?>" style="width:50px" />
							<input type="hidden" name="orig_widget_x" value="<?php print($data['widget_x']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">widget_y:</td>
						<td>
							<input type="text" name="spawn_signs|widget_y" value="<?php print($data['widget_y']) ?>" style="width:50px" />
							<input type="hidden" name="orig_widget_y" value="<?php print($data['widget_y']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">widget_z:</td>
						<td>
							<input type="text" name="spawn_signs|widget_z" value="<?php print($data['widget_z']) ?>" style="width:50px" />
							<input type="hidden" name="orig_widget_z" value="<?php print($data['widget_z']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">icon:</td>
						<td>
							<input type="text" name="spawn_signs|icon" value="<?php print($data['icon']) ?>" style="width:50px" />
							<input type="hidden" name="orig_icon" value="<?php print($data['icon']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">description:</td>
						<td>
							<input type="text" name="spawn_signs|description" value="<?php print($data['description']) ?>" style="width:600px" />
							<input type="hidden" name="orig_description" value="<?php print($data['description']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">language:</td>
						<td>
							<?php $spawns->PrintSignLanguageOptions($data) ?>
						</td>
					</tr>
					<tr>
						<td align="right">sign_distance:</td>
						<td>
							<input type="text" name="spawn_signs|sign_distance" value="<?php print($data['sign_distance']) ?>" style="width:50px" />
							<input type="hidden" name="orig_sign_distance" value="<?php print($data['sign_distance']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">zone_x:</td>
						<td>
							<input type="text" name="spawn_signs|zone_x" value="<?php print($data['zone_x']) ?>" style="width:50px" />
							<input type="hidden" name="orig_zone_x" value="<?php print($data['zone_x']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">zone_y:</td>
						<td>
							<input type="text" name="spawn_signs|zone_y" value="<?php print($data['zone_y']) ?>" style="width:50px" />
							<input type="hidden" name="orig_zone_y" value="<?php print($data['zone_y']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">zone_z:</td>
						<td>
							<input type="text" name="spawn_signs|zone_z" value="<?php print($data['zone_z']) ?>" style="width:50px" />
							<input type="hidden" name="orig_zone_z" value="<?php print($data['zone_z']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">zone_heading:</td>
						<td>
							<input type="text" name="spawn_signs|zone_heading" value="<?php print($data['zone_heading']) ?>" style="width:50px" />
							<input type="hidden" name="orig_zone_heading" value="<?php print($data['zone_heading']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">include_heading:</td>
						<td>
							<input type="text" name="spawn_signs|include_heading" value="<?php print($data['include_heading']) ?>" style="width:50px" />
							<input type="hidden" name="orig_include_heading" value="<?php print($data['include_heading']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">include_location:</td>
						<td>
							<input type="text" name="spawn_signs|include_location" value="<?php print($data['include_location']) ?>" style="width:50px" />
							<input type="hidden" name="orig_include_location" value="<?php print($data['include_location']) ?>" />
						</td>
					</tr>
				</table>
				</fieldset>
			</td>
		</tr>
		<?php 
		if( $eq2->CheckAccess(G_DEVELOPER) ) 
		{ 
		?>
		<tr>
			<td colspan="2" align="center">
				<input type="submit" name="cmd" value="Update" class="submit" />
				<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
				<input type="hidden" name="table_name" value="spawn_signs" />
			</td>
		</tr>
		<?php 
		} 
		?>
	</form>
	</table>
	<?
}

function spawn_widgets() 
{
	global $eq2, $spawns;

	$data = $spawns->GetSpawnWidgetData();
	?>
	<br />
	<table width="1000" border="0" cellpadding="5">
	<form method="post" name="snForm" />
		<tr>
			<td>
				<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span><br />
			</td>
		</tr>
		<tr>
			<td valign="top">
				<fieldset><legend>General</legend> 
				<table border="0">
					<tr>
						<td align="right">id:</td>
						<td>
							<input type="text" name="spawn_widgets|id" value="<?php print($data['id']) ?>" style="width:50px" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">spawn_id:</td>
						<td>
							<input type="text" name="spawn_widgets|spawn_id" value="<?php print($data['spawn_id']) ?>" style="width:50px" />
							<input type="hidden" name="orig_spawn_id" value="<?php print($data['spawn_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">widget_id:</td>
						<td>
							<input type="text" name="spawn_widgets|widget_id" value="<?php print($data['widget_id']) ?>" style="width:100px" />
							<input type="hidden" name="orig_widget_id" value="<?php print($data['widget_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">widget_x:</td>
						<td>
							<input type="text" name="spawn_widgets|widget_x" value="<?php print($data['widget_x']) ?>" style="width:50px" />
							<input type="hidden" name="orig_widget_x" value="<?php print($data['widget_x']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">widget_y:</td>
						<td>
							<input type="text" name="spawn_widgets|widget_y" value="<?php print($data['widget_y']) ?>" style="width:50px" />
							<input type="hidden" name="orig_widget_y" value="<?php print($data['widget_y']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">widget_z:</td>
						<td>
							<input type="text" name="spawn_widgets|widget_z" value="<?php print($data['widget_z']) ?>" style="width:50px" />
							<input type="hidden" name="orig_widget_z" value="<?php print($data['widget_z']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">include_heading:</td>
						<td>
							<input type="text" name="spawn_widgets|include_heading" value="<?php print($data['include_heading']) ?>" style="width:50px" />
							<input type="hidden" name="orig_include_heading" value="<?php print($data['include_heading']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">include_location:</td>
						<td>
							<input type="text" name="spawn_widgets|include_location" value="<?php print($data['include_location']) ?>" style="width:50px" />
							<input type="hidden" name="orig_include_location" value="<?php print($data['include_location']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">icon:</td>
						<td>
							<input type="text" name="spawn_widgets|icon" value="<?php print($data['icon']) ?>" style="width:50px" />
							<input type="hidden" name="orig_icon" value="<?php print($data['icon']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">type:</td>
						<td>
							<select name="spawn_widgets|type" class="combo" style="min-width:200px">
								<option<?php if( $data['type']=="Generic" || $data['type']=="" ) echo " selected" ?>>Generic</option>
								<option<?php if( $data['type']=="Door" ) echo " selected" ?>>Door</option>
								<option<?php if( $data['type']=="Lift" ) echo " selected" ?>>Lift</option>									
							</select>
							<input type="hidden" name="orig_type" value="<?php print($data['type']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">open_heading:</td>
						<td>
							<input type="text" name="spawn_widgets|open_heading" value="<?php print($data['open_heading']) ?>" style="width:50px" />
							<input type="hidden" name="orig_open_heading" value="<?php print($data['open_heading']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">closed_heading:</td>
						<td>
							<input type="text" name="spawn_widgets|closed_heading" value="<?php print($data['closed_heading']) ?>" style="width:50px" />
							<input type="hidden" name="orig_closed_heading" value="<?php print($data['closed_heading']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">open_x:</td>
						<td>
							<input type="text" name="spawn_widgets|open_x" value="<?php print($data['open_x']) ?>" style="width:50px" />
							<input type="hidden" name="orig_open_x" value="<?php print($data['open_x']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">open_y:</td>
						<td>
							<input type="text" name="spawn_widgets|open_y" value="<?php print($data['open_y']) ?>" style="width:50px" />
							<input type="hidden" name="orig_open_y" value="<?php print($data['open_y']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">open_z:</td>
						<td>
							<input type="text" name="spawn_widgets|open_z" value="<?php print($data['open_z']) ?>" style="width:50px" />
							<input type="hidden" name="orig_open_z" value="<?php print($data['open_z']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">action_spawn_id:</td>
						<td>
							<input type="text" name="spawn_widgets|action_spawn_id" value="<?php print($data['action_spawn_id']) ?>" style="width:50px" />
							<input type="hidden" name="orig_action_spawn_id" value="<?php print($data['action_spawn_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">open_sound_file:</td>
						<td>
							<input type="text" name="spawn_widgets|open_sound_file" value="<?php print($data['open_sound_file']) ?>" style="width:450px" />
							<input type="hidden" name="orig_open_sound_file" value="<?php print($data['open_sound_file']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">close_sound_file:</td>
						<td>
							<input type="text" name="spawn_widgets|close_sound_file" value="<?php print($data['close_sound_file']) ?>" style="width:450px" />
							<input type="hidden" name="orig_close_sound_file" value="<?php print($data['close_sound_file']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">open_duration:</td>
						<td>
							<input type="text" name="spawn_widgets|open_duration" value="<?php print($data['open_duration']) ?>" style="width:50px" />
							<input type="hidden" name="orig_open_duration" value="<?php print($data['open_duration']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">close_x:</td>
						<td>
							<input type="text" name="spawn_widgets|close_x" value="<?php print($data['close_x']) ?>" style="width:50px" />
							<input type="hidden" name="orig_close_x" value="<?php print($data['close_x']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">close_y:</td>
						<td>
							<input type="text" name="spawn_widgets|close_y" value="<?php print($data['close_y']) ?>" style="width:50px" />
							<input type="hidden" name="orig_close_y" value="<?php print($data['close_y']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">close_z:</td>
						<td>
							<input type="text" name="spawn_widgets|close_z" value="<?php print($data['close_z']) ?>" style="width:50px" />
							<input type="hidden" name="orig_close_z" value="<?php print($data['close_z']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">linked_spawn_id:</td>
						<td>
							<input type="text" name="spawn_widgets|linked_spawn_id" value="<?php print($data['linked_spawn_id']) ?>" style="width:50px" />
							<input type="hidden" name="orig_linked_spawn_id" value="<?php print($data['linked_spawn_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">house_id:</td>
						<td>
							<input type="text" name="spawn_widgets|house_id" value="<?php print($data['house_id']) ?>" style="width:50px" />
							<input type="hidden" name="orig_house_id" value="<?php print($data['house_id']) ?>" />
						</td>
					</tr>
				</table>
				</fieldset>
			</td>
		</tr>
		<?php 
		if( $eq2->CheckAccess(G_DEVELOPER) ) 
		{ 
		?>
		<tr>
			<td colspan="2" align="center">
				<input type="submit" name="cmd" value="Update" class="submit" />
				<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
				<input type="hidden" name="table_name" value="spawn_widgets" />
			</td>
		</tr>
		<?php 
		} 
		?>
	</form>
	</table>
	<?php
}
		
function spawn_ground() 
{
	global $eq2, $spawns;

	$gsid_array = $spawns->GetGroundSpawnIDs();
	$data = $spawns->GetSpawnGroundData();
	
	if( is_array($data) )
	{
 ?>
	<br />
	<table width="1000" border="0" cellpadding="5">
	<form method="post" name="single|spawn_groundForm" />
		<tr>
			<td>
				<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span><br />&nbsp;
			</td>
		</tr>
		<tr>
			<td valign="top">
				<fieldset><legend>spawn_ground</legend>
				<table width="100%" cellpadding="0" border="0">
					<tr>
						<td align="right">id:</td>
						<td>
							<input type="text" name="spawn_ground|id" value="<?php print($data['id']) ?>" style="width:55px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
						<td align="right">spawn_id:</td>
						<td>
							<input type="text" name="spawn_ground|spawn_id" value="<?php print($data['spawn_id']) ?>" style="width:65px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_spawn_id" value="<?php print($data['spawn_id']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">number_harvests:</td>
						<td>
							<input type="text" name="spawn_ground|number_harvests" value="<?php print($data['number_harvests']) ?>" style="width:25px;" />
							<input type="hidden" name="orig_number_harvests" value="<?php print($data['number_harvests']) ?>" />
						</td>
						<td align="right">num_attempts_per_harvest:</td>
						<td>
							<input type="text" name="spawn_ground|num_attempts_per_harvest" value="<?php print($data['num_attempts_per_harvest']) ?>" style="width:25px;" />
							<input type="hidden" name="orig_num_attempts_per_harvest" value="<?php print($data['num_attempts_per_harvest']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">groundspawn_id:</td>
						<td>
							<select name="spawn_ground|groundspawn_id" style="width:200px;">
								<?php 
								if( is_array($gsid_array) )
								{
									foreach($gsid_array as $key=>$val)
									{
										$selected = ($key == $data['groundspawn_id']) ? " selected" : "";
										printf('<option value="%s"%s>%s - %s</option>', $key, $selected, $key, $val);
									}
								}
								?>
							</select> <a href="server.php?page=groundspawns" target="_self">Edit Groundspawns</a>
							<input type="hidden" name="orig_groundspawn_id" value="<?php print($data['groundspawn_id']) ?>" />
						</td>
						<td align="right">collection_skill:</td>
						<td>
							<select name="spawn_ground|collection_skill" style="width:120px;">
								<option<?php if($data['collection_skill'] == 'Unused') print(" selected"); ?>>Unused</option>
								<option<?php if($data['collection_skill'] == 'Gathering') print(" selected"); ?>>Gathering</option>
								<option<?php if($data['collection_skill'] == 'Mining') print(" selected"); ?>>Mining</option>
								<option<?php if($data['collection_skill'] == 'Trapping') print(" selected"); ?>>Trapping</option>
								<option<?php if($data['collection_skill'] == 'Fishing') print(" selected"); ?>>Fishing</option>
								<option<?php if($data['collection_skill'] == 'Foresting') print(" selected"); ?>>Foresting</option>
								<option<?php if($data['collection_skill'] == 'Collecting') print(" selected"); ?>>Collecting</option>
							</select>
							<input type="hidden" name="orig_collection_skill" value="<?php print($data['collection_skill']) ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">randomize_heading:</td>
						<td>
							<?php $eq2->GenerateBlueCheckbox("spawn_ground|randomize_heading", $data['randomize_heading']) ?>
							<input type="hidden" name="orig_randomize_heading" value="<?php print($data['randomize_heading']) ?>" />
						</td>
					</tr>
				</table>
				</fieldset>
			</td>
		</tr>
		<?php 
		if( $eq2->CheckAccess(G_DEVELOPER) ) 
		{ 
		?>
		<tr>
			<td colspan="4" align="center">
				<input type="submit" name="cmd" value="Update" style="width:100px;" />&nbsp;
				<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
				<input type="hidden" name="table_name" value="spawn_ground" />
			</td>
		</tr>
		<?php 
		} 
		?>
	</form>
	</table>
	<?php
	} 
}

function appearance() 
{
	global $eq2, $spawns;

	?>
	<br />
	<table width="1000" cellpadding="5" border="0">
		<tr>
			<td><span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span></td>
		</tr>
		<tr>
			<td valign="top">
				<fieldset><legend>General Appearances</legend>
				<table>
					<tr align="center">
						<td>id</td>
						<td width="75">spawn_id</td>
						<td width="100">signed_value</td>
						<td width="100">type</td>
						<td width="75">red</td>
						<td width="75">green</td>
						<td width="75">blue</td>
						<td colspan="2">&nbsp;</th>
					</tr>
				<?php
				$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.npc_appearance WHERE spawn_id = %s", $spawns->spawn_id);
				$results = $eq2->RunQueryMulti();
					
				if( is_array($results) )
				{
					foreach($results as $data) 
					{
					?>
					<form method="post" name="naForm" />
					<tr align="center">
						<td>
							<strong><?php print($data['id']); ?></strong>
							<input type="hidden" name="orig_id" value="<?php print($data['id']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance|spawn_id" value="<?php print($data['spawn_id']); ?>" style="width:50px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_spawn_id" value="<?php print($data['spawn_id']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance|signed_value" value="<?php print($data['signed_value']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_signed_value" value="<?php print($data['signed_value']); ?>" />
						</td>
						<td>
							<?php $appearance_options = $spawns->GetAppearances($data['type']) ?>
							<select name="npc_appearance|type" class="combo">
								<option value="0">---</option>
								<?= $appearance_options ?>
							</select>
							<input type="hidden" name="orig_type" value="<?php print($data['type']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance|red" value="<?php print($data['red']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_red" value="<?php print($data['red']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance|green" value="<?php print($data['green']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_green" value="<?php print($data['green']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance|blue" value="<?php print($data['blue']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_blue" value="<?php print($data['blue']); ?>" />
						</td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" class="submit" /><?php } ?></td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Delete" class="submit" /><?php } ?></td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="npc_appearance" />
					</form>
					<?php
					}
				}
				
				if( $eq2->CheckAccess(G_DEVELOPER) ) 
				{ 
				?>
					<form method="post" name="naForm|new" />
					<tr align="center">
						<td><strong>new</strong></td>
						<td>
							<?php print($_GET['id']); ?>
							<input type="hidden" name="npc_appearance|spawn_id|new" value="<?= $spawns->spawn_id ?>" />
						</td>
						<td><input type="text" name="npc_appearance|signed_value|new" value="0" style="width:50px;" /></td>
						<td>
							<?php $appearance_options = $spawns->GetAppearances() ?>
							<select name="npc_appearance|type" class="combo">
								<option value="0">---</option>
								<?= $appearance_options ?>
							</select>
						</td>
						<td><input type="text" name="npc_appearance|red|new" value="0" style="width:50px;" /></td>
						<td><input type="text" name="npc_appearance|green|new" value="0" style="width:50px;" /></td>
						<td><input type="text" name="npc_appearance|blue|new" value="0" style="width:50px;" /></td>
						<td>
							<input type="submit" name="cmd" value="Insert" class="submit" />
						</td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="npc_appearance" />
					</form>
				<?php 
				} 
				?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	<br />
	<table width="1000" cellpadding="5" border="0">
		<tr>
			<td valign="top">
				<fieldset><legend>Equipment Appearances</legend>
				<table>
					<tr align="center">
						<td>id</td>
						<td width="75">spawn_id</td>
						<td width="100">slot_id</td>
						<td width="100">equip_type</td>
						<td width="75">red</td>
						<td width="75">green</td>
						<td width="75">blue</td>
						<td width="75">hl_red</td>
						<td width="75">hl_green</td>
						<td width="75">hl_blue</td>
						<td colspan="2">&nbsp;</th>
					</tr>
				<?php
				$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.npc_appearance_equip WHERE spawn_id = %s", $spawns->spawn_id);
				$results = $eq2->RunQueryMulti();
				
				if( is_array($results) )
				{
					foreach($results as $data) 
					{
					?>
					<form method="post" name="naeForm" />
					<tr align="center">
						<td>
							<strong><?php print($data['id']); ?></strong>
							<input type="hidden" name="orig_id" value="<?php print($data['id']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance_equip|spawn_id" value="<?php print($data['spawn_id']); ?>" style="width:50px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_spawn_id" value="<?php print($data['spawn_id']); ?>" />
						</td>
						<td>
							<?php $spawns->PrintAttachmentSlots($data['slot_id']) ?>
							<input type="hidden" name="orig_slot_id" value="<?php print($data['slot_id']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance_equip|equip_type" value="<?php print($data['equip_type']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_equip_type" value="<?php print($data['equip_type']); ?>" />
							<?php $tooltip = $spawns->GetAppearanceNameFromID($data['equip_type']) ?>
              &nbsp;<abbr title="<?= $tooltip ?>">?</abbr>
						</td>
						<td>
							<input type="text" name="npc_appearance_equip|red" value="<?php print($data['red']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_red" value="<?php print($data['red']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance_equip|green" value="<?php print($data['green']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_green" value="<?php print($data['green']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance_equip|blue" value="<?php print($data['blue']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_blue" value="<?php print($data['blue']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance_equip|highlight_red" value="<?php print($data['highlight_red']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_highlight_red" value="<?php print($data['highlight_red']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance_equip|highlight_green" value="<?php print($data['highlight_green']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_highlight_green" value="<?php print($data['highlight_green']); ?>" />
						</td>
						<td>
							<input type="text" name="npc_appearance_equip|highlight_blue" value="<?php print($data['highlight_blue']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_highlight_blue" value="<?php print($data['highlight_blue']); ?>" />
						</td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" /><?php } ?></td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="npc_appearance_equip" />
					</form>
					<?php
					}
				}
			
				if( $eq2->CheckAccess(G_DEVELOPER) ) 
				{ 
					?>
					<form method="post" name="naeForm|new" />
					<tr align="center">
						<td><strong>new</strong></td>
						<td>
							<?php print($spawns->spawn_id); ?>
							<input type="hidden" name="npc_appearance_equip|spawn_id|new" value="<?= $spawns->spawn_id ?>" />
						</td>
						<td><?php $spawns->PrintAttachmentSlots(null); ?></td>
						<td>&nbsp;&nbsp;
							<input type="text" name="npc_appearance_equip|equip_type|new" value="0" style="width:50px;" style="border:1px solid #cc0000;" />
							<input type="button" value="..." class="lookup" onclick="window.open('spawn_func.php?func=model','luApp','width=700,height=500,left=10,top=75,scrollbars=yes');" />
						</td>
						<td><input type="text" name="npc_appearance_equip|red|new" value="0" style="width:50px;" /></td>
						<td><input type="text" name="npc_appearance_equip|green|new" value="0" style="width:50px;" /></td>
						<td><input type="text" name="npc_appearance_equip|blue|new" value="0" style="width:50px;" /></td>
						<td><input type="text" name="npc_appearance_equip|highlight_red|new" value="0" style="width:50px;" /></td>
						<td><input type="text" name="npc_appearance_equip|highlight_green|new" value="0" style="width:50px;" /></td>
						<td><input type="text" name="npc_appearance_equip|highlight_blue|new" value="0" style="width:50px;" /></td>
						<td>
							<input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" id="naeAdd" />
						</td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="npc_appearance_equip" />
					</form>
				<?php 
				} 
				?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	<?php
}

function spawnlocation() 
{
	global $eq2, $spawns;
	$zone = (isset($_GET['zone'])?$_GET['zone']:"");
	$type = (isset($_GET['type'])?$_GET['type']:"");
	$id = (isset($_GET['id'])?$_GET['id']:"");
	$tab = (isset($_GET['tab'])?$_GET['tab']:"");
	$page = (isset($_GET['page'])?$_GET['page']:"");
	$displayType = (isset($_GET['adv'])?"advanced":"basic");
	$strOffset = str_repeat("\x20",22);

	$strHTML = "";
	$strHTML .= $strOffset . "<script src='//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>\n";
	$strHTML .= $strOffset . "<script id='rendered-js'>\n";
	$strHTML .= $strOffset . "  $(document).ready(function () {\n";
	$strHTML .= $strOffset . "    $('[data-toggle=\"toggle\"]').change(function () {\n";
	$strHTML .= $strOffset . "      $(this).parents().next('.hide').toggle();\n";
	$strHTML .= $strOffset . "    });\n";
	$strHTML .= $strOffset . "  });\n";
	$strHTML .= $strOffset . "</script>\n";
	$strHTML .= $strOffset . "<br />\n";
	$strHTML .= $strOffset . "<table width='1100' cellpadding='5' border='0'>\n";
	$strHTML .= $strOffset . "  <thead>\n";
	$strHTML .= $strOffset . "    <tr>\n";
	$strHTML .= $strOffset . "      <td>\n";
	$strHTML .= $strOffset . "        <span class='heading'>Editing: " . $spawns->spawn_name . "(" . $spawns->spawn_id . ")</span>\n";
	$strHTML .= $strOffset . "        <br><br>\n";
	$strHTML .= $strOffset . "        <span style='font-size:14px;font-weight:bold;color:red'>To create a new spawn location create a `spawn_location_entry` row followed by a `spawn_location_placement` below.</span>\n";
	$strHTML .= $strOffset . "        <br>\n";
	$strHTML .= $strOffset . "      </td>\n";
	$strHTML .= $strOffset . "    </tr>\n";
	$strHTML .= $strOffset . "    <tr>\n";
	$strHTML .= $strOffset . "      <td>\n";
	$strHTML .= $strOffset . "        <p>Jump to: <a href='#entry'>spawn_location_entry</a> | <a href='#placement'>spawn_location_placement</a> | <a href='#overrides'>overrides</a></p>\n";
	$strHTML .= $strOffset . "      </td>\n";
	$strHTML .= $strOffset . "    </tr>\n";
	$strHTML .= $strOffset . "  </thead>\n";
	$strHTML .= $strOffset . "  <tbody>\n";
	$strHTML .= $strOffset . "    <tbody class='labels'>\n";
	$strHTML .= $strOffset . "      <tr>\n";
	$strHTML .= $strOffset . "        <td valign='top'>\n";
	$strHTML .= $strOffset . "          <label for='spawn_location_name_table'>Collapse/Restore: Spawn Location Name Table</label>\n";
	$strHTML .= $strOffset . "          <input type='checkbox' name='spawn_location_name_table' id='spawn_location_name_table' data-toggle='toggle'>\n";
	$strHTML .= $strOffset . "        </td>\n";
	$strHTML .= $strOffset . "      </tr>\n";
	$strHTML .= $strOffset . "    </tbody>\n";
	$strHTML .= $strOffset . "    <tbody class='hide'>\n";
	$strHTML .= $strOffset . "      <tr>\n";
	$strHTML .= $strOffset . "        <td valign='top'>\n";
	
	$results = $spawns->GetSpawnLocationNames(); // moved here to get count()

	$strHTML .= $strOffset . "        <fieldset>\n";
	$strHTML .= $strOffset . "          <legend>Table: spawn_location_name (" . count($results) .")</label></legend>\n";
	$strHTML .= $strOffset . "          <table class='ContrastTable' cellpadding='0'>\n";
	$strHTML .= $strOffset . "                <tr align='center'>\n";
	$strHTML .= $strOffset . "                  <th width='75'>id</th>\n";
	$strHTML .= $strOffset . "                  <th width='100'>name</th>\n";
	$strHTML .= $strOffset . "                  <th colspan='2'>\n";
	if($displayType != "advanced")
	{
		$strHTML .= $strOffset . "                    <button onClick=\"location.href='spawns.php?tab=" . $tab . "&zone=" . $zone . "&type=" . $type . "&id=" . $id . "&adv=1'\">Show Advanced Form</button>\n";
	}else{
		$strHTML .= $strOffset . "                    <button onClick=\"location.href='spawns.php?tab=" . $tab . "&zone=" . $zone . "&type=" . $type . "&id=" . $id . "'\">Show Basic Form</button>\n";
	}
	$strHTML .= $strOffset . "                  </th>\n";
	$strHTML .= $strOffset . "                </tr>\n";
	
	if( is_array($results) )
	{
		if($displayType == "advanced")
		{
			$strHTML .= $strOffset . "                  <form method='post' name='spawn_location_name|BulkDel'>\n";
			$strHTML .= $strOffset . "                    <tr>\n";
			$strHTML .= $strOffset . "                      <td></td>\n";
			$strHTML .= $strOffset . "                      <td><input type='hidden' name='DeleteSpawn' value='true'></td>\n";
			$strHTML .= $strOffset . "                      <td><input type='submit' name='cmd' value='BulkDel'></td>\n";
			$strHTML .= $strOffset . "                    </tr>\n";
		}
		foreach($results as $data)
		{
			$strHTML .= $strOffset . "                <tr align='center'>\n";
			if($displayType != "advanced")
			{
				$strHTML .= $strOffset . "                  <form method='post' name='zsgForm'>\n";
			}
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                      <strong>" . $data['id'] ."</strong>\n";
			$strHTML .= $strOffset . "                      <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
			$strHTML .= $strOffset . "                      <input type='hidden' name='object_id' value='" . $spawns->spawn_name . "|" . $spawns->spawn_id . "' />\n";
			$strHTML .= $strOffset . "                      <input type='hidden' name='table_name' value='spawn_location_name' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                      <input type='text' name='spawn_location_name|name' value='" . $data['name'] ."' style='width:300px;' />\n";
			$strHTML .= $strOffset . "                      <input type='hidden' name='orig_name' value='" . $data['name'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			if($displayType == "advanced")
			{
				$strHTML .= $strOffset . "              " . $eq2->ReturnBlueCheckbox("delete_spawn_location_entry|" . $data['id'], false,) . "\n";
			}else{
				if( $eq2->CheckAccess(G_DEVELOPER) )
				{
					$strHTML .= $strOffset . "                      <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
					$strHTML .= $strOffset . "                      <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
				}
			}
			$strHTML .= $strOffset . "                    </td>\n";
			if($displayType != "advanced")
			{
				$strHTML .= $strOffset . "                  </form>\n";
			}
			$strHTML .= $strOffset . "                </tr>\n";
		}
		if($displayType == "advanced")
		{
			$strHTML .= $strOffset . "            </form>\n";
		}
	}
	$strHTML .= $strOffset . "            </table>\n";
	$strHTML .= $strOffset . "        </fieldset>\n";
	$strHTML .= $strOffset . "      </td>\n";
	$strHTML .= $strOffset . "    </tr>\n";
	$strHTML .= $strOffset . "  </tbody>\n";
	$strHTML .= $strOffset . "  </tbody>\n";
	$strHTML .= $strOffset . "</table>\n";
	$strHTML .= $strOffset . "<a name='entry'></a>\n";
	$strHTML .= $strOffset . "<br />\n";
	$strHTML .= $strOffset . "Jump <a href='#'>back to top</a>\n";
	$strHTML .= $strOffset . "<br />\n";
	$strHTML .= $strOffset . "<table width='1100' cellpadding='5' border='0'>\n";
	$strHTML .= $strOffset . "  <tbody>\n";
	$strHTML .= $strOffset . "    <tbody class='labels'>\n";
	$strHTML .= $strOffset . "      <tr>\n";
	$strHTML .= $strOffset . "        <td valign='top'>\n";
	$strHTML .= $strOffset . "          <label for='spawn_location_entry_table'>Collapse/Restore: Spawn Location Entry Table</label>\n";
	$strHTML .= $strOffset . "          <input type='checkbox' name='spawn_location_entry_table' id='spawn_location_entry_table' data-toggle='toggle'>\n";
	$strHTML .= $strOffset . "        </td>\n";
	$strHTML .= $strOffset . "      </tr>\n";
	$strHTML .= $strOffset . "    </tbody>\n";
	$strHTML .= $strOffset . "    <tbody class='hide'>\n";

	$strHTML .= $strOffset . "      <tr>\n";
	$strHTML .= $strOffset . "        <td valign='top'>\n";
	
	$results = $spawns->GetSpawnLocationEntries(); // moved here to get count()

	$strHTML .= $strOffset . "      <fieldset>\n";
	$strHTML .= $strOffset . "        <legend>Table: spawn_location_entry (" . count($results) . ")</legend>\n";
	$strHTML .= $strOffset . "        <table class='ContrastTable' cellpadding='0'>\n";
	$strHTML .= $strOffset . "          <tr align='center'>\n";
	$strHTML .= $strOffset . "            <th colspan='5'>\n";
	$strHTML .= $strOffset . "            </th>\n";
	$strHTML .= $strOffset . "            <th colspan='4'>\n";
	$strHTML .= $strOffset . "              <strong style='color:red;text-decoration:underline'>Conditionals</strong>\n";
	$strHTML .= $strOffset . "            </th>\n";
	$strHTML .= $strOffset . "            <th colspan='2'>\n";
	$strHTML .= $strOffset . "            </th>\n";
	$strHTML .= $strOffset . "          </tr>\n";
	$strHTML .= $strOffset . "          <tr align='center'>\n";
	$strHTML .= $strOffset . "            <th width='55'>id</th>\n";
	$strHTML .= $strOffset . "            <th width='125'>name</th>\n";
	$strHTML .= $strOffset . "            <th width='105'>spawn_id</th>\n";
	$strHTML .= $strOffset . "            <th width='105'>spawn_location_id</th>\n";
	$strHTML .= $strOffset . "            <th width='105'>spawnpercentage</th>\n";
	$strHTML .= $strOffset . "            <th width='45' style='color:red'>Day</th>\n";
	$strHTML .= $strOffset . "            <th width='45' style='color:red'>Night</th>\n";
	$strHTML .= $strOffset . "            <th width='45' style='color:red'>Raining</th>\n";
	$strHTML .= $strOffset . "            <th width='45' style='color:red'>No Rain</th>\n";
	$strHTML .= $strOffset . "            <th colspan='2'>&nbsp;</th>\n";
	$strHTML .= $strOffset . "          </tr>\n";

	if( $eq2->CheckAccess(G_DEVELOPER) )
	{
		$strHTML .= $strOffset . "          <tr align='center'>\n";
		$strHTML .= $strOffset . "            <form method='post' name='zseForm|new'>\n";
		$strHTML .= $strOffset . "              <td><strong>new</strong></td>\n";
		$strHTML .= $strOffset . "              <td><input type='text' name='spawn_location_name|name|new' value='' style='width:125px;' /></td>\n";
		$strHTML .= $strOffset . "              <td><strong>" . $_GET['id'] . "</strong></td>\n";
		$strHTML .= $strOffset . "              <td><strong>new</strong></td>\n";
		$strHTML .= $strOffset . "              <td><input type='text' name='spawn_location_entry|spawnpercentage|new' value='100' style='width:100px;' /></td>\n";

		for ($i = 0; $i < 4; $i++) {
			$mask = 1 << $i;
			$strHTML .= $strOffset . "            <td>\n";

			$strHTML .= $strOffset . $eq2->ReturnBlueCheckbox("spawn_location_entry|condition|".$mask, false);
			$strHTML .= $strOffset . "            </td>\n";
		}

		$strHTML .= $strOffset . "            <td colspan='2'>\n";
		$strHTML .= $strOffset . "              <input type='submit' value='Insert' style='font-size:10px; width:60px' />\n";
		$strHTML .= $strOffset . "              <input type='hidden' name='cmd' value='InsertSpawnLoc' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "          </form>\n";
		$strHTML .= $strOffset . "        </tr>\n";
	}
	if( is_array($results) )
	{
		foreach($results as $data)
		{
			$strHTML .= $strOffset . "        <tr align='center'>\n";
			$strHTML .= $strOffset . "          <form method='post' name='zseForm'>\n";
			$strHTML .= $strOffset . "            <td>\n";
			$strHTML .= $strOffset . "              <strong>" . $data['id'] . "</strong>\n";
			$strHTML .= $strOffset . "              <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
			$strHTML .= $strOffset . "              <input type='hidden' name='object_id' value='<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>' />\n";
			$strHTML .= $strOffset . "              <input type='hidden' name='table_name' value='spawn_location_entry' />\n";
			$strHTML .= $strOffset . "            </td>\n";
			$strHTML .= $strOffset . "            <td>\n";
			$strHTML .= $strOffset . "              <span>" . $data['name'] . "</span>\n";
			$strHTML .= $strOffset . "            </td>\n";
			$strHTML .= $strOffset . "            <td>\n";
			$strHTML .= $strOffset . "              <input type='text' name='spawn_location_entry|spawn_id' value='" . $data['spawn_id'] . "' style='width:100px;' />\n";
			$strHTML .= $strOffset . "              <input type='hidden' name='orig_spawn_id' value='" . $data['spawn_id'] . "' />\n";
			$strHTML .= $strOffset . "            </td>\n";
			$strHTML .= $strOffset . "            <td>\n";
			$strHTML .= $strOffset . "              <input type='text' name='spawn_location_entry|spawn_location_id' value='" . $data['spawn_location_id'] . "' style='width:100px;' />\n";
			$strHTML .= $strOffset . "              <input type='hidden' name='orig_spawn_location_id' value='" . $data['spawn_location_id'] . "' />\n";
			$strHTML .= $strOffset . "            </td>\n";
			$strHTML .= $strOffset . "            <td>\n";
			$strHTML .= $strOffset . "              <input type='text' name='spawn_location_entry|spawnpercentage' value='" . $data['spawnpercentage'] . "' style='width:100px;' />\n";
			$strHTML .= $strOffset . "              <input type='hidden' name='orig_spawnpercentage' value='" . $data['spawnpercentage'] . "' />\n";
			$strHTML .= $strOffset . "              <input type='hidden' name='orig_condition' value='" . $data['condition'] . "' />\n";
			$strHTML .= $strOffset . "            </td>\n";

			$cond = $data['condition'];
			for ($i = 0; $i < 4; $i++)
			{
				$mask = 1 << $i;
				$strHTML .= $strOffset . "        <td>\n";
				$strHTML .= $strOffset . $eq2->ReturnBlueCheckbox("spawn_location_entry|condition|".$mask, $cond & $mask);
				$strHTML .= $strOffset . "        </td>\n";
			}
			if( $eq2->CheckAccess(G_DEVELOPER) ) 
			{
				$strHTML .= $strOffset . "        <td><input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' /></td>\n";
				$strHTML .= $strOffset . "        <td><input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' /></td>\n";
			}
			$strHTML .= $strOffset . "      </form>\n";
			$strHTML .= $strOffset . "    </tr>\n";
		}
	}
	$strHTML .= $strOffset . "      </tbody>\n";
	$strHTML .= $strOffset . "    </tbody>\n";
	$strHTML .= $strOffset . "  </table>\n";
	$strHTML .= $strOffset . "</fieldset>\n";
	$strHTML .= $strOffset . "</td>\n";
	$strHTML .= $strOffset . "</tr>\n";
	$strHTML .= $strOffset . "</table>\n";
	$strHTML .= $strOffset . "<a name='placement'></a>\n";
	$strHTML .= $strOffset . "<br />\n";
	$strHTML .= $strOffset . "Jump <a href='#'>back to top</a>\n";
	$strHTML .= $strOffset . "<br />\n";
	$strHTML .= $strOffset . "<fieldset>\n";
	$strHTML .= $strOffset . "  <legend>Table: spawn_location_placement</legend>\n";
	$strHTML .= $strOffset . "    <table width='1100' cellpadding='5' border='0'>\n";
	$strHTML .= $strOffset . "      <tbody>\n";
	$strHTML .= $strOffset . "        <tbody class='labels'>\n";
	$strHTML .= $strOffset . "          <tr>\n";
	$strHTML .= $strOffset . "            <td valign='top'>\n";
	$strHTML .= $strOffset . "              <label for='spawn_location_placement_location'>Collapse/Restore: spawn_location_placement_location_data Table</label>\n";
	$strHTML .= $strOffset . "              <input type='checkbox' name='spawn_location_placement_location' id='spawn_location_placement_location' data-toggle='toggle'>\n";
	$strHTML .= $strOffset . "            </td>\n";
	$strHTML .= $strOffset . "          </tr>\n";
	$strHTML .= $strOffset . "        </tbody>\n";
	$strHTML .= $strOffset . "        <tbody class='hide'>\n";
	$strHTML .= $strOffset . "          <tr>\n";
	$strHTML .= $strOffset . "            <td valign='top'>\n";
	
	$results = $spawns->GetSpawnLocationPlacements(); // moved here to get count()
	
	$strHTML .= $strOffset . "              <fieldset>\n";
	$strHTML .= $strOffset . "                <legend>Location Data</legend>\n";
	$strHTML .= $strOffset . "                  <table border='0' cellpadding='0' cellspacing='4'>\n";
	
	if( $eq2->CheckAccess(G_DEVELOPER) )
	{
		$strHTML .= $strOffset . "              <tr>\n";
		$strHTML .= $strOffset . "                <form method='post' id='sOffset'>\n";
		$strHTML .= $strOffset . "                  <td colspan='10'>Add X,Z Offsets to all spawn points for this spawn_id:\n";
		$strHTML .= $strOffset . "                    <input type='submit' name='cmd' value='Sml (5px)' class='submit' />\n";
		$strHTML .= $strOffset . "                    <input type='submit' name='cmd' value='Med (10px)' class='submit' />\n";
		$strHTML .= $strOffset . "                    <input type='submit' name='cmd' value='Lrg (20px)' class='submit' />\n";
		$strHTML .= $strOffset . "                    <input type='submit' name='cmd' value='None (0px)' class='submit' />\n";
		$strHTML .= $strOffset . "                    <input type='hidden' name='object_id' value='" . $spawns->spawn_name . "|" . $spawns->spawn_id . "' />\n";
		$strHTML .= $strOffset . "                    <input type='hidden' name='table_name' value='spawn_location_placement' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td colspan='5'>&nbsp;</td>\n";
		$strHTML .= $strOffset . "                </form>\n";
		$strHTML .= $strOffset . "              </tr>\n";
	}
	$strHTML .= $strOffset . "              <tr>\n";
	$strHTML .= $strOffset . "                <td colspan='17'><hr /></td>\n";
	$strHTML .= $strOffset . "              </tr>\n";
	$strHTML .= $strOffset . "              <tr>\n";
	$strHTML .= $strOffset . "                <td>\n";
	$strHTML .= $strOffset . "                  <table class='ContrastTable'>\n";
	$strHTML .= $strOffset . "                    <tr align='center'>\n";
	$strHTML .= $strOffset . "                      <th width='50'>id</th>\n";
	$strHTML .= $strOffset . "                      <th>zone_id</th>\n";
	$strHTML .= $strOffset . "                      <th>location_id</th>\n";
	$strHTML .= $strOffset . "                      <th>x</th>\n";
	$strHTML .= $strOffset . "                      <th>y</th>\n";
	$strHTML .= $strOffset . "                      <th>z</th>\n";
	$strHTML .= $strOffset . "                      <th>x_offset</th>\n";
	$strHTML .= $strOffset . "                      <th>y_offset</th>\n";
	$strHTML .= $strOffset . "                      <th>z_offset</th>\n";
	$strHTML .= $strOffset . "                      <th>heading</th>\n";
	$strHTML .= $strOffset . "                      <th>pitch</th>\n";
	$strHTML .= $strOffset . "                      <th>roll</th>\n";
	$strHTML .= $strOffset . "                      <th>respawn</th>\n";
	$strHTML .= $strOffset . "                      <th>respawn_offset_low</th>\n";
	$strHTML .= $strOffset . "                      <th>respawn_offset_high</th>\n";
	$strHTML .= $strOffset . "                      <th>duplicated_spawn</th>\n";
	$strHTML .= $strOffset . "                      <th>expire_timer</th>\n";
	$strHTML .= $strOffset . "                      <th>grid_id</th>\n";
	$strHTML .= $strOffset . "                      <th colspan='2'></th>\n";
	$strHTML .= $strOffset . "                    </tr>\n";
					
	if( $eq2->CheckAccess(G_DEVELOPER) )
	{
		$strHTML .= $strOffset . "                    <form method='post' name='zsForm|new'>\n";
		$strHTML .= $strOffset . "                      <tr align='center'>\n";
		$strHTML .= $strOffset . "                        <td><strong>new</strong></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|zone_id|new' value='" . $_GET['zone'] . "' style='width:40px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|spawn_location_id|new' value='" . $spawns->CreatedLocID . "' style='width:70px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|x|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|y|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|z|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|x_offset|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|y_offset|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|z_offset|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|heading|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|pitch|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|roll|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|respawn|new' value='300' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|respawn_offset_low|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|respawn_offset_high|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|duplicated_spawn|new' value='1' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|expire_timer|new' value='0' style='width:50px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td><input type='text' name='spawn_location_placement|grid_id|new' value='0' style='width:90px;' /></td>\n";
		$strHTML .= $strOffset . "                        <td colspan='2'>\n";
		$strHTML .= $strOffset . "                          <input type='submit' name='cmd' value='Insert' style='font-size:10px; width:60px' />\n";
		$strHTML .= $strOffset . "                        </td>\n";
		$strHTML .= $strOffset . "                      </tr>\n";
		$strHTML .= $strOffset . "                      <input type='hidden' name='object_id' value='" . $spawns->spawn_name . "|" . $spawns->spawn_id . "' />\n";
		$strHTML .= $strOffset . "                      <input type='hidden' name='table_name' value='spawn_location_placement' />\n";
		$strHTML .= $strOffset . "                    </form>\n";
	}
	if( is_array($results) )
	{
		foreach($results as $data)
		{
			$strHTML .= $strOffset . "                    <tr align='center'>\n";
			$strHTML .= $strOffset . "                      <form method='post' name='naeForm' >\n";
			$strHTML .= $strOffset . "                        <td>\n";
			$strHTML .= $strOffset . "                          <strong>" . $data['id'] . "</strong>\n";
			$strHTML .= $strOffset . "                          <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
			$strHTML .= $strOffset . "                        </td>\n";
			$strHTML .= $strOffset . "                        <td>\n";
			$strHTML .= $strOffset . "                          <input type='text' name='spawn_location_placement|zone_id' value='" . $data['zone_id'] . "' style='width:40px;' />\n";
			$strHTML .= $strOffset . "                          <input type='hidden' name='orig_zone_id' value='" . $data['zone_id'] . "' />\n";
			$strHTML .= $strOffset . "                          <input type='hidden' name='object_id' value='" . $spawns->spawn_name . "|" . $spawns->spawn_id . "' />\n";
			$strHTML .= $strOffset . "                          <input type='hidden' name='table_name' value='spawn_location_placement' />\n";
			$strHTML .= $strOffset . "                        </td>\n";
			$strHTML .= $strOffset . "                        <td>\n";
			$strHTML .= $strOffset . "                          <input type='text' name='spawn_location_placement|spawn_location_id' value='" . $data['spawn_location_id'] . "' style='width:70px;' />\n";
			$strHTML .= $strOffset . "                          <input type='hidden' name='orig_spawn_location_id' value='" . $data['spawn_location_id'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|x' value='" . $data['x'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_x' value='" . $data['x'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|y' value='" . $data['y'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_y' value='" . $data['y'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|z' value='" . $data['z'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_z' value='" . $data['z'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|x_offset' value='" . $data['x_offset'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_x_offset' value='" . $data['x_offset'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|y_offset' value='" . $data['y_offset'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_y_offset' value='" . $data['y_offset'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|z_offset' value='" . $data['z_offset'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_z_offset' value='" . $data['z_offset'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|heading' value='" . $data['heading'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_heading' value='" . $data['heading'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|pitch' value='" . $data['pitch'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_pitch' value='" . $data['pitch'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|roll' value='" . $data['roll'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_roll' value='" . $data['roll'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|respawn' value='" . $data['respawn'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_respawn' value='" . $data['respawn'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|respawn_offset_low' value='" . $data['respawn_offset_low'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_respawn_offset_low' value='" . $data['respawn_offset_low'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|respawn_offset_high' value='" . $data['respawn_offset_high'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_respawn_offset_high' value='" . $data['respawn_offset_high'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|duplicated_spawn' value='" . $data['duplicated_spawn'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_duplicated_spawn' value='" . $data['duplicated_spawn'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|expire_timer' value='" . $data['expire_timer'] . "' style='width:50px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_expire_timer' value='" . $data['expire_timer'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			$strHTML .= $strOffset . "                    <td>\n";
			$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|grid_id' value='" . $data['grid_id'] . "' style='width:90px;' />\n";
			$strHTML .= $strOffset . "                    <input type='hidden' name='orig_grid_id' value='" . $data['grid_id'] . "' />\n";
			$strHTML .= $strOffset . "                    </td>\n";
			if( $eq2->CheckAccess(G_DEVELOPER) ) 
			{
				$strHTML .= $strOffset . "                    <td>\n";
				$strHTML .= $strOffset . "                      <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
				$strHTML .= $strOffset . "                      <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
				$strHTML .= $strOffset . "                    </td>\n";
			}
			$strHTML .= $strOffset . "                    </form>\n";
			$strHTML .= $strOffset . "                  </tr>\n";
		}
	}
	$strHTML .= $strOffset . "                    </table>\n";
	$strHTML .= $strOffset . "                  </td>\n";
	$strHTML .= $strOffset . "                </tr>\n";
	$strHTML .= $strOffset . "            </table>\n";
	$strHTML .= $strOffset . "          </fieldset>\n";
	$strHTML .= $strOffset . "        </tbody>\n";
	$strHTML .= $strOffset . "        </tbody>\n";

	$strHTML .= $strOffset . "        </td>\n";
	$strHTML .= $strOffset . "      </tr>\n";
	$strHTML .= $strOffset . "      <tbody>\n";
	$strHTML .= $strOffset . "        <tbody class='labels'>\n";
	$strHTML .= $strOffset . "          <tr>\n";
	$strHTML .= $strOffset . "            <td valign='top'>\n";
	$strHTML .= $strOffset . "              <label for='spawn_location_placement_overrides'>Collapse/Restore: spawn_location_placement_overrides Table</label>\n";
	$strHTML .= $strOffset . "              <input type='checkbox' name='spawn_location_placement_overrides' id='sspawn_location_placement_overrides' data-toggle='toggle'>\n";
	$strHTML .= $strOffset . "            </td>\n";
	$strHTML .= $strOffset . "          </tr>\n";
	$strHTML .= $strOffset . "        </tbody>\n";
	$strHTML .= $strOffset . "        <tbody class='hide'>\n";
	$strHTML .= $strOffset . "          <tr>\n";
	$strHTML .= $strOffset . "            <td>\n";
	$strHTML .= $strOffset . "              <a name='overrides'></a>\n";
	$strHTML .= $strOffset . "              <fieldset>\n";
	$strHTML .= $strOffset . "                <legend>Overrides</legend>\n";
	$strHTML .= $strOffset . "                  <table class='ContrastTable'>\n";
	$strHTML .= $strOffset . "                    <tr align='center'>\n";
	$strHTML .= $strOffset . "                <th>id</th>\n";
	$strHTML .= $strOffset . "                <th>location_id</th>\n";
	$strHTML .= $strOffset . "                <th>lvl</th>\n";
	$strHTML .= $strOffset . "                <th>difficulty</th>\n";
	$strHTML .= $strOffset . "                <th>hp</th>\n";
	$strHTML .= $strOffset . "                <th>pow</th>\n";
	$strHTML .= $strOffset . "                <th>str</th>\n";
	$strHTML .= $strOffset . "                <th>sta</th>\n";
	$strHTML .= $strOffset . "                <th>wis</th>\n";
	$strHTML .= $strOffset . "                <th>int</th>\n";
	$strHTML .= $strOffset . "                <th>agi</th>\n";
	$strHTML .= $strOffset . "                <th>heat</th>\n";
	$strHTML .= $strOffset . "                <th>cold</th>\n";
	$strHTML .= $strOffset . "                <th>magic</th>\n";
	$strHTML .= $strOffset . "                <th>mental</th>\n";
	$strHTML .= $strOffset . "                <th>divine</th>\n";
	$strHTML .= $strOffset . "                <th>disease</th>\n";
	$strHTML .= $strOffset . "                <th>poison</th>\n";
	$strHTML .= $strOffset . "                <th colspan='2'>&nbsp;</th>\n";
	$strHTML .= $strOffset . "              </tr>\n";

	foreach($results as $data)
	{
		$strHTML .= $strOffset . "              <tr align='center'>\n";
		$strHTML .= $strOffset . "                <form method='post' name='slpOverrideForm'>\n";
		$strHTML .= $strOffset . "                  <td width='30'>\n";
		$strHTML .= $strOffset . "                    <strong>" . $data['id'] . "</strong>\n";
		$strHTML .= $strOffset . "                    <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td width='30'>\n";
		$strHTML .= $strOffset . "                    <strong>" . $data['spawn_location_id'] . "</strong>\n";
		$strHTML .= $strOffset . "                    <input type='hidden' name='orig_spawn_location_id' value='" . $data['spawn_location_id'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                    <input type='text' name='spawn_location_placement|lvl_override' value='" . $data['lvl_override'] . "' style='width:30px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_lvl_override' value='" . $data['lvl_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|difficulty_override' value='" . $data['difficulty_override'] . "' style='width:30px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_difficulty_override' value='" . $data['difficulty_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|hp_override' value='" . $data['hp_override'] . "' style='width:90px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_hp_override' value='" . $data['hp_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|mp_override' value='" . $data['mp_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_mp_override' value='" . $data['mp_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|str_override' value='" . $data['str_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_str_override' value='" . $data['str_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|sta_override' value='" . $data['sta_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_sta_override' value='" . $data['sta_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|wis_override' value='" . $data['wis_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_wis_override' value='" . $data['wis_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|int_override' value='" . $data['int_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_int_override' value='" . $data['int_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|agi_override' value='" . $data['agi_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_agi_override' value='" . $data['agi_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|heat_override' value='" . $data['heat_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_heat_override' value='" . $data['heat_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|cold_override' value='" . $data['cold_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_cold_override' value='" . $data['cold_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|magic_override' value='" . $data['magic_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_magic_override' value='" . $data['magic_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|mental_override' value='" . $data['mental_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_mental_override' value='" . $data['mental_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|divine_override' value='" . $data['divine_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_divine_override' value='" . $data['divine_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|disease_override' value='" . $data['disease_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_disease_override' value='" . $data['disease_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";
		$strHTML .= $strOffset . "                  <td>\n";
		$strHTML .= $strOffset . "                  <input type='text' name='spawn_location_placement|poison_override' value='" . $data['poison_override'] . "' style='width:50px;' />\n";
		$strHTML .= $strOffset . "                  <input type='hidden' name='orig_poison_override' value='" . $data['poison_override'] . "' />\n";
		$strHTML .= $strOffset . "                  </td>\n";

		if ($eq2->CheckAccess(G_DEVELOPER))
		{
			$strHTML .= $strOffset . "                  <td>\n";
			$strHTML .= $strOffset . "                  <input type='hidden' name='table_name' value='spawn_location_placement' />\n";
			$strHTML .= $strOffset . "                  <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= $strOffset . "                  </td>\n";
			$strHTML .= $strOffset . "                  <td>\n";
			$strHTML .= $strOffset . "                  <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
			$strHTML .= $strOffset . "                  </td>\n";
		}else{
			$strHTML .= $strOffset . "                  <td colspan='2'></td>\n";
		}
		$strHTML .= $strOffset . "                  </form>\n";
		$strHTML .= $strOffset . "                </tr>\n";
	}
	$strHTML .= $strOffset . "          </tbody>\n";
	$strHTML .= $strOffset . "          </tbody>\n";

	$strHTML .= $strOffset . "                  </table>\n";
	$strHTML .= $strOffset . "                </fieldset>\n";
	$strHTML .= $strOffset . "              </td>\n";
	$strHTML .= $strOffset . "            </tr>\n";
	$strHTML .= $strOffset . "          </table>\n";
	$strHTML .= $strOffset . "        </fieldset>\n";
	print($strHTML);
}

function spawngroup() 
{
	global $eq2, $spawns;

	?>
	<br />
	<table width="800" cellpadding="5" border="0">
		<tr>
			<td colspan="6">
				<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span><br />
				<p>Jump to: <a href="#associations">spawn_location_group_associations</a> | <a href="#chances">spawn_location_group_chances</a></p>
				<p>Remember when editing spawn group data, that other spawn_Id's belong to these groups as well. <strong>Update/Delete with caution!</strong></p>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<fieldset><legend>Table: spawn_location_group</legend>
				<table cellpadding="0" cellspacing="4">
					<tr>
						<td width="55" align="center">id</td>
						<td>name</td>
						<td>group_id</td>
						<td>placement_id</td>
						<td colspan="2">&nbsp;</td>
					</tr>
				<?php
				$results = $spawns->GetSpawnLocationGroup();
				
				if( is_array($results) )
				{
					foreach($results as $data)
					{
					?>
					<form method="post" name="slgForm" />
					<tr>
						<td>
							<strong><?php print($data['id']); ?></strong>
							<input type="hidden" name="orig_id" value="<?php print($data['id']); ?>" />
						</td>
						<td>
							<input type="text" name="spawn_location_group|name" value="<?php print($data['name']); ?>" style="width:200px;" />
							<input type="hidden" name="orig_name" value="<?php print($data['name']); ?>" />
						</td>
						<td>
							<input type="text" name="spawn_location_group|group_id" value="<?php print($data['group_id']); ?>" style="width:80px;" />
							<input type="hidden" name="orig_group_id" value="<?php print($data['group_id']); ?>" />
						</td>
						<td>
							<input type="text" name="spawn_location_group|placement_id" value="<?php print($data['placement_id']); ?>" style="width:80px;" />
							<input type="hidden" name="orig_placement_id" value="<?php print($data['placement_id']); ?>" />
						</td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" class="submit" /><?php } ?></td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Delete" class="submit" /><?php } ?></td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="spawn_location_group" />
					</form>
					<?php
					}
				}
				
				if( $eq2->CheckAccess(G_DEVELOPER) ) 
				{ 
				?>
					<form method="post" name="slgForm|new" />
					<tr align="center">
						<td><strong>new</strong></td>
						<td>
							<input type="text" name="spawn_location_group|name|new" value="" style="width:200px;" />
						</td>
						<td>
							<input type="text" name="spawn_location_group|group_id|new" value="" style="width:80px;" />
						</td>
						<td>
							<input type="text" name="spawn_location_group|placement_id|new" value="" style="width:80px;" />
						</td>
						<td>
							<input type="submit" name="cmd" value="Insert" class="submit" />
						</td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="spawn_location_group" />
					</form>
				<?php 
				} 
				?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	<a name="associations"></a>
	<br />
	Jump <a href="#">back to top</a>
	<br />
	&nbsp;
	<table width="700" cellpadding="5" border="0">
		<tr>
			<td valign="top">
				<fieldset><legend>Table: spawn_location_group_associations</legend>
				<table border="0" cellpadding="0" cellspacing="4">
					<tr>
						<td width="55" align="center">id</td>
						<td>group_id1</td>
						<td>group_id2</td>
						<td>type</td>
						<td colspan="2">&nbsp;</th>
					</tr>
				<?php
				$results = $spawns->GetSpawnLocationGroupAssociations();
				
				if( is_array($results) )
				{
					foreach($results as $data)
					{
					?>
					<form method="post" name="slgaForm" />
					<tr align="center">
						<td>
							<strong><?php print($data['id']); ?></strong>
							<input type="hidden" name="orig_id" value="<?php print($data['id']); ?>" />
						</td>
						<td>
							<input type="text" name="spawn_location_group_associations|group_id1" value="<?php print($data['group_id1']); ?>" style="width:80px;" />
							<input type="hidden" name="orig_group_id1" value="<?php print($data['group_id1']); ?>" />
						</td>
						<td>
							<input type="text" name="spawn_location_group_associations|group_id2" value="<?php print($data['group_id2']); ?>" style="width:80px;" />
							<input type="hidden" name="orig_group_id2" value="<?php print($data['group_id2']); ?>" />
						</td>
						<td>
							<input type="text" name="spawn_location_group_associations|type" value="<?php print($data['type']); ?>" style="width:200px;" />
							<input type="hidden" name="orig_type" value="<?php print($data['type']); ?>" />
						</td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" class="submit" /><?php } ?></td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Delete" class="submit" /><?php } ?></td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="spawn_location_group_associations" />
					</form>
					<?php
					}
				}

				if( $eq2->CheckAccess(G_DEVELOPER) ) 
				{ 
				?>
					<form method="post" name="slgaForm|new" />
					<tr align="center">
						<td><strong>new</strong></td>
						<td><input type="text" name="spawn_location_group_associations|group_id1|new" value="" style="width:80px;" /></td>
						<td><input type="text" name="spawn_location_group_associations|group_id2|new" value="" style="width:80px;" /></td>
						<td><input type="text" name="spawn_location_group_associations|type|new" value="SPAWN_SEPARATELY" style="width:200px;" readonly="readonly" /></td>
						<td><input type="submit" name="cmd" value="Insert" class="submit" /></td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="spawn_location_group_associations" />
					</form>
				<?php 
				} 
				?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	<a name="chances"></a>
	<br />
	Jump <a href="#">back to top</a>
	<br />
	&nbsp;
	<table width="700" cellpadding="5" border="0">
		<tr>
			<td valign="top">
				<fieldset><legend>Table: spawn_location_group_chances</legend>
				<table border="0" cellpadding="0" cellspacing="4">
					<tr>
						<td width="55" align="center">id</td>
						<td>group_id</td>
						<td>percentage</td>
						<td colspan="2">&nbsp;</th>
					</tr>
					<?php
					$results = $spawns->GetSpawnLocationGroupChances();
					
					if( is_array($results) )
					{
						foreach($results as $data)
						{
						?>
						<form method="post" name="slgcForm" />
						<tr>
							<td>
								<strong><?php print($data['id']); ?></strong>
								<input type="hidden" name="orig_id" value="<?php print($data['id']); ?>" />
							</td>
							<td>
								<input type="text" name="spawn_location_group_chances|group_id" value="<?php print($data['group_id']); ?>" style="width:80px;" />
								<input type="hidden" name="orig_group_id" value="<?php print($data['group_id']); ?>" />
							</td>
							<td>
								<input type="text" name="spawn_location_group_chances|percentage" value="<?php print($data['percentage']); ?>" style="width:80px;" />
								<input type="hidden" name="orig_percentage" value="<?php print($data['percentage']); ?>" />
							</td>
							<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" class="submit" /><?php } ?></td>
							<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Delete" class="submit" /><?php } ?></td>
						</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="spawn_location_group_chances" />
						</form>
						<?php
						}
					}
					
					if( $eq2->CheckAccess(G_DEVELOPER) ) 
					{
					?>
					<form method="post" name="slgcForm|new" />
					<tr>
						<td><strong>new</strong></td>
						<td><input type="text" name="spawn_location_group_chances|group_id|new" value="" style="width:80px;" /></td>
						<td><input type="text" name="spawn_location_group_chances|percentage|new" value="100" style="width:80px;" /></td>
						<td>
							<input type="submit" name="cmd" value="Insert" class="submit" />
						</td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="spawn_location_group_chances" />
					</form>
					<?php 
					} 
					?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	
	<?php
}

function spawn_loot() 
{
	global $eq2, $spawns;
 ?>
	<br />
	<table width="1000" border="0" cellspacing="0" cellpadding="5">
		<tr>
			<td colspan="5">
				<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span>
			</td>
		</tr>
		<tr>
			<td>
				<fieldset>
				<legend>spawn_loot</legend>
				<table class="ContrastTable">
					<tr>
						<th>loot table</th>
						<th>name</th>
						<th></th>
					</tr>
					<tr>
						<form method="post" name="SpawnLootForm|New">
						<td>
							<input type="hidden" name="table_name" value="spawn_loot"/>
							<input type="hidden" name="spawn_loot|spawn_id" value="<?=$spawns->spawn_id?>"/>
							<?php $new_id = $eq2->GetNextIDX("loottable", "id"); ?>
							<input id="lt_txtSearch" type="text" autocomplete="off" onkeyup="LootTableLookupAJAX(true);" style="width:60px" name="spawn_loot|loottable_id" value="<?=$new_id?>"/>
							<div id="lt_search_suggest"></div>
						</td>
						<td><strong>New Entry</strong></td>
						<td align="center">
							<input type="submit" name="cmd" value="create"/>
						</td>
						</form>
					</tr>
					<?php
					$query = "SELECT sl.*,";
					$query .= "      lt.name";
					$query .= " FROM `" . ACTIVE_DB . "`.`spawn_loot` AS sl";
					$query .= " INNER JOIN `" . ACTIVE_DB . "`.`loottable` AS lt";
					$query .= " ON sl.loottable_id = lt.id";
					$query .= " WHERE spawn_id = " . $spawns->spawn_id;
					$rows = $eq2->RunQueryMulti($query);
					foreach ($rows as $data) : ?>
					<tr>
						<form method="post" name="SpawnLootForm|<?= $data['id']?>">
						<td>
							<input type="hidden" name="table_name" value="spawn_loot"/>
							<input type="hidden" name="orig_id" value="<?=$data['id']?>"/>
							<input type="hidden" name="spawn_loot|spawn_id" value="<?=$spawns->spawn_id?>"/>
							<input type="text" style="width:60px" name="spawn_loot|loottable_id" value="<?= $data['loottable_id']?>"/>
							<input type="hidden" name="orig_loottable_id" value="<?=$data['loottable_id']?>"/>
						</td>
						<td>
							<a href="server.php?page=loot_table&id=<?=$data['loottable_id']?>"><?= $data['name']?></a>
						</td>
						<td>
							<input type="submit" name="cmd" value="Update"/>
							<input type="submit" name="cmd" value="Delete"/>
						</td>
						</form>
					</tr>
					<?php endforeach; ?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
 <?php
}

function advanced() 
{
	global $eq2, $spawns;
	
	print("<p>&nbsp;May not be putting these settings here... stand by...</p>");
}

function spawn_scripts() 
{
	global $eq2, $spawns, $querystring;

	?>
	<br />
	<table width="1000" border="0" cellpadding="5">
		<tr>
			<td>
				<span class="heading">Editing: <?= $spawns->spawn_name ?> (<?= $spawns->spawn_id ?>)</span><br />&nbsp;
			</td>
		</tr>
		<tr>
			<td valign="top">
				<fieldset><legend>General</legend>
				<table>
					<tr>
						<td width="55">id</td>
						<td width="75">spawn_id</td>
						<td>spawnentry_id</td>
						<td>spawn_location_id</td>
						<td>lua_script</td>
						<td colspan="3">&nbsp;</td>
					</tr>
				<?php
				$results = $spawns->GetSpawnScriptEntries();
				
				if( is_array($results) )
				{
					foreach($results as $data)
					{
					?>
						<form method="post" name="multiForm|<?php print($data['id']); ?>" />
						<tr>
							<td>
								<input type="text" name="spawn_scripts|id" value="<?php print($data['id']) ?>" style="width:50px; background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
							<td>
								<input type="text" name="spawn_scripts|spawn_id" value="<?php print($data['spawn_id']) ?>" style="width:70px;" />
								<input type="hidden" name="orig_spawn_id" value="<?php print($data['spawn_id']) ?>" />
							</td>
							<td>
							<?php $entryOptions = $spawns->GetSpawnEntryOptions($data['spawnentry_id']) ?>
								<select name="spawn_scripts|spawnentry_id" class="combo" style="min-width:200px;" />
									<option value="0">---</option>
									<?= $entryOptions ?>
								</select>
								<input type="hidden" name="orig_spawnentry_id" value="<?php print($data['spawnentry_id']) ?>" />
							</td>
							<td>
							<?php $locationOptions = $spawns->GetSpawnLocationOptions($data['spawn_location_id']) ?>
								<select name="spawn_scripts|spawn_location_id" class="combo" style="min-width:200px;" >
									<option value="0">---</option>
									<?= $locationOptions ?>
								</select>
								<input type="hidden" name="orig_spawn_location_id" value="<?php print($data['spawn_location_id']) ?>" />
							</td>
							<td>
								<input type="text" name="spawn_scripts|lua_script" value="<?php print($data['lua_script']) ?>" style="font-size:11px; width:250px;" />
								<input type="hidden" name="orig_lua_script" value="<?php print($data['lua_script']) ?>" />
							</td>
							<td><input type="button" value="<?php (  $eq2->CheckAccess(G_DEVELOPER)  ) ? print("Edit") : print("View") ?>" class="submit" onclick="window.open('<?= $querystring ?>&tab=edit&sid=<?php print($data['id']) ?>', target='_self');" /></td>
							<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" class="submit" /><?php } ?></td>
							<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Delete" class="submit" /><?php } ?></td>
						</tr>
						<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
						<input type="hidden" name="table_name" value="spawn_scripts" />
						</form>
					<?php
					$new_script_name = $data['lua_script'];
					}
				}

				if( $eq2->CheckAccess(G_DEVELOPER) ) 
				{ 
					$pattern[0]="/ /";
					$pattern[1]="/'/";
					$pattern[2]="/`/";
					$lua_script = sprintf("SpawnScripts/%s/%s", $spawns->zone_name, $spawns->GetCleanSpawnScriptName());
				?>
					<form method="post" name="sdForm|new" />
					<tr align="center">
						<td><strong>new</strong></td>
						<td>
							<input type="text" name="spawn_scripts|spawn_id|new" value="0" onchange="document.getElementById('ssNew').disabled = false;" style="width:70px;" />
						</td>
						<td>
							<?php $newEntryOptions = $spawns->GetSpawnEntryOptions() ?>
							<select name="spawn_scripts|spawnentry_id|new" class="combo" style="min-width:200px;" onchange="document.getElementById('ssNew').disabled = false;" />
								<option value="0">---</option>
								<?= $newEntryOptions ?>
							</select>
						</td>
						<td>
							<?php $newLocationOptions = $spawns->GetSpawnLocationOptions() ?>
							<select name="spawn_scripts|spawn_location_id|new" class="combo" style="min-width:200px;" onchange="document.getElementById('ssNew').disabled = false;" >
								<option value="0">---</option>
								<?= $newLocationOptions ?>
							</select>
						</td>
						<td>
							<input type="text" name="spawn_scripts|lua_script|new" value="<?= $new_script_name ?? $lua_script ?>" style="font-size:11px; width:250px;" />
						</td>
						<td>
							<input type="submit" name="cmd" id="ssNew" value="Insert" class="submit" disabled="disabled" />
						</td>
					</tr>
						<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
						<input type="hidden" name="table_name" value="spawn_scripts" />
					</form>
				</table>
				<?php } ?>
				</fieldset>
			</td>
		</tr>
	</table>
<?php
}

function script_editor() 
{
	global $eq2, $spawns;

	$row 									= $spawns->GetSpawnScriptName($_GET['sid']);
	$script_path					= sprintf("SpawnScripts/%s", $spawns->zone_name);
	$script_full_name			= ( strlen($row['lua_script']) > 0 ) ? $row['lua_script'] : sprintf("%s/%s", $script_path, $spawns->GetCleanSpawnScriptName());
	?>
	<br />
	
	<?php 
	
		print($eq2->DisplayScriptEditor($script_full_name, $spawns->spawn_name, sprintf("%s|%s", $spawns->spawn_name, $spawns->spawn_id), "spawn_scripts")); 
}

function edit_merchant_list($id) 
{
	global $eq2, $objectName, $spawns;

	$table="merchants";
 ?>
	<table border="0" cellpadding="5">
		<tr>
			<td width="600" valign="top">
				<fieldset>
					<legend>merchants</legend>
					<form method="post" name="merchantForm|<?php echo $id; ?>">
						<table width="100%" cellpadding="0" border="0">
							<tr>
								<td colspan="3">
									<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
								</td>
							</tr>
							<tr>
								<td width="55">id</td>
								<td width="75">merchant_id</td>
								<td width="75">inventory_id</td>
								<td width="300">description</td>
								<td>&nbsp;</td>
							</tr>
		<?php
		$query = sprintf("select * from `%s`.merchants where merchant_id = %s",	ACTIVE_DB, $id);
		$data = $eq2->RunQuerySingle($query);
		$inventory_id = $data['inventory_id'];
		?>
				
							<tr>
								<td>
									<input type="text" name="merchants|id" value="<?php print($data['id']) ?>" style="width:45px; background-color:#ddd;" readonly />
									<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
								</td>
								<td>
									<input type="text" name="merchants|merchant_id" value="<?php print($data['merchant_id']) ?>" style="width:45px; background-color:#ddd;" readonly />
									<input type="hidden" name="orig_merchant_id" value="<?php print($data['merchant_id']) ?>" />
								</td>
								<td>
									<input type="text" name="merchants|inventory_id" value="<?php print($data['inventory_id']) ?>" style="width:45px;" />
									<input type="hidden" name="orig_inventory_id" value="<?php print($data['inventory_id']) ?>" />
								</td>
								<td>
									<input type="text" name="merchants|description" value="<?php print($data['description']) ?>" style="width:290px;" />
									<input type="hidden" name="orig_description" value="<?php print($data['description']) ?>" />
								</td>
								<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
							</tr>
							<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
							<input type="hidden" name="table_name" value="<?= $table ?>" />
						</table>
					</form>
				</fieldset>
			</td>
		</tr>
 <!--	</table> -->
 <!--	<br /> -->
 <!--	<table border="0" cellpadding="5"> -->
		<tr>
			<td valign="top">
				<fieldset>
					<legend>merchant_inventory</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td width="55">id</td>
							<td width="75">inventory_id</td>
							<td width="300">item_id</td>
							<td width="75">quantity</td>
							<td colspan="2">&nbsp;</td>
						</tr>
		<?php
		$table="merchant_inventory";
		$query=sprintf("select * from `%s`.%s where inventory_id = %s", ACTIVE_DB, $table, $inventory_id);
		$result = $eq2->RunQueryMulti($query);
		foreach ($result as $data) 
		{
		?>
						<form method="post" name="multiForm|merchant_inventory|<?php print($data['id']); ?>">
							<tr>
								<td>
									<input type="text" name="merchant_inventory|id" value="<?php print($data['id']) ?>" style="width:45px; background-color:#ddd;" readonly />
									<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
								</td>
								<td>
									<input type="text" name="merchant_inventory|inventory_id" value="<?php print($data['inventory_id']) ?>" style="width:45px;" />
									<input type="hidden" name="orig_inventory_id" value="<?php print($data['inventory_id']) ?>" />
								</td>
								<td>
									<input type="text" name="merchant_inventory|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;" />
									<span style="font-size:9px; vertical-align:middle; text-align:left"><?php print($eq2->getItemName($data['item_id'])); ?><?php print($eq2->GenerateItemHover($data['item_id'])) ?></span>
									<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
								</td>
								<td>
									<input type="text" name="merchant_inventory|quantity" value="<?php print($data['quantity']) ?>" style="width:45px;" />
									<input type="hidden" name="orig_quantity" value="<?php print($data['quantity']) ?>" />
								</td>
								<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
								<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" /><?php } ?></td>
							</tr>
							<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
							<input type="hidden" name="table_name" value="<?= $table ?>" />
						</form>
		<?php 
		}
		
		if( $eq2->CheckAccess(G_DEVELOPER) ) 
		{ 
		?>
						<form method="post" name="merchantsForm|new" />
							<tr>
								<td align="center"><strong>new</strong></td>
								<td>
									<input type="text" name="merchant_inventory|inventory_id|new" value="<?php print($inventory_id) ?>" style="width:45px; background-color:#ddd;" readonly />
								</td>
								<td>
									<input type="text" name="merchant_inventory|item_id|new" value="1" style="width:45px;" />
								</td>
								<td>
									<input type="text" name="merchant_inventory|quantity|new" value="65535" style="width:45px;" />
								</td>
								<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" /><?php } ?></td>
							</tr>
							<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
							<input type="hidden" name="table_name" value="<?= $table ?>" />
						</form>
		<?php } ?>
					</table>
				</fieldset>
			</td>
		</tr>
 <!--	</table> -->
 <!--	<br /> -->
 <!--	<table border="0" cellpadding="5"> -->
		<tr>
			<td width="1000" valign="top">
				<fieldset><legend>merchant_multipliers</legend>
				<table width="100%" cellpadding="0" border="0">
					<tr>
						<td width="50">id</td>
						<td width="100">merchant_id</td>
						<td width="120">low_buy_multiplier</td>
						<td width="125">high_buy_multiplier</td>
						<td width="125">low_sell_multiplier</td>
						<td width="125">high_sell_multiplier</td>
						<td width="135">multiplier_faction_id</td>
						<td width="100">min_faction</td>
						<td width="100">max_faction</td>
						<td width="120" colspan="2">&nbsp;</td>
					</tr>
		<?php
		$table="merchant_multipliers";
		$query=sprintf("select * from `%s`.%s where merchant_id = %s", ACTIVE_DB, $table, $id);
		$result = $eq2->RunQueryMulti($query);
		foreach ($result as $data) {
		?>
					<form method="post" name="multiForm|multipliers|<?php print($data['id']); ?>" />
					<tr>
						<td>
							<input type="text" name="merchant_multipliers|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|merchant_id" value="<?php print($data['merchant_id']) ?>" style="width:45px;" readonly />
							<input type="hidden" name="orig_merchant_id" value="<?php print($data['merchant_id']) ?>" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|low_buy_multiplier" value="<?php print($data['low_buy_multiplier']) ?>" style="width:45px;" />
							<input type="hidden" name="orig_low_buy_multiplier" value="<?php print($data['low_buy_multiplier']) ?>" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|high_buy_multiplier" value="<?php print($data['high_buy_multiplier']) ?>" style="width:45px;" />
							<input type="hidden" name="orig_high_buy_multiplier" value="<?php print($data['high_buy_multiplier']) ?>" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|low_sell_multiplier" value="<?php print($data['low_sell_multiplier']) ?>" style="width:45px;" />
							<input type="hidden" name="orig_low_sell_multiplier" value="<?php print($data['low_sell_multiplier']) ?>" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|high_sell_multiplier" value="<?php print($data['high_sell_multiplier']) ?>" style="width:45px;" />
							<input type="hidden" name="orig_high_sell_multiplier" value="<?php print($data['high_sell_multiplier']) ?>" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|multiplier_faction_id" value="<?php print($data['multiplier_faction_id']) ?>" style="width:45px;" />
							<input type="hidden" name="orig_multiplier_faction_id" value="<?php print($data['multiplier_faction_id']) ?>" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|min_faction" value="<?php print($data['min_faction']) ?>" style="width:45px;" />
							<input type="hidden" name="orig_min_faction" value="<?php print($data['min_faction']) ?>" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|max_faction" value="<?php print($data['max_faction']) ?>" style="width:45px;" />
							<input type="hidden" name="orig_max_faction" value="<?php print($data['max_faction']) ?>" />
						</td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
						<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?><input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" /><?php } ?></td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
					</form>
		<?php
		}
		?>
					<?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
					<form method="post" name="multipliersForm|new" />
					<tr>
						<td align="center"><strong>new</strong></td>
						<td>
							<input type="text" name="merchant_multipliers|merchant_id|new" value="<?php print($id) ?>" readonly style="width:45px;" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|low_buy_multiplier|new" value="1" style="width:45px;" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|high_buy_multiplier|new" value="10" style="width:45px;" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|low_sell_multiplier|new" value="1" style="width:45px;" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|high_sell_multiplier|new" value="10" style="width:45px;" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|multiplier_faction_id|new" value="0" style="width:45px;" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|min_faction|new" value="-20000" style="width:45px;" />
						</td>
						<td>
							<input type="text" name="merchant_multipliers|max_faction|new" value="50000" style="width:45px;" />
						</td>
						<td>
							<input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" />
						</td>
					</tr>
					<input type="hidden" name="object_id" value="<?= $spawns->spawn_name ?>|<?= $spawns->spawn_id ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
					</form>
		<?php } ?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
<?php
}
?>
