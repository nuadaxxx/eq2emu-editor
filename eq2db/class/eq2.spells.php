<?php
class eq2Spells
{
	// JSON vars
	private $spells					= array();
	private $spell_tier			= array();
	private $spell_fx				= array();
	private $spell_classes	= array();
	private $total_time			= 0; // total processing time
	private $soe_time				= 0; // how long did we spend sucking from SOE?
	private $our_time				= 0; // how long did we spend processing our own loops?
	
	// Spell vars
	public $spell_id				= 0;
	public $spell_name			= NULL;
	public $spell_crc				= 0;
	public $is_aa						= false;
	
	// any table having to do with Spells
	var $eq2SpellTables = array("spells","spell_tiers","spell_display_effects","spell_data","spell_classes","spell_traits",
															"spell_aa","spell_aa_list","spell_aa_nodelist","spell_error_versions", 
															"starting_spells","starting_skillbar","character_spells","character_skillbar");
	
	var $eq2SpellClasses = array(
		1 => "Fighter",
		11 => "Priest",
		21 => "Mage",
		31 => "Scout",
		2 => "Warrior",
		3	=> "Guardian",
		4 => "Berserker",
		5 => "Brawler",
		6 => "Monk",
		7 => "Bruiser",
		8 => "Crusader",
		9 => "Shadowknight",
		10 => "Paladin",
		12 => "Cleric",
		13 => "Templar",
		14 => "Inquisitor",
		15 => "Druid",
		16 => "Warden",
		17 => "Fury",
		18 => "Shaman",
		19 => "Mystic",
		20 => "Defiler",
		22 => "Sorcerer",
		23 => "Wizard",
		24 => "Warlock",
		25 => "Enchanter",
		26 => "Illusionist",
		27 => "Coercer",
		28 => "Summoner",
		29 => "Conjuror",
		30 => "Necromancer",
		32 => "Rogue",
		33 => "Swashbuckler",
		34 => "Brigand",
		35 => "Bard",
		36 => "Troubador",
		37 => "Dirge",
		38 => "Predator",
		39 => "Ranger",
		40 => "Assassin",
		41 => "Animist",
		42 => "Beastlord",
		43 => "Shaper",
		44 => "Channeler",
	 255 => "All"
	);
	
	var $eq2SpellBookTypes = array(
		0 => "Spell",
		1 => "Combat Art",
		2 => "Ability",
		3 => "Tradeskill",
		4 => "Not Shown"
	);

	var $eq2SOESpellTypes = array(
		0 => "spells",
		1 => "arts",
		2 => "abilities",
		3 => "tradeskills",
		4 => "pcinnates"
	);

	var $eq2SOESpellClassifications = array(
		"unset",
		"alternateadvancement",
		"charactertrait",
		"class",
		"classtraining",
		"race",
		"racialinnate",
		"racialtradition",
		"spellscroll",
		"tradeskillclass",
		"warderspell",
		"all"
	);
	
	var $eq2TraitTypes = array(
		"stats",
		"resists",
		"pools"
	);

	var $eq2TargetTypes = array(
		0 => "Self",
		1 => "Other",
		2 => "Group",
		3 => "Caster Pet",
		4 => "Other Pet",
		5 => "Other Corpse",
		6 => "Group Member Corpse",
		7 => "None",
		8 => "Raid (AE)",
		9 => "Other Group",
		10 => "Caster Mercenary"
	);

	var $eq2BaseSpellRanges = array(
		5000	=> "Common/Custom EQ2Emu",
		1000000	=> "PC Innates",
		8000	=> "Abilities",
		9000	=> "Tradeskills"
	);
	
	var $eq2SpellTiers = array(
		1 => "Apprentice",
		2 => "Apprentice II",
		3 => "Apprentice III",
		4 => "Apprentice IV",
		5 => "Adept I",
		6 => "Adept II",
		7 => "Adept III",
		8 => "Adept IV",
		9 => "Master I",
		10 => "Master II",
		11 => "Master III",
		12 => "Master IV"
	);

	var $emuSpellToggles = 
	array( 'spells' => array('persist_through_death', 
	'cast_while_moving', 'display_spell_tier', 'can_effect_raid',
	'not_maintained', 'friendly_spell'=>'beneficial_spell', 'duration_until_cancel'=>'until_cancelled',
	'interruptable', 'affect_only_group_members'=>'group_only', 'group_spell'=>'multi_target', 'can_fizzle',
	'incurable', 'is_deity', 'is_aa', 'is_active')
	);

	public function __construct() 
	{
		if(isset($_GET['id']) && $_GET['id'] > 0 )
		{
			$this->spell_id		= $_GET['id'];
			$this->spell_name	= $this->GetSpellName($this->spell_id);
			$this->spell_crc	= $this->GetSpellCRC($this->spell_id);
			$this->is_aa 			= ( $this->is_spell_aa($this->spell_id) ) ? true : false;
			$this->is_trait		= ( $this->is_spell_trait($this->spell_id) ) ? true : false;
		}
	}
	
	public function CheckRawSpellExists()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT COUNT(id) as CNT FROM `".PARSER_DB."`.raw_spells WHERE spell_id = %s", $this->spell_crc);
		$ret = $eq2->RunQuerySingle();
		return $ret['CNT'] > 0 ? true : false;
	}
	
	public function CheckSOESpellExists()
	{
		global $eq2;
		$ret=false;
		
		$DataURL = sprintf("http://census.daybreakgames.com/json/get/eq2/spell/?crc=%s", $this->spell_crc);
		$spell_array = json_decode($eq2->file_get_contents_curl($DataURL), true);

		if( $spell_array["returned"] > 0 )
			$ret = true;
			
		return $ret;
	}
	
	public function GetActiveSpells()
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT DISTINCT s.id, name, adventure_class_id, level, type, lua_script " . 
										 "FROM `".ACTIVE_DB."`.spells s " .
										 "JOIN `".ACTIVE_DB."`.spell_classes s2 ON s.id = s2.spell_id " .
										 "WHERE is_active = 1 AND adventure_class_id BETWEEN 1 AND 50 " .
										 "ORDER BY adventure_class_id, level, name";
		return $eq2->RunQueryMulti();
	}
	
	public function GetCleanSpellScriptName($var)
	{
		// sanitize
		$var = preg_replace("/[^a-zA-Z0-9]+/", "", html_entity_decode($var, ENT_QUOTES));
		
		// take apart spell 'name' and return a clean lua_script name
		$pattern[0] = "/(.*?)(IX|IV|V?I{0,3})$/"; // removes the roman numerals for the spell line
		$replace[0] = "$1";

		$name = preg_replace($pattern, $replace, $var);
		
		return sprintf("%s.lua", $name);
	}
	
	public function GetNextSpellDataIndex($field, $spellid, $tierid) 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT MAX(%s)+1 AS next_idx FROM `".ACTIVE_DB."`.spell_data WHERE spell_id = %s AND tier = %s", $field, $spellid, $tierid);
		$ret = $eq2->RunQuerySingle();
		return $ret['next_idx'] > 0 ? $ret['next_idx'] : 0;
	}
	
	public function GetSpellClassPath($class)
	{
		// Builds $path based on class structure Archetype/Class/SubClass/Spell.lua
		switch($class)
		{
			case 0:
				$path = "Commoner/";
				break;

			case 1:
				$path = "Fighter/";
				break;

			case 2:
				$path = "Fighter/Warrior/";
				break;

			case 3:
				$path = "Fighter/Warrior/Guardian/";
				break;

			case 4:
				$path = "Fighter/Warrior/Berserker/";
				break;
				
			case 5:
				$path = "Fighter/Brawler/";
				break;
				
			case 6:
				$path = "Fighter/Brawler/Monk/";
				break;
				
			case 7:
				$path = "Fighter/Brawler/Bruiser/";
				break;
				
			case 8:
				$path = "Fighter/Crusader/";
				break;
				
			case 9:
				$path = "Fighter/Crusader/Shadowknight/";
				break;
				
			case 10:
				$path = "Fighter/Crusader/Paladin/";
				break;

			case 11:
				$path = "Priest/";
				break;
				
			case 12:
				$path = "Priest/Cleric/";
				break;
				
			case 13:
				$path = "Priest/Cleric/Templar/";
				break;
				
			case 14:
				$path = "Priest/Cleric/Inquisitor/";
				break;
				
			case 15:
				$path = "Priest/Druid/";
				break;
				
			case 16:
				$path = "Priest/Druid/Warden/";
				break;
				
			case 17:
				$path = "Priest/Druid/Fury/";
				break;
				
			case 18:
				$path = "Priest/Shaman/";
				break;
				
			case 19:
				$path = "Priest/Shaman/Mystic/";
				break;
				
			case 20:
				$path = "Priest/Shaman/Defiler/";
				break;
				
			case 21:
				$path = "Mage/";
				break;
				
			case 22:
				$path = "Mage/Sorcerer/";
				break;
				
			case 23:
				$path = "Mage/Sorcerer/Wizard/";
				break;
				
			case 24:
				$path = "Mage/Sorcerer/Warlock/";
				break;
				
			case 25:
				$path = "Mage/Enchanter/";
				break;
				
			case 26:
				$path = "Mage/Enchanter/Illusionist/";
				break;
				
			case 27:
				$path = "Mage/Enchanter/Coercer/";
				break;
				
			case 28:
				$path = "Mage/Summoner/";
				break;
				
			case 29:
				$path = "Mage/Summoner/Conjuror/";
				break;
				
			case 30:
				$path = "Mage/Summoner/Necromancer/";
				break;
				
			case 31:
				$path = "Scout/";
				break;
				
			case 32:
				$path = "Scout/Rogue/";
				break;
				
			case 33:
				$path = "Scout/Rogue/Swashbuckler/";
				break;
				
			case 34:
				$path = "Scout/Rogue/Brigand/";
				break;
				
			case 35:
				$path = "Scout/Bard/";
				break;
				
			case 36:
				$path = "Scout/Bard/Troubador/";
				break;
				
			case 37:
				$path = "Scout/Bard/Dirge/";
				break;
				
			case 38:
				$path = "Scout/Predator/";
				break;
				
			case 39:
				$path = "Scout/Predator/Ranger/";
				break;
				
			case 40:
				$path = "Scout/Predator/Assassin/";
				break;
				
			case 41:
				$path = "Scout/Animist/";
				break;
				
			case 42:
				$path = "Scout/Animist/Beastlord/";
				break;
				
			case 43:
				$path = "Priest/Shaper/";
				break;
				
			case 44:
				$path = "Priest/Shaper/Channeler/";
				break;
				
		}
		return $path;
	}
	
	public function GetSpellData($spell_id)
	{
		global $eq2;
		
		$ret = NULL;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.spell_data WHERE id = %s", $spell_id);
		$ret = $eq2->RunQuerySingle();
		
		return $ret;
	}
	
	public function GetSpellCRC() 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT soe_spell_crc AS ret FROM `".ACTIVE_DB."`.spells WHERE id = %s", $this->spell_id);
		$ret = $eq2->RunQuerySingle();
		return ( $ret['ret'] > 0 ) ? $ret['ret'] : 0;
	}
	
	public function GetSpellName() 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT name AS ret FROM `".ACTIVE_DB."`.spells WHERE id = %s", $this->spell_id);
		$ret = $eq2->RunQuerySingle();
		return ( strlen($ret['ret']) > 0 ) ? $ret['ret'] : "Unknown";
	}
	
	public function GetSpellAALevels($level) 
	{
		if( $level==0 ) 
			$ret = "<option value=\"0\">---</option>\n";
		
		for( $i = 1; $i <= 10; $i++ )
			$ret .= sprintf("<option%s>%s</option>\n", ( $i == $level ) ? " selected" : "", $i);
		
		return $ret;
	}

	public function GetSOESpellType($type)
	{
		foreach($this->eq2SOESpellTypes as $key=>$val) 
		{
			//printf("%s => %s<br />", $key, $val);
			if( $type == $key )
				return $val;
		}
	}
	
	public function GetSpellBookTypes($type)
	{
		$type = ( isset($type) ) ? $type : -1;
			
		$ret = "";
		foreach($this->eq2SpellBookTypes as $key=>$val) 
			$ret .= sprintf("<option value='%s'%s>%s</option>", $key, ( $type == $key ) ? " selected" : "", $val);

		return $ret;		
	}
	
	public function GetSpellTargetTypes($type) 
	{
		$type = ( isset($type) ) ? $type : -1;
		
		$ret = "";
		foreach($this->eq2TargetTypes as $key=>$val) 
			$ret .= sprintf("<option value='%s'%s>%s</option>", $key, ( $type == $key ) ? " selected" : "", $val);

		return $ret;		
	}
	
	public function GetSpellTiers($tier) 
	{
		$ret = "";
		if( $tier==0 ) 
			$ret = "                  <option value=\"0\">---</option>\n";
			
		foreach($this->eq2SpellTiers as $key=>$val) 
			$ret .= sprintf("                  <option value='%s'%s>%s</option>\n", $key, ( $tier == $key ) ? " selected" : "", $val);

		return $ret;
	}

	public function GetSpellsMatching()
	{
		global $eq2;
		
		if( strlen($_POST['txtSearch']) > 0 )
		{
			$sql = sprintf("SELECT DISTINCT s.id, soe_spell_crc, name, description, level, type, given_by, is_active, is_aa, last_auto_update, icon, icon_backdrop FROM `".ACTIVE_DB."`.spells s LEFT JOIN `".ACTIVE_DB."`.spell_classes sc ON s.id = sc.spell_id LEFT JOIN `".ACTIVE_DB."`.spell_tiers st ON s.id = st.spell_id WHERE name RLIKE '%s' GROUP BY soe_spell_crc ORDER BY level", $eq2->SQLEscape($_POST['txtSearch']));
			//printf("%s<br />", $sql); exit;
			$rows = $eq2->RunQueryMulti($sql);
			
			foreach($rows as $row)
				$ret[] = $row;
			
			return $ret;
		}
		
		return 0;
	}
	
	public function HideSpellID()
	{
		$this->SetSpells("is_active", 2, $_POST['id']);
	}
	
	public function InsertStartingSpell()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("INSERT IGNORE INTO `".ACTIVE_DB."`.starting_spells (class_id, spell_id, tier) VALUES ('%s', '%s', 1)", $_POST['spell_classes|adventure_class_id'], $_GET['id']);
		$eq2->RunQuery();

		$eq2->SQLQuery = sprintf("SELECT MAX(slot)+1 as slot FROM `".ACTIVE_DB."`.starting_skillbar WHERE class_id = %s AND hotbar = 0", $_POST['spell_classes|adventure_class_id']);
		$data = $eq2->RunQuerySingle();
		$next_slot = $data['slot'] > 0 ? $data['slot'] : 0;
		
		$eq2->SQLQuery = sprintf("INSERT IGNORE INTO `".ACTIVE_DB."`.starting_skillbar (class_id, spell_id, slot, text_val) VALUES ('%s', '%s', '%s', '%s')", $_POST['spell_classes|adventure_class_id'], $_GET['id'], $next_slot, $eq2->db->sql_escape($_POST['objectName']));
		$eq2->RunQuery();
	}
	
	public function DeleteTier() 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spell_tiers WHERE spell_id = %s and tier = %s;", $_POST['orig_spell_id'], $_POST['tier']);
		$eq2->RunQuery();

		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spell_data WHERE spell_id = %s and tier = %s;", $_POST['orig_spell_id'], $_POST['tier']);
		$eq2->RunQuery();

		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spell_display_effects WHERE spell_id = %s and tier = %s;", $_POST['orig_spell_id'], $_POST['tier']);
		$eq2->RunQuery();
	}

	public function InsertTier() 
	{
		global $eq2;
		
		// if no tiers exist, insert 1 and bail out
		if( $_POST['tier_id'] == 0 )
		{
			$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spell_tiers (`spell_id`, `tier`) VALUES (%s, %s)", $_POST['spell_id'], $_POST['new_tier']);
			$eq2->RunQuery();
		}
		else // copy existing tier
		{
			$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spell_tiers (`spell_id`, `tier`, `hp_req`, `hp_req_percent`, `hp_upkeep`, `power_req`, `power_req_percent`, `power_upkeep`, `savagery_req`, `savagery_req_percent`, `savagery_upkeep`, `dissonance_req`, `dissonance_req_percent`, `dissonance_upkeep`, `req_concentration`, `cast_time`, `recovery`, `recast`, `radius`, `max_aoe_targets`, `min_range`, `range`, `duration1`, `duration2`, `resistibility`, `hit_bonus`, `call_frequency`, `unknown9`, `given_by`) " . 
																															"SELECT `spell_id`, '%s', `hp_req`, `hp_req_percent`, `hp_upkeep`, `power_req`, `power_req_percent`, `power_upkeep`, `savagery_req`, `savagery_req_percent`, `savagery_upkeep`, `dissonance_req`, `dissonance_req_percent`, `dissonance_upkeep`, `req_concentration`, `cast_time`, `recovery`, `recast`, `radius`, `max_aoe_targets`, `min_range`, `range`, `duration1`, `duration2`, `resistibility`, `hit_bonus`, `call_frequency`, `unknown9`, `given_by` " .
																															"FROM `".ACTIVE_DB."`.spell_tiers " .
																															"WHERE spell_id = %s " .
																															"ORDER BY tier " .
																															"LIMIT 0,1",
																															$_POST['new_tier'],
																															$_POST['spell_id']);
			$eq2->RunQuery();
	
			$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spell_data (`spell_id`, `tier`, `index_field`, `value_type`, `value`) " .
																														 "SELECT `spell_id`, '%s', `index_field`, `value_type`, `value` " .
																														 "FROM `".ACTIVE_DB."`.spell_data " .
																														 "WHERE spell_id = %s " . 
																														 "GROUP BY spell_id, index_field " .
																														 "ORDER BY tier, index_field ",
																														 $_POST['new_tier'],
																														 $_POST['spell_id']);
			$eq2->RunQuery();
	
			$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spell_display_effects (`spell_id`, `tier`, `percentage`, `description`, `bullet`, `index`) " .
																																				"SELECT `spell_id`, '%s', `percentage`, `description`, `bullet`, `index` " . 
																																				"FROM `".ACTIVE_DB."`.spell_display_effects " . 
																																				"WHERE spell_id = %s " .
																																				"GROUP BY spell_id, `index` " .
																																				"ORDER BY tier, `index` ",
																																				$_POST['new_tier'],
																																				$_POST['spell_id']);
			$eq2->RunQuery();
		}
	}

	public function is_spell_aa($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT COUNT(*) as is_aa FROM `".ACTIVE_DB."`.spell_aa_nodelist aa, `".ACTIVE_DB."`.spells s WHERE s.soe_spell_crc = aa.spellcrc AND s.id = %s", $id);
		$ret = $eq2->RunQuerySingle();
		
		return $ret['is_aa'] > 0 ? $ret['is_aa'] : 0;
	}
	
	public function is_spell_trait($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT COUNT(*) as is_trait FROM `".ACTIVE_DB."`.spell_tiers WHERE given_by in ('charactertrait', 'racialinnate') AND spell_id = %s", $id);
		$ret = $eq2->RunQuerySingle();
		
		return $ret['is_trait'] > 0 ? $ret['is_trait'] : 0;
	}

	private function ParseSpellRange($str)
	{
		$ret = NULL;
		if( strlen($str) > 0 )
		{
			$myArr = explode(" ", $str);
			if( is_array($myArr) )
			{
				foreach($myArr as $var)
				{
					if( intval($var) > 0 )
						$ret = intval($var);
					else
						continue;
				}
			}
		}
		return $ret;
	}
	
	
	private function ParseSpellResistibility($str)
	{
		$ret = NULL;
		if( strlen($str) > 0 )
		{
			$myArr = explode(" ", $str);
			if( is_array($myArr) )
			{
				foreach($myArr as $var)
				{
					if( intval($var) > 0 )
						$resist = intval($var);
					else
						$direction = $var;
				}
				
				if( $direction == "Easier" )
					$ret = (100 - $resist) / 100;
			}
		}
		return $ret;
	}
	
	private function ParseSpellSkill($skill)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT id FROM `".ACTIVE_DB."`.skills WHERE short_name = '%s' LIMIT 0,1", $skill);
		$row = $eq2->RunQuerySingle();
		
		if( $row['id'] > 0 )
			$ret = $row['id'];
		else
			die("Cannot find skill: " . $skill . ". May be new?");
	
		return $ret;
	}

	public function PrintTierName($tier)
	{
		return sprintf("Tier Data for: %s (%s)", $this->eq2SpellTiers[$tier], $tier);
	}
	
	public function PrintOffsiteLinks()
	{
		?>
		<div style="float:right">
			<a href="http://census.daybreakgames.com/xml/get/eq2/spell/?crc=<?php print($this->spell_crc); ?>&c:limit=100&c:sort=tier" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
			<a href="http://eq2.wikia.com/wiki/<?php print($this->spell_name); ?>" target="_blank"><img src="../images/wikia.png" border="0" align="top" title="Wikia" alt="Wikia" height="20" /></a>
			<a href="http://eq2.zam.com/search.html?q=<?php print($this->spell_name); ?>" target="_blank"><img src="../images/zam.png" border="0" align="top" title="Zam" alt="Zam" height="20" /></a>								
		</div>
		<?php
	}

	public function GetOffsiteLinks()
	{
		$strHTML = "";
		$strHTML .= "        <div style='float:right'>\n";
		$strHTML .= "          <a href='http://census.daybreakgames.com/xml/get/eq2/spell/?crc=" . $this->spell_crc . "&c:limit=100&c:sort=tier' target='_blank'><img src='../images/soe.png' border='0' align='top' title='SOE' alt='SOE' height='20' /></a>\n";
		$strHTML .= "          <a href='http://eq2.wikia.com/wiki/" . $this->spell_name . "' target='_blank'><img src='../images/wikia.png' border='0' align='top' title='Wikia' alt='Wikia' height='20' /></a>\n";
		$strHTML .= "          <a href='http://eq2.zam.com/search.html?q=" . $this->spell_name . "' target='_blank'><img src='../images/zam.png' border='0' align='top' title='Zam' alt='Zam' height='20' /></a>\n";
		$strHTML .= "        </div>\n";
		return($strHTML);
	}
	
	public function BuildLUAFunction($type)
	{
		global $eq2;
		
		$ret = NULL;

		switch($type)
		{
			case "Generic":
				$ret = "\r\n" .
							 "function cast(Caster, Target)\r\n" .
							 "\r\n" .
							 "end\r\n";
				break;

			case "Heal":
				$ret = "\r\n" .
							 "function cast(Caster, Target, MinVal, MaxVal)\r\n" .
							 "    if MaxVal ~= nil and MinVal < MaxVal then\r\n" .
							 "        ModifyHP(Target, math.random(MinVal, MaxVal))\r\n" .
							 "    else\r\n" .
							 "        ModifyHP(Target, MinVal)\r\n" .
							 "    end\r\n" .
							 "end\r\n";
				break;

			case "Damage":
				$ret = "\r\n" .
							 "function cast(Caster, Target, DmgType, MinVal, MaxVal)\r\n" .
							 "    if MaxVal ~= nil and MinVal < MaxVal then\r\n" .
							 "        SpellDamage(Target, DmgType, math.random(MinVal, MaxVal))\r\n" .
							 "    else\r\n" .
							 "        SpellDamage(Target, DmgType, MinVal)\r\n" .
							 "    end\r\n" .
							 "end\r\n";
				break;
				
			case "DoT":
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
				
			case "AddBonus":
				$ret = "    AddSpellBonus(Target, 600, BonusAmt)\n";
				break;
				
			case "AddSkill":
				$ret = "    AddSkillBonus(Target, GetSkillIDByName(\"Slashing\"), SkillAmt)\n";
				break;
				
			case "AddControl":
				$ret = "    AddControlEffect(Target, 1)\n";
				break;
				
			case "Tick":
				$ret = "\r\n" .
							 "function tick(Caster, Target)\r\n" .
							 "\r\n" .
							 "end\r\n";
				break;
				
			case "Remove":
				$ret = "\r\n" .
							 "function remove(Caster, Target)\r\n" .
							 "\r\n" .
							 "end\r\n";
				break;
				
			case "RemoveBonus":
				$ret = "    RemoveSpellBonus(Target)\n";
				break;
				
			case "RemoveSkill":
				$ret = "    RemoveSkillBonus(Target)\n";
				break;
				
			case "RemoveControl":
				$ret = "    RemoveControlEffect(Target, 1)\n";
				break;
				
			case "Interrupt":
				$ret = "    Interrupt(Target)\n";
				break;
				
			case "Say":
				$ret = "    Say(Caster, \"Say What?\")\n";
				break;
				
			case "NotImplemented":
				$ret = "\r\n" .
							 "function cast(Caster, Target)\r\n" .
							 "    Say(Target, \"Hah, nice try! That's not implemented yet!\")\r\n" .
							 "end\r\n";
				break;
				
			case "Data":
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
	
	public function RebuildSpellLUAscript()
	{
		global $eq2;
		
		$script_relative_path = ( strlen($_POST['saveSpellScript']) > 0 ) ? $_POST['saveSpellScript'] : "";
		$script_text = $eq2->CreateLUATemplate($script_relative_path);
		$eq2->CreateLUAScript($script_relative_path, $script_text);
	}
	
	function ReSyncAllFromRawData()
	{
		global $eq2;
		$this->silent = true;
		
		$sql = "SELECT DISTINCT id, name, soe_spell_crc FROM `" . ACTIVE_DB . "`.`spells`";
		
		$rows = $eq2->RunQueryMulti($sql);
		
		foreach($rows as $data)
		{
			$this->spell_name = $data['name'];
			$this->spell_id = $data['id'];
			$this->crc = $data['soe_spell_crc'];
			$this->ReSyncFromRawData();
		}
	}
	
	public function ReSyncFromRawData()
	{
		global $eq2;
		
		if( $this->spell_crc > 0 )
		{
			$eq2->SQLQuery = sprintf("SELECT spell_id, `name`, `type`, class_skill, mastery_skill, min_class_skill_req, target, can_effect_raid, affect_only_group_members, display_spell_tier, group_spell, success_message, effect_message FROM `".PARSER_DB."`.raw_spells WHERE spell_id = %s ORDER BY tier LIMIT 0,1;", $this->spell_crc);
			$data = $eq2->RunQuerySingle();
			
			if( !is_array($data) && !$this->silent )
				die("No RAW spell data for crc: " . $this->spell_crc);
	
			// do a quick sanity check to ensure we're reading the right raw spell data
			if( strcmp($data['name'], $this->spell_name) > 0 )
			{
				printf("<p>Raw Spell data mismatch: Spell '%s', Raw '%s'</p>", $this->spell_name, $data['name']);
				print("<p>Spell Info:</p>");
				print_r($_POST);
				print("<p>Raw Data:</p>");
				print_r($data);
				die();
			}
			
			$eq2->ObjectName = $eq2->db->sql_escape($data['name']);
			$eq2->TableName = "spells";
			
			// not much data in raw_spells to update that isn't more current on SOE data, so only take stuff that is definitely different
			$raw_spell_type									= $data['type'];
			$raw_class_skill								= $data['class_skill'];
			$raw_mastery_skill							= $data['mastery_skill'];
			$min_class_skill_req						= $data['min_class_skill_req'];
			$raw_target											= $data['target'];
			$raw_can_effect_raid						= $data['can_effect_raid'];
			$raw_affect_only_group_members	= $data['affect_only_group_members'];
			$raw_display_spell_tier 				= $data['display_spell_tier'];
			$raw_group_spell								= $data['group_spell'];
			$raw_success_message						= $eq2->db->sql_escape($data['success_message']);
			$raw_effect_message							= $eq2->db->sql_escape($data['effect_message']);
			
			$query_array = array();
			
			$query_array[] = sprintf("UPDATE `".ACTIVE_DB."`.spells SET type = '%s', class_skill = '%s', mastery_skill = '%s', min_class_skill_req = '%s', target_type = '%s', can_effect_raid = '%s', affect_only_group_members = '%s', display_spell_tier = '%s', group_spell = '%s', success_message = '%s', effect_message = '%s' WHERE id = %s", 
																 $raw_spell_type,
																 $raw_class_skill,
																 $raw_mastery_skill,
																 $min_class_skill_req,
																 $raw_target,
																 $raw_can_effect_raid,
																 $raw_affect_only_group_members,
																 $raw_display_spell_tier,
																 $raw_group_spell,
																 $raw_success_message,
																 $raw_effect_message,
																 $this->spell_id);
			
			$query_array[] = sprintf("UPDATE `".PARSER_DB."`.raw_spells SET populated_spell_id  = '%s' WHERE spell_id = %s", 
															 $this->spell_id,
															 $this->spell_crc);
		}
		else
			die("CRC Not Set!");
		
		// commit SQL
		if( is_array($query_array) )
		{
			foreach($query_array as $Query)
				$eq2->RunQuery(true, $Query);
		}
	}
	
	public function SaveClonedSpell()
	{
		global $eq2;
		
		// Spells clone
		$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spells (`id`, `type`, `cast_type`, `name`, `description`, `icon`, `icon_heroic_op`, `icon_backdrop`, `class_skill`, `mastery_skill`, `min_class_skill_req`, " . 
																														 "`duration_until_cancel`, `target_type`, `success_message`, `fade_message`, `interruptable`, `lua_script`, `spell_visual`, `effect_message`, `spell_book_type`, " . 
																														 "`can_effect_raid`, `affect_only_group_members`, `display_spell_tier`, `friendly_spell`, `group_spell`, `linked_timer_id`, `is_aa`, `is_deity`, `deity`, `last_auto_update`) " . 
																														 "SELECT %s, `type`, `cast_type`, `name`, `description`, `icon`, `icon_heroic_op`, `icon_backdrop`, `class_skill`, `mastery_skill`, `min_class_skill_req`, " . 
																														 "`duration_until_cancel`, `target_type`, `success_message`, `fade_message`, `interruptable`, `lua_script`, `spell_visual`, `effect_message`, `spell_book_type`, " . 
																														 "`can_effect_raid`, `affect_only_group_members`, `display_spell_tier`, `friendly_spell`, `group_spell`, `linked_timer_id`, `is_aa`, `is_deity`, `deity`, '%s' " . 
																														 "FROM `".ACTIVE_DB."`.spells WHERE id = %s", $_POST['next_id'], time(), $this->spell_id);
		$eq2->RunQuery();

		// Spell Tiers clone
		$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spell_tiers (`spell_id`, `tier`, `hp_req`, `hp_req_percent`, `hp_upkeep`, `power_req`, `power_req_percent`, `power_upkeep`, `savagery_req`, `savagery_req_percent`, `savagery_upkeep`, " . 
																																	"`dissonance_req`, `dissonance_req_percent`, `dissonance_upkeep`, `req_concentration`, `cast_time`, `recovery`, `recast`, `radius`, `max_aoe_targets`, `min_range`, `range`, " . 
																																	"`duration1`, `duration2`, `resistibility`, `hit_bonus`, `call_frequency`, `unknown9`, `given_by`) " . 
																																	"SELECT %s, `tier`, `hp_req`, `hp_req_percent`, `hp_upkeep`, `power_req`, `power_req_percent`, `power_upkeep`, `savagery_req`, `savagery_req_percent`, `savagery_upkeep`, " . 
																																	"`dissonance_req`, `dissonance_req_percent`, `dissonance_upkeep`, `req_concentration`, `cast_time`, `recovery`, `recast`, `radius`, `max_aoe_targets`, `min_range`, `range`, " . 
																																	"`duration1`, `duration2`, `resistibility`, `hit_bonus`, `call_frequency`, `unknown9`, `given_by` " . 
																																	"FROM `".ACTIVE_DB."`.spell_tiers WHERE spell_id = %s ORDER BY tier", $_POST['next_id'],$this->spell_id);
		$eq2->RunQuery();
		
		// Spell Traits clone
		$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spell_traits (`spell_id`, `level`, `class_req`, `race_req`, `isInate`, `isFocusEffect`, `tier`, `group`) " . 
																																	 "SELECT %s, `level`, `class_req`, `race_req`, `isInate`, `isFocusEffect`, `tier`, `group` " . 
																																	 "FROM `".ACTIVE_DB."`.spell_traits WHERE spell_id = %s ORDER BY tier", $_POST['next_id'],$this->spell_id);
		$eq2->RunQuery();

		// Spell Data clone
		$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spell_data (`spell_id`, `tier`, `index_field`, `value_type`, `value`) " . 
																																 "SELECT %s, `tier`, `index_field`, `value_type`, `value` " . 
																																 "FROM `".ACTIVE_DB."`.spell_data WHERE spell_id = %s ORDER BY tier", $_POST['next_id'],$this->spell_id);
		$eq2->RunQuery();

		// Spell Effects clone
		$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spell_display_effects (`spell_id`, `tier`, `percentage`, `description`, `bullet`, `index`) " . 
																																						"SELECT %s, `tier`, `percentage`, `description`, `bullet`, `index` " . 
																																						"FROM `".ACTIVE_DB."`.spell_display_effects WHERE spell_id = %s ORDER BY tier", $_POST['next_id'],$this->spell_id);
		$eq2->RunQuery();
	}
	
	public function SaveReIndexedSpell()
	{
		global $eq2;
		
		// process the move
		$this->SetSpells("id", $_POST['next_id'], $this->spell_id);
		
		// character_skillbar has no constraint, due to no spell_id 0
		$eq2->SQLQuery = sprintf("UPDATE `".ACTIVE_DB."`.character_skillbar SET spell_id = %s WHERE spell_id = %s", $_POST['next_id'], $this->spell_id);
		$eq2->RunQuery();

		// item_details_skill currently points spell_id to soe_spell_crc, which is wrong... but for the future, we'll change it manually too
		//$eq2->SQLQuery = sprintf("UPDATE ".ACTIVE_DB.".item_details_skill SET spell_id = %s WHERE spell_id = %s", $_POST['next_id'], $_POST['old_id']);
		//$eq2->RunQuery();

		// starting_skillbar has no constraint, due to no spell_id 0
		$eq2->SQLQuery = sprintf("UPDATE `".ACTIVE_DB."`.starting_skillbar SET spell_id = %s WHERE spell_id = %s", $_POST['next_id'], $this->spell_id);
		$eq2->RunQuery();
	}

	public function SetLUAScript()
	{
		$this->SetSpells("lua_script", $_POST['spellName']);
	}

	public function SetLastAutoUpdate()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("UPDATE `".ACTIVE_DB."`.spells SET last_auto_update = '%s' WHERE id = %s", time(), $this->spell_id);
		$eq2->RunQuery(false); // don't log this update
	}
	
	private function SetSpells($field, $value, $id = 0)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("UPDATE `".ACTIVE_DB."`.spells SET %s = '%s' WHERE id = %s", $field, $value, ( $id > 0 ) ? $id : $this->spell_id);
		$eq2->RunQuery();
	}

	public function DeleteSpell()
	{
		global $eq2;
		
		// these should all return 0 rows affected, if the constraints are working... just a safety precaution
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spell_classes WHERE spell_id = %s", $_POST['orig_id']);
		$eq2->RunQuery();
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spell_display_effects WHERE spell_id = %s", $_POST['orig_id']);
		$eq2->RunQuery();
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spell_data WHERE spell_id = %s", $_POST['orig_id']);
		$eq2->RunQuery();
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spell_traits WHERE spell_id = %s", $_POST['orig_id']);
		$eq2->RunQuery();
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spell_tiers WHERE spell_id = %s", $_POST['orig_id']);
		$eq2->RunQuery();
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spells WHERE id = %s", $_POST['orig_id']);
		$eq2->RunQuery();
		
	}
	
	public function UpdateFromSOEData()
	{
		switch($_POST['table_name'])
		{
			case "spells":
				$this->UpdateSpellFromSOEData();
				break;
				
			case "spell_tiers":
				$this->UpdateSpellTierFromSOEData();
				break;
				
		}
	}

	private function UpdateSpellFromSOEData()
	{
		global $eq2;
		
		$spell_id = intval($_GET['id']);
	
		if( $spell_id > 0 )
		{			
			$DataURL = sprintf("http://census.daybreakgames.com/json/get/eq2/spell/?crc=%s", $_POST['soe_spell_crc']);
			//printf("%s<br />", $DataURL);
			
			$spell_info = json_decode($eq2->file_get_contents_curl($DataURL), true);
	
			if( is_array($spell_info["spell_list"]) && isset($spell_info["spell_list"][0]["name"]) )
			{
				// stuff spell_list data into a new array to make it less path-y
				$si = $spell_info["spell_list"];
				//print_r($si); exit;

				foreach ($si[0] as $k=>$v) {
					if (is_string($v)) {
						$d = html_entity_decode($v);
						while ($d != html_entity_decode($d)){
							$d = html_entity_decode($d);
						}
						$si[0][$k] = $d;
					}
				}
				
				$spells['name'] = $si[0]["name"];
				$spells['friendly_spell'] = $si[0]["beneficial"];
				//$spells['description_pvp'] = $si[0][description_pvp];
				$spells['description'] = $si[0]["description"];
				$spells['icon'] = ( $si[0]["icon"]["id"] >= 0 ) ? $si[0]["icon"]["id"] : 65535;
				$spells['icon_heroic_op'] = ( $si[0]["icon"]["icon_heroic_op"] >= 0 ) ? $si[0]["icon"]["icon_heroic_op"] : 65535;
				$spells['icon_backdrop'] = ( $si[0]["icon"]["backdrop"] >= 0 ) ? $si[0]["icon"]["backdrop"] : 65535;
				$spells['spell_book_type'] = $si[0]["typeid"];
				//$spells['spellbook'] = $si[0][spellbook];
				$spells['duration_until_cancel'] = $si[0]["duration"]["does_not_expire"]; // 0 or 1
				$spells['last_auto_update'] = time();
				
				$DataURL = sprintf("http://census.daybreakgames.com/json/get/eq2/item/?typeinfo.spellid=%s&c:show=spelltarget,requiredskill.min_skill,requiredskill.text", $_POST['soe_spell_crc']);
				$spell_info_item = json_decode($eq2->file_get_contents_curl($DataURL), true);
				
				if( $spell_info_item["returned"] > 0 )
				{
					$sii = $spell_info_item["item_list"][0];
					
					$spells["min_class_skill_req"] 	= ( !empty($sii["requiredskill"]["min_skill"]) ) ? $sii["requiredskill"]["min_skill"] : 0;
					$spells["class_skill"] 					= ( !empty($sii["requiredskill"]["text"]) ) ? $this->ParseSpellSkill($sii["requiredskill"]["text"]) : 0;
					$spells["target_type"] 					= ( !empty($sii["typeinfo"]["spelltarget"]) ) ? $eq2->eq2TargetTypes[$sii["typeinfo"]["spelltarget"]] : 1;
				}
				
				$sets = "";
				foreach($spells as $key=>$val)
				{
					if( empty($sets) )
						$sets .= sprintf("%s = '%s'", $key, $eq2->SQLEscape($val));
					else
						$sets .= sprintf(", %s = '%s'", $key, $eq2->SQLEscape($val));
				}
				
				if( !empty($sets) ) 
				{
					$eq2->SQLQuery = sprintf("UPDATE `".ACTIVE_DB."`.spells SET %s WHERE id = %s;", $sets, $spell_id);
					$eq2->RunQuery();
					
					$this->SetLastAutoUpdate();
				}		
			}
			else
				printf('<span style="font-size:14px; font-weight:bold; color:#f00">No data for this spell (%u).</span>', $spell_id);
		}
	}
		
	
	private function UpdateSpellTierFromSOEData()
	{
		global $eq2;
	
		$spell_id = intval($_GET['id']);
	
		if( $spell_id > 0 )
		{
			$DataURL = sprintf("http://census.daybreakgames.com/s:Darksun/json/get/eq2/spell/?crc=%s&tier=%s", $_POST['soe_spell_crc'], $_POST['tier']);
			$spell_info = json_decode($eq2->file_get_contents_curl($DataURL), true);
			
			if( is_array($spell_info['spell_list']) && isset($spell_info['spell_list'][0]['name']) )
			{
				$si = $spell_info['spell_list'][0];
	
				$spell_tiers['hp_req'] = $si['cost']['health'];
				$spell_tiers['hp_upkeep'] = $si['cost']['per_tick']['health'];
				$spell_tiers['power_req'] = $si['cost']['power'];
				$spell_tiers['power_upkeep'] = $si['cost']['per_tick']['power'];
				$spell_tiers['savagery_req'] = $si['cost']['savagery'];
				$spell_tiers['savagery_upkeep'] = $si['cost']['per_tick']['savagery'];
				$spell_tiers['dissonance_req'] = $si['cost']['dissonance'];
				$spell_tiers['dissonance_upkeep'] = $si['cost']['per_tick']['dissonance'];
				$spell_tiers['req_concentration'] = $si['cost']['concentration'];
				$spell_tiers['cast_time'] = $si['cast_secs_hundredths'];
				$spell_tiers['recovery'] = $si['recovery_secs_tenths'];
				$spell_tiers['recast'] = round($si['recast_secs']);
				$spell_tiers['max_aoe_targets'] = $si['max_targets'];
				$spell_tiers['radius'] = $si['aoe_radius_meters'];
				$spell_tiers['duration1'] = $si['duration']['min_sec_tenths'];
				$spell_tiers['duration2'] = $si['duration']['max_sec_tenths'];
				$spell_tiers['given_by'] = $si['given_by'];
	
				$DataURL = sprintf("http://census.daybreakgames.com/s:Darksun/json/get/eq2/item/?typeinfo.spellid=%s&typeinfo.tier=%s&c:show=typeinfo.spellrange,typeinfo.resistability", $_POST['soe_spell_crc'], $_POST['tier']);
				
				$spell_info_item = json_decode($eq2->file_get_contents_curl($DataURL), true);
				
				if( is_array($spell_info_item)  && isset($spell_info_item['item_list'][0]))
				{
					$sii = $spell_info_item['item_list'][0];
					
					if( !empty($sii['typeinfo']['spellrange']) )
						$spell_tiers['range'] = $this->ParseSpellRange($sii['typeinfo']['spellrange']);
					
					if( !empty($sii['typeinfo']['resistability']) && $sii['typeinfo']['resistability'] != "na" )
						$spell_tiers['resistibility'] = $this->ParseSpellResistibility($sii['typeinfo']['resistability']);
				}
				
				foreach($spell_tiers as $key=>$val)
				{
					if( !empty($sets) ) {
						$sets .= ',';
					}
					$sets .= sprintf("`%s` = '%s'", $key, $eq2->db->sql_escape($val));
				}
				
				if( !empty($sets) ) 
				{
					if( $_POST['tier'] >= 1 && $_POST['tier'] <= 3 )
						$eq2->SQLQuery = sprintf("UPDATE `".ACTIVE_DB."`.spell_tiers SET %s WHERE spell_id = %s AND tier IN (1, 2, 3);", $sets, $_GET['id']); 
					else
						$eq2->SQLQuery = sprintf("UPDATE `".ACTIVE_DB."`.spell_tiers SET %s WHERE spell_id = %s AND tier = %s;", $sets, $_GET['id'], $_POST['tier']); 
	
					$eq2->RunQuery();
					
					$this->SetLastAutoUpdate();
				}		
	
			}
			else
				printf('<span style="font-size:14px; font-weight:bold; color:#f00">No tier %s data for this spell.</span>', $_GET['tier']);
		}
	}

	public function PrintSpellTypeOptions($selected_type) 
	{
		$spell_types = array("Unset", "DD", "DoT", "Heal", "HoT-Ward", "Debuff", "Buff", "CombatBuff",
		"Taunt", "Detaunt", "Rez", "Cure", "Food", "Drink", "Root", "Snare", "GroupTarget");

		foreach ($spell_types as $t) {
			printf('<option value="%s"%s>%s</option>', $t, $t == $selected_type ? " selected" : "", $t);
		}
	}

	public function DisplaySpellToggles($spell) {
		global $eq2;
		?>

		<fieldset style="display:inline">
			<legend>Toggles</legend>
			<div id="spellTogglesGrid">
			<?php foreach($this->emuSpellToggles['spells'] as $field=>$alias) : ?>
				<table>
					<tr>
						<td>
							<?php
							if (is_integer($field)) {
								$field = $alias;
							} 
							printf("<strong%s>%s:</strong>", $field == "is_active" ? ' style=color:red;' : "", $alias);
							printf('<input type="hidden" name="orig_%s" value="%s"/>', $field, $spell[$field] ? 1 : 0);
							 ?>
						</td>
						<td>
							<?php $eq2->GenerateBlueCheckbox(sprintf('spells|%s', $field), $spell[$field] != 0); ?>
						</td>
					</tr>
				</table>		
			<?php endforeach; ?>
			</div>
		</fieldset>
		
		<?php
	}

	public function HandleCheckBoxes() {
		$tab = $_GET['tab'] ?? "";

		if ($tab == "spells") {
			foreach ($this->emuSpellToggles['spells'] as $field=>$alias) {
				if (is_integer($field)) {
					$field = $alias;
				}
				$name = sprintf('spells|%s', $field);
				$_POST[$name] = isset($_POST[$name]) ? 1 : 0;
			}
		}
	}
	/*
	private function UpdateSpellEffectsFromSOEData()
	{
		global $eq2;
	
		$spell_id = intval($_GET['id']);
	
		if( $spell_id > 0 )
		{
			// Start on spell_display_effects
			// set these for use in replacing display effects as well as parsing for spell data (lua params)
			if( is_array($si['effect_list']) )
			{
				foreach($si['effect_list'] as $key=>$val)
				{
					$spell_display_effects[$key]['description'] = $val['description'];
					$spell_display_effects[$key]['bullet'] = $val['indentation'];
				}
			}
	
			if( is_array($spell_display_effects) )
			{
				$values = "";
				foreach($spell_display_effects as $effect)
				{
					if( empty($values) )
						$values .= sprintf("('%s', '%s', '%s', '%s')", $_GET['id'], $_GET['tier'], $eq2->db->sql_escape($effect['description']), $effect['bullet']);
					else
						$values .= sprintf(", ('%s', '%s', '%s', '%s')", $_GET['id'], $_GET['tier'], $eq2->db->sql_escape($effect['description']), $effect['bullet']);
				}
					
				if( !empty($values) ) 
				{
					// delete only if there is something to re-insert 
					$eq2->SQLQuery = sprintf("DELETE FROM ".ACTIVE_DB.".spell_display_effects WHERE spell_id = %s and tier = %s;", $_GET['id'], $_GET['tier']);
					$eq2->RunQuery();
					
					$eq2->SQLQuery = sprintf("INSERT INTO ".ACTIVE_DB.".spell_display_effects (spell_id, tier, description, bullet) VALUES %s;", $values); 
					$eq2->RunQuery();
						
					$this->SetLastAutoUpdate();
				}
				
			}
	
		}
		
	}*/


	/****************************************************************************
	 * Misc Functions
	 ****************************************************************************/







	/****************************************************************************
	 * Obsolete Functions
	 * These are commented out until I can determine if they are called anymore
	 * If not, delete them forever
	 ****************************************************************************/
//	function processHideSingleSpell()
//	{
//		$query = sprintf("UPDATE ".PARSER_DB.".raw_spells SET processed = 2 WHERE spell_id = %s;", $_POST['orig_spell_id']);
//		$this->db->sql_query($query);
//	}
//
//	function processMigrateSingleSpell($orig_spell_id, $new_spell_id, $continue = false)
//	{
//		$raw_spell_type									= 0;
//		$raw_class_skill								= 0;
//		$raw_mastery_skill							= 0;
//		$raw_duration_flag							= 0;
//		$raw_can_effect_raid						= 0;
//		$raw_affect_only_group_members	= 0;
//		$raw_display_spell_tier 				= 0;
//		$raw_group_spell								= 0;
//		$raw_success_message						= "";
//		$raw_effect_message							= "";
//		
//		$start_time = time();
//		
//		$this->id = $new_spell_id;
//		
//		/*** Get SOE data for the spell we're trying to migrate, which can also be a manually entered crc ID ***/
//		if( !$this->GetSOESpellData($orig_spell_id) )
//		{
//			if( !$continue)
//				die("No SOE data found.");
//			else
//			{
//				$query = sprintf("UPDATE ".PARSER_DB.".raw_spells SET processed = 2 WHERE spell_id = %s;", $orig_spell_id);
//				$this->db->sql_query($query);
//				return;
//			}
//		}
//		
//		$start_our = time();
//
//		if( strlen($this->spells[name]) > 1 )
//			$spell_name = $this->spells[name];
//		else
//			die("No spell name for crc: " . $orig_spell_id);
//
//		/*** Fetch spell from raw data - but if it does not exist, process anyway based on SOE data crc provided ***/
//		$query = sprintf("SELECT `type`, class_skill, mastery_skill, duration_flag, can_effect_raid, affect_only_group_members, display_spell_tier, group_spell, success_message, effect_message " .
//												"FROM ".PARSER_DB.".raw_spells " . 
//												"WHERE spell_id = %s " .
//												"LIMIT 0,1;", 
//												$orig_spell_id
//												);
//		if( !$result = $this->db->sql_query($query) )
//			$this->DBError($query);
//		
//		if( $this->db->sql_numrows($result) > 0 )
//		{
//			$data = $this->db->sql_fetchrow($result);
//			
//			$raw_spell_type									= $data['type'];
//			$raw_class_skill								= $data['class_skill'];
//			$raw_mastery_skill							= $data['mastery_skill'];
//			$raw_duration_flag							= $data['duration_flag'];
//			$raw_can_effect_raid						= $data['can_effect_raid'];
//			$raw_affect_only_group_members	= $data['affect_only_group_members'];
//			$raw_display_spell_tier 				= $data['display_spell_tier'];
//			$raw_group_spell								= $data['group_spell'];
//			$raw_success_message						= $data['success_message'];
//			$raw_effect_message							= $data['effect_message'];
//		}
//		/*** ***/
//	
//		$query_array = array();
//		
//		// create insert spells query using data from raw_spells and census.daybreakgames.com
//		// note: removed ACTIVE_DB for logging purposes - do I even need it?
//		$query_array[] = sprintf("INSERT INTO spells (id, soe_spell_crc, type, name, description, icon, icon_heroic_op, icon_backdrop, class_skill, mastery_skill, min_class_skill_req, duration_until_cancel, target_type, spell_book_type, can_effect_raid, affect_only_group_members, display_spell_tier, friendly_spell, group_spell, success_message, effect_message, is_aa, is_deity, last_auto_update, soe_last_update) VALUES (%s, %s, %s, '%s', '%s', %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, '%s', '%s', %s, %s, %s, %s)",
//															$this->id,
//															$orig_spell_id,
//															$raw_spell_type,
//															$this->db->sql_escape($this->spells[name]),
//															$this->db->sql_escape($this->spells[description]),
//															$this->spells[icon],
//															$this->spells[icon_heroic_op],
//															$this->spells[icon_backdrop],
//															$raw_class_skill,
//															$raw_mastery_skill,
//															$this->spells[min_class_skill_req],
//															$raw_duration_flag,
//															$this->spells[target_type]==0 ? "target" : $this->spells[target_type],
//															$this->spells[spell_book_type],
//															$raw_can_effect_raid,
//															$raw_affect_only_group_members,
//															$raw_display_spell_tier,
//															$this->spells[friendly_spell],
//															$raw_group_spell,
//															$this->db->sql_escape($raw_success_message),
//															$this->db->sql_escape($raw_effect_message),
//															$this->spells[is_aa],
//															$this->spells[is_deity],
//															time(),
//															round($this->spells[soe_last_update])
//															);
//		
//		// create insert spell_classes query using just data from census.daybreakgames.com
//		if( is_array( $this->spell_classes ) )
//		{
//			foreach( $this->spell_classes as $key=>$val )
//				$query_array[] = sprintf("INSERT IGNORE INTO spell_classes (spell_id, adventure_class_id, tradeskill_class_id, `level`) VALUES ('%s', '%s', '%s', '%s');", $this->id, $key, 255, $val);
//		}
//
//		// create insert spell_tiers query using just data from census.daybreakgames.com
//		// ** after comparing many records, it does not appear raw_spell_details has anything other than what SOE API offers
//		if( is_array( $this->spell_tier ) )
//		{
//			foreach($this->spell_tier as $tKey=>$tVal)
//			{
//				$tier = $tKey;
//				$query_array[] = sprintf("INSERT INTO spell_tiers (`spell_id`,`tier`,`radius`,`cast_time`,`max_aoe_targets`,`recast`,`recovery`,`req_concentration`,`dissonance_req`,`hp_req`,`power_req`,`savagery_req`,`dissonance_upkeep`,`hp_upkeep`,`power_upkeep`,`savagery_upkeep`,`duration1`,`duration2`, `range`, `resistibility`) " . 
//																	 "VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');",
//																	 $this->id, 
//																	 $tier,
//																	 $tVal[radius],
//																	 $tVal[cast_time],
//																	 $tVal[max_aoe_targets],
//																	 $tVal[recast],
//																	 $tVal[recovery],
//																	 $tVal[req_concentration],
//																	 $tVal[dissonance_req],
//																	 $tVal[hp_req],
//																	 $tVal[power_req],
//																	 $tVal[savagery_req],
//																	 $tVal[dissonance_upkeep],
//																	 $tVal[hp_upkeep],
//																	 $tVal[power_upkeep],
//																	 $tVal[savagery_upkeep],
//																	 $tVal[duration1],
//																	 $tVal[duration2],
//																	 $tVal['range'],
//																	 $tVal[resistibility]
//																	 );
//			
//			}
//		}
//		
//		// create insert spell_display_effects query using just data from census.daybreakgames.com
//		// raw_spell_effects is generally wrong, due to player stat adjustments
//		if( is_array( $this->spell_fx ) )
//		{
//			foreach($this->spell_fx as $tKey=>$tVal)
//			{
//				$tier = $tKey;
//				
//				foreach($tVal as $key=>$val)
//				{
//					$index = $key;
//					$query_array[] = sprintf("INSERT INTO spell_display_effects (`spell_id`, `tier`, `description`, `bullet`, `index`) " . 
//																		 "VALUES ('%s', '%s', '%s', '%s', '%s');",
//																		 $this->id, 
//																		 $tier,
//																		 $this->db->sql_escape($val[description]),
//																		 $val[bullet], 
//																		 $index
//																		 );
//				}
//			}
//		}
//
//		$query_array[] = sprintf("UPDATE ".PARSER_DB.".raw_spells SET processed = 1, populated_spell_id = %s WHERE spell_id = %s;", $this->id, $orig_spell_id);
//		//print_r($query_array); exit;
//		
//		// commit SQL
//		if( is_array($query_array) )
//		{
//			foreach($query_array as $Query)
//			{
//				$this->db->sql_query($Query);
//				//printf("%s<br />", $Query);
//				if( substr($Query, 0, 6) != "UPDATE" )
//					$this->logQuery($Query);
//			}
//		}
//		
//		// Create the spell LUA if it does not already exist
//		// has to be done after query_array so CreateLUATemplate() has spell_display_effects to read from
//		$script_relative_path = sprintf("Spells/%s%s", $this->getSpellClassPath($_GET['class']), $this->GetCleanSpellScriptName($spell_name));
//		$query = sprintf("UPDATE spells SET lua_script = '%s' WHERE id = %s;", $script_relative_path, $this->id);
//		//printf("QUERY: %s<br />", $query);
//		$this->db->sql_query($query);
//		$this->logQuery($query);
//		
//		if( !$this->CheckScriptExists($script_relative_path) )
//		{
//			$script_text = $this->LoadLUAScript($script_relative_path);
//			$this->CreateLUAScript($script_relative_path, $script_text);
//		}
//
//		if( $this->our_time > 0 )
//			$this->our_time += time() - $start_our;
//		else
//			$this->our_time = time() - $start_our;
//		
//		if( $this->total_time > 0 )
//			$this->total_time += time() - $start_time;
//		else
//			$this->total_time = time() - $start_time;
//
//	}
//
//	function UpdateTierFromSOEData()
//	{
//		$spell_id = intval($_GET['s']);
//		$tier_id 	= ( isset($_POST['orig_tier']) ) ? intval($_POST['orig_tier']) : 1;
//		
//		if( $spell_id > 0 && $tier_id > 0 )
//		{
//			$sql = sprintf("SELECT soe_spell_crc, tier FROM spells, spell_tiers WHERE spells.id = spell_tiers.spell_id AND spell_id = %s and tier = %s LIMIT 0,1;", $spell_id, $tier_id);
//			if( !$result = $this->db->sql_query($sql) )
//				$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
//			
//			$data = $this->db->sql_fetchrow($result);
//			
//			die("Now, why are you filtering SOE data by given_by=class? Investigate");
//			$DataURL = sprintf("http://census.daybreakgames.com/json/get/eq2/spell/?crc=%s&tier=%s&given_by=class", $data['soe_spell_crc'], $tier_id);
//			//printf("%s<br />", $DataURL);
//			
//			$spell_info = json_decode($this->file_get_contents_curl($DataURL), true);
//			
//			//print_r($spell_info);
//			
//			if( is_array($spell_info[spell_list]) && isset($spell_info[spell_list][0][name]) )
//			{
//				// stuff spell_list data into a new array to make it less path-y
//				$si = $spell_info[spell_list];
//				//print_r($si); exit;
//				
//				$spells['name'] = $si[0][name];
//				$spells['friendly_spell'] = $si[0][beneficial];
//				$spells['description_pvp'] = $si[0][description_pvp];
//				$spells['description_'] = $si[0][description];
//				$spells['icon'] = $si[0][icon][id];
//				$spells['icon2'] = $si[0][icon][backdrop];
//				$spells['icon_type'] = $si[0][icon][icon_heroic_op];
//				$spells['spell_book_type'] = $si[0][typeid];
//				$spells['spellbook'] = $si[0][spellbook];
//
//				$spell_tiers['tier'] = $si[0][tier];
//				$spell_tiers['hp_req'] = $si[0][cost][health];
//				$spell_tiers['hp_upkeep'] = $si[0][cost][per_tick][health];
//				$spell_tiers['power_req'] = $si[0][cost][power];
//				$spell_tiers['power_upkeep'] = $si[0][cost][per_tick][power];
//				$spell_tiers['savagery_req'] = $si[0][cost][savagery];
//				$spell_tiers['savagery_upkeep'] = $si[0][cost][per_tick][savagery];
//				$spell_tiers['req_concentration'] = $si[0][cost][concentration];
//				$spell_tiers['cast_time'] = $si[0][cast_secs_hundredths];
//				$spell_tiers['recovery'] = $si[0][recovery_secs_tenths];
//				$spell_tiers['recast'] = round($si[0][recast_secs]);
//				$spell_tiers['max_aoe_targets'] = $si[0][max_targets];
//				$spell_tiers['range'] = $si[0][aoe_radius_meters];
//				$spell_tiers['duration1'] = $si[0][duration][min_sec_tenths];
//				$spell_tiers['duration2'] = $si[0][duration][max_sec_tenths];
//				$spell_tiers['given_by'] = $si[0][given_by];
//				$spells['duration_until_cancel'] = $si[0][duration][does_not_expire]; // 0 or 1
//
//				//$spell_levels['level'] = $si[0][level];
//				
//				if( is_array($si[0][classes]) )
//				{
//					$i=0;
//					foreach($si[0][classes] as $key=>$val)
//					{
//						$spell_classes[$i]['adventure_class_id'] = $val[id];
//						$spell_classes[$i]['level'] = $val[level];
//						$i++;
//					}
//				}
//				
//				if( is_array($si[0][effect_list]) )
//				{
//					$i=0;
//					foreach($si[0][effect_list] as $key=>$val)
//					{
//						$spell_display_effects[$i]['description'] = $val[description];
//						$spell_display_effects[$i]['bullet'] = $val[indentation];
//						$i++;
//					}
//				}
//				
//				$this->ForMe($spells);
//				$this->ForMe($spell_tiers);
//				$this->ForMe($spell_display_effects);
//				$this->ForMe($spell_classes);
//			}
//			else
//				printf('<span style="font-size:14px; font-weight:bold; color:#f00">No tier %s data for this spell.</span>', $tier_id);
//		}
//	}
//
//	function GetSOESpellData($spell_id)
//	{
//		$this->spells = array();
//		$this->spell_tier = array();
//		$this->spell_fx = array();
//		$this->spell_classes = array();
//		$ret = false;
//
//		// First, verify the data still exists on census.daybreakgames.com
//		$DataURL = sprintf("http://census.daybreakgames.com/json/get/eq2/spell/?crc=%s&c:limit=20&c:sort=tier", $spell_id);
//		//printf("DataURL: %s<br />", $DataURL);
//		
//		//$this->PrintDebugTimer("census.daybreakgames.com", 1, $spell_id);
//		$spell_array = json_decode($this->file_get_contents_curl($DataURL), true);
//		//$this->PrintDebugTimer("census.daybreakgames.com", 0);
//
//		if( $spell_array[returned] < 1 )
//			return $ret;
//
//		if( is_array($spell_array[spell_list]) )
//		{
//			$ret = true;
//			
//			$start_soe = time();
//			
//			//$this->PrintDebugTimer("spell_list loop", 3);
//			$loopCount = 0; // debugging only
//			foreach($spell_array[spell_list] as $spell)
//			{
//				//$this->PrintDebugTimer("spell_list count", 2, ++$loopCount);
//				
//				$max_tier = $spell_array[max_tier];
//				
//				$tier = $spell[tier];
//				//printf("Tier %s<br />", $tier);
//				
//				//$this->PrintDebugTimer("spells[array]", 1);
//				$this->spells[is_aa] 												= $spell[alternate_advancement];
//				$this->spells[friendly_spell] 							= $spell[beneficial];
//				$this->spells[is_deity] 										= $spell[deity];
//				$this->spells[description] 									= $spell[description];
//				$this->spells[soe_last_update] 							= $spell[last_update];
//				$this->spells[spell_book_type] 							= $spell[typeid];
//				$this->spells[name] 												= $spell[name];
//				$this->spells[duration_until_cancel] 				= $spell[duration][does_not_expire];
//				// icon
//				// test a few icons using SOE data rather than the 65535 replacement
//				// if they look funky, replace this code
//				/*$spells[icon] 												= ( $spell[icon][id] >= 0 ) ? $spell[icon][id] : 65535;
//				$spells[icon_heroic_op] 							= ( $spell[icon][icon_heroic_op] >= 0 ) ? $spell[icon][icon_heroic_op] : 65535;
//				$spells[icon_backdrop] 								= ( $spell[icon][backdrop] >= 0 ) ? $spell[icon][backdrop] : 65535;*/
//				$this->spells[icon] 												= $spell[icon][id];
//				$this->spells[icon_heroic_op] 							= $spell[icon][icon_heroic_op];
//				$this->spells[icon_backdrop] 								= $spell[icon][backdrop];
//				//$this->PrintDebugTimer("spells[array]", 0);
//
//				//$this->PrintDebugTimer("spell_tiers[array]", 1);
//				$this->spell_tier[$tier][radius] 						= $spell[aoe_radius_meters];
//				$this->spell_tier[$tier][cast_time]					= $spell[cast_secs_hundredths];
//				$this->spell_tier[$tier][max_aoe_targets]		= $spell[max_targets];
//				$this->spell_tier[$tier][recast] 						= round($spell[recast_secs]);
//				$this->spell_tier[$tier][recovery] 					= $spell[recovery_secs_tenths];
//				$this->spell_tier[$tier][req_concentration]	= $spell[cost][concentration];
//				$this->spell_tier[$tier][dissonance_req] 		= $spell[cost][dissonance];
//				$this->spell_tier[$tier][hp_req] 						= $spell[cost][health];
//				$this->spell_tier[$tier][power_req] 				= $spell[cost][power];
//				$this->spell_tier[$tier][savagery_req]			= $spell[cost][savagery];
//				$this->spell_tier[$tier][dissonance_upkeep]	= $spell[cost][per_tick][dissonance];
//				$this->spell_tier[$tier][hp_upkeep] 				= $spell[cost][per_tick][health];
//				$this->spell_tier[$tier][power_upkeep] 			= $spell[cost][per_tick][power];
//				$this->spell_tier[$tier][savagery_upkeep] 	= $spell[cost][per_tick][savagery];
//				$this->spell_tier[$tier][duration1] 				= $spell[duration][min_sec_tenths];
//				$this->spell_tier[$tier][duration2] 				= $spell[duration][max_sec_tenths];
//				//$this->PrintDebugTimer("spell_tiers[array]", 0);
//				
//				// effects list
//				if( is_array($spell[effect_list]) )
//				{
//					//$this->PrintDebugTimer("spell_fx[array]", 1);
//					$loopCount2 = 0; 
//					foreach($spell[effect_list] as $key=>$val)
//					{
//						//$this->PrintDebugTimer("spell_fx count", 2, ++$loopCount2);
//						$this->spell_fx[$tier][$key][description] = $val[description];
//						//$this->spell_fx[$tier][$key][bullet] 			= $val[indentation];
//					}
//					//$this->PrintDebugTimer("spell_fx[array]", 0);
//				}
//
//				// classes
//				if( is_array($spell[classes]) )
//				{
//					//$this->PrintDebugTimer("spell_classes[array]", 1);
//					$loopCount3 = 0; 
//					foreach($spell[classes] as $class)
//					{
//						//$this->PrintDebugTimer("spell_classes count", 2, ++$loopCount3);
//						$this->spell_classes[$class[id]] = $class[level];
//					}
//					//$this->PrintDebugTimer("spell_classes[array]", 0);
//				}
//
//				// get additional spell tier info from the SOE Item itself (boggle)
//				$DataURL = sprintf("http://census.daybreakgames.com/json/get/eq2/item/?typeinfo.spellid=%s&typeinfo.tier=%s&c:show=typeinfo.spellrange,typeinfo.resistability,typeinfo.spelltarget,requiredskill.min_skill", $spell_id, $tier);
//				//printf("%s<br />", $DataURL);
//
//				//$this->PrintDebugTimer("census.daybreakgames.com [spell_info_item]", 1, $spell_id);
//				$spell_info_item = json_decode($this->file_get_contents_curl($DataURL), true);
//				//$this->PrintDebugTimer("census.daybreakgames.com [spell_info_item]", 0);
//				//print_r($spell_info_item); //exit;
//
//				if( $spell_info_item[returned] > 0 )
//				{
//					$sii = $spell_info_item[item_list][0];
//					
//					//$this->PrintDebugTimer("spells[from items]", 1, $spell_id);
//					$this->spell_tier[$tier]['range'] 			= ( !empty($sii[typeinfo][spellrange]) ) ? $this->ParseSpellRange($sii[typeinfo][spellrange]) : 0;
//					$this->spell_tier[$tier][resistibility] = ( !empty($sii[typeinfo][resistability]) ) ? $this->ParseSpellResistibility($sii[typeinfo][resistability]) : 0; // SOE spelled 'resistibility' wrong??
//					$this->spells[min_class_skill_req] 			= ( !empty($sii[requiredskill][min_skill]) ) ? $sii[requiredskill][min_skill] : 0;
//					$this->spells[target_type] 							= ( !empty($sii[typeinfo][spelltarget]) ) ? $this->eq2TargetTypes[$sii[typeinfo][spelltarget]] : 1;
//					//$this->PrintDebugTimer("spells[from items]", 0);
//				}
//				else
//				{
//					//$this->PrintDebugTimer("spells[from items]", 1);
//					$this->spell_tier[$tier]['range'] = 0;
//					$this->spell_tier[$tier][resistibility] = 0;
//					$this->spells[min_class_skill_req] = 0;
//					$this->spells[target_type] = 1;
//					//$this->PrintDebugTimer("spells[from items]", 0);
//				}
//			}
//			//$this->PrintDebugTimer("spell_list loop", 4);
//			
//			if( $this->soe_time > 0 )
//				$this->soe_time += time() - $start_soe;
//			else
//				$this->soe_time = time() - $start_soe;
//			
//			//print_r($this->spells);
//			//print_r($this->spell_tier);
//			//print_r($this->spell_fx); 
//			//print_r($this->spell_classes);
//			//exit;
//		}
//		else
//			$ret = false;
//
//		return $ret;
//	}
//
//	function processMigrateAllClassSpells()
//	{
//		if( is_array($_POST['migrate']) )
//		{
//			$mSpellArr = $_POST['migrate'];
//			
//			foreach($mSpellArr as $orig_spell_id)
//			{
//				if( $new_spell_id == 0 )
//					$new_spell_id = $this->GetNextIDX("spells", $_GET['class']);
//				else
//					$new_spell_id++;
//					
//				//printf("%s, %s<br />", $orig_spell_id, $new_spell_id);
//				$this->processMigrateSingleSpell($orig_spell_id, $new_spell_id, true);
//			}
//		}
//		else
//			die("Not yet!");
//	}
//
//
//	/*temp function*/
//	function _autoupdategivenby()
//	{
//		// commented out because I only needed this to auto update given_by after moving the column to a new table
//		// leaving the code here though in case it is needed again
//		
//		$DataURL = sprintf("http://census.daybreakgames.com/json/count/eq2/spell/?given_by=classtraining");
//		$count_array = json_decode($this->file_get_contents_curl($DataURL), true);
//		if( $count_array['count'] > 0 )
//		{
//			// loop through batches of 100
//			for( $i = 0; $i < $count_array['count']; )
//			{
//				$DataURL = sprintf("http://census.daybreakgames.com/json/get/eq2/spell/?given_by=classtraining&c:start=%s&c:limit=100&c:show=crc,tier", $i);
//				$spell_array = json_decode($this->file_get_contents_curl($DataURL), true);
//				//print_r($spell_array);
//				//exit;
//				if( is_array($spell_array[spell_list]) )
//				{
//					foreach($spell_array[spell_list] as $spell)
//					{
//						$sql = sprintf("UPDATE spell_tiers, spells SET given_by = 'classtraining' WHERE spells.id = spell_id AND (tier = %s AND soe_spell_crc = %s)", $spell[tier], $spell[crc]);
//						$this->db->sql_query($sql);
//						//printf("%s</br />", $sql);
//						//exit;
//					}
//				}
//				$i = $i + 100;
//			}
//		}
//	}

/**
 * Display the page for creating a new spell.
 */
public function DisplayAddNewSpellPage()
{
    global $spellInsertError;

    if($spellInsertError ?? false) { 
        if ($spellInsertError == 1) $errtext = "You must provide a name for your spell!";
        else $errtext = "Error inserting your new spell!";
        printf('<span class="heading" style="color:red">%s</span>', $errtext);
        echo "</br>";
    }
    ?>

    <form method="post" name="AddSpell">
        <h2>Create a New Spell</h2>

        <input type="text" name="spellName" placeholder="New Spell Name" style="min-width: 200px;" required autofocus>

        <div style="margin-top: 0.5rem; margin-bottom: 1rem;">
            <label for="spellType">Spell Type:</label>
            <select id="spellType" name="spellType">
                <?php $this->DisplaySpellTypeOptions("spells"); ?>
            </select>
        </div>

        <button type="submit" name="cmd" value="Create">Create</button>
    </form>

    <?php
}

/**
 * Generate a list of spell types
 */
public function DisplaySpellTypeOptions($selectedType) {
    $labels = ['Spell', 'Combat Art', 'Ability', 'Crafting', 'Passive'];
    foreach ($this->eq2SOESpellTypes as $id => $key) {
        $selected = ($selectedType == $id) ? ' selected' : '';
        printf("<option value=\"%s\"%s>%s</option>\r\n", $id, $selected, $labels[$id]);
    }
}

function CreateNewSpell() {
    global $eq2, $spellInsertError;

    $name = $_POST['spellName'] ?? "";

    if ($name == "") {
        $spellInsertError = 1;
        return;
    }

    $type = $_POST['spellType'];

    $eq2->BeginSQLTransaction();
    $eq2->RunQuery(true, "LOCK TABLE ".ACTIVE_DB."`spells` WRITE;");

    $query = sprintf('INSERT INTO %s.spells(name, type, description) VALUES("%s", "%s", "New spell.")', ACTIVE_DB, $eq2->SQLEscape($name), $type);

    $success = false;

    if ($eq2->RunQuery(true, $query) == 1) {
        $success = true;
        if ($success) { $eq2->SQLTransactionCommit(); }
    }

    if (!$success) { $eq2->SQLTransactionRollback(); }

    $eq2->RunQuery(true, "UNLOCK TABLES;");

    if ($success) {
        $id = $eq2->RunQuerySingle(sprintf("SELECT MAX(id) FROM %s.spells ORDER BY id DESC LIMIT 1;", ACTIVE_DB))["MAX(id)"];
        $eq2->RunQuery(true, sprintf("INSERT INTO %s.spell_tiers(spell_id, tier) VALUES(%s, 1);", ACTIVE_DB, $id));
        $search = sprintf("spells.php?id=%s", $id);
        header("Location: ".$search);
        //We're redirecting at this point, go ahead and exit
        exit;
    }
    else {
        $spellInsertError = 2;
    }
}
	


} // end eq2Spells class






?>