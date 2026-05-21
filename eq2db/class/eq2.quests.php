<?php

class eq2Quests
{
	var $quest_editor_base_page = 'quests.php';

	var $eq2QuestTables = array("quests", "quest_details");
	var $eq2QuestTypes = array('None','Prereq','Reward');
	var $eq2QuestSubtypes = array('None','Experience','Faction','Item','Quest','Race','Class','AdvLevel','TSLevel','Coin','Selectable','MaxCoin','MaxAdvLevel','MaxTSLevel',
																'TSExperience');

																
	// this doesn't belong here, but i don't see it anywhere else.
	// one of these days i'll read it from the editor db
	var $eq2ItemTiers =
	array(
		'2'=>'Common',
		'3'=>'Uncommon',
		'4'=>'Treasured',
		'7'=>'Legendary',
		'9'=>'Fabeled',
		'12'=>'Mythical'
	);

	var $eq2ItemToggles =
	array(
		'attuneable', 'artifact', 'lore', 'temporary',
		'notrade', 'novalue', 'nozone', 'nodestroy', 'crafted',
		'good_only', 'evil_only', 'stacklore', 'lore_equip',
		'no_transmute', 'CURSED_flags_32768', 'ornate', 'heirloom',
		'appearance_only','unlocked','norepair','etheral','refined',
		'usable','collectable','body_drop','display_charges','harvest',
		'no_salvage','indestructable','no_experiment','house_lore',
		'flags2_4096','building_block','free_reforge','infusable', 'no_buy_back'
	);

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

	var $eq2ItemStats = array(
		0 => "STR",
		1 => "STA",
		2 => "AGI",
		3 => "WIS",
		4 => "INT",
		200 => "VS_SLASH",
		201 => "VS_CRUSH",
		202 => "VS_PIERCE",
		203 => "VS_HEAT",
		204 => "VS_COLD",
		205 => "VS_MAGIC",
		206 => "VS_MENTAL",
		207 => "VS_DIVINE",
		208 => "VS_DISEASE",
		209 => "VS_POISON",
		210 => "VS_DROWNING",
		211 => "VS_FALLING",
		212 => "VS_PAIN",
		213 => "VS_MELEE",
		300 => "DMG_SLASH",
		301 => "DMG_CRUSH",
		302 => "DMG_PIERCE",
		303 => "DMG_HEAT",
		304 => "DMG_COLD",
		305 => "DMG_MAGIC",
		306 => "DMG_MENTAL",
		307 => "DMG_DIVINE",
		308 => "DMG_DISEASE",
		309 => "DMG_POISON",
		310 => "DMG_DROWNING",
		311 => "DMG_FALLING",
		312 => "DMG_PAIN",
		313 => "DMG_MELEE",
		500 => "HEALTH",
		501 => "POWER",
		502 => "CONCENTRATION",
		600 => "HPREGEN",
		601 => "MANAREGEN",
		602 => "HPREGENPPT",
		603 => "MPREGENPPT",
		604 => "COMBATHPREGENPPT",
		605 => "COMBATMPREGENPPT",
		606 => "MAXHP",
		607 => "MAXHPPERC",
		608 => "SPEED",
		609 => "SLOW",
		610 => "MOUNTSPEED",
		611 => "OFFENSIVESPEED",
		612 => "ATTACKSPEED",
		613 => "MAXMANA",
		614 => "MAXMANAPERC",
		615 => "MAXATTPERC",
		616 => "BLURVISION",
		617 => "MAGICLEVELIMMUNITY",
		618 => "HATEGAINMOD",
		619 => "COMBATEXPMOD",
		620 => "TRADESKILLEXPMOD",
		621 => "ACHIEVEMENTEXPMOD",
		622 => "SIZEMOD",
		623 => "UNKNOWN",
		624 => "STEALTH",
		625 => "INVIS",
		626 => "SEESTEALTH",
		627 => "SEEINVIS",
		628 => "EFFECTIVELEVELMOD",
		629 => "RIPOSTECHANCE",
		630 => "PARRYCHANCE",
		631 => "DODGECHANCE",
		632 => "AEAUTOATTACKCHANCE",
		633 => "DOUBLEATTACKCHANCE",
		634 => "RANGEDDOUBLEATTACKCHANCE",
		635 => "SPELLDOUBLEATTACKCHANCE",
		636 => "FLURRY",
		637 => "EXTRAHARVESTCHANCE",
		638 => "EXTRASHIELDBLOCKCHANCE",
		639 => "DEFLECTIONCHANCE",
		640 => "ITEMHPREGENPPT",
		641 => "ITEMPPREGENPPT",
		642 => "MELEECRITCHANCE",
		643 => "RANGEDCRITCHANCE",
		644 => "DMGSPELLCRITCHANCE",
		645 => "HEALSPELLCRITCHANCE",
		646 => "MELEECRITBONUS",
		647 => "RANGEDCRITBONUS",
		648 => "DMGSPELLCRITBONUS",
		649 => "HEALSPELLCRITBONUS",
		650 => "UNCONSCIOUSHPMOD",
		651 => "SPELLTIMEREUSEPCT",
		652 => "SPELLTIMERECOVERYPCT",
		653 => "SPELLTIMECASTPCT",
		654 => "MELEEWEAPONRANGE",
		655 => "RANGEDWEAPONRANGE",
		656 => "FALLINGDAMAGEREDUCTION",
		657 => "SHIELDEFFECTIVENESS",
		658 => "RIPOSTEDAMAGE",
		659 => "MINIMUMDEFLECTIONCHANCE",
		660 => "MOVEMENTWEAVE",
		661 => "COMBATHPREGEN",
		662 => "COMBATMANAREGEN",
		663 => "CONTESTSPEEDBOOST",
		664 => "TRACKINGAVOIDANCE",
		665 => "STEALTHINVISSPEEDMOD",
		666 => "LOOT_COIN",
		667 => "ARMORMITIGATIONINCREASE",
		668 => "AMMOCONSERVATION",
		669 => "STRIKETHROUGH",
		670 => "STATUSBONUS",
		671 => "ACCURACY",
		672 => "COUNTERSTRIKE",
		673 => "SHIELDBASH",
		674 => "WEAPONDAMAGEBONUS",
		675 => "ADDITIONALRIPOSTECHANCE",
		676 => "CRITICALMITIGATION",
		677 => "COMBATARTDAMAGE",
		678 => "SPELLDAMAGE",
		679 => "HEALAMOUNT",
		680 => "TAUNTAMOUNT",
		700 => "SPELL_DAMAGE",
		701 => "HEAL_AMOUNT",
		702 => "SPELL_AND_HEAL"
	);

	var $eq2ItemSlots = array(
		1=>'PRIMARY',
		2=>'SECONDARY',
		4=>'HEAD',
		8=>'CHEST',
		16=>'SHOULDERS',
		32=>'FOREARMS',
		64=>'HANDS',
		128=>'LEGS',
		256=>'FEET',
		512=>'LRING',
		1024=>'RRING',
		2048=>'EARS_1',
		4096=>'EARS_2',
		8192=>'NECK',
		16384=>'LWRIST',
		32768=>'RWRIST',
		65536=>'RANGE',
		131072=>'AMMO',
		262144=>'WAIST',
		524288=>'CLOAK',
		1048576=>'CHARM_1',
		2097152=>'CHARM_2',
		4194304=>'FOOD',
		8388608=>'DRINK',
		16777216=>'TEXTURES',
		33554432=>'HAIR',
		67108864=>'BEARD',
		134217728=>'WINGS',
		268435456=>'NAKED_CHEST',
		536870912=>'NAKED_LEGS',
		1073741824=>'BACK',
		524288=>'ORIG_FOOD',
		1048576=>'ORIG_DRINK',
		1048576=>'DOF_FOOD',
		2097152=>'DOF_DRINK'
	);

	
	public function __construct() 
	{
		$this->quest_id = $_GET['id'] ?? NULL;
		if( $this->quest_id > 0 && is_numeric($this->quest_id))
			$this->quest_name = $this->GetQuestName();
	}
	
	public function DeleteQuest() 
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.quest_details WHERE quest_id = %s", $this->quest_id);
		$eq2->RunQuery();
		
		$eq2->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.quests WHERE quest_id = %s", $this->quest_id);
		$eq2->RunQuery();
		
		$eq2->DeleteLUAScript($_POST['script_path']);
	}
	
	public function GetCleanQuestScriptName()
	{
		// sanitize
		$var = preg_replace("/[^a-zA-Z0-9]+/", "", html_entity_decode($this->quest_name, ENT_QUOTES));
		return sprintf("%s.lua", $var);
	}
	
	public function GetOptionsQuestDetailTypes($selected = '') 
	{
		$ret = "";
		foreach($this->eq2QuestTypes as $type) 
			$ret .= sprintf("<option%s>%s</option>", ( $type == $selected ) ? " selected" : "", $type);
				
		return $ret;		
	}

	public function GetOptionsQuestDetailSubTypes($selected = '') 
	{
		$ret = "";
		foreach($this->eq2QuestSubtypes as $subtype) 
			$ret .= sprintf("<option%s>%s</option>", ( $subtype == $selected ) ? " selected" : "", $subtype);
				
		return $ret;		
	}

	public function GetQuestData()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.quests WHERE quest_id = %s", $this->quest_id);
		return $eq2->RunQuerySingle();
	}
	
	public function GetQuestDetails()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".ACTIVE_DB."`.quest_details WHERE quest_id = %s", $this->quest_id);
		return $eq2->RunQueryMulti();
	}
	
	public function SetQuestEditorBasePage($page)
	{
		$page = trim((string)$page);
		if( $page !== '' )
			$this->quest_editor_base_page = $page;
	}

	public function GetQuestName()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT name FROM `".ACTIVE_DB."`.quests WHERE quest_id = %s", $this->quest_id);
		$data = $eq2->RunQuerySingle();
		return $data['name'];
	}
	
	public function GetQuestZoneOptions()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT DISTINCT zone FROM `".ACTIVE_DB."`.quests ORDER BY zone");
		$results = $eq2->RunQueryMulti();
		
		if( is_array($results) )
		{
			$ret = "";
			foreach($results as $data)
				$ret .= sprintf('<option value="%s?zone=%s"%s>%s</option>', $this->quest_editor_base_page, $data['zone'], ( isset($_GET['zone']) && $_GET['zone'] == $data['zone'] ) ? " selected" : "", $data['zone']);
		} 
		
		return $ret;
	}
	
	public function GetQuestOptionsByZone()
	{
		global $eq2;

		$quest_zone = $_GET['zone'];
		
		$eq2->SQLQuery = sprintf("SELECT quest_id, name FROM `".ACTIVE_DB."`.quests WHERE zone = '%s' ORDER BY lua_script", $eq2->db->sql_escape($quest_zone));
		$results = $eq2->RunQueryMulti();
		
		$ret = "";
		if( is_array($results) )
		{
			foreach($results as $data)
				$ret .= sprintf('<option value="%s?zone=%s&id=%s&tab=register"%s>%s (%d)</option>', $this->quest_editor_base_page, $quest_zone, $data['quest_id'], ( isset($_GET['id']) && $_GET['id'] == $data['quest_id'] ) ? " selected" : "", $data['name'], $data['quest_id']);
		} 
		
		return $ret;
	}
	
	public function GetQuestScriptName($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT lua_script FROM `".ACTIVE_DB."`.quests WHERE quest_id = %s", $id);	
		return $eq2->RunQuerySingle();
	}
	
	public function GetQuestsMatching()
	{
		global $eq2;
		
		if( strlen($_POST['txtSearch']) > 0 )
		{
			$search = $eq2->SQLEscape($_POST['txtSearch']);
			$eq2->SQLQuery = "SELECT * FROM `".ACTIVE_DB."`.quests WHERE quest_id = '".$search."' OR (name RLIKE '".$search."') OR (description RLIKE '".$search."') OR (type RLIKE '".$search."') OR (zone RLIKE '".$search."') OR (lua_script RLIKE '".$search."') ORDER BY name";
			return $eq2->RunQueryMulti();
		}
	}
	
	public function PrintOffsiteLinks()
	{
	}

	public function GetQuestRewardItemDescription($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT name, icon, tier, bPvpDesc, skill_id_req, item_type, adventure_classes FROM `".ACTIVE_DB."`.items WHERE id = '".$id."' ";
		return $eq2->RunQuerySingle();
	}

	public function GetQuestRewardItemTierName($itemID)
	{
		global $eq2;
		$eq2->SQLQuery = "SELECT tier FROM `".ACTIVE_DB."`.items WHERE id = '".$itemID."' ";
		$data = $eq2->RunQuerySingle();
		$return_string = "";

		foreach($this->eq2ItemTiers as $key=>$name)
		{
			$return_string .= $data['tier'] == $key ? $name : "";
		}

		Return ($return_string);
	}

	public function GetQuestRewardItemToggleList($itemID)
	{
		global $eq2;
		$eq2->SQLQuery = "SELECT * FROM `".ACTIVE_DB."`.items WHERE id = '".$itemID."' ";
		$data = $eq2->RunQuerySingle();
		$return_string = "";
		foreach ($this->eq2ItemToggles as $ItemToggles)
		{
			$return_string .= $data[$ItemToggles] == 1 ? ' ' . ucwords($ItemToggles) . ' ' : '';
		}
		//$return_string .= "";
		return($return_string);
	}

	public function GetQuestRewardItemStats($itemID)
	{
		global $eq2;
		$eq2->SQLQuery = "SELECT i.name AS name, s.type AS type, s.subtype AS subtype, s.ivalue AS value FROM `".ACTIVE_DB."`.items AS i INNER JOIN `".ACTIVE_DB."`.item_mod_stats AS s ON i.id = s.item_id WHERE i.id = ".$itemID;
		$data = $eq2->RunQueryMulti();
		$return_string = "";
		foreach($data as $row)
		{
			//$return_string .=$row['type'] . ":" . $row['subtype'] . ":" . $row['value'];
			if($row['type'] == 0)
			{
				$return_string .= sprintf("%+d",$row['value']) . "  " . $this->eq2ItemStats[$row['subtype']] . " ";
			}
			if($row['type'] > 0)
			{
				$return_string .= sprintf("%+d",$row['value']) . "  " . $this->eq2ItemStats[$row['type']*100 + sprintf('%02d',$row['subtype'])] . " ";
			}
		}

		Return($return_string);
	}

	public function GetQuestRewardItemAdventureClass($adv_class)
	{
		global $eq2;
		$strBinary = strrev(decbin($adv_class));
		//print($strBinary);
		$arryAdventureClass = array(
			0=>'Commoner',
			1=>'Fighter',
			2=>'Warrior',
			3=>'Guardian',
			4=>'Berserker',
			5=>'Brawler',
			6=>'Monk',
			7=>'Bruiser',
			8=>'Crusader',
			9=>'Shadowknight',
			10=>'Paladin',
			11=>'Priest',
			12=>'Cleric',
			13=>'Templar',
			14=>'Inquisitor',
			15=>'Druid',
			16=>'Warden',
			17=>'Fury',
			18=>'Shaman',
			19=>'Mystic',
			20=>'Defiler',
			21=>'Mage',
			22=>'Sorcerer',
			23=>'Wizard',
			24=>'Warlock',
			25=>'Enchanter',
			26=>'Illusionist',
			27=>'Coercer',
			28=>'Summoner',
			29=>'Conjuror',
			30=>'Necromancer',
			31=>'Scout',
			32=>'Rouge',
			33=>'Swashbuckler',
			34=>'Brigand',
			35=>'Bard',
			36=>'Troubador',
			37=>'Dirge',
			38=>'Predator',
			39=>'Ranger',
			40=>'Assassin',
			41=>'Animalist',
			42=>'Beastlord',
			43=>'Shaper',
			44=>'Channeler'
		);
		$return_string = "";

		//LLAMA NOTE
		//I'm sure i'll thing of a better way to do this later,
		//but this is what i got for now


		//Fighters
		$allWarriors = FALSE;
		$allBrawlers = FALSE;
		$allCrusaders = FALSE;
		$allFighters = FALSE;
		$strWarriors ="";
		$strBrawlers ="";
		$strCrusaders ="";
		$strFighters ="";
		if(substr($strBinary,3,1) == 1 AND substr($strBinary,4,1)== 1)
		{
			$allWarriors = TRUE;
			$strWarriors = "All Warriors ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[2] = 0;
			$temp_Binary[3] = 0;
			$temp_Binary[4] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if(substr($strBinary,6,1) == 1 AND substr($strBinary,7,1)== 1)
		{
			$allBrawlers = TRUE;
			$strBrawlers = "All Brawlers ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[4] = 0;
			$temp_Binary[6] = 0;
			$temp_Binary[7] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if(substr($strBinary,9,1) == 1 AND substr($strBinary,10,1)== 1)
		{
			$allCrusaders = TRUE;
			$strCrusaders = "All Crusaders ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[8] = 0;
			$temp_Binary[9] = 0;
			$temp_Binary[10] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if($allWarriors AND $allBrawlers AND $allCrusaders)
		{
			$strWarriors = "";
			$strBrawlers = "";
			$strCrusaders = "";
			$strFighters = "All Fighters ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[1] = 0;
			$temp_Binary[2] = 0;
			$temp_Binary[5] = 0;
			$temp_Binary[8] = 0;
			$strBinary = implode('', $temp_Binary);
			$allFighters =TRUE;
			//print("\n" . $strBinary . "\n");
		}

		$return_string .= $strWarriors . $strBrawlers . $strCrusaders . $strFighters;

		//Priests
		$allClerics = FALSE;
		$allDruids = FALSE;
		$allShawmen = FALSE;
		$allShapers = FALSE;
		$allPriests = FALSE;
		$strClerics = "";
		$strDruids = "";
		$strShawmen = "";
		$strShapers = "";
		$strPriests = "";
		if(substr($strBinary,13,1) == 1 AND substr($strBinary,14,1)== 1)
		{
			$allClerics = TRUE;
			$strClerics = "All Clerics ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[12] = 0;
			$temp_Binary[13] = 0;
			$temp_Binary[14] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if(substr($strBinary,16,1) == 1 AND substr($strBinary,17,1)== 1)
		{
			$allDruids = TRUE;
			$strDruids = "All Druids ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[15] = 0;
			$temp_Binary[16] = 0;
			$temp_Binary[17] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if(substr($strBinary,19,1) == 1 AND substr($strBinary,20,1)== 1)
		{
			$allShawmen = TRUE;
			$strShawmen = "All Shamen ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[18] = 0;
			$temp_Binary[19] = 0;
			$temp_Binary[20] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}

		if(substr($strBinary,44,1) == 1)
		{
			$allShapers = TRUE;
			$strShapers = "All Shapers ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[43] = 0;
			$temp_Binary[44] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}

		if($allClerics AND $allDruids AND $allShawmen AND $allShapers)
		{
			$strClerics = "";
			$strDruids = "";
			$strShawmen = "";
			$strShapers = "";
			$strPriests = "All Priests ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[11] = 0;
			$temp_Binary[12] = 0;
			$temp_Binary[15] = 0;
			$temp_Binary[18] = 0;
			$temp_Binary[43] = 0;
			$strBinary = implode('', $temp_Binary);
			$allPriests = TRUE;
			//print("\n" . $strBinary . "\n");
		}

		$return_string .= $strClerics . $strDruids . $strShawmen . $strShapers . $strPriests;

		//Mage
		$allSorcerers = FALSE;
		$allEnchanters = FALSE;
		$allSummoners = FALSE;
		$allMages = FALSE;
		$strSorcerers = "";
		$strEnchanters = "";
		$strSummoners = "";
		$strMage = "";
		if(substr($strBinary,23,1) == 1 AND substr($strBinary,24,1)== 1)
		{
			$allSorcerers = TRUE;
			$strSorcerers = "All Sorcerers ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[22] = 0;
			$temp_Binary[23] = 0;
			$temp_Binary[24] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if(substr($strBinary,26,1) == 1 AND substr($strBinary,27,1)== 1)
		{
			$allEnchanters = TRUE;
			$strEnchanters = "All Enchanters ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[25] = 0;
			$temp_Binary[26] = 0;
			$temp_Binary[27] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if(substr($strBinary,29,1) == 1 AND substr($strBinary,30,1)== 1)
		{
			$allSummoners = TRUE;
			$strSummoners = "All Summoners ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[28] = 0;
			$temp_Binary[29] = 0;
			$temp_Binary[30] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}

		if($allSorcerers AND $allEnchanters AND $allSummoners)
		{
			$strSorcerers = "";
			$strEnchanters = "";
			$strSummoners = "";
			$strMage = "All Mages ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[21] = 0;
			$temp_Binary[22] = 0;
			$temp_Binary[25] = 0;
			$temp_Binary[28] = 0;
			$strBinary = implode('', $temp_Binary);
			$allMages = TRUE;
			//print("\n" . $strBinary . "\n");
		}

		$return_string .= $strSorcerers . $strEnchanters . $strSummoners . $strMage;

		//Scouts
		$allRouges = FALSE;
		$allBards = FALSE;
		$allPredators = FALSE;
		$allAnimalists = FALSE;
		$allScouts = FALSE;
		$strRouges = "";
		$strBards = "";
		$strPredators = "";
		$strAnimalists = "";
		$strScouts = "";
		if(substr($strBinary,33,1) == 1 AND substr($strBinary,34,1)== 1)
		{
			$allRouges = TRUE;
			$strRouges = "All Rouges ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[32] = 0;
			$temp_Binary[33] = 0;
			$temp_Binary[34] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if(substr($strBinary,36,1) == 1 AND substr($strBinary,37,1)== 1)
		{
			$allBards = TRUE;
			$strBards = "All Bards ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[35] = 0;
			$temp_Binary[36] = 0;
			$temp_Binary[37] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}
		if(substr($strBinary,39,1) == 1 AND substr($strBinary,40,1)== 1)
		{
			$allPredators = TRUE;
			$strPredators = "All Predators ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[38] = 0;
			$temp_Binary[39] = 0;
			$temp_Binary[40] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}

		if(substr($strBinary,42,1) == 1)
		{
			$allAnimalists = TRUE;
			$strAnimalists = "All Animalists ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[41] = 0;
			$temp_Binary[42] = 0;
			$strBinary = implode('', $temp_Binary);
			//print("\n" . $strBinary . "\n");
		}

		if($allRouges AND $allBards AND $allPredators AND $allAnimalists)
		{
			$strRouges = "";
			$strBards = "";
			$strPredators = "";
			$strAnimalists = "";
			$strScouts = "All Scouts ";
			$temp_Binary = str_split($strBinary,1);
			$temp_Binary[31] = 0;
			$temp_Binary[32] = 0;
			$temp_Binary[35] = 0;
			$temp_Binary[38] = 0;
			$temp_Binary[41] = 0;
			$strBinary = implode('', $temp_Binary);
			$allScouts = TRUE;
			//print("\n" . $strBinary . "\n");
		}

		$return_string .= $strRouges . $strBards . $strPredators . $strAnimalists . $strScouts;

		for($x = 0; $x <= strlen($strBinary) ; $x++)
		{
			$arryBinDigits[$x] = substr($strBinary, $x, 1);
		}

		for($x=0; $x <= strlen($strBinary); $x++)
		{
			if($arryBinDigits[$x] == "1")
			{
				$return_string .=( $arryAdventureClass[$x] . "  ");
			}
		}


		Return($return_string);
	}
	
	public function GetQuestRewardItemSlots($itemID)
	{
		global $eq2;
		$eq2->SQLQuery = "SELECT slots FROM `".ACTIVE_DB."`.items WHERE id = '".$itemID."' ";
		$data = $eq2->RunQuerySingle();
		$return_string = $this->eq2ItemSlots[($data['slots'])];
		Return($return_string);
	}

	public function GetQuestRewardItemSkills($skillID)
	{
		global $eq2;
		$eq2->SQLQuery = "SELECT name FROM `".ACTIVE_DB."`.skills WHERE id = '".$skillID."' ";
		$data = $eq2->RunQuerySingle();
		$return_string = $data['name'];
		Return($return_string);
	}

	public function GetQuestRewardItemLevel($itemID)
	{
		global $eq2;
		$eq2->SQLQuery = "SELECT required_level FROM `".ACTIVE_DB."`.items WHERE id = '".$itemID."' ";
		$data = $eq2->RunQuerySingle();
		$return_string = "Level: " . $data['required_level'];
		Return($return_string);
	}

	public function GetQuestRewardItemEffects($itemID)
	{
		global $eq2;
		$return_string="<ul>";

		$eq2->SQLQuery = "SELECT effect FROM `".ACTIVE_DB."`.item_effects WHERE item_id = '".$itemID."' ";
		$data = $eq2->RunQueryMulti();
		foreach($data as $row)
		{
			$return_string .= "<li>".$row['effect']."</li>";
		}
		$return_string .= "</ul>";

		Return($return_string);
	}

	public function GetQuestRewardItemDetails($itemID, $itemType)
	{
		global $eq2;
		switch($itemType)
		{
			case "Armor":
				$eq2->SQLQuery = "SELECT mitigation_low AS low, mitigation_high AS high FROM `".ACTIVE_DB."`.item_details_armor WHERE item_id = '".$itemID."' ";
				$data = $eq2->RunQuerySingle();
				$return_string = "Mitigation: (Low)".$data['low']." - (High)". $data['high'];
				break;
			case "Book":
				$eq2->SQLQuery = "SELECT language FROM `".ACTIVE_DB."`.item_details_book WHERE item_id = '".$itemID."' ";
				$data = $eq2->RunQuerySingle();
				$return_string = "Language: " . $data['language'];
				break;
			case "Bag":
				$eq2->SQLQuery = "SELECT num_slots, weight_reduction, backpack FROM `".ACTIVE_DB."`.item_details_bag WHERE item_id = '".$itemID."' ";
				$data = $eq2->RunQuerySingle();
				$return_string = "Slots:" . $data['num_slots'] . " Weight Reduction:" . $data['weight_reduction'] . " Backpack:" . $data['backpak'];
				break;
			case "House":
				$eq2->SQLQuery = "SELECT rent_reduction, status_rent_reduction, coin_rent_reduction, house_only FROM `".ACTIVE_DB."`.item_details_house WHERE item_id = '".$itemID."' ";
				$data = $eq2->RunQuerySingle();
				$return_string = "Rent Redu:" . $data['rent_reduction'] . " Status Rent Redu:" . $data['status_rent_reduction'] . " Coin Rent Redu:" . $data['coin_rent_reduction'] . " House Only:" . $data['house_only'];
				break;
			case "Food":
					$eq2->SQLQuery = "SELECT type, level, duration, satiation FROM `".ACTIVE_DB."`.item_details_food WHERE item_id = '".$itemID."' ";
					$data = $eq2->RunQuerySingle();
					$return_string = "Type:" . $data['type'] . " Level:" . $data['level'] . " Duration:" . $data['duration'] . " Satiation:" . $data['satiation'];
					break;
			default:
			$return_string = "No Item Details";
		}
		Return($return_string);
	}
}

?>