<?php 
define('IN_EDITOR', true);
if (($_POST['cmd'] ?? "") == "byName" && isset($_POST['itemname']) && $_POST['itemname'] != ($_GET['search'] ?? "")) {
	header("Location: ?search=".$_POST['itemname']);
	exit;
}
include("header.php"); 

if ( !$eq2->CheckAccess(M_ITEMS) )
	die("ACCESS: Denied!");

include("../class/eq2.items.php");

$eq2Items = new eq2Items();
$eq2Items->populateItemTypeArrays();

/* 
	TODO: 
		Hide editors from Items that do not need certain editors (like Spell-Type books, only need items and classes?)
		Show previews of Item data?
		
		Change groundspawn_items editor to get it's next-highest ID from spawns -> spawn_ground
		Add Reload Page
		
		Provide hover panel like SOE item examine
		From eq2wire-
		<div id="item_1263337103" class="itemd_surround itemd_hoverdiv">
			<div class="itemd_name">Heroic Hauberk of Entitlement 
			</div><div class="itemd_icon"><img src="http://census.daybreakgames.com/s:eq2wire/img/eq2/icons/1523/item/"></div>
			<div class="itemd_desc">To stay alive in a world that is constantly trying to make you dead is the greatest accomplishment.</div>
			<div class="itemd_tier itemd_tier_fabled">FABLED</div>
			<div class="itemd_flags">HEIRLOOM &nbsp;ATTUNEABLE &nbsp;</div>
			<div class="itemd_green">+185 sta &nbsp;+185 agi &nbsp;</div>
			<div class="itemd_green"></div>
			<div class="itemd_green">+13 Weapon Skills &nbsp;</div>
			<div class="itemd_blue">10.9%  Potency<br />21.7%  Crit Bonus<br />23.2%  Attack Speed<br />50.6%  Multi Attack Chance<br /></div>
			<div class="itemd_blue"></div>
			<div class="itemd_blue"></div>
			<div class="ui-helper-clearfix" style="min-height: 12px;"></div>
			<div style="width: 150px; float: left; color: white;">Chain Armor (Chest)</div>
			<div class="ui-helper-clearfix"></div>
			<div style="width: 80px; float: left;">Mitigation</div>
			<div style="width: 100px; float: left; color: white;">525</div>
			<div class="ui-helper-clearfix"></div>
			<div style="width: 80px; float: left;">Level</div>
			<div style="width: 150px; float: left;" class="itemd_green">93</div>
			<div class="ui-helper-clearfix"></div>
			<div class="itemd_green" style="margin-top: 4px;">All Scouts</div>
			<div class="itemd_adornslots">Adornment Slots:</div>
			<div style="font-weight: normal;">
				<span style="color: white;">White</span>, 
				<span style="color: yellow;">Yellow</span>, 
				<span style="color: white;">White</span>, 
				<span style="color: red;">Red</span>
			</div>
		</div>
*/
?>
<div id="sub-menu1">
	<a href="items.php?show=items">Item Editor</a> | 
	<a href="items.php?cl=history">Items Changelog</a>
	<?php
	if ($eq2->CheckAccess(M_ADMIN)) {
		echo ' | <a href="items.php?show=new">Create New Item</a>';
	}
	?>
</div>
<?php
if( isset($_GET['cl']) ) {
	?>
	<table>
		<tr>
			<td>
				<select name="table_name" onchange="dosub(this.options[this.selectedIndex].value)">
					<option>Pick a table</option>
					<option value="items.php?cl=history&t=items"<?php if( $_GET['t']=="items" ) echo " selected" ?>>items</option> 
				</select>
			</td>
			<?php 
			if( isset($_GET['t']) ) 
			{ 
				$table = ( isset($_GET['t']) ) ? $_GET['t'] : "";
				$editor_id = ( isset($_GET['c']) ) ? $_GET['c'] : 0;
			?>
			<td>Limit by user:&nbsp;
				<select name="char_id" onchange="dosub(this.options[this.selectedIndex].value)">
					<?= $eq2->getDBTeamSelector($table,$editor_id) ?>
				</select>
			</td>
			<?php } ?>
		</tr>
	</table>
	<?php
	if( !empty($table) ) {
		// TODO: Changelog per item, all data
		printf("<p><b>All changes to the `<i>%s</i>` table on record - copy/paste to your SQL query window to apply changes to your database.</b></p>",$table);
		printf("-- Changes to table: `%s`<br />",$table);
		$eq2->showChangeLog($table,$editor_id);
	}
	exit;
}

// do updates/deletes here
switch(strtolower($_POST['cmd'] ?? "")) {
	case "insert": 
		$eq2Items->PreInsert();
		$eq2->ProcessInserts();
		 break;
	case "update": 			
		$eq2Items->HandleCheckBoxes();
		$eq2Items->PreUpdate();
		$eq2->ProcessUpdates(); 
		break;
	case "delete": $eq2->ProcessDeletes(); break;
	case "create":
		if ($_GET['show'] == "new") {
			if (!$eq2->CheckAccess(M_ADMIN)) die("lolno");
			$eq2Items->CreateNewItem();
		}
		else {
			$scriptFile = $eq2->SaveLUAScript();
			$replaceCount = 1;
			$scriptFile = str_replace(SCRIPT_PATH, "", $scriptFile, $replaceCount);
			$query = sprintf("UPDATE %s.items SET `lua_script` = '%s' WHERE `id` = %s", ACTIVE_DB, $eq2->db->sql_escape($scriptFile), $_GET['id']);
			$eq2->RunQuery(true, $query);
		}
		break;
	case "save":
		$eq2->SaveLUAScript(); 
		break;
}

switch($_GET['show'] ?? "") 
{

	case "groundspawn" :
	
		if( isset($_GET['id']) ) {
			print("editing groundspawn_items list here");
		} else {
			$query = "SELECT DISTINCT collection_skill FROM spawn_ground ORDER BY collection_skill;";
			$result = $eq2->db->sql_query($query);
			while( $data = $eq2->db->sql_fetchrow($result) ) {
				$data_row[] .= $data['collection_skill'];
			}
			sort($data_row);
			?>
			<table>
				<tr>
					<td valign="top">
						<select name="merchantID" onchange="dosub(this.options[this.selectedIndex].value)" style="width:150px;">
						<option>Pick a Type</option>
						<?php
						foreach($data_row as $key)
						{
							$selected = $key == $_GET['type'] ? " selected" : "";
							printf("<option value=\"items.php?show=groundspawn&type=%s\"%s>%s</option>\n",$key,$selected,$key);
						}
						?>
						</select>
					</td>
				</tr>
			</table>
			<?php
			if( isset($_GET['type']) )
			{

				if( $_POST['cmd'] )
					foreach($_POST as $key) print_r($key);
								
				switch(strtolower($_POST['cmd'])) 
				{
					case "insert"					: $eq2->ProcessInserts(); break;
					case "update"					: $eq2->ProcessUpdates(); break;
					case "delete"					: $eq2->ProcessDeletes(); break;
				}

				/* display editor(s) */
				$show = $_GET['show'];
				$type = $_GET['type'];
				$link = sprintf("%s?show=%s&type=%s",$_SERVER['SCRIPT_NAME'],$show,$type);
				?>
				<div id="sub-menu1">
					<table cellspacing="0">
						<tr>
							<td align="right"><strong>Data:&nbsp;</strong></td>
							<td>
								[ <a href="<?php print($link) ?>">groundspawn_items</a> ] &bull;
								[ <a href="<?php print($link) ?>&page=groundspawn_bonus">groundspawn_bonus</a> ]
							</td>
						</tr> 	
					</table>
				</div>
				<?php
				switch($_GET['p']) {
					case "groundspawn_bonus"	: break;
					case "groundspawn_items"	: 
					default										: groundspawn_items(); break;
				}
				
			}
		}
		break;
		
	case "loot"				:	
		break;

	case "merchants"	:
		if( isset($_GET['id']) ) {
			print("editing merchant list here");
		} else {
			$query=sprintf("select id,name,sub_title,merchant_id from spawn where merchant_id > 0 order by name,sub_title;");
			if( !$result=$eq2->db->sql_query($query)) {
				$error = $eq2->db->sql_error();
				$message = "<p align=center>".$error['message']."<br>"."Error Code: ".$error['code']."</p><p>".$query."</p>";
				die($message);
			}
			while($data=$eq2->db->sql_fetchrow($result)) {
				$selected=( $data['id']==$_REQUEST['id'] ) ? " selected" : "";
				$listOptions .= sprintf("<option value=\"?show=merchants&id=%s\"%s>%s</option>\n",$data['id'],$selected,$data['name']);
			}
			?>
			<table>
				<tr>
					<td valign="top">
						<select name="merchantID" onchange="dosub(this.options[this.selectedIndex].value)" style="width:300px;">
						<option>Pick a Merchant</option>
						<?= $listOptions ?>
						</select>
					</td>
				</tr>
			</table>
			<?php 
		}
		break;

	case "new":
		$eq2Items->DisplayAddNewItemPage();
		include("footer.php");
		exit;
		break;
	case "items"			:
	default						:	
		if( isset($_GET['id']) ) {		
			/* display editor(s) */
			$id 	= $_GET['id'];
			if (!isset($_GET['type'])) {
				$_GET['type'] = $eq2Items->GetItemType($id);
			}
			$type = $_GET['type'];
			$link = sprintf("%s?show=items&type=%s",$_SERVER['SCRIPT_NAME'],$type);
			$objectName=$eq2->getItemName($id);
			?>
			<div id="sub-menu1">
			<?php $eq2->TabGenerator($_GET['tab'] ?? 'items', $eq2Items->GetTabArray(), $link); ?>
			</div>
			<?php
			switch( $_GET['tab'] ?? "") {
                case "item_classifications"						:	item_classifications($id); break;
				case "item_appearances"							:	item_appearances($id); break;
				case "item_details_armor"						:	item_details_armor($id); break;
				case "item_details_bag"							:	item_details_bag($id); break;
				case "item_details_bauble"					:	item_details_bauble($id); break;
				case "item_details_food"						:	item_details_food($id); break;
				case "item_details_house"						:	item_details_house($id); break;
				case "item_details_house_container"	:	item_details_house_container($id); break;
				case "item_details_range"						:	item_details_range($id); break;
				case "item_details_recipe"					:	item_details_recipe($id); break;
				case "item_details_recipe_items"		:	item_details_recipe_items($id); break;
				case "item_details_shield"					:	item_details_shield($id); break;
				case "item_details_skill"						:	item_details_skill($id); break;
				case "item_details_skills"					:	item_details_skills($id); break;
				case "item_details_thrown"					:	item_details_thrown($id); break;
				case "item_details_weapon"					:	item_details_weapon($id); break;
				case "item_effects"									:	item_effects($id); break;
				case "item_stats"										: item_stats($id); break;
				case "item_script"									: item_script($id); break;
				case "item_details_book": item_details_book($id); break;
				case "item_mod_strings": item_mod_strings($id); break;
				case "base":
				case "pvp": item_pvplink_redirect($id); break;
				default: items($id); break;													
			}	
			include("footer.php");
			exit; // end of page

		} else {
			print("&nbsp;Due to the number of Items available and their complex assignments, the Items Editor is laid out differently. Use the form below to narrow the Items list, then click the item to edit.<br />");
			if( ($_REQUEST['mode'] ?? "") == 'adv' )
			{
				$showSimple					= false;
				$showAdvanced 			= true;
			}
			else
			{
				$showSimple					= true;
				$showAdvanced 			= false;
			}
			$showSearchResults 	= false;

			if (isset($_GET['search'])) {
				$_POST['cmd'] = 'byName';
				$_POST['itemname'] = $_GET['search'];
			}
		
			if( isset($_POST['cmd']) ) { 
				switch($_POST['cmd']) {
					case "byName"			:
						$itemName = $eq2->SQLEscape($_POST['itemname']);
						if( strlen($_POST['itemname']) > 2 ) {
							$query="SELECT id, soe_item_id, name, item_type, tier, description, slots, classic_icon, icon, recommended_level as level, skill_id_req, crafted
											FROM `".ACTIVE_DB."`.items 
											WHERE bPvpDesc = 0 AND (`name` rlike '".$itemName."' OR id = '".$itemName."') 
											LIMIT 0,250";
							$totalRows=getItemSearchResults($query);
							if( $totalRows ) {
								$showSearchResults 	= true;
								$status="Search has discovered $totalRows results...";
							} else {
								$status="Your search returned 0 results. Try again.";
							}
						} else {
							$status="Search by Name must contain at least 3 characters!";
						}
						break;
						
					case "advSearch"	: 
					
						$showSimple		= false;

						$itemName 		= $eq2->SQLEscape($_POST['itemname']);
						$itemClass		= ( $_POST['c'] > -1 ) ? $_POST['c'] : "";
						$itemSlot		= ( $_POST['s'] > -1 ) ? $_POST['s'] : "";
						$itemLevel		= ( $_POST['l'] > -1 ) ? $_POST['l'] : "";
						$itemTier		= ( $_POST['ti'] > -1 ) ? $_POST['ti'] : "";
						$itemType		= ( $_POST['t'] > -1 ) ? $_POST['t'] : "";
						$maxResults		= $_POST['max'];
						$itemClassOnly	= ($_POST['co'] ?? 0) == 1;
						$itemSlotOnly	= ($_POST['so'] ?? 0) == 1;
						$itemLevelOnly	= ($_POST['lo'] ?? 0) == 1;
						$itemTierOnly	= ($_POST['to'] ?? 0) == 1;
						$itemHarvestOnly = ($_POST['harvestOnly'] ?? 0) == 1;
						
						//echo $itemClass.$itemRace.$itemSlot.$itemLevel.$itemZType;
						
						if( empty($itemName) && ($itemClass<0) && ($itemRace<0) && ($itemSlot<0) && ($itemLevel<0) && ($itemType<0) ) {
							$status="<span class=\"error_text\">Advanced Search must contain at least 1 parameter!</span>";
							break;
						} else if( strlen($itemName) > 0 && strlen($itemName) < 3 ) {
							$status="<span class=\"error_text\">Search must contain at least 3 characters!</span>";
							break;
						} else {

							$query="SELECT id, soe_item_id, name, item_type, tier, description, slots, classic_icon, icon, recommended_level as level, adventure_default_level as ad_level, skill_id_req, crafted
											FROM `".ACTIVE_DB."`.items i
											WHERE bPvpDesc = 0";  // has to be at least 1 param, so WHERE is assumed ok
							// WHERE builder
							$filters = array();
							if (strlen($itemName) > 0)
								$filters[] = "(name RLIKE '".$eq2->SQLEscape($itemName)."')";
							
							if( !empty($itemClass) )
								$filters[] = ( $itemClassOnly ) ? sprintf("adventure_classes = %s", 1 << $itemClass) : sprintf("adventure_classes & %s", 1 << $itemClass);
							
							if( !empty($itemSlot) )
								$filters[] = ( $itemSlotOnly ) ? sprintf("(slots = 1 << %s)", 1 << $itemSlot) : sprintf("slots & %s", $itemSlot);
							
							if( !empty($itemLevel) )
								$filters[] = ( $itemLevelOnly ) ? sprintf("(adventure_default_level = %s)", $itemLevel) : sprintf("(adventure_default_level >= %s)", $itemLevel);

							if( !empty($itemType) )
								$filters[] = "(item_type = '".$itemType."')";

							if (!empty($itemTier))
								$filters[] = ( $itemTierOnly ) ? sprintf("(tier = %s)", $itemTier) : sprintf("tier & %s", $itemTier);
							
							if ($itemHarvestOnly)
								$filters[] = "harvest";
							
							foreach ($filters as $f) {
								$query .= " AND " . $f;
							}

							$query.= (intval($maxResults) > 0) ? " LIMIT 0,".$maxResults : "";
							
							// fetch results
							$totalRows=getItemSearchResults($query);

							if( $totalRows ) {
								$showSearchResults 	= true;
								$status="Search has discovered $totalRows results...";
							} else {
								$status="Your search returned 0 results. Try again.";
							}
						}
						break;
				}
			} else {
				$showSearchResults = false;
			}
			?>
			<table width="100%" border="0" cellpadding="5">
				<?php if( $showSimple ) { ?>
				<tr>
					<td valign="top">
						<fieldset><legend>Search By Name</legend>
						<table width="60%" border="0" cellpadding="4" cellspacing="0" align="center">
						<form method="post" name="form1">
							<tr>
								<td width="20%" align="right">&nbsp;</td>
								<td>&nbsp;Find your item(s) by name or id using this option.</td>
							</tr>
							<tr>
							<script>
								function ItemAjaxSelect() {
									let e = document.getElementById("txtSearch");

									//Find the selected item type and id via regex
									const pat = /\(([a-zA-Z]+)\)\s\((\d+)\)$/;

									const m = e.value.match(pat);

									window.location.search = "?show=items&id=" + m[2] + "&type=" + m[1];
								}

								function ItemLookupAJAX() {
									if (searchReq.readyState == 4 || searchReq.readyState == 0) {
										let str = escape(document.getElementById('txtSearch').value);
										if (str.length < 3) {
											let ss = document.getElementById('search_suggest')
											ss.innerHTML = '';
											return;
										}

										searchReq.open("GET", '../ajax/eq2Ajax.php?type=luI&search=' + str, true);
										searchReq.onreadystatechange = handleSearchSuggest; 
										ajaxSelectCallback = ItemAjaxSelect;
										searchReq.send(null);
									}		
								}
							</script>
								<td align="right" valign="top"><strong>Lookup:</strong>&nbsp;</td>
								<td>
									&nbsp;<input type="text" id="txtSearch" name="itemname" autocomplete="off" size="40" value="<?= stripslashes($_POST['itemname'] ?? "") ?>" onkeyup="ItemLookupAJAX();" />&nbsp;
									<div id="search_suggest">
									</div>
									<br />
									&nbsp;<input type="submit" name="submit" value="Search" style="width:60px; font-size:9px;" />
									&nbsp;<input type="button" value="Clear" onclick="window.open('items.php?show=items', target='_self');" style="width:60px; font-size:9px;" />&nbsp;
									<span style="font-size:9px">(at least 3 characters required)</span>
									<br />
									&nbsp;<a href="?show=items&mode=adv">Advanced Search</a>
									<input type="hidden" name="cmd" value="byName" />
								</td>
							</tr>
						</form>
						</table>
						</fieldset>
					</td>
				</tr>
				<?php } ?>
				<?php if( $showAdvanced ) { ?>
				<tr>
					<td width="900">
						<fieldset><legend>Advanced Search</legend>
						<table width="60%" border="0" cellpadding="4" cellspacing="0" align="center">
						<form method="post" name="form2">
							<tr>
								<td align="right">Name:&nbsp;</td>
								<td colspan="2">
									<input type="text" name="itemname" size="40" value="<?= stripslashes($_POST['itemname'] ?? "") ?>" />&nbsp;
									<span style="font-size:9px">(at least 3 characters required)</span>
								</td>
							</tr>
							<tr>
								<td align="right" style="width:108px">Class:&nbsp;</td>
								<td>
									<select name="c">
										<option value="-1">Pick a Class</option>
										<?php 
										$c = $_REQUEST['c'] ?? null;
										foreach($eq2->eq2PlayableClasses as $key=>$val) {
											$selected = ( $c && $key == $c ) ? " selected" : "";
											printf("<option value=\"%d\"%s>%s</option>",$key,$selected,$val);
										}
										?>
									</select>
									<input type="checkbox" name="co" value="1" title="Search for selected class ONLY"<?= ( isset($_POST['co']) ) ? " checked" : ""; ?> />&nbsp;(only)
								</td>
								<td></td>
							</tr>
							<tr>
								<td align="right">Slot:&nbsp;</td>
								<td>
									<select name="s" style="width:108px">
										<option value="-1">Pick a Slot</option>
										<?php 
										$s = $_REQUEST['s'] ?? null;
										foreach($eq2->eq2EquipSlots as $key=>$val) {
											$selected = ($s && $key == $s) ? " selected" : "";
											printf("<option value=\"%s\"%s>%s</option>",$key,$selected,$val);
										}
										?>
									</select>
									<input type="checkbox" name="so" value="1" title="Search for selected slot ONLY"<?= ( isset($_POST['so']) ) ? " checked" : ""; ?> />&nbsp;(only)
								</td>
								<td></td>
							</tr>
							<tr>
								<td align="right">Level:&nbsp;</td>
								<td>
									<select name="l" style="width:108px">
										<option value="-1">Pick a Level</option>
										<?php
										$l = $_REQUEST['l'] ?? null;
										for( $i = 1; $i <= 80; $i++ ) {	
											$selected = ( $i == $l ) ? " selected" : "";
											printf("<option%s>%d</option>\r\n",$selected,$i);
										}
										?>
									</select>
									<input type="checkbox" name="lo" value="1" title="Search for selected level ONLY"<?= ( isset($_POST['lo']) ) ? " checked" : ""; ?> />&nbsp;(only)
								</td>
								<td></td>
							</tr>

							<tr>
								<td align="right">Tier:&nbsp;</td>
								<td>
									<select name="ti" style="width:108px">
										<option value="-1">Pick a Level</option>
										<?php
										$l = $_REQUEST['ti'] ?? null;
										for( $i = 1; $i <= 12; $i++ ) {	
											$selected = ( $i == $ti ) ? " selected" : "";
											printf("<option%s>%d</option>\r\n",$selected,$i);
										}
										?>
									</select>
									<input type="checkbox" name="lo" value="1" title="Search for selected level ONLY"<?= ( isset($_POST['lo']) ) ? " checked" : ""; ?> />&nbsp;(only)
								</td>
								<td></td>
							</tr>


							<tr>
								<td align="right">IsHarvest:&nbsp;</td>
								<td>
								<input type="checkbox" name="harvestOnly" value="1"<?= ( isset($_POST['harvestOnly']) ) ? " checked" : ""; ?> />
								</td>
								<td></td>
							</tr>
							<tr>
								<td align="right">Item Type:&nbsp;</td>
								<td colspan="2">
									<select name="t">
										<option value="-1">Pick a Type</option>
									<?php									
									$query=sprintf("select distinct item_type from %s.items order by item_type", ACTIVE_DB);
									$result=$eq2->db->sql_query($query);
									$t = $_POST['t'] ?? null;
									while($data=$eq2->db->sql_fetchrow($result)) {
										if( strlen($data['item_type'])>1 ) {
											$selected=( $t && $data['item_type'] == $t ) ? " selected" : "";
											printf("<option%s>%s</option>\r\n",$selected,$data['item_type']);
										}
									}
									?>
									</select>
								</td>
							</tr>
							<tr>
								<td align="right">Max Results:&nbsp;</td>
								<td colspan="2">
									<select name="max">
										<?php $max = $_POST['max'] ?? 0 ?>
										<option<?php if( $max==100 ) print(" selected") ?>>100</option>
										<option<?php if( $max==250 ) print(" selected") ?>>250</option>
										<option<?php if( $max==500 ) print(" selected") ?>>500</option>
										<option<?php if( $max==1000 ) print(" selected") ?>>1000</option>
										<?php if ($eq2->CheckAccess(G_SUPERADMIN)) { ?><option<?php if( $max=='All Results' ) print(" selected") ?>>All Results</option><?php } ?>
									</select>
								</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td colspan="2">
									<input name="submit" type="submit" value="Search" style="width:60px; font-size:9px;" />&nbsp;
									<input type="button" value="Clear" onclick="javascript:window.open('items.php?show=items&mode=adv', target='_self');" style="width:60px; font-size:9px;" />
									<br /><a href="?show=items">Simple Search</a>
									<input type="hidden" name="cmd" value="advSearch" />
								</td>
							</tr>
						</form>
						</table>
						</fieldset>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<td align="center"><span id="EditorStatus" style="color:#FF0000; font-size:14px; font-weight:bold"></span></td>
				</tr>
				<?php if( $showSearchResults ) {  ?>
				<tr>
					<td valign="top">
						<fieldset><legend>Results</legend>
						<table width="100%" cellpadding="4" cellspacing="0" border="1">
							<tr align="center">
								<td style="border-bottom:1px solid #666666" width="5%"><strong>ID</strong></td>
								<td style="border-bottom:1px solid #666666" width="5%"><strong>Icon</strong></td>
								<td style="border-bottom:1px solid #666666" width="25%" align="left"><strong>Name</strong></td>
								<td style="border-bottom:1px solid #666666" width="5%"><strong>Slot</strong></td>
								<td style="border-bottom:1px solid #666666" width="1%"><strong>Lvl</strong></td>
								<td style="border-bottom:1px solid #666666" width="1%"><strong>Adv Def Lvl</strong></td>
								<td style="border-bottom:1px solid #666666" width="1%"><strong>Tier</strong></td>
								<td style="border-bottom:1px solid #666666" width="3%"><strong>Type</strong></td>
								<td style="border-bottom:1px solid #666666" width="5%"><strong>Skill</strong></td>
								<td style="border-bottom:1px solid #666666" width="50%"><strong>Stats</strong></td>
							</tr>
						<?php
							print($itemResults);
						?>
						</table>
						</fieldset>
					</td>
				</tr>
				<?php } ?>
			</table>
			<?php
		}
		break;
}


/* 
	Functions:
*/
function item_details_bauble($id) {
	global $eq2,$objectName,$link;

	$table="item_details_bauble";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) :
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>Bauble</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="4">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td width="25%" align="right">id:</td>
							<td>
								<input type="text" name="item_details_bauble|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_bauble|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">cast:</td>
							<td>
								<input type="text" name="item_details_bauble|cast" value="<?php print($data['cast']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_cast" value="<?php print($data['cast']) ?>" />
							</td>
							<td align="right">recast:</td>
							<td>
								<input type="text" name="item_details_bauble|recast" value="<?php print($data['recast']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_recast" value="<?php print($data['recast']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">effect_radius:</td>
							<td>
								<input type="text" name="item_details_bauble|effect_radius" value="<?php print($data['effect_radius']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_effect_radius" value="<?php print($data['effect_radius']) ?>" />
							</td>
							<td align="right">duration:</td>
							<td>
								<input type="text" name="item_details_bauble|duration" value="<?php print($data['duration']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_duration" value="<?php print($data['duration']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">equipped_optional:</td>
							<td>
								<input type="text" name="item_details_bauble|display_slot_optional" value="<?php print($data['display_slot_optional']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_display_slot_optional" value="<?php print($data['display_slot_optional']) ?>" />
							</td>
							<td align="right">display_cast_time:</td>
							<td>
								<input type="text" name="item_details_bauble|display_cast_time" value="<?php print($data['display_cast_time']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_display_cast_time" value="<?php print($data['display_cast_time']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">display_bauble_type:</td>
							<td>
								<input type="text" name="item_details_bauble|display_bauble_type" value="<?php print($data['display_bauble_type']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_display_bauble_type" value="<?php print($data['display_bauble_type']) ?>" />
							</td>
							<td align="right">until_cancelled:</td>
							<td>
								<input type="text" name="item_details_bauble|display_until_cancelled" value="<?php print($data['display_until_cancelled']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_display_until_cancelled" value="<?php print($data['display_until_cancelled']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">max_targets:</td>
							<td>
								<input type="text" name="item_details_bauble|max_aoe_targets" value="<?php print($data['max_aoe_targets']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_max_aoe_targets" value="<?php print($data['max_aoe_targets']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) : ?>
			<tr>
				<td align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php endif; ?>
		</table>
	<?php endif;
}


function item_details_thrown($id) {
	global $eq2,$objectName,$link;

	$table="item_details_thrown";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="3">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td>
								<input type="text" name="item_details_thrown|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_thrown|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">range_bonus:</td>
							<td>
								<input type="text" name="item_details_thrown|range_bonus" value="<?php print($data['range_bonus']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_range_bonus" value="<?php print($data['range_bonus']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">damage_bonus:</td>
							<td>
								<input type="text" name="item_details_thrown|damage_bonus" value="<?php print($data['damage_bonus']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_damage_bonus" value="<?php print($data['damage_bonus']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">hit_bonus:</td>
							<td>
								<input type="text" name="item_details_thrown|hit_bonus" value="<?php print($data['hit_bonus']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_hit_bonus" value="<?php print($data['hit_bonus']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">damage_type:</td>
							<td>
								<input type="text" name="item_details_thrown|damage_type" value="<?php print($data['damage_type']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_damage_type" value="<?php print($data['damage_type']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
		} else {
		if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1|new" />
			<tr>
				<td width="680" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="1">
						<tr>
							<td colspan="4">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="4">No data found for this item. You may insert a new record if necessary.</td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td>
								<input type="text" name="item_details_thrown|id|new" value="0" style="width:45px;  background-color:#ddd;" readonly />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_thrown|item_id|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">unknown12:</td>
							<td>
								<input type="text" name="item_details_thrown|unknown12|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">unknown13:</td>
							<td>
								<input type="text" name="item_details_thrown|unknown13|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">unknown14:</td>
							<td>
								<input type="text" name="item_details_thrown|unknown14|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">unknown15:</td>
							<td>
								<input type="text" name="item_details_thrown|unknown15|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">unknown16:</td>
							<td>
								<input type="text" name="item_details_thrown|unknown16|new" value="0" style="width:45px;" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="insert" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
		</table>
		<?php
		}
	}
}


function item_details_house_container($id) {
	global $eq2,$objectName,$link;

	$table="item_details_house_container";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="2">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td>
								<input type="text" name="item_details_house_container|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_house_container|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">num_slots:</td>
							<td>
								<input type="text" name="item_details_house_container|num_slots" value="<?php print($data['num_slots']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_num_slots" value="<?php print($data['num_slots']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">disallowed_types:</td>
							<td>
								<input type="text" name="item_details_house_container|disallowed_types" value="<?php print($data['disallowed_types']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_disallowed_types" value="<?php print($data['disallowed_types']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">allowed_types:</td>
							<td>
								<input type="text" name="item_details_house_container|allowed_types" value="<?php print($data['allowed_types']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_allowed_types" value="<?php print($data['allowed_types']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
		} else {
		if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1|new" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="2">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="4">No data found for this item. You may insert a new record if necessary.</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td>
								<input type="text" name="item_details_house_container|id|new" value="0" style="width:45px;  background-color:#ddd;" readonly />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_house_container|item_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
							</td>
						</tr>
						<tr>
							<td align="right">num_slots:</td>
							<td>
								<input type="text" name="item_details_house_container|num_slots|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">disallowed_types:</td>
							<td>
								<input type="text" name="item_details_house_container|disallowed_types|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">allowed_types:</td>
							<td>
								<input type="text" name="item_details_house_container|allowed_types|new" value="0" style="width:45px;" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="insert" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
		</table>
		<?php
		}
	}
}


function item_details_house($id) {
	global $eq2,$objectName,$link;

	$table="item_details_house";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="2">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td>
								<input type="text" name="item_details_house|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_house|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">rent_reduction:</td>
							<td>
								<input type="text" name="item_details_house|rent_reduction" value="<?php print($data['rent_reduction']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_rent_reduction" value="<?php print($data['rent_reduction']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">status_reduction:</td>
							<td>
								<input type="text" name="item_details_house|status_rent_reduction" value="<?php print($data['status_rent_reduction']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_status_rent_reduction" value="<?php print($data['status_rent_reduction']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">coin_reduction:</td>
							<td>
								<input type="text" name="item_details_house|coin_rent_reduction" value="<?php print($data['coin_rent_reduction']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_coin_rent_reduction" value="<?php print($data['coin_rent_reduction']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">house_type:</td>
							<td>
								<input type="text" name="item_details_house|house_only" value="<?php print($data['house_only']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_house_only" value="<?php print($data['house_only']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">house_location:</td>
							<td>
								<input type="text" name="item_details_house|house_location" value="<?php print($data['house_location']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_house_location" value="<?php print($data['house_location']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
	<?php
	}
}

function item_details_book($id) {
	global $eq2,$objectName;

	$table="item_details_book";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$data = $eq2->RunQuerySingle($query);
	?>
	<?php if($data) : ?>
		<form method="post" name="Form1">
		<table border="0" cellpadding="5">
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="2">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td>
								<input type="text" name="item_details_book|id" value="<?php print($data['id']) ?>" style="width:45px;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_book|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">language:</td>
							<td>
								<input type="text" name="item_details_book|language" value="<?php print($data['language']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_language" value="<?php print($data['language']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">author:</td>
							<td>
								<input type="text" name="item_details_book|author" value="<?php print($data['author']) ?>" style="width:120px;" />
								<input type="hidden" name="orig_author" value="<?php print($data['author']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">title:</td>
							<td>
								<input type="text" name="item_details_book|title" value="<?php print($data['title']) ?>" style="width:120px;" />
								<input type="hidden" name="title" value="<?php print($data['title']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
		</form>
	<?php endif; ?>

	<?php
}

function item_details_recipe_items($id) {
	global $eq2,$objectName,$link;
	$table="item_details_recipe";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
		}
	$rid = $data['recipe_id'];
	$table="item_details_recipe_items";
?>
	<table border="0" cellpadding="5">
		<tr>
			<td width="680" valign="top">
				<fieldset><legend>General</legend>
				<table width="100%" cellpadding="0" border="0">
					<tr>
						<td colspan="5">
							<span class="heading">Editing: <?= $objectName ?> Item ID: <?= $id ?></span><br />&nbsp;
						</td>
					</tr>
					<tr>
						<td width="55">id</td>
						<td width="55">recipe_id</td>
						<td width="410">name</td>
						<td width="55">icon</td>
						<td width="90">soe_recipe_crc</td>
						<td colspan="2">&nbsp;</td>
					</tr>

						<?php
						$query=sprintf("select * from `%s`.%s where recipe_id = %s",ACTIVE_DB,$table, $rid);
						$result=$eq2->db->sql_query($query);
						while($data=$eq2->db->sql_fetchrow($result)) {
						?>
					<form method="post" name="multiForm|<?php print($data['id']); ?>" />
					<tr>
						<td>
							<input type="text" name="item_details_recipe_items|id" value="<?php print($data['id']) ?>" style="width:45px;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_details_recipe_items|recipe_id" value="<?php print($data['recipe_id']) ?>" style="width:45px;" readonly />
							<input type="hidden" name="orig_recipe_id" value="<?php print($data['recipe_id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_details_recipe_items|name" value="<?php print($data['name']) ?>" style="width:400px;" />
							<input type="hidden" name="orig_name" value="<?php print($data['name']) ?>" />
						</td>
						<td>
							<input type="text" name="item_details_recipe_items|icon" value="<?php print($data['icon']) ?>" style="width:45px;"/>
							<input type="hidden" name="orig_icon" value="<?php print($data['icon']) ?>" />
						</td>
						<td>
							<input type="text" name="item_details_recipe_items|soe_recipe_crc" value="<?php print($data['soe_recipe_crc']) ?>" style="width:80px;"/>
							<input type="hidden" name="orig_soe_recipe_crc" value="<?php print($data['soe_recipe_crc']) ?>" />
						</td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" /><?php } ?></td>
					</tr>
					<input type="hidden" name="objectName" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
					</form>
				<?php
				}
				?>
				<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
					<form method="post" name="sdForm|new">
					<tr>
						<td align="center"><strong>new</strong></td>
						<td>
							<input type="text" name="item_details_recipe_items|recipe_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
						</td>
						<td>
							<input type="text" name="item_details_recipe_items|name|new" value="" style="width:400px;" />
						</td>
						<td>
							<input type="text" name="item_details_recipe_items|icon" style="width:45px;"/>
						</td>
						<td>
							<input type="text" name="item_details_recipe_items|soe_recipe_crc" style="width:80px;"/>
						</td>
						<td>
							<input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" />
						</td>
					</tr>
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


function item_details_recipe($id) {
	global $eq2,$objectName,$link;

	$table="item_details_recipe";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="3">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td>
								<input type="text" name="item_details_recipe|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_recipe|item_id" value="<?php print($data['item_id']) ?>" style="width:45px; background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">recipe_id:</td>
							<td>
								<input type="text" name="item_details_recipe|recipe_id" value="<?php print($data['recipe_id']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_recipe_id" value="<?php print($data['recipe_id']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
		} else {
		if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1|new" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="4">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="4">No data found for this item. You may insert a new record if necessary.</td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td align="center"><strong>new</strong></td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_recipe|item_id|new" value="<?php print($id) ?>" style="width:45px;" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="insert" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
		</table>
		<?php
		}
	}
}


function item_details_shield($id) {
	global $eq2,$objectName,$link;

	$table="item_details_shield";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="3">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td>
								<input type="text" name="item_details_shield|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_shield|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">mitigation_low:</td>
							<td>
								<input type="text" name="item_details_shield|mitigation_low" value="<?php print($data['mitigation_low']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_mitigation_low" value="<?php print($data['mitigation_low']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">mitigation_high:</td>
							<td>
								<input type="text" name="item_details_shield|mitigation_high" value="<?php print($data['mitigation_high']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_mitigation_high" value="<?php print($data['mitigation_high']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
		} else {
		if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1|new" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="4">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="4">No data found for this item. You may insert a new record if necessary.</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td align="center"><strong>new</strong></td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_shield|item_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
							</td>
						</tr>
						<tr>
							<td align="right">mitigation_low:</td>
							<td>
								<input type="text" name="item_details_shield|mitigation_low|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">mitigation_high:</td>
							<td>
								<input type="text" name="item_details_shield|mitigation_high|new" value="0" style="width:45px;" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="insert" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
		</table>
		<?php
		}
	}
}


function item_details_range($id) {
	global $eq2,$objectName;

	$table="item_details_range";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1">
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="4">
								<span class="heading">Editing: <?= $objectName ?></span><br>
							</td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td>
								<input type="text" name="item_details_range|id" value="<?php print($data['id']) ?>" style="width:50px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_range|item_id" value="<?php print($data['item_id']) ?>" style="width:50px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">delay:</td>
							<td>
								<input type="text" id="dmgDelay" name="item_details_range|delay" value="<?php print($data['delay']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_delay" value="<?php print($data['delay']) ?>" />
							</td>
							<td align="right">damage_rating:</td>
							<td>
								<input type="text" id="dmgRating" name="item_details_range|damage_rating" value="<?php print($data['damage_rating']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_damage_rating" value="<?php print($data['damage_rating']) ?>" />
								<button type="button" style="width:15px;text-align:center;padding:0px;" title="Calculate" onclick="CalculateWeaponDamageRating()">...</button>
							</td>
						</tr>
						<tr>
							<td align="right">dmg_low:</td>
							<td>
								<input type="text" name="item_details_range|dmg_low" value="<?php print($data['dmg_low']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_dmg_low" value="<?php print($data['dmg_low']) ?>" />
							</td>
							<td align="right">dmg_high:</td>
							<td>
								<input type="text" name="item_details_range|dmg_high" value="<?php print($data['dmg_high']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_dmg_high" value="<?php print($data['dmg_high']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">dmg_mastery_low:</td>
							<td>
								<input type="text" name="item_details_range|dmg_mastery_low" value="<?php print($data['dmg_mastery_low']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_dmg_mastery_low" value="<?php print($data['dmg_mastery_low']) ?>" />
							</td>
							<td align="right">dmg_mastery_high:</td>
							<td>
								<input type="text" name="item_details_range|dmg_mastery_high" value="<?php print($data['dmg_mastery_high']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_dmg_mastery_high" value="<?php print($data['dmg_mastery_high']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">dmg_base_low:</td>
							<td>
								<input type="text" id="dmgBaseLow" name="item_details_range|dmg_base_low" value="<?php print($data['dmg_base_low']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_dmg_base_low" value="<?php print($data['dmg_base_low']) ?>" />
							</td>
							<td align="right">dmg_base_high:</td>
							<td>
								<input type="text" id="dmgBaseHigh" name="item_details_range|dmg_base_high" value="<?php print($data['dmg_base_high']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_dmg_base_high" value="<?php print($data['dmg_base_high']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">range_low:</td>
							<td>
								<input type="text" name="item_details_range|range_low" value="<?php print($data['range_low']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_range_low" value="<?php print($data['range_low']) ?>" />
							</td>
							<td align="right">range_high:</td>
							<td>
								<input type="text" name="item_details_range|range_high" value="<?php print($data['range_high']) ?>" style="width:50px;" />
								<input type="hidden" name="orig_range_high" value="<?php print($data['range_high']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
	<?php
	}
}


function item_details_bag($id) {
	global $eq2,$objectName,$link;

	$table="item_details_bag";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1">
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="2">
								<span class="heading">Editing: <?= $objectName ?></span><br>
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td>
								<input type="text" name="item_details_bag|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_bag|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">num_slots:</td>
							<td>
								<input type="text" name="item_details_bag|num_slots" value="<?php print($data['num_slots']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_num_slots" value="<?php print($data['num_slots']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">weight_reduction:</td>
							<td>
								<input type="text" name="item_details_bag|weight_reduction" value="<?php print($data['weight_reduction']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_weight_reduction" value="<?php print($data['weight_reduction']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
		} else {
		if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1|new">
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="4">
								<span class="heading">Editing: <?= $objectName ?></span><br>
							</td>
						</tr>
						<tr>
							<td colspan="4">No data found for this item. You may insert a new record if necessary.</td>
						</tr>
						<tr>
							<td width="50%" align="right">id:</td>
							<td align="center"><strong>new</strong></td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_bag|item_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
							</td>
						</tr>
						<tr>
							<td align="right">num_slots:</td>
							<td>
								<input type="text" name="item_details_bag|num_slots|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">weight_reduction:</td>
							<td>
								<input type="text" name="item_details_bag|weight_reduction|new" value="0" style="width:45px;" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="insert" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
		</table>
		<?php
		}
	}
}


function item_details_food($id) {
	global $eq2,$objectName,$link;

	$table="item_details_food";
	$query=sprintf("select * from `%s`.%s where item_id = %d", ACTIVE_DB, $table, $id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="3">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td>
								<input type="text" name="item_details_food|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_food|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">type:</td>
							<td>
								<select name="item_details_food|type" style="width:100px;">
									<option value="-1"<?php if (empty($data['type'])) print(" selected"); ?>>---</option>
									<option value="0"<?php if ( $data['type'] == 0 ) print(" selected"); ?>>Drink</option>
									<option value="1"<?php if ( $data['type'] == 1 ) print(" selected"); ?>>Food</option>
								</select>
								<input type="hidden" name="orig_type" value="<?php print($data['type']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">level:</td>
							<td>
								<input type="text" name="item_details_food|level" value="<?php print($data['level']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_level" value="<?php print($data['level']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">duration:</td>
							<td>
								<input type="text" name="item_details_food|duration" value="<?php print($data['duration']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_duration" value="<?php print($data['duration']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">satiation:</td>
							<td>
								<input type="text" name="item_details_food|satiation" value="<?php print($data['satiation']) ?>" style="width:45px;" />
								<input type="hidden" name="orig_satiation" value="<?php print($data['satiation']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
		} else {
		if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
		<table border="0" cellpadding="5">
		<form method="post" name="Form1|new" />
			<tr>
				<td width="480" valign="top">
					<fieldset><legend>General</legend>
					<table width="100%" cellpadding="0" border="0">
						<tr>
							<td colspan="2">
								<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="2">No data found for this item. You may insert a new record if necessary.</td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td align="center"><strong>new</strong></td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_food|item_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
							</td>
						</tr>
						<tr>
							<td align="right">type:</td>
							<td>
								<input type="text" name="item_details_food|type|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">level:</td>
							<td>
								<input type="text" name="item_details_food|level|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">duration:</td>
							<td>
								<input type="text" name="item_details_food|duration|new" value="0" style="width:45px;" />
							</td>
						</tr>
						<tr>
							<td align="right">satiation:</td>
							<td>
								<input type="text" name="item_details_food|satiation|new" value="0" style="width:45px;" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
					<input type="hidden" name="cmd" value="insert" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
		</table>
		<?php
		}
	}
}
 

function item_details_armor($id) {
	global $eq2, $objectName, $link;

	$table="item_details_armor";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0) {
		$data=$eq2->db->sql_fetchrow($result);
?>
		<form method="post" name="ItemsForm" >
		<table border="0" cellpadding="5">
			<tr>
				<td width="480" valign="top">
					<fieldset>
					<legend>General</legend>
					<table width="100%" border="0">
					<tr>
							<td colspan="6"> <span class="heading">Editing: <?= $objectName ?></span><br /></td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td>
								<input type="text" name="item_details_armor|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_armor|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">mitigation_low:</td>
							<td>
								<input type="text" name="item_details_armor|mitigation_low" value="<?php print($data['mitigation_low']) ?>" style="width:50px" />
								<input type="hidden" name="orig_mitigation_low" value="<?php print($data['mitigation_low']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">mitigation_high:</td>
							<td>
								<input type="text" name="item_details_armor|mitigation_high" value="<?php print($data['mitigation_high']) ?>" style="width:50px" />
								<input type="hidden" name="orig_mitigation_high" value="<?php print($data['mitigation_high']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />						
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
		</form>
		<?php
		} else {
		if($eq2->CheckAccess(G_DEVELOPER)) { ?>
		<table border="0" cellpadding="5">
			<form method="post" name="ItemsFormNew" />
			<tr>
				<td width="680" valign="top">
					<fieldset>
					<legend>General</legend>
					<table width="100%" border="0">
						<tr>
							<td colspan="6"> <span class="heading">Editing: <?= $objectName ?></span><br /></td>
						</tr>
						<tr>
							<td colspan="4">No data found for this item. You may insert a new record if necessary.</td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td align="center"><strong>new</strong></td>
						</tr>
						<tr>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_armor|item_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
							</td>
						</tr>
						<tr>
							<td align="right">mitigation_low:</td>
							<td>
								<input type="text" name="item_details_armor|mitigation_low|new" value="0" style="width:50px" />
							</td>
						</tr>
						<tr>
							<td align="right">mitigation_high:</td>
							<td>
								<input type="text" name="item_details_armor|mitigation_high|new" value="0" style="width:50px" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />						
					<input type="hidden" name="cmd" value="insert" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
		</table>
		<?php 
		} 
	}
}


function item_details_weapon($id) {
	global $eq2, $objectName;

	$table="item_details_weapon";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	if($eq2->db->sql_numrows($result) > 0)
	{
		$data=$eq2->db->sql_fetchrow($result);
		?>
		<table border="0" cellpadding="5">
			<form method="post" name="ItemsForm" />
			<tr>
				<td width="680" valign="top">
					<fieldset>
					<legend>General</legend>
					<table width="100%" border="0">
						<tr>
							<td colspan="6"> <span class="heading">Editing: <?= $objectName ?></span><br /></td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td>
								<input type="text" name="item_details_weapon|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_weapon|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">damage_type:</td>
							<td colspan="3">
								<select name="item_details_weapon|damage_type" style="width:250px">
									<option value="-1"<?= ( empty($data['damage_type']) ) ? ' selected' : ''; ?>>---</option>
									<option value="0"<?= ( $data['damage_type'] == 0 ) ? ' selected' : ''; ?>>Slashing</option>
									<option value="1"<?= ( $data['damage_type'] == 1 ) ? ' selected' : ''; ?>>Crushing</option>
									<option value="2"<?= ( $data['damage_type'] == 2 ) ? ' selected' : ''; ?>>Piercing</option>
									<option value="3"<?= ( $data['damage_type'] == 3 ) ? ' selected' : ''; ?>>Heat</option>
									<option value="4"<?= ( $data['damage_type'] == 4 ) ? ' selected' : ''; ?>>Cold</option>
									<option value="5"<?= ( $data['damage_type'] == 5 ) ? ' selected' : ''; ?>>Magic</option>
									<option value="6"<?= ( $data['damage_type'] == 6 ) ? ' selected' : ''; ?>>Mental</option>
									<option value="7"<?= ( $data['damage_type'] == 7 ) ? ' selected' : ''; ?>>Divine</option>
									<option value="8"<?= ( $data['damage_type'] == 8 ) ? ' selected' : ''; ?>>Disease</option>
									<option value="9"<?= ( $data['damage_type'] == 9 ) ? ' selected' : ''; ?>>Poison</option>
									<option value="10"<?= ( $data['damage_type'] == 10 ) ? ' selected' : ''; ?>>Drown</option>
									<option value="11"<?= ( $data['damage_type'] == 11 ) ? ' selected' : ''; ?>>Falling</option>
									<option value="12"<?= ( $data['damage_type'] == 12 ) ? ' selected' : ''; ?>>Pain</option>
								</select>
								<input type="hidden" name="orig_damage_type" value="<?php print($data['damage_type']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">wield_style:</td>
							<td colspan="3">
								<select name="item_details_weapon|wield_style" style="width:250px">
									<option value="-1"<?= ( empty($data['wield_style']) ) ? ' selected' : ''; ?>>---</option>
									<option value="1"<?= ( $data['wield_style'] == 1 ) ? ' selected' : ''; ?>>Dual Wield</option>
									<option value="2"<?= ( $data['wield_style'] == 2 ) ? ' selected' : ''; ?>>Single Wield</option>
									<option value="4"<?= ( $data['wield_style'] == 4 ) ? ' selected' : ''; ?>>Two-Handed</option>
								</select>
								<input type="hidden" name="orig_wield_style" value="<?php print($data['wield_style']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">delay:</td>
							<td>
								<input type="text" id="dmgDelay" name="item_details_weapon|delay" value="<?php print($data['delay']) ?>" style="width:50px" />
								<input type="hidden" name="orig_delay" value="<?php print($data['delay']) ?>" />
							</td>
							<td align="right">damage_rating:</td>
							<td>
								<input type="text" id="dmgRating" name="item_details_weapon|damage_rating" value="<?php print($data['damage_rating']) ?>" style="width:50px" />							
								<input type="hidden" name="orig_damage_rating" value="<?php print($data['damage_rating']) ?>" />
								<button type="button" style="width:15px;text-align:center;padding:0px;" title="Calculate" onclick="CalculateWeaponDamageRating()">...</button>
							</td>
						</tr>
						<tr>
							<td align="right">dmg_low:</td>
							<td>
								<input type="text" name="item_details_weapon|dmg_low" value="<?php print($data['dmg_low']) ?>" style="width:50px" />
								<input type="hidden" name="orig_dmg_low" value="<?php print($data['dmg_low']) ?>" />
							</td>
							<td align="right">dmg_high:</td>
							<td>
								<input type="text" name="item_details_weapon|dmg_high" value="<?php print($data['dmg_high']) ?>" style="width:50px" />
								<input type="hidden" name="orig_dmg_high" value="<?php print($data['dmg_high']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">dmg_mastery_low:</td>
							<td>
								<input type="text" name="item_details_weapon|dmg_mastery_low" value="<?php print($data['dmg_mastery_low']) ?>" style="width:50px" />
								<input type="hidden" name="orig_dmg_mastery_low" value="<?php print($data['dmg_mastery_low']) ?>" />
							</td>
							<td align="right">dmg_mastery_high:</td>
							<td>
								<input type="text" name="item_details_weapon|dmg_mastery_high" value="<?php print($data['dmg_mastery_high']) ?>" style="width:50px" />
								<input type="hidden" name="dmg_mastery_high" value="<?php print($data['dmg_mastery_high']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">dmg_base_low:</td>
							<td>
								<input type="text" id="dmgBaseLow" name="item_details_weapon|dmg_base_low" value="<?php print($data['dmg_base_low']) ?>" style="width:50px" />
								<input type="hidden" name="orig_dmg_base_low" value="<?php print($data['dmg_base_low']) ?>" />
							</td>
							<td align="right">dmg_base_high:</td>
							<td>
								<input type="text" id="dmgBaseHigh" name="item_details_weapon|dmg_base_high" value="<?php print($data['dmg_base_high']) ?>" style="width:50px" />
								<input type="hidden" name="orig_dmg_base_high" value="<?php print($data['dmg_base_high']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />						
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
	<?php }
}


function item_details_skills($id) {
	global $eq2, $objectName, $link;

	$table="item_details_skills";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);

	// Show detail, else show insert form
	if($eq2->db->sql_numrows($result) > 0)
	{
		$data=$eq2->db->sql_fetchrow($result);
?>
		<table border="0" cellpadding="5">
			<form method="post" name="ItemsForm" />
			<tr>
				<td width="680" valign="top">
					<fieldset>
					<legend>General</legend>
					<table border="0">
						<tr>
							<td colspan="6"> <span class="heading">Editing: <?= $objectName ?></span><br /></td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td>
								<input type="text" name="item_details_skills|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
							</td>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_skills|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
								<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">hp_req:</td>
							<td>
								<input type="text" name="item_details_skills|hp_req" value="<?php print($data['hp_req']) ?>" style="width:50px" />
								<input type="hidden" name="orig_hp_req" value="<?php print($data['hp_req']) ?>" />
							</td>
							<td align="right">hp_upkeep_req:</td>
							<td>
								<input type="text" name="item_details_skills|hp_upkeep_req" value="<?php print($data['hp_upkeep_req']) ?>" style="width:50px" />
								<input type="hidden" name="orig_hp_upkeep_req" value="<?php print($data['hp_upkeep_req']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">power_req:</td>
							<td>
								<input type="text" name="item_details_skills|power_req" value="<?php print($data['power_req']) ?>" style="width:50px" />
								<input type="hidden" name="orig_power_req" value="<?php print($data['power_req']) ?>" />
							</td>
							<td align="right">power_upkeep_req:</td>
							<td>
								<input type="text" name="item_details_skills|power_upkeep_req" value="<?php print($data['power_upkeep_req']) ?>" style="width:50px" />
								<input type="hidden" name="orig_power_upkeep_req" value="<?php print($data['power_upkeep_req']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">cast:</td>
							<td>
								<input type="text" name="item_details_skills|cast" value="<?php print($data['cast']) ?>" style="width:50px" />
								<input type="hidden" name="orig_cast" value="<?php print($data['cast']) ?>" />
							</td>
							<td align="right">duration:</td>
							<td>
								<input type="text" name="item_details_skills|duration" value="<?php print($data['duration']) ?>" style="width:50px" />
								<input type="hidden" name="orig_duration" value="<?php print($data['duration']) ?>" />
							</td>
						</tr>
						<tr>
							<td align="right">recovery:</td>
							<td>
								<input type="text" name="item_details_skills|recovery" value="<?php print($data['recovery']) ?>" style="width:50px" />
								<input type="hidden" name="orig_recovery" value="<?php print($data['recovery']) ?>" />
							</td>
							<td align="right">recast:</td>
							<td>
								<input type="text" name="item_details_skills|recast" value="<?php print($data['recast']) ?>" style="width:50px" />
								<input type="hidden" name="orig_recast" value="<?php print($data['recast']) ?>" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />						
					<input type="hidden" name="cmd" value="update" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
			<?php } ?>
		</table>
<?php } else { ?>
		<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
		<table border="0" cellpadding="5">
			<form method="post" name="ItemsForm" />
			<tr>
				<td width="680" valign="top">
					<fieldset>
					<legend>General</legend>
					<table width="80%" border="0">
						<tr>
							<td colspan="4"> <span class="heading">Editing: <?= $objectName ?></span><br /></td>
						</tr>
						<tr>
							<td colspan="4">No data found for this item. You may insert a new record if necessary.</td>
						</tr>
						<tr>
							<td align="right">id:</td>
							<td align="center"><strong>new</strong></td>
							<td align="right">item_id:</td>
							<td>
								<input type="text" name="item_details_skills|item_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
							</td>
						</tr>
						<tr>
							<td align="right">hp_req:</td>
							<td>
								<input type="text" name="item_details_skills|hp_req|new" value="0" style="width:50px" />
							</td>
							<td align="right">hp_upkeep_req:</td>
							<td>
								<input type="text" name="item_details_skills|hp_upkeep_req|new" value="0" style="width:50px" />
							</td>
						</tr>
						<tr>
							<td align="right">power_req:</td>
							<td>
								<input type="text" name="item_details_skills|power_req|new" value="0" style="width:50px" />
							</td>
							<td align="right">power_upkeep_req:</td>
							<td>
								<input type="text" name="item_details_skills|power_upkeep_req|new" value="0" style="width:50px" />
							</td>
						</tr>
						<tr>
							<td align="right">cast:</td>
							<td>
								<input type="text" name="item_details_skills|cast|new" value="0" style="width:50px" />
							</td>
							<td align="right">duration:</td>
							<td>
								<input type="text" name="item_details_skills|duration|new" value="0" style="width:50px" />
							</td>
						</tr>
						<tr>
							<td align="right">recovery:</td>
							<td>
								<input type="text" name="item_details_skills|recovery|new" value="0" style="width:50px" />
							</td>
							<td align="right">recast:</td>
							<td>
								<input type="text" name="item_details_skills|recast|new" value="0" style="width:50px" />
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
					<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />						
					<input type="hidden" name="cmd" value="insert" />
					<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</td>
			</tr>
		</table>
		<?php } ?>
<?php
	}
}


function item_stats($id) {
	global $eq2,$objectName,$eq2Items;
	$disp = (isset($_GET['disp'])?$_GET['disp']:NULL);
	$isSelected = null;

	$table="item_mod_stats";
	$strOffset = str_repeat("\x20",22);

	$strHTML = "\n";

	//JAVASCRIPT TO HANDLE SUBTYPE SELECT UPDATES
	$strHTML .= $strOffset . "<script>\n";
	$strHTML .= $strOffset . "sizeOfArray = function (array) {\n";
	$strHTML .= $strOffset . "  let size = 0;\n";
	$strHTML .= $strOffset . "  for (let key in array) {\n";
	$strHTML .= $strOffset . "    if (array.hasOwnProperty(key)) {\n";
	$strHTML .= $strOffset . "      size++;\n";
	$strHTML .= $strOffset . "    }\n";
	$strHTML .= $strOffset . "  }\n";
	$strHTML .= $strOffset . "  return size;\n";
	$strHTML .= $strOffset . "}\n";

	$strHTML .= $strOffset . "  function updateSubType(x,z){\n";
	
	$strjs = $strOffset . "    var type ={";
	foreach($eq2Items->eq2ItemStatTypes as $x =>$type)
	{
		$strjs .= $x . ":{";
		$intX = 0;

		foreach($eq2Items->eq2ItemStatSubTypes as $y => $subtype)
		{
			if(intval($x)<=0 AND intval($y)<=99){
				$strjs .= $intX . ":['" . $y . "','" . $subtype . "'],";
				$intX++;
			}elseif(intval($x)>=1 AND intval($y)>=99 AND substr($y,0,1) == $x)
			{
				$outterTrim = (strlen($y)>=2?substr($y,1):$y);
			
				if(strlen($outterTrim)>=2 AND substr($outterTrim,0,1)==0)
				{
					$innerTrim = substr($outterTrim,1);
				}else{
					$innerTrim = $outterTrim;
				}
				$strjs .= $intX . ":['" . $innerTrim . "','" . $subtype . "'],";
				$intX++;
			}else{
				//print(">>MADEITHERE<<\n");
			}
			
		}
		$strjs = substr_replace($strjs,"",-1);
		$strjs .= "},\n";
	}
	$strjs = substr_replace($strjs,"",-2);
	$strHTML .= $strjs . "};\n";
	$strHTML .= $strOffset . "    while(x.options.length > 0) {\n";
	$strHTML .= $strOffset . "      x.remove(x.options.length-1);\n";
	$strHTML .= $strOffset . "    }\n";
	$strHTML .= $strOffset . "    var arylen = sizeOfArray(type[z]);\n";
	$strHTML .= $strOffset . "    for(i=0;i < arylen; i++)\n";
	$strHTML .= $strOffset . "    {\n";
	$strHTML .= $strOffset . "        var opt = document.createElement('option');\n";
	$strHTML .= $strOffset . "        opt.value = type[z][i][0];\n";
	$strHTML .= $strOffset . "        opt.text = type[z][i][0] + ' - ' + type[z][i][1]\n";
	$strHTML .= $strOffset . "        x.add(opt, null);\n";
	$strHTML .= $strOffset . "    }\n";
	$strHTML .= $strOffset . "  }\n";
	$strHTML .= $strOffset . "</script>\n";

	$strHTML .= $strOffset . "<table border='0' cellpadding='5'>\n";
	$strHTML .= $strOffset . "  <tr>\n";
	$strHTML .= $strOffset . "    <td width='780' valign='top'>\n";
	$strHTML .= $strOffset . "      <fieldset>\n";
	$strHTML .= $strOffset . "      <legend>General</legend>\n";
	$strHTML .= $strOffset . "        <table width='100%' cellpadding='0' border='0'>\n";
	$strHTML .= $strOffset . "          <tr>\n";
	$strHTML .= $strOffset . "            <td colspan='8'>\n";
	$strHTML .= $strOffset . "              <span class='heading'>Editing:" . $objectName . "</span><br />&nbsp;\n";
	$strHTML .= $strOffset . "            </td>\n";
	$strHTML .= $strOffset . "          </tr>\n";
	$strHTML .= $strOffset . "          <tr>\n";
	$strHTML .= $strOffset . "            <td width='45'>id</td>\n";
	$strHTML .= $strOffset . "            <td width='45'>item_id</td>\n";
	$strHTML .= $strOffset . "            <td width='35'>index</td>\n";
	$strHTML .= $strOffset . "            <td width='55'>type</td>\n";
	$strHTML .= $strOffset . "            <td width='55'>subtype</td>\n";
	$strHTML .= $strOffset . "            <td width='60'>int</td>\n";
	$strHTML .= $strOffset . "            <td width='60'>float</td>\n";
	$strHTML .= $strOffset . "            <td width='100'>string</td>\n";
	$strHTML .= $strOffset . "            <td colspan='2'>&nbsp;</td>\n";
	$strHTML .= $strOffset . "          </tr>\n";

	$query=sprintf("select * from `%s`.%s where item_id = %s ORDER BY stats_order",ACTIVE_DB,$table, $id);
	$result=$eq2->db->sql_query($query);
	$lastStatOrder = null;
	while($data=$eq2->db->sql_fetchrow($result)) {
		$lastStatOrder = $data['stats_order'];
		
		$strHTML .= $strOffset . "          <form method='post' name='multiForm|" . $data['id'] . "'>\n";
		$strHTML .= $strOffset . "            <tr>\n";
		$strHTML .= $strOffset . "            <td>\n";
		$strHTML .= $strOffset . "              <input type='text' name='item_mod_stats|id' value='" . $data['id'] ."' style='width:45px;  background-color:#ddd;' readonly />\n";
		$strHTML .= $strOffset . "              <input type='hidden' name='orig_id' value='" . $data['id'] . "' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "            <td>\n";
		$strHTML .= $strOffset . "              <input type='text' name='item_mod_stats|item_id' value='" . $data['item_id'] . "'  style='width:45px;  background-color:#ddd;' readonly />\n";
		$strHTML .= $strOffset . "              <input type='hidden' name='orig_item_id' value='" . $data['item_id'] ."' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "            <td>\n";
		$strHTML .= $strOffset . "              <input type='text' name='item_mod_stats|stats_order' value='" . $data['stats_order'] . "'  style='width:45px;' />\n";
		$strHTML .= $strOffset . "              <input type='hidden' name='orig_stats_order' value='" . $data['stats_order'] . "' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "            <td>\n";
		if($disp=='advanced')
		{
			$strHTML .= $strOffset . "              <input type='text' name='item_mod_stats|type' value='" . $data['type'] . "'  style='width:45px;' />\n";
		}else{
			$strHTML .= $strOffset . "                <select name='item_mod_stats|type' id='type_" . $data['id'] . "' onchange='updateSubType(subtype_" . $data['id'] . ", this.value)'/>\n";
			foreach($eq2Items->eq2ItemStatTypes as $x =>$type)
			{
				$isSelected = ($x == intval($data['type'])?" selected ":"");
				$strHTML .= $strOffset . "                  <option " . $isSelected . " value='" . $x . "'>" . $x . " - " .$type . "</option>\n";
			}
			$strHTML .= $strOffset . "                </select>";
		}
		$strHTML .= $strOffset . "              <input type='hidden' name='orig_type' value='" . $data['type'] . "' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "            <td>\n";
		if($disp=='advanced')
		{
			$strHTML .= $strOffset . "              <input type='text' name='item_mod_stats|subtype' value='" . $data['subtype'] . "'  style='width:45px;' />\n";
		}else{
			$strHTML .= $strOffset . "                <select name='item_mod_stats|subtype' id='subtype_" . $data['id'] . "'>\n";
			foreach($eq2Items->eq2ItemStatSubTypes as $y => $subtype)
			{
				$realSubType = ($data['type']*100)+$data['subtype'];
				$isSelected = ($y == $realSubType?" selected ":"");
				$outterTrim = (strlen($y)>=2?substr($y,1):$y);
				
				if(strlen($outterTrim)>=2 AND substr($outterTrim,0,1)==0)
				{
					$innerTrim = substr($outterTrim,1);
				}else{
					$innerTrim = $outterTrim;
				}
				$strHTML .= $strOffset . "                  <option " . $isSelected . " value='" . $innerTrim . "'>" . $innerTrim . " - " . $subtype . "</option>\n";
			}
			$strHTML .= $strOffset . "                </select>";
		}
		$strHTML .= $strOffset . "              <input type='hidden' name='orig_subtype' value='" . $data['subtype'] . "' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "            <td>\n";
		$strHTML .= $strOffset . "              <input type='text' name='item_mod_stats|iValue' value='" . $data['iValue'] . "'  style='width:80px;' />\n";
		$strHTML .= $strOffset . "              <input type='hidden' name='orig_iValue' value='" . $data['iValue'] . "' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "            <td>\n";
		$strHTML .= $strOffset . "              <input type='text' name='item_mod_stats|fValue' value='" . $data['fValue'] . "'  style='width:80px;' />\n";
		$strHTML .= $strOffset . "              <input type='hidden' name='orig_fValue' value='" . $data['fValue'] . "' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "            <td>\n";
		$strHTML .= $strOffset . "              <input type='text' name='item_mod_stats|sValue' value='" . $data['sValue'] . "'  style='width:120px;' />\n";
		$strHTML .= $strOffset . "              <input type='hidden' name='orig_sValue' value='" . $data['sValue'] . "' />\n";
		$strHTML .= $strOffset . "            </td>\n";
		$strHTML .= $strOffset . "            <td>" . ($eq2->CheckAccess(G_DEVELOPER)?"<input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />":"") . "</td>\n";
		$strHTML .= $strOffset . "            <td>" . ($eq2->CheckAccess(G_DEVELOPER)?"<input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />":"") . "</td>\n";
		$strHTML .= $strOffset . "          </tr>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='objectName' value='" . $objectName . "' />\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='table_name' value='" . $table . "' />\n";
		$strHTML .= $strOffset . "        </form>\n";
	}

	if($eq2->CheckAccess(G_DEVELOPER)) {
		$strHTML .= $strOffset . "          <form method='post' name='sdForm|new'>\n";
		$strHTML .= $strOffset . "            <tr>\n";
		$strHTML .= $strOffset . "              <td align='center'><strong>new</strong></td>\n";
		$strHTML .= $strOffset . "              <td>\n";
		$strHTML .= $strOffset . "                <input type='text' name='item_mod_stats|item_id|new' value='" . $id . "' readonly style='width:45px;' />\n";
		$strHTML .= $strOffset . "              </td>\n";
		$strHTML .= $strOffset . "              <td>\n";
		$strHTML .= $strOffset . "                <input type='text' name='item_mod_stats|stats_order|new' value='" . ($lastStatOrder == null ? 0 : $lastStatOrder + 1) . "'  style='width:45px;' />\n";
		$strHTML .= $strOffset . "              </td>\n";
		$strHTML .= $strOffset . "              <td>\n";
		if($disp=='advanced')
		{
			$strHTML .= $strOffset . "                <input type='text' name='item_mod_stats|type|new' value='255'  style='width:45px;' />\n";
		}else{
			$strHTML .= $strOffset . "                <select name='item_mod_stats|type|new'>";
			foreach($eq2Items->eq2ItemStatTypes as $x =>$type)
			{
				$strHTML .= $strOffset . "                  <option " . $isSelected . " value='" . $x . "'>" . $x . " - " .$type . "</option>\n";
			}
			$strHTML .= $strOffset . "                </select>";
		}
			$strHTML .= $strOffset . "              </td>\n";
			$strHTML .= $strOffset . "              <td>\n";
		if($disp=='advanced')
		{
			$strHTML .= $strOffset . "                <input type='text' name='item_mod_stats|subtype|new' value='255'  style='width:45px;' />\n";
		}else{
			$strHTML .= $strOffset . "                <select name='item_mod_stats|subtype|new'>";
			foreach($eq2Items->eq2ItemStatSubTypes as $y => $subtype)
			{
				$strHTML .= $strOffset . "                  <option " . $isSelected . " value='" . $y . "'>" . $y . " - " . $subtype . "</option>\n";
			}
			$strHTML .= $strOffset . "                </select>";
		}
		$strHTML .= $strOffset . "              </td>\n";
		$strHTML .= $strOffset . "              <td>\n";
		$strHTML .= $strOffset . "                <input type='text' name='item_mod_stats|iValue|new' value=''  style='width:80px;' />\n";
		$strHTML .= $strOffset . "              </td>\n";
		$strHTML .= $strOffset . "              <td>\n";
		$strHTML .= $strOffset . "                <input type='text' name='item_mod_stats|fValue|new' value=''  style='width:80px;' />\n";
		$strHTML .= $strOffset . "              </td>\n";
		$strHTML .= $strOffset . "              <td>\n";
		$strHTML .= $strOffset . "                <input type='text' name='item_mod_stats|sValue|new' value=''  style='width:120px;' />\n";
		$strHTML .= $strOffset . "              </td>\n";
		$strHTML .= $strOffset . "              <td>\n";
		$strHTML .= $strOffset . "                <input type='submit' name='cmd' value='Insert' style='font-size:10px; width:60px' />\n";
		$strHTML .= $strOffset . "              </td>\n";
		$strHTML .= $strOffset . "            </tr>\n";
		$strHTML .= $strOffset . "            <input type='hidden' name='table_name' value='" . $table . "' />\n";
		$strHTML .= $strOffset . "          </form>\n";
	}	
	
	//$lastStatOrder = $data['stats_order'];
	$strHTML .= $strOffset . "        </table>\n";
	$strHTML .= $strOffset . "      </fieldset>\n";
	if($disp != "advanced"){
		$strHTML .= $strOffset . "      <a href='items.php?show=items&type=" . $_GET['type'] . "&tab=" . $_GET['tab'] . "&id=" . $_GET['id'] . "&disp=advanced'>Show Advanced Form</a>\n";
	}else{
		$strHTML .= $strOffset . "      <a href='items.php?show=items&type=" . $_GET['type'] . "&tab=" . $_GET['tab'] . "&id=" . $_GET['id'] . "&disp=simple'>Show Simple Form</a>\n";
	}
	$strHTML .= $strOffset . "    </td>\n";
	$strHTML .= $strOffset . "    <td width='350' valign='top'>\n";
	//NEW STATS
	$strHTML .= $strOffset . "      <fieldset>\n";
	$strHTML .= $strOffset . "      <legend>Stats View(New)</legend>\n";
	$strHTML .= $strOffset . "          <table border='1'>\n";
	$strHTML .= $strOffset . "            <tr>\n";
	$strHTML .= $eq2->GenerateItemHover($_GET['id']);
	$strHTML .= $strOffset . "            <tr>\n";
	$strHTML .= $strOffset . "        </table>\n";
	$strHTML .= $strOffset . "      </fieldset>\n";
	$strHTML .= $strOffset . "    </td>\n";
	$strHTML .= $strOffset . "  </tr>\n";
	$strHTML .= $strOffset . "</table>\n";
	/*
	//OLD STATS VIEW
	$strHTML .= $strOffset . "    <td width='200' valign='top'>\n";
	$strHTML .= $strOffset . "      <fieldset><legend>Stats View</legend>\n";
	//$strHTML .= $strOffset . "        <table class='ContrastTable'>\n";
	//$strHTML .= $strOffset . "          <tr>\n";
	//$strHTML .= $strOffset . "            <td>\n";
	$strHTML .= $strOffset . "              <table class='ContrastTable'>\n";
	$strHTML .= $strOffset . "                <tr>\n";
	$strHTML .= $strOffset . "                  <th>Type</th>\n";
	$strHTML .= $strOffset . "                  <th>SubType</th>\n";
	$strHTML .= $strOffset . "                  <th>Combined</th>\n";
	$strHTML .= $strOffset . "                  <th>Name</th>\n";
	$strHTML .= $strOffset . "                </tr>\n";

	$query=sprintf("select * from `%s`.%s where item_id = %s ORDER BY stats_order",ACTIVE_DB,$table, $id);
	$result=$eq2->db->sql_query($query);
	$lastStatOrder = null;
	while($data=$eq2->db->sql_fetchrow($result)) {
		$strHTML .= $strOffset . "            <tr>\n";
		$strHTML .= $strOffset . "              <td>" . $data['type'] . "</td>\n";
		$strHTML .= $strOffset . "              <td>" .$data['subtype'] ."</td>\n";

		$statType = (intval($data['type'])*100)+intval($data['subtype']);
		$statName = $eq2Items->eq2ItemStatSubTypes[$statType];
		$strHTML .= $strOffset . "              <td>" . $statType ."</td>\n";
		$strHTML .= $strOffset . "              <td>" . $statName ."</td>\n";
	
		$strHTML .= $strOffset . "            </tr>\n";
	}
	$strHTML .= $strOffset . "              </table>\n";
	//$strHTML .= $strOffset . "            </td>\n";
	//$strHTML .= $strOffset . "          </tr>\n";
	//$strHTML .= $strOffset . "        </table>\n";
	$strHTML .= $strOffset . "      </fieldset>\n";


	$strHTML .= $strOffset . "    </td>\n";
	$strHTML .= $strOffset . "  </tr>\n";
	$strHTML .= $strOffset . "  <tr>\n";
	$strHTML .= $strOffset . "    <td align='center'>\n";
	$strHTML .= $strOffset . "      <input type='button' value='Help' style='width:100px' onclick='javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');' />\n";
	$strHTML .= $strOffset . "    </td>\n";
	$strHTML .= $strOffset . "  </tr>\n";
	$strHTML .= $strOffset . "  <tr>\n";
	$strHTML .= $strOffset . "    <td>Stat Types are a matter of simple math: (type * 100) + subtype.<br />\n";
	$strHTML .= $strOffset . "      Example: Stat for 'vs_heat' would be <i>type: 2</i> * 100 ( =200 ) + <i>subtype 3</i> ( =203 )<br />\n";
	$strHTML .= $strOffset . "      Above, you would only enter type = 2, and subtype = 3. The server will do the rest.<br />\n";
	$strHTML .= $strOffset . "      <br />\n";
	$strHTML .= $strOffset . "      A list of stat types/subtypes can be found in Help.\n";
	$strHTML .= $strOffset . "    </td>\n";
	$strHTML .= $strOffset . "  </tr>\n";
	$strHTML .= $strOffset . "</table>\n";
	*/

	print($strHTML);
}


function item_mod_strings($id) {
	global $eq2,$objectName;

	$table="item_mod_strings";
?>
	<table border="0" cellpadding="5">
		<tr>
			<td valign="top">
				<fieldset><legend>General</legend>
				<table width="100%" cellpadding="0" border="0">
					<tr>
						<td colspan="6">
							<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
						</td>
					</tr>
					<tr>
						<td width="45">id</td>
						<td width="45">item_id</td>
						<td width="30">index</td>
						<td width="120">mod</td>
						<td width="150">description</td>
						<td colspan="2"></td>
					</tr>

						<?php
						$query=sprintf("select * from `%s`.%s where item_id = %s ORDER BY `index`",ACTIVE_DB,$table, $id);
						$result=$eq2->RunQueryMulti($query);
						$itemModIndex = null;
						foreach ($result as $data) {
							$itemModIndex = $data['index'];
						?>
					<form method="post" name="multiForm|<?php print($data['id']); ?>">
					<tr>
						<td>
							<input type="hidden" name="objectName" value="<?= $objectName ?>" />
							<input type="hidden" name="table_name" value="<?= $table ?>" />	
							<input type="text" name="item_mod_strings|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_mod_strings|item_id" value="<?php print($data['item_id']) ?>"  style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_mod_strings|index" value="<?php print($data['index']) ?>"  style="width:30px;" />
							<input type="hidden" name="orig_index" value="<?php print($data['index']) ?>" />
						</td>
						<td>
							<input type="text" name="item_mod_strings|mod" value="<?php print($data['mod']) ?>"  style="width:120px;" />
							<input type="hidden" name="orig_mod" value="<?php print($data['mod']) ?>" />
						</td>
						<td>
							<input type="text" name="item_mod_strings|description" value="<?php print($data['description']) ?>"  style="width:150px;" />
							<input type="hidden" name="orig_description" value="<?php print($data['description']) ?>" />
						</td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" /><?php } ?></td>
					</tr>
					</form>
				<?php
				}
				?>
				<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
					<form method="post" name="sdForm|new">
					<tr>
						<td><strong>new</strong></td>
						<td>
							<input type="text" name="item_mod_strings|item_id|new" value="<?php echo $id; ?>" readonly style="width:45px;" />
						</td>
						<td>
							<input type="text" name="item_mod_strings|index|new" value="<?php echo $itemModIndex == null ? 0 : $itemModIndex + 1 ?>"  style="width:30px;" />
						</td>
						<td>
							<input type="text" name="item_mod_strings|mod|new" value=""  style="width:120px;" />
						</td>
						<td>
							<input type="text" name="item_mod_strings|description|new" value=""  style="width:150px;" />
						</td>
						<td>
							<input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" />
						</td>
						<td></td>
					</tr>
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


function item_classifications($id){
    global $eq2,$objectName;

	$table= "item_classifications";
?>
	<table border="0" cellpadding="5">
		<tr>
			<td valign="top">
				<fieldset><legend>General</legend>
				<table cellpadding="0" border="0">
					<tr>
						<td colspan="8">
							<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
						</td>
					</tr>
					<tr>
						<td width="50">id</td>
						<td width="50">item_id</td>
						<td width="30">classification</td>
						<td colspan="2">&nbsp;</td>
					</tr>
				<?php
				$query=sprintf("select * from `%s`.%s where item_id = %s ORDER BY `classification`",ACTIVE_DB,$table, $id);
				$result=$eq2->RunQueryMulti($query);
				$lastEffectIndex = null;
				foreach ($result as $data) {
					$lastEffectIndex = $data['index'];
				?>
					<form method="post" name="multiForm|<?php print($data['id']); ?>" />
					<tr>
						<td>
							<input type="text" name="item_classifications|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_classifications|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_classifications|classification" value="<?php print($data['classification']) ?>" style="width:200px;" />
							<input type="hidden" name="orig_classification" value="<?php print($data['index']) ?>" />
						</td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" /><?php } ?></td>
					</tr>
					<input type="hidden" name="objectName" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
					</form>
				<?php
				}
				?>
				<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
					<form method="post" name="sdForm|new" />
					<tr>
						<td width="50" align="center"><strong>new</strong></td>
						<td>
							<input type="text" name="item_classifications|item_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
						</td>
						<td>
							<input type="text" name="item_classifications|classifications|new" value="<?php echo $lastEffectIndex == null ? 0 : $lastEffectIndex + 1 ?>"  style="width:30px;" />
						</td>
						<td>
							<input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" />
						</td>
						<td>&nbsp;</td>
					</tr>
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
function item_effects($id) {
	global $eq2,$objectName;

	$table= "item_effects";
?>
	<table border="0" cellpadding="5">
		<tr>
			<td valign="top">
				<fieldset><legend>General</legend>
				<table cellpadding="0" border="0">
					<tr>
						<td colspan="8">
							<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
						</td>
					</tr>
					<tr>
						<td width="50">id</td>
						<td width="50">item_id</td>
						<td width="30">index</td>
						<td width="300">effect</td>
						<td width="50">percentage</td>
						<td width="30">bullet</td>
						<td colspan="2">&nbsp;</td>
					</tr>
				<?php
				$query=sprintf("select * from `%s`.%s where item_id = %s ORDER BY `index`",ACTIVE_DB,$table, $id);
				$result=$eq2->RunQueryMulti($query);
				$lastEffectIndex = null;
				foreach ($result as $data) {
					$lastEffectIndex = $data['index'];
				?>
					<form method="post" name="multiForm|<?php print($data['id']); ?>" />
					<tr>
						<td>
							<input type="text" name="item_effects|id" value="<?php print($data['id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_effects|item_id" value="<?php print($data['item_id']) ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_effects|index" value="<?php print($data['index']) ?>" style="width:30px;" />
							<input type="hidden" name="orig_index" value="<?php print($data['index']) ?>" />
						</td>
						<td>
							<input type="text" name="item_effects|effect" value="<?php print($data['effect']) ?>"  style="width:285px;" />
							<input type="hidden" name="orig_effect" value="<?php print($data['effect']) ?>" />
						</td>
						<td>
							<input type="text" name="item_effects|percentage" value="<?php print($data['percentage']) ?>"  style="width:45px;" />
							<input type="hidden" name="orig_percentage" value="<?php print($data['percentage']) ?>" />
						</td>
						<td>
							<input type="text" name="item_effects|bullet" value="<?php print($data['bullet']) ?>"  style="width:45px;" />
							<input type="hidden" name="orig_bullet" value="<?php print($data['bullet']) ?>" />
						</td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" /><?php } ?></td>
					</tr>
					<input type="hidden" name="objectName" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
					</form>
				<?php
				}
				?>
				<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
					<form method="post" name="sdForm|new" />
					<tr>
						<td width="50" align="center"><strong>new</strong></td>
						<td>
							<input type="text" name="item_effects|item_id|new" value="<?php print($id) ?>" style="width:45px;  background-color:#ddd;" readonly />
						</td>
						<td>
							<input type="text" name="item_effects|index|new" value="<?php echo $lastEffectIndex == null ? 0 : $lastEffectIndex + 1 ?>"  style="width:30px;" />
						</td>
						<td>
							<input type="text" name="item_effects|effect|new" value=""  style="width:285px;" />
						</td>
						<td>
							<input type="text" name="item_effects|percentage|new" value="100"  style="width:45px;" />
						</td>
						<td>
							<input type="text" name="item_effects|bullet|new" value="0"  style="width:45px;" />
						</td>
						<td>
							<input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" />
						</td>
						<td>&nbsp;</td>
					</tr>
					<input type="hidden" name="table_name" value="<?= $table ?>" />
					</form>
				<?php } ?>
				</table>
				</fieldset>
			</td>
			<td width='350'>
				<fieldset>
					<legend>Stats</legend>
					<?= print($eq2->GenerateItemHover($_GET['id'])); ?>
				</fieldset>
			</td>
		</tr>
	</table>
<?php
}


function item_classes($id) {
	global $eq2,$objectName,$link;

	$table="item_classes";
?>
	<table border="0" cellpadding="5">
		<tr>
			<td width="680" valign="top">
				<fieldset><legend>General</legend>
				<table width="100%" border="0" cellpadding="0">
					<tr>
						<td colspan="7">
							<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
						</td>
					</tr>
					<tr>
						<td width="55">id</td>
						<td width="55">item_id</td>
						<td width="155">class_id</td>
						<td width="155">tradeskill_class_id</td>
						<td width="55">level</td>
						<td colspan="2">&nbsp;</td>
					</tr>
				<?php
				$query=sprintf("select * from %s where item_id = %s",$table, $id);
				$result=$eq2->db->sql_query($query);
				while($data=$eq2->db->sql_fetchrow($result)) {
				?>
					<form method="post" name="multiForm|<?php print($data['id']); ?>" />
					<tr>
						<td>
							<input type="text" name="item_classes|id" value="<?php print($data['id']) ?>" style="width:50px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
						</td>
						<td>
							<input type="text" name="item_classes|item_id" value="<?php print($data['item_id']) ?>" style="width:50px; background-color:#ddd;" readonly />
							<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
						</td>
						<td>
							<select name="item_classes|class_id" style="width:150px;">
								<?php 
								foreach($eq2->eq2PlayableClasses as $key=>$val) {
									$selected = ( $key == $data['class_id'] ) ? " selected" : "";
									echo "<option value='$key'$selected>$val</option>";
								}
								?>
							</select>
							<input type="hidden" name="orig_class_id" value="<?php print($data['class_id']); ?>" />
						</td>
						<td>
							<select name="item_classes|tradeskill_class_id" style="width:150px;">
								<option value="255"<?php if( $data['tradeskill_class_id']==255 ) print(" selected") ?>>N/A</option>
								<option value="1"<?php if( $data['tradeskill_class_id']==1 ) print(" selected") ?>>Tradeskiller</option>
							</select>
							<input type="hidden" name="orig_tradeskill_class_id" value="<?php print($data['tradeskill_class_id']); ?>" />
						</td>
						<td>
							<input type="text" name="item_classes|level" value="<?php print($data['level']) ?>"  style="width:50px;" />
							<input type="hidden" name="orig_level" value="<?php print($data['level']) ?>" />
						</td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" /><?php } ?></td>
						<td><?php if($eq2->CheckAccess(G_DEVELOPER)) { ?><input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" /><?php } ?></td>
					</tr>
					<input type="hidden" name="objectName" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
					</form>
				<?php
				}
				?>
				<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
					<form method="post" name="sdForm|new" />
					<tr>
						<td width="55" align="center"><strong>new</strong></td>
						<td>
							<input type="text" name="item_classes|item_id|new" value="<?php print($id) ?>" style="width:50px; background-color:#ddd;" readonly />
						</td>
						<td>
							<select name="item_classes|class_id|new" style="width:150px;">
								<option>---</option>
								<?php 
								foreach($eq2->eq2PlayableClasses as $key=>$val) {
									echo "<option value='$key'>$val</option>";
								}
								?>
							</select>
						</td>
						<td>
							<select name="item_classes|tradeskill_class_id|new" style="width:150px;">
								<option value="255">N/A</option>
								<option value="1">Tradeskiller</option>
							</select>
						</td>
						<td>
							<input type="text" name="item_classes|level|new" value="0"  style="width:50px;" />
						</td>
						<td>
							<input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" />
						</td>
					</tr>
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


function item_appearances($id) {
	global $eq2,$objectName;

	$table="item_appearances";	
?>
	<fieldset style="display:inline">
		<legend>General</legend>
		<span class="heading">Editing: <?= $objectName ?></span>
		</br></br>
		<form method="post" class="itemAppGridForm" name="multiForm" ?>
		<div class="itemAppGrid">
			<span>id</span>
			<span>item_id</span>
			<span>equip_type</span>
			<span>red</span>
			<span>green</span>
			<span>blue</span>
			<span>highlight_red</span>
			<span>highlight_green</span>
			<span>highlight_blue</span>
			<span style="grid-column-start:span 2"></span>

			<?php
				$query = sprintf("select * from `%s`.%s where item_id = %s", ACTIVE_DB, $table, $id);
				$result = $eq2->RunQueryMulti($query);
			?>
			<?php if (count($result)) : ?>
			<?php foreach($result as $data) : ?>
				<div>
					<input type="text" name="item_appearances|id" value="<?php print($data['id']) ?>"  style="width:55px;  background-color:#ddd;" readonly />
					<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
					<input type="hidden" name="objectName" value="<?= $objectName ?>" />
					<input type="hidden" name="table_name" value="<?= $table ?>" />
				</div>
				<div>
					<input type="text" name="item_appearances|item_id" value="<?php print($data['item_id']) ?>" style="width:55px;  background-color:#ddd;" readonly />
					<input type="hidden" name="orig_item_id" value="<?php print($data['item_id']) ?>" />
				</div>
				<div>
					<input type="text" name="item_appearances|equip_type" value="<?php print($data['equip_type']) ?>"  style="width:50px;" />
					<input type="hidden" name="orig_equip_type" value="<?php print($data['equip_type']) ?>" />
				</div>
				<div>
					<input type="text" name="item_appearances|red" value="<?php print($data['red']) ?>"  style="width:50px;" />
					<input type="hidden" name="orig_red" value="<?php print($data['red']) ?>" />
				</div>
				<div>
					<input type="text" name="item_appearances|green" value="<?php print($data['green']) ?>"  style="width:50px;" />
					<input type="hidden" name="orig_green" value="<?php print($data['green']) ?>" />
				</div>
				<div>
					<input type="text" name="item_appearances|blue" value="<?php print($data['blue']) ?>"  style="width:50px;" />
					<input type="hidden" name="orig_blue" value="<?php print($data['blue']) ?>" />
				</div>
				<div>
					<input type="text" name="item_appearances|highlight_red" value="<?php print($data['highlight_red']) ?>"  style="width:50px;" />
					<input type="hidden" name="orig_highlight_red" value="<?php print($data['highlight_red']) ?>" />
				</div>
				<div>
					<input type="text" name="item_appearances|highlight_green" value="<?php print($data['highlight_green']) ?>"  style="width:50px;" />
					<input type="hidden" name="orig_highlight_green" value="<?php print($data['highlight_green']) ?>" />
				</div>
				<div>
					<input type="text" name="item_appearances|highlight_blue" value="<?php print($data['highlight_blue']) ?>"  style="width:50px;" />
					<input type="hidden" name="orig_highlight_blue" value="<?php print($data['highlight_blue']) ?>" />
				</div>
				<?php if($eq2->CheckAccess(G_DEVELOPER)) : ?>
				<div>
					<input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" />
				</div>
				<div>
					<input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" />
				</div>
				<?php else : ?>
				<div></div><div></div>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php elseif($eq2->CheckAccess(G_DEVELOPER)) : ?>
				<span width="55"><strong>new</strong></span>
					<span>
						<input type="text" name="item_appearances|item_id|new" value="<?php print($id) ?>" style="width:55px;  background-color:#ddd;" readonly />
					</span>
					<span>
						<input type="text" name="item_appearances|equip_type|new" value=""  style="width:50px;" />
					</span>
					<span>
						<input type="text" name="item_appearances|red|new" value="0"  style="width:50px;" />
					</span>
					<span>
						<input type="text" name="item_appearances|green|new" value="0"  style="width:50px;" />
					</span>
					<span>
						<input type="text" name="item_appearances|blue|new" value="0"  style="width:50px;" />
					</span>
					<span>
						<input type="text" name="item_appearances|highlight_red|new" value="0"  style="width:50px;" />
					</span>
					<span>
						<input type="text" name="item_appearances|highlight_green|new" value="0"  style="width:50px;" />
					</span>
					<span>
						<input type="text" name="item_appearances|highlight_blue|new" value="0"  style="width:50px;" />
					</span>
					<span style="grid-column-start:span 2">
						<input type="submit" name="iInsert" value="Insert" style="font-size:10px; width:60px" />
						<input type="hidden" name="cmd" value="insert"/>
						<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
						<input type="hidden" name="table_name" value="<?= $table ?>" />
					</span>
			<?php endif; ?>
		</div>
		</form>
	</fieldset>
<?php
}


function items($id) {
	global $eq2, $objectName, $eq2Items;

	$table="items";
	$query=sprintf("select * from `%s`.%s where id = %d",ACTIVE_DB,$table,$id);
	$result=$eq2->db->sql_query($query);
	$data=$eq2->db->sql_fetchrow($result);
	
	if( !empty($data['lua_script']) && !preg_match("/ItemScripts\//i", $data['lua_script']) )
		$error_message = "Invalid lua_script entry. Use the proper format!";

	?>
<form method="post" name="ItemsForm">
<table border="0" cellpadding="5">
  <tr>
    <td valign="top">
      <?php $eq2Items->PrintItemGeneralFields($data); ?>
    </td>
	<td valign="top">
      <fieldset><legend>Slots</legend>
	  	<?php $slots = $data['slots']; ?>
	    <input type="hidden" name="orig_slots" value="<?php echo $slots; ?>" />
		<div id="itemSlotsGrid">
			<?php foreach ($eq2->eq2EquipSlots as $val=>$slotName) : ?>
			<table>
			<tr>
				<td align="right"><?php echo $slotName; ?>:</td>
				<td>
					<?php $eq2->GenerateBlueCheckbox(sprintf('items|slots|%s', $val), $slots & $val); ?>
				</td>
			</tr>
			</table>
			<?php endforeach; ?>
		</div>
      </fieldset>
	</td>
	<td valign="top" rowspan="2">
      <?php $eq2Items->DisplayItemsToggles($data); ?>
	  <script>document.getElementsByName('items|crafted')[0].setAttribute("onchange", "ReloadItemIcon();UpdateItemTierTag();");</script>
    </td>
  </tr>
	<tr>
		<td valign="top">
        <fieldset>
			<legend>Adventure Classes</legend>
			<table id="itemClassesTable">
				<?php $advClasses = $data['adventure_classes']; ?>
				<tr id="itemHighlightedRow">
					<td align="right">Commoner:</td>
					<td>
						<?php $eq2->GenerateBlueCheckbox("items|adventure_classes|0", $advClasses & 1); ?>
					</td>
					<td align="right">adventure_classes:</td>
					<td>
						<?php 
							echo $advClasses;
							printf('<input type="hidden" name="orig_adventure_classes" value="%s" />', $advClasses);
						?>
					</td>
				</tr>
				<tr>
					<?php foreach($eq2->eq2ArchetypeSortedClasses as $archetype=>$classes):?>
						<?php if ($archetype == "Commoner") continue; ?>
						<td valign="top">
						<table>
						<?php foreach($classes as $classID=>$className):?>
							<tr>
								<td align="right"><?php echo $className;?>:</td>
								<td>
								<?php 
								$eq2->GenerateBlueCheckbox(sprintf('items|adventure_classes|%s', $classID),  $advClasses & (1 << $classID));
								?>
								</td>
							</tr>		
						<?php endforeach;?>
						</table>
						</td>
					<?php endforeach;?>
				</tr>
      		</table>
      </fieldset>
	</td>
    <td valign="top">
	<fieldset style="display:inline"><legend>Tradeskill Classes</legend>
	<?php $tsClasses = $data['tradeskill_classes']; ?>
			<input type="hidden" name="orig_tradeskill_classes" value="<?=$tsClasses?>"/>
			<div id="itemTSClassesGrid">
				<?php foreach($eq2->eq2ArchetypeSortedTSClasses as $arch=>$classes) : ?>
				<?php foreach ($classes as $classID=>$className) : ?>
				<?php if ($className == "Unskilled") : ?>
					<strong style="background:#eee">value: <?= $tsClasses ?></strong>
				<?php endif; ?>
				<table>
				<tr>
					<td align="right"><?= $className ?>:</td>
					<td>
						<?php 
						$eq2->GenerateBlueCheckbox(sprintf('items|tradeskill_classes|%s', $classID),  $tsClasses & (1 << $classID));
						?>
					</td>
				</tr>
				</table>
				<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
      	</fieldset>
    	<fieldset style="display:inline"><legend>Adornments</legend>
			<div id="itemAdornmentsGrid">
				<?php for ($numAdorns = 6, $i = 1; $i <= $numAdorns; $i++) : ?>
				<table>
				<tr>
					<td align="right">Slot<?php echo $i;?>:</td>
					<td>
						<?php 
						$slotName = "adornment_slot".$i;
						$eq2Items->GenerateAdornmentDropdown($slotName, $data);
						printf('<input type="hidden" name="orig_%s" value="%s" />', $slotName, $data[$slotName]);
						?>
					</td>
				</tr>
				</table>
				<?php endfor; ?>
			</div>
      	</fieldset>
    </td>
	</tr>
	<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
	<tr>
		<td colspan="3" align="center">
			<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
			<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />						
			<input type="hidden" name="cmd" value="update" />
			<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
			<input type="hidden" name="table_name" value="<?= $table ?>" />
		</td>
	</tr>
	<?php } ?>
</table>
</form>
<?php
}

function item_details_skill($item_id) {
	global $eq2,$objectName;

	$table="item_details_skill";
	$query=sprintf("select * from `%s`.%s where item_id = %d",ACTIVE_DB,$table,$item_id);
	$data=$eq2->RunQuerySingle($query);

	if (isset($data)) {
		$id = $data['id'];
		$spell_id = $data['spell_id'];
		$spell_tier = $data['spell_tier'];
	}
	else {
		$spell_id = 0;
		$spell_tier = 1;
	}
	?>
	<form method="post" name="Form1">
	<table border="0" cellpadding="5">
		<tr>
			<td width="480" valign="top">
				<fieldset><legend>General</legend>
				<table width="100%" cellpadding="0" border="0">
					<tr>
						<td colspan="3">
							<span class="heading">Editing: <?= $objectName ?></span><br />&nbsp;
						</td>
					</tr>
					<?php if (!isset($data)) : ?>
					<tr>
						<td colspan="4">No data found for this item. You may insert a new record if necessary.</td>
					</tr>
					<?php endif; ?>
					<tr>	
						<td width="50%" align="right">id:</td>
						<td>
							<?php if (isset($data)) : ?>
							<input type="text" name="item_details_skill|id" value="<?php echo $id; ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_id" value="<?php echo $id; ?>" />
							<?php else : ?>
							<strong>new</strong>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td align="right">item_id:</td>
						<td>
							<input type="text" name="item_details_skill|item_id" value="<?php echo $item_id; ?>" style="width:45px;  background-color:#ddd;" readonly />
							<input type="hidden" name="orig_item_id" value="<?php echo $item_id; ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">spell_id:</td>
						<td>
							<input type="text" name="item_details_skill|spell_id" value="<?php echo $spell_id; ?>" style="width:45px;" />
							<input type="hidden" id="spellid" name="orig_spell_id" value="<?php echo $spell_id; ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">spell_tier:</td>
						<td>
							<input type="text" name="item_details_skill|spell_tier" value="<?php echo $spell_tier; ?>" style="width:45px;" />
							<input type="hidden" name="orig_spell_tier" value="<?php echo $spell_tier; ?>" />
						</td>
					</tr>
				</table>
				</fieldset>
			</td>
		</tr>
		<script>
			function RedirToSpell() {
				let e = document.getElementById("spellid");
				location = "spells.php?id=" + e.value;
			}
		</script>
		<?php if($eq2->CheckAccess(G_DEVELOPER)) : ?>
		<tr>
			<td colspan="4" align="center">
				<?php if (isset($data)) : ?>
				<input type="button" value="Go To Spell" onclick="RedirToSpell()" style="width:100px;" />&nbsp;
				<input type="hidden" name="cmd" value="update" />
				<input type="submit" name="iUpdate" value="Update" style="width:100px;" />&nbsp;
				<?php else : ?>
				<input type="hidden" name="cmd" value="insert" />
				<input type="submit" name="iInsert" value="Insert" style="width:100px;" />&nbsp;
				<?php endif; ?>
				<input type="button" value="Help" style="width:100px" onclick="javascript:window.open('help.php#items','help','resizable,width=480,height=640,left=10,top=75,scrollbars=yes');" />
				<input type="hidden" name="orig_object" value="<?= $objectName ?>" />
				<input type="hidden" name="table_name" value="<?= $table ?>" />
			</td>
		</tr>
		<?php endif; ?>
	</table>
	</form>

	<?php
}

function GetCleanItemScriptName($name)
{
	// sanitize
	$var = preg_replace("/[^a-zA-Z0-9]+/", "", html_entity_decode($name, ENT_QUOTES));
	return sprintf("%s.lua", $var);
}

function item_script($id) {
	global $eq2, $eq2Items;
	$strHTML = "";
		
	$query=sprintf("select id,name,lua_script from %s.items where id = %s", ACTIVE_DB, $id);
	$data = $eq2->RunQuerySingle($query);
	$script_name = GetCleanItemScriptName($data['name']);
	$script_relative_path = ( !empty($data['lua_script']) ) ? $data['lua_script'] : "ItemScripts/".$script_name;

	$strHTML .= $eq2->DisplayScriptEditor($script_relative_path, $data['name'], sprintf("%s|%s", $data['name'], $_GET['id']), "items", 'none');
	print($strHTML);
}

function getItemSearchResults($query) {
	global $eq2, $itemResults, $eq2Items;
	
	if($result=$eq2->RunQueryMulti($query)) {
		$i=0;
		foreach ($result as $items) {
			$rowClass = ( $i % 2 ) ? " bgcolor=\"#dddddd\"" : "";
			$skillString = $eq2->getSkillName($items['skill_id_req']);
			$statsString = $eq2Items->getItemStats($items['id']);
			
			$icon = "";
			$itemResults.=sprintf("<tr%s>",$rowClass);
			$itemResults.=sprintf("<td>&nbsp;%d</td>",$items['id']);
			$itemResults.=sprintf('<td align="center"><img src="%s"/></td>', $eq2Items->GetItemIconLink($items));
			$itemResults.=sprintf("<td nowrap>%s <a href=\"items.php?show=items&type=%s&id=%s\"><strong>%s</strong></a></td>",$icon,strtolower($items['item_type']),$items['id'],$items['name']);

			$slots = $eq2Items->GetEquipSlotsStringListFromBitmask($items['slots']);
			$bFirst = true;
			$slotsString = "";
			foreach ($slots as $slot) {
				if ($bFirst) $bFirst = false;
				else $slotsString .= '<br/>';
				$slotsString .= $slot;
			}

			$itemResults.=sprintf("<td valign=\"middle\" nowrap align=\"center\">%s</td>", $slotsString);
			$itemResults.=sprintf("<td align=\"center\">%s</td>",$items['level']);
			$itemResults.=sprintf("<td align=\"center\">%s</td>",$items['ad_level']);
			$itemResults.=sprintf("<td align=\"center\">%s</td>",$items['tier']);
			$itemResults.=sprintf("<td align=\"center\" nowrap>%s</td>",$items['item_type']);
			$itemResults.=sprintf("<td nowrap align=\"center\">%s</td>",$skillString);
			$itemResults.=sprintf("<td align=\"center\">%s</td>",$statsString);
			$itemResults.=sprintf("</tr>");
			$i++;
		}
	}
	return $i;
}

function item_pvplink_redirect($id) {
	global $eq2;

	$data = $eq2->RunQuerySingle(sprintf('SELECT base_item, pvp_item FROM %s.item_pvp_link WHERE base_item = %s OR pvp_item = %s'
		,ACTIVE_DB, $id, $id));

	if ($data) {
		$tab = $_GET['tab'];
		if ($tab == "pvp")
			$other = $data['pvp_item'];
		else
			$other = $data['base_item'];
		$search = sprintf("items.php?show=items&type=%s&id=%s", $_GET['type'], $other);
		header("Location: ".$search);
		exit;
	}
}

include("footer.php");
?>

