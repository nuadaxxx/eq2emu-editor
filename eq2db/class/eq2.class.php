<?php
if(!defined("EQ2CLS_LAYER")) {

define("EQ2CLS_LAYER","eq2Cls");

$DebugGeneralStartupWarnings = NULL;
$DebugGeneralStartupNotices = NULL;

class SQLNull {

}

class eq2Cls
{
	public $userdata;
	/*
	private $id = 0;
	*/
	private $min_pwd_length	= 3;
	private $cookie_timeout = 2592000; // 30 day timeout
	
	// SQL vars
	public $ObjectName		= NULL;
	public $TableName		= NULL;
	public $SQLQuery		= NULL;
	public $SQLError        = NULL;
	
	// Debug vars
	public $DebugGeneral	= NULL;
	public $DebugData		= NULL;
	public $DebugFunctions	= NULL;
	public $DebugForms		= NULL;
	public $DebugQueries	= NULL;
	public $Status = NULL;
	public $user_role = NULL;
	
	// Logger vars
	private $LogArray		= array();
	
	// Editor Arrays (load from SQL?)
	var $eq2Genders = array(
							0 => "",
							1 => "Male",
							2 => "Female"
							);
	
	var $eq2Races = array(
		0 => "Barbarian",
		1 => "Dark Elf",
		2 => "Dwarf",
		3 => "Erudite",
		4 => "Froglok",
		5 => "Gnome",
		6 => "Half Elf",
		7 => "Halfling",
		8 => "High Elf",
		9 => "Human",
		10 => "Iksar",
		11 => "Kerra",
		12 => "Ogre",
		13 => "Ratonga",
		14 => "Troll",
		15 => "Wood Elf",
		16 => "Fae",
		17 => "Arasai",
		18 => "Sarnak",
		19 => "Freeblood",		
	 255 => "ALL"
	);

	//THIS DATA IS AVAILABLE VIA EDITORDB IN TABLE eq2classes
	//WE CAN USE FIELD ho_class to identify TOP LEVEL CLASSES
	var $eq2ArchetypeSortedClasses = array(
		"Commoner" => array(
			0 => "Commoner"
		),
		"Fighter" => array (
			1 => "Fighter",
			2 => "Warrior",
			3 => "Guardian",
			4 => "Berserker",
			5 => "Brawler",
			6 => "Monk",
			7 => "Bruiser",
			8 => "Crusader",
			9 => "Shadowknight",
			10 => "Paladin",
		),
		"Priest" => array(
			11 => "Priest",
			12 => "Cleric",
			13 => "Templar",
			14 => "Inquisitor",
			15 => "Druid",
			16 => "Warden",
			17 => "Fury",
			18 => "Shaman",
			19 => "Mystic",
			20 => "Defiler",
			43 => "Shaper",
			44 => "Channeler"
		),
		"Mage" => array(
			21 => "Mage",
			22 => "Sorcerer",
			23 => "Wizard",
			24 => "Warlock",
			25 => "Enchanter",
			26 => "Illusionist",
			27 => "Coercer",
			28 => "Summoner",
			29 => "Conjuror",
			30 => "Necromancer",
		),
		"Scout" => array(
			31 => "Scout",
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
		)
	);

	var $eq2ArchetypeSortedTSClasses = array(
		"Unskilled" => array(
			0 => "Unskilled",
			1 => "Artisan"
		),
		"Craftsman" => array (
			2 => "Craftsman",
			3 => "Provisioner",
			4 => "Woodworker",
			5 => "Carpenter"
		),
		"Outfitter" => array(
			6 => "Outfitter",
			7 => "Armorer",
			8 => "Weaponsmith",
			9 => "Tailor"
		),
		"Scholar" => array(
			10 => "Scholar",
			11 => "Jeweler",
			12 => "Sage",
			13 => "Alchemist"
		)
	);

	var $eq2TSTechniques  = array(
		"Adorning" => 931275816,
		"Artificing" => 3330500131,
		"Artistry" => 3881305672,
		"Binding" => 2896808154,
		"Chemistry" => 2557647574,
		"Fletching" => 3076004370,
		"Focus" => 2638198038,
		"Geocraft" => 1386343008,
		"Geomancy" => 2812765109,
		"Metal Shaping" => 3108933728,
		"Metalworking" => 4032608519,
		"Scribing" => 773137566,
		"Sculpting" => 1039865549,
		"Tailoring" => 2082133324,
		"Thaumaturgy" => 2591116872,
		"Tinkering" => 1038997614,
		"Transmuting" => 1970131346,
		"Woodcraft" => 1478114179
	);

	var $eq2TSKnowledge = array(
		"Adorning" => 931275816,
		"Alchemy" => 2817699641,
		"Apothecary" => 574366497,
		"Arcana" => 2949308177,
		"Brawler" => 3856706740,
		"Conjuror" => 2042842194,
		"Craftsmanship" => 2639209773,
		"Culinary" => 935416212,
		"Geocraft" => 1386343008,
		"Geomancy" => 2812765109,
		"Heavy Armoring" => 1151456682,
		"Leather Armor" => 2897193374,
		"Light Armoring" => 2710531826,
		"Plate Armor" => 241174330,
		"Runecraft" => 2463992638,
		"Timbercraft" => 1703539708,
		"Tinkering" => 1038997614,
		"Transmuting" => 1970131346,
		"Weaponry" => 3395302654,
		"Weaving" => 2530063117,
		"Woodworking" => 1677747280
	);

	var $eq2TSDevices = array(
		"alchemy_mortar",
		"allyrian_kiln",
		"arcannase_destruction",
		"arcannase_magic",
		"arcannase_nature",
		"arcannase_passion",
		"arcannase_workbench",
		"bastion_ts_forge",
		"bcg_forge",
		"blood_iron_forge",
		"brell_forge",
		"brewery_station",
		"brewing_stump",
		"camp_fire",
		"chemistry_table",
		"crafting_intro_anvil",
		"dalnir_forge",
		"dinree_workbench",
		"dolas_mortar",
		"draconic_alchemystation",
		"draconite_forge",
		"drunder_great_forge",
		"everfrost_keg",
		"everfrost_sewingtable",
		"everfrost_workbench",
		"exp03_cauldron",
		"exp09_cardin_blessed_kiln",
		"exp14_td_turret",
		"exp14_ts_molten_throne_construct",
		"exp14_ts_sig_03_stage4",
		"exp14_ts_sig_03_stage8",
		"exp15_poj_chamber_pot_tradeskill_station",
		"forge",
		"foust cauldron",
		"froglok_bucket",
		"glaufaye_fire",
		"gneblin_workbench",
		"goblin_fire",
		"goblin_forge",
		"goblin_workbench",
		"hyperspacial_work_bench",
		"innovation_tradeskill_workstation",
		"kaesora_workbench",
		"live_event_burning_loom",
		"monument_pof",
		"nest_loom",
		"nest_workbench",
		"nika_x3_fire",
		"orc_forge",
		"pirate_distillery",
		"pixie_brewer",
		"planar_workstation",
		"pq_ballista",
		"pq_bhoughbomb_station",
		"pq_clockwork_station",
		"pq_fiend_fount",
		"pq_gateway_anchor",
		"pq_malicious_mixer",
		"pq_octoyogg_station",
		"pq_organ_key_1",
		"pq_organ_key_2",
		"pq_organ_key_3",
		"prayer_shawl_fire",
		"quadrolith_work_station",
		"quest_defiler_loom",
		"sathirian_alch_table",
		"sewing_table",
		"skull",
		"snowfang_fire",
		"snowfang_pot",
		"soh_ws_vessel",
		"sootfoot_forge",
		"spacial_work_bench",
		"spell_cube",
		"steam_tank",
		"stove",
		"stove and keg",
		"tinkered_workstation",
		"tower_desk",
		"tower_forge",
		"tower_work_bench",
		"ts_bastion",
		"ts_disease",
		"ts_innovation",
		"ts_poi_time_portal",
		"ts_solro",
		"tunarian_work_bench",
		"veiled mist forge",
		"woodworking_table",
		"work_bdesk",
		"work_bench",
		"work_desk"
	);

	//Populate this from the archetype sorted list in the constructor
	var $eq2Classes = array();

	var $eq2PlayableClasses = array(
		3	=> "Guardian",
		4 => "Berserker",
		6 => "Monk",
		7 => "Bruiser",
		9 => "Shadowknight",
		10 => "Paladin",
		13 => "Templar",
		14 => "Inquisitor",
		16 => "Warden",
		17 => "Fury",
		19 => "Mystic",
		20 => "Defiler",
		23 => "Wizard",
		24 => "Warlock",
		26 => "Illusionist",
		27 => "Coercer",
		29 => "Conjuror",
		30 => "Necromancer",
		33 => "Swashbuckler",
		34 => "Brigand",
		36 => "Troubador",
		37 => "Dirge",
		39 => "Ranger",
		40 => "Assassin",
		42 => "Beastlord",
		44 => "Channeler",
	 255 => "ALL"
	);

	var $eq2DamageTypes = array(
		0	 => "Slashing",
		1  => "Crushing",
		2  => "Piercing",
		3  => "Heat",
		4  => "Cold",
		5  => "Magic",
		6  => "Mental",
		7  => "Divine",
		8  => "Disease",
		9  => "Poison",
		10 => "Drown",
		11 => "Falling",
		12 => "Pain",
		13 => "Melee"
	);

	var $eq2StartingCities = array(
		1 	=> "Qeynos",
		2 	=> "Freeport",
		4 	=> "Kelethin",
		8 	=> "Neriak",
		16	=> "Gorowyn",
		32	=> "Halas"
	);

	var $eq2EquipSlots = array(
		1 				=> "Primary",
		2 				=> "Secondary",
		4 				=> "Head",
		8 				=> "Chest",
		16 				=> "Shoulders",
		32 				=> "Forearms",
		64 				=> "Hands",
		128 			=> "Legs",
		256 			=> "Feet",
		512 			=> "Ring 1",
		1024 			=> "Ring 2",
		2048 			=> "Ear 1",
		4096 			=> "Ear 2",
		8192 			=> "Neck",
		16384 		=> "Wrist 1",
		32768 		=> "Wrist 2",
		65536 		=> "Range",
		131072 		=> "Ammo",
		262144 		=> "Waist",
		524288 		=> "Cloak",
		1048576 	=> "Charm 1",
		2097152 	=> "Charm 2",
		4194304 	=> "Food",
		8388608 	=> "Drink",
		16777216 	=> "Texture"
	);

	var $eq2ItemMenuTypes = array(
		0					=> "unknown0",
		1					=> "unknown1",
		2					=> "Equip",
		3					=> "Normal",
		4					=> "unknown4",
		5					=> "unknown5",
		6					=> "unknown6",
		7					=> "Bag",
		8					=> "unknown8",
		9					=> "unknown9",
	 10					=> "House Equip",
	 11					=> "House"
	);

	var $eq2ItemSubTypes = array(
		0					=> "unknown0",
		1					=> "unknown1",
		2					=> "Unusable",
		4					=> "unknown4",
		8					=> "Attuned",
	 16					=> "Attunable",
	 32					=> "Readable",
	 64					=> "Display Charges"
	);

	// 32 = Legs, which is currently unused in code
	var $eq2Randomize = array(
		1 				=> "gender",
		2 				=> "race",
		4 				=> "model",
		8 				=> "face_hair_type",
		16 				=> "hair_type",
		64 				=> "wing_type",
		128 			=> "cheek",
		256 			=> "chin",
		512 			=> "ear",
		1024 			=> "eye_brow",
		2048 			=> "eye",
		4096 			=> "lip",
		8192 			=> "nose",
		16384 		=> "eye_color",
		32768 		=> "hair_color1",
		65536 		=> "hair_color2",
		131072 		=> "hair_hi",
		262144 		=> "face_hair_color",
		524288 		=> "face_hair_color_hi",
		1048576 	=> "hair_type_color",
		2097152 	=> "hair_type_color_hi",
		4194304 	=> "skin_color",
		8388608 	=> "wing_color1",
		16777216 	=> "wing_color2"
	);

	var $eq2Languages = array(
		1 => "Halasian",
		2 => "Thexian",
		3 => "Dwarven",
		4 => "Erudian",
		5 => "Guktan",
		6 => "Gnomish",
		7 => "Ayr'Dal",
		8 => "Stout",
		9 => "Koada'Dal",
		10 => "Lucanic",
		11 => "Antonican",
		12 => "Sebilisian",
		13 => "Kerran",
		14 => "Oggish",
		15 => "Ykeshan",
		16 => "Feir'Dal",
		17 => "Orcish",
		18 => "Gnollish",
		19 => "Draconic",
		20 => "Goblish",
		21 => "Thulian",
		22 => "Words of Shade",
		23 => "Fayefolk",
		24 => "Chaos Tongue",
		25 => "Krombral",
		26 => "Ratongan",
		27 => "Druzaic",
		28 => "Uruvanian",
		29 => "Death's Whisper",
		30 => "Screechsong",
		31 => "Volant",
		32 => "Argot",
		33 => "Serilian",
		34 => "Tik-Tok",
		35 => "Faerlie",
		36 => "Gorwish",
		37 => "Sathirian",
		38 => "Di'Zokian",
		39 => "Froak",
		40 => "Shisik",
		41 => "Sul'Dal",
		42 => "Ancient Draconic",
		43 => "Fleshless Tongue",
		44 => "Gymy"
	);

	//
	// Constructor
	//
	public function __construct() 
	{	
		include("mysql.class.php");
		foreach ($this->eq2ArchetypeSortedClasses as $classes) {
			foreach ($classes as $classID=>$className) {
				$this->eq2Classes[$classID] = $className;
			}
		}
		$this->db = new sql_db(env("DB_HOST"), env("DB_USER"), env("DB_PASS"), env("DB_NAME"));
		if( !$this->db->db_connect_id )
			$this->DBError();
			
		// read the role list (ACL)
		$this->role_list 	= $this->GetRoleList();
		
		// data needed for SQL logging
		$this->ObjectID 	= ( isset($_POST['object_id']) ) ? $_POST['object_id'] : (isset($_GET['id']) ? $_GET['id'] : NULL);
		$this->TableName 	= $_POST['table_name'] ?? NULL;

		global $DebugGeneralStartupWarnings, $DebugGeneralStartupNotices;
		if (isset($DebugGeneralStartupWarnings)) {
			foreach ($DebugGeneralStartupWarnings as $w) {
				$this->AddDebugGeneral("Warning", $w);
			}
			$DebugGeneralStartupWarnings = NULL;
		}
		if (isset($DebugGeneralStartupNotices)) {
			foreach ($DebugGeneralStartupNotices as $n) {
				$this->AddDebugGeneral("Notice", $n);
			}
			$DebugGeneralStartupNotices = NULL;
		}		
	}


	/* 
	 * 2013.11.22 
	 * Everything at the top of this script has been refactored to the new design
	 * 
	 * Things that still need to be identified and cleaned up are below in the next commented section
	*/
	



	/****************************************************************************
	 * Editor Config and Security Functions
	 ****************************************************************************/
	/*
		Function: LoadConfig()
		Purpose	: Function call to database to get eq2editor.config records.
		Syntax	: $eq2->LoadConfig()
	*/
	public function LoadConfig()
	{
		// keeping these separate routines for now in case they need to be re-called elsewhere
		$this->GetEditorConfig();		
		$this->LoadDataSources();
	}

	public function CheckAccess($role)
	{
		//printf("<p>Required: %s, User: %s</p>", $role, $this->user_role);
		if($role && ($role & $this->user_role) == $role)
			return true;
			
		return false;
	}

	public function DeleteCookie()
	{
		//printf("%s : %s<br />", __FUNCTION__, __LINE__);
		if (isset($_COOKIE['eq2db'])) 
		{
			foreach ($_COOKIE['eq2db'] as $key => $val) 
			{
				setcookie("eq2db[$key]", 0, time() - 1);
			}
		}
	}

	public function GetCookie()
	{
		if (isset($_COOKIE['eq2db'])) 
		{
			$user_data = $this->GetUser($_COOKIE['eq2db']['name'], $_COOKIE['eq2db']['pass']);

			if( is_array($user_data) )
				foreach($user_data as $key=>$val)
					$this->userdata[$key] = $val;

			return $this->userdata;
		}
		return 0;
	}

	private function SaveCookie($userdata)
	{
		$cookie_timeout = time() + $this->cookie_timeout;
		setcookie("eq2db[id]", $userdata['id'], $cookie_timeout);
		setcookie("eq2db[name]", $userdata['username'], $cookie_timeout);
		setcookie("eq2db[pass]", $userdata['password'], $cookie_timeout);
		setcookie("eq2db[db]", $userdata['datasource_id'], $cookie_timeout); // added for AJAX db lookups
	}
	
	private function ResetCookiePwd($pwd)
	{
		$cookie_timeout = time() + $this->cookie_timeout;
		setcookie("eq2db[pass]", $pwd, $cookie_timeout);
	}

	public function LastSQLError() {
		return $this->SQLError;
	}
	
	private function GetEditorConfig()
	{
		$this->SQLQuery = "SELECT config_name, config_value FROM config;";
		$rows = $this->RunQueryMulti();

		foreach($rows as $row)
			$GLOBALS['config'][$row['config_name']] = $row['config_value'];
	
		$this->GetRoles();
	}
	
	public function GetRoles()
	{
		$this->SQLQuery = "SELECT role_name, role_value, is_global FROM roles";
		$rows = $this->RunQueryMulti();
		
		foreach($rows as $row) {
			define(strtoupper(preg_replace("/\:/","_", $row['role_name'])), $row['is_global'] == "1" ? $row['role_value'] : 0);
		}

		//The following is just to get rid of warnings in intellisence
		if (!defined("G_DEVELOPER")) {
			define("G_DEVELOPER", 0);
		}
		if (!defined("G_ADMIN")) {
			define("G_ADMIN", 0);
		}
		if (!defined("G_GUIDE")) {
			define("G_GUIDE", 0);
		}
		if (!defined("G_SUPERADMIN")) {
			define("G_SUPERADMIN", 0);
		}
		if (!defined("M_ADMIN")) {
			define("M_ADMIN", 0);
		}
	}
	
	public function GetRoleList()
	{
		$this->SQLQuery = "SELECT * FROM roles WHERE is_global = 1;";
		return $this->RunQueryMulti();
	}
	
	public function GetUserRoleName()
	{
		foreach($this->role_list as $role=>$val)
		{
			if( $val[1] == $this->user_role )
				return $val[0];
			else
				continue;
		}
		
		return "Custom";
	}
	
	private function LoadDataSources()
	{
		$this->SQLQuery = "SELECT * FROM datasources WHERE is_active = 1;";
		$rows = $this->RunQueryMulti();
		
		$i = 1; // eq2editor DB = id 0, so start custom data configs at 1
		foreach($rows as $row)
			$GLOBALS['database'][$i++] = $row;
	}

	public function DBPicker()
	{
		$strHTML = "";
		return($strHTML);
	}
	
	private function GetUser($lname, $lpass)
	{
		$this->SQLQuery = sprintf("SELECT * FROM users WHERE username = '%s' AND password = '%s' LIMIT 0,1;", $this->SQLEscape($lname), $this->SQLEscape($lpass));
		$userdata = $this->RunQuerySingle('', false);
		
		if( is_array($userdata) )
		{
			//printf("%s == %s, %s", $userdata['session_id'], session_id(), !strcmp($userdata['session_id'], session_id()));
			// update last visited timestamp only when session id changes
			if( strcmp($userdata['session_id'], session_id()) != 0 )
				$this->UpdateLastVisited($userdata['id']);
			
			// display various login issues
			if( $userdata['is_active'] == 0 )
			{
				$this->DeleteCookie();
				$this->AddStatus("Account inactive. Cannot log in.");
			}
			else
				return $userdata;
		}
		return 0;
	}
	
	public function LoginUser()
	{
		$user_data = $this->GetUser($_POST['lName'], md5($_POST['lPass']));

		if( is_array($user_data) )
		{
			foreach($user_data as $key=>$val)
			{
				$this->userdata[$key] = $val;
			}
		
			$this->SaveCookie($this->userdata);
			$this->UpdateLastVisited($this->userdata['id']);
			return true;
		}
		
		return false;
	}

	private function UpdateLastVisited($user_id)
	{
		$this->SQLQuery = sprintf("UPDATE users SET last_visited = '%s', session_id = '%s' WHERE id = '%s'", time(), session_id(), $user_id);
		$this->RunQuery(false); // do not log query
	}
	
	public function DisplayChangeLogPicker($table_array)
	{
		if( is_array($table_array) )
		{
			$tableOptions = "";
			foreach($table_array as $table)
			{
				$tableOptions .= sprintf('<option value="%s?cl=history&table=%s"%s>%s</option>', 
																 $_SERVER['SCRIPT_NAME'], 
																 $table, 
																 ( $_GET['table'] == $table ) ? " selected" : "", 
																 $table);
			}
		}
		else
			$tableOptions = sprintf('<option value="%s?cl=history">No Tables Defined.</option>', $_SERVER['SCRIPT_NAME']);
				
		?>
		<table>
			<tr>
				<td>
					<select name="tableName" onchange="dosub(this.options[this.selectedIndex].value)">
						<option>Pick a table</option>
						<?= $tableOptions ?>
					</select>
				</td>
				<?php 
				if( isset($_GET['table']) ) 
				{ 
					$userOptions = $this->GetDBTeamSelector($_GET['table'], $_GET['id']);
				?>
				<td>Limit by user:&nbsp;
					<select name="user_id" onchange="dosub(this.options[this.selectedIndex].value)">
						<?= $userOptions ?>
					</select>
				</td>
				<?php 
				} 
				?>
			</tr>
		</table>
		<?php
		if( !empty($_GET['table']) ) 
		{
			printf("<p><b>All changes to the `<i>%s</i>` table on record - copy/paste to your SQL query window to apply changes to your database.</b></p>", $_GET['table']);
			printf("-- Changes to table: `%s`<br />", $_GET['table']);
			$this->showChangeLog($_GET['table'], $_GET['id']);
		}
	}
	
	private function GetDBTeamSelector($table = '',$id = 0) 
	{
		// function to be used only in changelogs due to cl=history
		if( $id == 0 ) 
			$editorOptions = "<option value=\"0\">---</option>";
			
		$link = sprintf("%s?cl=history&table=%s",$_SERVER['SCRIPT_NAME'],$_GET['table']);
		
		$this->SQLQuery = sprintf("SELECT user_id, username, count(*) AS edits FROM log WHERE archived = 0 %s GROUP BY username",( !empty($table) ? " AND table_name = '".$table."'" : ""));
		
		$rows = $this->RunQueryMulti();
		
		if( is_array($rows) )
		{
			foreach($rows as $data ) 
				$editorOptions .= sprintf('<option value="%s&id=%s"%s>%s (%s edits)</option>', $link, $data['user_id'], ( $id == $data['user_id'] ) ? " selected" : "", $data['username'], $data['edits']);
		}
		
		return $editorOptions;
	}

	private function ShowChangeLog($table = '', $id = 0) 
	{
		if( $id ) 
			$this->SQLQuery = sprintf("SELECT * FROM log WHERE archived = 0 AND table_name = '%s' AND user_id = %s ORDER BY update_date", $table, $id);
		else
			$this->SQLQuery = sprintf("SELECT * FROM log WHERE archived = 0 AND table_name = '%s' ORDER BY update_date", $table);

		$rows = $this->RunQueryMulti();

		if( is_array($rows) )
		{
			$i = 0;
			foreach($rows as $data) 
			{
				printf("-- On %s, <strong>%s</strong> edited '%s' in the '%s' table:<br />%s<br /><br />",
					date("Y/m/d H:m:s",$data['update_date']),$data['username'],$data['object_id'],$data['table_name'],$data['update_query']);
				$i++;
			}
		}
			
		if( $i )
			printf("-- %d edits found.<br />",$i);
		else
			print("-- No records found.<br />");
	}
	
	public function Redir($url, $delay = 0)
	{
		printf('<meta http-equiv="refresh" content="%s; url=%s" />', $delay, $url);
	}
	
	/*
		Function: TabGenerator2()
		Purpose	: Builds screen Tabs based on array '$tab_array'.
		Syntax	: $eq2->TabGenerator($current_tab_idx, array(...))
		Param		: Optional True/False param to strip check for `id` fields
						: $query_string represents the data filters
		Example	: $eq2->TabGenerator($current_tab_idx, array('general'=>'General','database'=>'Database','paths'=>'Paths','logs'=>'Logs','misc'=>'Misc'));						 
				: where array $key = tab querystring value, and $val is the title text shown on the Tab.
	*/
	function TabGenerator($current_tab, $tab_array, $query_string, $keep_id = true)
	{
		if( is_array($tab_array) )
		{
			print("<div id='mmtabs'>\n");
			print("  <ul>\n");
			foreach($tab_array as $key=>$val)
			{
				$tab_index = ( isset($current_tab) ) ? sprintf("&tab=%s", $key) : "";
				$is_active = ( $current_tab == $key ) ? ' id="activetab"' : '';
				$id = ( isset($_GET['id']) && $keep_id ) ? sprintf('&id=%s', $_GET['id']) : '';
				printf("    <li%s>\n",$is_active);
				printf("      <a href='%s%s%s'>\n",$query_string, $tab_index, $id);
				printf("        <span>%s</span>\n",$val);
				printf("      </a>\n");
				printf("    </li>\n");
			}
			print("    <div class='mmcolortabsline'>\n      &nbsp;\n    </div>\n  </ul>\n</div>\n<br />&nbsp;\n");
		}
	}

	//SAME THING BUT RETURNS STRING
	function ReturnTabGenerator($current_tab, $tab_array, $query_string, $keep_id = true)
	{
		if( is_array($tab_array) )
		{
			$return_string = "";
			$return_string .= "<div id='mmtabs'>\n";
			$return_string .= "  <ul>\n";
			foreach($tab_array as $key=>$val)
			{
				$tab_index = ( isset($current_tab) ) ? sprintf("&tab=%s", $key) : "";
				$is_active = ( $current_tab == $key ) ? ' id="activetab"' : '';
				$id = ( isset($_GET['id']) && $keep_id ) ? sprintf('&id=%s', $_GET['id']) : '';
				$return_string .= "    <li " . $is_active . ">\n";
				$return_string .= "      <a href='" . $query_string . $tab_index . $id . "'>\n";
				$return_string .= "        <span>" . $val . "</span>\n";
				$return_string .= "      </a>\n";
				$return_string .= "    </li>\n";
			}
			$return_string .= "    <div class='mmcolortabsline'>\n";
			$return_string .= "      &nbsp;\n";
			$return_string .= "    </div>\n";
			$return_string .= "  </ul>\n";
			$return_string .= "</div>\n";
			$return_string .= "<br />&nbsp;\n";
		}
		return($return_string);
	}

	/*
		Function: GridGenerator()
		Purpose	: Builds screen Grids based on arrays parameters.
		Syntax	: $vgo->GridGenerator($column_array, $data_array)
		Param		: $column_array() is made of 'column_name' => 'width'
						: $data_array() is 'column_name' => 'data'
		Note		: the $data_array must be derived by calling $vgo->RunQueryMulti('', 1) 
						: with those parameters, otherwise you get a filty array
	*/
	function GridGenerator($column_array, $data_array)
	{
		print('<table width="100%" cellpadding="4" cellspacing="0" border="0">');
		// column header
		print('<tr bgcolor="#cccccc">');
		foreach($column_array as $column=>$width)
			printf('<td width="%s"><strong>%s</strong></td>', $width, $column);
		print('</tr>');

		// grid data
		$i = 0;
		foreach($data_array as $row) // rows of chunks
		{
			$row_class = ( $i % 2 ) ? " bgcolor=\"#eeeeee\"" : "";
			printf('<tr%s valign="top">', $row_class);
			foreach($row as $data) // chunk data columns
			{
				//printf('%s => %s<br />', $key, $val);
				printf('<td nowrap>%s</td>', $data);
			}
			print('</tr>');
			$i++;
		}
		printf('<tr bgcolor="#CCCCCC"><td colspan="%s">%s rows returned...</td></tr>', count($column_array), $i);
		print('</table>');
	}



	/****************************************************************************
	 * Editor UI and Debugging
	 ****************************************************************************/
	private function GetBacktrace()
	{
		$output = '<div style="font-family: monospace;">';
		$backtrace = debug_backtrace();
		
		// We skip the first one, because it only shows this file/function
		unset($backtrace[0]);
		
		foreach ($backtrace as $trace)
		{
			// Strip the current directory from path
			$trace['file'] = (empty($trace['file'])) ? '(not given by php)' : htmlspecialchars($trace['file']);
			$trace['line'] = (empty($trace['line'])) ? '(not given by php)' : $trace['line'];
		
			// Only show function arguments for include etc.
			// Other parameters may contain sensible information
			$argument = '';
			if (!empty($trace['args'][0]) && in_array($trace['function'], array('include', 'require', 'include_once', 'require_once')))
			{
				$argument = htmlspecialchars($trace['args'][0]);
			}
		
			$trace['class'] = (!isset($trace['class'])) ? '' : $trace['class'];
			$trace['type'] = (!isset($trace['type'])) ? '' : $trace['type'];
		
			$output .= '<br />';
			$output .= '<b>FILE:</b> ' . $trace['file'] . '<br />';
			$output .= '<b>LINE:</b> ' . ((!empty($trace['line'])) ? $trace['line'] : '') . '<br />';
		
			$output .= '<b>CALL:</b> ' . htmlspecialchars($trace['class'] . $trace['type'] . $trace['function']);
			$output .= '(' . (($argument !== '') ? "'$argument'" : '') . ')<br />';
		}
		$output .= '</div>';
		return $output;
	}
	
	public function AddDebugGeneral($label, $data)
	{
		$this->DebugGeneral .= sprintf("<p>&nbsp;&nbsp;<strong>%s:</strong> %s</p>", $label, $data);
	}
	
	public function AddDebugData($name, $var)
	{
		if( is_array($var) )
		{
			$this->DebugData .= '<p>';
			foreach($var as $key=>$val)
				if( is_array($val) )
					foreach($val as $key2=>$val2)
						$this->DebugData .= sprintf("&nbsp;&nbsp;<strong>array[%s]</strong>: %s = %s<br />", $name, $key2, $val2);
				else
					$this->DebugData .= sprintf("&nbsp;&nbsp;<strong>array[%s]</strong>: %s = %s<br />", $name, $key, $val);
			$this->DebugData .= '</p>';
		}
		else
			$this->DebugData .= sprintf("<p>%s = %s</p>", $name, $var);
	}
	
	public function AddDebugFunction($func, $var)
	{
		$this->DebugFunctions .= sprintf("<p>&nbsp;&nbsp;<strong>function %s:</strong> %s</p>", $func, $var);
	}
	
	public function AddDebugForm($arr)
	{
		foreach($arr as $key=>$val)
		{
			if( is_array($val) )
				$this->DebugForms .= sprintf("<p>&nbsp;&nbsp;<strong>%s:</strong> %s</p>", $key, print_r($val)); // todo: find a better way to print the array data
			else
				$this->DebugForms .= sprintf("<p>&nbsp;&nbsp;<strong>%s:</strong> %s</p>", $key, $val);
		}
	}
	
	public function AddDebugQuery($func, $var)
	{
		$this->DebugQueries .= sprintf("<p>&nbsp;&nbsp;-- <strong>function %s:</strong><br />&nbsp;&nbsp;%s</p>", $func, $var);
	}

	public function AddStatus($var)
	{
		// only 1 status at a time?
		$this->Status = sprintf("&nbsp;&nbsp;%s", $var);
	}

	public function DisplayDebug()
	{
		?>
		<table width="100%" class="debug">
			<tr height="30" valign="bottom">
				<td><strong>STATUS Data:</strong></td>
			</tr>
			<tr>
				<td><?= $this->Status ?></td>
			</tr>
			<?php if (env("DEBUG")) { ?>
			<tr height="30" valign="bottom">
				<td><strong>DEBUG General:</strong></td>
			</tr>
			<tr>
				<td><?= $this->DebugGeneral ?></td>
			</tr>
			<?php } ?>
			<?php if( $GLOBALS['config']['debug_func'] ) { ?>
			<tr height="30" valign="bottom">
				<td><strong>DEBUG Functions:</strong></td>
			</tr>
			<tr>
				<td><?= $this->DebugFunctions ?></td>
			</tr>
			<?php } ?>
			<?php if( $GLOBALS['config']['debug_forms'] ) { ?>
			<tr height="30" valign="bottom">
				<td><strong>DEBUG Form Data:</strong></td>
			</tr>
			<tr>
				<td><?= $this->DebugForms ?></td>
			</tr>
			<?php } ?>
			<?php if( $GLOBALS['config']['debug_query'] ) { ?>
			<tr height="30" valign="bottom">
				<td><strong>DEBUG Queries:</strong></td>
			</tr>
			<tr>
				<td><?= $this->DebugQueries ?></td>
			</tr>
			<?php } ?>
			<?php if( $GLOBALS['config']['debug_data'] ) { ?>
			<tr height="30" valign="bottom">
				<td><strong>DEBUG Data:</strong></td>
			</tr>
			<tr>
				<td><?= $this->DebugData ?></td>
			</tr>
			<?php } ?>
		</table>
		<?php
	}

	public function DisplayStatus()
	{
		?>
		<table width="100%" class="warning">
			<tr>
				<td><strong>Status:</strong> <?= $this->Status ?></td>
			</tr>
		</table>
		<?php
	}

	public function GetStatus()
	{
		$ret = "<table width='100%' class='warning'>\n";
		$ret .= "  <tr>\n";
		$ret .= "    <td><strong>Status:</strong>" . $this->Status . "</td>\n";
		$ret .= "  </tr>\n";
		$ret .= "</table>\n";

		return($ret);
	}
	
	public function ResetPasswordForm()
	{
		$old_password = ( $this->userdata['reset_password'] ) ? 'value="***" disabled' : 'value=""';
	?>
	<div id="Editor">
		<table class="SubPanel" cellspacing="0" border="1">
			<tr>
				<td id="EditorStatus"><?php if( !empty($this->Status) ) $this->DisplayStatus(); ?></td>
			</tr>
			<tr>
				<td class="Title">Reset Password</td>
			</tr>
			<tr>
				<td valign="top">
					<form method="post" name="ResetPassword" />
					<table class="SectionMain" cellspacing="0">
						<tr>
							<td colspan="2" align="center" class="warning">You must reset your password before you can continue!</td>
						</tr>
						<tr>
							<td colspan="2" align="center">A strong password should consist of both upper/lower case characters, and at least one number (0-9) and a symbol ($, !, etc).</td>
						</tr>
						<tr>
							<td class="Label">Old Password:</td>
							<td class="Detail"><input type="password" name="old_password" <?= $old_password ?> /></td>
						</tr>
						<tr>
							<td class="Label">New Password:</td>
							<td class="Detail"><input type="password" name="lPass" value="" /></td>
						</tr>
						<tr>
							<td class="Label">Confirm Password:</td>
							<td class="Detail"><input type="password" name="pass2" value="" /></td>
						</tr>
						<tr>
							<td colspan="2" align="center"><input type="submit" name="cmd" value="Set Password" /></td>
						</tr>
					</table>
					<input type="hidden" name="id" value="<?= $this->userdata['id'] ?>" />
					<input type="hidden" name="lName" value="<?= $this->userdata['username'] ?>" />
					</form>
				</td>
			</tr>
		</table>
	</div>
	<?php
	}

	public function SavePassword()
	{
		// validate password
		if( ( strlen($_POST['lPass']) >= $this->min_pwd_length && strlen($_POST['pass2']) >= $this->min_pwd_length ) && $_POST['lPass'] == $_POST['pass2'] )
		{
			$new_password = md5($this->SQLEscape($_POST['lPass']));
			$this->SQLQuery = sprintf("UPDATE users SET password = '%s', reset_password = 0 WHERE id = %s", $new_password, $_POST['id']);

			if( $this->RunQuery(false) )
			{
				$this->DeleteCookie();
				$this->LoginUser();
			}
			else
				$this->AddStatus("Something went horribly wrong saving the password/cookie.");	
		}
		else
			$this->AddStatus("Invalid Password");	
	}
	

	/****************************************************************************
	 * SQL Database Functions
	 ****************************************************************************/
	public function DBError($bExit = true)
	{
		$error = $this->db->sql_error();
		$this->SQLError = $error;
		?>
		<div id="error-box">
		<table cellspacing="0" align="center">
			<tr>
				<td colspan="2" class="title">SQL Error: <?php print($error['code']); ?></td>
			</tr>
			<tr>
				<td class="label">Message:</td>
				<td class="detail"><?php print($error['message']); ?></td>
			</tr>
			<tr>
				<td class="label">Trace:</td>
				<td class="detail">
					<?php 
					print($this->GetBacktrace());
					?>
				</td>
			</tr>
			<tr>
				<td class="label">Query:</td>
				<td><?php print($this->SQLQuery); ?></td>
			</tr>
		</table>
		</div>
		<?php

		if ($bExit) {
			include("../editors/footer.php");
			exit;
		}
	}

	/* SELECT: Use this RunQuery to return a single-row result set */
	public function RunQuerySingle($sql = '', $bLog = true, $ignore_error = false)
	{
		global $GLOBALS;
		/*** Override $this with passed parameters ***/
		if( !isset($this->SQLQuery) && strlen($sql) > 6 )
			$this->SQLQuery = $sql;
		/**********************************************/
			
		// add ; to end of query, if missing, just for logging to screen
		if( strpos($this->SQLQuery, ";") == 0 )
			$this->SQLQuery = trim($this->SQLQuery) . ";";
			
		if ($bLog && env("DEBUG")) {
			$start_time = time();
			$this->AddDebugFunction(__FUNCTION__, "Enter");
			$this->AddDebugQuery(__FUNCTION__, $this->SQLQuery);
		}

		if( !$result = $this->db->sql_query($this->SQLQuery) ) {
			if(!$ignore_error)
				$this->DBError();
			$num_rows = 0;
			$rtn = 0;
		}
		else
		{
			$num_rows = $this->db->sql_numrows($result);
			$rtn = $this->db->sql_fetchrow($result);
		}
		
		if ($bLog && env("DEBUG")) {
			$this->AddDebugData("RunQuerySingle", $rtn);
			$this->AddDebugFunction(__FUNCTION__, $num_rows." row(s)");
			$Elapsed = time() - $start_time;
			if( $Elapsed > 1 )
				$Exit = sprintf("Exit - Elapsed: %s<br />Slow Query: %s", $Elapsed, $this->SQLQuery);
			else
				$Exit = "Exit";
			$this->AddDebugFunction(__FUNCTION__, $Exit);
		}

		unset($this->SQLQuery);  // nuke the query so it isn't used again
		
		return $rtn;
	}
	
	
	/* SELECT: Use this RunQuery to return a multiple-row result set */
	public function RunQueryMulti($sql = '')
	{
		global $GLOBALS;
		$rtn = NULL;
		$this->SQLError = null;

		/*** Override $this with passed parameters ***/
		if( !isset($this->SQLQuery) && strlen($sql) > 6 )
			$this->SQLQuery = $sql;
		/**********************************************/
			
		// add ; to end of query, if missing, just for logging to screen
		if( strpos($this->SQLQuery, ";") == 0 )
			$this->SQLQuery = trim($this->SQLQuery) . ";";
			
		if (env("DEBUG")) {
			$start_time = time();
			$this->AddDebugFunction(__FUNCTION__, "Enter");
			$this->AddDebugQuery(__FUNCTION__, $this->SQLQuery);
		}

		if( !$result = $this->db->sql_query($this->SQLQuery) )
			$this->DBError();
		else
		{
			$num_rows = $this->db->sql_numrows($result);
			$rtn = array();
			while ($data = $this->db->sql_fetchrow($result))
				$rtn[] = $data;
		}
		
		if (env("DEBUG")) {
			if (isset($rtn)) {
				$this->AddDebugData("RunQueryMulti Data", $rtn);
			}
			$this->AddDebugFunction(__FUNCTION__, $num_rows." row(s)");
			$Elapsed = time() - $start_time;
			if( $Elapsed > 1 )
				$Exit = sprintf("Exit - Elapsed: %s<br />Slow Query: %s", $Elapsed, $this->SQLQuery);
			else
				$Exit = "Exit";
			$this->AddDebugFunction(__FUNCTION__, $Exit);
		}

		unset($this->SQLQuery);  // nuke the query so it isn't used again
		
		return $rtn;
	}
	

	public function RunQuery($log = true, $sql = '')
	{
		global $GLOBALS;
		/*** Override $this with passed parameters ***/
		if( is_null($this->SQLQuery) && strlen($sql) > 6 )
			$this->SQLQuery = $sql;
		/**********************************************/
		$SQLError = null;
		
		// this should set the QueryType always
		$this->QueryType = substr($this->SQLQuery, 0, 6);
		
		// add ; to end of query, if missing, just for logging to screen
		if( strpos($this->SQLQuery, ";") == 0 )
			$this->SQLQuery = trim($this->SQLQuery) . ";";
			
		if (env("DEBUG")) {
			$start_time = time();
			$this->AddDebugFunction(__FUNCTION__, "Enter");
			if( $GLOBALS['config']['readonly'] )
				$this->AddDebugQuery(__FUNCTION__, "READ-ONLY: ".$this->SQLQuery);
			else
				$this->AddDebugQuery(__FUNCTION__, $this->SQLQuery);
		}

        $data = "";
		
		switch($this->QueryType)
		{
			case "SELECT":
				if( !$result=$this->db->sql_query($this->SQLQuery) )
					$this->DBError();
				else
					$data = $this->db->sql_fetchrow($result);
					
				break;
				
			case "INSERT":
			case "UPDATE":
			case "DELETE":
				if ($GLOBALS['config']['readonly']) {
                    $data = "READ-ONLY MODE (".$this->QueryType.") - No data updated!";
					$this->AddStatus($data);
					if ($log)
						$this->SQLLog(); // debugging
				} else {
					if ($log) {
						if ($this->SQLLog()) {
							$log_entry = $this->db->sql_last_insert_id();
						}
					}

					if (($result = $this->db->sql_query($this->SQLQuery)) == false ) {
						$this->DBError(false);

						if ($log_entry ?? false) {
							$this->db->sql_query(sprintf('DELETE FROM `log` WHERE id = %s;', $log_entry));
						}
						include("../editors/footer.php");
						exit;
					} else {
						$num_rows = $this->db->sql_affectedrows($result);
					}

					// if we're not logging this query, don't bother reporting successful
					if ($num_rows) {
                        $data = "Data update successful!";
						$this->AddStatus($data);
					}
						
				}
				break;
		}
		
		if (env("DEBUG")) {
			$this->AddDebugData("RunQuery Data", $data);
			$this->AddDebugFunction(__FUNCTION__, $num_rows." row(s)");
			$Elapsed = time() - $start_time;
			if( $Elapsed > 1 )
				$Exit = sprintf("Exit - Elapsed: %s<br />Slow Query: %s", $Elapsed, $this->SQLQuery);
			else
				$Exit = "Exit";
			$this->AddDebugFunction(__FUNCTION__, $Exit);
		}
		
		unset($this->QueryType); // nuke the type so it isn't used again
		unset($this->SQLQuery);  // nuke the query so it isn't used again
		
		return $num_rows;
	}

	private function SQLLog()
	{
		global $GLOBALS;
		// stuff insert, update, delete queries into eq2editor.log table
		if (env("DEBUG"))
			$this->AddDebugFunction(__FUNCTION__, "Enter");
		
		/*
		 * Logging Stuff goes Here
		 */
		if( $GLOBALS['config']['sql_log'] )
		{
			$pattern[0] = "/".ACTIVE_DB."\./i";
			$replace[0] = "";
			
			$log_query = preg_replace($pattern, $replace, $this->SQLQuery);
			
			$sql = sprintf("INSERT INTO log (user_id, username, table_name, object_id, update_query, update_date) VALUES ('%s','%s','%s','%s','%s','%s')",
																	$this->userdata['id'],
																	$this->SQLEscape($this->userdata['username']),
																	$this->SQLEscape($this->TableName),
																	$this->SQLEscape($this->ObjectID),
																	$this->SQLEscape($log_query),
																	time());
			return $this->db->sql_query($sql);
		}

		/*
		 * File Logging
		 */
		if( $GLOBALS['config']['sql_log_file'] )
		{
			$logfile = sprintf("../logs/session_%s_%s_week%s.txt", strtolower($this->userdata['username']), date("Y", time()), date("W", time()));
			$log_query .= "\n";
			
			if( $GLOBALS['config']['readonly'] )
				$this->AddStatus("READ-ONLY MODE - ".$logfile." not saved!");
			else
			{
				if( file_exists($logfile) ) 
				{
					if( !$f = fopen($logfile, 'a') ) 
						die("Cannot open existing filename: $logfile");
		
					if( !fwrite($f, $log_query) )
						die("Cannot write to existing filename: $logfile");
		
					fclose($f);
				} 
				else 
				{
					if( !$f = fopen($logfile, 'x') ) 
						die("Cannot create new file: $logfile");
						
					if( !fwrite($f, $log_query) )
							die("Cannot write to new filename: $logfile");
							
					fclose($f);
				}
			}
			
		}

		if (env("DEBUG"))
			$this->AddDebugFunction(__FUNCTION__, "Exit");
	}

	public function SQLEscape($str)
	{
		return $this->db->sql_escape($str);
	}
	
	public function ProcessUpdates($schema = ACTIVE_DB) 
	{
		
		$idx_field = $_POST['idx_name'] ?? "id";
		$idx_value = $_POST['orig_id'];
		$sets = '';
			
		foreach($_POST as $key=>$val) 
		{
			if($_POST['table_name']=='eq2lists' 
			OR $_POST['table_name'] == 'eq2list_types' 
			OR $_POST['table_name'] == 'eq2list_values' 
			OR $_POST['table_name'] == 'eq2lua_blocks' 
			OR $_POST['table_name'] == 'eq2lua_categories' 
			OR $_POST['table_name'] == 'eq2lua_types' 
			OR $_POST['table_name'] == 'config'
			OR $_POST['table_name'] == 'datasources'
			OR $_POST['table_name'] == 'eq2news_items'
			OR $_POST['table_name'] == 'eq2news_types'
			)
			{
				$schema = "";
			}
			//printf("<p>%s -> %s</p>\n", $key, $val);
			if (!isset($val)) continue;

			$chkKey = explode("|",$key);
			
			if( $chkKey[0]==$this->TableName ) 
			{
				//printf("<p>%s -> %s</p>", $key, $val);

				$field = $chkKey[1];
				if( $_POST['orig_'.$field] != $val ) 
				{
					if (!empty($sets) ) $sets .= ", ";

					if (is_a($val, "SQLNull")) {
						$sets .= sprintf("`%s` = NULL", $field);
					}
					else {
						$sets .= sprintf("`%s` = '%s'", $field, $this->SQLEscape($val));
					}
				}
			}
		}
		
		if( !empty($sets) ) 
		{
			$this->SQLQuery = sprintf("UPDATE ".$schema.".%s SET %s where `%s` = %s;", $this->TableName, $sets, $idx_field, $idx_value); //printf("<p>%s</p>", $this->SQLQuery); exit;
			$this->RunQuery();
		}		
		else
			$this->AddStatus("No data updated!");
			
	}

	public function ProcessDeletes($schema = ACTIVE_DB) 
	{
		if($_POST['table_name']=='eq2lists' 
			OR $_POST['table_name'] == 'eq2list_types' 
			OR $_POST['table_name'] == 'eq2list_values' 
			OR $_POST['table_name'] == 'eq2lua_blocks' 
			OR $_POST['table_name'] == 'eq2lua_categories' 
			OR $_POST['table_name'] == 'eq2lua_types' 
			OR $_POST['table_name'] == 'config'
			OR $_POST['table_name'] == 'datasources'
			OR $_POST['table_name'] == 'eq2news_items'
			OR $_POST['table_name'] == 'eq2news_types'
			)
			{
				$schema = "";
			}

		$idx_field = isset($_POST['idx_name']) ? $_POST['idx_name'] : "id";
		$idx_value	=	$_POST['orig_id'];

		$this->SQLQuery = sprintf("DELETE FROM ".$schema.".%s WHERE `%s` = '%s'", $this->TableName, $idx_field, $this->SQLEscape($idx_value)); 
		$this->RunQuery();
	}
	
	public function ProcessInserts($schema = ACTIVE_DB) 
	{
		if($_POST['table_name']=='eq2lists' 
			OR $_POST['table_name'] == 'eq2list_types' 
			OR $_POST['table_name'] == 'eq2list_values' 
			OR $_POST['table_name'] == 'eq2lua_blocks' 
			OR $_POST['table_name'] == 'eq2lua_categories' 
			OR $_POST['table_name'] == 'eq2lua_types' 
			OR $_POST['table_name'] == 'config'
			OR $_POST['table_name'] == 'datasources'
			OR $_POST['table_name'] == 'eq2news_items'
			OR $_POST['table_name'] == 'eq2news_types'
			OR $_POST['table_name'] == 'users'
		)
		{
			$schema = "";
		}
				
		$fields = "";
		$values = "";

		foreach($_POST as $key=>$val) 
		{

			$chkKey = explode("|",$key);
			if( $chkKey[0] == $this->TableName ) 
			{
				if($chkKey[1])
				if( empty($fields) )
				{
					$fields.="`".$chkKey[1]."`";
					$values.="'".$this->SQLEscape($val)."'";
				}
				else
				{
					$fields.=", `".$chkKey[1]."`";
					$val = ($val=='on'?'1':$val);
					$values.=",'".$this->SQLEscape($val)."'";
				}
			}
		}
		if( !empty($fields) ) 
		{
			$this->SQLQuery = sprintf("INSERT INTO ".$schema.".%s (%s) VALUES (%s);", $this->TableName, $fields, $values); 
			return $this->RunQuery();
		}
		else
			$this->AddStatus("No data updated!");
	}		

	public function ProcessMultiInsert() 
	{
		if($_POST['table_name']=='eq2lists' 
			OR $_POST['table_name'] == 'eq2list_types' 
			OR $_POST['table_name'] == 'eq2list_values'
			)
		{
			$schema = "";
		}

		$fields = "";
		$values = "";
		$rowset = "";

		foreach($_POST as $key=>$val) 
		{

			//$chkKey = explode("|",$key);
			
			$loottableRow = explode("!",$key);
			
			if($loottableRow[0] == "chkVal")
			{
				$rowset .= "(" . $_POST['e_list_id'] . "," . $loottableRow[1] . "),";
			}
		}
		$query = "INSERT INTO " . $schema . "." . $this->TableName . " (list_id, value) VALUES ". substr($rowset, 0, -1);
		print($query . "<br>");
		$this->SQLQuery = $query;
		$this->RunQuery();		
	}

	public function ProcessBulkDeletes() 
	{
		if($_POST['table_name']=='eq2lists' 
		OR $_POST['table_name'] == 'eq2list_types' 
		OR $_POST['table_name'] == 'eq2list_values'
		)
		{
			$schema = "";
		}else{
			$schema = ACTIVE_DB;
		}
		$rowset = "";
		foreach($_POST as $key=>$val) 
		{
			$tableRow = explode("|",$key);
			//print("[key:" . $tableRow[0] . "/val:" . $tableRow[1] . "]<br>");
			if($tableRow[0] == "DeleteSpawns")
			{
				$rowset .= $tableRow[1] . ",";
			}
			if($tableRow[0] == "delete_spawn_location_entry")
			{
				$rowset .= $tableRow[1] . ",";
			}
		}
		$query = "DELETE FROM `" . $schema . "`.`" . $this->TableName . "` WHERE id IN (". substr($rowset, 0, -1) . ");";
		//print($query . "<br>");
		$this->SQLQuery = $query;
		$this->RunQuery();		
	}
	
	public function DBInsertSpawnScript() 
	{
		global $eq2;
	
		// lookup Live zone name of NPC
		$eq2->SQLQuery = sprintf("SELECT id FROM `".ACTIVE_DB."`.zones WHERE description = '%s'", $eq2->SQLEscape($_POST['zone_name']));
		$data = $eq2->RunQuerySingle();
	
		if( $data['id'] > 0 ) 
			$zone_id = $data['id'];
		else
			die($_POST['zone_name'] . " not found in Live 'zones' table.");


		// get spawn_id from live spawn table
		$eq2->SQLQuery = sprintf("SELECT id FROM `".ACTIVE_DB."`.spawn WHERE name = '%s' AND id LIKE '%s____'", $eq2->SQLEscape($_POST['spawn_name']), $zone_id); 
		$data = $eq2->RunQuerySingle();

		if( $data['id']>0 )
			$spawn_id = $data['id'];
		else
			die($_POST['spawn_name'] . " not found in Live 'spawn' table. Cannot create script til zone is populated.");
			
		
		// insert record into live spawn_script if it does not already exist
		$eq2->SQLQuery = sprintf("SELECT count(*) AS cnt FROM `".ACTIVE_DB."`.spawn_scripts WHERE lua_script = '%s'", $_POST['script_name']);
		$data = $eq2->RunQuerySingle();
		
		if( $data['cnt'] == 0 ) 
		{
			$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.spawn_scripts (spawn_id, lua_script) VALUES ('%s','%s')", $spawn_id, $_POST['script_name']);
			$eq2->RunQuery();
		}
	}




	/****************************************************************************
	 * Get Functions
	 ****************************************************************************/
	public function GetClasses($id) // was getClassOptions()
	{
		$ret = "";
		foreach($this->eq2Classes as $key=>$val)
			$ret .= sprintf("<option value='%s'%s>%s</option>", $key, ( $key == $id ) ? " selected" : "", $val);

		return $ret;
	}
	
	public function GetClassSkills($id) 
	{
		$ret = "";

		if( $id==0 ) 
			$ret = "<option value=\"0\">---</option>";
			
		$this->SQLQuery = "SELECT id, name, short_name FROM `".ACTIVE_DB."`.skills ORDER BY name";

		$rows = $this->RunQueryMulti();
		
		foreach($rows as $row)
			$ret .= sprintf('<option value="%s"%s>%s</option>', $row['id'], ( $id == $row['id'] ) ? " selected" : "", $row['name']);
			
		return $ret;
	}
	
	public function GetNextIDX($table, $field) 
	{
		$this->SQLQuery = sprintf("SELECT MAX(%s)+1 AS nextID FROM `".ACTIVE_DB."`.%s", $field, $table);
		$data = $this->RunQuerySingle();
		return $data['nextID'];
	}
	
	public function GetPHPScriptName()
	{
		$tmp = explode("/", $_SERVER['SCRIPT_NAME']);
		return $tmp[count($tmp)-1];
	}
	



	/****************************************************************************
	 * LUA Script Functions
	 ****************************************************************************/
	public function CheckScriptExists($script) 
	{
		$ret = false;
		
		if( empty($script) ) 
			return $ret;

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;

		$file = $path . $script;

		if( file_exists($file) ) {
			$this->DebugGeneral .= '<p>Script Path: ' . $file . ' - FOUND</p>';
			$ret = true;
		} else {
			$this->DebugGeneral .= '<p>Script Path: ' . $file . ' - NOT FOUND</p>';
		}
		
		return $ret;
	}

	public function CreateLUATemplate($var)
	{
		// common header for all scripts
		$username = ( strlen($this->userdata['displayname']) > 0 ) ? $this->userdata['displayname'] : $this->userdata['username'];
		$template_header = "--[[\r\n";
		$template_header .= sprintf("    Script Name    : %s\r\n", $var);
		$template_header .= sprintf("    Script Author  : %s\r\n", $username);
		$template_header .= sprintf("    Script Date    : %s\r\n", date("Y.m.d h:m:s", time()));
		$template_header .= sprintf("    Script Purpose : \r\n");
		$template_header .= sprintf("                   : \r\n");
		$template_header .= "--]]\r\n\r\n";

		$template_body = "";
		
		$type = explode("/", $var);
		switch($type[0])
		{
			case "Spells":
				$sql = sprintf("SELECT description, bullet FROM `".ACTIVE_DB."`.spell_display_effects WHERE spell_id = %s GROUP BY `index`;", $_GET['id']);
				if( !$result = $this->db->sql_query($sql) )
					die("Error while fetching spell_display_effects in %s" . __FUNCTION__);
					
				$param_count = 0;
				$template_effect_comments = "--[[ Info from spell_display_effects (remove from script when done)\r\n\r\n";
				while( $data = $this->db->sql_fetchrow($result) )
				{
					$bullet = "";
					for($i=0;$i<$data['bullet'];$i++)
						$bullet .= "\t";
						
					$template_effect_comments .= sprintf("%s*%s\r\n", $bullet, $data['description']);
				}
				$template_effect_comments .= "\r\n--]]\r\n\r\n";
				
				$template_body .= $template_effect_comments;
				//$template_body .= sprintf("function cast(Caster, Target)\r\n    -- code to cast the spell\r\n    Say(Caster, \"Whoops! Guess this is not implemented yet!\")\r\n\r\n%s\r\nend\r\n\r\n", $template_effect_comments);
				//$template_body .= "function tick(Caster, Target)\r\n    -- code to process each call_frequency (tick) set in spell_tiers\r\nend\r\n\r\n";
				//$template_body .= "function remove(Caster, Target)\r\n    -- code to remove the spell\r\nend\r\n\r\n";
				break;

			case "ItemScripts":
				$this->SQLQuery = sprintf("SELECT effect, bullet FROM `".ACTIVE_DB."`.item_effects WHERE item_id = %s;", $_GET['id']);
				$rows = $this->RunQueryMulti();
				if (count($rows)) {
					$template_body .= "--[[ Begin Item Effects\r\n";
					foreach ($rows as $data) {
						$template_body .= "\r\n";
						$indent = $data['bullet'] & 0x7f;
						for ($i = 0; $i < $indent; $i++) {
							$template_body .= "\t";
						}
						$template_body .= "* ".$data['effect'];
					}
					$template_body .= "\r\n\r\nEnd Item Effects--]]\r\n\r\n";
				}
				$template_body .= "function examined(Item, Player)\r\n\r\nend\r\n\r\n";
				break;
				
			case "SpawnScripts":
				$template_body .= "function spawn(NPC)\r\n\r\nend\r\n\r\n";
				$template_body .= "function hailed(NPC, Spawn)\r\n\tFaceTarget(NPC, Spawn)\r\nend\r\n\r\n";
				$template_body .= "function respawn(NPC)\r\n\tspawn(NPC)\r\nend";
				break;

			case "Quests":
				// Quests require a different header... so overwrite the one above
				$template_header = "--[[\r\n";
				$template_header .= sprintf("    Script Name    : %s\r\n", $var);
				$template_header .= sprintf("    Script Author  : %s\r\n", $username);
				$template_header .= sprintf("    Script Date    : %s\r\n", date("Y.m.d h:m:s", time()));
				$template_header .= sprintf("    Script Purpose : \r\n\r\n");
				$template_header .= sprintf("        Zone       : %s\r\n", $type[1]);
				$template_header .= sprintf("        Quest Giver: \r\n");
				$template_header .= sprintf("        Preceded by: None\r\n");
				$template_header .= sprintf("        Followed by: \r\n");
				$template_header .= "--]]\r\n\r\n";
				
				$template_body .= "function Init(Quest)\r\n\r\nend\r\n\r\n";
				$template_body .= "function Accepted(Quest, QuestGiver, Player)\r\n\r\nend\r\n\r\n";
				$template_body .= "function Declined(Quest, QuestGiver, Player)\r\n\r\nend\r\n\r\n";
				$template_body .= "function quest_complete(Quest, QuestGiver, Player)\r\n\r\nend\r\n\r\n";
				$template_body .= "function Reload(Quest, QuestGiver, Player, Step)\r\n\r\nend\r\n\r\n";
				break;
				
			case "ZoneScripts":
				$template_body .= "function init_zone_script(Zone)\r\n\r\nend\r\n\r\n";
				$template_body .= "function player_entry(Zone, Player)\r\n\r\nend\r\n\r\n";
				break;
		}

		$template = $template_header . $template_body;
		return htmlspecialchars($template, ENT_HTML401 | ENT_NOQUOTES);
	}
	
	// Creates a new LUA script from CreateLUATemplate() as $ScriptName 
	public function CreateLUAScript($ScriptName, $LUAScriptText) 
	{
		$file = SCRIPT_PATH . $ScriptName;
		
		if( $GLOBALS['config']['readonly'] )
			$this->AddStatus("READ-ONLY MODE - ".$file." not created!");
		else
		{
			if( !$f = fopen($file,'w') ) 
			{
				if( !$this->CheckLUAFolderExists($_POST['script_path'], true) )
					die("Folder Name check failed: " . $_POST['script_path']);
				else if( !$f = fopen($file,'w') ) {			
					die("Cannot create filename: $file");
				}
			}

			//We created a file, fix the file permissions first then reopen it
			fclose($f);
			$this->FixFilePermissions($file);
			$f = fopen($file, 'w');
				
			if (!fwrite($f, $LUAScriptText)) 
				die("Cannot write to file ($file)");
			
			fclose($f);
		}
	}

	public function DeleteLUAScript($ScriptName)
	{
		$file = SCRIPT_PATH . $ScriptName;
		
		if( $GLOBALS['config']['readonly'] )
			$this->AddStatus("READ-ONLY MODE - ".$file." not deleted!");
		else
		{
			if( file_exists($file) )
				if( !unlink($file) ) 
					die("Cannot delete filename: $file");
		}
	}
	
	public function LoadLUAScript($script) 
	{
		if( empty($script) ) 
			return "Must provide a script path/file!";

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;
	
		$file = $path . $script;
		//print("llama debug:" . $file);

		$line = "";

		if( file_exists($file) ) 
		{
			if( !$f = fopen($file,'rb') ) 
				$line = "Cannot open existing filename: $file";

			while(!feof($f)) 
				$line .= fgets($f);
			
			fclose ($f); 
		} 
		else 
			$line = $this->CreateLUATemplate($script); // create new script off template
		
		return htmlspecialchars($line, ENT_HTML401 | ENT_NOQUOTES);		
	}
	
	public function RebuildLUAscript()
	{
		$script_relative_path = ( strlen($_POST['script_name']) > 0 ) ? $_POST['script_name'] : "";
		$script_text = $this->CreateLUATemplate($script_relative_path);
		$this->CreateLUAScript($script_relative_path, $script_text);
	}
	
	public function CheckLUAFolderExists($folder_name, $create = false) 
	{
		if( empty($folder_name) ) 
			die("Folder name to check cannot be blank!");

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;

		$f = $path . $folder_name;

		if( file_exists($f) ) 
		{
			return true;
		}
		elseif( !file_exists($f) && $create )
		{
			mkdir($f);
			// check again
			if( file_exists($f) ) 
			{
				$this->FixFilePermissions($f);
				return true;
			}			
		}
		
		return false;
	}

	public function FixFilePermissions($f)
	{
		if (PERM_SCRIPT != null) {
			//This is intended to be a bash script to be executed after a file is created.
			//You likely need to set up passwordless sudo for the script.
			$cmd = sprintf("sudo \"%s\" \"%s\" 2>&1", PERM_SCRIPT, $f);
			$output = null;
			$code = null;
			exec($cmd, $output, $code);

			if ($code != 0) {
				$this->AddDebugGeneral("PermScript", $cmd);
				$this->AddDebugGeneral("PermScript", "Code: ".$code);			
				$this->AddDebugGeneral("PermScript", print_r($output, true));
			}
		}
	}

	public function SaveLUAScript() 
	{
		if( empty($_POST['script_text']) ) 
			die("Cannot save a blank script path/file!");
			
		//$pattern[0] = "/\\\/i";
		//$replace[0] = "/";
		//$LUAScriptText = preg_replace($pattern, $replace, $_POST['script_text']);
		$LUAScriptText = htmlspecialchars_decode($_POST['script_text'], ENT_HTML401 | ENT_NOQUOTES);
			
		$file = SCRIPT_PATH . $_POST['script_name'];
		
		if( $GLOBALS['config']['readonly'] )
			$this->AddStatus("READ-ONLY MODE - ".$file." not saved!");
		else
		{
			// check if folder is there, create if not
			if( !$this->CheckLUAFolderExists($_POST['script_path'], true) )
				die("Folder Name check failed: " . $_POST['script_path']);

			$bCreate = !file_exists($file);
			
			if( !$f = fopen($file,'w') ) {
				require("../editors/footer.php");
				die("Cannot open filename: $file");
			}

			if ($bCreate) {
				//Fix the file permissions for this newly created file
				fclose($f);
				$this->FixFilePermissions($file);
				$f = fopen($file,'w');
			}

			if (!fwrite($f, $LUAScriptText)) 
				die("Cannot write to file ($file)");
				
			fclose($f);

			?>

			<script>
			window.addEventListener('load', (event) => {clearCachedScript();});
			</script>

			<?php
		}

		return $file;
	}

	public function GetLuaBlocks($action)
	{
		global $eq2;
		$strOffset = str_repeat("\x20",22);
		$return_string = "";
		
		switch($action)
		{
			
			case "showList":
				$thisPage = (isset($_GET['page'])?$_GET['page']:null);
				$thisTab = (isset($_GET['tab'])?$_GET['tab']:null);

				//SWITCH FOR CATEGORIES RELATED TO PAGE
				switch($thisTab)
				{
					case "item_script":
						$scriptType='items';
						break;
					case "spell_script":
						$scriptType='spells';
						break;
					case "edit":
						$scriptType='spawns';
						break;
					case "zone_script":
						$scriptType='zones';
						break;
					case "quest_script":
						$scriptType='quests';
						break;
					default:
						$scriptType='null';
				}

				$lua_block_query = "SELECT id,name,type FROM `eq2lua_categories`";
				if($thisPage != 'lua_blocks')
				{
					$lua_block_query .= "WHERE type=(SELECT id FROM `eq2lua_types` WHERE value='" . $scriptType . "') AND type != 0 \n";
				}
				$lua_categories = $eq2->RunQueryMulti($lua_block_query);
				$return_string .= $strOffset . "<tbody>\n";
				foreach($lua_categories as $category)
				{
					//PREP CAT HEADER FOR USE INSIDE THE NEXT LOOP
					$categoryHeader =  $strOffset . "<tr>\n";					
					$categoryHeader .= $strOffset . "  <th></th>\n";

					$type_name_query = "SELECT name FROM eq2lua_types WHERE id=" . $category['type'];
					$lua_category = $eq2->RunQuerySingle($type_name_query);
					$categoryHeader .= $strOffset . "  <th align='center'><i><small>" . $lua_category['name'] . "</small></i><br><strong>" . $category['name'] . "</strong></th>\n";

					if($thisPage == 'lua_blocks')
					{
						$categoryHeader .= $strOffset . "  <th>\n";
						$categoryHeader .= $strOffset . "    <a href='./server.php?page=lua_blocks&action=edit_category&id=" . $category['id'] . "'><i class='fa fa-pencil' aria-hidden='true' title='Edit Category'></i></a>\n";
						$categoryHeader .= $strOffset . "  </th>\n";
					}
					$categoryHeader .= "</tr>\n";
					$showHeader = true;
				
					//TRYING TO MAKE THIS GENERIC AS POSSIBLE SO WE CAN USE IT
					//IN ANY OF THE PAGES
					//NOTE: WE NEED TO ADD "SHARED LISTS" TOO, SOONISH
					$lua_block_query = "SELECT id, name, description, type, category, text ";
					$lua_block_query .= "FROM `eq2lua_blocks` WHERE ";
					$lua_block_query .= " category=" . $category['id'];
					$lua_block_query .= " ORDER BY shared, id";
					//print($lua_block_query . "\n");
					$lua_blocks = $eq2->RunQueryMulti($lua_block_query);

					//ALLOWS CATEGORIES THAT DON'T HAVE BLOCKS ON SERVER PAGE
					if($thisPage =='lua_blocks')
					{
						$return_string .= "" . $categoryHeader;
					}

					foreach($lua_blocks as $block)
					{
						//SHOWING CATEGORY ONLY ONCE
						if($thisPage !='lua_blocks' AND $showHeader)
						{
							$return_string .= $categoryHeader;
							$showHeader=false;
						}
						$return_string .= $strOffset . "<tr>\n";
						$return_string .= $strOffset . "  <td align='center'>" . $block['id'] . "</td>\n";
						$return_string .= $strOffset . "  <td align='center'>\n";
						$return_string .= $strOffset . "    <input type='button' name='block_" . $block['id'] . "' value='" . $block['name'] . "' class='submit-template' onclick=\"AddTextToEditor(this)\" myFuncText=\"" . htmlspecialchars($block['text']) . "\"/>\n";
						$return_string .= $strOffset . "  </td>\n";
						if($thisPage == 'lua_blocks')
						{
							$return_string .= $strOffset . "  <td>\n";
							$return_string .= $strOffset . "    <a href='./server.php?page=lua_blocks&action=edit_block&id=" . $block['id'] . "'><i class='fa fa-pencil' aria-hidden='true' title='Edit Block'></i></a>\n";
							$return_string .= $strOffset . "  </td>\n";
						}
						$return_string .= $strOffset . "</tr>\n";
					}

					
				}
				//DO IT ALL AGAIN FOR MISC SCRIPTS
				if($thisPage != 'lua_blocks')
				{

					$lua_block_cat_misc_query = "SELECT id, name, type FROM `eq2lua_categories` WHERE type=0";
					$lua_block_cats = $eq2->RunQueryMulti($lua_block_cat_misc_query);
					foreach($lua_block_cats as $lua_block_cat)
					{
						//SET THE CATEGORY HEADER
						$categoryHeader = $strOffset . "<tr>\n";					
						$categoryHeader .= $strOffset . "  <th></th>\n";

						$type_name_query = "SELECT name FROM eq2lua_types WHERE id=" . $lua_block_cat['type'];
						$lua_category = $eq2->RunQuerySingle($type_name_query);
						$categoryHeader .= $strOffset . "  <th align='center'><i><small>" . $lua_category['name'] . "</small></i><br><strong>" . $lua_block_cat['name'] . "</strong></th>\n";

						if($thisPage == 'lua_blocks')
						{
							$categoryHeader .= $strOffset . "  <th>\n";
							$categoryHeader .= $strOffset . "    <a href='./server.php?page=lua_blocks&action=edit_category&id=" . $lua_block_cat['id'] . "'><i class='fa fa-pencil' aria-hidden='true' title='Edit Category'></i></a>\n";
							$categoryHeader .= $strOffset . "  </th>\n";
						}
						$categoryHeader .= $strOffset . "</tr>\n";
						$showHeader = true;

						//GRAB ALL BLOCK FOR CATEGORY
						$lua_block_misc_query = "SELECT id, name, description, type, category, text FROM `eq2lua_blocks` WHERE category=".$lua_block_cat['id'];
						$lua_block_misc_data = $eq2->RunQueryMulti($lua_block_misc_query);
						
						foreach($lua_block_misc_data as $luablock_misc)
						{
							if($thisPage !='lua_blocks' AND $showHeader)
							{
								$return_string .= $categoryHeader;
								$showHeader=false;
							}
							$return_string .= $strOffset . "<tr>\n";
							$return_string .= $strOffset . "  <td align='center'>" . $luablock_misc['id'] . "</td>\n";
							$return_string .= $strOffset . "  <td align='center'>\n";
							$return_string .= $strOffset . "    <input type='button' name='block_" . $luablock_misc['id'] . "' value='" . $luablock_misc['name'] . "' class='submit-template' onclick=\"AddTextToEditor(this)\" myFuncText=\"" . htmlspecialchars($luablock_misc['text']) . "\"/>\n";
							$return_string .= $strOffset . "  </td>\n";
							if($thisPage == 'lua_blocks')
							{
								$return_string .= $strOffset . "  <td>\n";
								$return_string .= $strOffset . "    <a href='./server.php?page=lua_blocks&action=edit_block&id=" . $luablock_misc['id'] . "'><i class='fa fa-pencil' aria-hidden='true' title='Edit Block'></i></a>\n";
								$return_string .= $strOffset . "  </td>\n";
							}
							$return_string .= $strOffset . "</tr>\n";
						}
						$lua_block_misc_data = null;
					}
				}
				
				$return_string .= $strOffset . "</tbody>\n";
				return($return_string);
				break;
		}
	}

	public function EditLuaBlocks($action, $item)
	{

	}

	public function GenerateBlueCheckbox($inputName, $bChecked, $id = NULL) {
		?>
		<label class="blue_checkbox">
			<?php printf('<input type="checkbox" name="%s"%s%s />', $inputName, $bChecked ? " checked" : "", $id != null ? " id=".$id : ""); ?>
			<span class="checkmark"></span>
		</label>
		<?php
	}

	public function ReturnBlueCheckbox($inputName, $bChecked, $id = NULL) {

		$return_string = "<label class='blue_checkbox'>\n";
		$isChecked = ($bChecked?"checked":"");
		$return_string .= "  <input type='checkbox' name='" . $inputName . "' " . $isChecked . " id='" . $id . "'/>\n";
		$return_string .= "  <span class='checkmark'></span>\n";
		$return_string .= "</label>\n";

		return($return_string);
	}

	public function GenerateRowOrigValues($data) {
		foreach ($data as $k=>$v) : ?>
			<input type="hidden" name="orig_<?php echo $k ?>" value="<?php echo $v ?>" />
		<?php endforeach;
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
				$return_string = "Slots:" . $data['num_slots'] . " Weight Reduction:" . $data['weight_reduction'] . " Backpack:" . $data['backpack'];
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
			$return_string = "Item Type: " . $itemType . " (Note: Tell LLama to add this Type)";
		}
		Return($return_string);
	}

	public function GenerateItemLookup()
	{
		global $eq2;
		$return_string = "<i class='fa fa-exchange'></i>";
		return($return_string);
	}

	public function GenerateItemHover($id, $img='', $side='')
	{
		global $eq2;
		$return_string = "";
		$tab = (isset($_GET['tab'])?$_GET['tab']:"");
		$imgGreenRed = ($id>0?"green":"red");

		if($tab =='item_stats' OR $tab =='item_effects')
		{
			$cssDivClass = "tooltipBox";
			$cssSpanClass = "tooltipBoxtext" . $side;
			$imgCode = "";
		}else{

			$cssDivClass = "tooltip";
			$cssSpanClass = "tooltiptext" . $side;
			if($img=='')
			{
				$imgCode = "  <img src='../images/nav_plain_" . $imgGreenRed . ".png'>\n";
			}else{
				$imgCode = "  <img src='" . $img . "'>\n";
			}
		}
		$return_string .= "<div class='" . $cssDivClass . "'>\n";
		$return_string .= $imgCode;
		$return_string .= "  <span class='" . $cssSpanClass . "'>\n";
		
		if($id>0){
			//TOP LEVEL ITEM
			$itemQuery = "SELECT * FROM `" . ACTIVE_DB . "`.`items` WHERE id = " . $id;
			$itemData = $eq2->RunQuerySingle($itemQuery);
			$return_string .= "    <div id='tooltipTitle'>\n";
			$return_string .= "      <a href='items.php?show=items&id=" . $id . "' target='_blank'>" . $itemData['name'] . "</a>\n";
			$return_string .= "      <img src='eq2Icon.php?type=item&id=" . $itemData['icon'] . "&tier=" . $itemData['tier'] . "'>\n";
			$return_string .= "    </div>\n";

			//ITEM NAME & ICON
			$tagQuery = "SELECT name FROM `eq2meta` WHERE value=" . $itemData['tier'] . " AND type=(SELECT id FROM `eq2meta_types` WHERE name='item_tag');";
			$itemTag = $eq2->RunQuerySingle($tagQuery);
			$return_string .= "    <div id='tooltipTier'>" . (isset($itemTag['name'])?$itemTag['name']:"") . "</div>\n";

			//ITEM TAGS/FLAGS
			$itemToggleListQuery = "SELECT tag, name FROM `eq2meta` WHERE type=(SELECT id FROM `eq2meta_types` WHERE name='items_flag');";
			$itemToggleListData = $eq2->RunQueryMulti($itemToggleListQuery);
			$return_string .= "    <div id='tooltipToggles'>";
			foreach($itemToggleListData as $toggleListItem)
			{
				if($toggleListItem['tag'] != NULL)
				{
					if($itemData[$toggleListItem['tag']]==1)
					{
						$return_string .= $toggleListItem['tag'] . "&nbsp;&nbsp;";
					}
				}
			}
			$return_string .= "</div>\n";

			//ITEM STATS
			$itemStatsQuery = "SELECT type, subtype, iValue, fValue, sValue FROM `".ACTIVE_DB."`.`item_mod_stats` WHERE item_id = " . $id . " ORDER BY type, subtype ASC";
			$itemStatsData = $eq2->RunQueryMulti($itemStatsQuery);
			$return_string .= "    <div id='tooltipStats'>";
			foreach($itemStatsData as $stat)
			{
				$value = '';
				$combinedStat = ($stat['type']*100)+$stat['subtype'];
				$statNameQuery = "SELECT name FROM `eq2meta` WHERE type=6 AND subtype='stat_subtype' AND value=" . $combinedStat;

				$statName = $eq2->RunQuerySingle($statNameQuery);
				$sign = "+";
				if($stat['iValue'] != 'NULL' AND $stat['fValue'] > 0)
				{
					$value = $sign . $stat['fValue'];
				}elseif($stat['iValue'] >= 1)
				{
					$value = $sign . $stat['iValue'];
				}
				if(strlen($stat['sValue']) > 0)
				{
					$postfix = "(" . $stat['sValue'] . ")";
				}else{
					$postfix = "";
				}
				$return_string .= $value . " " . $statName['name'] . $postfix . "&nbsp;&nbsp;";
			}
			$return_string .= "</div>\n";

			//ITEM EFFECTS
			$return_string .= "    <div id='tooltipEffects'>";
			$itemEffectsQuery = "SELECT effect FROM `".ACTIVE_DB."`.`item_effects` WHERE item_id = '" .$id . "' ";
			$itemEffectsData = $eq2->RunQueryMulti($itemEffectsQuery);
			foreach($itemEffectsData as $effect)
			{
				$return_string .= $effect['effect']."&nbsp;&nbsp;";
			}
			$return_string .= "</div>\n";

			//ITEM SKILLS
			$itemSkillsQuery = "SELECT name FROM `".ACTIVE_DB."`.skills WHERE id = '". $itemData['skill_id_req'] ."' ";
			$itemSkillsData = $eq2->RunQuerySingle($itemSkillsQuery);
			$return_string .= "<div id='tooltipSkills'>";
			$return_string .= "  Req Skill:" . (isset($itemSkillsData['name'])?$itemSkillsData['name']:"");
			$return_string .= "</div>\n";

			//ITEM DETAILS
			$return_string .= "    <div id='tooltipDetails'>";
			$return_string .= $eq2->GetQuestRewardItemDetails($id, $itemData['item_type']);
			$return_string .= "</div>\n";

			//ITEM LEVEL
			$return_string .= "    <div id='tooltipLevel'>";
			$return_string .= "Req Lvl: " .$itemData['required_level'];
			$return_string .= "</div>\n";

			//ITEM ADVENTURE CLASS
			$return_string .= "    <div id='tooltipAdvClass'>";
			
			$return_string .= "</div>\n";
		}
		$return_string .= "</span>\n";
		$return_string .= "</div>\n";

		return($return_string);
	}




	/****************************************************************************
	 * JSON Functions
	 ****************************************************************************/
	



	/****************************************************************************
	 * Spawns Functions
	 ****************************************************************************/
	
































	/* 
	 * 2013.11.22 
	 * Everything below this comment needs to be refactored to the new style.
	 * As I refactor these functions, they will be moved up into the new area above so I can finally remove obsolete code.
	 *
	*/
	
	function __STUFF_BELOW_HERE_NEEDS_REFACTORING()
	{
	}
	
	// from UI function ShowQueryWindow()
	function processQuery()
	{
		$table = trim($_POST['tableName']);
		$objectName="QueryBox";
		$query_text = ( !empty($_POST['querytext']) ) ? trim($_POST['querytext']) : "";
		
		//printf("%s<br />", $query_text); exit;
		
		// is the query at least 10 characters? A valid query should never be shorter than INSERT INTO <table>
		if( strlen($query_text) >= 10 )
		{
			// check that the submitted query has semi-colons in it, or abort
			if( strpos($query_text, ';')===false )
				die("invalid query (no semi-colon)!");
			else
			{
				$queries = explode(";", $query_text);
				//print_r($queries);
				// parse the queries - see if there's more than 1, and process on a loop
				if( is_array($queries) )
				{
					$query_count = 0;
					foreach($queries as $testquery)
					{
						// make sure array is not an empty key
						if( strlen(trim($testquery)) > 3 )
						{
							// strip CRLFs
							$testquery = preg_replace("/[\r\n]+/i", "", $testquery);

							//printf("%s<br />", $testquery);
							// Check that query is INSERT or UPDATE ONLY
							$QueryType = strtoupper(substr($testquery, 0, 6));
							//printf("%s<br />", $QueryType);
							if( (strcmp($QueryType, "INSERT") <> 0) && (strcmp($QueryType, "UPDATE") <> 0) )
							{
								$p = array($table, "INVALID", $testquery);
								$this->dbEditorLog($p);
								die("Not an INSERT or UPDATE query!<br />" . $query_text);
							}

							// fix query if semi-colon was left off
							if( substr($testquery, strlen($testquery)-1, 1) != ';' )
							{
								$testquery .= ';';
							}
							
							// process query
							if( !$result = $this->db->sql_query($testquery) )
								$this->DBError($testquery);
							else
							{
								// log action
								$this->logQuery($testquery);
								$p = array($table, $objectName, $testquery);
								$this->dbEditorLog($p);
							}
						} // end strlen
					} // end foreach
				} // end is_array
			} // end strpos
		}
	}
	
	function GetFieldNames($table, $arr)
	{
	
		if( !empty($table) )
		{
			$query = sprintf("SHOW COLUMNS FROM %s", $table);
			$result = $this->db->sql_query($query);
			
			while( $row = $this->db->sql_fetchrow($result) )
			{
				if( in_array($row['Field'], $arr) )
					continue;
					
				//printf("%s:%s<br />", $table, $row['Field']);
				$field_array[] = $row['Field'];
			}
			
			return $field_array;
		}
	}
	
	// specific data processors - hope to get rid of these someday
	function processInsertVars($table) 
	{
		$objectName=$_POST['orig_variable_name'];
		foreach($_POST as $key=>$val) {
			$chkKey = explode("|",$key);
			if( $chkKey[0]==$table ) {
				if( empty($fields) ) :
					$fields.=$chkKey[1];
					$values.="'".$val."'";
				else :
					$fields.=", ".$chkKey[1];
					$values.=",'".$val."'";
				endif;
			}
		}		
		if( !empty($fields) ) {
			$sql = sprintf("insert into %s (%s) values (%s);",$table,$fields,$values); 
			$this->runQuery($sql);
			$this->logQuery($sql);
			$p = array($table,$objectName,$sql);
			$this->dbEditorLog($p);
		}		
	}
	
	function processUpdateVars($table) 
	{
		$objectName=$_POST['orig_variable_name'];
		$sets='';
		foreach($_POST as $key=>$val) {
			$chkKey = explode("|",$key);
			if( $chkKey[0]==$table ) {
				// has something changed?
				if( $_POST['orig_'.$chkKey[1]] != $val ) {
					if( empty($sets) ) :
						$sets.=$chkKey[1]."='".addslashes($val)."'";
					else :
						$sets.=", ".$chkKey[1]."='".addslashes($val)."'";
					endif;
				}
			}
		}
		if( !empty($sets) ) {
			$sql = sprintf("update %s set %s where variable_name = '%s';",$table,$sets,$objectName); 
			$this->runQuery($sql);
			$this->logQuery($sql);
			$p = array($table,$objectName,$sql);
			$this->dbEditorLog($p);
		}		
	}
	
	function processDeleteVars($table) 
	{
		$objectName=$_POST['orig_variable_name'];
		$sql = sprintf("delete from %s where variable_name = '%s';",$table,$objectName); 
		$this->runQuery($sql);
		$this->logQuery($sql);
		$p = array($table,$objectName,$sql);
		$this->dbEditorLog($p);
	}

	// name filters
	function processInsertBadName($table) 
	{
		$objectName=$_POST['orig_object'];
		foreach($_POST as $key=>$val) {
			$chkKey = explode("|",$key);
			if( $chkKey[0]==$table ) {
				if( empty($fields) ) :
					$fields.=$chkKey[1];
					$values.="'".$val."'";
				else :
					$fields.=", ".$chkKey[1];
					$values.=",'".$val."'";
				endif;
			}
		}		
		if( !empty($fields) ) {
			$sql = sprintf("insert into %s (%s) values (%s);",$table,$fields,$values); 
			$this->runQuery($sql);
			$this->logQuery($sql);
			$p = array($table,$objectName,$sql);
			$this->dbEditorLog($p);
		}		
	}
	
	function processUpdateBadName($table) 
	{
		$objectName=$_POST['orig_object'];
		$sets='';
		foreach($_POST as $key=>$val) {
			$chkKey = explode("|",$key);
			if( $chkKey[0]==$table ) {
				// has something changed?
				if( $_POST['orig_'.$chkKey[1]] != $val ) {
					if( empty($sets) ) :
						$sets.=$chkKey[1]."='".$val."'";
					else :
						$sets.=", ".$chkKey[1]."='".$val."'";
					endif;
				}
			}
		}
		if( !empty($sets) ) {
			$sql = sprintf("update %s set %s where name = '%s';",$table,$sets,$objectName); 
			$this->runQuery($sql);
			$this->logQuery($sql);
			$p = array($table,$objectName,$sql);
			$this->dbEditorLog($p);
		}		
	}
	
	function processDeleteBadName($table) 
	{
		$objectName=$_POST['orig_object'];
		$sql = sprintf("delete from %s where name = '%s';",$table,$objectName); 
		$this->runQuery($sql);
		$this->logQuery($sql);
		$p = array($table,$objectName,$sql);
		$this->dbEditorLog($p);
	}

	function scriptInDB($type, $script) 
	{
		switch($type) {
			case "SpawnScripts":
				$sql=sprintf("select count(*) as cnt from " . LIVE_DB . ".spawn_scripts where lua_script rlike '%s'", $this->db->sql_escape($script));
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
				$data=$this->db->sql_fetchrow($result);
				return ( $data['cnt']>0 ) ? 1 : 0;
				break;

			case "Quests":
				$sql=sprintf("select count(*) as cnt from " . LIVE_DB . ".quests where lua_script rlike '%s'",$this->db->sql_escape($script));
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
				$data=$this->db->sql_fetchrow($result);
				return ( $data['cnt']>0 ) ? 1 : 0;
				break;			

			case "Spells":
				$sql=sprintf("select count(*) as cnt from " . LIVE_DB . ".spells where lua_script rlike '%s'",$this->db->sql_escape($script));
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
				$data=$this->db->sql_fetchrow($result);
				return ( $data['cnt']>0 ) ? 1 : 0;
				break;
							
			case "Zones":
				$sql=sprintf("select count(*) as cnt from " . LIVE_DB . ".zones where zone_script rlike '%s'",$this->db->sql_escape($script));
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
				$data=$this->db->sql_fetchrow($result);
				return ( $data['cnt']>0 ) ? 1 : 0;
				break;
							
		}
	}


	//
	// Admin Functions
	//
	function CheckHasBugNotes($bug_id) 
	{
		$sql = sprintf("select count(*) as cnt from eq2editor.bug_notes where bug_id = %lu;", $bug_id);
		$data = $this->runScalarQuery($sql);
		return ( $data['cnt'] ) ? "*" : "&nbsp;";
	}
	
	function loglist($binlog)
	{
		if( !defined("LOG_PATH") || !defined("LOGIN_FOLDER") || !defined("PATCH_FOLDER") || !defined("WORLD_FOLDER") )
			die("All PATH constants not set in config.php");
		else
			$path = LOG_PATH . $binlog . "/logs/";

		if (($d = @opendir ($path)) === false) 
			echo ('[Could not open path: ]'.$path);
		else 
		{
			while ($f = readdir ($d)) 
			{
				if( $f != "." && $f != ".." )
				{
					$selected = ( $f == $_GET['log'] ) ? " selected" : "";
					$logOptions .= sprintf('<option value="_admin.php?page=logs&type=%s&log=%s"%s>%s</option>', $binlog, $f, $selected, $f);
				}
			}
		}
		return $logOptions;
		
	}
	
	function parse_log_line( $data ) 
	{
	// get command handler from hex
	//	if( preg_match("/.*?: \w{2}/", $data) )
	//	{
	//		$command = hexdec( substr( $data, 6, 2 ) );
	//		// fetch command from `commands` table
	//		$sql = "SELECT command, subcommand, required_status FROM commands WHERE handler = " . $command;
	//		return sprintf("Command: %s<br />", $command);
	//	}
		
	
		// clean up any other lines
		$pattern[0] = "/Unhandled command: .*?[\r\n]+/i";
		$pattern[1] = "/Request2: [\r\n]+/i";
	
		$replace[0] = "";
		$replace[1] = "";
		
		$rtn = preg_replace($pattern, $replace, $data);
	
		return $rtn;	
	}

	function ForMe($data)
	{
		foreach($data as $key=>$val)
		{
			if( is_array($val) )
			{
				foreach($val as $key2=>$val2)
					printf("=> Array: %s = %s<br />", $key2, $val2);
			}
			else
				printf("%s = %s<br />", $key, $val);
		}
		print("<br />");
	}
	
	function ForMeClean($data)
	{
		foreach($data as $key=>$val)
		{
			if( is_array($val) )
			{
				foreach($val as $key2=>$val2)
					printf("%s<br />", $val2);
			}
			else
				printf("%s<br />", $val);
		}
		print("<br />");
	}
	
	function file_get_contents_curl($url,$json=false)
	{
		global $eq2;
		$ch = curl_init();
		$headers = array();
		if($json) {
				$headers[] = 'Content-type: application/json';
				$headers[] = 'X-HTTP-Method-Override: GET';
		}
		$options = array(
				CURLOPT_URL => $url,
				CURLOPT_HTTPHEADER => array($headers),
				CURLOPT_TIMEOUT => 20,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_HEADER => 0,
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_MAXREDIRS => 3,
				CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)'
		);
		curl_setopt_array($ch,$options);
		$response = curl_exec($ch);
		$err = NULL;
		if($response == false) {
			if ($err = curl_error($ch)) $eq2->AddDebugGeneral("CURL", $err);
			curl_close($ch);
			return false;
		}

		curl_close($ch);
		return $response;
	}
	
	
	//
	// LUA Script Functions
	//
	function BuildAllSpawnScripts() 
	{
		global $dialog_id, $conversation_id, $sequence_id, $index_id, $spawn_id, $npc_text;

		$pattern[0]="/ /i";
		$pattern[1]="/'/i";
		$pattern[2]="/`/i";
		$pattern[3]="/\"/i";
		$pattern[4]="/\./i";

		$myArray = explode("|",$_POST['spawn_names']);
		foreach($myArray as $key=>$val) {
			$spawn_data			= $this->runQuery("select id, name, guild from `".PARSER_DB."`.raw_spawn_info where id = ".$val);
			$scriptName			= preg_replace($pattern,"",$spawn_data['name']).".lua";
			$scriptPath			= sprintf("SpawnScripts/%s/", $_POST['zone_name']);
			$fullScriptPath	= $scriptPath."$scriptName";

			
			// check existing
			if( !$this->existsSpawnScript($fullScriptPath) ) {
				//printf("%s<br>", $fullScriptPath);
				//printf("%s = %s<br>", $key, $val);
				
				// clear block variables before using them
				$isFirst 							= true;
				$hailBlock						= '';
				$npc_text							= '';
				$functionBlock 				= '';
				$rawConversationsBlock = '';

				// script header
				$scriptHeader = sprintf("--[[\n");
				$scriptHeader .= sprintf("\tScript Name\t: %s\n",$fullScriptPath);
				$scriptHeader .= sprintf("\tScript Purpose\t: %s %s\n", $scriptName, $spawn_data['guild']);
				$scriptHeader .= sprintf("\tScript Author\t: %s\n", $_SESSION['cookieUserName']);
				$scriptHeader .= sprintf("\tScript Date\t: %s\n",date("Y.m.d",time()));
				$scriptHeader .= sprintf("\tScript Notes\t: Auto-Generated Conversation from PacketParser Data\n");
				$scriptHeader .= sprintf("--]]\n\n");
			
				$sql=sprintf("select * from `".PARSER_DB."`.raw_dialogs where spawn_id = %d order by conversation_id,sequence;", $spawn_data['id']); //echo $sql; exit;
				$result=$this->db->sql_query($sql);
				while($data=$this->db->sql_fetchrow($result)) {
				
					
					$dialog_id 				= $data['id'];
					$conversation_id	= $data['conversation_id'];
					$sequence_id 			= $data['sequence'];
					$index_id 				= $data['index'];
					$spawn_id 				= $data['spawn_id'];
					
					$pattern1[0]="/\"/i";
					$pattern1[1]="/X{4,14}/";
					$replace1[0]="'";
					$replace1[1]="\" .. GetName(Spawn) .. \"";
					$npc_text					= preg_replace($pattern1,$replace1,$data['npc_text']);

					
					if( $sequence_id==0 ) { // part of Hail
						if( $isFirst ) { // is first Hail in function
							$isFirst=false;
							
							$hailBlock .= "function spawn(NPC)\n";
							$hailBlock .= "\tSetPlayerProximityFunction(NPC, 10, \"InRange\", \"LeaveRange\")\n";
							if( $this->isQuestNPC($spawnName) ) { // insert Provides Quest feather
								$isQuest=true;
								$hailBlock .= "\tProvidesQuest(NPC, 1)\n";
							} 
							$hailBlock .= "end\n\n";
							$hailBlock .= "function respawn(NPC)\n";
							$hailBlock .= "\tspawn(NPC)\n";
							$hailBlock .= "end\n\n";
	
							$hailBlock .= "function InRange(NPC, Spawn)\n";
							$hailBlock .= "end\n\n";
	
							$hailBlock .= "function LeaveRange(NPC, Spawn)\n";
							$hailBlock .= "end\n\n";
			
							$hailBlock .= "function hailed(NPC, Spawn)\n";
							$hailBlock .= "\tFaceTarget(NPC, Spawn)\n";
							$hailBlock .= "\tconversation = CreateConversation()\n\n";
			
							// if NPC greets you with random callouts, get them from raw_conversations here - build into function hailed() for now
							$hailBlock .= $this->buildRandomGreeting($spawn_id);
							
							if( $isQuest ) {
								$hailBlock .= sprintf("\n\tif HasCompletedQuest(Spawn, 1) then\n");
								$hailBlock .= "\telse\n";
								$hailBlock .= sprintf("\t\tPlayFlavor(NPC, \"%s\", \"\", \"\", %s, %s, Spawn)\n",$data['voice_file'],$data['key1'],$data['key2']);
								$hailBlock .= $this->buildHailChoices();
								$hailBlock .= sprintf("\t\tStartConversation(conversation, NPC, Spawn, \"%s\")\n",$npc_text);
								$hailBlock .= "\tend\n\n";
								$isQuest=false;					
							} else {
								$hailBlock .= sprintf("\t\tPlayFlavor(NPC, \"%s\", \"\", \"\", %s, %s, Spawn)\n",$data['voice_file'],$data['key1'],$data['key2']);
								$hailBlock .= $this->buildFunctionChoices();
								$hailBlock .= sprintf("\tStartConversation(conversation, NPC, Spawn, \"%s\")\n",$npc_text);
							}
						} else { // is hail, but not first
							$hailBlock .= sprintf("\tif convo==%d then\n",$conversation_id);
							$hailBlock .= sprintf("\t\tPlayFlavor(NPC, \"%s\", \"\", \"\", %s, %s, Spawn)\n",$data['voice_file'],$data['key1'],$data['key2']);
							$hailBlock .= $this->buildHailChoices();
							$hailBlock .= sprintf("\t\tStartConversation(conversation, NPC, Spawn, \"%s\")\n",$npc_text);
							$hailBlock .= "\tend\n\n";
						}
					} else { // is not a part of Hail
						$functionBlock .= sprintf("function dlg_%s_%s(NPC, Spawn)\n",$conversation_id,$sequence_id);
						$functionBlock .= "\tFaceTarget(NPC, Spawn)\n";
						$functionBlock .= "\tconversation = CreateConversation()\n\n";
						$functionBlock .= sprintf("\tPlayFlavor(NPC, \"%s\", \"\", \"\", %s, %s, Spawn)\n",$data['voice_file'],$data['key1'],$data['key2']);
						$functionBlock .= $this->buildFunctionChoices();
						$functionBlock .= sprintf("\tStartConversation(conversation, NPC, Spawn, \"%s\")\n",$npc_text);
						$functionBlock .= sprintf("end\n\n");
					}
				}
			
				$rawConvos = $this->getRawConversations($spawn_id);
				if( !empty($rawConvos) ) {
					$rawConversationsBlock .= "--[[ raw_conversations\n";
					$rawConversationsBlock .= $rawConvos;
					$rawConversationsBlock .= "--]]\n\n";
				}
				
				$pattern[0]="/\\t/i";
				$pattern[1]="/\\n/i";
				$replace[0]="&nbsp;&nbsp;";
				$replace[1]="<br />";
				
				$hailBlock .= "end\n\n";
				
				// print to textarea and offer option to create new script file
				$script_text = $scriptHeader . $hailBlock . $functionBlock . $rawConversationsBlock;

				$post_array['spawnScriptText'] 	= $script_text;
				$post_array['zone_name'] 				= $_POST['zone_name'];
				$post_array['spawn_name'] 			= $spawn_data['name'];
				$post_array['spawnScriptName'] 	= $scriptName;
				$post_array['spawnScriptPath'] 	= $scriptPath;
				$post_array['orig_object'] 			= $fullScriptPath;
				
				// print_r($post_array);

				// stuffInDB()
					// lookup Live zone name of NPC
					$sql=sprintf("select id from `" . ACTIVE_DB . "`.`zones` where name = '%s';", $post_array['zone_name']);
					$result=$this->db->sql_query($sql);
					$data=$this->db->sql_fetchrow($result);
					if( $data['id']>0 ) {
						$zone_id = $data['id'];
					} else {
						die($_POST['zone_name'] . " not found in Live 'zones' table.");
					}
					
					// get spawn_id from live spawn table
					$sql=sprintf("select id from `" . ACTIVE_DB . "`.`spawn` where name = '%s' and id like '%d____';", addslashes($post_array['spawn_name']), $zone_id); 
					// echo $sql;
					$result=$this->db->sql_query($sql);
					$data=$this->db->sql_fetchrow($result);
					if( $data['id']>0 ) {
						$spawn_id = $data['id'];
					} else {
						die($post_array['spawn_name'] . " not found in live spawn table. Cannot create script til zone is populated.");
					}
					
					// insert record into live spawn_script if it does not already exist
					$sql=sprintf("select count(*) as cnt from `" . ACTIVE_DB . "`.`spawn_scripts` where lua_script = '%s';", $post_array['orig_object']);
					$result=$this->db->sql_query($sql);
					$data=$this->db->sql_fetchrow($result);
					if( $data['cnt'] == 0 ) {
						$sql=sprintf("insert into `" . ACTIVE_DB . "`.`spawn_scripts` (spawn_id,lua_script) values ('%s','%s');", $spawn_id, $post_array['orig_object']);
						if( !$result2=$this->db->sql_query($sql) ) {
							die("Error inserting " . $post_array['orig_object'] . " into spawn_scripts table.");
						}
					}
				
					$this->logQuery($sql);
					$p = array('spawn_scripts',$post_array['orig_object'],$sql);
					$this->dbEditorLog($p);


				// saveSpawnScript()
				$path = SCRIPT_PATH . $post_array['spawnScriptPath'];
				if( !file_exists($path) ) mkdir($path); 
				if( !preg_match("/.lua/",$post_array['spawnScriptName']) ) $post_array['spawnScriptName'].=".lua";
				$file = $path . $post_array['spawnScriptName'];
				$script_name = sprintf("Script Name\t: %s%s", preg_replace("/\//","\/",$post_array['spawnScriptPath']), $post_array['spawnScriptName']);
				if( !preg_match("/$script_name/i", $post_array['spawnScriptText']) ) {
					$pattern[0] = sprintf("/Script Name\t: %s/i", $post_array['spawnScriptName']);
					$pattern[1] = "/Script Name\t: <script-name>/i";
					$replace[0] = stripslashes($script_name);
					$replace[1] = stripslashes($script_name);
					$post_array['spawnScriptText'] = preg_replace($pattern, $replace, $post_array['spawnScriptText']);
				}

				// new file create
				if( !$f = fopen($file,'w') ) die("Cannot create filename: $file");
				if (!fwrite($f, stripslashes($post_array['spawnScriptText']))) die("Cannot write to file ($file)");
				fclose($f);
			}
		}
	}

	function loadQuestScript($script) 
	{
		if( empty($script) ) return "Must provide a script path/file!";
		if( !preg_match("/.lua/",$script) ) $script.=".lua";

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;

		
		$file = $path . $script;
		if( file_exists($file) ) {
			if( !$f = fopen($file,'rb') ) {
				$line = "Cannot open existing filename: $file";
			}
			while(!feof($f)) {
				$line .= fgets($f);
			}
			fclose ($f); 
		} else {
			// open the template file
			$file = $path . "Quests/template.lua";
			if( !$f = fopen($file,'rb') ) {
				$line = "Cannot open existing filename: $file";
			}
			while(!feof($f)) {
				$line .= fgets($f);
			}
			fclose ($f); 
		}
		return $line;		
	}

	function saveQuestScript($data) 
	{
		if( empty($data) ) die("No script data to process - aborting!");

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH . $data['questScriptPath'];

		if( !file_exists($path) ) mkdir($path); 
		if( !preg_match("/.lua/",$data['questScriptName']) ) $data['questScriptName'].=".lua";
		$file = $path . $data['questScriptName'];
//		$script_name = sprintf("Script Name\t: %s%s", preg_replace("/\//","\/",$data['questScriptPath']), $data['questScriptName']);
//		if( !preg_match("/$script_name/i", $data['questScriptText']) ) {
//			$pattern[0] = "/Script Name\t: <script-name>/i";
//			$pattern[1] = sprintf("/Script Name\t: %s/i", $data['questScriptName']);
//			$replace[0] = stripslashes($script_name);
//			$replace[1] = stripslashes($script_name);
//			$data['questScriptText'] = preg_replace($pattern, $replace, $data['questScriptText']);
//		} 

		// new file create
		if( !$f = fopen($file,'w') ) die("Cannot create filename: $file");

		// 2009.09.28 - removed stripslashes for Scat
		// 2010.01.18 - added stripslashes for Scat
		if (!fwrite($f, stripslashes($data['questScriptText']))) die("Cannot write to file ($file)");
		//if (!fwrite($f, $data['questScriptText'])) die("Cannot write to file ($file)");
		fclose($f);
	}

	function existsSpawnScript($script) 
	{
		if( empty($script) ) return "Must provide a script path/file!";
		if( !preg_match("/.lua/",$script) ) $script.=".lua";

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;

		$file = $path . $script;
		if( file_exists($file) ) {
			return true;
		}
		return false;
	}
	
	function loadSpawnScript($script) 
	{
		if( empty($script) ) return "Must provide a script path/file!";
		if( !preg_match("/.lua/",$script) ) $script.=".lua";
		
		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;

		$file = $path . $script;
		if( file_exists($file) ) {
			if( !$f = fopen($file,'rb') ) {
				$line = "Cannot open existing filename: $file";
			}
			while(!feof($f)) {
				$line .= fgets($f);
			}
			fclose ($f); 
		} else {
			// open the template file
			$file = $path . "SpawnScripts/template.lua";
			if( !$f = fopen($file,'rb') ) {
				$line = "Cannot open existing filename: $file";
			}
			while(!feof($f)) {
				$line .= fgets($f);
			}
			fclose ($f); 
		}
		return $line;		
	}
	
	function loadItemScript($script) 
	{
		if( empty($script) || !preg_match("/ItemScripts\//i", $script) ) return "Must provide a script path/file!";
		if( !preg_match("/.lua/",$script) ) $script.=".lua";
		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;
		$file = $path . $script;
		if( file_exists($file) ) 
		{
			if( !$f = fopen($file,'rb') )
				$line = "Cannot open existing filename: $file";
			while(!feof($f))
				$line .= fgets($f);
			fclose ($f); 
		} 
		else 
		{
			$file = $path . "ItemScripts/template.lua";
			if( !$f = fopen($file,'rb') ) 
				$line = "Cannot open existing filename: $file";
			while(!feof($f))
				$line .= fgets($f);
			fclose ($f); 
		}
		$line = preg_replace("/(Script Name.*?\:) ItemScripts\/itemname.lua/i", "$1 ".$script, $line);
		return $line;		
	}

	function loadZoneScript($script) 
	{
		if( empty($script) ) return "Must provide a script path/file!";
		if( !preg_match("/.lua/",$script) ) $script.=".lua";

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;
	
		$file = $path . $script;
		if( file_exists($file) ) {
			if( !$f = fopen($file,'rb') ) {
				$line = "Cannot open existing filename: $file";
			}
			while(!feof($f)) {
				$line .= fgets($f);
			}
			fclose ($f); 
		} else {
			// open the template file
			$file = $path . "ZoneScripts/template.lua";
			if( !$f = fopen($file,'rb') ) {
				$line = "Cannot open existing filename: $file";
			}
			while(!feof($f)) {
				$line .= fgets($f);
			}
			fclose ($f); 
		}
		return $line;		
	}

	function saveZoneScript($data) 
	{
		if( empty($data) ) return "No script data to process - aborting!";

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH . $data['zoneScriptPath'];

		if( !preg_match("/.lua/", $data['zoneScriptName']) ) $data['zoneScriptName'].=".lua";
		$file = $path . $data['zoneScriptName'];
		// 2009.03.29 - removing ".bak" functionality
		//if( file_exists($file) ) {
			// update existing file
			//$filebak = preg_replace("/lua/","bak",$file);
			//copy($file,$filebak);
		//} 
// new file create
		if( !$f = fopen($file,'w') ) die("Cannot create filename: $file");
		// 2009.09.28 - removed stripslashes for Scat
		// 2010.01.18 - put stripslashes back in for Scat
		if (!fwrite($f, stripslashes($data['zoneScriptText']))) die("Cannot write to file ($file)");
		//if (!fwrite($f, $data['zoneScriptText'])) die("Cannot write to file ($file)");
		fclose($f);
	}

	/*function loadSpellScript($script) 
	{
		if( empty($script) ) return "Must provide a script path/file!";
		if( !preg_match("/.lua/",$script) ) $script.=".lua";

		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH;
	
		$file = $path . $script;

		if( file_exists($file) ) {
			if( !$f = fopen($file,'rb') ) {
				$line = "Cannot open existing filename: $file";
			}
			while(!feof($f)) {
				$line .= fgets($f);
			}
			fclose ($f); 
		} else {
			// create new script off template
			$line = $this->CreateLUATemplate($script);
		}
		return $line;		
	}*/
	


	//
	// Spawn Functions
	//
	function CompareMerchants() 
	{
		global $link;
		
		// fetch merchant data
		$sql = "select id,name,guild from `".PARSER_DB."`.raw_spawn_info where default_command rlike 'merchant' and id in (select spawn_id from `".PARSER_DB."`.raw_merchant_items) order by name;";
		if( !$result = $this->db->sql_query($sql) ) {
		} else {
			while($data = $this->db->sql_fetchrow($result)) {
				$row_data[] = $data;
			}
			//print_r($row_data);
			// pick first merchant
			?>
			<table width="100%">
				<tr>
					<td width="50%" valign="top">
						<select name="merchant1" onchange="dosub(this.options[this.selectedIndex].value)" style="width:300px;">
							<option>Pick First Merchant</option>
							<?php
							foreach($row_data as $key=>$val) {
								$sub_title = "";
								$sub_title = preg_replace("/<(.*)>/"," - &lt;$1&gt;",$val['guild']);
								$selected = ( $val['id'] == $_GET['m1'] ) ? " selected" : "";
								printf("<option value=\"%s?p=merchants&m1=%s\"%s>%s%s</option>\n", $link, $val['id'], $selected, $val['name'], $sub_title);
							}
							?>
						</select>
					<?php 
					if( isset($_GET['m1']) ) { 
						$this->GetMerchantsItemList($_GET['m1']);
					?>
					</td>
					<td width="50%" valign="top">
						<select name="merchant2" onchange="dosub(this.options[this.selectedIndex].value)" style="width:300px;">
							<option>Pick Second Merchant</option>
							<?php
							foreach($row_data as $key=>$val) {
								$sub_title = "";
								$sub_title = preg_replace("/<(.*)>/"," - &lt;$1&gt;",$val['guild']);
								$selected = ( $val['id'] == $_GET['m2'] ) ? " selected" : "";
								printf("<option value=\"%s?p=merchants&m1=%s&m2=%s\"%s>%s%s</option>\n", $link, $_GET['m1'], $val['id'], $selected, $val['name'], $sub_title);
							}
							?>
						</select>
						<?php 
						if( isset($_GET['m2']) ) { 
							$this->GetMerchantsItemList($_GET['m2']);
						} 
						?>					
					</td>
					<?php } else { ?>
					</td>
					<?php } ?>
				</tr>
			</table>
			<?php
			// pick second merchant
			?>
			<?php
		}
		
	}
	
	function Convert2GroundSpawn($id) 
	{
		$objectName=$_POST['orig_object'];
		$sql = sprintf("INSERT INTO spawn_ground (spawn_id, groundspawn_id) VALUES (%s, %s);", $id, $this->getNextPK("spawn_ground","groundspawn_id")); 
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		$this->logQuery($sql);
		$p = array("spawn_ground",$objectName,$sql);
		$this->dbEditorLog($p);

		// inserted new record, now clean house!
		$sql = sprintf("DELETE FROM spawn_npcs WHERE spawn_id = %s;", $id);
		$this->runQuery($sql);
		$this->logQuery($sql);
		$p = array("spawn_npcs",$objectName,$sql);
		$this->dbEditorLog($p);

		$sql = sprintf("DELETE FROM spawn_objects WHERE spawn_id = %s;", $id);
		$this->runQuery($sql);
		$this->logQuery($sql);
		$p = array("spawn_objects",$objectName,$sql);
		$this->dbEditorLog($p);

		$sql = sprintf("DELETE FROM npc_appearance WHERE spawn_id = %s;", $id);
		$this->runQuery($sql);
		$this->logQuery($sql);
		$p = array("npc_appearance",$objectName,$sql);
		$this->dbEditorLog($p);
		
		$sql = sprintf("UPDATE spawn SET attackable = 0 WHERE id = %s;", $id);
		$this->runQuery($sql);
		$this->logQuery($sql);
		$p = array("spawn",$objectName,$sql);
		$this->dbEditorLog($p);
		
		printf("<p>Spawn moved! Click <a href=\"spawns.php?z=%d&t=ground\" target=\"_self\">here</a> to go to Ground Spawns, or select another spawn.</p>", $_GET['z']);

	}
	
	function GetPets()
	{
		if( isset($_GET['t']) ) 
			$type = sprintf(" AND guild RLIKE '%s'", $_GET['t']);
			
		$sql = sprintf("SELECT 
											populate_spawn_id as id,
											`version`,
											rsi.name as spawn_name,
											guild as sub_title,
											rsi.model_type,
											a.name AS appearance,
											rsi.min_level,
											rsi.max_level,
											difficulty 
										FROM
											`".PARSER_DB."`.raw_spawn_info rsi 
											JOIN `".RAW_DB."`.spawn s 
												ON rsi.populate_spawn_id = s.id 
											JOIN `".RAW_DB."`.appearances a 
												ON rsi.model_type = a.appearance_id 
										WHERE guild NOT RLIKE 'Personae' 
											AND a.name NOT RLIKE '/pc/'
											AND ((
												rsi.spawn_type = 6 
												AND `version` < 1142
											) 
											OR (
												rsi.spawn_type = 10 
												AND `version` >= 1142
											)) 
											%s
										ORDER BY rsi.model_type, rsi.min_level", 
										$type);
		//echo $sql;
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		while( $row = $this->db->sql_fetchrow($result) )
		{
			$data[] = $row;
		}
		
		return $data;
	}
	
	function existsMerchantItem( $id )
	{
		$sql = sprintf("select distinct merchant_id from merchants where item_id = %s", $id);
		$sql = sprintf("select distinct merchant_id from merchants m, merchant_inventory mi where m.inventory_id = mi.inventory_id and mi.item_id = %s", $id);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		
		while( $data = $this->db->sql_fetchrow( $result ) )
		{
			if( empty($list) )
			{
				$list = $data['merchant_id'];
			}
			else
			{
				$list .= ", " . $data['merchant_id'];
			}
		}
		return $list;
	}
	
	function PurgeLiveZone($zone_id)
	{
		$query_array = array();
		
		if( $zone_id ) 
		{
			// this is an array of all tables that reference spawn_id
			$spawn_cleanup_array = array(
																 'spawn_npcs', 
																 'spawn_objects', 
																 'spawn_signs', 
																 'spawn_widgets', 
																 'spawn_ground', 
																 'npc_appearance', 
																 'npc_appearance_equip', 
																 'spawn_loot', 
																 'spawn_scripts',
																 'quests',
																 'instance_spawns_removed'
																 );
			
			// Step 1: Gather up all spawn location data pertaining to this zones spawns
			$query = sprintf("SELECT sln.id FROM `".LIVE_DB."`.spawn_location_name sln LEFT JOIN `".LIVE_DB."`.spawn_location_placement slp ON sln.id = slp.spawn_location_id WHERE zone_id = %s;", $zone_id);
			//printf("<p>%s</p>", $query);
			if( !$results = $this->db->sql_query($query) ) 
				$this->DBError($query);
				
			while( $data = $this->db->sql_fetchrow($result) )
				$placement_id_array[] = $data['id'];
			
			
			// Step 2: Gather spawn ID's to delete from zone
			$query = sprintf("SELECT id FROM " . LIVE_DB . ".spawn WHERE id LIKE '%s____';", $zone_id);
			//printf("%s<br />", $query);
			if( !$results = $this->db->sql_query($query) ) 
				$this->DBError($query);
				
			while( $data = $this->db->sql_fetchrow($result) )
				$spawn_id_array[] = $data['id'];


			// Step 3: Delete all spawn location data collected in step 1
			if( is_array($placement_id_array) )
			{
				$query_array[] = sprintf("DELETE FROM " . LIVE_DB . ".spawn_placement_name WHERE id IN (%s);", implode(", ", $placement_id_array));
				$query_array[] = sprintf("DELETE FROM " . LIVE_DB . ".spawn_placement_entry WHERE spawn_location_id IN (%s);", implode(", ", $placement_id_array));
				$query_array[] = sprintf("DELETE FROM " . LIVE_DB . ".spawn_placement_placement WHERE spawn_location_id IN (%s);", implode(", ", $placement_id_array));
				$query_array[] = sprintf("DELETE FROM " . LIVE_DB . ".spawn_placement_group WHERE placement_id IN (%s);", implode(", ", $placement_id_array));
			}


			// Step 4: Delete all spawn data collected in step 2
			if( is_array($spawn_id_array) )
			{
				$query_array[] = sprintf("DELETE FROM " . LIVE_DB . ".spawn WHERE id IN (%s);", implode(", ", $spawn_id_array));
				
				foreach($spawn_cleanup_array as $table)
					$query_array[] = sprintf("DELETE FROM " . LIVE_DB . ".%s WHERE spawn_id IN (%s);", $table, implode(", ", $spawn_id_array));
			}
		
		}
		else if( $zone_id === 0 )
		{
			die("Cannot purge nothing, silly!<br>");
		}
		
		// commit SQL
		if( is_array($query_array) )
		{
			$count = 0;
			
			foreach($query_array as $Query)
			{
				/*if( !$this->db->sql_query($Query) ) 
				{
					$error = $this->db->sql_error();
					$message = "<p align=center>".$error['message']."<br>"."Error Code: ".$error['code']."</p><p>Rows: ".$this->db->sql_affectedrows()."</p><p>".$Query."</p>";
					die($message);
				}*/
				printf("<p>%s</p>", $Query);
				$count++;
			}
			
		}
		
		if( $count > 0 )
			printf("<p>Zone %s, Purged! Starting migration...</p>", $zone_id);
			
		return;
	}

	// return true if zone has any spawns or placements
	function isPopulated($database, $id)
	{
		$ret = 0;
		
		$sql = sprintf("SELECT COUNT(id) as cnt FROM %s.spawn WHERE id LIKE '%s____'", $database, $id);
		
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
			
		$data = $this->db->sql_fetchrow($result);
		
		if( intval($data['cnt']) > 0 )
			$ret = 1;
			
		if( !$ret )
		{
			$sql = sprintf("SELECT COUNT(id) as cnt FROM %s.spawn_location_placement WHERE zone_id = %s", $database, $id);
			
			if( !$result = $this->db->sql_query($sql) )
				$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
				
			$data = $this->db->sql_fetchrow($result);
			
			if( intval($data['cnt']) > 0 )
				$ret = 1;
		}
		
		return $ret;
	}
	
	function processCopyZone() 
	{
		$query_array = array();
		$from = isset($_GET['from']) ? $_GET['from'] : 0;
		$to 	= isset($_GET['to']) ? $_GET['to'] : 0;
		
		if( $from > 0 && $to > 0 )
		{
			// purge destination zone
			$this->PurgeLiveZone($_GET['to']);
			
			// this is an array of all tables that reference spawn_id
			$spawn_table_array = array(
																 'spawn_npcs', 
																 'spawn_objects', 
																 'spawn_signs', 
																 'spawn_widgets', 
																 'spawn_ground', 
																 'npc_appearance', 
																 'npc_appearance_equip', 
																 'spawn_loot', 
																 'spawn_scripts',
																 'quests',
																 'instance_spawns_removed'
																 );
			
			// TODO: support copying from one zone ID to another (instance building?) For now, just from->to must equal
			
			$exclude_array = array("processed");
			$field_array = $this->GetFieldNames("spawn", $exclude_array);
			
			$query_array[] = sprintf("INSERT INTO `".LIVE_DB."`.spawn (%s) SELECT %s FROM `".ACTIVE_DB."`.spawn WHERE id LIKE '%s____'", implode(", ", $field_array), implode(", ", $field_array), $_GET['from']);
			
			foreach($spawn_table_array as $table)
			{
				$exclude_array = array("id", "processed");
				$field_array = $this->GetFieldNames($table, $exclude_array);
				$query_array[] = sprintf("INSERT INTO `".LIVE_DB."`.%s (%s) SELECT %s FROM `".ACTIVE_DB."`.%s WHERE spawn_id LIKE '%s____'", $table, implode(", ", $field_array), implode(", ", $field_array), $table, $_GET['from']);
			}
			
			// commit SQL
			if( is_array($query_array) )
			{
				$count = 0;
				foreach($query_array as $Query)
				{
					/*if( !$this->db->sql_query($Query) ) 
					{
						$error = $this->db->sql_error();
						$message = "<p align=center>".$error['message']."<br>"."Error Code: ".$error['code']."</p><p>Rows: ".$this->db->sql_affectedrows()."</p><p>".$Query."</p>";
						die($message);
					}*/
					
					$count++;
					printf("<p>%s</p>", $Query);
				}
			}
		}
		die("Zone Copy complete!");
	}

	function eradicateSpawns() 
	{
		foreach($_POST as $key=>$val) {
			$chkKey = explode("|",$key);
			if( $chkKey[0]=="delete" ) {
				$sql=sprintf("delete from spawn where id = %d; ",$val);
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
				$p = array("spawn","remove spawn",$sql);
				$this->dbEditorLog($p);
			}
		}
	}
	
	function eradicateSpawnGroups() 
	{
		foreach($_POST as $key=>$val) 
		{
			$chkKey = explode("|",$key);
			if( $chkKey[0]=="delete" ) 
			{
				$sql=sprintf("delete from spawn_location_name where id = %d; ",$chkKey[1]);
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

				$p = array("spawn_location_name","remove dupe",$sql);
				$this->dbEditorLog($p);

				$sql=sprintf("delete from spawn_location_entry where spawn_location_id = %d; ",$chkKey[1]);
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

				$p = array("spawn_location_entry","remove dupe",$sql);
				$this->dbEditorLog($p);

				$sql=sprintf("delete from spawn_location_placement where spawn_location_id = %d; ",$chkKey[1]);
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

				$p = array("spawn_location_placement","remove dupe",$sql);
				$this->dbEditorLog($p);
			}
		}
	}
	
	function SpawnAssociates()
	{
		if( isset($_GET['z']) )
		{
			if( empty($_GET['spawn']) )
			{
				// First show the list of Spawn that are in groups in a given zone
				$sql = sprintf("SELECT 
													slg.group_id, slg.placement_id, slp.x, slp.y, slp.z 
												FROM
													spawn_location_group slg, spawn_location_placement slp 
												WHERE slg.placement_id = slp.id AND zone_id = %d
												ORDER BY  slp.x, slp.z, group_id", $_GET['z']);
				//printf("%s<br>", $sql);
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
					
				//echo $this->db->sql_numrows($result);
				
				while($data = $this->db->sql_fetchrow($result))
				{
					$spawn_group_data[$data['group_id']][] = $data;
				}
				print_r($spawn_group_data);

				/*Array
				(
						[7] => Array
								(
										[0] => Array
												(
														[group_id] => 7
														[placement_id] => 13363
														[x] => -396.65
														[y] => 90.41
														[z] => -618.68
												)
				
										[1] => Array
												(
														[group_id] => 7
														[placement_id] => 13364
														[x] => -386.91
														[y] => 91.14
														[z] => -622.25
												)
				
								)*/
				
				print('<br /><table cellpadding="4" cellspacing="2" border="1"><tr><td>Count</td><td>x</td><td>y</td><td>z</td><td>group_id</td><td>placement_id</td><td>&nbsp;</td></tr>');
				if( is_array($spawn_group_data) )
				{
					$i = 0;
					foreach($spawn_group_data as $group)
					{
						$x = $group[0]['x'];
						printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>&nbsp;<a href="?z=%d&t=%s&s=clean&placement=%lu">List</a>&nbsp;</td><tr>', -1, $group['x'], $group['y'], $group['z'], $group['group_id'], $group['placement_id'], $_GET['z'], $_GET['t'], $group['placement_id']);
						$i++;
					}
					printf('<tr><td colspan="4">%d records</td></tr>', $i);
				}
				print('</table>');
			}
		}
		
	}
	
	function SpawnCleanup() {

		if( isset($_GET['zone']) )
		{
			if( empty($_GET['spawn']) )
			{
				// First show the list of Spawn (types) in a zone with a count of how many of that spawn_id there are
				$this->SQLQuery = sprintf("SELECT count(sle.spawn_id) as num_spawns, s.id, s.name
												FROM `".ACTIVE_DB."`.spawn s 
												JOIN `".ACTIVE_DB."`.spawn_%s st ON s.id = st.spawn_id
												JOIN `".ACTIVE_DB."`.spawn_location_entry sle ON s.id = sle.spawn_id 
												JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON sle.spawn_location_id = slp.spawn_location_id
												WHERE 
													slp.zone_id = %d AND
													spawnpercentage > 0 AND
													slp.id NOT IN (SELECT DISTINCT placement_id FROM `".ACTIVE_DB."`.spawn_location_group)
												GROUP BY sle.spawn_id HAVING count(sle.spawn_id) > 1
												ORDER BY s.name, s.id", $_GET['type'], $_GET['zone']);
														
				//printf("%s<br>", $sql);
				$results = $this->RunQueryMulti();
					
				foreach($results as $data) {
					$spawn_data[] = $data;
					$clean_all_count += $data['num_spawns'];
				}

				print('<br /><table cellpadding="4" cellspacing="2" border="1"><tr><td>Count</td><td>Spawn Name</td><td>SpawnID</td><td>&nbsp;</td></tr>');
				// todo: add List All option that will de-dupe entire zone
				if( is_array($spawn_data) )
				{
					$i = 0;
					foreach($spawn_data as $spawns) {
						printf('<tr><td colspan="4"><a name="%s"></a></td></tr><tr><td>%d</td><td>%s</td><td>%s</td><td>&nbsp;<a href="?zone=%d&type=%s&id=clean&num=%s&spawn=%s">List</a>&nbsp;</td><tr>', $i, $spawns['num_spawns'], $spawns['name'], $spawns['id'], $_GET['zone'], $_GET['type'], $i, $spawns['id']);
						$i++;
						$spawn_count += $spawns['num_spawns'];
					}
					printf('<tr><td colspan="4">%d records, %d spawns</td></tr>', $i, $spawn_count);
				}
				print('</table>');
			}
			else
			{
				
				// Show the single spawn_id's many spawn locations, determine if any are $distance_offset within each other (dupes)
				printf('<br /><a href="spawns.php?zone=%d&type=%s&id=clean#%s">Back</a><br />', $_GET['zone'], $_GET['type'], $_GET['num']);
				
				
				if( isset($_POST['cmd']) )
				{
					// if you choose to delete the checked spawns, do so here.
					foreach($_POST as $key=>$val)
					{
						$tmp = explode("|", $key);
						if( $tmp[0] == "del" )
						{
							if( empty($spawn_location_ids) )
								$spawn_location_ids .= $val;
							else
								$spawn_location_ids .= ", " . $val;
						}
					}
					$this->SQLQuery = sprintf("DELETE FROM `".ACTIVE_DB."`.spawn_location_name WHERE id IN (%s);", $spawn_location_ids);
					$this->RunQuery();
					return; // done
				}
				
				$this->SQLQuery = sprintf("SELECT slp.*, s.name 
																		FROM `".ACTIVE_DB."`.spawn_location_entry sle
																		LEFT JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON sle.spawn_location_id = slp.spawn_location_id
																		JOIN `".ACTIVE_DB."`.spawn s ON sle.spawn_id = s.id
																		WHERE
																			spawn_id = %lu
																		ORDER BY x, y, z, spawn_id", $_GET['spawn']);
				
				$results = $this->RunQueryMulti();

				foreach($results as $data) {
					$placement_data[] = $data;
				}

				$to_delete	= 0;
				$to_keep 		= 0;
				
				if( is_array($placement_data) )
				{
					// read distance_offset value and use it, or default to 2 meters
					$distance_offset = ( isset($_POST['distance_offset']) && $_POST['distance_offset'] > 0 ) ? $distance_offset = $_POST['distance_offset'] : $distance_offset = 3;

					printf('<p><strong>Spawn: %s</strong>', $placement_data[0]['name']);
					print('<form method="post" name="SpawnCleanup">');
					print('<table width="600" cellpadding="4" cellspacing="2" border="0">');
					print('<tr><td colspan="7"><i>Distance Offset</i> is how close together the spawns are before they are considered DUPLICATES and will be purged. <b>Use catiously!</b></td></tr>');
					print('<tr><td colspan="7">');
					printf('Distance Offset: <input type="text" name="distance_offset" value="%d" style="width: 30px; font-size: 10px" />&nbsp;&nbsp;', $distance_offset);
					print('<input type="submit" name="offset" value="Re-Calc New Offset" style="width: 120px; font-size: 10px" />&nbsp;&nbsp;');
					print('<input type="submit" name="cmd" value="Delete Duplicates" style="width: 100px; font-size: 10px" />');
					print('</td></tr>');
					print('</table>');
		
					$i = 0;
					$pIndex = 0;
					
					foreach($placement_data as $placements) {
						
						$x_low = round($placements['x']) - $distance_offset;
						$x_high = round($placements['x']) + $distance_offset;
						$z_low = round($placements['z']) - $distance_offset;
						$z_high = round($placements['z']) + $distance_offset;
		
						$this->SQLQuery = sprintf("SELECT * 
																				FROM `".ACTIVE_DB."`.spawn_location_placement slp, `".ACTIVE_DB."`.spawn_location_entry sle
																				WHERE
																					slp.spawn_location_id = sle.spawn_location_id AND
																					sle.spawn_id = %lu AND
																					(slp.x BETWEEN %d AND %d) AND
																					(slp.z BETWEEN %d AND %d) 
																					%s
																					ORDER BY slp.spawn_location_id", $_GET['spawn'], 
																					$x_low, $x_high, $z_low, $z_high, ( $exclude_placements ) ? sprintf(" AND slp.spawn_location_id NOT IN (%s)", $exclude_placements) : "" );
						
						$results2 = $this->RunQueryMulti();
						
						$num_rows = count($results2);
						$checked = ( $num_rows > 1 ) ? " checked" : "";
						
						if( $num_rows > 1 )
						{
							printf('<table width="600" cellpadding="4" cellspacing="2" border="1"><tr><td>id</td><td>location_id</td><td>X</td><td>Y</td><td>Z</td><td>grid_id</td><td>&nbsp;</td></tr>', $spawn_name);
							foreach($results2 as $data2)
							{
								//printf("<br>rows: %s, i = %s, placement: %s", $num_rows, $i, $data2['spawn_location_id']);
								if( $i > 0 ) {
									$checked = " checked";
									$to_delete++;
									
									if( empty($exclude_placements) )
										$exclude_placements = $data2['spawn_location_id'];
									else
										$exclude_placements .= sprintf(", %lu", $data2['spawn_location_id']);
										
								} else {
									$checked = "";
									$to_keep++;
								}
								printf('<tr align="right"><td>%lu</td><td>%lu</td><td>%f</td><td>%f</td><td>%f</td><td>%lu</td><td>&nbsp;<input type="checkbox" name="del|%lu" value="%lu"%s>&nbsp;</td></tr>', 
											 $data2['id'], $data2['spawn_location_id'], $data2['x'], $data2['y'], $data2['z'], $data2['grid_id'], $data2['id'], $data2['spawn_location_id'], $checked);
								$i++;
								$total++;
							}
							printf('<tr><td colspan="7">%d records</td></tr>', $i);
							print('</table><br />&nbsp;');
						}
						$i = 0;
						unset($placement_data[$pIndex]);
						$pIndex++;
					}
					printf('<table width="600" cellpadding="4" cellspacing="2" border="0">');
					printf('<tr><td>Found %lu entries, keeping %lu spawn points, deleting %lu spawn points</td></tr>', $total, $to_keep, $to_delete);
					printf('<tr><td><input type="submit" name="cmd" value="Delete Duplicates" /></td></tr>');
					print('</table></form>');
				}
			}
		}
	
	}

	function SpawnCleanup_1()
	{
		if( isset($_POST['sgDelete']) && $this->mLev>=200 ) 
			$this->eradicateSpawnGroups();

		?>
		<form method="post" name="SpawnCleanup">
		<table>
			<tr>
				<td><br />&nbsp;
					Partial Spawn Name:&nbsp;<input type="text" name="name" value="<?= $_POST['name'] ?>" style="font-size:9px; width:300px;" tabindex="0" />&nbsp;
					<input type="submit" name="cmd" value="Search" style="font-size:10px; width:70px;" />
				</td>
			</tr>
		</table>
		</form>
		<?php
		
		if( $_POST['cmd'] == "Search" )
		{
			$sql = sprintf("SELECT id FROM spawn WHERE name RLIKE '%s' AND id IN (SELECT spawn_id FROM spawn_location_entry sle, spawn_location_placement slp WHERE sle.spawn_location_id = slp.spawn_location_id AND zone_id = %d)", $_POST['name'], $_GET['z']);
			if( !$result = $this->db->sql_query($sql) )
				$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
			while($data=$this->db->sql_fetchrow($result)) 
			{
				$spawn_id_list .= ( empty($spawn_id_list) ) ? $data['id'] : ", ".$data['id'];
			}
			// print($spawn_id_list);
			
			$sql = sprintf("SELECT slp.spawn_location_id, spawnpercentage, name, sub_title, model_type, gender, initial_state, action_state, visual_state, round(x) as x, round(y) as y, round(z) as z, min_level, max_level, enc_level
												FROM 
													spawn_location_placement slp, 
													spawn_location_entry sle, 
													spawn s, 
													spawn_%s s1
												WHERE 
													s.id IN (%s)
													AND slp.spawn_location_id = sle.spawn_location_id 
													AND sle.spawn_id = s.id 
													AND s.id = s1.spawn_id 
													AND zone_id = %d
													AND (
															SELECT count(*) 
															FROM spawn_location_placement 
															WHERE 
																zone_id = %d 
																AND round(x) = round(slp.x) 
																AND round(z) = round(slp.z)
															) > 1 
												ORDER BY x,z,s.id;",$_GET['t'], $spawn_id_list, $_GET['z'],$_GET['z']);
			// echo $sql; exit;
			set_time_limit(900);
			if( !$result = $this->db->sql_query($sql) )
				$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
				
			print("<table cellpadding='5'><form method='post'>");
			while($data=$this->db->sql_fetchrow($result)) {
	
				if( $currX == $data['x'] && $currZ == $data['z'] && $currSpawnID == $data['spawn_id'] ) {
					print("<tr>");
					printf("<td>%d</td>",$data['spawn_location_id']);
					printf("<td>%d</td>",$data['spawnpercentage']);
					printf("<td>%s (%d/%d/%d)</td>",$data['name'],$data['min_level'],$data['max_level'],$data['enc_level']);
					printf("<td>%d</td>",$data['spawn_id']);
					printf("<td>%d %d %d</td>",$data['x'],$data['y'],$data['z']);
					printf("<td%s>%d</td>",( $currRace==$data['model_type'] ) ? "" : " bgcolor='#00ccff'",$data['model_type']);
					printf("<td>%d</td>",$data['gender']);
					printf("<td>%d</td>",$data['initial_state']);
					printf("<td>%d</td>",$data['visual_state']);
					printf("<td>%d</td>",$data['action_state']);
					if( $this->mLev >= 200 ) printf("<td><input type='checkbox' name='delete|%d' value='%d'%s></td>",$data['spawn_location_id'], $data['id'], ( $currRace==$data['model_type'] ) ? " checked" : "");
					print("</tr>\n");
					// printf("Dupe/PH: [%s (%d)] [X/Y: %d/%d] [model_type: %s]<br>",$data['name'],$data['id'],$data['x'],$data['z'],$data['model_type']);
				} else {
					$currX=$data['x'];
					$currZ=$data['z'];
					$currRace=$data['model_type'];
					$currSpawnID=$data['spawn_id'];
					print("<tr height='8'>");
					print("<td>&nbsp;</td>");
					print("</tr>\n");
					print("<tr>");
					print("<td><strong>Spawngroup</strong></td>");
					print("<td><strong>Chance</strong></td>");
					print("<td><strong>Name (min/max/diff)</strong></td>");
					print("<td><strong>Spawn_ID</strong></td>");
					print("<td><strong>X / Z</strong></td>");
					print("<td><strong>Race</strong></td>");
					print("<td><strong>Sex</strong></td>");
					print("<td><strong>Initial</strong></td>");
					print("<td><strong>Visual</strong></td>");
					print("<td><strong>Action</strong></td>");
					if( $this->mLev >= 200 ) print("<td><strong>Delete</strong></td>");
					print("</tr>\n");
					print("<tr>");
					printf("<td>%d</td>",$data['spawn_location_id']);
					printf("<td>%d</td>",$data['spawnpercentage']);
					printf("<td>%s (%d/%d/%d)</td>",$data['name'],$data['min_level'],$data['max_level'],$data['enc_level']);
					printf("<td>%d</td>",$data['spawn_id']);
					printf("<td>%d %d %d</td>",$data['x'],$data['y'],$data['z']);
					printf("<td>%d</td>",$data['model_type']);
					printf("<td>%d</td>",$data['gender']);
					printf("<td>%d</td>",$data['initial_state']);
					printf("<td>%d</td>",$data['visual_state']);
					printf("<td>%d</td>",$data['action_state']);
					if( $this->mLev >= 200 ) printf("<td><input type='checkbox' name='delete|%d' value='%d'></td>",$data['spawn_location_id'],$data['id']);
					print("</tr>\n");
					// printf("<br />New Group: [%s (%d)] [X/Y: %d/%d] [model_type: %s]<br>",$data['name'],$data['id'],$data['x'],$data['z'],$data['model_type']);
				}
	
			}
			if( $this->mLev >= 200 ) print("<tr><td colspan='11' align='center'><input type='submit' name='sgDelete' value='Delete Spawngroup(s)'></td></tr>\n");
			printf('<input type="hidden" name="name" value="%s" />', $_POST['name']);
			print("</form></table>");
			
		}
	
	}
	
	function processPlaceholders() 
	{
		if( isset($_POST['sDelete']) && $this->mLev>=200 ) {
			//$this->eradicateSpawns();
		}
		if( isset($_POST['sgDelete']) && $this->mLev>=200 ) {
			$this->eradicateSpawnGroups();
		}
		
		$sql = sprintf("select slp.spawn_location_id, spawnpercentage, name, sub_title, model_type, gender, initial_state, action_state, visual_state, round(x) as x, round(y) as y, round(z) as z, min_level, max_level, enc_level
											from spawn_location_placement slp, spawn_location_entry sle, spawn s, spawn_%s s1
											where 
												slp.spawn_location_id = sle.spawn_location_id 
												and sle.spawn_id = s.id 
												and s.id = s1.spawn_id 
												and zone_id = %d
												and (select count(*) from spawn_location_placement where zone_id = %d and round(x) = round(slp.x) and round(z) = round(slp.z)) > 1 
											order by x,z,s.id;",$_GET['t'], $_GET['z'],$_GET['z']);
		// echo $sql; exit;
		set_time_limit(900);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
			
		print("<table cellpadding='5'><form method='post'>");
		while($data=$this->db->sql_fetchrow($result)) {

			if( $currX == $data['x'] && $currZ == $data['z'] && $currSpawnID == $data['spawn_id'] ) {
				print("<tr>");
				printf("<td>%d</td>",$data['spawn_location_id']);
				printf("<td>%d</td>",$data['spawnpercentage']);
				printf("<td>%s (%d/%d/%d)</td>",$data['name'],$data['min_level'],$data['max_level'],$data['enc_level']);
				printf("<td>%d</td>",$data['spawn_id']);
				printf("<td>%d %d %d</td>",$data['x'],$data['y'],$data['z']);
				printf("<td%s>%d</td>",( $currRace==$data['model_type'] ) ? "" : " bgcolor='#00ccff'",$data['model_type']);
				printf("<td>%d</td>",$data['gender']);
				printf("<td>%d</td>",$data['initial_state']);
				printf("<td>%d</td>",$data['visual_state']);
				printf("<td>%d</td>",$data['action_state']);
				if( $this->mLev >= 200 ) printf("<td><input type='checkbox' name='delete|%d' value='%d'%s></td>",$data['spawn_location_id'], $data['id'], ( $currRace==$data['model_type'] ) ? " checked" : "");
				print("</tr>\n");
				// printf("Dupe/PH: [%s (%d)] [X/Y: %d/%d] [model_type: %s]<br>",$data['name'],$data['id'],$data['x'],$data['z'],$data['model_type']);
			} else {
				$currX=$data['x'];
				$currZ=$data['z'];
				$currRace=$data['model_type'];
				$currSpawnID=$data['spawn_id'];
				print("<tr height='8'>");
				print("<td>&nbsp;</td>");
				print("</tr>\n");
				print("<tr>");
				print("<td><strong>Spawngroup</strong></td>");
				print("<td><strong>Chance</strong></td>");
				print("<td><strong>Name (min/max/diff)</strong></td>");
				print("<td><strong>Spawn_ID</strong></td>");
				print("<td><strong>X / Z</strong></td>");
				print("<td><strong>Race</strong></td>");
				print("<td><strong>Sex</strong></td>");
				print("<td><strong>Initial</strong></td>");
				print("<td><strong>Visual</strong></td>");
				print("<td><strong>Action</strong></td>");
				if( $this->mLev >= 200 ) print("<td><strong>Delete</strong></td>");
				print("</tr>\n");
				print("<tr>");
				printf("<td>%d</td>",$data['spawn_location_id']);
				printf("<td>%d</td>",$data['spawnpercentage']);
				printf("<td>%s (%d/%d/%d)</td>",$data['name'],$data['min_level'],$data['max_level'],$data['enc_level']);
				printf("<td>%d</td>",$data['spawn_id']);
				printf("<td>%d %d %d</td>",$data['x'],$data['y'],$data['z']);
				printf("<td>%d</td>",$data['model_type']);
				printf("<td>%d</td>",$data['gender']);
				printf("<td>%d</td>",$data['initial_state']);
				printf("<td>%d</td>",$data['visual_state']);
				printf("<td>%d</td>",$data['action_state']);
				if( $this->mLev >= 200 ) printf("<td><input type='checkbox' name='delete|%d' value='%d'></td>",$data['spawn_location_id'],$data['id']);
				print("</tr>\n");
				// printf("<br />New Group: [%s (%d)] [X/Y: %d/%d] [model_type: %s]<br>",$data['name'],$data['id'],$data['x'],$data['z'],$data['model_type']);
			}

		}
		if( $this->mLev >= 200 ) print("<tr><td colspan='11' align='center'><input type='submit' name='sDelete' value='Delete Spawn(s)' disabled>&nbsp;<input type='submit' name='sgDelete' value='Delete Spawngroup(s)'></td></tr>\n");
		print("</form></table>");
	}
	
	function processMerchant($id) 
	{
		if( $id=="all" ) {
			if( isset($_GET['s']) ) {
				$sql=sprintf("select i.id as item_id,s.id as spawn_id,rmi.price
												from `".PARSER_DB."`.`raw_merchant_items` rmi
												join `".PARSER_DB."`.`raw_spawn_info` rsi on rmi.spawn_id = rsi.id
												join `" . ACTIVE_DB . "`.`spawn` s on rsi.name = s.name
												join `" . ACTIVE_DB . "`.`items` i on rmi.item_name = i.name
												where rmi.spawn_id = %s
												group by i.name;",$_GET['s']); //echo $sql;
				if( !$result = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

				$merchant_id = 1;
				$sql="select max(merchant_id) as nextId from `" . ACTIVE_DB . "`.`merchants`;";
				$data2=$this->db->sql_fetchrow($this->db->sql_query($sql));
				$merchant_id = $data2['nextId']+1;
	
				while($data=$this->db->sql_fetchrow($result)) {
	
					$spawn_id = $data['spawn_id']; // for use outside this loop
					
					$sql2 = sprintf("insert into `" . ACTIVE_DB . "`.`merchants` (merchant_id, item_id) values ('%s','%s');",$merchant_id,$data['item_id']);
					// printf("%s<br />",$insert);
					if( !$result1 = $this->db->sql_query($sql2) )
						$this->DBError($sql2);
						
					// update sell_price in items table
					$sql=sprintf("update `" . ACTIVE_DB . "`.`items` set sell_price = '%s' where id = '%s';",$data['price'],$data['item_id']);
					//printf("%s<br />",$sql);
					if( !$result1 = $this->db->sql_query($sql2) )
						$this->DBError($sql2);
				}
				// after merchant list is built, update spawn.merchant_id
				$sql=sprintf("update `" . ACTIVE_DB . "`.`spawn` set merchant_id = '%s' where id = '%s';",$merchant_id,$spawn_id);
				//printf("%s<br />",$sql);
				if( !$result1 = $this->db->sql_query($sql) )
					$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
	
			}
		} else {
			// not doing individual items at this time
		}
	}


	//
	// Spell Functions
	//

	function deletePlayerSpellSet($id) 
	{
		$this->db->sql_query("delete from `".ACTIVE_DB."`.character_spells where char_id = ".$id);
	}
	
	function swapPlayerSpellSet() 
	{
		$id = $_POST['char_id'];
		$this->deletePlayerSpellSet($id);

		$playerClassID = $_POST['class_id'] ?? 0;
		$classList = "";
		switch($playerClassID) {
			case 0: // commoner

			case 1: $classList = "1"; break;
			case 2: $classList = "1,2"; break;
			case 3: $classList = "1,2,3"; break;
			case 4: $classList = "1,2,4"; break;
			case 5: $classList = "1,5"; break;
			case 6: $classList = "1,5,6"; break;
			case 7: $classList = "1,5,7"; break;
			case 8: $classList = "1,8"; break;
			case 9: $classList = "1,8,9"; break;
			case 10: $classList = "1,8,10"; break;
			
			case 11: $classList = "11"; break;
			case 12: $classList = "11,12"; break;
			case 13: $classList = "11,12,13"; break;
			case 14: $classList = "11,12,14"; break;
			case 15: $classList = "11,15"; break;
			case 16: $classList = "11,15,16"; break;
			case 17: $classList = "11,15,17"; break;
			case 18: $classList = "11,18"; break;
			case 19: $classList = "11,18,19"; break;
			case 20: $classList = "11,18,20"; break;
			
			case 21: $classList = "21"; break;
			case 22: $classList = "21,22"; break;
			case 23: $classList = "21,22,23"; break;
			case 24: $classList = "21,22,24"; break;
			case 25: $classList = "21,25"; break;
			case 26: $classList = "21,25,26"; break;
			case 27: $classList = "21,25,27"; break;
			case 28: $classList = "21,28"; break;
			case 29: $classList = "21,28,29"; break;
			case 30: $classList = "21,28,30"; break;
			
			case 31: $classList = "31"; break;
			case 32: $classList = "31,32"; break;
			case 33: $classList = "31,32,33"; break;
			case 34: $classList = "31,32,34"; break;
			case 35: $classList = "31,35"; break;
			case 36: $classList = "31,35,36"; break;
			case 37: $classList = "31,35,37"; break;
			case 38: $classList = "31,38"; break;
			case 39: $classList = "31,38,39"; break;
			case 40: $classList = "31,38,40"; break;			
		}
		$setLive = ( isset($_POST['is-live']) ) ? " and is_live = 1" : "";
		$sql=sprintf("SELECT max(tier) as tier,s.id,s.name,spell_book_type,sc.level FROM `%s`.spells s JOIN `%s`.spell_tiers st on s.id = st.spell_id LEFT JOIN `%s`.spell_classes sc ON s.id = sc.spell_id WHERE sc.adventure_class_id in (0,%s) %s group by `name` order by s.spell_book_type,sc.level,s.id;",
			ACTIVE_DB, ACTIVE_DB, ACTIVE_DB, $classList,$setLive); // echo $sql;
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		$i=0;
		$bulkLoad="insert into `".ACTIVE_DB."`.character_spells (char_id,spell_id,tier,knowledge_slot) values ";
		$currType = "";
		while($data=$this->db->sql_fetchrow($result)) {
			if( $currType != $data['spell_book_type'] ) {
				$currType = $data['spell_book_type'];
				$i = ( $data['spell_book_type']==2 ) ? 8 : 0;
			} else {
				$i++;
			}
			$bulkLoad.=sprintf("(%d,%d,%d,%d)",$id,$data['id'],$data['tier'],$i);
		}
		$bulkLoad=preg_replace("/\)\(/","),(",$bulkLoad).";"; // echo $bulkLoad; exit;
		if( !$result = $this->db->sql_query($bulkLoad) )
			$this->DBError($bulkLoad);
	}
		
	function getClassSpells($id) 
	{
		if( $id==0 ) $spellOptions = "<option value=\"0\">---</option>";
		$sql = sprintf("select s.id, s.name from spells s, spell_classes sc where s.id = sc.spell_id and sc.adventure_class_id = %d order by s.name", $_GET['c']);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		while($data=$this->db->sql_fetchrow($result)) {
			$selected=( $id == $data['id'] ) ? " selected" : "";
			$spellOptions .= sprintf("<option value=\"%s\"$selected>%s (%lu)</option>\n", $data['id'], $data['name'], $data['id']);
		}
		return $spellOptions;
	}
	
	function getSpellScriptListByClass($id, $script) 
	{

		$not_found = true; // always assume folder is not yet created
		
		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH . "Spells/";

		// clean up $script value
		$tmp = explode("/", $script);
		$clean_script = end($tmp);

		// get full class path
		$class_path .= $this->getSpellClassPath($id);
		$fullpath = $path.$class_path; // preserve $path for use during mkdir
		if (($d = @opendir ($fullpath)) === false)
		{
			$dirArr = explode('/', $class_path);
			if( !empty($dirArr[0]) )
			{
				// make Archetype folder
				$archetype 	= $path.$dirArr[0];
				if( !file_exists($archetype) ) mkdir($archetype);
			}
			if( !empty($dirArr[1]) )
			{
				// make class folder 1
				$class 			= $archetype."/".$dirArr[1];
				if( !file_exists($class) ) mkdir($class);
			}
			if( !empty($dirArr[2]) )
			{
				// make class folder 2
				$subclass 	= $class."/".$dirArr[2];
				if( !file_exists($subclass) ) mkdir($subclass);
			}
			// now try again...
			if (($d = @opendir ($fullpath)) === false)
			{
				$scriptOptions = '<option>[Could not open path: '.$fullpath.']</option>';
			}
			else 
				$not_found = false;
		}
		else
			$not_found = false;

		if( !$not_found )
		{
			while ($f = readdir ($d)) 
			{
				if( $f != "." && $f != ".." && $f != ".svn" )
				{
					$selected = ( $f == $clean_script ) ? " selected" : "";
					$scriptOptions .= sprintf("<option%s>%s</option>\r\n",$selected,$f);
				}
			}
		}
		
		return $scriptOptions;
	}

	function getSpellScriptList($script) 
	{
		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH . "Spells/";

		if (($d = @opendir ($path)) === false) 
			echo ('[Could not open path: ]'.$path);
		else 
		{
			while ($f = readdir ($d)) 
			{
				if( $f != "." && $f != ".." && $f != ".svn" && preg_match("/\.bak/",$f)==0 && preg_match("/template/",$f)==0 )
				{
					$selected = ( $f == $script ) ? " selected" : "";
					$scriptOptions .= sprintf("<option%s>%s</option>\r\n",$selected,$f);
				}
			}
		}
		return $scriptOptions;
	}
	
	function getTotalSpells() 
	{
	}
	
	function getTraitSpellOptions() 
	{
		$sql = sprintf("SELECT s.id, s.name FROM spells s, spell_traits st WHERE s.id = st.spell_id AND st.level = %s", $_GET['l']);

		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		printf('<select name="traitSpell" onchange="dosub(this.options[this.selectedIndex].value)"><option value="traits.php?t=%s&l=%s">---</option>', $_GET['t'], $_GET['l']);
		
		while( $data = $this->db->sql_fetchrow($result) )
			printf('<option value="traits.php?t=%s&l=%s&s=%s%s"%s>%s</option>', $_GET['t'], $_GET['l'], $data['id'], isset($_GET['p']) ? "&p=".$_GET['p'] : "", $_GET['s'] == $data['id'] ? " selected" : "", $data['name']);

		print('</select>');
	}
	
	function getSpellTraitsData()
	{
		$sql = sprintf("SELECT * FROM spell_traits WHERE spell_id = %s", $_GET['s']);

		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		$data = $this->db->sql_fetchrow($result);
		return $data;
	}
	
	function getNextSpellID($range) 
	{
		$sql = sprintf("select max(id) as maxID from spells where id like '%s'", $range);
		//printf("%s<br />", $sql);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		$data=$this->db->sql_fetchrow($result);
		return $data['maxID']+1;
	}
	
	function doSpellPopup()
	{
		?>
		<div id="item_1263337103" class="itemd_surround itemd_hoverdiv">
			<div class="itemd_name">Heroic Hauberk of Entitlement</div>
			<div class="itemd_icon"><img src="http://census.daybreakgames.com/s:eq2wire/img/eq2/icons/1523/item/"></div>
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
		<?php

	}
	

	//
	// Zone Functions
	//
	function drawZonePicker($populated = false) 
	{
		if( $populated ) {
			$sql=sprintf("select id,name,description from zones where id in (select zone_id from spawn_location_placement) order by description");
		} else {
			$sql=sprintf("select id,name,description from zones order by description");
		}
		$result=$this->db->sql_query($sql);
		while($data=$this->db->sql_fetchrow($result)) {
			$selected=( $_GET['z'] == $data['id'] ) ? " selected" : "";
			$zoneOptions.='<option value="index.php?page='.$_GET['page'].'&tab='.$_GET['tab'].'&z='.$data['id'].'"'.$selected.'>'.$data['description'].' ('.$data['name'].')</option>\n';
		}
	?>
	<tr>
		<td valign="top">
			<select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)">
			<option>Pick a Zone</option>
			<?= $zoneOptions ?>
			</select>
		</td>
	</tr>
	<?php
	}

	function updateZone() 
	{
		$sets='';
		foreach($_POST as $key=>$val) {
			if( substr($key,0,1)=="z" ) { 
				if( empty($sets) ) :
					$sets.=substr($key,1)."='".$val."'";
				else :
					$sets.=", ".substr($key,1)."='".$val."'";
				endif;
			}
		}
		$sql = sprintf("update zones set %s where id = %d;",$sets,$_POST['id']);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		// write query to user_session log
		$this->logQuery($sql);
	}


	//
	// General Editor UI Functions
	//
	function ShowQueryWindow($colspan, $message)
	{
		?>
		<tr>
			<td colspan="<?= $colspan ?>">
				<br />
				<hr />
				<span><?= $message ?></span><br />
				<input type="submit" name="cmd" value="Query" style="font-size:10px; width:60px" /><br />
				<textarea name="querytext" style="width:900px; height:100px; font:'Courier New', Courier, monospace;"></textarea>
			</td>
		</tr>
		<?php
	}


	//
	// Select Functions
	//
	function selectEntityCommands($id) 
	{
		print("<option value=\"0\">---</option>\n");
		$sql=sprintf("select command_list_id,command_text from entity_commands order by command_text");
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		while($data=$this->db->sql_fetchrow($result)) {
			$selected=( $data['command_list_id'] == $id ) ? " selected" : "";
			printf("<option value=\"%d\"%s>%s</option>\n",$data['command_list_id'],$selected,$data['command_text']);
		}
	}

	function SelectItemNameByID($id) 
	{
		$sql=sprintf("select id, name from items where id = %lu", $id);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		while( $data=$this->db->sql_fetchrow($result) )
		{
			$selected = ( $data['id'] == $id ) ? " selected" : "";
			printf('<option value="%lu">%s (%lu)</option>\n', $data['id'], $data['name'], $data['id']);
		}
	}

	function ZoneSelector($pop = 0) 
	{
	
		$q_string = ( isset($_GET['page']) ) ? "page=" . $_GET['page'] : "";
		$q_string .= ( isset($_GET['type']) ) ? "&type=".$_GET['type'] : "";
		
		print('<select name="zoneID" onChange="dosub(this.options[this.selectedIndex].value)" class="zone" />');
		print('<option>Pick a Zone</option>');
		if( $pop ) 
			$sql="SELECT id,name,file,description FROM zones WHERE id in (SELECT DISTINCT zone_id FROM spawn_location_placement) ORDER BY description;";
		else
			$sql="SELECT id,name,description FROM zones ORDER BY description;";
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		while($data=$this->db->sql_fetchrow($result)) {
			$selected=( $_GET['zone'] == $data['id'] ) ? " selected" : "";
			printf("<option value=\"?%s&zone=%d\"$selected>%s (%s)</option>\n", 
				$q_string, $data['id'], $data['description'], $data['name']);
		}
		print('</select>');
	}

	function SOECollectionTypeSelector()
	{
		print('<select onchange="dosub(this.options[this.selectedIndex].value)" style="width:100px; font-size:11px;">');
		printf('<option value="%s">---</option>', $this->PageLink);
		switch($_GET['collection'])
		{
			case "spell":
			
				$spell_collection_types = array("arts","spells","pcinnates","tradeskills","abilities");
				
				foreach($spell_collection_types as $type)
					printf('<option value="%s&collection=%s&type=%s"%s>%s</option>', $this->PageLink, $_GET['collection'], $type, ( $_GET['type'] == $type ) ? " selected" : "", $type);
				break;
				
			case "item":
				break;
				
		}
		print('</select>');
		return;
	}
	
	function SOECollectionSelector()
	{
		$collection_options = sprintf('<option value="%s">---</option>', $this->PageLink);
		$collection_options .= sprintf('<option value="%s&collection=spell"%s>spell</option>', $this->PageLink, ( $_GET['collection'] == "spell" ) ? " selected" : "");
		$collection_options .= sprintf('<option value="%s&collection=item"%s>item</option>', $this->PageLink, ( $_GET['collection'] == "item" ) ? " selected" : "");
		printf('<select onchange="dosub(this.options[this.selectedIndex].value)" style="width:100px; font-size:11px;">%s</select>', $collection_options);
		return;
		
		
		
		// for future use when we care about more collections than spells and items
		$DataURL = "http://census.daybreakgames.com/json/get/eq2/";
		$soe_array = json_decode($this->file_get_contents_curl($DataURL), true);
		//printf("%s<br />%s<br />", $DataURL, $soe_array[returned]);
		//print_r($soe_array);
		if( is_array($soe_array) )
		{
			foreach($soe_array["collections"] as $key=>$val)
				$collection_options .= sprintf('<option value="%s&collection=%s"%s>%s</option>', $this->PageLink, $val[name], ( $_GET['collection'] == $val[name] ) ? " selected" : "", $val[name]);
			
			printf('<select onchange="dosub(this.options[this.selectedIndex].value)">%s</select>', $collection_options);
		}
	}


	//
	// Get Functions
	//
	function GetCollectionCount($collection, $type)
	{
		$DataURL = sprintf("http://census.daybreakgames.com/json/count/eq2/%s/?type=%s", $collection, $type);
		$soe_array = json_decode($this->file_get_contents_curl($DataURL), true);
		//printf("%s<br />%s<br />", $DataURL, $soe_array[returned]);
		
		if( is_array($soe_array) )
			$this->soe_count = $soe_array['count'];
			
		return $this->soe_count;
	}
	
	function GetCharacterNameByID($id) 
	{		
		if( $id==0 ) return;
		$sql=sprintf("select name from %s.characters where id = %lu", ACTIVE_DB, $id); //echo $sql;
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		else
			$data = $this->db->sql_fetchrow($result);

		return $data['name'] ?? "Unknown";
	}

	function getExpansionOptions($id) 
	{
		global $eq2;
		print("<option value=\"0\">---</option>\n");
		$eq2->SQLQuery = sprintf("select id, expansion from `%s`.`eq2expansions`;", ACTIVE_DB);
		$rows = $eq2->RunQueryMulti();
		$expansion_options = "";
		foreach($rows as $data) {
			$selected=( $data['id'] == $id ) ? " selected" : "";
			$expansion_options .= sprintf("<option value=\"%d\"%s>%s</option>\n",$data['id'],$selected,$data['expansion']);
		}
		return $expansion_options;
	}
	
	function getFactions($id) 
	{
		if( $id==0 ) $factionOptions = "<option value=\"0\">---</option>";
		$sql="select id,name from factions order by name";
		//$sql="select * from skills where description rlike 'spells'";
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		while($data=$this->db->sql_fetchrow($result)) {
			$selected=( $id == $data['id'] ) ? " selected" : "";
			$factionOptions .= sprintf("<option value=\"%s\"$selected>%s</option>\n",$data['id'],$data['name']);
		}
		return $factionOptions;
	}

	function GetGMNameColor($status) 
	{

		if( $status >= 200 )
			$GMColor = ' style="color:#990000; font-weight:bold;"';
		else if( $status >= 150 && $status < 200 )
			$GMColor = ' style="color:#000099;"';
		else if( $status >= 100 && $status < 150 )
			$GMColor = ' style="color:#009900;"';
		else if( $status >= 70 && $status < 100 )
			$GMColor = ' style="color:#999900;"';
		else if( $status >= 50 && $status < 70 )
			$GMColor = ' style="color:#009999;"';
		else if( $status >= 10 && $status < 50 )
			$GMColor = ' style="color:#999999;"';
		else 
			$GMColor = ' style="color:#000000;"';
		return $GMColor;

	}
	
	// Refactored: 2015.12.15
	public function getItemName($id) 
	{
		$this->SQLQuery=sprintf("select name from `".ACTIVE_DB."`.items where id = %lu", $id);
		$data = $this->RunQuerySingle();
		return ( !empty($data['name']) ) ? $data['name'] : $id;
	}
	
	function getItemTiers($tier = 0) 
	{
		$this->SQLQuery="select distinct tier from `".ACTIVE_DB."`.items order by tier";
		$result=$this->RunQueryMulti();
		if(is_array($result)) {
			$factionOptions = "";
			foreach($result as $data) {
				$selected=( $tier == $data['tier'] ) ? " selected" : "";
				$factionOptions .= sprintf("<option$selected>%s</option>\n",$data['tier']);
			}
		}
		return $factionOptions;
	}

	function GetMerchantsItemList($id) 
	{
		$sql = sprintf("select item_id, item_name, price, level from `".PARSER_DB."`.raw_merchant_items where spawn_id = %s order by item_name;", $id);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		print('<table width="100%" cellpadding="2" cellspacing="2" border="1" align="center">');
		print('<tr><td>id</td><td>item</td><td>price</td><td>level</td></tr>');
		while($data=$this->db->sql_fetchrow($result)) {
			printf('<tr><td>&nbsp;%s</td><td>&nbsp;%s</td><td>&nbsp;%s</td><td>&nbsp;%s</td></tr>',
				$data['item_id'], $data['item_name'], $data['price'], $data['level']);
		}
		print('</table>');
	}
	
	function getPlayerClassOptions($id) 
	{
		foreach($this->eq2PlayableClasses as $key=>$val) {
			$selected = ( $key == $id ) ? " selected" : "";
			$raceOptions .= "<option value='$key'$selected>$val</option>\n";
		}
		return $raceOptions;
	}
	
	function getPlayerRaceOptions($id) 
	{
		$raceOptions = "";
		foreach($this->eq2Races as $key=>$val) {
			$selected = ( $key == $id ) ? " selected" : "";
			$raceOptions .= "<option value='$key'$selected>$val</option>\n";
		}
		return $raceOptions;
	}
	
	function getSkillName($id) 
	{
		$sql = sprintf("select name from `".ACTIVE_DB."`.skills where id = %s",$id);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		$data=$this->db->sql_fetchrow($result);
		return ( !empty($data['name']) ) ? $data['name'] : '';
	}

	function GetSpawnTypeBySpawnID($id) 
	{
		$sql = sprintf("SELECT 
												spawn_npcs.spawn_id AS npc, 
												spawn_objects.spawn_id AS object, 
												spawn_signs.spawn_id AS SIGN, 
												spawn_widgets.spawn_id AS widget, 
												spawn_ground.spawn_id AS ground 
											FROM `" . ACTIVE_DB . "`.spawn 
											LEFT JOIN `" . ACTIVE_DB . "`.spawn_npcs ON spawn.id = spawn_npcs.spawn_id
											LEFT JOIN `" . ACTIVE_DB . "`.spawn_objects ON spawn.id = spawn_objects.spawn_id
											LEFT JOIN `" . ACTIVE_DB . "`.spawn_signs ON spawn.id = spawn_signs.spawn_id
											LEFT JOIN `" . ACTIVE_DB . "`.spawn_widgets ON spawn.id = spawn_widgets.spawn_id
											LEFT JOIN `" . ACTIVE_DB . "`.spawn_ground ON spawn.id = spawn_ground.spawn_id
											WHERE 
												spawn.id = %lu", $id);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		$data=$this->db->sql_fetchrow($result);
		if( isset($data['npc']) ) 
			return "npcs";
		if( isset($data['object']) ) 
			return "objects";
		if( isset($data['sign']) ) 
			return "signs";
		if( isset($data['widget']) ) 
			return "widgets";
		if( isset($data['ground']) ) 
			return "ground";
	}
	
	function getOrphanedScripts($base) 
	{
		if( !defined("SCRIPT_PATH") )
			die("SCRIPT_PATH constant not set in config.php");
		else
			$path = SCRIPT_PATH . $base . "/";
	
		if (($d = @opendir ($path)) === false) echo ('[Could not open path: ]'.$path);
		else {
			$i = 0;
			while ($f = readdir ($d)) {
				if( $f != "." && $f != ".." && $f != ".svn" && preg_match("/\.bak/",$f)==0 && preg_match("/template/",$f)==0 && !$this->scriptInDB($base, $f) ) {
					$rowStyle=( $i % 2 ) ? " bgcolor=\"#ffffff\"" : " bgcolor=\"#dddddd\"";
					if (is_dir ($path . "/" . $f)) {
						$this->getOrphanedScripts($base . "/" . $f);
					} else {
						print("<tr$rowStyle>\n");
						print("  <td>&nbsp;</td>\n");
						print("  <td>$path$f</td>\n");
						print("  <td>&nbsp;</td>\n");
						print("</tr>");
					}
					$i++;
				}
			}
		}
	}

	function getStartingCity($id) 
	{
		$cityOptions = "";
		if( $id==0 ) $cityOptions = "<option value=\"0\">---</option>";
		foreach($this->eq2StartingCities as $key=>$val) {
			$selected = ( $key == $id ) ? " selected" : "";
			$cityOptions .= "<option value='$key'$selected>$val</option>\n";
		}
		return $cityOptions;
	}

	function getTraitTypeOptions()
	{
		printf('<select name="traitType" onchange="dosub(this.options[this.selectedIndex].value)" style="width:70px"><option value="traits.php">---</option>');
		foreach($this->eq2TraitTypes as $trait) 
				printf("<option value=\"traits.php?t=%s\"%s>%s</option>\n", $trait, $_GET['t'] == $trait ? " selected" : "", ucfirst(strtolower($trait)));
		print('</select>');
	}
	
	function getTraitLevelOptions()
	{
		printf('<select name="traitLevel" onchange="dosub(this.options[this.selectedIndex].value)" style="width:70px"><option value="traits.php?t=%s">---</option>', $_GET['t']);
		switch($_GET['t'])
		{
			case "stats":
				for( $i = 8; $i <= 88; $i += 20)
					printf('<option value="traits.php?t=%s&l=%s"%s>%s</option>', $_GET['t'], $i, $_GET['l'] == $i ? " selected" : "", $i);
				break;
			case "resists":
				for( $i = 15; $i <= 90; $i += 15)
					printf('<option value="traits.php?t=%s&l=%s"%s>%s</option>', $_GET['t'], $i, $_GET['l'] == $i ? " selected" : "", $i);
				break;
			case "pools":
				for( $i = 22; $i <= 82; $i += 20)
					printf('<option value="traits.php?t=%s&l=%s"%s>%s</option>', $_GET['t'], $i, $_GET['l'] == $i ? " selected" : "", $i);
				break;
			default:
				break;
		}
		print('</select>');
	}
	
	function getLastPlayers($qty) 
	{
	}
	
	function getTopPlayers($qty) 
	{
	}
	
	function getZoneDescriptionByID($id) 
	{
		$sql="select description from zones where id = $id";
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		$data=$this->db->sql_fetchrow($result);
		return ( isset($data) ) ? $data['description'] : "Not Found.";
	}	
	
	function getZoneIDBySpawnID($id) 
	{
		$node_id = round($id / 10000);
		$sql = sprintf("select id from zones where id = %lu", $node_id);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		$data=$this->db->sql_fetchrow($result);
		return ( isset($data) ) ? $data['id'] : 0;
	}	
	
	function getZoneNameBySpawnID($id) 
	{
		$node_id = round($id / 10000);
		$sql = sprintf("select name from zones where id = %lu", $node_id);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		$data=$this->db->sql_fetchrow($result);
		return ( isset($data) ) ? $data['name'] : "Not Found.";
	}	
	
	function getZoneOptionsByDescription($description='') 
	{
		$sql="select description from zones order by description";
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		printf("<option value=\"0\">---</option>\n");
		while($data=$this->db->sql_fetchrow($result)) {
			$selected=( $description == $data['description'] ) ? " selected" : "";
			printf("<option$selected>%s</option>\n",$data['description']);
		}
	}
	
	function getZoneOptionsByName($name='') 
	{
		$sql="select name,description from zones order by description";
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));

		printf("<option value=\"0\">---</option>\n");
		while($data=$this->db->sql_fetchrow($result)) {
			$selected=( $name == $data['name'] ) ? " selected" : "";
			printf("<option value=\"%s\"$selected>%s (%s)</option>\n",$data['name'],$data['description'],$data['name']);
		}
	}
	
	function getZoneBySpawnID($spawnID) 
	{
		$zoneID = intval($spawnID / 10000);
		return $zoneID;
	}

	function getNextHighestID($table,$idtype,$template) 
	{
		$sql = sprintf("select max(%s) as nextID from %s where %s like '%s'",$idtype,$table,$idtype,$template);
		if( !$result = $this->db->sql_query($sql) )
			$this->DBError($sql, array(__FILE__, __FUNCTION__, __LINE__));
		$data=$this->db->sql_fetchrow($result);
		return $data['nextID'];
	}
	
	function getSpawnNameByID($spawnID)
	{
		$this->SQLQuery = sprintf("SELECT name FROM `".ACTIVE_DB."`.spawn WHERE id = %u", $spawnID);
		$row = $this->RunQuerySingle();
		return $row['name'];
	}

	function getZoneNameByID($id)
	{
		$query = "SELECT `description` FROM `".ACTIVE_DB."`.`zones` WHERE id = " . $id;
		$data = $this->RunQuerySingle($query);
		//$row = $this->RunQuerySingle();
		return($data['description']);
	}

	function GetTableColumns($schema, $table, $criteria = "") {
		$this->SQLQuery = sprintf("SELECT COLUMN_NAME FROM information_schema.columns WHERE `TABLE_SCHEMA` = '%s' AND TABLE_NAME = '%s' %s;", $schema, $table, $criteria);
		return $this->RunQueryMulti();
	}

	function GetRowCloneQuery($schema, $table, $keyField, $key, $new_key, $ignoreFields = NULL, $destSchema = NULL, $bIgnore = false) {
		$criteria = sprintf("AND COLUMN_NAME NOT IN('%s'", $keyField);
		if (isset($ignoreFields)) {
			$criteria .= ",".$ignoreFields;
		}
		$criteria .= ")";
		$rows = $this->GetTableColumns($schema, $table, $criteria);
		$query = sprintf("INSERT %sINTO `%s`.`%s` (`%s`", $bIgnore ? "IGNORE " : "", isset($destSchema) ? $destSchema : $schema, $table, $keyField);
		foreach ($rows as $rowInfo) {
			foreach ($rowInfo as $k => $v) {
				$query .= sprintf(',`%s`', $v);
			}
		}
		$query .= ") SELECT ".$new_key;
		foreach ($rows as $rowInfo) {
			foreach ($rowInfo as $k => $v) {
				$query .= sprintf(',`%s`', $v);
			}
		}
		$query .= sprintf(" FROM `%s`.`%s` WHERE `%s` = %s;", $schema, $table, $keyField, $key);
		return $query;
	}

	function PrintRGBColorBox($r, $g, $b, $id = "")
	{
		printf("<label id=\"%s\" class=\"rgb_colorbox\" style=\"background-color:#%02X%02X%02X\"></label>", $id, $r, $g, $b);
	}

	function BeginSQLTransaction() {
		$this->db->sql_begin_transaction();
	}

	function SQLTransactionCommit() {
		$this->db->sql_commit();
	}

	function SQLTransactionRollback() {
		$this->db->sql_rollback();
	}

	//If templates is used it should be formatted as an array like templates[category][name][scriptText]
	function DisplayScriptEditor($scriptPath, $editingName, $objectID, $table, $templates = NULL) {
		global $eq2;
		$strOffset = str_repeat("\x20",22);
		$script_exists = $this->CheckScriptExists($scriptPath);

		$return_string = "";
		$return_string .= $strOffset . "<div id='Editor' style='max-width:100%;'>\n";
		$return_string .= $strOffset . "  <table class='SubPanel' cellspacing='0' border='0' style='width:100%;'>\n";
		$return_string .= $strOffset . "    <tr>\n";
		$return_string .= $strOffset . "      <td id='EditorStatus' colspan='2'>" . (isset($eq2->Status)?$eq2->DisplayStatus():'') ."</td>\n";
		$return_string .= $strOffset . "    </tr>\n";
		$return_string .= $strOffset . "    <tr>\n";
		$return_string .= $strOffset . "      <td class='Title' colspan='2'>\n";
		$return_string .= $strOffset . "        Editing: " . $editingName . "(" . $scriptPath . ") " . (!$script_exists?"<strong>*New*</strong>":"") ."\n";
		$return_string .= $strOffset . "      </td>\n";
		$return_string .= $strOffset . "    </tr>\n";
		$return_string .= $strOffset . "    <tr>\n";
		$return_string .= $strOffset . "      <td valign='top' style='width:20%;height:100%;background-color:white;'>\n";
		$return_string .= $strOffset . "        <table class='SectionToggles' cellspacing='0' border='0' style='width:100%;'>\n";
		$return_string .= $strOffset . "          <tr>\n";
		$return_string .= $strOffset . "            <td class='SectionTitle' align='center'>Templates</td>\n";
		$return_string .= $strOffset . "          </tr>\n";
		$return_string .= $strOffset . "          <script>\n";
		$return_string .= $strOffset . "            function AddTextToEditor(element) {\n";
		$return_string .= $strOffset . "              console.log(element.getAttribute('myFuncText'));\n";
		$return_string .= $strOffset . "              editor.insert(element.getAttribute('myFuncText'));\n";
		$return_string .= $strOffset . "            }\n";
		$return_string .= $strOffset . "          </script>\n";
		$return_string .= $strOffset . "          <tr align='center'>\n";
		$return_string .= $strOffset . "            <td align='center'>\n";
		//BEGIN TEMPLATE SECTION
		$return_string .= $strOffset . "              <table class='ContrastTable' width='100%'>\n";
		$return_string .=  $eq2->GetLuaBlocks('showList');
		//END TEMPLATE SECTION

		$return_string .= $strOffset . "            </td>\n";
		$return_string .= $strOffset . "          </tr>\n";
		$return_string .= $strOffset . "        </table>\n";
		$return_string .= $strOffset . "      </td>\n";
		$return_string .= $strOffset . "    </tr>\n";
		$return_string .= $strOffset . "  </table>\n";
		$return_string .= $strOffset . "</td>\n";
		$return_string .= $strOffset . "<td valign='top' style='width:80%;'>\n";
		$return_string .= $strOffset . "  <form method='post' name='ScriptForm'>\n";
		$return_string .= $strOffset . "    <table class='SectionMain' cellspacing='0' border='0' style='width:100%;'>\n";
		$return_string .= $strOffset . "      <tr>\n";
		$return_string .= $strOffset . "        <td class='SectionTitle' align='center'>Script Editor</td>\n";
		$return_string .= $strOffset . "          </tr>\n";
		$return_string .= $strOffset . "            <tr>\n";
		$return_string .= $strOffset . "              <td id='ScriptToolbar'>\n";
		$return_string .= $strOffset . "              </td>\n";
		$return_string .= $strOffset . "            </tr>\n";
		$return_string .= $strOffset . "            <tr>\n";
		$return_string .= $strOffset . "              <td height='480px'> \n";

		$scriptText = $this->LoadLUAScript($scriptPath);

		$return_string .= $strOffset . "                <div id='scripteditor' style='margin: 0; width: 100%; height: 100%'>" . $scriptText . "</div>\n";
		$return_string .= $strOffset . "                  <script src='../ace/src-noconflict/ace.js' charset='utf-8'></script>\n";
		$return_string .= $strOffset . "                  <script src='../ace/src-noconflict/ext-language_tools.js'></script>\n";
		$return_string .= $strOffset . "                  <script>\n";
		$return_string .= $strOffset . "                    var lang_tools = ace.require('../ace/ext/language_tools');\n";
		$return_string .= $strOffset . "                    var editor = ace.edit('scripteditor');\n";
		$return_string .= $strOffset . "                    editor.setShowPrintMargin(false);\n";
		$return_string .= $strOffset . "                    editor.setTheme('../ace/theme/textmate');\n";
		$return_string .= $strOffset . "                    editor.session.setMode('../ace/mode/lua'); \n";
		$return_string .= $strOffset . "                    lang_tools.setCompleters([lang_tools.snippetCompleter, lang_tools.keyWordCompleter]);\n";
		$return_string .= $strOffset . "                    editor.setOptions({\n";
		$return_string .= $strOffset . "                      enableLiveAutocompletion: true\n";
		$return_string .= $strOffset . "                    });\n";
		$return_string .= $strOffset . "                    editor.on('change', function() {updateCachedScript();});\n";
		$return_string .= $strOffset . "                  </script>\n";
		$return_string .= $strOffset . "                </td>\n";
		$return_string .= $strOffset . "              </tr>\n";
		
		if( $this->CheckAccess(G_DEVELOPER) )
		{
			$return_string .= $strOffset . "              <tr>\n";
			$return_string .= $strOffset . "                <td align='center'>\n";
			$return_string .= $strOffset . "                  <input type='submit' align='center' name='cmd' value='" . ($script_exists ? 'Save' : 'Create') . "' class='submit' id='savescript' />\n";
			$return_string .= $strOffset . "                  <!--<input type='submit' name='cmd' value='Rebuild' class='submit' title='Rebuilds the script from scratch (overwrite old one).' />-->\n";
			$return_string .= $strOffset . "                  <button id='scriptRevert' type='button'>Revert</button>\n";
			$return_string .= $strOffset . "                  <input type='hidden' id='script_name' name='script_name' value='" . $scriptPath . "' />\n";
			$return_string .= $strOffset . "                  <input type='hidden' name='script_path' value='" . substr($scriptPath, 0, strrpos($scriptPath, '/')) . "' />\n";
			$return_string .= $strOffset . "                  <input type='hidden' name='table_name' value='" . $table . "' />\n";
			$return_string .= $strOffset . "                  <input type='hidden' name='object_id' value='" . $objectID . "' />\n";
			$return_string .= $strOffset . "                  <input type='hidden' name='script_text' id='LuaScript' />\n";
			$return_string .= $strOffset . "                  <script>\n";
			$return_string .= $strOffset . "                    document.getElementById('savescript').onclick = \n";
			$return_string .= $strOffset . "                    function() {\n";
			$return_string .= $strOffset . "                      document.getElementById('LuaScript').value = editor.getValue();\n";
			$return_string .= $strOffset . "                    };\n";
			$return_string .= $strOffset . "                    document.getElementById('scriptRevert').onclick = \n";
			$return_string .= $strOffset . "                    function() {\n";
			$return_string .= $strOffset . "                      if (confirm('Are you sure you want to revert? You will lose your local changes.')) {\n";
			$return_string .= $strOffset . "                        editor.setValue(original_lua_script_text, 1);\n";
			$return_string .= $strOffset . "                        clearCachedScript();\n";
			$return_string .= $strOffset . "                      }\n";
			$return_string .= $strOffset . "                    };\n";
			$return_string .= $strOffset . "                    checkForCachedScript();\n";
			$return_string .= $strOffset . "                  </script>\n";
			$return_string .= $strOffset . "                </td>\n";
			$return_string .= $strOffset . "              </tr>\n";
		}
		$return_string .= $strOffset . "            </table>\n";
		$return_string .= $strOffset . "          </form>\n";
		$return_string .= $strOffset . "        </td>\n";
		$return_string .= $strOffset . "      </tr>\n";
		$return_string .= $strOffset . "    </table>\n";
		$return_string .= $strOffset . "  </div>\n";
		return($return_string);
	}

	//
	// Set Functions
	//
	function SetRandomColor()
	{
		$color_array = '0123456789abcdef';
		for( $i=0; $i <= 5; $i++ )
		{
			$color .= substr($color_array, rand(0, 15), 1);
		}
		return $color;
	}

	function GetNewsTypeNameByID($id)
	{
		$query = "SELECT emu_name AS name FROM `eq2news_types` WHERE emu_type='" . $id . "'";
		$data = $this->RunQuerySingle($query);

		return($data['name']);
	}

	function GetNewsSubTypeNameByID($id)
	{
		$query = "SELECT emu_name AS name FROM `eq2news_types` WHERE emu_type='" . $id . "'";
		$data = $this->RunQuerySingle($query);
		
		return($data['name']);
	}

	function GetUserNameByID($id)
	{
		$query = "SELECT username FROM users WHERE id = " . $id;
		$rtn = $this->RunQuerySingle($query);

		return $rtn['username'];
	}

} // class eq2db

} // if ... define

?>

<?php

class Paginator{
    var $items_per_page;
    var $items_total;
    var $current_page;
    var $num_pages;
    var $mid_range;
    var $low;
    var $high;
    var $limit;
    var $return;
    var $default_ipp = 25;

    function __construct()
    {
        $this->current_page = 1;
        $this->mid_range = 7;
        $this->items_per_page = (!empty($_GET['ipp'])) ? $_GET['ipp']:$this->default_ipp;
    }

    function paginate()
    {
        if($_GET['ipp'] == 'All')
        {
            $this->num_pages = ceil($this->items_total/$this->default_ipp);
            $this->items_per_page = $this->default_ipp;
        }
        else
        {
            if(!is_numeric($this->items_per_page) OR $this->items_per_page <= 0) $this->items_per_page = $this->default_ipp;
            $this->num_pages = ceil($this->items_total/$this->items_per_page);
        }
        $this->current_page = (int) $_GET['page']; // must be numeric > 0
        if($this->current_page < 1 Or !is_numeric($this->current_page)) $this->current_page = 1;
        if($this->current_page > $this->num_pages) $this->current_page = $this->num_pages;
        $prev_page = $this->current_page-1;
        $next_page = $this->current_page+1;

        if($this->num_pages > 10)
        {
            $this->return = ($this->current_page != 1 And $this->items_total >= 10) ? "<a class=\"paginate\" href=\"$_SERVER[PHP_SELF]?page=$prev_page&ipp=$this->items_per_page\">� Previous</a> ":"<span class=\"inactive\" href=\"#\">� Previous</span> ";

            $this->start_range = $this->current_page - floor($this->mid_range/2);
            $this->end_range = $this->current_page + floor($this->mid_range/2);

            if($this->start_range <= 0)
            {
                $this->end_range += abs($this->start_range)+1;
                $this->start_range = 1;
            }
            if($this->end_range > $this->num_pages)
            {
                $this->start_range -= $this->end_range-$this->num_pages;
                $this->end_range = $this->num_pages;
            }
            $this->range = range($this->start_range,$this->end_range);

            for($i=1;$i<=$this->num_pages;$i++)
            {
                if($this->range[0] > 2 And $i == $this->range[0]) $this->return .= " ... ";
                // loop through all pages. if first, last, or in range, display
                if($i==1 Or $i==$this->num_pages Or in_array($i,$this->range))
                {
                    $this->return .= ($i == $this->current_page And $_GET['page'] != 'All') ? "<a title=\"Go to page $i of $this->num_pages\" class=\"current\" href=\"#\">$i</a> ":"<a class=\"paginate\" title=\"Go to page $i of $this->num_pages\" href=\"$_SERVER[PHP_SELF]?page=$i&ipp=$this->items_per_page\">$i</a> ";
                }
                if($this->range[$this->mid_range-1] < $this->num_pages-1 And $i == $this->range[$this->mid_range-1]) $this->return .= " ... ";
            }
            $this->return .= (($this->current_page != $this->num_pages And $this->items_total >= 10) And ($_GET['page'] != 'All')) ? "<a class=\"paginate\" href=\"$_SERVER[PHP_SELF]?page=$next_page&ipp=$this->items_per_page\">Next �</a>\n":"<span class=\"inactive\" href=\"#\">� Next</span>\n";
            $this->return .= ($_GET['page'] == 'All') ? "<a class=\"current\" style=\"margin-left:10px\" href=\"#\">All</a> \n":"<a class=\"paginate\" style=\"margin-left:10px\" href=\"$_SERVER[PHP_SELF]?page=1&ipp=All\">All</a> \n";
        }
        else
        {
            for($i=1;$i<=$this->num_pages;$i++)
            {
                $this->return .= ($i == $this->current_page) ? "<a class=\"current\" href=\"#\">$i</a> ":"<a class=\"paginate\" href=\"$_SERVER[PHP_SELF]?page=$i&ipp=$this->items_per_page\">$i</a> ";
            }
            $this->return .= "<a class=\"paginate\" href=\"$_SERVER[PHP_SELF]?page=1&ipp=All\">All</a> \n";
        }
        $this->low = ($this->current_page-1) * $this->items_per_page;
        $this->high = ($_GET['ipp'] == 'All') ? $this->items_total:($this->current_page * $this->items_per_page)-1;
        $this->limit = ($_GET['ipp'] == 'All') ? "":" LIMIT $this->low,$this->items_per_page";
    }

    function display_items_per_page()
    {
        $items = '';
        $ipp_array = array(10,25,50,100,'All');
        foreach($ipp_array as $ipp_opt)    $items .= ($ipp_opt == $this->items_per_page) ? "<option selected value=\"$ipp_opt\">$ipp_opt</option>\n":"<option value=\"$ipp_opt\">$ipp_opt</option>\n";
        return "<span class=\"paginate\">Items per page:</span><select class=\"paginate\" onchange=\"window.location='$_SERVER[PHP_SELF]?page=1&ipp='+this[this.selectedIndex].value;return false\">$items</select>\n";
    }

    function display_jump_menu()
    {
        for($i=1;$i<=$this->num_pages;$i++)
        {
            $option .= ($i==$this->current_page) ? "<option value=\"$i\" selected>$i</option>\n":"<option value=\"$i\">$i</option>\n";
        }
        return "<span class=\"paginate\">Page:</span><select class=\"paginate\" onchange=\"window.location='$_SERVER[PHP_SELF]?page='+this[this.selectedIndex].value+'&ipp=$this->items_per_page';return false\">$option</select>\n";
    }

    function display_pages()
    {
        return $this->return;
    }


}
?>
