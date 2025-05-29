<?php
class eq2Spawns
{
	public $GridColumnArray = array();
	public $CreatedLocID = "";

	var $eq2SpawnTables = array("spawn","spawn_npcs","spawn_objects","spawn_signs","spawn_widgets","spawn_ground","spawn_loot","spawn_scripts",
															"spawn_npc_equipment","spawn_npc_skills","spawn_npc_spells","spawn_pet_names",
															"spawn_location_name","spawn_location_entry","spawn_location_placement",
															"spawn_location_group","spawn_location_group_associates","spawn_location_group_chances",
															"spawn_templates");
	
	var $eq2SpawnTypes = array("NPCs","Objects","Signs","Widgets","Ground");

	// don't forget to loop through this array twice, prepending "soga_" to every appearance to cover them all
	var $eq2Appearances = array("cheek_type","chin_type","ear_type","eye_brow_type","eye_type","lip_type","nose_type",
															"eye_color","hair_color1","hair_color2","hair_face_color","hair_face_highlight_color","hair_highlight",
															"hair_type_color","hair_type_highlight_color","skin_color","wing_color1","wing_color2");


	var $eq2MerchantTypes = array(
		0	=> "Normal/None",
		1	=> "No Buy",
		2	=> "No Buy Back",
		4	=> "Spells",
		8	=> "Crafting",
	 	16	=> "Repair",
	 	32	=> "Lotto",
		64  => "City Merchant"
	);


	var $eq2HeroicFlags = array(
		0 => "Off",
		1 => "Heroic",
		2 => "Epic (x2)",
		3 => "Epic (x3)",
		4 => "Epic (x4)"
	);

	var $eq2SpawnToggles = array(
		"targetable", "show_name", "attackable", "show_level", "show_command_icon", "display_hand_icon", "disable_sounds"
	);

	var $eq2HolidayFlags = array(
		1<<0 => "Holiday Excluded",
		1<<1 => "Erollisi Day",
		1<<2 => "Brew Day",
		1<<3 => "Chronoportals",
		1<<4 => "Bristlebane Day",
		1<<5 => "Beast'r",
		1<<6 => "Oceansfull Festival",
		1<<7 => "Scorched Sky",
		1<<8 => "Tinkerfest",
		1<<9 => "Nights of the Dead",
		1<<10 => "Heroes' Festival",
		1<<11 => "Dragons Attack!",
		1<<12 => "Frostfell",
		1<<13 => "City Festival",
		1<<14 => "Moonlight Enchantments",
		1<<15 => "Summer Celebration"
	);

	var $eq2ExpansionFlags = array(
		1<<0 => "Classic",
		1<<1 => "Desert of Flames",
		1<<2 => "Kingdom of Sky",
		1<<3 => "Echoes of Faydwer",
		1<<4 => "Rise of Kunark",
		1<<5 => "The Shadow Odyssey",
		1<<6 => "Sentinel's Fate",
		1<<7 => "Destiny of Velious",
		1<<8 => "Age of Destiny",
		1<<9 => "Chains of Eternity",
		1<<10 => "Tears of Veeshan",
		1<<11 => "Altar of Malice",
		1<<12 => "Terrors of Thalumbra",
		1<<13 => "Kunark Ascending",
		1<<14 => "Planes of Prophecy",
		1<<15 => "Chaos Descending",
		1<<16 => "Blood of Luclin",
		1<<17 => "Reign of Shadows",
		1<<18 => "Bloodline Chronicles",
		1<<19 => "The Splitpaw Saga",
		1<<20 => "The Fallen Dynasty"
	);

	var $eq2SpawnAttachmentSlots = array(
		0 => "Primary",
		1 => "Secondary",
		2 => "Head",
		3 => "Chest",
		4 => "Shoulders",
		5 => "Forearms",
		6 => "Hands",
		7 => "Legs",
		8 => "Feet",
		9 => "Left Ring",
		10 => "Right Ring",
		11 => "Ears",
		12 => "Ear 2",
		13 => "Neck",
		14 => "Left Wrist",
		15 => "Right Wrist",
		16 => "Ranged",
		17 => "Ammo",
		18 => "Waist",
		19 => "Cloak",
		20 => "Charm1",
		21 => "Charm2",
		22 => "Food",
		23 => "Drink",
		24 => "Mount Adornment"
	);

	public function __construct() 
	{
		include("../class/eq2.zones.php");
		$this->zones = new eq2Zones();

		// ints
		$this->zone_id		= $_GET['zone'] ?? 0;
		$this->spawn_id		= preg_match('/spawns.php$/', __FILE__) ? intval(($_GET['id'] ?? 0)) : 0;
		
		// strings
		if ($this->zone_id > 0) {
			$this->zone_name = strlen($this->zones->zone_name) == 0 ? 
				$this->zones->GetZoneName() : 
				$this->zones->zone_name; // only fetch zone name if zones->zone_name is not already set
		} 
		$this->spawn_type	= $_GET['type'] ?? "";
		$this->spawn_name	= ( $this->spawn_id ) ? $this->GetSpawnName() : "";
	}

	public function CreateNewLootTable() 
	{
		global $eq2;
		
		$loottable_id = $_POST['spawn_loot|loottable_id'];
		
		if( $loottable_id > 0 )
		{
			// first, create a loottable entry for this new loot assignment, if it does not already exist
			$eq2->SQLQuery = sprintf("INSERT IGNORE INTO `".ACTIVE_DB."`.loottable (id) VALUES (%s)", $loottable_id);
			$eq2->RunQuery();
	
			// now that we have the loottable, the FK Constraint will not fail, insert the spawn_loot record
			$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spawn_loot (spawn_id, loottable_id) VALUES (%s, %s);", $this->spawn_id, $loottable_id);
			$eq2->RunQuery();
		}
	}
	
	public function GetAppearances($type = '', $hide_soga = false) 
	{
		// remember we're doing normal and soga at once...
		$normalOptions = "";
		$sogaOptions = "";
		foreach($this->eq2Appearances as $appearance)
		{
			$normalOptions .= sprintf('<option%s>%s</option>', ( $appearance == $type ) ? " selected" : "", $appearance);
			
			if( !$hide_soga )
			{
				$sogaAppearance = sprintf('soga_%s', $appearance);
				$sogaOptions .= sprintf('<option%s>%s</option>', ( $sogaAppearance == $type ) ? " selected" : "", $sogaAppearance);
			}
		}
		
		return $normalOptions . $sogaOptions;
	}
	
	public function GetAppearanceNameFromID($id)
	{
		global $eq2;
		
		if($id > 0)
		{
			$eq2->SQLQuery = sprintf("SELECT name FROM `".ACTIVE_DB."`.appearances WHERE appearance_id = %s", $id);
			$data = $eq2->RunQuerySingle();
			return $data['name'];
		}
		return "No Data";
	}
	
	public function GetCleanSpawnScriptName()
	{
		// sanitize
		$var = preg_replace("/[^a-zA-Z0-9]+/", "", $this->spawn_name);
		return sprintf("%s.lua", $var);
	}
	
	public function GetEntityCommandOptions($id) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT command_list_id, command_text FROM `".ACTIVE_DB."`.entity_commands GROUP BY command_list_id HAVING COUNT(command_list_id) = 1 ORDER BY command_text;");
		$results = $eq2->RunQueryMulti();

		
		if( is_array($results) )
		{
			$command_options = "";
			foreach($results as $data) {
				$command_options .= sprintf('<option value="%s"%s>%s (%s)</option>', 
				$data['command_list_id'], 
				$data['command_list_id'] == $id ? " selected" : "", 
				$data['command_text'],
				$data['command_list_id'] );		
			}
		}
		
		return $command_options;
	}

	public function GetEntityCommandOptionsSecondary($id) 
	{
		global $eq2;

		// First, get the command_list_id of any command with more than 1 entry
		$eq2->SQLQuery = "SELECT command_list_id, command_text FROM `".ACTIVE_DB."`.entity_commands WHERE command_list_id IN (SELECT command_list_id FROM `".ACTIVE_DB."`.entity_commands GROUP BY command_list_id HAVING COUNT(command_list_id) > 1);";
		$results = $eq2->RunQueryMulti();
		
		$command_options = "";
		if( is_array($results) ) 
		{
			$cmds = array();
			foreach($results as $data) 
			{
				$cmd = $data['command_list_id'];
				if (!isset($cmds[$cmd])) {
					$cmds[$cmd] = $cmd;
				}

				$cmds[$cmd] .= sprintf(", %s", $data['command_text']);
			}

			//Sort the cmds by the command text values alphabetically
			asort($cmds, SORT_NATURAL | SORT_FLAG_CASE);

			foreach ($cmds as $k=>$v)
			{
				$command_options .= sprintf('<option value="%s"%s>%s</option>', $k, ( $k == $id ) ? " selected" : "", $v);
			}
		}

		$command_options .= "<option value=\"0\">-- Single Commands --</option>\n";
		$command_options .= $this->GetEntityCommandOptions($id);
		
		return $command_options;
	}

	public function GetGroundSpawnIDs()
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT groundspawn_id, tablename FROM `".ACTIVE_DB."`.groundspawns ORDER BY groundspawn_id";
		$rows = $eq2->RunQueryMulti();
		
		$ret[0] = 'None'; // first entry
		foreach($rows as $row)
		{
			$tablename = strlen($row['tablename']) > 0 ? $row['tablename'] : "Unnamed";
			$ret[$row['groundspawn_id']] = $tablename;
		}
		
		return $ret;
	}

	public function GetLootTableOptions($id = 0) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT id, name FROM `".ACTIVE_DB."`.loottable ORDER BY name");
		$rows = $eq2->RunQueryMulti();
		
		if( is_array($rows) )
		{
			$ret = "";
			foreach($rows as $row) 
				$ret .= sprintf('<option value="%s"%s>%s (%s)</option>', $row['id'], ( $id == $row['id'] ) ? " selected" : "", $row['name'], $row['id']);
		}
		
		return $ret;
	}
	
	public function GetMerchantList($id) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT merchant_id, description FROM `".ACTIVE_DB."`.merchants ORDER BY description");
		return $eq2->RunQueryMulti();
	}

	public function GetModelNameByID($id) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT model_name FROM eq2models WHERE model_type = %s", $id);
		$data = $eq2->RunQuerySingle();
		return ( !empty($data['model_name']) ) ? sprintf("(%s)", $data['model_name']) : "";
	}

	public function GetSpawnLocationGroup()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT slg.* " .
														 "FROM `".ACTIVE_DB."`.spawn_location_group slg " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON slg.placement_id = slp.id " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON slp.spawn_location_id = sle.spawn_location_id " .
														 "WHERE sle.spawn_id = %s " .
														 "ORDER BY group_id", $this->spawn_id, $this->zone_id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnLocationGroupAssociations()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT DISTINCT slga.* " .
														 "FROM `".ACTIVE_DB."`.spawn_location_group slg " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON slg.placement_id = slp.id " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON slp.spawn_location_id = sle.spawn_location_id " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_group_associations slga ON slga.group_id1 = slg.group_id OR slga.group_id2 = slg.group_id " .
														 "WHERE sle.spawn_id = %s " .
														 "ORDER BY group_id", $this->spawn_id, $this->zone_id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnLocationGroupChances()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT slgc.* " .
														 "FROM `".ACTIVE_DB."`.spawn_location_group slg " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON slg.placement_id = slp.id " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON slp.spawn_location_id = sle.spawn_location_id " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_group_chances slgc ON slgc.group_id = slg.group_id " .
														 "WHERE sle.spawn_id = %s " .
														 "ORDER BY group_id", $this->spawn_id, $this->zone_id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnLocationNames() 
	{
		global $eq2;

		$eq2->SQLQuery = sprintf("SELECT sln.* " .
														 "FROM `".ACTIVE_DB."`.spawn_location_name sln " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON sln.id = sle.spawn_location_id " .
														 "WHERE sle.spawn_id = %s;", $this->spawn_id);
		return $eq2->RunQueryMulti();
	}

	public function GetSpawnLocationEntries() 
	{
		global $eq2;

		$eq2->SQLQuery = sprintf("SELECT sle.*, sln.`name`" .
														 "FROM `".ACTIVE_DB."`.spawn_location_entry sle " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_name sln ON sle.spawn_location_id = sln.id ".
														 "WHERE sle.spawn_id = %s;", $this->spawn_id);
		return $eq2->RunQueryMulti();
	}

	public function GetSpawnLocationPlacements() 
	{
		global $eq2;

		$eq2->SQLQuery = sprintf("SELECT slp.* " .
														 "FROM `".ACTIVE_DB."`.spawn_location_placement slp " .
														 "JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON slp.spawn_location_id = sle.spawn_location_id " .
														 "WHERE sle.spawn_id = %s;", $this->spawn_id);
		return $eq2->RunQueryMulti();
	}

	public function GetSpawnLootData()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spawn_loot WHERE spawn_id = %s", $this->spawn_id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnLootTables($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.loottable WHERE id = %s", $id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetLootDrops($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.lootdrop WHERE loot_table_id = %s", $id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnName() 
	{
		global $eq2;

		if (!is_int($this->spawn_id)) {
			return "Not Found.";
		}

		$eq2->SQLQuery = sprintf("SELECT name FROM `".ACTIVE_DB."`.spawn where id = %s", $this->spawn_id);
		$data = $eq2->RunQuerySingle();
		if(isset($data['name']))
		{
			return ( strlen($data['name']) > 0 ) ? $data['name'] : "Not Found.";
		}
	}

	public function GetSpawnNPCData()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spawn_npcs WHERE spawn_id = %s LIMIT 0,1", $this->spawn_id);
		return $eq2->RunQuerySingle();
	}
	
	public function GetSpawnObjectData()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spawn_objects WHERE spawn_id = %s LIMIT 0,1", $this->spawn_id);
		return $eq2->RunQuerySingle();
	}
	
	public function GetSpawnPlacements($spawn_id, $showHidden)
	{
		global $eq2;

		$eq2->SQLQuery = sprintf("SELECT slp.*, slg.group_id " .
															"FROM `".ACTIVE_DB."`.spawn_location_placement slp " .
															"JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON slp.spawn_location_id = sle.spawn_location_id " .
															"LEFT JOIN `".ACTIVE_DB."`.spawn_location_group slg ON slp.id = slg.placement_id " .
															"WHERE spawn_id = %lu AND processed = %s ", 
															$spawn_id, 
															$showHidden ? 2 : 0); 
		
		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnSignData()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spawn_signs WHERE spawn_id = %s LIMIT 0,1", $this->spawn_id);
		return $eq2->RunQuerySingle();
	}
	
	public function GetSpawnWidgetData()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spawn_widgets WHERE spawn_id = %s LIMIT 0,1", $this->spawn_id);
		return $eq2->RunQuerySingle();
	}
	
	public function GetSpawnGroundData()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spawn_ground WHERE spawn_id = %s LIMIT 0,1", $this->spawn_id);
		return $eq2->RunQuerySingle();
	}
	
	public function GetSpawnScriptEntries()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spawn_scripts " . 
														 "WHERE spawn_id = %s " . 
														 "OR spawn_location_id IN (SELECT spawn_location_id FROM `".ACTIVE_DB."`.spawn_location_entry WHERE spawn_id = %s) " . 
														 "OR spawnentry_id IN (SELECT id FROM `".ACTIVE_DB."`.spawn_location_entry WHERE spawn_id = %s) " . 
														 "ORDER BY spawn_id, spawnentry_id, spawn_location_id", $this->spawn_id, $this->spawn_id, $this->spawn_id);

		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnEntryOptions($id = 0) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT sle.id, name FROM `".ACTIVE_DB."`.spawn_location_entry sle, `".ACTIVE_DB."`.spawn_location_name sln WHERE sle.spawn_location_id = sln.id AND spawn_id = %s ORDER BY sle.id", $this->spawn_id);
		$rows = $eq2->RunQueryMulti();
		
		if( is_array($rows) )
		{
			$ret = "";
			foreach($rows as $row) 
				$ret .= sprintf('<option value="%s"%s>%s (%s)</option>', $row['id'], ( $id == $row['id'] ) ? " selected" : "", $row['name'], $row['id']);
		}
		
		return $ret;
	}
	
	public function GetSpawnLocationOptions($id = 0) 
	{
		global $eq2;

		$eq2->SQLQuery = sprintf("SELECT sln.id, name FROM `".ACTIVE_DB."`.spawn_location_name sln, `".ACTIVE_DB."`.spawn_location_entry sle WHERE sln.id = sle.spawn_location_id AND sle.spawn_id = %d ORDER BY sln.id", $this->spawn_id);
		$rows = $eq2->RunQueryMulti();
		
		if( is_array($rows) )
		{
			$ret = "";
			foreach($rows as $row) 
				$ret .= sprintf('<option value="%s"%s>%s (%s)</option>', $row['id'], ( $id == $row['id'] ) ? " selected" : "", $row['name'], $row['id']);
		}
		
		return $ret;
	}
	
	public function GetSpawnEquipmentList($id = 0) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT equipment_list_id, description FROM `".ACTIVE_DB."`.spawn_npc_equipment ORDER BY description");
		$rows = $eq2->RunQueryMulti();
		
		if( is_array($rows) )
		{
			$ret = "";
			foreach($rows as $row) 
				$ret .= sprintf('<option value="%s"%s>%s (%s)</option>', $row['equipment_list_id'], ( $id == $row['equipment_list_id'] ) ? " selected" : "", $row['description'], $row['equipment_list_id']);
		}
		
		return $ret;
	}
	
	public function GetSpawnSkillList($id = 0) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT skill_list_id, description FROM `".ACTIVE_DB."`.spawn_npc_skills ORDER BY description");
		$rows = $eq2->RunQueryMulti();
		
		if( is_array($rows) )
		{
			$ret = "";
			foreach($rows as $row) 
				$ret .= sprintf('<option value="%s"%s>%s (%s)</option>', $row['skill_list_id'], ( $id == $row['skill_list_id'] ) ? " selected" : "", $row['description'], $row['skill_list_id']);
		}
		
		return $ret;
	}
	
	public function GetSpawnSpellList($id = 0) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT id as spell_list_id, description FROM `".ACTIVE_DB."`.spawn_npc_spell_lists ORDER BY description");
		$rows = $eq2->RunQueryMulti();
		
		if( is_array($rows) )
		{
			$ret = "";
			foreach($rows as $row) 
				$ret .= sprintf('<option value="%s"%s>%s (%s)</option>', $row['spell_list_id'], ( $id == $row['spell_list_id'] ) ? " selected" : "", $row['description'], $row['spell_list_id']);
		}
		
		return $ret;
	}
	
	public function GetSpawnsByZone()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT DISTINCT s1.id,s1.name " .
															"FROM `".ACTIVE_DB."`.spawn s1 " .
															"JOIN `".ACTIVE_DB."`.spawn_%s s2 ON s1.id = s2.spawn_id " .
															"LEFT JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON s1.id = sle.spawn_id " .
															"LEFT JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON sle.spawn_location_id = slp.spawn_location_id " .
															"WHERE slp.zone_id = %s or s1.`id` BETWEEN %s0000 and %s9999 " .
															"ORDER BY s1.name", $this->spawn_type, $this->zone_id, $this->zone_id, $this->zone_id);

		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnsMatching()
	{
		global $eq2;
		
		$ret = NULL;
		
		if( strlen($_GET['search']) > 0 )
		{
			$eq2->SQLQuery = sprintf("SELECT s1.id, s1.name, s1.sub_title, s1.model_type, " . 
															 				"s2.min_level, s2.max_level, s2.enc_level, " . 
																			"s4.type, s4.title, s4.zone_id, " . 
																			"s6.groundspawn_id, s6.collection_skill, " .
																			"s2.spawn_id AS is_npc, " . 
																			"s3.spawn_id AS is_object, " . 
																			"s4.spawn_id AS is_sign, " . 
																			"s5.spawn_id AS is_widget, " . 
																			"s6.spawn_id AS is_ground, " . 
																			"FLOOR(s1.id / 10000) AS loc_zone " .
															 "FROM `".ACTIVE_DB."`.spawn s1 " .
															 "LEFT JOIN `".ACTIVE_DB."`.spawn_npcs s2 ON s1.id = s2.spawn_id " .
															 "LEFT JOIN `".ACTIVE_DB."`.spawn_objects s3 ON s1.id = s3.spawn_id " .
															 "LEFT JOIN `".ACTIVE_DB."`.spawn_signs s4 ON s1.id = s4.spawn_id " .
															 "LEFT JOIN `".ACTIVE_DB."`.spawn_widgets s5 ON s1.id = s5.spawn_id " .
															 "LEFT JOIN `".ACTIVE_DB."`.spawn_ground s6 ON s1.id = s6.spawn_id " .
															 //"LEFT JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON s1.id = sle.spawn_id " .
															 //"JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON sle.spawn_location_id = slp.spawn_location_id " .
															 "WHERE name RLIKE '%s' " .
															 "ORDER BY name", $eq2->SQLEscape($_GET['search']));
			
			$results = $eq2->RunQueryMulti();
			
			if( is_array($results) )
			{
				foreach($results as $row)
				{
					// determine spawn_type
					if( $row['is_npc'] == $row['id'] )
						$row['spawn_type'] = "npcs";
					elseif( $row['is_object'] == $row['id'] )
						$row['spawn_type'] = "objects";
					elseif( $row['is_sign'] == $row['id'] )
						$row['spawn_type'] = "signs";
					elseif( $row['is_widget'] == $row['id'] )
						$row['spawn_type'] = "widgets";
					elseif( $row['is_ground'] == $row['id'] )
						$row['spawn_type'] = "ground";
					
					$ret[] = $row;
				}
			}
		}
		
		return $ret;
	}
	
	public function GetSpawnScriptName($spawnscript_id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT lua_script FROM `".ACTIVE_DB."`.spawn_scripts WHERE id = %s", $spawnscript_id);	
		return $eq2->RunQuerySingle();
	}
	
	public function is_merchant($id) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT merchant_id FROM `".ACTIVE_DB."`.spawn WHERE id = %s", $id);
		$data = $eq2->RunQuerySingle();
		return $data['merchant_id'] > 0 ? $data['merchant_id'] : 0;
	}

	public function is_QuestNPC($name) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT count(*) AS cnt FROM `".PARSER_DB."`.raw_quests WHERE description RLIKE '%s';", $eq2->SQLEscape($name));
		$data = $eq2->RunQuerySingle();
		return ( $data['cnt'] > 0 ) ? 1 : 0;
	}
	
	public function SetXZOffsets($val)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("UPDATE `".ACTIVE_DB."`.spawn_location_placement " . 
														 "SET x_offset = %s, z_offset = %s " . 
														 "WHERE zone_id = %s AND " . 
														 "spawn_location_id IN (" . 
														 		"SELECT spawn_location_id FROM `".ACTIVE_DB."`.spawn_location_entry " . 
																"WHERE spawn_id = %s" . 
															")", $val, $val, $this->zone_id, $this->spawn_id);

		$eq2->RunQuery();
	}

	public function DisplayAddNewSpawnPage()
	{
		global $spawnInsertError;

		if($spawnInsertError ?? false) { 
			if ($spawnInsertError == 1) $errtext = "Entered Zone ID is invalid!";
			else $errtext = "Error inserting your new spawn!";
			printf('<span class="heading" style="color:red">%s</span>', $errtext);
			echo "</br>";
		}
		?>

		<form method="post" name="AddSpawn">
		<fieldset style="width:320px;">
		<legend>Spawn Create</legend>
		<table cellpadding="5">
			<thead>
				<tr>
					<td>
						<span class="heading">Basic Details For Your New Spawn</span>
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td align="right">
						<label>Spawn Name:</label>
						<input type="text" class="box" name="spawnName"/>
					</td>
				</tr>
				<tr>
					<td align="right">
						<label>Zone ID:</label>
						<input type="text" class="box" name="zoneID" value="<?php echo $_GET['zone'] ?? ""; ?>"/>
					</td>
				</tr>
				<tr>
					<td align="center">
						<label>Spawn Type:</label>
						<select name="spawnType" value="npcs">
							<?php 
							$t = $_GET['type'] ?? "npcs";
							
							$spawn_types = array("npcs"=>"NPC","signs"=>"Sign","objects"=>"Object",
							"ground"=>"Ground","widgets"=>"Widget");

							foreach ($spawn_types as $k=>$v) {
								printf('<option value="%s"%s>%s</option>',
								$k, $v == $t ? " selected" : "", $v);
							} 
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


	function CreateNewSpawn() {
		global $eq2, $spawnInsertError;

		$name = $_POST['spawnName'] ?? "";
		$zone = $_POST['zoneID'];

		if (!$this->zones->DoesZoneExist($zone)) {
			$spawnInsertError = 1;
			return;
		}

		$minRangeID = sprintf("%s0000", $zone);
		$maxRangeID = sprintf("%s9999", $zone);

		$eq2->BeginSQLTransaction();
		$eq2->RunQuery(true, "LOCK TABLE `".ACTIVE_DB."`.`spawn` WRITE;");

		$query = sprintf('SELECT MAX(id) + 1 as newid FROM `%s`.`spawn` s WHERE s.id BETWEEN %s AND %s',
		ACTIVE_DB, $minRangeID, $maxRangeID);

		$row = $eq2->RunQuerySingle($query);
		$nextID = $row['newid'] ?? $minRangeID;

		$query = sprintf("INSERT INTO `%s`.`spawn` (`id`, `name`) VALUES (%s,'%s')",
		 ACTIVE_DB, $nextID, $eq2->SQLEscape($name));

		 $success = false;

		if ($eq2->RunQuery(true, $query) == 1) {
			$type = $_POST['spawnType'];

			if ($type == "signs") {
				//The signs table is missing a default value so handle that
				$query = sprintf("INSERT INTO `%s`.`spawn_signs` (`spawn_id`, `description`) VALUES (%s,'');",
				ACTIVE_DB, $nextID);
			}
			else {
				$query = sprintf("INSERT INTO `%s`.`spawn_%s` (`spawn_id`) VALUES (%s)",
				ACTIVE_DB, $type, $nextID);
			}

			if ($eq2->RunQuery(true, $query) == 1) {
				$eq2->SQLTransactionCommit();
				$success = true;
			}
		}

		if (!$success) {
			$eq2->SQLTransactionRollback();
		}

		$eq2->RunQuery(true, "UNLOCK TABLES;");

		if ($success) {
			$search = sprintf("spawns.php?zone=%s&type=%s&id=%s", $zone, $type, $nextID);
			header("Location: ".$search);
			//We're redirecting at this point, go ahead and exit
			exit;
		}
		else {
			$spawnInsertError = 2;
		}
	}

	public function GenerateCreateNewSpawnLink() {
		$type = isset($_GET['type']) ? "&type=".$_GET['type'] : "";
		$zone = isset($_GET['zone']) ? "&zone=".$_GET['zone'] : "";

		return sprintf("spawns.php?new%s%s", $type, $zone);
	}

	public function PrintHeroicFlagOptions($npc) {
		foreach ($this->eq2HeroicFlags as $k=>$v) {
			printf("<option value=%s%s>%s</option>", $k, $npc['heroic_flag'] == $k ? " selected" : "", $v);
		}
	}

	public function PrintSpawnToggles($spawn) {
		global $eq2;
		?>
		<fieldset style="height:173px;">
		<legend>Toggles</legend> 
			<table>
				<?php
				foreach ($this->eq2SpawnToggles as $toggle) {
					printf('<tr><td align="right">%s:</td><td>', $toggle);
					$eq2->GenerateBlueCheckbox("spawn|" . $toggle, $spawn[$toggle] == 1);
					printf('<input type="hidden" name="orig_%s" value="%s">', $toggle, $spawn[$toggle]);
					print("</td></tr>");
				}
				?>
			</table>
		</fieldset>
		<?php
	}

	public function PrintHolidayFlags($spawn) {
		global $eq2;
		?>
		<fieldset>
		<legend>Holiday Flags</legend> 
			<div id="spawnHolidayFlagsGrid">
			<?php foreach($this->eq2HolidayFlags as $flag=>$holiday) : ?>
				<table>
				<tr>
					<td><?php echo $holiday.":"; ?></td>
					<td><?php $eq2->GenerateBlueCheckbox("spawn|holiday_flag|".$flag, $spawn['holiday_flag'] & $flag); ?></td>
				</tr>
				</table>
			<?php endforeach; ?>
			<input type="hidden" name="orig_holiday_flag" value="<?php echo $spawn['holiday_flag']; ?>"/>
			</div>
		</fieldset>

		<?php
	}

	public function PrintExpansionFlags($spawn) {
		global $eq2;
		?>
		<fieldset>
		<legend>Expansion Flags</legend> 
			<div id="spawnExpansionFlagsGrid">
			<?php foreach($this->eq2ExpansionFlags as $flag=>$exp) : ?>
				<table>
				<tr>
					<td><?php echo $exp.":"; ?></td>
					<td><?php $eq2->GenerateBlueCheckbox("spawn|expansion_flag|".$flag, $spawn['expansion_flag'] & $flag); ?></td>
				</tr>
				</table>
			<?php endforeach; ?>
			<input type="hidden" name="orig_expansion_flag" value="<?php echo $spawn['expansion_flag']; ?>"/>
			</div>
		</fieldset>

		<?php
	}

	public function PrintSpawnCommands($data) {
		$spawns = $this
		?>
		<fieldset style="height:80px; width:675px;"><legend>Commands</legend> 
				<table>
					<tr>
						<td align="right">command_primary:</td>
						<td>
							<strong id="ECmdText"><?php echo $this->GetEntityCmdString($data['command_primary']) ?></strong>
							<input type="hidden" id="ECmdID" name="spawn|command_primary" value="<?php echo $data['command_primary'] ?>" />
							<img src="../images/search.png" style="cursor:pointer" onclick="document.getElementById('ECmdSearch').removeAttribute('hidden')" />
							<input hidden id="ECmdSearch" type="text" onkeyup="EntityCmdLookupAJAX('ECmdSearch','ECmdSuggest','ECmdText','ECmdID',true)"/>
							<div id="ECmdSuggest"></div>
							<input type="hidden" name="orig_command_primary" value="<?= $data['command_primary'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">command_secondary:</td>
						<td>
							<strong id="ESCmdText"><?php echo $this->GetEntityCmdString($data['command_secondary']) ?></strong>
							<input type="hidden" id="ESCmdID" name="spawn|command_secondary" value="<?php echo $data['command_secondary'] ?>" />
							<img src="../images/search.png" style="cursor:pointer" onclick="document.getElementById('ESCmdSearch').removeAttribute('hidden')" />
							<input hidden id="ESCmdSearch" type="text" onkeyup="EntityCmdLookupAJAX('ESCmdSearch','ESCmdSuggest','ESCmdText','ESCmdID',false)"/>
							<div id="ESCmdSuggest"></div>
							<input type="hidden" name="orig_command_secondary" value="<?= $data['command_secondary'] ?>" />
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td align="right">*build secondary commands in <a href="server.php?page=entity_commands" target="_self">Server / entity_commands</a> page</td>
					</tr>
				</table>
				</fieldset>
				<?php
	}

	public function PrintSpawnGeneralFields($data) {
		$spawns = $this
		?>
		<fieldset style="width:675px;"><legend>General</legend> 
				<table border="0">
					<tr>
						<td align="right" width="200">id:</td>
						<td width="100">
							<input type="text" name="spawn|id" value="<?= $data['id'] ?>" readonly style="width:100px; background-color:#ddd;" />
							<input type="hidden" name="orig_id" value="<?= $data['id'] ?>" />
						</td>
						<td align="right" width="120">name:</td>
						<td width="200" colspan="3">
							<input type="text" name="spawn|name" value="<?php print($data['name']); ?>" style="width:300px;" />
							<input type="hidden" name="orig_name" value="<?= $data['name'] ?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
						<td align="right" width="120">sub_title:</td>
						<td width="200" colspan="3">
							<input type="text" name="spawn|sub_title" value="<?php print($data['sub_title']); ?>" style="width:300px;" />
							<input type="hidden" name="orig_sub_title" value="<?= $data['sub_title'] ?>" />
						</td>
					</tr>
					<tr>
						<td align="right">race:</td>
						<td>
							<input type="text" name="spawn|race" value="<?php print($data['race']); ?>" style="width:50px" />
							<input type="hidden" name="orig_race" value="<?= $data['race'] ?>" />
						</td>
						<td align="right">model_type:</td>
						<td colspan="3">
							<input type="text" name="spawn|model_type" value="<?php print($data['model_type']); ?>" style="width:50px" />
							<input type="hidden" name="orig_model_type" value="<?= $data['model_type'] ?>" />
							<input type="button" value="Lookup Model" class="submit" onclick="javascript:window.open('spawn_func.php?func=model','lookup','width=1024,height=768,left=10,top=75,scrollbars=yes');" style="width:100px;" />
							<?= $spawns->GetModelNameByID($data['model_type']); ?>
							<!--<a href="http://eq2emu-reference.wetpaint.com/page/Category%3ACreature+Masterlist" target="_blank">Zexis Model Reference</a>-->
						</td>
					</tr>
					<tr>
						<td align="right">size:</td>
						<td>
							<input type="text" name="spawn|size" value="<?php print($data['size']); ?>" style="width:50px" />
							<input type="hidden" name="orig_size" value="<?= $data['size'] ?>" />
						</td>
						<td align="right">size_offset:</td>
						<td>
							<input type="text" name="spawn|size_offset" value="<?php print($data['size_offset']); ?>" style="width:50px" />
							<input type="hidden" name="orig_size_offset" value="<?= $data['size_offset'] ?>" />
						</td>
						<td align="right">loot_tier:</td>
						<td>
							<input type="text" name="spawn|loot_tier" value="<?php print($data['loot_tier']); ?>" style="width:50px" />
							<input type="hidden" name="orig_loot_tier" value="<?= $data['loot_tier'] ?>" />
						</td>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td align="right">faction_id:</td>
						<td>
							<input type="text" name="spawn|faction_id" value="<?php print($data['faction_id']); ?>" style="width:50px" />
							<input type="hidden" name="orig_faction_id" value="<?= $data['faction_id'] ?>" />
						</td>
						<td align="right">collision_radius:</td>
						<td>
							<input type="text" name="spawn|collision_radius" value="<?php print($data['collision_radius']); ?>" style="width:50px" />
							<input type="hidden" name="orig_collision_radius" value="<?= $data['collision_radius'] ?>" />
						</td>
						<td align="right">loot_drop_type:</td>
						<td>
							<select name="spawn|loot_drop_type">
								<option value="0"<?php if ($data['loot_drop_type'] == 0) echo " selected" ?>>Encounter</option>
								<option value="1"<?php if ($data['loot_drop_type'] == 1) echo " selected" ?>>Individual</option>
							</select>
							<input type="hidden" name="orig_loot_drop_type" value="<?= $data['loot_drop_type'] ?>" />
						</td>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td align="right">hp:</td>
						<td>
							<input type="text" name="spawn|hp" value="<?php print($data['hp']); ?>" style="width:50px" />
							<input type="hidden" name="orig_hp" value="<?= $data['hp'] ?>" />
						</td>
						<td align="right">power:</td>
						<td>
							<input type="text" name="spawn|power" value="<?php print($data['power']); ?>" style="width:50px" />
							<input type="hidden" name="orig_power" value="<?= $data['power'] ?>" />
						</td>
						<td align="right">transport_id:</td>
						<td>
							<input type="text" name="spawn|transport_id" value="<?php print($data['transport_id']); ?>" style="width:50px" />
							<input type="hidden" name="orig_transport_id" value="<?= $data['transport_id'] ?>" />
						</td>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td align="right">savagery:</td>
						<td>
							<input type="text" name="spawn|savagery" value="<?php print($data['savagery']); ?>" style="width:50px" />
							<input type="hidden" name="orig_savagery" value="<?= $data['savagery'] ?>" />
						</td>
						<td align="right">dissonance:</td>
						<td>
							<input type="text" name="spawn|dissonance" value="<?php print($data['dissonance']); ?>" style="width:50px" />
							<input type="hidden" name="orig_dissonance" value="<?= $data['dissonance'] ?>" />
						</td>
						<td align="right">aaxp_rewards:</td>
						<td>
							<input type="text" name="spawn|aaxp_rewards" value="<?php print($data['aaxp_rewards']); ?>" style="width:50px" />
							<input type="hidden" name="orig_aaxp_rewards" value="<?= $data['aaxp_rewards'] ?>" />
						</td>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td align="right">visual_state:</td>
						<td>
							<input type="text" name="spawn|visual_state" value="<?php print($data['visual_state']); ?>" style="width:50px" />
							<input type="hidden" name="orig_visual_state" value="<?= $data['visual_state'] ?>" />
						</td>
						<td align="right">merchant_type:</td>
						<td colspan="3">
							<?php
							$merchantOptions = "";
							foreach($spawns->eq2MerchantTypes as $key=>$val) 
								$merchantOptions .= sprintf('<option value="%s"%s>%s</option>', $key, ( $key == $data['merchant_type'] ) ? " selected" : "", $val);
							?>
							<select name="spawn|merchant_type" style="width:250px">
								<?= $merchantOptions ?>
							</select>
							<input type="hidden" name="orig_merchant_type" value="<?= $data['merchant_type'] ?>" />
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input type="button" value="Lookup Visual" class="submit" onclick="javascript:window.open('spawn_func.php?func=visual','lookup','width=1024,height=768,left=10,top=75,scrollbars=yes');" style="width:100px;" />
						</td>
						<td align="right">merchant_id:</td>
						<td colspan="3">
							<?php
							$merchants = $spawns->GetMerchantList($data['merchant_id']);
							if( is_array($merchants) )
								$merchantListOptions = "";
								foreach($merchants as $key=>$val) 
									$merchantListOptions .= sprintf('<option value="%s"%s>%s</option>', $val['merchant_id'], ( $val['merchant_id'] == $data['merchant_id'] ) ? " selected" : "", htmlspecialchars($val['description'], ENT_QUOTES, 'UTF-8'));
							?>
							<select name="spawn|merchant_id" style="width:250px">
								<option value="0">N/A</option>
							<?= $merchantListOptions ?>
							</select> <a href="server.php?p=merchant" target="_self">New</a>
							<input type="hidden" name="orig_merchant_id" value="<?= $data['merchant_id'] ?>" />
						</td>
				</table>
				</fieldset>
				
				<?php
	}

	public function CreateNewSpawnLocation() {
		global $eq2;

		$this->FixSpawnLocationEntryPostData();

		$name = $eq2->SQLEscape($_POST['spawn_location_name|name|new']);

		//Don't log this query because it will mess up our LAST_INSERT_ID()
		$res = $eq2->RunQuery(false, "INSERT INTO `".ACTIVE_DB."`.spawn_location_name (`name`) VALUES ('" .$name . "');");

		if (($res ?? 0) != 1) {
			$eq2->AddStatus("Error inserting a new spawn location...please try again");
			return;
		}

		$this->CreatedLocID = $eq2->db->sql_last_insert_id();

		$spawn_id = $_GET['id'];
		$percentage = $eq2->SQLEscape($_POST['spawn_location_entry|spawnpercentage|new']);
		$condition = $eq2->SQLEscape($_POST['spawn_location_entry|condition']);

		$res = $eq2->RunQuery(true, sprintf("INSERT INTO %s.spawn_location_entry (spawn_id, spawn_location_id, spawnpercentage, `condition`) VALUES (%s, %s, %s, %s);", 
		ACTIVE_DB, $spawn_id, $this->CreatedLocID, $percentage, $condition));

		if (($res ?? 0) != 1) {
			$eq2->AddStatus("Error inserting a new spawn location...please try again ");
			return;
		}
	}

	public function FixSpawnLocationEntryPostData()
	{
		$new_cond = 0;
		foreach($_POST as $k=>$v) {
			$vals = explode('|', $k);
			if (count($vals) < 3) continue;

			if ($vals[1] == "condition") {
				$new_cond |= intval($vals[2]);
				$_POST[$k] = NULL;
			}
		}
		$_POST['spawn_location_entry|condition'] = $new_cond;
	}

	public function PrintAttachmentSlots($slot)
	{
		?>
		<select name="npc_appearance_equip|slot_id" value="0">
		<?php foreach ($this->eq2SpawnAttachmentSlots as $k=>$v) : ?>		
			<?php printf("<option value=\"%s\"%s>%s</option>", $k, $slot == $k ? " selected" : "", $v); ?>
		<?php endforeach; ?>
		</select>
		<?php
	}

	public function PrintNPCAttackTypes($type)
	{
		global $eq2;
		?>
		<select name="spawn_npcs|attack_type" value="0">
		<?php foreach ($eq2->eq2DamageTypes as $k=>$v) : ?>		
			<?php printf("<option value=\"%s\"%s>%s</option>", $k, $type == $k ? " selected" : "", $v); ?>
		<?php endforeach; ?>
		</select>
		<?php
	}

	public function GetEntityCmdString($id) {
		global $eq2;

		if ($id == 0) {
			return "---NONE--- (0)";
		}
		
		$query = "SELECT command_text FROM `".ACTIVE_DB."`.entity_commands WHERE command_list_id = ".$id;
		$res = $eq2->RunQueryMulti($query);

		$ret = "";
		foreach ($res as $data) {
			if ($ret != "") $ret .= ", ";
			$ret .= $data['command_text'];
		}
		$ret .= sprintf(" (%s)", $id);

		return $ret;
	}

	public function PrintSignLanguageOptions($data) {
		global $eq2;
		?>
		<select name="spawn_signs|language">
			<option value="0">Common</option>
		<?php foreach ($eq2->eq2Languages as $k=>$v) {
			printf('<option value="%s"%s>%s</option>', $k, $data['language'] == $k ? " selected" : "", $v);
		}
		?>
		</select>
		<input type="hidden" name="orig_language" value="<?php echo $data['language'] ?>" />
		<?php
	}
}
?>