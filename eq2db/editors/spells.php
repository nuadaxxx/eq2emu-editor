<?php 

if (isset($_GET['id']) && !isset($_GET['action']) && !isset($_GET['tab'])) {
	$qs = "spells.php?" . $_SERVER['QUERY_STRING'] . "&tab=spells";
	header("Location: ".$qs);
	exit;
}

define('IN_EDITOR', true);
include("header.php");

if (!defined("M_SPELLS"))
    define("M_SPELLS", 0);

if (!$eq2->CheckAccess(M_SPELLS))
	die("Access denied!");

// instantiate the eq2Spells class, where all spell-related functons now live
// $spells->spell_id should be set after this instantiation, if a spell is currently selected $_GET['id']
include("../class/eq2.spells.php");
$spells = new eq2Spells();

?>
<div id="sub-menu1">
	<a href="spells.php">Spell Editor</a> | 
	<a href="spells.php?cl=history">Spells Changelog</a>
    <?php
	if ($eq2->CheckAccess(M_ADMIN)) {
		echo ' | <a href="spells.php?show=new">Create New Spell</a>';
	}
	?>
</div>
<?php
if( isset($_GET['cl']) ) 
{
	$eq2->DisplayChangeLogPicker($spells->eq2SpellTables);
	include("footer.php");
	exit;
}

/*
 * Process commands here
 */
switch(strtolower($_REQUEST['cmd'] ?? "")) 
{
	case "insert"			: $eq2->ProcessInserts(); break;
	case "update"			: 
		$spells->HandleCheckBoxes();
		if (isset($_POST['orig_description'])) {
			$_POST['orig_description'] = html_entity_decode($_POST['orig_description']);
		}
		$eq2->ProcessUpdates(); 
		break;
	case "delete"			: 
		if( $_POST['deleteTier'] )
			$spells->DeleteTier();
		else
			$eq2->ProcessDeletes(); 
		break;
	case "re-link"		: $spells->SetLUAScript(); break;
	case "rebuild"		: $spells->RebuildSpellLUAScript(); break;
	case "re-index"		: $spells->SaveReIndexedSpell(); break;
	case "clone"			: $spells->SaveClonedSpell(); break;
	case "hide"				: $spells->HideSpellID(); break;
	case "starting"		: $spells->InsertStartingSpell(); break;
	case "create":
		if ($_GET['show'] == "new") {
			if (!$eq2->CheckAccess(M_ADMIN)) die("lolno");
			$spells->CreateNewSpell();
		} else {
            $scriptFile = $eq2->SaveLUAScript();
            $replaceCount = 1;
            $scriptFile = str_replace(SCRIPT_PATH, "", $scriptFile, $replaceCount);
            $query = sprintf("UPDATE %s.spells SET `lua_script` = '%s' WHERE `id` = %s", ACTIVE_DB, $eq2->SQLEscape($scriptFile), $_GET['id']);
            $eq2->RunQuery(true, $query);
        }
		break;
	case "inserttier"	: $spells->InsertTier(); break;
	case "census"			: $spells->UpdateFromSOEData(); break;
	case "raw"				: $spells->ReSyncFromRawData(); break;
	case "save"				: 
		if( isset($_POST['script_name']) ) 
			$eq2->SaveLUAScript();
		else
			$eq2->ProcessUpdates(); 
		break;
	default: break;
}

$type = $_GET['type'] ?? NULL;
$classification = $_GET['classification'] ?? NULL;
$class = $_GET['class'] ?? NULL;

if (isset($_GET['show']) && $_GET['show'] == "new") {
    $spells->DisplayAddNewSpellPage();
    include("footer.php");
    exit;
}

?>
<form action="spells.php" id="frmSearch" method="post">
<table width="1000" border="0">
	<tr>
		<td class="filter_labels">Filters:</td>
		<td valign="top">
		<?php
			$filter_add = sprintf("%s%s", 
			isset($classification) ? "&classification=".$classification : "", 
			isset($class) ? "&class=".$class : "");

			$spellTypeOptions = "";
			foreach( $spells->eq2SOESpellTypes as $key=>$val )
				$spellTypeOptions .= sprintf('<option value="spells.php?type=%s%s%s">%s</option>', $val, ( $type == $val ) ? " selected" : "", $filter_add, ucfirst($val));
		?>
			<select name="spellType" onchange="dosub(this.options[this.selectedIndex].value)" class="combo">
			<option value="spells.php">Pick a Spell Type</option>
			<option value="spells.php?type=all<?php echo $filter_add; ?>"<?php if( $type =="all" ) echo " selected" ?>>All</option>
			<option value="spells.php?type=custom<?php echo $filter_add; ?>"<?php if( $type == "custom" ) echo " selected" ?>>Custom</option>
			<?php echo $spellTypeOptions; ?>
			</select>&nbsp;
		<?php 
		
		if( $type ) 
		{
			$spellClassificationOptions = "";
			foreach( $spells->eq2SOESpellClassifications as $soeCls )
				$spellClassificationOptions .= sprintf('<option value="spells.php?type=%s&classification=%s%s"%s>%s</option>', 
																							 $type, 
																							 $soeCls, 
																							 $class ? "&class=".$class : "",
																							 $classification == $soeCls ? " selected" : "", 
																							 ucfirst($soeCls));
		?>
			<select name="spellID" onchange="dosub(this.options[this.selectedIndex].value)" class="combo">
			<option value="spells.php?type=<?php echo $type; ?>">Pick a Classification</option>
			<?= $spellClassificationOptions ?>
			</select>&nbsp;
			<?php 
			$spellClassOptions = "";
			foreach( $spells->eq2SpellClasses as $key=>$val )
				$spellClassOptions .= sprintf('<option value="spells.php?type=%s&classification=%s&class=%s"%s>%s</option>', 
																			$type, 
																			$classification, 
																			$key, 
																			$class == $key ? " selected" : "", 
																			ucfirst($val));
			?>
			<select name="spellID" onchange="dosub(this.options[this.selectedIndex].value)" class="combo">
			<option value="spells.php?type=<?= $_GET['type'] ?>&classification=<?= $_GET['classification'] ?>">Pick a Class</option>
			<?= $spellClassOptions ?>
			</select> 
			<?php 
		}
		?>
			<a href="spells.php?<?php if (!empty($_SERVER['QUERY_STRING'])) { $_SERVER['QUERY_STRING']; } ?>">Reload Page</a>
		</td>
	</tr>
	<script>
	function SpellLookupAJAX() {
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			let str = escape(document.getElementById('txtSearch').value);
			if (str.length == 0) {
				let ss = document.getElementById('search_suggest')
				ss.innerHTML = '';
				return;
			}
			searchReq.open("GET", '../ajax/eq2Ajax.php?type=luSE&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest; 
			searchReq.send(null);
		}		
	}
	</script>
	
	<tr>
		<td class="filter_labels">Lookup:</td>
		<td colspan="3">
				<input type="text" id="txtSearch" name="txtSearch" alt="Search Criteria" onkeyup="SpellLookupAJAX();" autocomplete="off" class="box" value="<?= isset($_POST['txtSearch']) ? $_POST['txtSearch'] : "" ?>" /><!--onclick="this.value='';"-->
				<input type="submit" id="cmdSearch" name="cmdSearch" value="Search" alt="Run Search" class="submit" />
				<input type="button" value="Clear" class="submit" onclick="dosub('spells.php');" />
				<div id="search_suggest">
				</div>
		</td>
	</tr>
</table>
</form>
<?php

// once the filters are set, show the spell selector grid
if( ($_POST['cmdSearch'] ?? "") == 'Search' )
{
	$data = $spells->GetSpellsMatching();
	if (DisplaySpellSelectionGrid($data)) {
		include("footer.php");
		exit; // end page here, since actions requires none of the code below
	}
}

if( isset($type) && $spells->spell_id == 0 )
{
	$typeKey = array_keys($spells->eq2SOESpellTypes, $type);
	$select = sprintf("SELECT DISTINCT s.id, soe_spell_crc, name, description, level, type, given_by, is_active, is_aa, last_auto_update, icon, icon_backdrop FROM `".ACTIVE_DB."`.spells s LEFT JOIN `".ACTIVE_DB."`.spell_classes sc ON s.id = sc.spell_id LEFT JOIN `".ACTIVE_DB."`.spell_tiers st ON s.id = st.spell_id");
	
	switch($type)
	{
		case "abilities":
		case "tradeskills":
		case "pcinnates":
			if( isset($classification) && $classification != "all" ) 
				$sql = sprintf("%s WHERE type = %s AND given_by = '%s' GROUP BY soe_spell_crc ORDER BY level", $select, $typeKey[0], $classification);
			else
				$sql = sprintf("%s WHERE type = %s", $select, $typeKey[0]);
			
			break;
			
		case "spells":
		case "arts":
			if( $class && $class < 255 )
			{
				if( strlen($classification) > 0 && $classification != "all" ) 
					$sql = sprintf("%s WHERE type = %s AND is_active <= 1 AND s.id LIKE '%s____' AND given_by = '%s' GROUP BY soe_spell_crc ORDER BY level", $select, $typeKey[0], $class, $classification);
				else
					$sql = sprintf("%s WHERE type = %s AND is_active <= 1 AND s.id LIKE '%s____' GROUP BY soe_spell_crc ORDER BY level", $select, $typeKey[0], $class);
			}
			elseif( $classification == "unset" )
				$sql = sprintf("%s WHERE type = %s AND is_active <= 1 AND given_by = '%s' ORDER BY s.id", $select, $typeKey[0], $class, $classification);
			else
			{
				print("&nbsp;Must pick a class if chosing all/all");
				exit;
			}
			
			break;
			
		case "all":
			if( $class && $class < 255 )
			{
				if( strlen($classification) > 0 && $classification != "all" ) 
					$sql = sprintf("%s WHERE is_active <= 1 AND s.id LIKE '%s____' AND given_by = '%s' GROUP BY soe_spell_crc ORDER BY level", $select, $class, $classification);
				else
					$sql = sprintf("%s WHERE is_active <= 1 AND s.id LIKE '%s____' GROUP BY soe_spell_crc ORDER BY level", $select, $class);
			}
			elseif( $classification == "all" || $classification == "class" )
			{
				print("&nbsp;Must pick a class if chosing all/all");
				exit;
			}
			else
				$sql = sprintf("%s WHERE is_active <= 1 AND given_by = '%s' ORDER BY s.id", $select, $classification);
				
			break;
			
		default:
			$sql = sprintf("%s WHERE s.id LIKE '5___'", $select);
			break;
	}
	
	$eq2->SQLQuery = $sql;
	$spell_data = $eq2->RunQueryMulti();
	DisplaySpellSelectionGrid($spell_data);
}
elseif( isset($_GET['action']) )
{
	switch($_GET['action'])
	{
		case "reindex":
			ReIndexSpell();
			break;
			
		case "clone":
			CloneSpell();
			break;

		case "delete":
			DeleteSpell();
			break;
		
		case "split":
			SplitSpell();
			break;
			
		case "insert":
			InsertSpell();
			break;
	}
	
	include("footer.php");
	exit; // end page here, since actions requires none of the code below
}

if( $spells->spell_id > 0 ) // once a spell is picked, display all it's data for editing
{
	/* display editor(s) */	
	$querystring = sprintf("spells.php?type=%s", $_GET['type']);
	if( strlen($classification) > 0 )
		$querystring = sprintf("%s&classification=%s", $querystring, $_GET['classification']);
	if( isset($_GET['class']) )
		$querystring = sprintf("%s&class=%s", $querystring, $_GET['class']);
	$querystring .= sprintf("&id=%s", $spells->spell_id);
	?>
	<table id="sub-menu1">
		<tr>
			<td><strong>Navigation:</strong></td>
			<td>
				[ <a href="spells.php?type=<?= $_GET['type'] ?>">Back to Type</a> ]
				<?php if ( $classification ) { ?>&bull;&nbsp;[ <a href="spells.php?type=<?= $_GET['type'] ?>&classification=<?= $_GET['classification'] ?>">Back to Classification</a> ] <?php } ?>
				<?php if ( $class ) { ?>&bull;&nbsp;[ <a href="spells.php?type=all&classification=all&class=<?= $_GET['class'] ?>">Back to Class</a> ] <?php } ?>
			</td>
		</tr>
	</table>
	<?php
	// Build the Tab menu
	$current_tab_idx = ( isset($_GET['tab']) ) ? $_GET['tab'] : 'spells';
	$tab_array = array(
		'spells'								=> 'Spells',
		'spell_tiers'						=> 'Tiers',
		'spell_data'						=> 'Data',
		'spell_display_effects'	=> 'Effects',
		'spell_classes'					=> 'Classes',
		'spell_script'					=> 'Script'
	);
	
	if( $spells->is_trait ) 
		$tab_array = array_merge($tab_array, array('spell_trait' => 'Trait'));
		
	if( $spells->is_aa ) 
		$tab_array = array_merge($tab_array, array('spell_aa' => 'AA List'));
	
	$eq2->TabGenerator($current_tab_idx, $tab_array, $querystring, false);

	if (isset($_GET['tab'])) {
		switch($_GET['tab']) {
			case "spell_trait"					: spell_trait(); break;
			case "spell_aa"						: spell_aa(); break;
			case "spell_script"					: spell_script(); break;
			case "spell_data"					: spell_data(); break;
			case "spell_display_effects"		: spell_display_effects(); break;
			case "spell_classes"				: spell_classes(); break;			
			case "spell_tiers"					: spell_tiers(); break;
			case "spells"						: 
			default								: spells(); break;			
		}
	}
	else {
		spells();
	}
	include("footer.php");
	exit; // end of page

} 
else
{
	include("footer.php");
	exit; // end page here, since actions requires none of the code below
}


/* Functions */
function DisplaySpellSelectionGrid($spell_data)
{
	global $eq2, $spells;
	
	//print_r($spell_data);
	
	if( is_array($spell_data) )
	{
	?>
	<table width="100%" cellpadding="4" cellspacing="0" border="0">
		<tr bgcolor="#cccccc">
			<td width="50"><strong>Spell ID</strong></td>
			<td width="50" align="center"><strong>Icon</strong></td>
			<td width="120"><strong>Name</strong></td>
			<td><strong>Description</strong></td>
			<td width="70"><strong>Type</strong></td>
			<td width="100"><strong>Classification</strong></td>
			<td width="20"><strong>AA</strong></td>
			<td width="20"><strong>Level</strong></td>
			<td width="20"><strong>Active</strong></td>
			<td width="100" align="center"><strong>Last Auto Update</strong></td>
			<td width="25"><strong>Hide</strong></td>
		</tr>
		<?php 
		$i = 0;
		foreach($spell_data as $row)
		{
			$row_class = ( $i % 2 ) ? " bgcolor=\"#eeeeee\"" : "";
			//$description = ( strlen($row['description']) > 90 ) ? substr($row['description'],0,90).'...' : $row['description'];
			$description = $row['description']; // use above to truncate descriptions
			$spell_type = $spells->GetSOESpellType($row['type']); //( !empty($_GET['type']) ) ? $spells->eq2SOESpellTypes[$_GET['type']] : $spells->eq2SOESpellTypes[$row['type']];
			
			// having a problem switching classes once in the editor
			$querystring = sprintf("spells.php?type=%s", $spell_type);
			if( strlen($row['given_by']) > 0 )
				$querystring .= sprintf("&classification=%s", $row['given_by']);
			//$querystring = sprintf("spells.php?type=%s&classification=%s", $_GET['type'], $_GET['classification']);
			if( $row['id'] >= 10000 )
				$querystring .= sprintf("&class=%s", intval($row['id'] / 10000));
			$querystring .= "&tab=spells";
		?>
		<tr<?= $row_class ?> valign="top">
			<td>
				<a href="<?= $querystring ?>&id=<?= $row['id'] ?>"><?= $row['id'] ?></a>
			</td>
			<td align="center">
				<img src="<?php printf('eq2Icon.php?id=%s&type=spell&backdrop=%s', $row['icon'], $row['icon_backdrop']); ?>"/>
			</td>
			<td nowrap>
				<a href="http://census.daybreakgames.com/xml/get/eq2/spell/?crc=<?= $row['soe_spell_crc'] ?>&c:limit=100&c:sort=tier" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
				<a href="http://eq2.wikia.com/wiki/<?= $row['name'] ?>" target="_blank"><img src="../images/wikia.png" border="0" align="top" title="Wikia" alt="Wikia" height="20" /></a>
				<a href="http://eq2.zam.com/search.html?q=<?= $row['name'] ?>" target="_blank"><img src="../images/zam.png" border="0" align="top" title="Zam" alt="Zam" height="20" /></a>
				<?= $row['name'] ?>
			</td>
			<td><?= $description ?></td>
			<td><?= $spell_type ?></td>
			<td><?= $row['given_by'] ?></td>
			<td><?= ( $row['is_aa'] ) ? "Yes" : "No" ?></td>
			<td><?= $row['level'] ?></td>
			<td><img src="../images/<?= ( $row['is_active'] ) ? "nav_plain_green.png" : "nav_plain_red.png" ?>"></td>
			<td align="right" nowrap="nowrap"><?= date('Y/m/d h:i:s', $row['last_auto_update']) ?></td>
			<td>
				<form method="post">
					<input type="submit" name="cmd" value="Hide" style="font-size:9px; width:50px;" />
					<input type="hidden" name="id" value="<?= $row['id'] ?>" />
				</form>
			</td>
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
		print("&nbsp;No data found for set filters. Lookup the spell by name, as some classifications are unknown.");
}



function spells() 
{
	global $eq2, $spells, $querystring;

	$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spells WHERE id = %s", $spells->spell_id);
	$row = $eq2->RunQuerySingle();
	
	$enable_soe = true;
	$update_warning =  "ReadOnlyDetail"; // hack
	/*if( $row['soe_last_update'] > $row['last_auto_update'] )
	{
		$update_warning =  "ReadOnlyAlert";
		$enable_soe = true;
	}
	else
	{
		$update_warning =  "ReadOnlyDetail";
		$enable_soe = false;
	}*/
	
	?>
	<script language="javascript">
	function lockScriptName()
	{
	}
	</script>

	<div id="Editor">
	<form method="post" name="SpellForm" />
		<table class="SubPanel" cellspacing="0">
			<tr>
				<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
			</tr>
			<tr>
				<td class="Title" colspan="2">
					Editing: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
					<?php $spells->PrintOffsiteLinks(); ?>
				</td>
			</tr>
			<tr>
				<td valign="top">
					<table class="SectionMain" cellspacing="0">
						<tr>
							<td class="SectionTitle">General</td>
						</tr>
						<tr>
							<td class="SectionBody">
							<fieldset><legend>Info and Links</legend> 
							<table cellspacing="0">
								<tr>
									<td width="10" class="Label">id:</td>
									<td class="ReadOnlyDetail"><?= $row['id'] ?><input type="hidden" name="orig_id" value="<?= $row['id'] ?>" /></td>
									<td width="120" class="Label">soe_spell_crc:</td>
									<td class="ReadOnlyDetail"><?= ( $row['soe_spell_crc'] > 0 ) ? $row['soe_spell_crc'] : "N/a" ?><input type="hidden" name="soe_spell_crc" value="<?= $row['soe_spell_crc'] ?>" /></td>
									<td width="130" class="Label">SOE Last Update:</td>
									<td class="ReadOnlyDetail"><?= ( $row['soe_last_update'] > 0 ) ? date('M.d.Y h:i:s', $row['soe_last_update']) : "N/a" ?><input type="hidden" name="soe_last_update" value="<?= $row['soe_last_update'] ?>" /></td>
									<td width="130" class="Label">Last Auto Update:</td>
									<td class="<?= $update_warning ?>"><?= ( $row['last_auto_update'] > 0 ) ? date('M.d.Y h:i:s', $row['last_auto_update']) : "N/a" ?><input type="hidden" name="last_auto_update" value="<?= $row['last_auto_update'] ?>" /></td>
								</tr>
							</table>
							</fieldset>
							</td>
						</tr>
						<tr>
							<td class="SectionBody">
							<fieldset><legend>Text</legend> 
							<table cellspacing="0">
								<tr>
									<td width="120" class="Label">name:</td>
									<td class="Detail">
										<input type="text" name="spells|name" value="<?php print($row['name']); ?>" style="width:150px;" />
										<input type="hidden" name="orig_name" value="<?= $row['name'] ?>" />
									</td>
									<td width="120" class="Label">lua_script:</td>
									<td class="Detail">
									<?php
										switch($row['spell_book_type'])
										{
											case 3:
												?>
												<select name="spells|lua_script" style="width:405px;">
													<option value="">---</option>
													<option value="Spells/Tradeskills/DurabilityAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/DurabilityAdd.lua' ) echo " selected" ?>>DurabilityAdd.lua</option>
													<option value="Spells/Tradeskills/ProgressAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/ProgressAdd.lua' ) echo " selected" ?>>ProgressAdd.lua</option>
													<option value="Spells/Tradeskills/DurabilityAddProgressAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/DurabilityAddProgressAdd.lua' ) echo " selected" ?>>DurabilityAddProgressAdd.lua</option>
													<option value="Spells/Tradeskills/DurabilityModProgressAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/DurabilityModProgressAdd.lua' ) echo " selected" ?>>DurabilityModProgressAdd.lua</option>
													<option value="Spells/Tradeskills/DurabilityModSuccessAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/DurabilityModSuccessAdd.lua' ) echo " selected" ?>>DurabilityModSuccessAdd.lua</option>
													<option value="Spells/Tradeskills/ProgressModDurabilityAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/ProgressModDurabilityAdd.lua' ) echo " selected" ?>>ProgressModDurabilityAdd.lua</option>
													<option value="Spells/Tradeskills/ProgressModSuccessAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/ProgressModSuccessAdd.lua' ) echo " selected" ?>>ProgressModSuccessAdd.lua</option>
													<option value="Spells/Tradeskills/SuccessModDurabilityAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/SuccessModDurabilityAdd.lua' ) echo " selected" ?>>SuccessModDurabilityAdd.lua</option>
													<option value="Spells/Tradeskills/SuccessModProgressAdd.lua"<?php if ( $row['lua_script']=='Spells/Tradeskills/SuccessModProgressAdd.lua' ) echo " selected" ?>>SuccessModProgressAdd.lua</option>
												</select>
												<?php
												break;
												
											default:
												printf('<input type="text" name="spells|lua_script" value="%s" style="width:405px;" />', $eq2->CheckScriptExists($row['lua_script'])  ? $row['lua_script'] : ""); 
												break;
										}
									?>
									&nbsp;
									<input type="hidden" name="orig_lua_script" value="<?= $row['lua_script'] ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">description:</td>
									<td colspan="3" class="Detail">
										<textarea name="spells|description" style="font:13px Arial, Helvetica, sans-serif; width:99%"><?php echo ($row['description']); ?></textarea>
										<input type="hidden" name="orig_description" value="<?= htmlentities($row['description']) ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">effect_message:</td>
									<td colspan="3" class="Detail">
										<input type="text" name="spells|effect_message" value="<?php print($row['effect_message']); ?>" style="width:500px" />
										<input type="hidden" name="orig_effect_message" value="<?= $row['effect_message'] ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">success_message:</td>
									<td colspan="3" class="Detail">
										<input type="text" name="spells|success_message" value="<?php print($row['success_message']); ?>" style="width:500px" />
										<input type="hidden" name="orig_success_message" value="<?= $row['success_message'] ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">fade_message:</td>
									<td colspan="3" class="Detail">
										<input type="text" name="spells|fade_message" value="<?php print($row['fade_message']); ?>" style="width:500px" />
										<input type="hidden" name="orig_fade_message" value="<?= $row['fade_message'] ?>" />
									</td>
								</tr>
							</table>
							</fieldset>
						</tr>
						<tr>
							<td class="SectionBody">
							<fieldset style="height:232px"><legend>Settings</legend> 
							<table cellspacing="0">
								<tr>
									<td width="100" class="Label">class_skill:</td>
									<td class="Detail">
										<?php $options = $eq2->GetClassSkills($row['class_skill']); ?>
										<select name="spells|class_skill" style="width:150px;">
											<option value="0">---</option>
											<?php print($options); ?>
										</select>
										<input type="hidden" name="orig_class_skill" value="<?= $row['class_skill'] ?>" />
									</td>
									<td width="150" class="Label">mastery_skill:</td>
									<td class="Detail">
										<?php $options = $eq2->GetClassSkills($row['mastery_skill']); ?>
										<select name="spells|mastery_skill" style="width:150px;">
											<option value="0">---</option>
											<?php print($options); ?>
										</select>
										<input type="hidden" name="orig_mastery_skill" value="<?= $row['mastery_skill'] ?>" />
									</td>
									<td width="200" class="Label">min_class_skill_req:</td>
									<td class="Detail">
										<input type="text" name="spells|min_class_skill_req" value="<?php print($row['min_class_skill_req']); ?>" style="width:50px;" />
										<input type="hidden" name="orig_min_class_skill_req" value="<?= $row['min_class_skill_req'] ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">target_type:</td>
									<td class="Detail">
										<?php $options = $spells->GetSpellTargetTypes($row['target_type']) ?>
										<select name="spells|target_type" style="width:150px;">
											<?php print($options); ?>
										</select>
										<input type="hidden" name="orig_target_type" value="<?= $row['target_type'] ?>" />
									</td>
									<td class="Label">category:</td>
									<td class="Detail">
										<select name="spells|type" style="width:150px;">
											<option value="0"<?php if ( $row['type']==0 ) echo " selected" ?>>Spell</option>
											<option value="1"<?php if ( $row['type']==1 ) echo " selected" ?>>Combat Art</option>
											<option value="2"<?php if ( $row['type']==2 ) echo " selected" ?>>Ability</option>
											<option value="3"<?php if ( $row['type']==3 ) echo " selected" ?>>Crafting</option>
											<option value="4"<?php if ( $row['type']==4 ) echo " selected" ?>>Passive</option>
										</select>
										<input type="hidden" name="orig_type" value="<?= $row['type'] ?>" />
									</td>
									<td class="Label">deity:</td>
									<td class="Detail">
										<input type="text" name="spells|deity" value="<?php print($row['deity']); ?>" style="width:50px;" />
										<input type="hidden" name="orig_deity" value="<?= $row['deity'] ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">cast_type:</td>
									<td class="Detail">
										<select name="spells|cast_type" style="width:150px;">
											<option value="0"<?php if ( $row['cast_type']==0 ) echo " selected" ?>>Normal</option>
											<option value="1"<?php if ( $row['cast_type']==1 ) echo " selected" ?>>Toggle</option>
										</select>
										<input type="hidden" name="orig_cast_type" value="<?= $row['cast_type'] ?>" />
									</td>
									<td class="Label">spell_book_type:</td>
									<td class="Detail">
										<?php $options = $spells->GetSpellBookTypes($row['spell_book_type']); ?>
										<select name="spells|spell_book_type" style="width:150px;">
											<?php print($options); ?>
										</select>
										<input type="hidden" name="orig_spell_book_type" value="<?= $row['spell_book_type'] ?>" />
									</td>
									<td class="Label">linked_timer_id:</td>
									<td class="Detail">
										<input type="text" name="spells|linked_timer_id" value="<?php print($row['linked_timer_id']); ?>" style="width:50px;" />
										<input type="hidden" name="orig_linked_timer_id" value="<?= $row['linked_timer_id'] ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">det_type:</td>
									<td class="Detail">
										<select name="spells|det_type" style="width:150px;">
											<option value="0">---</option>
											<option value="1"<?php if ( $row['det_type']==1 ) echo " selected" ?>>Trauma</option>
											<option value="2"<?php if ( $row['det_type']==2 ) echo " selected" ?>>Arcane</option>
											<option value="3"<?php if ( $row['det_type']==3 ) echo " selected" ?>>Noxious</option>
											<option value="4"<?php if ( $row['det_type']==4 ) echo " selected" ?>>Elemental</option>
											<option value="5"<?php if ( $row['det_type']==5 ) echo " selected" ?>>Curse</option>
										</select>
										<input type="hidden" name="orig_det_type" value="<?= $row['det_type'] ?>" />
									</td>
									<td class="Label">control_effect_type:</td>
									<td style="padding-left:4px;">
										<select name="spells|control_effect_type" style="width:150px;">
											<option value="0">---</option>
											<option value="1"<?php if ( $row['control_effect_type']==1 ) echo " selected" ?>>Mez</option>
											<option value="2"<?php if ( $row['control_effect_type']==2 ) echo " selected" ?>>Stifle</option>
											<option value="3"<?php if ( $row['control_effect_type']==3 ) echo " selected" ?>>Daze</option>
											<option value="4"<?php if ( $row['control_effect_type']==4 ) echo " selected" ?>>Stun</option>
										</select>
										<input type="hidden" name="orig_control_effect_type" value="<?= $row['control_effect_type'] ?>" />
									</td>
									<td class="Label">casting_flags:</td>
									<td class="Detail">
										<input type="text" name="spells|casting_flags" value="<?php print($row['casting_flags']); ?>" style="width:50px;" />
										<input type="hidden" name="orig_casting_flags" value="<?= $row['casting_flags'] ?>" />
									</td>
									<td colspan="4">&nbsp;</td>
								</tr>
								<tr>
									<td class="Label">savage_bar:</td>
									<td class="Detail">
										<input type="text" name="spells|savage_bar" value="<?php print($row['savage_bar']); ?>" style="width:145px;" />
										<input type="hidden" name="orig_savage_bar" value="<?= $row['savage_bar'] ?>" />
									</td>
									<td class="Label">savage_bar_slot:</td>
									<td class="Detail">
										<input type="text" name="spells|savage_bar_slot" value="<?php print($row['savage_bar_slot']); ?>" style="width:145px;" />
										<input type="hidden" name="orig_savage_bar_slot" value="<?= $row['savage_bar_slot'] ?>" />
									</td>
									<td class="Label">spell_type:</td>
									<td class="Detail">
									<?php $spell_type = $row['spell_type'] ?>
										<select name="spells|spell_type" style="width:145px;">
										<?php $spells->PrintSpellTypeOptions($spell_type); ?>
										</select>
										<input type="hidden" name="orig_spell_type" value="<?php echo $spell_type; ?>" />
									</td>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td class="Label">type_group_spell_id:</td>
									<td class="Detail">
										<input type="text" name="spells|type_group_spell_id" value="<?php print($row['type_group_spell_id']); ?>" style="width:145px;"/>
										<input type="hidden" name="orig_type_group_spell_id" value="<?= $row['type_group_spell_id'] ?>" />
									</td>
								</tr>
							</table>
							</fieldset>
						</tr>
					</table>
				</td>
				<td valign="top">
					<table class="SectionToggles" cellspacing="0">
						<tr>
							<td class="SectionTitle">Misc</td>
						</tr>
						<tr>
							<td class="SectionBody">
								<fieldset>
								<legend>Appearance</legend> 
								<table width="100%" border="0">
									<tr>
										<td>
										<table>
										<tr>
											<td class="Label">icon</span></td>
											<td>
												<input type="text" name="spells|icon" value="<?php print($row['icon']); ?>" style="width:50px" />
												<input type="hidden" name="orig_icon" value="<?= $row['icon'] ?>" />
											</td>
										</tr>
										<tr>
											<td class="Label">icon_backdrop</span></td>
											<td>
												<input type="text" name="spells|icon_backdrop" value="<?php print($row['icon_backdrop']); ?>" style="width:50px" />&nbsp;
												<input type="hidden" name="orig_icon_backdrop" value="<?= $row['icon_backdrop'] ?>" />
											</td>
										</tr>
										</table>
										</td>
										<td>
											<img src="<?php printf('eq2Icon.php?id=%s&type=spell&backdrop=%s', $row['icon'], $row['icon_backdrop']); ?>"/>
										</td>
									</tr>
									<tr>
									<td>
									<table>
									<tr>
										<td class="Label">icon_heroic_op</span></td>
										<td>
											<input type="text" name="spells|icon_heroic_op" value="<?php print($row['icon_heroic_op']); ?>" style="width:50px" />&nbsp;
											<input type="hidden" name="orig_icon_heroic_op" value="<?= $row['icon_heroic_op'] ?>" />
										</td>
									</tr>
									<tr>
									<td align="left" class="Label">spell_visual:</td>
										<td>
											<input type="text" name="spells|spell_visual" value="<?php print($row['spell_visual']) ?>"  style="width:50px;" />
											<input type="hidden" name="orig_spell_visual" value="<?php print($row['spell_visual']) ?>" />
										</td>
									</tr>
									</table>
									</td>
									<td>
										<img src="<?php printf('eq2Icon.php?id=%s&type=ho', $row['icon_heroic_op']); ?>"/>
									</td>
									</tr>
									<tr>
										<td colspan="2" align="center">&nbsp;<input type="button" value="Lookup Effect" style="font-size:10px; width:100px;" onclick="javascript:window.open('spell_func.php?type=effects','luVS','width=1024,height=768,left=1,top=1,scrollbars=yes');" /></td>
									</tr>
								</table>
								</fieldset>
							</td>
						</tr>
						<tr>
							<td class="SectionBody" align="center">
								<?php $spells->DisplaySpellToggles($row); ?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?php 
			if($eq2->CheckAccess(G_DEVELOPER)) 
			{ 
			// for now, we'll leave it enabled always
				if( $enable_soe )
					$soe_button_text = 'title="Updates Spell data from Census Data feed."';
				else
					$soe_button_text = 'title="When SOE updates their spell data, this button will re-sync with their data." disabled';
			?>
			<tr>
				<table align="center">
				<td class="SectionBody" align="center" colspan="2" valign="top" height="40">
					<input type="submit" name="cmd" value="Update" class="submit" />&nbsp;
					<input type="button" value="Re-Index" class="submit" onclick="dosub('<?= $querystring ?>&action=reindex'); return false;" />&nbsp;
					<input type="button" value="Clone" class="submit" onclick="dosub('<?= $querystring ?>&action=clone'); return false;" />&nbsp;
					<?php if ($eq2->CheckAccess(G_SUPERADMIN)) : ?>
					<input type="button" value="Delete" class="submit" onclick="dosub('<?= $querystring ?>&action=delete'); return false;" />&nbsp;
					<?php endif; ?>
					<?php /* disabling for now */ if($eq2->CheckAccess(0)) : ?> 
					<input type="button" value="Insert" class="submit" onclick="dosub('<?= $querystring ?>&action=insert'); return false;" />&nbsp;
					<input type="button" value="Split" class="submit" onclick="dosub('<?= $querystring ?>&action=split'); return false;" />&nbsp;
					<?php endif; ?>
					<input type="submit" name="cmd" value="Census" class="submit" <?= $soe_button_text ?> />&nbsp;
					<input type="submit" name="cmd" value="RAW" class="submit" <?php if( !$spells->CheckRawSpellExists() ) print("disabled") ?> />&nbsp;
					<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>" />
					<input type="hidden" name="table_name" value="spells" />
				</td>
				</table>
			</tr>
			<?php 
			} 
			?>
		</table>
		</form>
	</div>
	<?php
}


function spell_tiers() 
{
	global $eq2, $spells, $link;

?>
<div id="Editor">
	<table class="SubPanel" cellspacing="0" border="0">
		<tr>
			<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
		</tr>
		<tr>
			<td class="Title" colspan="2">
				Editing: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
				<?php $spells->PrintOffsiteLinks(); ?>
			</td>
		</tr>
	<?php
	$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spell_tiers WHERE spell_id = %s ORDER BY tier;", $spells->spell_id);
	$rows = $eq2->RunQueryMulti();
	
	foreach($rows as $row)
	{
		$tier = $row['tier'];
		$tier_id = $row['id'];
		
		if( $spells->is_aa && $tier > 0 )
		{
			$tier_text = sprintf("AA Level %s Data", $tier);
			$tier_options = $spells->GetSpellAALevels($row['tier']); 
		}
		else
		{
			$tier_text = $spells->PrintTierName($tier);
			$tier_options = $spells->GetSpellTiers($row['tier']); 
		}
	?>
		<tr>
			<td valign="top" align="center">
				<table class="SectionMainFloat" cellspacing="0" border="0">
				<form method="post" name="multiForm|<?php print($row['id']); ?>" />
					<tr>
						<td colspan="8" class="SectionTitle"><?php print($tier_text); ?></td>
					</tr>
					<tr>
						<td width="120" class="Label">id:</td>
						<td width="70" class="Detail">
							<input type="text" name="spell_tiers|id" value="<?php print($row['id']) ?>"  style="width:50px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($row['id']) ?>" />
						</td>
						<td width="125" class="Label">spell_id:</td>
						<td width="70" class="Detail">
							<input type="text" name="spell_tiers|spell_id" value="<?php print($row['spell_id']) ?>"  style="width:50px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_spell_id" value="<?php print($row['spell_id']) ?>" />
						</td>
						<td align="right" width="100" class="Label"><?= ( $spells->is_aa ) ? "level" : "tier" ?>:</td>
						<td width="130" class="Detail">
							<select name="spell_tiers|tier" style="width:125px;">
							<?php print($tier_options); ?>
							</select>
							<input type="hidden" name="orig_tier" value="<?php print($row['tier']) ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td class="Label">hp_req:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|hp_req" value="<?php print($row['hp_req']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_hp_req" value="<?= $row['hp_req'] ?>" />
						</td>
						<td class="Label">hp_req_percent:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|hp_req_percent" value="<?php print($row['hp_req_percent']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_hp_req_percent" value="<?= $row['hp_req_percent'] ?>" />
						</td>
						<td class="Label">hp_upkeep:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|hp_upkeep" value="<?php print($row['hp_upkeep']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_hp_upkeep" value="<?= $row['hp_upkeep'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td class="Label">power_req:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|power_req" value="<?php print($row['power_req']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_power_req" value="<?= $row['power_req'] ?>" />
						</td>
						<td class="Label">power_req_percent:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|power_req_percent" value="<?php print($row['power_req_percent']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_power_req_percent" value="<?= $row['power_req_percent'] ?>" />
						</td>
						<td class="Label">power_upkeep:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|power_upkeep" value="<?php print($row['power_upkeep']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_power_upkeep" value="<?= $row['power_upkeep'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					
					<tr>
						<td class="Label">savagery_req:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|savagery_req" value="<?php print($row['savagery_req']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_savagery_req" value="<?= $row['savagery_req'] ?>" />
						</td>
						<td class="Label">savagery_req_percent:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|savagery_req_percent" value="<?php print($row['savagery_req_percent']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_savagery_req_percent" value="<?= $row['savagery_req_percent'] ?>" />
						</td>
						<td class="Label">savagery_upkeep:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|savagery_upkeep" value="<?php print($row['savagery_upkeep']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_savagery_upkeep" value="<?= $row['savagery_upkeep'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td class="Label">dissonance_req:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|dissonance_req" value="<?php print($row['dissonance_req']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_dissonance_req" value="<?= $row['dissonance_req'] ?>" />
						</td>
						<td class="Label">dissonance_req_percent:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|dissonance_req_percent" value="<?php print($row['dissonance_req_percent']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_dissonance_req_percent" value="<?= $row['dissonance_req_percent'] ?>" />
						</td>
						<td class="Label">dissonance_upkeep:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|dissonance_upkeep" value="<?php print($row['dissonance_upkeep']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_dissonance_upkeep" value="<?= $row['dissonance_upkeep'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
					
					<tr>
						<td class="Label">req_concentration:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|req_concentration" value="<?php print($row['req_concentration']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_req_concentration" value="<?= $row['req_concentration'] ?>" />
						</td>
						<td colspan="2">&nbsp;</td>
						<td class="Label">resistibility:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|resistibility" value="<?php print($row['resistibility']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_resistibility" value="<?= $row['resistibility'] ?>" />
						</td>
						<td class="Label">hit_bonus:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|hit_bonus" value="<?php print($row['hit_bonus']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_hit_bonus" value="<?= $row['hit_bonus'] ?>" />
						</td>
					</tr>
					<tr>
						<td class="Label" title="in 100ths of a second">cast_time:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|cast_time" value="<?php print($row['cast_time']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_cast_time" value="<?= $row['cast_time'] ?>" />
						</td>
						<td class="Label" title="in 10ths of a second">recovery:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|recovery" value="<?php print($row['recovery']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_recovery" value="<?= $row['recovery'] ?>" />
						</td>
						<td class="Label" title="in seconds">recast:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|recast" value="<?php print($row['recast']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_recast" value="<?= $row['recast'] ?>" />
						</td>
						<td class="Label" title="in 10ths of a second">call_frequency:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|call_frequency" value="<?php print($row['call_frequency']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_call_frequency" value="<?= $row['call_frequency'] ?>" />
						</td>
					</tr>
					<tr>
						<td class="Label">radius:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|radius" value="<?php print($row['radius']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_radius" value="<?= $row['radius'] ?>" />
						</td>
						<td class="Label">max_aoe_targets:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|max_aoe_targets" value="<?php print($row['max_aoe_targets']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_max_aoe_targets" value="<?= $row['max_aoe_targets'] ?>" />
						</td>
						<td class="Label">range:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|range" value="<?php print($row['range']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_range" value="<?= $row['range'] ?>" />
						</td>
						<td class="Label">min_range:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|min_range" value="<?php print($row['min_range']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_min_range" value="<?= $row['min_range'] ?>" />
						</td>
					</tr>
					<tr>
						<td class="Label" title="in 10ths of a second">duration1:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|duration1" value="<?php print($row['duration1']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_duration1" value="<?= $row['duration1'] ?>" />
						</td>
						<td class="Label" title="in 10ths of a second">duration2:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|duration2" value="<?php print($row['duration2']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_duration2" value="<?= $row['duration2'] ?>" />
						</td>
						<td class="Label">given_by:</td>
						<td class="Detail">
							<?php 
							$options = "";
							foreach($spells->eq2SOESpellClassifications as $classification) 
								$options .= sprintf('<option%s>%s</option>', ( $row['given_by'] == $classification ) ? " selected" : "", $classification);
							?>
							<select name="spell_tiers|given_by" style="width:150px;">
							<?php print($options); ?>
							</select>
							<input type="hidden" name="orig_given_by" value="<?= $row['given_by'] ?>" />
						</td>
						<td class="Label">unknown9:</td>
						<td class="Detail">
							<input type="text" name="spell_tiers|unknown9" value="<?php print($row['unknown9']); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_unknown9" value="<?= $row['unknown9'] ?>" />
						</td>
					</tr>
					<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
					<tr>
						<td colspan="8" align="center">
							<input type="submit" name="cmd" value="Update" class="submit" />&nbsp;
							<input type="submit" name="cmd" value="Delete" class="submit" />
							<input type="submit" name="cmd" value="Census" class="submit" />&nbsp;
							<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>|Tier:<?= $row['tier'] ?>" />
							<input type="hidden" name="table_name" value="spell_tiers" />
							<input type="hidden" name="soe_spell_crc" value="<?php print($spells->spell_crc) ?>" />
							<input type="hidden" name="tier" value="<?php print($row['tier']) ?>" />
							<input type="hidden" name="deleteTier" value="1" />
						</td>
					</tr>
					<?php } ?>
					</form>
				</table>
			</td>
		</tr>
	<?php
	}

	if ($eq2->CheckAccess(G_DEVELOPER) && !$spells->is_aa) {
        $next_tier = 1;

		foreach ($spells->eq2SpellTiers as $key => $val) {
			if ($key > $tier) {
				$next_tier = $key;
				break;
			}
		}
		
		if ($next_tier > 0 && $next_tier <= 12) {
			$tier_options = $spells->GetSpellTiers($next_tier); 
			
			/*
			 * Notes on the convoluted manner of inserting a new tier :|
			 * $tier_id = the PK of the record used as a base (so you don't have to re-type all tier data)
			 * $tier = the last known Tier ID
			 * The new tier ID is derived from the $tier_options selected
			 * $_POST['spell_id'] passes the spell ID
			 *
			 * Note that deleting 1 tier, or inserting 1 tier, inserts also a spell_data and spell_display_effects record(s)
			 */
		?>
		<tr>
			<td height="50" valign="bottom">Click <strong>Tier</strong> to add a new <em>tier</em> to this specific spell; tiers being Apprentice II, Adept I, or Master I, etc.</td>
		</tr>
		<form method="post" name="singleForm|new" />
		<tr>
			<td valign="top">
				<table class="SectionMain" cellspacing="0" border="0">
					<tr>
						<td colspan="8" class="SectionTitle">
							Add New Tier
						</td>
					</tr>
					<tr>
						<th width="100">id</th>
						<th width="200">spell_id</th>
						<th width="200">tier</th>
					</tr>
					<tr>
						<td align="center">
							<strong>new</strong>
							<input type="hidden" name="tier_id" value="<?php print($tier_id) ?>" />
							<input type="hidden" name="orig_tier" value="<?php print($tier) ?>" />
						</td>
						<td align="center">
							<input type="text" name="spell_id" value="<?php print($spells->spell_id) ?>" style="width:50px;  background-color:#ddd;" readonly />
						</td>
						<td align="center">
							<select name="new_tier" style="width:125px;">
							<?php print($tier_options); ?>
							</select>
						</td>
					</tr>
					<tr>
						<td align="center" colspan="3">
							<input type="submit" name="submit" value="Insert" class="submit" />
							<input type="hidden" name="cmd" value="inserttier" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>|Tier:<?= $next_tier ?>" />
		<input type="hidden" name="table_name" value="spell_tiers" />
		</form>
		<?php 
		} // end next_tier
	}
?>
	</table>
</div>
<?php
}
 
 
function spell_data() 
{
	global $eq2, $spells;	

	$tier 				= 0;
	$current_tier = 0;

	$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spell_data WHERE spell_id = %s ORDER BY tier;", $spells->spell_id);
	$rows = $eq2->RunQueryMulti();

	?>
	<div id="Editor">
	<table class="SubPanel" cellspacing="0" border="0">
		<tr>
			<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
		</tr>
		<tr>
			<td class="Title" colspan="2">
				Editing: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
				<?php $spells->PrintOffsiteLinks(); ?>
			</td>
		</tr>
		<?php
		if( is_array($rows) )
		{
			foreach($rows as $row)
			{
				$tier = $row['tier'];
				
				if( $spells->is_aa && $tier > 0 )
				{
					$tier_text = sprintf("AA Level %s Data", $tier);
					$tier_options = $spells->GetSpellAALevels($tier); 
				}
				else
				{
					$tier_text = $spells->PrintTierName($tier);
					$tier_options = $spells->GetSpellTiers($tier); 
				}
?>
		<tr>
			<td valign="top">
				<form method="post" name="spellDataForm|<?php print($row['id']); ?>" />
				<table class="SectionMain" cellspacing="0" border="0">
					<col width="50" />
					<col width="75" />
					<col width="100" />
					<col width="100" />
					<col width="75" />
					<col width="75" />
					<col width="300" />
					<?php
					if( $current_tier != $tier )
					{
						$current_tier = $tier;
					?>
					<tr>
						<td colspan="8" class="SectionTitle"><?php print($tier_text); ?></td>
					</tr>
					<tr bgcolor="#dddddd">
						<td><strong>id</strong></td>
						<td><strong>spell_id</strong></td>
						<td><strong><?= ( $spells->is_aa ) ? "level" : "tier" ?></strong></td>
						<td><strong>index_field</strong></td>
						<td><strong>value_type</strong></td>
						<td><strong>value</strong></td>
						<td>&nbsp;</td>
					</tr>
					<?php } ?>
					<tr>
						<td>
							<strong><?php print($row['id']); ?></strong>
							<input type="hidden" name="orig_id" value="<?php print($row['id']); ?>" />
						</td>
						<td>
							<input type="text" name="spell_data|spell_id" value="<?php print($row['spell_id']); ?>" style="width:50px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_spell_id" value="<?php print($row['spell_id']); ?>" />
						</td>
						<td>
							<?php $options = $spells->GetSpellTiers($tier) ?>
							<select name="spell_data|tier">
								<?php print($options); ?>
							</select>
							<input type="hidden" name="orig_tier" value="<?php print($tier); ?>" />
						</td>
						<td>
							<input type="text" name="spell_data|index_field" value="<?php print($row['index_field']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_index_field" value="<?php print($row['index_field']); ?>" />
						</td>
						<td>
							<select name="spell_data|value_type">
								<option value="">---</option>
								<option<?php if($row['value_type']=="BOOL") echo " selected" ?>>BOOL</option>
								<option<?php if($row['value_type']=="FLOAT") echo " selected" ?>>FLOAT</option>
								<option<?php if($row['value_type']=="INT") echo " selected" ?>>INT</option>
								<option<?php if($row['value_type']=="STRING") echo " selected" ?>>STRING</option>
							</select>
							<input type="hidden" name="orig_value_type" value="<?php print($row['value_type']); ?>" />
						</td>
						<td>
							<input type="text" name="spell_data|value" value="<?php print($row['value']); ?>" style="width:50px;" />
							<input type="hidden" name="orig_value" value="<?php print($row['value']); ?>" />
						</td>
						<td>
						<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
							<input type="submit" name="cmd" value="Update" class="submit" />
							<input type="submit" name="cmd" value="Delete" class="submit" />
						<?php } ?>
						&nbsp;
						</td>
					</tr>
				</table>
				<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>|Tier:<?php print($tier); ?>|Index:<?= $row['index_field'] ?>" />
				<input type="hidden" name="table_name" value="spell_data" />
				</form>
			</td>
		</tr>
		<?php
			}
		}
		
		if ($eq2->CheckAccess(G_DEVELOPER)) {
            $next_tier = 1;

			foreach ($spells->eq2SpellTiers as $key => $val) {
				if ($key > $tier) {
					$next_tier = $key;
					break;
				}
			}

			if ($next_tier > 0 && $next_tier <= 12) {
		?>
		<tr>
			<td height="50" valign="bottom">Click &quot;Insert&quot; to add a new <em>tier</em> to this specific spell; tiers being Apprentice II, Adept I, or Master I, etc.</td>
		</tr>
		<form method="post" name="singleForm|new" />
		<tr>
			<td valign="top">
				<table class="SectionMain" cellspacing="0" border="0">
					<col width="50" />
					<col width="75" />
					<col width="100" />
					<col width="100" />
					<col width="75" />
					<col width="75" />
					<col width="300" />
					<tr>
						<td colspan="8" class="SectionTitle">
							Add New Tier of Spell Data
						</td>
					</tr>
					<?php if (isset($headerRow)) { print($headerRow); } ?>
					<tr>
						<td><strong>new</strong></td>
						<td><input type="hidden" name="spell_data|spell_id|new" value="<?php print($spells->spell_id); ?>" />&nbsp;<?php print($spells->spell_id); ?></td>
						<td>
							<?php $options = $spells->GetSpellTiers($next_tier); ?>
							<select name="spell_data|tier|new">
								<?php print($options); ?>
							</select>
						</td>
						<td>
							<?php $next_id = $spells->GetNextSpellDataIndex("index_field", $spells->spell_id, $next_tier); ?>
							<input type="text" name="spell_data|index_field|new" value="<?php print($next_id); ?>" style="width:50px;" />
						</td>
						<td>
							<select name="spell_data|value_type|new">
								<option>---</option>
								<option>BOOL</option>
								<option>FLOAT</option>
								<option>INT</option>
								<option>STRING</option>
							</select>
						</td>
						<td><input type="text" name="spell_data|value|new" value="0" style="width:50px;" /></td>
						<td>
							<input type="submit" name="cmd" value="Insert" class="submit" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<input type="hidden" name="table_name" value="spell_data" />
		</form>
<?php
			} // end next tier
		}
?>
	</table>
	<br />
	<table class="SectionMain">
		<form method="post" name="parseData">
		<tr>
			<td align="center">
				<input type="button" value="Help" class="submit" onclick="javascript:window.open('help.php#spelldata','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />&nbsp;
				<input type="button" value="Parse Data" class="submit" title="Attempt to parse data entries from spell_display_effects" onclick="javascript:window.open('popup_functions.php?page=spells&type=data&id=<?= $spells->spell_id ?>','luData','width=1152,height=800,left=1,top=1,scrollbars=yes');" />
			</td>
		</tr>
		</form>
	</table>
</div>
<?php
}

function spell_display_effects() 
{
	global $eq2, $spells;	
	
	/*if( $_POST['cmd'] == "Parse")
		$eq2->ParseSpellEffects();*/

	$tier 				= 0;
	$current_tier = 0;
	
	$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spell_display_effects WHERE spell_id = %s ORDER BY tier, `index`;", $spells->spell_id);
	$rows = $eq2->RunQueryMulti();
	
	?>
	<div id="Editor">
	<table class="SubPanel" cellspacing="0" border="0">
		<tr>
			<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
		</tr>
		<tr>
			<td class="Title" colspan="2">
				Editing: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
				<?php $spells->PrintOffsiteLinks(); ?>
			</td>
		</tr>
		<?php
		if( is_array($rows) )
		{
			foreach($rows as $row)
			{
				$tier = $row['tier'];
				
				if( $spells->is_aa && $tier > 0 )
				{
					$tier_text = sprintf("AA Level %s Data", $tier);
					$tier_options = $spells->GetSpellAALevels($tier); 
				}
				else
				{
					$tier_text = $spells->PrintTierName($tier);
					$tier_options = $spells->GetSpellTiers($tier); 
				}
?>
		<tr>
			<td valign="top">
				<form method="post" name="spellDataForm|<?php print($row['id']); ?>" />
				<table class="SectionMainFloat" cellspacing="0" border="0">
					<col width="50" />
					<col width="65" />
					<col width="110" />
					<col width="70" />
					<col width="500" />
					<col width="50" />
					<col width="50" />
					<col width="200" />
					<?php
					if( $current_tier != $tier )
					{
						$current_tier = $tier;
					?>
					<tr>
						<td colspan="8" class="SectionTitle"><?php print($tier_text); ?></td>
					</tr>
					<tr bgcolor="#dddddd">
						<td><strong>id</strong></td>
						<td><strong>spell_id</strong></td>
						<td><strong>tier</strong></td>
						<td><strong>percent</strong></td>
						<td><strong>description</strong></td>
						<td><strong>bullet</strong></td>
						<td><strong>index</strong></td>
						<td>&nbsp;</td>
					</tr>
					<?php } ?>
					<tr>
						<td valign="top">
							<strong><?php print($row['id']); ?></strong>
							<input type="hidden" name="orig_id" value="<?php print($row['id']); ?>" />
						</td>
						<td valign="top">
							<input type="text" name="spell_display_effects|spell_id" value="<?php print($row['spell_id']); ?>" style="width:50px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_spell_id" value="<?php print($row['spell_id']); ?>" />
						</td>
						<td valign="top">
							<?php $options = $spells->GetSpellTiers($tier); // this one builds options based on the array ?>
							<select name="spell_display_effects|tier">
								<?php print($options); ?>
							</select>
							<input type="hidden" name="orig_tier" value="<?php print($tier); ?>" />
						</td>
						<td valign="top">
							<input type="text" name="spell_display_effects|percentage" value="<?php print($row['percentage']); ?>" style="width:60px;" />
							<input type="hidden" name="orig_percentage" value="<?php print($row['percentage']); ?>" />
						</td>
						<td valign="top">
							<textarea name="spell_display_effects|description" style="width:99%; height:40px;" wrap="on"><?php print($row['description']); ?></textarea>
							<input type="hidden" name="orig_description" value="<?php echo htmlentities($row['description']); ?>" />
						</td>
						<td valign="top">
							<input type="text" name="spell_display_effects|bullet" value="<?php print($row['bullet']); ?>" style="width:30px;" />
							<input type="hidden" name="orig_bullet" value="<?php print($row['bullet']); ?>" />
						</td>
						<td valign="top">
							<input type="text" name="spell_display_effects|index" value="<?php print($row['index']); ?>" style="width:30px;" />
							<input type="hidden" name="orig_index" value="<?php print($row['index']); ?>" />
						</td>
						<td valign="top">
						<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
							<input type="submit" name="cmd" value="Update" class="submit" />
							<input type="submit" name="cmd" value="Delete" class="submit" />
							<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>|Tier:<?php print($tier); ?>|Index:<?= $row['index'] ?>" />
							<input type="hidden" name="table_name" value="spell_display_effects" />
						<?php } ?>
						</td>
					</tr>
				</table>
				</form>
			</td>
		</tr>
		<?php
			}
		}
		
		if ($eq2->CheckAccess(G_DEVELOPER))  {
            $next_tier = 1;

			// this iterates the array, doesn't build options
			foreach ($spells->eq2SpellTiers as $key => $val) {
				if ($key > $tier) {
					$next_tier = $key;
					break;
				}
			}

			if ($next_tier > 0 && $next_tier <= 12) {
		?>
		<tr>
			<td height="50" valign="bottom">Click &quot;Insert&quot; to add a new <em>tier</em> to this specific spell; tiers being Apprentice II, Adept I, or Master I, etc.</td>
		</tr>
		<form method="post" name="singleForm|new" />
		<tr>
			<td valign="top">
				<table class="SectionMain" cellspacing="0" border="0">
					<col width="50" />
					<col width="65" />
					<col width="110" />
					<col width="70" />
					<col width="500" />
					<col width="50" />
					<col width="50" />
					<col width="200" />
					<tr>
						<td colspan="8" class="SectionTitle">
							Add New Tier of Spell Data
						</td>
					</tr>
					<tr bgcolor="#dddddd">
						<td><strong>id</strong></td>
						<td><strong>spell_id</strong></td>
						<td><strong>tier</strong></td>
						<td><strong>percent</strong></td>
						<td><strong>description</strong></td>
						<td><strong>bullet</strong></td>
						<td><strong>index</strong></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td valign="top">
							<strong>new</strong>
						</td>
						<td valign="top">
							<input type="text" name="spell_display_effects|spell_id" value="<?php print($spells->spell_id); ?>" style="width:50px; background-color:#ddd;" readonly />
						</td>
						<td valign="top">
							<?php $options = $spells->GetSpellTiers($next_tier); // this one builds options based on the array ?>
							<select name="spell_display_effects|tier">
								<?php print($options); ?>
							</select>
						</td>
						<td valign="top"><input type="text" name="spell_display_effects|percentage" value="100" style="width:60px;" /></td>
						<td valign="top"><textarea name="spell_display_effects|description" style="width:300px; height:30px;" wrap="on"></textarea></td>
						<td valign="top"><input type="text" name="spell_display_effects|bullet" value="0" style="width:30px;" /></td>
						<td valign="top"><input type="text" name="spell_display_effects|index" value="0" style="width:30px;" /></td>
						<td valign="top">
							<input type="submit" name="cmd" value="Insert" class="submit" />
							<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>" />
							<input type="hidden" name="table_name" value="spell_display_effects" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</form>
		<?php
			} // end next tier
		}
		?>
	</table>
</div>
<?php
}


function spell_classes() 
{
	global $eq2, $spells;	
	
?>
<div id="Editor">
	<table class="SubPanel" cellspacing="0" border="0">
		<tr>
			<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
		</tr>
		<tr>
			<td class="Title" colspan="2">
				Editing: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
				<?php $spells->PrintOffsiteLinks(); ?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<table class="SectionMain" cellspacing="0" border="0">
					<tr>
						<td colspan="7" class="SectionTitle">Classes that can use this spell</td>
					</tr>
					<tr bgcolor="#dddddd">
						<td width="50"><strong>id</strong></td>
						<td width="75"><strong>spell_id</strong></td>
						<td width="150"><strong>adventure_class_id</strong></td>
						<td width="150"><strong>tradeskill_class_id</strong></td>
						<td width="100"><strong>level</strong></td>
						<td>&nbsp;</td>
					</tr>
					<?php
					$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spell_classes WHERE spell_id = %s", $spells->spell_id);
					$rows = $eq2->RunQueryMulti();
					
					if( is_array($rows) )
					{
						foreach($rows as $row) 
						{
					?>
					<form method="post" name="sdForm|<?php print($row['id']); ?>" />
					<tr>
						<td>
							<strong><?php print($row['id']); ?></strong>
							<input type="hidden" name="orig_id" value="<?php print($row['id']); ?>" />
						</td>
						<td>
							<input type="text" name="spell_classes|spell_id" value="<?php print($row['spell_id']); ?>" style="width:50px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_spell_id" value="<?php print($row['spell_id']); ?>" />
						</td>
						<td>
							<?php $options = $eq2->GetClasses($row['adventure_class_id']); ?>
							<select name="spell_classes|adventure_class_id" style="width:145px;">
								<?php print($options); ?>
							</select>
							<input type="hidden" name="orig_adventure_class_id" value="<?php print($row['adventure_class_id']); ?>" />
						</td>
						<td>
							<select name="spell_classes|tradeskill_class_id" style="width:145px;">
								<option value="255"<?php if( $row['tradeskill_class_id']==255 ) print(" selected") ?>>N/A</option>
								<option value="1"<?php if( $row['tradeskill_class_id']==1 ) print(" selected") ?>>Tradeskiller</option>
							</select>
							<input type="hidden" name="orig_tradeskill_class_id" value="<?php print($row['tradeskill_class_id']); ?>" />
						</td>
						<td>
							<input type="text" name="spell_classes|level" value="<?php print($row['level']); ?>" style="width:40px;" />
							<input type="hidden" name="orig_level" value="<?php print($row['level']); ?>" />
						</td>
						<td>
						<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
							<input type="submit" name="cmd" value="Update" class="submit" />&nbsp;
							<input type="submit" name="cmd" value="Delete" class="submit" />&nbsp;
							<input type="submit" name="cmd" value="Starting" class="submit" />
							<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>|Class:<?= $eq2->eq2Classes[$row['adventure_class_id']] ?>" />
							<input type="hidden" name="table_name" value="spell_classes" />
						<?php } ?>
						</td>
					</tr>
					</form>
					<?php 
						} // end foreach
					} // end is_array
					?>
				</table>
			</td>
		</tr>
		<?php
		if($eq2->CheckAccess(G_DEVELOPER)) 
		{ 
		?>
		<tr>
			<td height="50" valign="bottom">Click &quot;Insert&quot; to add a new <em>class</em> to this specific spell.</td>
		</tr>
		<tr>
			<td valign="top">
				<form method="post" name="sdForm|new" />
				<table class="SectionMain" cellspacing="0" border="0">
					<tr>
						<td colspan="8" class="SectionTitle">
							Add New Class
						</td>
					</tr>
					<tr bgcolor="#dddddd">
						<td width="50"><strong>id</strong></td>
						<td width="75"><strong>spell_id</strong></td>
						<td width="150"><strong>adventure_class_id</strong></td>
						<td width="150"><strong>tradeskill_class_id</strong></td>
						<td width="100"><strong>level</strong></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td><strong>new</strong></td>
						<td>
							<strong><?php print($spells->spell_id); ?></strong>
							<input type="hidden" name="spell_classes|spell_id|new" value="<?php print($spells->spell_id); ?>" />
						</td>
						<td>
							<?php $options = $eq2->GetClasses(255); ?>
							<select name="spell_classes|adventure_class_id|new" style="width:145px;">
								<?php print($options); ?>
							</select>
						</td>
						<td>
							<select name="spell_classes|tradeskill_class_id|new" style="width:145px">
								<option value="255">N/A</option>
								<option value="1">Tradeskiller</option>
							</select>
						</td>
						<td><input type="text" name="spell_classes|level|new" value="1" style="width:40px;" /></td>
						<td><input type="submit" name="cmd" value="Insert" class="submit" /></td>
					</tr>
					<input type="hidden" name="table_name" value="spell_classes" />
					<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>" />
					</form>
					<?php } ?>
				</table>
				</fieldset>
			</td>
		</tr>
	</form>
	</table>
	<?php
}

function spell_script() 
{
	global $eq2, $spells;	

	$eq2->SQLQuery = sprintf("SELECT lua_script FROM `".ACTIVE_DB."`.spells WHERE id = %s", $spells->spell_id);
	$row = $eq2->RunQuerySingle();
	
	$clean_spell_name = $spells->GetCleanSpellScriptName($spells->spell_name);

	if( strlen($row['lua_script']) > 0 )
	{
		$script_full_name = $row['lua_script'];
	}
	else
	{
		if( $_GET['classification']=="race" )
		{
			$script_path = "Spells/Traditions";
			$script_full_name = $script_path . "/" . $clean_spell_name;
		}
		else
		{
			$script_path = "Spells/".$spells->GetSpellClassPath($_GET['class']);
			$script_full_name = $script_path . $clean_spell_name;
		}
	}
	
	$script_exists = $eq2->CheckScriptExists($script_full_name);
	$script_text = $eq2->LoadLUAScript($script_full_name);
	?>

<?php 

	$templates = array();
	$templates["Cast Templates"] = array("Generic", "Heal", "Damage", 
		"DoT", "AddBonus", "AddSkill", "AddControl", "Interrupt");
	$templates["Tick Templates"] = array("Tick");
	$templates["Remove Templates"] = array("Remove", "RemoveBonus", "RemoveSkill", "RemoveControl");
	$templates["Misc Templaces"] = array("Say", "Not Implemented");

	//Get the template text now based on the template names
	$finalTemplates = array();
	foreach ($templates as $category=>$list) {
		foreach ($list as $function) {
			$finalTemplates[$category][$function] = $spells->BuildLUAFunction($function);
		}
	}

	print($eq2->DisplayScriptEditor($script_full_name, $spells->spell_name,sprintf("%s|%s", $spells->spell_name, $spells->spell_id), "spells", $finalTemplates));

}

function spell_trait()
{
	global $eq2, $spells;

	$eq2->SQLQuery = sprintf("SELECT s.id as sid, st.tier as spell_tier, st2.* " .
														 "FROM `".ACTIVE_DB."`.spells s " .
														 "LEFT JOIN `".ACTIVE_DB."`.spell_tiers st ON s.id = st.spell_id " .
														 "LEFT JOIN `".ACTIVE_DB."`.spell_traits st2 ON s.id = st2.spell_id AND st.tier = st2.tier " .
														 "WHERE s.id = st.spell_id AND s.id = %s " .
														 "ORDER BY st.tier;", $spells->spell_id);
	$rows = $eq2->RunQueryMulti();
	?>
	<div id="Editor">
		<table class="SubPanel" cellspacing="0" border="0">
			<tr>
				<td class="Title" colspan="2">
					Editing: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
					<?php $spells->PrintOffsiteLinks(); ?>
				</td>
			</tr>
			<tr>
				<td>
					Below are the known tiers for the selected trait/spell, with or without known values. Fill in the values and click Update.<br />
					<br />
					<strong>IMPORTANT:</strong> Remember only tier 10 spells are "Class Training" traits, so don't make tier 1 - 9 out of anything but classtraining types!
				</td>
			</tr>
	<?php
	if( is_array($rows) )
	{
		$spells->spell_id = 0;
		
		foreach($rows as $row)
		{
			$tier = $row['spell_tier'];
			$spell_traits_pk = $row['id'] > 0 ? $row['id'] : 0;
			$crc = $spells->getSpellCRC($spells->spell_id);
		?>
		<tr>
			<td valign="top">
				<table class="SectionMain" cellspacing="0" border="0">
				<form method="post" name="multiForm|<?php print($spell_traits_pk); ?>" />
					<tr>
						<td colspan="6" class="SectionTitle">
							Traits - Tier <?php print($tier) ?> Data <?php if( $spell_traits_pk == 0 ) print('<span style="color:#c00">*NEW*</span>'); ?>
							<?php if( $tier == 1 ) { ?>
							<div style="float:right">
								<a href="http://census.daybreakgames.com/xml/get/eq2/spell/?crc=<?= $crc ?>&c:limit=100&c:sort=tier" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
								<a href="http://eq2.wikia.com/wiki/<?= $spells->getSpellName($spells->spell_id) ?>" target="_blank"><img src="../images/wikia.png" border="0" align="top" title="Wikia" alt="Wikia" height="20" /></a>
								<a href="http://eq2.zam.com/search.html?q=<?= $spells->getSpellName($spells->spell_id) ?>" target="_blank"><img src="../images/zam.png" border="0" align="top" title="Zam" alt="Zam" height="20" /></a>								
							</div>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<td width="120" class="Label">id:</td>
						<td width="70" class="Detail">
						<?php if( $spell_traits_pk > 0 ) { ?>
							<input type="text" name="spell_traits|id" value="<?php print($spell_traits_pk) ?>"  style="width:50px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($spell_traits_pk) ?>" />
						<?php } else { ?>
							<span style="color:#c00">*NEW*</span>
						<?php } ?>
						</td>
						<td width="125" class="Label">spell_id:</td>
						<td width="70" class="Detail">
							<input type="text" name="spell_traits|spell_id" value="<?php print($spells->spell_id) ?>"  style="width:60px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_spell_id" value="<?php print($spells->spell_id) ?>" />
						</td>
						<td align="right" width="100" class="Label">tier:</td>
						<td width="130" class="Detail">
							<select name="spell_traits|tier" style="width:125px;">
								<option value="0">---</option>
							<?= $spells->GetSpellTiers($tier) ?>
							</select>
							<input type="hidden" name="orig_tier" value="<?php print($tier) ?>" />
						</td>
					</tr>
					<tr>
						<td class="Label">class:</td>
						<td class="Detail">
							<select name="spell_traits|class_req" style="width:145px;">
								<option value="255">ALL</option>
								<?php 
                                    foreach ($eq2->eq2Classes as $key => $val) {
                                        if ($val != "ALL")
                                            sprintf("<option value='%s'%s>%s</option>", $key, ($key == $row['class_req']) ? " selected" : "", $val);
                                    }
								?>
							</select>
							<input type="hidden" name="orig_class_req" value="<?php print($row['class_req']); ?>" />
						</td>
						<td class="Label">race:</td>
						<td class="Detail">
							<select name="spell_traits|race_req" style="width:145px;">
								<option value="255">ALL</option>
								<?php 
                                    foreach ($eq2->eq2Races as $key=>$val) {
                                        if ($val != "ALL")
                                            sprintf("<option value='%s'%s>%s</option>", $key, ($key == $row['race_req']) ? " selected" : "", $val);
                                    }
								?>
							</select>
							<input type="hidden" name="orig_race_req" value="<?php print($row['race_req']); ?>" />
						</td>
						<td class="Label">level:</td>
						<td class="Detail">
							<input type="text" name="spell_traits|level" value="<?php print($row['level'] > 0 ? $row['level'] : 0); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_level" value="<?= $row['level'] ?>" />
						</td>
					</tr>
					<tr>
						<td class="Label">group:</td>
						<td class="Detail">
							<input type="text" name="spell_traits|group" value="<?php print($row['group'] > 0 ? $row['group'] : 0); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_group" value="<?= $row['group'] ?>" />
						</td>
						<td class="Label">isInate:</td>
						<td class="Detail">
							<input type="text" name="spell_traits|isInate" value="<?php print($row['isInate'] > 0 ? $row['isInate'] : 0); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_isInate" value="<?= $row['isInate'] ?>" />
						</td>
						<td class="Label">isFocusEffect:</td>
						<td class="Detail">
							<input type="text" name="spell_traits|isFocusEffect" value="<?php print($row['isFocusEffect'] > 0 ? $row['isFocusEffect'] : 0); ?>" style="width:50px" />&nbsp;
							<input type="hidden" name="orig_isFocusEffect" value="<?= $row['isFocusEffect'] ?>" />
						</td>
					</tr>
					<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
					<tr>
						<td colspan="6" align="center">
						<?php if( $row['id'] ) { ?>
							<input type="submit" name="cmd" value="Update" class="submit" />&nbsp;
						<?php } else {?>
							<input type="submit" name="cmd" value="Insert" class="submit" />&nbsp;
						<?php } ?>
							<input type="submit" name="cmd" value="Delete" class="submit" />
						</td>
					</tr>
					<?php } ?>
					<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>" />
					<input type="hidden" name="table_name" value="spell_traits" />
					</form>
				</table>
			</td>
		</tr>
		<?php
		} // end foreach
	} // end if
	?>
	</table>
<?php 
}

function spell_aa()
{
	global $eq2, $spells;
	
	$eq2->SQLQuery = sprintf("SELECT aa.*, s.soe_spell_crc FROM `".ACTIVE_DB."`.spells s, `".ACTIVE_DB."`.spell_aa_list aa, `".ACTIVE_DB."`.spell_aa_nodelist node WHERE aa.list_id = node.aa_list_fk AND node.spellcrc = s.soe_spell_crc AND s.id = %s", $spells->spell_id);
	$row = $eq2->RunQuerySingle();
	?>
	<div id="Editor">
		<table class="SubPanel" cellspacing="0">
			<tr>
				<td class="Title" colspan="2">
					Editing: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
					<?php $spells->PrintOffsiteLinks(); ?>
				</td>
			</tr>
			<tr>
				<td valign="top">
					<table class="SectionMainFloat" cellspacing="0">
						<tr>
							<td class="SectionTitle">Category</td>
						</tr>
						<tr>
							<td class="SectionBody">
								<form method="post" name="AASpell" />
								<table cellspacing="0">
									<tr>
										<td class="Label">list_id:</td>
										<td class="Detail">
											<input type="text" name="spell_aa_list|list_id" value="<?= $row['list_id'] ?>" />
											<input type="hidden" name="orig_list_id" value="<?= $row['list_id'] ?>" />
										</td>
									</tr>
									<tr>
										<td class="Label">name:</td>
										<td class="Detail">
											<input type="text" name="spell_aa_list|name" value="<?= $row['name'] ?>" />
											<input type="hidden" name="orig_name" value="<?= $row['name'] ?>" />
										</td>
									</tr>
									<tr>
										<td class="Label">maximumpoints:</td>
										<td class="Detail">
											<input type="text" name="spell_aa_list|maximumpoints" value="<?= $row['maximumpoints'] ?>" />
											<input type="hidden" name="orig_maximumpoints" value="<?= $row['maximumpoints'] ?>" />
										</td>
									</tr>
									<tr>
										<td class="Label">minimumpointsrequired:</td>
										<td class="Detail">
											<input type="text" name="spell_aa_list|minimumpointsrequired" value="<?= $row['minimumpointsrequired'] ?>" />
											<input type="hidden" name="orig_minimumpointsrequired" value="<?= $row['minimumpointsrequired'] ?>" />
										</td>
									</tr>
									<tr>
										<td class="Label">iswardertree:</td>
										<td class="Detail">
											<input type="text" name="spell_aa_list|iswardertree" value="<?= $row['iswardertree'] ?>" />
											<input type="hidden" name="orig_iswardertree" value="<?= $row['iswardertree'] ?>" />
										</td>
									</tr>
									<tr>
										<td class="Label">maxpoints:</td>
										<td class="Detail">
											<input type="text" name="spell_aa_list|maxpoints" value="<?= $row['maxpoints'] ?>" />
											<input type="hidden" name="orig_maxpoints" value="<?= $row['maxpoints'] ?>" />
										</td>
									</tr>
									<tr>
										<td class="Label">level:</td>
										<td class="Detail">
											<input type="text" name="spell_aa_list|level" value="<?= $row['level'] ?>" />
											<input type="hidden" name="orig_level" value="<?= $row['level'] ?>" />
										</td>
									</tr>
								</table>
								</form>
							</td>
						</tr>
					</table>
				</td>
				<td valign="top">
					<?php 
					$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spell_aa_nodelist WHERE spellcrc = %s", $row['soe_spell_crc']);
					$row = $eq2->RunQuerySingle();
					?>
					<table class="SectionToggles" cellspacing="0">
						<tr>
							<td class="SectionTitle" colspan="4">Node Data</td>
						</tr>
						<tr>
							<td class="Label">aa_list_fk:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|aa_list_fk" value="<?= $row['aa_list_fk'] ?>" readonly="readonly" />
								<input type="hidden" name="orig_aa_list_fk" value="<?= $row['aa_list_fk'] ?>" />
							</td>
							<td class="Label">spellcrc:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|spellcrc" value="<?= $row['spellcrc'] ?>" />
								<input type="hidden" name="orig_spellcrc" value="<?= $row['spellcrc'] ?>" />
							</td>
						</tr>
						<tr>
							<td class="Label">classification:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|classification" value="<?= $row['classification'] ?>" />
								<input type="hidden" name="orig_classification" value="<?= $row['classification'] ?>" />
							</td>
							<td class="Label">classificationpointsrequired:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|classificationpointsrequired" value="<?= $row['classificationpointsrequired'] ?>" />
								<input type="hidden" name="orig_classificationpointsrequired" value="<?= $row['classificationpointsrequired'] ?>" />
							</td>
						</tr>
						<tr>
							<td class="Label">firstparentid:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|firstparentid" value="<?= $row['firstparentid'] ?>" />
								<input type="hidden" name="orig_firstparentid" value="<?= $row['firstparentid'] ?>" />
							</td>
							<td class="Label">firstparentrequiredtier:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|firstparentrequiredtier" value="<?= $row['firstparentrequiredtier'] ?>" />
								<input type="hidden" name="orig_firstparentrequiredtier" value="<?= $row['firstparentrequiredtier'] ?>" />
							</td>
						</tr>
						<tr>
							<td class="Label">nodeid:</td>
							<td class="Detail" colspan="3">
								<input type="text" name="spell_aa_list|nodeid" value="<?= $row['nodeid'] ?>" />
								<input type="hidden" name="orig_nodeid" value="<?= $row['nodeid'] ?>" />
							</td>
						</tr>
						<tr>
							<td class="Label">pointspertier:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|pointspertier" value="<?= $row['pointspertier'] ?>" />
								<input type="hidden" name="orig_pointspertier" value="<?= $row['pointspertier'] ?>" />
							</td>
							<td class="Label">pointsspentintreetounlock:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|pointsspentintreetounlock" value="<?= $row['pointsspentintreetounlock'] ?>" />
								<input type="hidden" name="orig_pointsspentintreetounlock" value="<?= $row['pointsspentintreetounlock'] ?>" />
							</td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
							<td class="Label">pointsspentgloballytounlock:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|pointsspentgloballytounlock" value="<?= $row['pointsspentgloballytounlock'] ?>" />
								<input type="hidden" name="orig_pointsspentgloballytounlock" value="<?= $row['pointsspentgloballytounlock'] ?>" />
							</td>
						</tr>
						<tr>
							<td colspan="4">&nbsp;</td>
						</tr>
						<tr>
							<td class="Label">name:</td>
							<td class="Detail" colspan="3">
								<input type="text" name="spell_aa_list|name" value="<?= $row['name'] ?>" style="width:500px;" />
								<input type="hidden" name="orig_name" value="<?= $row['name'] ?>" />
							</td>
						</tr>
						<tr>
							<td class="Label" valign="top">description:</td>
							<td class="Detail" colspan="3">
								<textarea name="spell_aa_list|description" style="font:13px Arial, Helvetica, sans-serif; width:500px; height:80px"><?= $row['description'] ?></textarea>
								<input type="hidden" name="orig_description" value="<?= $row['description'] ?>" />
							</td>
						</tr>
						<tr>
							<td class="Label">title:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|title" value="<?= $row['title'] ?>" />
								<input type="hidden" name="orig_title" value="<?= $row['title'] ?>" />
							</td>
							<td class="Label">titlelevel:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|titlelevel" value="<?= $row['titlelevel'] ?>" />
								<input type="hidden" name="orig_titlelevel" value="<?= $row['titlelevel'] ?>" />
							</td>
						</tr>
						<tr>
							<td class="Label">maxtier:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|maxtier" value="<?= $row['maxtier'] ?>" />
								<input type="hidden" name="orig_maxtier" value="<?= $row['maxtier'] ?>" />
							</td>
							<td class="Label">minlevel:</td>
							<td class="Detail">
								<input type="text" name="spell_aa_list|minlevel" value="<?= $row['minlevel'] ?>" />
								<input type="hidden" name="orig_minlevel" value="<?= $row['minlevel'] ?>" />
							</td>
						</tr>
						<tr>
							<td class="Label">xcoord:</td>
							<td>
								<input type="text" name="spell_aa_list|xcoord" value="<?= $row['xcoord'] ?>" size="1" />&nbsp;
								<strong>ycoord:</strong> <input type="text" name="spell_aa_list|ycoord" value="<?= $row['ycoord'] ?>" size="1" />
								<input type="hidden" name="orig_xcoord" value="<?= $row['xcoord'] ?>" />
								<input type="hidden" name="orig_ycoord" value="<?= $row['ycoord'] ?>" />
							</td>
							<td class="Label">icon_id:</td>
							<td>
								<input type="text" name="spell_aa_list|icon_id" value="<?= $row['icon_id'] ?>" size="1" />&nbsp;
								<strong>icon_backdrop:</strong> <input type="text" name="spell_aa_list|icon_backdrop" value="<?= $row['icon_backdrop'] ?>" size="1" />
								<input type="hidden" name="orig_icon_id" value="<?= $row['icon_id'] ?>" />
								<input type="hidden" name="orig_icon_backdrop" value="<?= $row['icon_backdrop'] ?>" />
							</td>
						</tr>
						<tr>
							<td colspan="4">&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

function InsertSpell()
{
	global $eq2, $spells;
	
}

function ReIndexSpell()
{
	global $eq2, $spells;

    $rangeOptions = "";
    $classOptions = "";
    $classification = "";
    $querystring = "";
    $class = 0;
	
	foreach ($spells->eq2BaseSpellRanges as $key => $val)
		$rangeOptions .= sprintf('<option value="%s">%s</option>', $key, $val);
	foreach ($spells->eq2SpellClasses as $key => $val)
		$classOptions .= sprintf('<option value="%s">%s</option>', $key, $val);
	
	if (strlen($classification) > 0)
		$querystring = sprintf("%s&classification=%s", $querystring, $_GET['classification']);
	if ($class > 0)
		$querystring = sprintf("%s&class=%s", $querystring, $_GET['class']);
	$querystring .= sprintf("&id=%s", $spells->spell_id);

	$redir = sprintf("spells.php?type=%s&classification=%s&class=%s", $_GET['type'] ,$_GET['classification'], $_GET['class']);
	?>
	<script>
        function GetNextBaseSpellID() {
            if (searchReq.readyState == 4 || searchReq.readyState == 0) {
                let str = escape(document.getElementById('baseID').value);
                searchReq.open("GET", '../ajax/eq2Ajax.php?type=spell&search=' + str, true);
                searchReq.onreadystatechange = handleSearchSuggest2; 
                searchReq.send(null);
            }		
        }
        
        function GetNextClassSpellID() {
            if (searchReq.readyState == 4 || searchReq.readyState == 0) {
                let str = escape(document.getElementById('classID').value);
                searchReq.open("GET", '../ajax/eq2Ajax.php?type=class&search=' + str, true);
                searchReq.onreadystatechange = handleSearchSuggest2; 
                searchReq.send(null);
            }		
        }

        function handleSearchSuggest2() 
        {
            if (searchReq.readyState == 4) 
            {
                let ss = document.getElementById('search_suggest')
                ss.innerHTML = '';
                let str = searchReq.responseText.split("\n");
                for(i=0; i < str.length - 1; i++) 
                {
                    ss.value = str[i];
                    ss.style = "";
                    document.getElementById('submit').disabled = false;
                }
            }
        }
	</script>
	<table id="sub-menu1">
		<tr>
			<td><strong>Navigation:</strong></td>
			<td>
				[ <a href="<?= $redir ?>&id=<?= ( isset($_POST['redir']) ) ? $_POST['next_id'] : $spells->spell_id ?>">Back to Spell</a> ]
			</td>
		</tr>
	</table>
	<div id="Editor">
		<table class="SubPanel" cellspacing="0">
			<tr>
				<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
			</tr>
			<?php
			if( isset($_POST['redir'] ) )
			{
				$redir_to_new = sprintf("%s&id=%s", $_POST['redir'], $_POST['next_id']);
			?>
			<tr>
				<td id="EditorStatus" colspan="2" class="warning">Spell re-indexed sucessfully!<br>Click <a href="<?= $redir_to_new ?>" target="_self">here</a> to proceed to the new spell</td>
			</tr>
			<?php
			include("footer.php");
			exit; // all done once we reindex
			}
			?>
			<tr>
				<td class="Title" colspan="2">
					Re-Indexing: <?= $spells->spell_name ?>
					<?php $spells->PrintOffsiteLinks(); ?>
				</td>
			</tr>
			<tr>
				<td valign="top">
					<form method="post" name="SpellForm" />
					<table class="SectionMain" cellspacing="0">
						<tr>
							<td class="SectionTitle">
								Choose Destination Range
							</td>
						</tr>
						<tr>
							<td class="SectionBody">
							Instructions:<br />
							Pick either a pre-defined range of Common spells, or choose the class you wish to move this spell to.<br />
							A lookup will find the next available spells.id value, allowing you to overwrite should you need to.<br />
							<br />
							This script will:<br />
							<ul>
								<li>Set the new `id` for spells, tiers, effects, data and class records</li>
								<li>Change any starting spells or hotbars, and update character spell/hotbar id's (if the constraint fails)</li>
							</ul>
							<fieldset><legend>Pick One</legend> 
							<table cellspacing="0">
								<tr>
									<td width="200" class="Label">Pick destination range:</td>
									<td class="Detail">
										<select id="baseID" onchange="GetNextBaseSpellID();" style="width:200px;">
											<option>Pick Range</option>
											<?= $rangeOptions ?>
										</select>
									</td>
								</tr>
								<tr>
									<td colspan="2"><span style="font-weight:bold; font-size:15px;">- OR -</span></td>
								</tr>
								<tr>
									<td class="Label">Pick destination class:</td>
									<td class="Detail">
										<select id="classID" onchange="GetNextClassSpellID();" style="width:200px;">
											<option>Pick Class</option>
											<?= $classOptions ?>
										</select>
									</td>
								</tr>
								<tr>
									<td colspan="2">&nbsp;</td>
								</tr>
								<tr>
									<td class="Label">Next ID is:</td>
									<td class="Detail"><input type="text" name="next_id" id="search_suggest" value="Pick only one type above..." style="width:300px; color:#999; font-style:italic;" /></td>
								</tr>
							</table>
							</fieldset>
							</td>
						</tr>
						<?php 
						if($eq2->CheckAccess(G_SUPERADMIN)) // JA only for now
						{ 
						?>
						<tr>
							<td align="center" colspan="2">
								<input type="submit" id="submit" name="cmd" value="Re-Index" style="width:100px;" disabled />&nbsp;
								<input type="hidden" name="old_id" value="<?= $spells->spell_id ?>" />
								<input type="hidden" name="spell_name" value="<?= $spells->spell_name ?>" />
								<input type="hidden" name="redir" value="<?= $redir ?>" />
							</td>
						</tr>
						<?php 
						} 
						?>
					</form>
					</table>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

function CloneSpell()
{
	global $eq2, $spells;
	
	$rangeOptions = "";
	$classOptions = "";
	foreach($spells->eq2BaseSpellRanges as $key=>$val)
		$rangeOptions .= sprintf('<option value="%s">%s</option>', $key, $val);
	foreach($spells->eq2SpellClasses as $key=>$val)
		$classOptions .= sprintf('<option value="%s">%s</option>', $key, $val);
	
	$querystring = $_SERVER['QUERY_STRING'];
	$classification = $_GET['classification'] ?? "";
	if( strlen($classification) > 0 )
		$querystring = sprintf("%s&classification=%s", $querystring, $classification);
	$class = $_GET['class'] ?? "";
	if( $class > 0 )
		$querystring = sprintf("%s&class=%s", $querystring, $_GET['class']);
	$querystring .= sprintf("&id=%s", $spells->spell_id);

	$redir = sprintf("spells.php?type=%s&classification=%s&class=%s&tab=spells", $_GET['type'] ,$_GET['classification'], $_GET['class']);
	?>
	<script>
	function GetNextBaseSpellID() {
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			let str = escape(document.getElementById('baseID').value);
			searchReq.open("GET", '../ajax/eq2Ajax.php?type=spell&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest2; 
			searchReq.send(null);
		}		
	}
	
	function GetNextClassSpellID() {
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			let str = escape(document.getElementById('classID').value);
			searchReq.open("GET", '../ajax/eq2Ajax.php?type=class&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest2; 
			searchReq.send(null);
		}		
	}

	function handleSearchSuggest2() {
		if (searchReq.readyState == 4) 
		{
			let ss = document.getElementById('next_id_text');
			ss.value = searchReq.responseText.replaceAll(/[\n\r]/g, '');
		}
	}

	function VerifySpellIDInteger() {
		const v = document.getElementById('next_id_text').value;
		let e = document.getElementById('submit');
		e.disabled = v.length == 0 || v.match(/[^0-9]/g) != null;
	}
	</script>
	<table id="sub-menu1">
		<tr>
			<td><strong>Navigation:</strong></td>
			<td>
				[ <a href="<?= $redir ?>&id=<?= ( isset($_POST['redir']) ) ? $_POST['next_id'] : $spells->spell_id ?>">Back to Spell</a> ]
			</td>
		</tr>
	</table>
	<div id="Editor">
	<table class="SubPanel" cellspacing="0">
		<tr>
			<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
		</tr>
		<?php if( isset($_POST['redir'] ) ) :
			$redir_to_new = sprintf("%s&id=%s", $_POST['redir'], $_POST['next_id']);
			$redir_to_old = sprintf("%s&id=%s", $_POST['redir'], $_POST['old_id']);
		?>
		<tr>
			<td id="EditorStatus" colspan="2" class="warning">
				<p>Spell cloned sucessfully!</p>
				<p>Click <a href="<?= $redir_to_new ?>" target="_self">here</a> to proceed to the new spell</p>
				<p>or click <a href="<?= $redir_to_old ?>" target="_self">Back</a> to go back to the original spell</p>
			</td>
		</tr>
		<?php
		include("footer.php");
		exit; // all done once we reindex
		?>
		<?php endif; ?>
		<tr>
			<td class="Title" colspan="2">
				Cloning: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
				<?php $spells->PrintOffsiteLinks(); ?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<form method="post" name="SpellForm" />
				<table class="SectionMain" cellspacing="0">
					<tr>
						<td class="SectionTitle">
							Choose Destination Range
						</td>
					</tr>
					<tr>
						<td class="SectionBody">
						Instructions:<br />
						Pick either a pre-defined range of Common spells, or choose the class you wish to clone this spell to.<br />
						A lookup will find the next available spells.id value, allowing you to overwrite should you need to.<br />
						<br />
						This script will:<br />
						<ul>
							<li>Make an exact copy of the current spell to the new spell ID in it's new range</li>
							<li>You will need to adjust the spell_classes data to ensure the cloned spell can be used by the appropriate classes</li>
						</ul>
						<fieldset><legend>Pick One</legend> 
						<table cellspacing="0">
							<tr>
								<td width="200" class="Label">Pick destination range:</td>
								<td class="Detail">
									<select id="baseID" onchange="GetNextBaseSpellID();" style="width:200px;">
										<option>Pick Range</option>
										<?= $rangeOptions ?>
									</select>
								</td>
							</tr>
							<tr>
								<td colspan="2"><span style="font-weight:bold; font-size:15px;">- OR -</span></td>
							</tr>
							<tr>
								<td class="Label">Pick destination class:</td>
								<td class="Detail">
									<select id="classID" onchange="GetNextClassSpellID();" style="width:200px;">
										<option>Pick Class</option>
										<?= $classOptions ?>
									</select>
								</td>
							</tr>
							<tr>
								<td colspan="2">&nbsp;</td>
							</tr>
							<tr>
								<td class="Label">Next ID is:</td>
								<td class="Detail"><input type="text" name="next_id" id="next_id_text" autcomplete="off" value="Pick only one type above..." style="width:300px;font-style:italic;" oninput="VerifySpellIDInteger()"/></td>
							</tr>
						</table>
						</fieldset>
						</td>
					</tr>
					<?php 
					?>
					<tr>
						<td align="center" colspan="2">
							<input type="submit" id="submit" name="cmd" value="Clone" style="width:100px;" disabled />&nbsp;
							<input type="hidden" name="old_id" value="<?= $spells->spell_id ?>" />
							<input type="hidden" name="spell_name" value="<?= $spells->spell_name ?>" />
							<input type="hidden" name="redir" value="<?= $redir ?>" />
						</td>
					</tr>
					</form>
					<?php 
					?>
				</table>
			</td>
		</tr>
	</table>
	</div>
	<?php
}

/* This splits a spell's tiers and data from the main spell */
function SplitSpell()
{
	global $eq2, $spells;
	
}

function DeleteSpell()
{
	global $eq2, $spells;
	
	$redir = "spells.php";
	
	if( strlen($_GET['type']) > 0 )
		$redir = sprintf("spells.php?type=%s", $_GET['type']);
	if( strlen($_GET['classification']) > 0 )
		$redir = sprintf("spells.php?type=%s&classification=%s", $_GET['type'], $_GET['classification']);
	if( strlen($_GET['class']) > 0 )
		$redir = sprintf("spells.php?type=%s&classification=all&class=%s", $_GET['type'], $_GET['class']);
		
	if( $_GET['id'] > 0 ) 
		$redir_back = sprintf("%s&id=%s", $redir, $_GET['id']);
		
	?>
	<table id="sub-menu1">
		<tr>
			<td><strong>Navigation:</strong></td>
			<td>
				[ <a href="<?= ( isset($_POST['deleted_redir']) ) ? $redir : $redir_back ?>">Back to Spell</a> ]
			</td>
		</tr>
	</table>
	<div id="Editor">
	<table class="SubPanel" cellspacing="0">
		<tr>
			<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
		</tr>
		<?php
		if( isset($_POST['deleted_redir'] ) )
		{
			$spells->DeleteSpell(); // clean up, just in case
		?>
		<tr>
			<td class="Title" colspan="2">
				Deleted: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
			</td>
		</tr>
		<tr>
			<td valign="top">
				<table class="SectionMain" cellspacing="0">
					<tr>
						<td class="SectionTitle">&nbsp;</td>
					</tr>
					<tr>
						<td class="warning" align="center">
							<p>Spell deleted sucessfully!</p>
							<p>Click <a href="<?= $redir ?>" target="_self">here</a> to continue.</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
		include("footer.php");
		exit; // all done once we reindex
		}
		?>
		<tr>
			<td class="Title" colspan="2">
				Deleting: <?= $spells->spell_name ?> (<?= $spells->spell_id ?>)
				<?php $spells->PrintOffsiteLinks(); ?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<form method="post" name="SpellForm" />
				<table class="SectionMain" cellspacing="0">
					<tr>
						<td class="SectionTitle">Confirmation</td>
					</tr>
					<tr>
						<td class="warning" align="center">You are about to delete the spell<br />
							<?= $spells->spell_name ?> (<?= $spells->spell_id ?>)<br />
							<br />
							ARE YOU SURE?
						</td>
					</tr>
					<?php 
					if($eq2->CheckAccess(G_SUPERADMIN)) // JA only for now
					{ 
					?>
					<tr>
						<td align="center" colspan="2">
							<input type="submit" id="submit" name="cmd" value="Delete" style="width:100px;" />&nbsp;
							<input type="hidden" name="deleted_redir" value="<?= $redir ?>" />
							<input type="hidden" name="object_id" value="<?= $spells->spell_name ?>|<?= $spells->spell_id ?>" />
							<input type="hidden" name="orig_id" value="<?= $spells->spell_id ?>" />
							<input type="hidden" name="table_name" value="spells" />
						</td>
					</tr>
					</form>
					<?php 
					} 
					?>
				</table>
			</td>
		</tr>
	</table>
	</div>
	<?php
	
}

?>
