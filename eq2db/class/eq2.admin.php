<?php
class eq2Admin
{
	
	var $eq2SpawnTypes = array("NPCs","Objects","Signs","Widgets","Ground");
	
	/* Build SpawnScript Vars */
	var $dialog_id			= 0;
	var $conversation_id	= 0;
	var $sequence_id		= 0;



	public function __construct() 
	{
		include_once("eq2.spawns.php");
		$this->spawns = new eq2Spawns();
		
		$this->pCount = $_GET['count'] ?? 0; // number of spawn placements in a selected zone (migration)
		
		include_once("eq2.spells.php");
		$this->spells = new eq2Spells();
	}

	private $NavigationSections = array(
		"Project"=>array(
			"Server Stats"=>"stats",
			"SOE Quests"=>"soequests",
			"Log Files"=>"logs",
			"Change Logs"=>"changes",
			"Validate Scripts"=>"scripts",
			"Adhoc SQL"=>"sql"
		),
		"Migrate"=>array(
			"Migrate Zone"=>"migrate",
			"Purge Zone"=>"purgezone",
			"Migrate Spells"=>"popspells",
			"Sync Spells"=>"syncspells",
			"Migrate Locations"=>"location"
		),
		"Scripting"=>array(
			"Build Dialogs"=>"dialogs",
			"Build Movement"=>"movement",
			"List Voiceovers"=>"voiceovers"
		),
        "Administration"=>array(
			"User Manager"=>"users",
			"Reset EQ2DB Server"=>"reset",
			"Compile Source"=>"compile"
		),
		"In Dev"=>array(
			"Migrate Pets"=>"pets",
			"Compare Merchant Data"=>"merchants",
			"Merchant Data"=>"popmerchant",
		),
		"Editor Admin"=>array(
			//"Update Item Values"=>"updateItemVals"
			"Editor News" => "editor_news",
			"Editor Configs"=>"editor_configs",
			"Datasources"=>"editor_datasources"
		),
		"Dev Tools"=>array(
			"Bug Reports"=>"bugreports"
		)
	);

	public function GenerateNavigationMenu() {
		$link = $_SERVER['SCRIPT_NAME'];
		//$page = (isset($_GET['page'])?$_GET['page']:"");

		$strHTML = "";
		$strHTML .= "<table class='SectionMenuLeft' cellspacing='0' border='0'>\n";
		$strHTML .= "  <tr>\n";
		$strHTML .= "    <td class='SectionTitle'>Navigation</td>\n";
		$strHTML .= "  </tr>\n";
		$strHTML .= "  <tr>\n";
		$strHTML .= "    <td class='SectionBody'>\n";
		$strHTML .= "      <ul class='menu-list'>\n";
		$strHTML .= "        <li class='menu-list'>&raquo; <a href='" . $link . "?" . $_SERVER['QUERY_STRING'] . "'>Reload Page</a></li>\n";
		$strHTML .= "        <li class='" . (($_GET['cl'] ?? '') == "history"?"active-menu-list":"menu-list") . "'>&raquo; <a href='" . $link ."?cl=history'>ChangeLogs</a></li>\n";
		$strHTML .= "      </ul>\n";
		$strHTML .= "    </td>\n";
		$strHTML .= "  </tr>\n";
		foreach ($this->NavigationSections as $title=>$body)
		{
			$strHTML .= "<tr>\n";
			$strHTML .= "  <td class='SectionTitle'>" . $title . "</td>\n";
			$strHTML .= "</tr>\n";
			$strHTML .= "<tr>\n";
			$strHTML .= "  <td class='SectionBody'>\n";
			foreach($body as $name=>$page)
			{
				$strHTML .= "    <ul class='menu-list'>\n";
				$strHTML .= "      <li class='" . ($page == ($_GET['page'] ?? "") ? "active-menu-list" : "menu-list") ."'>\n";
				$strHTML .= "        &raquo; <a href='" . $link . "?page=" . $page . "'>" . $name . "</a>\n";
				$strHTML .= "      </li>\n";
				$strHTML .= "    </ul>\n";
			}
			$strHTML .= "  </td>\n";
			$strHTML .= "</tr>\n";
		}
		$strHTML .= "</table>\n";

		return($strHTML);
	}

	public function PreInsert() {
		global $eq2;
	}

	public function PostInsert($insert_res) {
		global $eq2;
	}

	public function PreUpdate() {
		global $eq2;
	}

	public function PostUpdate() {
		global $eq2;
	}

	public function PreDelete() {
		global $eq2;
	}

	public function PostDeletes() {
		global $eq2;
	}

	public function BuildDialogPlayFlavors($npc)
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT fl.language, fl.emote, et.text as emote_text, mt.text as msg_text, vo.file, vo.key1, vo.key2 ".
		"FROM `".PARSER_DB."`.dialog_play_flavors dpf ".
		"INNER JOIN `".PARSER_DB."`.dialog_flavors fl ON dpf.flavor_id = fl.id ".
		"LEFT JOIN `".PARSER_DB."`.dialog_voiceovers vo ON fl.voiceover_id = vo.id ".
		"LEFT JOIN `".PARSER_DB."`.dialog_text et ON fl.emote_text_id = et.id ".
		"LEFT JOIN `".PARSER_DB."`.dialog_text mt ON fl.text_id = mt.id ".
		"WHERE dpf.npc_id = ".$npc;

		$rows = $eq2->RunQueryMulti();

		$ret = array();

		foreach ($rows as $data) {
			$func = sprintf("PlayFlavor(NPC, \"%s\", \"%s\", \"%s\", %s, %s, Spawn, %s)",
		$data['file'],
		$this->ScriptTextEscape($data['msg_text']),
		$data['emote'],
		$data['key1'] ?? 0,
		$data['key2'] ?? 0,
		$data['language']);
			array_push($ret, $func);
		}

		return $ret;
	}

	public function ScriptTextEscape($txt) {
		$patArray = array("/[\"]/", "/\\n/", "/\\r/", "/\\t/");
		$repArray = array("\\\"", "\\\\n", "\\\\r", "\\\\t");
		return preg_replace($patArray, $repArray, $txt);
	}

	public function BuildDialogFunctions($npc)
	{
		global $eq2;

		//First build a dialog list, then check for responses to those dialogs
		$eq2->SQLQuery = "SELECT d.id, vo.file, vo.key1, vo.key2, tt.text as title_text, mt.text as msg_text, d.language, d.signature ".
		"FROM `".PARSER_DB."`.dialogs d ".
		"LEFT JOIN `".PARSER_DB."`.dialog_text tt ON d.title_text_id = tt.id ".
		"LEFT JOIN `".PARSER_DB."`.dialog_text mt ON d.msg_text_id = mt.id ".
		"LEFT JOIN `".PARSER_DB."`.dialog_voiceovers vo ON d.voiceover_id = vo.id ".
		"WHERE d.npc_id = ".$npc;

		$rows = $eq2->RunQueryMulti();
		if (count($rows) == 0) {
			return null;
		}

		$dialogs = array();
		$i = 1;
		foreach ($rows as $data) {
			$dName = "Dialog".$i;
			$data['funcName'] = $dName;
			$data['responses'] = array();
			$dialogs[$data['id']] = $data;
			$i++;
		}

		//Link responses
		$eq2->SQLQuery = "SELECT dr.parent_dialog_id, dr.`index`, dt.text, dr.next_dialog_id ".
		"FROM `".PARSER_DB."`.dialog_responses dr ".
		"INNER JOIN `".PARSER_DB."`.dialog_text dt ON dr.text_id = dt.id ".
		"WHERE `parent_dialog_id` IN ".
		"(SELECT DISTINCT `id` FROM `".PARSER_DB."`.dialogs WHERE npc_id = ".$npc.") ".
		"ORDER BY dr.`index`";

		$rows = $eq2->RunQueryMulti();
		foreach ($rows as $data) {
			$resp = new stdClass();
			$resp->text = $data['text'];
			$next = $data['next_dialog_id'];
			$parent = $data['parent_dialog_id'];
			$resp->reply = $next ? $dialogs[$next]['funcName'] : null;
			array_push($dialogs[$parent]['responses'], $resp);
		}

		$ret = "";
		//Build the functions
		foreach ($dialogs as $d) {
			$ret .= "function " . $d['funcName'] . "(NPC, Spawn)\n";
			$ret .= "\tFaceTarget(NPC, Spawn)\n\tDialog.New(NPC, Spawn)\n";
			$ret .= "\tDialog.AddDialog(\"".$this->ScriptTextEscape($d['msg_text'])."\")\n";
			if ($d['file']) {
				$ret .= sprintf("\tDialog.AddVoiceover(\"%s\", %s, %s)\n", $d['file'], $d['key1'], $d['key2']);
			}
			if ($d['language'] != 0 && $d['language'] != 0xff) {
				$ret .= sprintf("\tDialog.AddLanguage(%s)\n", $d['language']);
			}
			if ($d['signature'] != 0) {
				$ret .= "\tDialog.SetIsSignature(true)\n";
			}
			foreach ($d['responses'] as $resp) {
				$ret .= sprintf("\tDialog.AddOption(\"%s\"%s)\n", 
				$this->ScriptTextEscape($resp->text),
				$resp->reply ? (sprintf(", \"%s\"", $resp->reply)) : ""
				);
			}
			$ret .= "\tDialog.Start()\nend\n\n";
		}

		return $ret;
	}

	public function BuildDialogPlayVoices($npc)
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT vo.file, vo.key1, vo.key2 ".
		"FROM `".PARSER_DB."`.dialog_play_voices dpv ".
		"INNER JOIN `".PARSER_DB."`.dialog_voiceovers vo ON dpv.voiceover_id = vo.id ".
		"WHERE dpv.npc_id = ".$npc;

		$rows = $eq2->RunQueryMulti();

		$ret = array();

		foreach ($rows as $data) {
			$func = sprintf("PlayVoice(NPC, \"%s\", %s, %s, Spawn)",
		$data['file'],
		$data['key1'],
		$data['key2']);
			array_push($ret, $func);
		}

		return $ret;
	}
	
	public function BuildRandomGreeting($npc) 
	{
		global $eq2;
		
		$greetings = $this->BuildDialogPlayFlavors($npc);
		$greetings = array_merge($greetings, $this->BuildDialogPlayVoices($npc));
		
		if( count($greetings) > 0) 
		{
			$text = sprintf("\tlocal choice = MakeRandomInt(1,%s)\n\n", count($greetings));
			$z = 1;
			$text .= sprintf("\tif choice == %s then\n", $z);
			
			foreach($greetings as $data) {				
				if( $z > 1 ) 
					$text .= sprintf("\telseif choice == %d then\n",$z);
					
				$text .= "\t\t".$data."\n";
				$z++;
			}
			
			$text .= "\tend\r";
			
			return $text;
		}
		return null;
	}
	
	private function CreateSpawnLocationName($id)
	{
		$spawn_name = $this->GetSpawnNameByLocationID($id);
		
		// first, strip all the crap we don't want -- add patterns as needed
		$pattern[0] = "/^a /i";
		$pattern[1] = "/^an /i";
		$pattern[2] = "/^the /i";
		$pattern[3] = "/^dpo_/i";
		$pattern[4] = "/_/i";					// this pattern just removes the _ character

		$replace[0] = "";
		$replace[1] = "";
		$replace[2] = "";
		$replace[3] = "";
		$replace[4] = " ";

		// cleaned %spawn_name
		$spawn_name = preg_replace($pattern, $replace, $spawn_name);
		
		// now, blow it up!
		$oldArr = explode(" ", $spawn_name);
		if( is_array($oldArr) )
			foreach($oldArr as $piece)
				$newArr[] = ucfirst($piece);
		// all fixed, glue it back together!
		$spawn_name = implode("", $newArr);

		// let's fetch our zone name now
		// $zones->zone_name should be set by instantiating the class
		$zone_name = $this->spawns->zone_name;

		// and finally!
		$spawn_name = sprintf("%s_%s", $zone_name, $spawn_name);
		
		return $spawn_name;
	}
	
	public function DisplayRoleOptions($user)
	{
		global $eq2;
		
		$roles = $eq2->role_list;

		if( is_array($roles) )
		{
			?>
			<table class="inner">
			<?php	
			$i = 0;
			//print_r($user);
			foreach($roles as $role)
			{
				//print_r($role);
				//printf("My Role: %s - Role Value: %s - Eval: %s<br />", $user['role'], $role['role_value'], (intval($role['role_value']) & intval($user['role'])));
				if( (intval($role['role_value']) & intval($user['role'])) ==  intval($role['role_value']))
				{
					$checked = " checked";
				}
				else
				{
					$checked = "";
				}
			?>
				<tr>
					<td width="65%" align="right" nowrap="nowrap"><?= $role['role_description'] ?>:</td>
					<td width="35%" align="left" nowrap="nowrap"><input type="checkbox" name="users|role[<?= $i ?>]" value="<?= $role['role_value'] ?>"<?= $checked ?><?= ( $role['id'] > 12 ) ? " disabled" : "" ?> />&nbsp;(<?= $role['role_value'] ?>)</td>
				</tr>
			<?php
				$i++;
			}
			?>
			</table>
			<?php	
		}
	}

	public function GetAverageLevel($char_count) 
	{
		global $eq2;

		$eq2->SQLQuery = "SELECT SUM(level) AS num FROM `".ACTIVE_DB."`.characters;";
		$results = $eq2->RunQuerySingle();
		
		$ret = $results['num'] / $char_count;
		
		return $ret;
	}

	// These <options> build the spawn filter for Build Dialogs
	public function GetDialogOptionsBySpawn()
	{
		global $eq2;

		$zn = $_GET['zone'] ?? "";
		
		$eq2->SQLQuery = 
			sprintf("SELECT `id`, `name` FROM `".PARSER_DB."`.dialog_npcs WHERE `zone` = '".
			$eq2->SQLEscape($zn)."' ORDER BY `name`;");
										 
		$results = $eq2->RunQueryMulti();
		
		if( is_array($results) ) {
			$ret = "";
			foreach($results as $data) 
			{
				$ret .= sprintf('<option value="_admin.php?page=dialogs&zone=%s&id=%s"%s>%s</option>', $zn, $data['id'], ( $data['id'] == ($_GET['id'] ?? "")) ? " selected" : "", $data['name']);
			}
		}

		return $ret;
	}
	
	// These <options> build the zone filter for Build Dialogs
	public function GetDialogOptionsByZone()
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT DISTINCT `zone` FROM `".PARSER_DB."`.dialog_npcs ORDER BY `zone`;";
										 
		$results = $eq2->RunQueryMulti();
		
		if( is_array($results) ) {
			$ret = "";
			foreach($results as $data) 
				$ret .= sprintf('<option value="_admin.php?page=dialogs&zone=%s"%s>%s</option>', $data['zone'], ( $data['zone'] == ($_GET['zone'] ?? "") )  ? " selected" : "", $data['zone']);
		}

		return $ret;
	}

	public function GetDialogNPCName($id)
	{
		global $eq2;

		$data = $eq2->RunQuerySingle("SELECT `name` FROM `".PARSER_DB."`.dialog_npcs WHERE `id` = ".$id);
		
		if ($data != null) {
			return $data['name'];
		}
	}

	public function GetMostActiveQuests() 
	{
		global $eq2;

		$eq2->SQLQuery =  "SELECT q.quest_id, q.lua_script, count(qc.quest_id) num_completed " .
			"FROM `".ACTIVE_DB."`.quests q, `".ACTIVE_DB."`.character_quests qc " .
			"WHERE q.quest_id = qc.quest_id AND completed_date IS NOT NULL " .
			"GROUP BY qc.quest_id " .
			"ORDER BY num_completed desc LIMIT 0, 10";

		return $eq2->RunQueryMulti();
	}
	
	public function GetMostExperiencedPlayers() 
	{
		global $eq2;

		$eq2->SQLQuery = "SELECT name, class, level, tradeskill_level, count(quest_id) as quests, admin_status " . 
			"FROM `".ACTIVE_DB."`.characters c, `".ACTIVE_DB."`.character_quests cq " . 
			"WHERE c.id = cq.char_id AND admin_status = 0 " . 
			"GROUP BY c.id " . 
			"ORDER BY level desc LIMIT 0, 10";
		return $eq2->RunQueryMulti();
	}
	
	// These <options> build the spawn filter for Migrate Zone
	public function GetRawSpawnOptions()
	{
		global $eq2;

		$eq2->SQLQuery = sprintf("SELECT DISTINCT s.name " .
			"FROM `".RAW_DB."`.spawn s " .
			"INNER JOIN `".RAW_DB."`.spawn_%s s1 ON s.id = s1.spawn_id " .
			"INNER JOIN `".RAW_DB."`.spawn_location_entry sle ON s.id = sle.spawn_id ".
			"INNER JOIN `".RAW_DB."`.spawn_location_placement slp ON slp.spawn_location_id = sle.spawn_location_id AND slp.processed = 0 ".
			"WHERE s.id LIKE '%s____' AND s.processed = 0 " .
			"ORDER BY s.name", 
			$_GET['type'], $_GET['zone']);
		
		$results = $eq2->RunQueryMulti();
		
		if( is_array($results) )
		{
			$ret = "";
			foreach($results as $data)
			{
				$selected = ( $_GET['filter'] == $data['name'] && strlen($data['name']) > 0 ) ? " selected" : "";
				$ret .= 
				sprintf('<option value="_admin.php?page=popzone&zone=%s&count=%s&type=%s&filter=%s"%s>%s</option>', 
					$this->spawns->zone_id, 
					$_GET['count'], 
					$this->spawns->spawn_type, 
					$data['name'], 
					$selected, 
					$data['name']);
			}
		}
		
		return $ret;
	}
	
	// this gets the spawn's name from eq2_rawdata
	public function SetRawSpawnName($raw_spawn_id = 0) 
	{
		global $eq2;

		// the spawn_id used here is from eq2_rawdata.raw_spawn_info
		$eq2->SQLQuery = sprintf("SELECT name, guild FROM `".PARSER_DB."`.raw_spawn_info WHERE id = %s", $this->spawns->spawn_id);
		$data = $eq2->RunQuerySingle();
		
		// override normal spawn name with raw name
		$this->spawns->spawn_name = $data['name'];
		$this->spawns->spawn_title = preg_replace("/<(.*)>/","&lt;$1&gt;", $data['guild']);

		return;
	}

	// These <options> build the zone filter for Migrate Zone
	public function GetRawZoneOptions()
	{
		global $eq2;

		$eq2->SQLQuery = sprintf("SELECT z.id, z.name, z.description, COUNT(zone_id) as total_placements " .
															"FROM `".RAW_DB."`.spawn_location_placement slp " .
															"JOIN `".ACTIVE_DB."`.zones z ON slp.zone_id = z.id " .
															"GROUP BY z.id " .
															"ORDER BY description");
		
		$results = $eq2->RunQueryMulti();
		
		if( is_array($results) )
		{
			$ret = "";
			foreach($results as $data)
			{
				$ret .= sprintf('<option value="_admin.php?page=popzone&zone=%s&count=%s"%s>%s (%s) [%s]</option>', 
					$data['id'], 
					$data['total_placements'],
					( $this->spawns->zone_id == $data['id'] ) ? " selected" : "",
					$data['description'],
					$data['name'], 
					$data['total_placements']);
			}
		}
		
		return $ret;
	}
	
	// this gets the zone name from eq2_rawdata
	public function SetRawZoneName($id = 0) 
	{
		global $eq2;
		
		if( $this->spawns->zone_id > 0 )
			$id = $this->spawns->zone_id;
			
		$eq2->SQLQuery = sprintf("SELECT zone_desc FROM `".PARSER_DB."`.raw_zones WHERE id = %s", $id);
		$data = $eq2->RunQuerySingle();
		
		$this->spawns->zone_name = $data['zone_desc'];
	}	

	public function GetServerStats()
	{
		global $eq2;
		
		$db_name = $eq2->SQLEscape(ACTIVE_DB);
		$eq2->SQLQuery = sprintf(
			"SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = '%s' AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME;",
			$db_name
		);
		$tables = $eq2->RunQueryMulti();
		
		if( is_array($tables) )
		{
			$server_stats = array();
			foreach($tables as $table)
			{
				$table_name = $table['TABLE_NAME'];
				$server_stats[$table_name] = $this->GetTotalRows(ACTIVE_DB, $table_name);
			}
			
			return $server_stats;
		}
	}
	
	public function GetSOEQuestCategories()
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT DISTINCT category FROM eq2_soedata.quest_list ORDER BY category";
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestData()
	{
		global $eq2;
		
		$query = "SELECT quest_id, soe_quest_crc, name, category, tier, level FROM eq2_soedata.quest_list";
		
		if( isset($_GET['category']) && strlen($_GET['category']) > 0 )
			$query .= sprintf(" WHERE category = '%s'", $_GET['category']);
			
		$eq2->SQLQuery = $query . " ORDER BY level, tier, name";
		$ret = $eq2->RunQueryMulti();
		
		$this->total_rows = count($ret);
		
		return $ret;
	}
	
	public function GetSOEQuest($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM eq2_soedata.quest_list WHERE quest_id = %s", $id);
		return $eq2->RunQuerySingle();
	}
	
	public function GetSOEQuestStages($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM eq2_soedata.quest_stage_list WHERE quest_id_fk = %s ORDER BY stage_num", $id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestStageBranches($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM eq2_soedata.quest_branch_items WHERE quest_id_fk = %s ORDER BY stage_num", $id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestRewards($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM eq2_soedata.quest_reward_list WHERE quest_id_fk = %s", $id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestRewardItems($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM eq2_soedata.quest_reward_item_list WHERE quest_reward_id_fk = %s", $id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestRewardFactions($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM eq2_soedata.quest_reward_faction_list WHERE quest_reward_id_fk = %s", $id);
		return $eq2->RunQueryMulti();
	}
	
	private function GetSpawnGroupID($location)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT group_id FROM `".RAW_DB."`.spawn_location_group slg " . 
			"JOIN `".RAW_DB."`.spawn_location_placement slp ON slg.placement_id = slp.id " . 
			"WHERE slp.spawn_location_id = %s LIMIT 0,1", $location);

		$row = $eq2->RunQuerySingle();
		return $row['group_id'];
	}
	
	private function GetSpawnNameByLocationID($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT name FROM `".RAW_DB."`.spawn s " . 
			"JOIN `".RAW_DB."`.spawn_location_entry sle ON s.id = sle.spawn_id " . 
			"WHERE sle.spawn_location_id = %lu", $id);

		$data = $eq2->RunQuerySingle();
		return $data['name']; // must not return array
	}
	
	public function GetSpawnTypeTotalsByZone($type, $zone_id) 
	{
		global $eq2;
		
		if($type == 'All')
			$eq2->SQLQuery = sprintf("SELECT COUNT(DISTINCT s1.id) AS num FROM `".ACTIVE_DB."`.spawn_location_placement z1, `".ACTIVE_DB."`.spawn_location_entry z2, `".ACTIVE_DB."`.spawn s1 WHERE z1.spawn_location_id = z2.spawn_location_id AND z2.spawn_id = s1.id AND z1.zone_id = %d;", $zone_id);
		else
			$eq2->SQLQuery = sprintf("SELECT COUNT(DISTINCT s1.spawn_id) AS num FROM `".ACTIVE_DB."`.spawn_location_placement z1, `".ACTIVE_DB."`.spawn_location_entry z2, `".ACTIVE_DB."`.spawn_%s s1 WHERE z1.spawn_location_id = z2.spawn_location_id AND z2.spawn_id = s1.spawn_id AND z1.zone_id = %d;", $type, $zone_id);
		
		$data = $eq2->RunQuerySingle();
		
		return ( !empty($data) ) ? $data['num'] : 0;
	}
		
	public function GetGroupedSpawnsToMigrate($selectColumns, $filterData)
	{
		global $eq2;
	
		$eq2->SQLQuery = 
			sprintf("SELECT DISTINCT %s " .
			"FROM `".RAW_DB."`.spawn s1 " .
			"JOIN `".RAW_DB."`.spawn_npcs s2 ON s1.id = s2.spawn_id " .
			"JOIN `".RAW_DB."`.spawn_location_entry s3 ON s1.id = s3.spawn_id " .
			"JOIN `".RAW_DB."`.spawn_location_placement s4 ON s4.spawn_location_id = s3.spawn_location_id " .
			"JOIN `".RAW_DB."`.spawn_location_group s5 ON s4.id = s5.placement_id " .
			"WHERE group_id > 0 AND s1.id BETWEEN %s0000 AND %s9999 " .
			"%s " .
			"ORDER BY group_id, s1.id", 
			$selectColumns, 
			$this->spawns->zone_id,
			$this->spawns->zone_id,
			$filterData); 
		
		return $eq2->RunQueryMulti();
	}
	
	public function GetSpawnsToMigrate($selectColumns, $filterData)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT DISTINCT %s " .
			"FROM `".RAW_DB."`.spawn s1 " .
			"JOIN `".RAW_DB."`.spawn_%s s2 ON s1.id = s2.spawn_id " .
			"JOIN `".RAW_DB."`.spawn_location_entry s3 ON s1.id = s3.spawn_id " .
			"JOIN `".RAW_DB."`.spawn_location_placement s4 ON s4.spawn_location_id = s3.spawn_location_id " .
			"JOIN `".RAW_DB."`.appearances a ON s1.model_type = a.appearance_id " .
			"LEFT JOIN eq2_rawdata.raw_spawns rs ON s1.id = rs.populate_spawn_id " .
			"WHERE s4.zone_id = %s AND ( s1.processed <> 1 AND s4.processed <> 1 ) " .
			"%s " .
			"ORDER BY rs.data_version DESC, s1.name, s1.id", 
			$selectColumns, 
			$this->spawns->spawn_type, 
			$this->spawns->zone_id, 
			$filterData); 
		
		return $eq2->RunQueryMulti();
	}
	
	public function GetTopPlayers($count = 10) 
	{
		global $eq2;

		$eq2->SQLQuery = sprintf("SELECT id, account_id, name, class, level, tradeskill_level, current_zone_id, last_played, admin_status FROM `".ACTIVE_DB."`.characters ORDER BY last_played desc LIMIT 0, %s", $count);

		return $eq2->RunQueryMulti();
	}
	
	public function GetTotalAccounts() 
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT COUNT(DISTINCT account_id) AS num FROM `".ACTIVE_DB."`.`characters`";
		$results = $eq2->RunQuerySingle();
		return $results['num'];
	}
	
	public function GetTotalCharacters() 
	{
		global $eq2;

		$eq2->SQLQuery = "SELECT COUNT(id) AS num FROM `".ACTIVE_DB."`.characters";
		$results = $eq2->RunQuerySingle();
		return $results['num'];
	}

	public function GetTotalQuestsByZone($zone_name) 
	{
		global $eq2;
		
		if(isset($_GET["type"]) && $_GET["type"] == 'All')
			$eq2->SQLQuery = sprintf("SELECT COUNT(*) AS num FROM `".ACTIVE_DB."`.quests;");
		else
			$eq2->SQLQuery = sprintf("SELECT COUNT(*) AS num FROM `".ACTIVE_DB."`.quests WHERE lua_script RLIKE \"%s\";", $zone_name);

		$results = $eq2->RunQuerySingle();
		return $results['num'];
	}
	
	public function GetTotalRows($database, $table) 
	{
		global $eq2;

		$database = str_replace('`', '``', $database);
		$table = str_replace('`', '``', $table);
		$eq2->SQLQuery = sprintf("SELECT COUNT(*) AS num FROM `%s`.`%s`;", $database, $table);
		$data = $eq2->RunQuerySingle();
		return ( !empty($data) ) ? $data['num'] : 0;
	}

	public function GetUserInfo()
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT * FROM users WHERE id = %lu;", $_GET['id']);
		return $eq2->RunQuerySingle();
	}
	
	public function GetUserByName($var)
	{
		global $eq2;
		
		if( $var != "all" )
		{
			$user_name = $eq2->SQLEscape($var);
			$eq2->SQLQuery = sprintf("SELECT id, username, role FROM users WHERE username RLIKE '%s';", $user_name);
		}
		else
		{
			$eq2->SQLQuery = "SELECT id, username, role FROM users WHERE is_active = 1 ORDER BY username;";
		}
		return $eq2->RunQueryMulti();
	}

	public function GetUserNameByID($id)
	{
		global $eq2;
		
		$eq2->SQLQuery = sprintf("SELECT username FROM users WHERE id = %lu;", $id);
		$rtn = $eq2->RunQuerySingle();

		return $rtn['username'];
	}

	public function GetUserOptions()
	{
		global $eq2;
		
		$eq2->SQLQuery = "SELECT id,username,role FROM users ORDER BY username;";
		$row = $eq2->RunQueryMulti();
		
		$user_options = "";

		foreach($row as $data) 
			$user_options .= sprintf('<option value="_admin.php?page=users&id=%s"%s>%s (%s)</option>', $data['id'], ( ($_GET['id'] ?? 0) == $data['id'] ) ? " selected" : "", $data['username'], $data['id']);
		return $user_options;
	}
	
	public function HideSpawnFromMigration($var)
	{
		global $eq2;

		$id = ( isset($_POST['spawn_id']) ) ? $_POST['spawn_id'] : 0;
		$processed = ( $var == 2 ) ? $var : 0;
		
		if( $id )
		{
			// first, hide the main spawn
			$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn SET processed = %s WHERE id = %s", $processed, $id);
			$eq2->RunQuery(false);
			
			// now, hide all spawn locations associated that have not already been migrated
			$eq2->SQLQuery = sprintf("SELECT slp.id FROM `".RAW_DB."`.spawn_location_placement slp, `".RAW_DB."`.spawn_location_entry sle WHERE slp.spawn_location_id = sle.spawn_location_id AND slp.processed = %s AND spawn_id = %s", $processed==2 ? 0 : 2, $id);
			$rows = $eq2->RunQueryMulti();
			
			if( is_array($rows) )
			{
				// build the array of ID's
				foreach($rows as $row)
					$id_array[] = $row['id'];
					
				$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn_location_placement SET processed = %s WHERE id IN (%s)", $processed, implode(",", $id_array)); // update all in 1 query
				$eq2->RunQuery(false);
			}
		}
	}
	
	public function HideSingleSpawnLocation($var)
	{
		global $eq2;

		$id = ( isset($_POST['spawn_location_id']) ) ? $_POST['spawn_location_id'] : 0;
		$processed = ( $var == 2 ) ? $var : 0;

		if( $id )
		{
			$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn_location_placement SET processed = %s WHERE spawn_location_id = %s", $processed, $id);
			$eq2->RunQuery(false);
			
			// after hiding the last one, there will be no more - process the spawn ID
			if( $_POST['spawn_count'] == 1 )
			{
				$eq2->SQLQuery = sprintf("UPDATE `" . RAW_DB . "`.spawn SET processed = %s WHERE id = %s", $processed, $_POST['spawn_id']);
				$eq2->RunQuery(false);
			}
		}
	}
	
	// This function will spawn all NPCs that belong to the same spawn_location_group $group_id
	private function ProcessAllSpawnGroupBuddies($group_id)
	{
		global $eq2;
		
		$query_array = array();
		$spawn_id_array	= array();
		$spawn_location_array = array();
		$spawn_placement_array = array();
		
		$eq2->SQLQuery = sprintf("SELECT spawn_id, slp.id AS placement_id, slp.spawn_location_id " . 
			"FROM `".RAW_DB."`.spawn_location_group slg " . 
			"JOIN `".RAW_DB."`.spawn_location_placement slp ON slg.placement_id = slp.id " . 
			"JOIN `".RAW_DB."`.spawn_location_entry sle ON slp.spawn_location_id = sle.spawn_location_id " . 
			"WHERE group_id = %s", $group_id);
		
		$rows = $eq2->RunQueryMulti();
		
		if( is_array($rows) )
		{
			foreach($rows as $row)
			{
				if( !in_array($row['spawn_id'], $spawn_id_array) )
					$spawn_id_array[] = $row['spawn_id'];
					
				if( !in_array($row['spawn_location_id'], $spawn_location_array) )
					$spawn_location_array[] = $row['spawn_location_id'];
				
				if( !in_array($row['placement_id'], $spawn_placement_array) )
					$spawn_placement_array[] = $row['placement_id'];
			}
		}

		if( is_array($spawn_id_array) && is_array($spawn_location_array) && is_array($spawn_placement_array) )
		{
			foreach ($spawn_id_array as $spawn_id) {
				$spawn_id = $this->CheckForSpawnIDConflict($spawn_id);
				if (!$this->CheckIfDevSpawnIDExists($spawn_id))
					$this->MigrateSpawnData($spawn_id);
			}

			$newIDs = array();
			foreach ($spawn_location_array as $locID) {
				$newIDs[] = $this->MigrateLocationData($locID);
			}

			//Get a new group ID if we're going to run into a conflict
			if ($this->CheckForGroupIDConflict($group_id)) {
				reset($spawn_placement_array);
				$eq2->RunQuery(false, 
				sprintf("INSERT IGNORE INTO `".ACTIVE_DB."`.spawn_location_group (group_id,placement_id) VALUES (MAX(group_id) + 1, %s)", current($spawn_placement_array)));
				$res = $eq2->RunQuerySingle(sprintf("SELECT group_id FROM `".ACTIVE_DB."`.spawn_location_group WHERE placement_id = %s", current($spawn_placement_array)));
				$group_id = $res['group_id'];
			}

			$query_array[] = sprintf("UPDATE `".RAW_DB."`.spawn_location_placement SET processed = 1 WHERE spawn_location_id IN (%s)", implode(",",$spawn_location_array));
			$query_array[] = sprintf("INSERT IGNORE INTO `".ACTIVE_DB."`.spawn_location_group (group_id, placement_id, `name`) SELECT %s, placement_id, `name` FROM `".RAW_DB."`.spawn_location_group WHERE placement_id IN (%s)", $group_id, implode(",",$spawn_placement_array));
			
			
			foreach($query_array as $Query)
				$eq2->RunQuery(false, $Query);
		}
	}

	public function CheckForGroupIDConflict($groupID) {
		global $eq2;
		$res = $eq2->RunQuerySingle(
			sprintf("SELECT COUNT(*) as cnt FROM `".ACTIVE_DB."`.spawn_location_group WHERE group_id = %s", $groupID));
		return $res['cnt'] >= 1;
	}
	
	public function ProcessMigrateSpawns() 
	{
		global $eq2;
		
		$id = $_POST['spawn_id'] ?? 0;
		
		// MIGRATE ALL
		if( $id == "all" ) 
		{
			//Handle the spawn groups FIRST
			$eq2->SQLQuery = sprintf("SELECT DISTINCT(slg.id) as group_id FROM `".RAW_DB."`.spawn_location_entry sle ".
			"INNER JOIN `".RAW_DB."`.spawn_%s s ON sle.spawn_id = s.spawn_id ".
			"INNER JOIN `".RAW_DB."`.spawn_location_placement slp ON slp.spawn_location_id = sle.spawn_location_id AND slp.processed = 0 ".
			"INNER JOIN `".RAW_DB."`.spawn_location_group slg ON slp.id = slg.placement_id ".
			"WHERE sle.spawn_id BETWEEN %s0000 AND %s9999 ".
			"ORDER BY group_id", 
			$this->spawns->spawn_type, $this->spawns->zone_id, $this->spawns->zone_id);

			$results = $eq2->RunQueryMulti();

			if (is_array($results)) {
				foreach ($results as $data) {
					$this->ProcessAllSpawnGroupBuddies($data['group_id']);
				}
			}

			//Now we can handle the non-grouped spawns
			$eq2->SQLQuery = sprintf("SELECT s.id as id, sle.spawn_location_id as spawn_location_id " .
				"FROM `".RAW_DB."`.spawn s " .
				"JOIN `".RAW_DB."`.spawn_%s s1 ON s.id = s1.spawn_id " .
				"JOIN `".RAW_DB."`.spawn_location_entry sle ON s1.spawn_id = sle.spawn_id " .
				"JOIN `".RAW_DB."`.spawn_location_placement slp ON sle.spawn_location_id = slp.spawn_location_id " .
				"WHERE slp.id NOT IN (SELECT placement_id FROM `".RAW_DB."`.spawn_location_group) AND s.id LIKE '%s____' AND (s.processed = 0 AND slp.processed = 0) " . 
				"ORDER BY s.id", 
				$this->spawns->spawn_type, $this->spawns->zone_id);

			$results = $eq2->RunQueryMulti();
			
			if( is_array($results) )
			{
				foreach($results as $data)
				{
					// init the query array each iteration
					$spawn_location_id = $data['spawn_location_id'];
					$spawn_id = $this->CheckForSpawnIDConflict($data['id']);
					if (!$this->CheckIfDevSpawnIDExists($spawn_id))
						$this->MigrateSpawnData($spawn_id, $this->spawns->spawn_type);
					$this->MigrateLocationData($spawn_location_id);
					
					$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn SET processed = 1 WHERE id = %s", $spawn_id);
					$eq2->RunQuery(false);
				} // end spawn loop
			}
		} // end ALL
		elseif( $id )
		{
			// SPAWN THESE
			$spawn_id = $this->CheckForSpawnIDConflict($_POST['spawn_id']);
			
			if (!$this->CheckIfDevSpawnIDExists($spawn_id))
				$this->MigrateSpawnData($spawn_id);

			// loop through all the locations, insert into ACTIVE_DB.

			//First the groups
			$eq2->SQLQuery = sprintf(
				"SELECT DISTINCT(slg.group_id) as group_id FROM `".RAW_DB."`.spawn_location_entry sle ".
				"INNER JOIN `".RAW_DB."`.spawn_location_placement slp ON slp.spawn_location_id = sle.spawn_location_id AND slp.processed <> 1 ".
				"INNER JOIN `".RAW_DB."`.spawn_location_group slg ON slp.id = slg.placement_id ".
				"WHERE sle.spawn_id = %s", $spawn_id);
			$results = $eq2->RunQueryMulti();

			if (is_array($results)) {
				foreach ($results as $data) {
					$this->ProcessAllSpawnGroupBuddies($data['group_id']);
				}
			}

			//Now the remaining ungrouped locations
			$eq2->SQLQuery = sprintf(
				"SELECT sle.spawn_location_id FROM `".RAW_DB."`.spawn_location_entry sle ".
				"INNER JOIN `".RAW_DB."`.spawn_location_placement slp ON slp.spawn_location_id = sle.spawn_location_id AND slp.processed <> 1 ".
				"WHERE sle.spawn_id = %s", $spawn_id);
			$results = $eq2->RunQueryMulti();
			
			if( is_array($results) )
			{
				foreach($results as $data)
				{
					$this->MigrateLocationData($data['spawn_location_id']);
				}
			}
			
			// after spawning this one, there will be no more - process the spawn ID
			$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn SET processed = 1 WHERE id = %s", $spawn_id);
			$eq2->RunQuery(false);
		}
		else
			die("No Spawn ID! FATAL!");
	}

	function ProcessMigrateProximitySpawns() 
	{
		global $eq2;
		
		// loop through spawn_id list sent from popup_functions.php (FindMyBuddies)
		if( is_array($_POST['spawnID']) )
		{
			foreach($_POST['spawnID'] as $spawn_id)
			{
				$spawn_id = $this->CheckForSpawnIDConflict($spawn_id);
				if (!$this->CheckIfDevSpawnIDExists($spawn_id))
					$this->MigrateSpawnData($spawn_id);
	
				// loop through all the locations, insert into ACTIVE_DB.
				$eq2->SQLQuery = sprintf("SELECT spawn_location_id FROM `".RAW_DB."`.spawn_location_entry WHERE spawn_id = %s", $spawn_id);
				$results = $eq2->RunQueryMulti();
			
				if( is_array($results) )
				{
					foreach($results as $data)
					{
						$this->MigrateLocationData($data['spawn_location_id']);
					}
				}
			
				// after spawning this one, there will be no more - process the spawn ID
				$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn SET processed = 1 WHERE id = %s", $spawn_id);
				$eq2->RunQuery(false);
			}
		}
		else
			die("No spawns to be migrated!");
	}

	private function CheckIfDevSpawnIDExists($spawn_id) 
	{
		global $eq2;

		$res = $eq2->RunQuerySingle(sprintf("SELECT COUNT(*) as cnt FROM `".ACTIVE_DB."`.spawn WHERE id = %s", $spawn_id));
		return $res['cnt'] == 1;
	}

	private function CheckForSpawnIDConflict($spawn_id) 
	{
		global $eq2;

		$result = $eq2->RunQuerySingle(sprintf("SELECT IF(s2.id IS NOT NULL AND s1.name != s2.name, 1, 0) AS 'CONFLICT' FROM %s.spawn s1 LEFT JOIN %s.spawn s2 ON s1.id = s2.id WHERE s1.id = %u", RAW_DB, ACTIVE_DB, $spawn_id));

		if ($result['CONFLICT'] == '1') {
			//We have a conflict, grab a new id in our raw database for this spawn before proceeding
			$range = intval($spawn_id) / 10000;
			$row = $eq2->RunQuerySingle(sprintf("SELECT max(id)+1 AS 'NEWID' FROM %s.spawn WHERE id BETWEEN %u0000 AND %u9999", ACTIVE_DB, $range, $range));
			$old_id = $spawn_id;
			$spawn_id = $row['NEWID'];

			//We have a new id, now assign it to the raw database for tables that use spawn id
			$SPAWN_TABLES = array("spawn"=>"id", "spawn_npcs"=>"spawn_id", "spawn_npc_equipment"=>"spawn_id", 
			"npc_appearance"=>"spawn_id", "npc_appearance_equip"=>"spawn_id", "spawn_objects"=>"spawn_id", 
			"spawn_signs"=>"spawn_id", "spawn_widgets"=>"spawn_id", 
			"spawn_ground"=>"spawn_id","spawn_location_entry"=>"spawn_id");

			$eq2->RunQuery(false, "SET FOREIGN_KEY_CHECKS=0;");
			foreach ($SPAWN_TABLES as $table=>$field) {
				$eq2->RunQuery(true, sprintf("UPDATE %s.%s SET %s = %u WHERE %s = %u", RAW_DB,
				 $table, $field, $spawn_id, $field, $old_id));
			}
			$eq2->RunQuery(false, "SET FOREIGN_KEY_CHECKS=1;");
		}

		return $spawn_id;
	}

	private function MigrateLocationData($oldLoc)
	{
		global $eq2;
		$spawn_location_name = $this->CreateSpawnLocationName($oldLoc); // create a fancy spawn_location_name.name value based on where the spawn lives and it's name (QueensColony-asapswillinvader)
		
		$ret = NULL;
		$eq2->SQLQuery = sprintf("INSERT INTO `".ACTIVE_DB."`.`spawn_location_name` (`id`,`name`) VALUES (%s,'%s')", $oldLoc, $eq2->SQLEscape($spawn_location_name));

		if ($eq2->RunQuery(false) == 1) {
			$eq2->SQLQuery = $eq2->GetRowCloneQuery(RAW_DB, "spawn_location_entry", "spawn_location_id", $oldLoc, $oldLoc, NULL, ACTIVE_DB);
			$eq2->RunQuery(false);
			$eq2->SQLQuery = $eq2->GetRowCloneQuery(RAW_DB, "spawn_location_placement", "spawn_location_id", $oldLoc, $oldLoc, NULL, ACTIVE_DB);
			$eq2->RunQuery(false);	
			$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn_location_placement SET processed = 1 WHERE spawn_location_id = %s", $oldLoc);
			$eq2->RunQuery(false);
		}

		return $ret;
	}

	private function MigrateSpawnData($spawn_id, $type = NULL)
	{
		global $eq2;

		$eq2->SQLQuery = $eq2->GetRowCloneQuery(RAW_DB, "spawn", "id", $spawn_id, $spawn_id, NULL, ACTIVE_DB);
		
		if ($eq2->RunQuery(false) != 1) {
			die("Could not migrate spawn with id ".$spawn_id);
		}

		$inserts = array();
		if (isset($type)) {
			$inserts[] = $eq2->GetRowCloneQuery(RAW_DB, "spawn_".$type, "spawn_id", $spawn_id, $spawn_id, "'id'", ACTIVE_DB);
			if ($type == "npcs") {
				$inserts[] = $eq2->SQLQuery = $eq2->GetRowCloneQuery(RAW_DB, "npc_appearance", "spawn_id", $spawn_id, $spawn_id, "'id'", ACTIVE_DB);
				$inserts[] = $eq2->SQLQuery = $eq2->GetRowCloneQuery(RAW_DB, "npc_appearance_equip", "spawn_id", $spawn_id, $spawn_id, "'id'", ACTIVE_DB);
			}
		}
		else {
			//We don't know what this is, so try everything..
			$SPAWN_TABLES = array("spawn_npcs"=>"spawn_id", "npc_appearance"=>"spawn_id", 
			"npc_appearance_equip"=>"spawn_id", "spawn_objects"=>"spawn_id", 
			"spawn_signs"=>"spawn_id", "spawn_widgets"=>"spawn_id", 
			"spawn_ground"=>"spawn_id","spawn_location_entry"=>"spawn_id");

			foreach ($SPAWN_TABLES as $k=>$v) {
				$inserts[] = $inserts[] = $eq2->GetRowCloneQuery(RAW_DB, $k, "spawn_id", $spawn_id, $spawn_id, "'id'", ACTIVE_DB, true);
			}
		}

		foreach ($inserts as $query) {
			$eq2->RunQuery(false, $query);
		}
	}

	public function ProcessSingleSpawnLocation()
	{
		global $eq2;
		
		$id = $_POST['spawn_location_id'] ?? 0;

		// SPAWN THIS
		if( $id ) 
		{	
			if( ($_POST['group_id'] ?? 0) > 0 )
				$this->ProcessAllSpawnGroupBuddies($_POST['group_id']);
			else {
				$spawn_id = $this->CheckForSpawnIDConflict($_POST['spawn_id']);
			
				if (!$this->CheckIfDevSpawnIDExists($spawn_id))
					$this->MigrateSpawnData($spawn_id, $this->spawns->spawn_type);
			
				$this->MigrateLocationData($id);

				$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn_location_placement SET processed = 1 WHERE spawn_location_id = %s", $id);
				$eq2->RunQuery(false);
				
				// after spawning this one, there will be no more - process the spawn ID
				if( $_POST['spawn_count'] == 1 )
				{
					$eq2->SQLQuery = sprintf("UPDATE `".RAW_DB."`.spawn SET processed = 1 WHERE id = %s", $spawn_id);
					$eq2->RunQuery(false);
				}
			}
			
		}
		else
			die("No Spawn Location ID! FATAL!");
		
	}

	function SaveGeneratedSpawnScriptToDB() 
	{
		global $eq2;
	
		// lookup Live zone name of NPC
		$query=sprintf("select id from `".ACTIVE_DB."`.zones where name = '%s';", $_POST['zone_name']);
		$data = $eq2->RunQuerySingle($query);
		if( isset($data['id']) ) {
			$zone_id = $data['id'];
		} else {
			die($_POST['zone_name'] . " not found in Live 'zones' table.");
		}
		
		// get spawn_id from live spawn table
		$query=sprintf("select id from `".ACTIVE_DB."`.spawn where name = '%s' and id like '%d____';", addslashes($_POST['spawn_name']), $zone_id); 
		$data = $eq2->RunQuerySingle($query);
		if( isset($data['id']) ) {
			$spawn_id = $data['id'];
		} else {
			die($_POST['spawn_name'] . " not found in live spawn table. Cannot create script til zone is populated.");
		}
		
		// insert record into live spawn_script if it does not already exist
		$query=sprintf("select count(*) as cnt from `".ACTIVE_DB."`.spawn_scripts where lua_script = '%s';", $_POST['orig_object']);
		$data = $eq2->RunQuerySingle($query);
		if( isset($data['cnt']) && isset($data['cnt']) == 0 ) {
			$sql=sprintf("insert into `".ACTIVE_DB."`.spawn_scripts (spawn_id,lua_script) values ('%s','%s');", $spawn_id, $_POST['orig_object']);
			if( !$eq2->RunQuery(true, $sql) ) {
				die("Error inserting " . $_POST['orig_object'] . " into spawn_scripts table.");
			}
		}
	
		$p = array('spawn_scripts',$_POST['orig_object'],$sql);
	}

	public function GetNewsTypeNameByID($id)
	{
		global $eq2;

		$query = "SELECT emu_name AS name FROM `eq2news_types` WHERE emu_type='" . $id . "'";
		$data = $eq2->RunQuerySingle($query);

		return($data['name']);
	}

	public function GetNewsSubTypeNameByID($id)
	{
		global $eq2;

		$query = "SELECT emu_name AS name FROM `eq2news_types` WHERE emu_type='" . $id . "'";
		$data = $eq2->RunQuerySingle($query);
		
		return($data['name']);
	}

	public function GetNewsTypes()
	{
		global $eq2;

		$query = "SELECT emu_type AS id, emu_name AS name FROM `eq2news_types` WHERE emu_parent='0'";
		$data = $eq2->RunQueryMulti($query);
		
		return($data);
	}

	public function GetNewsSubTypes()
	{
		global $eq2;

		$query = "SELECT emu_type AS id, emu_name AS name FROM `eq2news_types` WHERE emu_parent<>'0'";
		$data = $eq2->RunQueryMulti($query);
		
		return($data);
	}
}
?>
