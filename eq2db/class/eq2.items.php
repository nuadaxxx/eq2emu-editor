<?php

class eq2Items 
{

	public function __construct() {
		natcasesort($this->emuItemToggles['items']);
		
	}

    var $eq2ItemTypes = array(
		0 => "Normal",
		1 => "Weapon",
		2 => "Ranged",
		3 => "Armor",
		4 => "Shield",
		5 => "Bag",
		6 => "Skill",
		7 => "Recipe",
		8 => "Food",
		9 => "Bauble",
	    10 => "House",
	    11 => "Thrown",
	    12 => "House Container",
	    13 => "Adornment",
	    14 => "Book",
	    15 => "Pattern",
        16 => "Scroll",
	    17 => "Armor Set"
	);

	var $eq2ItemStatTypes = array();
	var $eq2ItemStatSubTypes = array();

    public function populateItemTypeArrays()
	{
		global $eq2;

		//POPULATE TYPE
		$query = "SELECT value, name FROM `eq2meta` WHERE type=6 AND subtype='stat_type';";
		$data = $eq2->RunQueryMulti($query);
		foreach($data as $row)
		{
			$this->eq2ItemStatTypes[$row['value']] = $row['name'];
		}

		//POPULATE SUBTYPE
		$query = "SELECT value, name FROM `eq2meta` WHERE type=6 AND subtype='stat_subtype';";
		$data = $eq2->RunQueryMulti($query);
		foreach($data as $row)
		{
			$this->eq2ItemStatSubTypes[$row['value']] = $row['name'];
		}
	}

    var $emuItemTypeIDRanges = 
        array('Normal'=>1000, 'Marketplace'=>20000, 'Profile'=>20100,
        'Decoration'=>20200, 'House Container'=>20300, 'Thrown'=>20400, 'Bag'=>20600,
        'Book'=>21000,'Item Set'=>22000, 'Pattern Set'=>23000, 'Dungeon Maker'=>24000,
        'Adornment'=>25000, 'Recipe'=>30000, 'Food'=>35000, 'Ranged'=>40000, 'Bauble'=>45000, 'Shield'=>50000,
        'House'=>60000, 'Weapon'=>70000, 'Scroll'=>100000, 'Armor'=>130000);

    var $emuItemTables =
    array(
        'Marketplace'=>'item_details_marketplace',
        'Decoration'=>'item_details_decoration',
        'House Container'=>'item_details_house_container',
        'Thrown'=>'item_details_thrown',
        'Bag'=>'item_details_bag',
        'Book'=>'item_details_book',
        'Pattern Set'=>'item_details_pattern',
        'Adornment'=>'item_details_adornments',
        'Recipe'=>'item_details_recipe',
        'Food'=>'item_details_food',
		'Ranged'=>'item_details_range',
        'Bauble'=>'item_details_bauble',
        'Shield'=>'item_details_shield',
        'House'=>'item_details_house',
        'Weapon'=>'item_details_weapon',
        'Scroll'=>'item_details_skill',
        'Armor'=>'item_details_armor');

	var $emuItemToggles =
	array('items'=> array(
		'show_name', 'attuneable', 'artifact', 'lore', 'temporary',
		'notrade', 'novalue', 'nozone', 'nodestroy', 'crafted',
		'good_only', 'evil_only', 'stacklore', 'lore_equip',
		'no_transmute', 'CURSED_flags_32768', 'ornate', 'heirloom',
		'appearance_only','unlocked','norepair','etheral','refined',
		'usable','collectable','body_drop','display_charges','harvest',
		'no_salvage','indestructable','no_experiment','house_lore',
		'flags2_4096','building_block','free_reforge','infusable', 'no_buy_back')
	);

	var $eq2ItemAdornSlotTypes =
	array(255=>'None',0=>'White',1=>'Red',2=>'Blue',3=>'Yellow',4=>'Green',5=>'Purple',6=>'Cyan',7=>'Temporary'
	,8=>'Orange',9=>'Turquoise',10=>'Black');

	public function BuildLUAFunction($type)
	{
		global $eq2;
		
		$ret = NULL;

		switch($type)
		{
			case "s1.Item1":
				$ret = "\r\n" .
							 "function cast(Caster, Target)\r\n" .
							 "\r\n" .
							 "end\r\n";
				break;

			case "s1.Item2":
				$ret = "\r\n" .
							 "function cast(Caster, Target, MinVal, MaxVal)\r\n" .
							 "    if MaxVal ~= nil and MinVal < MaxVal then\r\n" .
							 "        ModifyHP(Target, math.random(MinVal, MaxVal))\r\n" .
							 "    else\r\n" .
							 "        ModifyHP(Target, MinVal)\r\n" .
							 "    end\r\n" .
							 "end\r\n";
				break;

			case "s1.Item3":
				$ret = "\r\n" .
							 "function cast(Caster, Target, DmgType, MinVal, MaxVal)\r\n" .
							 "    if MaxVal ~= nil and MinVal < MaxVal then\r\n" .
							 "        SpellDamage(Target, DmgType, math.random(MinVal, MaxVal))\r\n" .
							 "    else\r\n" .
							 "        SpellDamage(Target, DmgType, MinVal)\r\n" .
							 "    end\r\n" .
							 "end\r\n";
				break;
				
			case "s1.Item3":
				$ret = "\r\n" .
							 "function cast(Caster, Target, DoTType, MinVal, MaxVal)\r\n" .
							 "    if MaxVal ~= nil and MinVal < MaxVal then\r\n" .
							 "        SpellDamage(Target, DoTType, math.random(MinVal, MaxVal))\r\n" .
							 "    else\r\n" .
							 "        SpellDamage(Target, DoTType, MinVal)\r\n" .
							 "    end\r\n" .
							 "end\r\n" .
							 "\r\n" .
							 "function tick(Caster, Target, DoTType, MinVal, MaxVal)\r\n" .
							 "    if MaxVal ~= nil and MinVal < MaxVal then\r\n" .
							 "        SpellDamage(Target, DoTType, math.random(MinVal, MaxVal))\r\n" .
							 "    else\r\n" .
							 "        SpellDamage(Target, DoTType, MinVal)\r\n" .
							 "    end\r\n" .
							 "end\r\n";
				break;
				
			case "s1.Item4":
				$ret = "    AddSpellBonus(Target, 600, BonusAmt)\n";
				break;
				
			case "s1.Item5":
				$ret = "    AddSkillBonus(Target, GetSkillIDByName(\"Slashing\"), SkillAmt)\n";
				break;
				
			case "s1.Item6":
				$ret = "    AddControlEffect(Target, 1)\n";
				break;
				
			case "s1.Item7":
				$ret = "\r\n" .
							 "function tick(Caster, Target)\r\n" .
							 "\r\n" .
							 "end\r\n";
				break;
				
			case "s1.Item8":
				$ret = "\r\n" .
							 "function remove(Caster, Target)\r\n" .
							 "\r\n" .
							 "end\r\n";
				break;
				
			case "s2.Item1":
				$ret = "    RemoveSpellBonus(Target)\n";
				break;
				
			case "s3.Item1":
				$ret = "    RemoveSkillBonus(Target)\n";
				break;
				
			case "s3.Item2":
				$ret = "    RemoveControlEffect(Target, 1)\n";
				break;
				
			case "s3.Item3":
				$ret = "    Interrupt(Target)\n";
				break;
				
			case "s3.Item4":
				$ret = "    Say(Caster, \"Say What?\")\n";
				break;
				
			case "s4.Item1":
				$ret = "\r\n" .
							 "function cast(Caster, Target)\r\n" .
							 "    Say(Target, \"Hah, nice try! That's not implemented yet!\")\r\n" .
							 "end\r\n";
				break;
				
			case "s4.Item2":
				$ret = "\r\nfunction cast(Caster, Target";
							 
				$sql = sprintf("SELECT description, bullet FROM `".ACTIVE_DB."`.spell_display_effects WHERE spell_id = %s and tier = 1 GROUP BY `index`;", $_GET['id']);
				if( !$result = $eq2->db->sql_query($sql) )
					die("Error while fetching spell_display_effects in %s" . __FUNCTION__);
					
				while( $data = $eq2->db->sql_fetchrow($result) )
				{
					$myArr = array();
					$data_values = array();
					
					// let the parsing begin!
					$myArr = explode(" ", $data['description']);
					$direction = ( in_array("Decreases", $myArr) > 0 ) ? "-" : "";
			
					foreach($myArr as $key)
					{
						if( intval($key) )
						{
							$val = $direction . $key;
							$data_values[] = preg_replace("/\%/", "", $val);
						}
						else
						{
							foreach($eq2->eq2DamageTypes as $id=>$effect) 
							{
								if( strtolower($key) == strtolower($effect) )
									$dmgType = $key;
							}
						}
					}

				}
				
				if( strlen($dmgType) > 0 )
					$ret .= ", Param1";
				
				$params = count($data_values);
				for( $i = 2; $i <= $params+1; $i++)
					$ret .= ", Param" . $i;
				
				$ret .= ")\r\n    \r\nend\r\n";
				break;
		}
		
		return $ret;
	}

    function getItemStats($id) 
	{
        global $eq2;

		$eq2->SQLQuery = sprintf("select * from `".ACTIVE_DB."`.item_stats where item_id = %lu", $id);
        
        $stats = "";
		foreach ($eq2->RunQueryMulti() as $data) {
			$stat_val = $data['type'] * 100 + $data['subtype'];
			if( $data['value'] > 0 )
			{
				// normal +stats
				if(!isset($stat_value)){
					continue;
				}
				printf("DEBUG: ".$data['value']." 2: ".$stat_val)."\n";
				$stats .= sprintf(" +%d %s", $data['value'], $this->eq2ItemStats[$stat_val]);
			}
			else
			{
				// text stats?
				$stats .= sprintf(" %s",$data['text']);
			}
		}
		return $stats;
	}

    public function DisplayAddNewItemPage()
	{
		global $itemInsertError;

		if($itemInsertError ?? false) { 
			if ($itemInsertError == 1) $errtext = "You must provide a name for your item!";
			else $errtext = "Error inserting your new item!";
			printf('<span class="heading" style="color:red">%s</span>', $errtext);
			echo "</br>";
		}
		?>

		<form method="post" name="AddItem">
		<fieldset style="width:320px;">
		<legend>Item Create</legend>
		<table cellpadding="5">
			<thead>
				<tr>
					<td>
						<span class="heading">Basic Details For Your New Item</span>
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td align="right">
						<label>Item Name:</label>
						<input type="text" class="box" name="itemName"/>
					</td>
				</tr>
				<tr>
					<td align="center">
						<label>Item Type:</label>
						<select name="itemType">
							<?php 
							$this->DisplayItemTypeOptions("Normal");
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td align="center">
						<input type="submit" name="cmd" value="Create"/>
					</td>
				</tr>
			</tbody>
		</table>
		</fieldset>
		</form>

		<?php
	}

    public function DisplayItemTypeOptions($selectedType) {
        foreach($this->eq2ItemTypes as $key)
		{
			$selected = ($selectedType == $key) ? ' selected' : '';
			printf("<option%s>%s</option>\r\n",$selected, $key);
		}
    }

    function CreateNewItem() {
		global $eq2, $itemInsertError;

		$name = $_POST['itemName'] ?? "";

        if ($name == "") {
            $itemInsertError = 1;
            return;
        }

        $type = $_POST['itemType'];

        $maxRangeID = 1999999;

        reset($this->emuItemTypeIDRanges);
        for (;key($this->emuItemTypeIDRanges);next($this->emuItemTypeIDRanges)) {
            $t = key($this->emuItemTypeIDRanges);

            if ($t == $type) {
                //We found the start of our range, figure out where this range ends
                //Also add 1 million for any items we create manually
                $minRangeID = current($this->emuItemTypeIDRanges) + 1000000;
                next($this->emuItemTypeIDRanges);
                if (key($this->emuItemTypeIDRanges)) {
                    $maxRangeID = current($this->emuItemTypeIDRanges) - 1 + 1000000;
                }
                break;
            }
        }
        //Just incase another function accesses this array
        reset($this->emuItemTypeIDRanges);

		$eq2->BeginSQLTransaction();
		$eq2->RunQuery(true, "LOCK TABLE `".ACTIVE_DB."`.`items` WRITE;");

		$query = sprintf('SELECT MAX(id) + 1 as newid FROM `%s`.`items` i WHERE i.id BETWEEN %s AND %s',
		ACTIVE_DB, $minRangeID, $maxRangeID);

		$row = $eq2->RunQuerySingle($query);
		$nextID = $row['newid'] ?? $minRangeID;

		$query = sprintf("INSERT INTO `%s`.`items` (`id`, `name`, `item_type`) VALUES (%s,'%s','%s')",
		    ACTIVE_DB, $nextID, $eq2->SQLEscape($name), $type);

		$success = false;

		if ($eq2->RunQuery(true, $query) == 1) {
            $success = true;

            $secondaryTable = $this->emuItemTables[$type] ?? NULL;

            if (isset($secondaryTable)) {
                $q = $type != "Scroll" ? "INSERT INTO `%s`.%s (item_id) VALUES (%s)" : "INSERT INTO `%s`.%s (item_id, soe_spell_crc) VALUES (%s, 0)";

                $query = sprintf($q, ACTIVE_DB, $secondaryTable, $nextID);
                $success = $eq2->RunQuery(true, $query) == 1;
            }

            if ($success) {
                $eq2->SQLTransactionCommit();
            }
		}

		if (!$success) {
			$eq2->SQLTransactionRollback();
		}

		$eq2->RunQuery(true, "UNLOCK TABLES;");

		if ($success) {
			$search = sprintf("items.php?show=items&type=%s&id=%s", strtolower($type), $nextID);
			header("Location: ".$search);
			//We're redirecting at this point, go ahead and exit
			exit;
		}
		else {
			$itemInsertError = 2;
		}
	}

	function DisplayItemsToggles($item) {
		global $eq2;
		?>

		<fieldset style="display:inline">
			<legend>Toggles</legend>
			<div id="itemTogglesGrid">
				<?php foreach ($this->emuItemToggles['items'] as $field) : ?>
					<table>
						<tr>
							<td>
							<?php printf('<label>%s:</label>', $field); ?>						
							</td>
							<td>
							<?php $eq2->GenerateBlueCheckbox('items|'.$field, $item[$field] == 1); ?>
							<input type="hidden" name="<?php printf("orig_%s", $field) ?>" value="<?php echo $item[$field]; ?>" />
							</td>
						</tr>
					</table>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<?php
	}

	function HandleCheckBoxes() {
		$page = $_GET['tab'] ?? "items";

		if($_GET['show'] == 'items' && $page == "items") {
			global $eq2;
			//Handle the toggle checkboxes
			foreach ($this->emuItemToggles['items'] as $field) {
				$name = sprintf("items|%s", $field);

				$val = isset($_POST[$name]) ? 1 : 0;
				$_POST[$name] = $val;
			}

			//Handle the bitmask boxes
			$slots = 0;
			$classes = 0;
			$tsClasses = 0;
			foreach($_POST as $key=>$val) 
			{
				$myArray = explode("|",$key);
				if (count($myArray) < 3) {
					//Orig values
					continue;
				}
				else if($myArray[1]=="slots") 
				{
					$slots |= intval($myArray[2]);
					$_POST[$key] = NULL; // delete form value so it doesn't repeat in update
				}
				// now do Classes
				else if($myArray[1]=="adventure_classes") 
				{
					$classes |= 1 << intval($myArray[2]);
					$_POST[$key] = NULL; // delete form value so it doesn't repeat in update
				}
				else if ($myArray[1] == "tradeskill_classes")
				{
					$tsClasses |= 1 << intval($myArray[2]);
					$_POST[$key] = NULL; // delete form value so it doesn't repeat in update
				}
			}
			$_POST['items|slots'] = $slots; // set fake slots form var for update
			$_POST['items|adventure_classes'] = $classes; // set fake adventure_classes form var for update
			$_POST['items|tradeskill_classes'] = $tsClasses;
		}
	}

	function GetItemIconLink($item, $classicIcon=false) {
		$field = "icon";
		if($classicIcon) {
			$field = "classic_icon";
		}
		return sprintf('eq2Icon.php?type=item&id=%s&tier=%s%s', 
		$item[$field], $item['tier'], $item['crafted'] ? "&crafted" : "");
	}

	function GetEquipSlotsStringListFromBitmask($slots) {
		global $eq2;

		$ret = array();

		foreach ($eq2->eq2EquipSlots as $mask=>$name) {
			if ($mask & $slots) {
				$ret[] = $name;
			}
		}

		return $ret;
	}

	function GetTabArray() {
		$ret = array('items'=>'Item', 'item_appearances'=>'Appearances', 'item_effects'=>'Effects',
		'item_stats'=>'Stats', 'item_mod_strings'=>'String Mods', 'item_classifications'=>'Classifications', 'item_script'=>'Script');

		switch($_GET['type'] ?? "") { 
			case "armor": $ret['item_details_armor'] = 'Armor'; break;
			case "bag": $ret['item_details_bag'] = 'Bag'; break;
			case "bauble": $ret['item_details_bauble'] = 'Bauble'; break;
			case "food": $ret['item_details_food'] = 'Provision'; break;
			case "ranged": $ret['item_details_range'] = 'Ranged'; break;;
			case "shield": $ret['item_details_shield'] = 'Shield'; break;
			case "thrown": $ret['item_details_thrown'] = 'Ammo'; break;
			case "weapon": $ret['item_details_weapon'] = 'Weapon'; break;
			case "house": $ret['item_details_house'] = 'House Item'; break;
			case "house container": $ret['item_details_house_container'] = 'House Container'; break;
			case "recipe"		: 
				$ret['item_details_recipe'] = 'Recipe'; 
				$ret['item_details_recipe_items'] = 'Recipe Items'; 
				break;
			case "scroll":
				$ret['item_details_skill'] = 'Spell Scroll';
				break;
			case "book":
				$ret['item_details_book'] = 'Book';
				$ret['item_details_house'] = 'House Item';
				break;
		}

		global $eq2;

		$id = $_GET['id'];

		$data = $eq2->RunQuerySingle(sprintf('SELECT base_item, pvp_item FROM %s.item_pvp_link WHERE base_item = %s OR pvp_item = %s'
		,ACTIVE_DB, $id, $id));

		if ($data) {
			if ($data['base_item'] == $id) {
				$ret['pvp'] = "PVP";
			}
			else {
				$ret['base'] = 'Base';
			}
		}

		return $ret;
	}

	function GenerateAdornmentDropdown($slotName, $item) {
		$val = $item[$slotName];
		printf('<select name="items|%s">', $slotName);
		foreach($this->eq2ItemAdornSlotTypes as $type=>$name) {
			printf('<option value="%s"%s>%s</option>', $type, $val == $type ? " selected" : "", $name);
		}
		echo '</select>';
	}

	function GetItemType($id) {
		global $eq2;

		$res = $eq2->RunQuerySingle(sprintf('SELECT LOWER(`item_type`) as "item_type" FROM %s.items WHERE id = %s', ACTIVE_DB, $id));

		return $res['item_type'];
	}

	function PrintItemGeneralFields($data) {
		global $eq2, $objectName;
		$eq2Items = $this;
		?>
		<fieldset>
	<legend>General</legend>
	<table border="0">
		<tr>
			<td colspan="4"> <span class="heading">Editing: <?=$objectName ?></span>&nbsp;&nbsp;&nbsp;
				<?php (isset($error_message)) ? printf("<font color='red'><b>%s</b></font>", $error_message) : "" ?>
					<br />
			</td>
			<td colspan="2" align="right">
				<a href="http://census.daybreakgames.com/get/eq2/item?id=<?=$data['soe_item_id_unsigned'] ?>" target="_blank"><img src="../images/soe.png" border="0" align="top" title="Census" alt="Census" height="20" /></a>
				<a href="http://eq2.zam.com/wiki/EQ2_Item:<?=preg_replace(" / /i ", "_ ", $data['name']) ?>" target="_blank"><img src="../images/zam.png" border="0" align="top" title="Zam" alt="Zam" height="20" /></a>
				<a href="https://eq2.fandom.com/wiki/<?=preg_replace(" / /i ", "_ ", $data['name']) ?>" target="_blank"><img src="../images/wikia.png" border="0" align="top" title="Wikia" alt="Wikia" height="20" /></a>
			</td>
		</tr>
		<tr>
			<td align="right">id:</td>
			<td>
				<input type="text" name="items|id" value="<?=$data['id'] ?>" readonly style="width:90px; background-color:#ddd;" />
				<input type="hidden" name="orig_id" value="<?=$data['id'] ?>" /> 
			</td>
			<td align="right">soe_item_id:</td>
			<td>
				<input type="text" name="items|soe_item_id" value="<?=$data['soe_item_id'] ?>" <?php if (!$eq2->CheckAccess(G_SUPERADMIN)) echo " readonly"; ?> style="width:90px;
				<?php if (!$eq2->CheckAccess(G_SUPERADMIN)) echo " background-color:#ddd;"; ?>" />
					<input type="hidden" name="orig_soe_item_id" value="<?=$data['soe_item_id'] ?>" /> 
			</td>
			<td align="right">soe_item_crc:</td>
			<td nowrap>
				<input type="text" name="items|soe_item_crc" value="<?=$data['soe_item_crc'] ?>" <?php if (!$eq2->CheckAccess(G_SUPERADMIN)) echo " readonly"; ?> style="width:90px;
				<?php if (!$eq2->CheckAccess(G_SUPERADMIN)) echo " background-color:#ddd;"; ?>" />
					<input type="hidden" name="orig_soe_item_crc" value="<?=$data['soe_item_crc'] ?>" />&nbsp; 
			</td>
		</tr>
		<tr>
			<td align="right">name:</td>
			<td colspan="5">
				<input type="text" name="items|name" value="<?=$data['name'] ?>" style="width:300px" />
				<input type="hidden" name="orig_name" value="<?=$data['name'] ?>" /> 
			</td>
		</tr>
		<tr>
			<td align="right">lua_script:</td>
			<td colspan="3">
				<input type="text" name="items|lua_script" value="<?=$data['lua_script'] ?>" style="width:300px" />
				<input type="hidden" name="orig_lua_script" value="<?=$data['lua_script'] ?>" /> 
			</td>
			<td colspan="2"><b>Format:</b> ItemScripts/itemname.lua</td>
		</tr>
		<tr>
			<td align="right">item_type:</td>
			<td><strong><?php echo $data['item_type']; ?></strong></td>
			<td align="right">set_id:</td>
			<td>
				<input type="text" name="items|set_id" value="<?=$data['set_id'] ?>" style="width:50px" />
				<input type="hidden" name="orig_set_id" value="<?=$data['set_id'] ?>" /> 
			</td>
			<td align="right" colspan="2" rowspan="2" style="padding-right:35px">
				<script>
				window.addEventListener('load', (event) => {
					UpdateItemTierTag();
				})
				</script>
				<table>
					<tr>
						<td align="center" style="width:100px;background-color:black;"> <span id="tierTag"></span>
							<br/> <img id="itemIcon" src="<?php echo $eq2Items->GetItemIconLink($data); ?>" /> 
						</td>
						<td align="center" style="width:100px;background-color:black;"> <font color="white">Classic Icon:</font> <br/><span id="tierClassicTag"></span>
						<?php if($data['classic_icon'] > 0) { ?>
							<img id="itemClassicIcon" src="<?php echo $eq2Items->GetItemIconLink($data, true); ?>" /> 
						<?php } ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align="right">item_level:</td>
			<td>
				<input type="text" name="items|recommended_level" value="<?=$data['recommended_level'] ?>" style="width:50px" />
				<input type="hidden" name="orig_recommended_level" value="<?=$data['recommended_level'] ?>" /> 
			</td>
			<td align="right">skill_min:</td>
			<td>
				<input type="text" name="items|skill_min" value="<?=$data['skill_min'] ?>" style="width:50px" />
				<input type="hidden" name="orig_skill_min" value="<?=$data['skill_min'] ?>" /> 
			</td>
		</tr>
		<tr>
			<td align="right">adventure_default_level:</td>
			<td>
				<input type="text" name="items|adventure_default_level" value="<?=$data['adventure_default_level'] ?>" style="width:50px" />
				<input type="hidden" name="orig_adventure_default_level" value="<?=$data['adventure_default_level'] ?>" /> 
			</td>
			<td align="right">skill_id_req:</td>
			<td>
				<select name="items|skill_id_req">
					<option value="0">---</option>
					<?=$eq2->getClassSkills($data['skill_id_req']); ?>
				</select>
				<input type="hidden" name="orig_skill_id_req" value="<?=$data['skill_id_req'] ?>" /> 
			</td>
			<td align="right">icon:</td>
			<td>
				<input type="text" name="items|icon" value="<?=$data['icon'] ?>" style="width:50px" onkeyup="ReloadItemIcon()" />
				<input type="hidden" name="orig_icon" value="<?=$data['icon'] ?>" /> 
			</td>
		</tr>
		<tr>
			<td></td><td></td>
			<td></td><td></td>
			<td align="right">classic icon:</td>
			<td>
				<input type="text" name="items|classic_icon" value="<?=$data['classic_icon'] ?>" style="width:50px" onkeyup="ReloadItemIcon()" />
				<input type="hidden" name="orig_classic_icon" value="<?=$data['classic_icon'] ?>" /> 
			</td>
		</tr>
		<tr>
			<td align="right">tradeskill_default_level:</td>
			<td>
				<input type="text" name="items|tradeskill_default_level" value="<?=$data['tradeskill_default_level'] ?>" style="width:50px" />
				<input type="hidden" name="orig_tradeskill_default_level" value="<?=$data['tradeskill_default_level'] ?>" /> 
			</td>
			<td align="right">skill_id_req2:</td>
			<td>
				<select name="items|skill_id_req2">
					<option value="0">---</option>
					<?=$eq2->getClassSkills($data['skill_id_req2']); ?>
				</select>
				<input type="hidden" name="orig_skill_id_req2" value="<?=$data['skill_id_req2'] ?>" /> 
			</td>
			<td align="right">tier:</td>
			<td>
				<select name="items|tier" style="width:56px" onchange="ReloadItemIcon();UpdateItemTierTag();">
					<option value="0">---</option>
					<?php print ($eq2->getItemTiers($data['tier'])); ?>
				</select>
				<input type="hidden" name="orig_tier" value="<?=$data['tier'] ?>" /> 
			</td>
		</tr>
		<tr>
			<td align="right">offers_quest_id:</td>
			<td>
				<input type="text" name="items|offers_quest_id" value="<?=$data['offers_quest_id'] ?>" style="width:50px" />
				<input type="hidden" name="orig_offers_quest_id" value="<?=$data['offers_quest_id'] ?>" /> 
			</td>
			<td align="right">stack_count:</td>
			<td>
				<input type="text" name="items|stack_count" value="<?=$data['stack_count'] ?>" style="width:50px" />
				<input type="hidden" name="orig_stack_count" value="<?=$data['stack_count'] ?>" /> 
			</td>
			<td align="right">count:</td>
			<td>
				<input type="text" name="items|count" value="<?=$data['count'] ?>" style="width:50px" />
				<input type="hidden" name="orig_count" value="<?=$data['count'] ?>" /> 
			</td>
		</tr>
		<tr>
			<td align="right">part_of_quest_id:</td>
			<td>
				<input type="text" name="items|part_of_quest_id" value="<?=$data['part_of_quest_id'] ?>" style="width:50px" />
				<input type="hidden" name="orig_part_of_quest_id" value="<?=$data['part_of_quest_id'] ?>" /> 
			</td>
			<td align="right">max_charges:</td>
			<td>
				<input type="text" name="items|max_charges" value="<?=$data['max_charges'] ?>" style="width:50px" />
				<input type="hidden" name="orig_max_charges" value="<?=$data['max_charges'] ?>" /> 
			</td>
			<td align="right">weight:</td>
			<td>
				<input type="text" name="items|weight" value="<?=$data['weight'] ?>" style="width:50px" />
				<input type="hidden" name="orig_weight" value="<?=$data['weight'] ?>" />
			</td>
		</tr>
		<tr>
			<td colspan="2"></td>
			<td align="right">sell_price:</td>
			<td>
				<input type="text" name="items|sell_price" value="<?=$data['sell_price'] ?>" style="width:50px" />
				<input type="hidden" name="orig_sell_price" value="<?=$data['sell_price'] ?>" /> 
			</td>
			<td align="right">sell_status_amount:</td>
			<td>
				<input type="text" name="items|sell_status_amount" value="<?=$data['sell_status_amount'] ?>" style="width:50px" />
				<input type="hidden" name="orig_sell_status_amount" value="<?=$data['sell_status_amount'] ?>" /> 
			</td>
		</tr>
		<tr>
			<td align="right" valign="top">description:</td>
			<td colspan="5">
				<textarea name="items|description" cols="100" rows="3" style="resize:none;font:12px Arial, Helvetica, sans-serif"><?php print $data['description']; ?></textarea>
				<input type="hidden" name="orig_description" value="<?=$data['description'] ?>" /> 
			</td>
		</tr>
		<tr>
			<td align="right" valign="top">developer_notes:</td>
			<td colspan="5">
				<textarea name="items|developer_notes" cols="100" rows="2" style="resize:none;font:12px Arial, Helvetica, sans-serif"><?php print ($data['developer_notes']);?></textarea>
				<input type="hidden" name="orig_developer_notes" value="<?=$data['developer_notes'] ?>" /> 
			</td>
		</tr>
	</table>
	</fieldset>
	<?php
	}

	function PreUpdate() {
		$tab = $_GET['tab'] ?? "";

		if ($tab == "item_stats") {
			$sqln = new SQLNull;
			if ($_POST['item_mod_stats|fValue'] == "") {
				$_POST['item_mod_stats|fValue'] = $sqln;
				if ($_POST['orig_fValue'] == "") $_POST['orig_fValue'] = $sqln;
			}
			if ($_POST['item_mod_stats|iValue'] == "") {
				$_POST['item_mod_stats|iValue'] = $sqln;
				if ($_POST['orig_iValue'] == "") $_POST['orig_iValue'] = $sqln;
			}
		}
	}

	function PreInsert() {
		$tab = $_GET['tab'] ?? "";

		if ($tab == "item_stats") {
			if ($_POST['item_mod_stats|fValue|new'] == "") {
				unset($_POST['item_mod_stats|fValue|new']);
			}
			if ($_POST['item_mod_stats|iValue|new'] == "") {
				unset($_POST['item_mod_stats|iValue|new']);
			}
		}
	}

}

?>