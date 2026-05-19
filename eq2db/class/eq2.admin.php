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
	
	public function HasSOEQuestSchema()
	{
		global $eq2;
		
		$schema = $eq2->SQLEscape(SOE_DATA);
		$required = array(
			'quest_list',
			'quest_stage_list',
			'quest_branch_items',
			'quest_reward_list',
			'quest_reward_item_list',
			'quest_reward_faction_list'
		);
		
		$quoted = array();
		foreach($required as $table)
			$quoted[] = "'" . $eq2->SQLEscape($table) . "'";
		
		$eq2->SQLQuery = sprintf(
			"SELECT COUNT(DISTINCT TABLE_NAME) AS cnt FROM information_schema.tables WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME IN (%s);",
			$schema,
			implode(',', $quoted)
		);
		$data = $eq2->RunQuerySingle('', true, true);
		return is_array($data) && (int)$data['cnt'] === count($required);
	}
	
	public function GetSOEQuestCategories()
	{
		global $eq2;
		
		if( !$this->HasSOEQuestSchema() )
			return array();
		
		$eq2->SQLQuery = "SELECT DISTINCT category FROM `".SOE_DATA."`.quest_list ORDER BY category";
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestData()
	{
		global $eq2;
		
		if( !$this->HasSOEQuestSchema() )
		{
			$this->total_rows = 0;
			return array();
		}
		
		$query = "SELECT quest_id, soe_quest_crc, name, category, tier, level FROM `".SOE_DATA."`.quest_list";
		
		if( isset($_GET['category']) && strlen($_GET['category']) > 0 )
			$query .= sprintf(" WHERE category = '%s'", $eq2->SQLEscape($_GET['category']));
			
		$eq2->SQLQuery = $query . " ORDER BY level, tier, name";
		$ret = $eq2->RunQueryMulti();
		
		$this->total_rows = is_array($ret) ? count($ret) : 0;
		
		return is_array($ret) ? $ret : array();
	}
	
	public function GetSOEQuest($id)
	{
		global $eq2;
		
		if( !$this->HasSOEQuestSchema() )
			return array();
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".SOE_DATA."`.quest_list WHERE quest_id = %d", (int)$id);
		return $eq2->RunQuerySingle();
	}
	
	public function GetSOEQuestStages($id)
	{
		global $eq2;
		
		if( !$this->HasSOEQuestSchema() )
			return array();
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".SOE_DATA."`.quest_stage_list WHERE quest_id_fk = %d ORDER BY stage_num", (int)$id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestStageBranches($id)
	{
		global $eq2;
		
		if( !$this->HasSOEQuestSchema() )
			return array();
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".SOE_DATA."`.quest_branch_items WHERE quest_id_fk = %d ORDER BY stage_num", (int)$id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestRewards($id)
	{
		global $eq2;
		
		if( !$this->HasSOEQuestSchema() )
			return array();
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".SOE_DATA."`.quest_reward_list WHERE quest_id_fk = %d", (int)$id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestRewardItems($id)
	{
		global $eq2;
		
		if( !$this->HasSOEQuestSchema() )
			return array();
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".SOE_DATA."`.quest_reward_item_list WHERE quest_reward_id_fk = %d", (int)$id);
		return $eq2->RunQueryMulti();
	}
	
	public function GetSOEQuestRewardFactions($id)
	{
		global $eq2;
		
		if( !$this->HasSOEQuestSchema() )
			return array();
		
		$eq2->SQLQuery = sprintf("SELECT * FROM `".SOE_DATA."`.quest_reward_faction_list WHERE quest_reward_id_fk = %d", (int)$id);
		return $eq2->RunQueryMulti();
	}
	
	private function QuestFixNormalizeWhitespace($text)
	{
		$text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8');
		$text = preg_replace('/\s+/u', ' ', $text);
		return trim($text);
	}
	
	private function QuestFixStripArticle($text)
	{
		return preg_replace('/^(?:a|an|the|some|any|many|several|more)\s+/iu', '', trim((string)$text));
	}
	
	private function QuestFixCountFromToken($token)
	{
		$token = strtolower(trim((string)$token));
		if( ctype_digit($token) )
			return (int)$token;
		
		$map = array(
			'one'=>1,'two'=>2,'three'=>3,'four'=>4,'five'=>5,'six'=>6,'seven'=>7,'eight'=>8,'nine'=>9,'ten'=>10,
			'eleven'=>11,'twelve'=>12,'thirteen'=>13,'fourteen'=>14,'fifteen'=>15,'sixteen'=>16,'seventeen'=>17,
			'eighteen'=>18,'nineteen'=>19,'twenty'=>20,'thirty'=>30,'forty'=>40,'fifty'=>50
		);
		return isset($map[$token]) ? $map[$token] : 1;
	}
	
	private function QuestFixSingularizeLastWord($phrase)
	{
		$phrase = trim((string)$phrase);
		if( $phrase === '' )
			return '';
		
		$parts = preg_split('/\s+/u', $phrase);
		$last = array_pop($parts);
		$lower = strtolower($last);
		$singular = $last;
		
		if( preg_match('/ies$/iu', $last) )
			$singular = preg_replace('/ies$/iu', 'y', $last);
		elseif( preg_match('/sses$/iu', $last) )
			$singular = preg_replace('/es$/iu', '', $last);
		elseif( preg_match('/(ches|shes|xes|zes)$/iu', $last) )
			$singular = preg_replace('/es$/iu', '', $last);
		elseif( preg_match('/s$/iu', $last) && !preg_match('/ss$/iu', $last) )
			$singular = preg_replace('/s$/iu', '', $last);
		
		$parts[] = $singular;
		return trim(implode(' ', $parts));
	}
	
	public function QuestFixBuildSearchVariants($target)
	{
		$target = $this->QuestFixNormalizeWhitespace($target);
		$target = trim($target, " \t\n\r\0\x0B.,;:!?\"'");
		$target = $this->QuestFixStripArticle($target);
		if( $target === '' )
			return array();

		// Build both the raw phrase and safer trimmed variants. Quest text often carries
		// descriptive tails that are useful to a human, but poison DB name matching:
		//   "Blackshield assassins looking for Nikora" -> "Blackshield assassins"
		//   "lost soul and recover ectoplasmic goo" -> "lost soul"
		$bases = array($target);
		$trimmed = preg_replace('/\s+(?:looking\s+for|searching\s+for|hunting\s+for|guarding|attacking|after|near|at|around|inside|outside|within|by|with|from|to|in)\b.*$/iu', '', $target);
		$trimmed = trim((string)$trimmed, " \t\n\r\0\x0B.,;:!?\"'");
		if( $trimmed !== '' )
			$bases[] = $trimmed;
		$actionTailTrimmed = preg_replace('/\s+and\s+(?:recover|collect|retrieve|obtain|gather|loot|take|bring|deliver)\b.*$/iu', '', $target);
		$actionTailTrimmed = trim((string)$actionTailTrimmed, " \t\n\r\0\x0B.,;:!?\"'");
		if( $actionTailTrimmed !== '' )
			$bases[] = $actionTailTrimmed;

		// Titles/roles are often followed by the actionable proper name after a comma:
		//   "old nomad, Gierasa" -> "Gierasa".
		if( strpos($target, ',') !== false )
		{
			$parts = array_map('trim', explode(',', $target));
			foreach($parts as $part)
			{
				$part = trim((string)$part, " \t\n\r\0\x0B.,;:!?\"'");
				if( $part !== '' )
					$bases[] = $part;
			}
		}

		// Possessive targets often encode a relationship while the DB stores either the
		// owner name or the role noun. Keep all three safely:
		//   "D'Verin's Mistress" -> "D'Verin", "Mistress".
		if( preg_match('/^(.+?)\'s\s+(.+)$/u', $target, $pm) )
		{
			$owner = trim((string)$pm[1], " \t\n\r\0\x0B.,;:!?\"'");
			$role = trim((string)$pm[2], " \t\n\r\0\x0B.,;:!?\"'");
			if( $owner !== '' )
				$bases[] = $owner;
			if( $role !== '' )
				$bases[] = $role;
		}

		$variants = array();
		foreach(array_values(array_unique($bases)) as $base)
		{
			$base = trim((string)$base);
			if( $base === '' )
				continue;
			$singular = $this->QuestFixSingularizeLastWord($base);
			foreach(array($base, $singular) as $value)
			{
				$value = trim((string)$value);
				if( $value === '' )
					continue;
				$quoteAliases = array($value);
				if( strpos($value, "'") !== false )
					$quoteAliases[] = str_replace("'", '`', $value);
				if( strpos($value, '`') !== false )
					$quoteAliases[] = str_replace('`', "'", $value);
				foreach(array_values(array_unique($quoteAliases)) as $alias)
				{
					$variants[] = $alias;
					$variants[] = 'a ' . $alias;
					$variants[] = 'an ' . $alias;
					$variants[] = 'the ' . $alias;
				}
			}
		}

		$ret = array();
		foreach($variants as $variant)
		{
			$key = strtolower(trim((string)$variant));
			if( $key !== '' && !isset($ret[$key]) )
				$ret[$key] = trim((string)$variant);
		}
		return array_values($ret);
	}
	
	public function QuestFixStepTypes()
	{
		return array('Chat', 'Craft', 'Generic', 'Harvest', 'Kill', 'Location', 'Obtain Item', 'Spell', 'Unknown');
	}
	
	private function QuestFixExtractZoneHint($clean)
	{
		if( preg_match('/\b(?:in|within|at|around|near|by|inside|outside)\s+(?:the\s+)?(.+?)(?=\s+(?:for|from|to|with)\b|[.!?]|$)/iu', $clean, $z) )
			return trim($z[1], " \t\n\r\0\x0B.,;:!?\"'");
		return '';
	}
	
	private function QuestFixExtractTargetByPattern($clean, $pattern)
	{
		if( preg_match($pattern, $clean, $m) )
			return trim($m[1], " \t\n\r\0\x0B.,;:!?\"'");
		return '';
	}
	
	private function QuestFixLooksLikePersonTarget($target)
	{
		$target = $this->QuestFixNormalizeWhitespace($target);
		if( $target === '' )
			return false;
		$lo = strtolower($target);

		// Generic person/NPC-role signals. These are intentionally role-based, not quest-specific.
		if( preg_match('/\b(?:advisor|archivist|artisan|bartender|captain|chef|clerk|commander|corporal|crafter|elder|emissary|farmer|fisherman|guard|healer|herbalist|historian|innkeeper|keeper|knight|librarian|lieutenant|lord|lady|master|mayor|merchant|miner|monk|oracle|priest|priestess|professor|quartermaster|ranger|recruit|sage|scholar|scout|sergeant|smith|soldier|speaker|trainer|warden|wizard)\b/iu', $lo) )
			return true;

		// Honorifics or titles followed by a name are usually NPC/chat targets.
		if( preg_match('/\b(?:mr|mrs|ms|sir|lady|lord|captain|commander|elder|master|professor|sergeant)\.?\s+[A-Z][A-Za-z\'\-]+/u', $target) )
			return true;

		// Two or three title-cased words with no obvious POI/location noun are often NPC names.
		if( preg_match('/^[A-Z][A-Za-z\'\-]+(?:\s+[A-Z][A-Za-z\'\-]+){1,2}$/u', $target) )
		{
			if( !preg_match('/\b(?:bridge|cave|camp|citadel|door|entrance|farm|forest|gate|gates|grove|hall|hill|isle|keep|lake|lair|monument|pass|pond|road|ruin|ruins|shrine|spire|stormhold|tower|vale|village|wood|woods)\b/iu', $lo) )
				return true;
		}
		return false;
	}

	private function QuestFixFindPersonTarget($text)
	{
		$clean = $this->QuestFixNormalizeWhitespace($text);
		if( $clean === '' )
			return '';
		$target = $this->QuestFixExtractTargetByPattern($clean,
			'/\bfind\s+(?:the\s+)?(.+?)(?=\s+(?:at|in|near|by|within|outside|inside)\b|[.!?]|$)/iu');
		if( $target !== '' && $this->QuestFixLooksLikePersonTarget($target) )
			return $target;
		return '';
	}

	public function QuestFixAnalyzeText($text)
	{
		$clean = $this->QuestFixNormalizeWhitespace($text);
		$ret = array(
			'raw_text' => $clean,
			'step_type' => 'Unknown',
			'count' => 1,
			'target' => '',
			'target_singular' => '',
			'zone' => '',
			'search_variants' => array(),
			'confidence' => 0,
			'notes' => array(),
			'auto_type' => 'Unknown',
			'manual_override' => false
		);
		
		if( $clean === '' )
		{
			$ret['notes'][] = 'No step text supplied.';
			return $ret;
		}
		
		$ret['zone'] = $this->QuestFixExtractZoneHint($clean);
		if( $ret['zone'] !== '' )
			$ret['confidence'] += 5;
		
		$killVerb = '(?:slay|kill|defeat|destroy|hunt|eliminate|vanquish|dispatch|battle|fight)';
		$chatVerb = '(?:speak\s+(?:to|with)|talk\s+(?:to|with)|return\s+to|report\s+to|deliver(?:\s+[^.!?]{0,45})?\s+to|inform)';
		$locationVerb = '(?:go\s+to|travel\s+to|visit|find|reach|locate|search(?:\s+for)?|investigate|check|discover|enter)';
		$harvestVerb = '(?:harvest|mine|quarry|chop|forage)';
		$craftVerb = '(?:craft|create|forge|brew|cook|synthesize|make)';
		$spellVerb = '(?:cast|chant|invoke|use\s+(?:the\s+)?spell|poison\s+the|bless\s+the)';
		$genericVerb = '(?:inspect|examine|activate|click|touch|place|plant|use\s+(?:the\s+)?(?:device|object|lever|bell|stone|crate|book|scroll|altar|totem|idol|orb|portal))';
		$obtainVerb = '(?:collect|obtain|acquire|retrieve|recover|gather|buy|purchase|pick\s+up|loot|find)';
		
		$findPersonTarget = $this->QuestFixFindPersonTarget($clean);
		if( $findPersonTarget !== '' )
		{
			$ret['step_type'] = 'Chat';
			$ret['confidence'] += 62;
			$ret['target'] = $findPersonTarget;
			$ret['notes'][] = 'Find-target looks like a person/NPC role, so Chat outranks generic Location.';
		}
		elseif( preg_match('/\b' . $killVerb . '\b/iu', $clean) )
		{
			$ret['step_type'] = 'Kill';
			$ret['confidence'] += 55;
			$pattern = '/\b' . $killVerb . '\s+(?:(\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\s+)?(.+?)(?=\s+(?:in|within|at|around|near|by)\s+(?:the\s+)?|[.!?]|$)/iu';
			if( preg_match($pattern, $clean, $m) )
			{
				$ret['count'] = isset($m[1]) && trim($m[1]) !== '' ? $this->QuestFixCountFromToken($m[1]) : 1;
				$ret['target'] = trim($m[2], " \t\n\r\0\x0B.,;:!?\"'");
			}
		}
		elseif( preg_match('/\b' . $chatVerb . '\b/iu', $clean) )
		{
			$ret['step_type'] = 'Chat';
			$ret['confidence'] += 55;
			$ret['target'] = $this->QuestFixExtractTargetByPattern($clean,
				'/\b(?:speak\s+(?:to|with)|talk\s+(?:to|with)|return\s+to|report\s+to|inform)\s+(?:the\s+)?(.+?)(?=\s+(?:at|in|near|by|within|outside|inside)\b|[.!?]|$)/iu');
			if( $ret['target'] === '' )
				$ret['target'] = $this->QuestFixExtractTargetByPattern($clean,
					'/\bdeliver(?:\s+[^.!?]{0,45})?\s+to\s+(?:the\s+)?(.+?)(?=\s+(?:at|in|near|by|within|outside|inside)\b|[.!?]|$)/iu');
		}
		elseif( preg_match('/\b' . $craftVerb . '\b/iu', $clean) )
		{
			$ret['step_type'] = 'Craft';
			$ret['confidence'] += 45;
			$ret['target'] = $this->QuestFixExtractTargetByPattern($clean,
				'/\b(?:to\s+)?(?:craft|create|brew|cook|synthesize|make)\s+(?:(?:a|an|the|some)\s+)?(.+?)(?=\s+(?:using|with|for|in|at)\b|[.!?]|$)/iu');
			if( $ret['target'] === '' )
				$ret['target'] = $this->QuestFixExtractTargetByPattern($clean,
					'/\bforge\s+(?:(?:a|an|the|some)\s+)?(.+?)(?=\s+(?:using|with|for|in|at)\b|[.!?]|$)/iu');
		}
		elseif( preg_match('/\b' . $harvestVerb . '\b/iu', $clean) )
		{
			$ret['step_type'] = 'Harvest';
			$ret['confidence'] += 45;
			$ret['target'] = $this->QuestFixExtractTargetByPattern($clean,
				'/\b(?:harvest|mine|quarry|chop|forage)\s+(?:(\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\s+)?(.+?)(?=\s+(?:in|within|at|around|near|for|from)\b|[.!?]|$)/iu');
			if( preg_match('/\b' . $harvestVerb . '\s+(?:(\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\s+)?(.+?)(?=\s+(?:in|within|at|around|near|for|from)\b|[.!?]|$)/iu', $clean, $m) )
			{
				$ret['count'] = isset($m[1]) && trim($m[1]) !== '' ? $this->QuestFixCountFromToken($m[1]) : 1;
				$ret['target'] = trim($m[2], " \t\n\r\0\x0B.,;:!?\"'");
			}
		}
		elseif( preg_match('/\b' . $spellVerb . '\b/iu', $clean) )
		{
			$ret['step_type'] = 'Spell';
			$ret['confidence'] += 40;
			$ret['target'] = $this->QuestFixExtractTargetByPattern($clean,
				'/\b(?:cast|chant|invoke|use\s+(?:the\s+)?spell|poison\s+the|bless\s+the)\s+(.+?)(?=\s+(?:on|at|in|near|with)\b|[.!?]|$)/iu');
		}
		elseif( preg_match('/\b' . $genericVerb . '\b/iu', $clean) )
		{
			$ret['step_type'] = 'Generic';
			$ret['confidence'] += 35;
			$ret['target'] = $this->QuestFixExtractTargetByPattern($clean,
				'/\b(?:inspect|examine|activate|click|touch|place|plant|use)\s+(?:(?:a|an|the|some)\s+)?(.+?)(?=\s+(?:at|in|near|by|within|outside|inside|on)\b|[.!?]|$)/iu');
		}
		elseif( preg_match('/\b' . $locationVerb . '\b/iu', $clean) )
		{
			$ret['step_type'] = 'Location';
			$ret['confidence'] += 45;
			$ret['target'] = $this->QuestFixExtractTargetByPattern($clean,
				'/\b(?:go\s+to|travel\s+to|visit|find|reach|locate|search(?:\s+for)?|investigate|check|discover|enter)\s+(?:(?:a|an|the|some)\s+)?(.+?)(?=\s+(?:in|at|near|by|within|outside|inside)\b|[.!?]|$)/iu');
		}
		elseif( preg_match('/\b' . $obtainVerb . '\b/iu', $clean) )
		{
			$ret['step_type'] = 'Obtain Item';
			$ret['confidence'] += 40;
			if( preg_match('/\b' . $obtainVerb . '\s+(?:(\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\s+)?(.+?)(?=\s+(?:from|in|within|at|around|near|for)\b|[.!?]|$)/iu', $clean, $m) )
			{
				$ret['count'] = isset($m[1]) && trim($m[1]) !== '' ? $this->QuestFixCountFromToken($m[1]) : 1;
				$ret['target'] = trim($m[2], " \t\n\r\0\x0B.,;:!?\"'");
			}
		}
		else
		{
			$ret['step_type'] = 'Unknown';
			$ret['notes'][] = 'No high-confidence step type phrase matched.';
		}
		
		if( $ret['target'] !== '' )
		{
			$ret['target_singular'] = $this->QuestFixSingularizeLastWord($this->QuestFixStripArticle($ret['target']));
			$ret['search_variants'] = $this->QuestFixBuildSearchVariants($ret['target']);
			$ret['confidence'] += 25;
		}
		
		$ret['auto_type'] = $ret['step_type'];
		if( $ret['confidence'] > 100 )
			$ret['confidence'] = 100;
		return $ret;
	}

	private function QuestFixNormalizedCompare($text)
	{
		$text = strtolower($this->QuestFixNormalizeWhitespace($text));
		$text = trim($text, " \t\n\r\0\x0B.,;:!?\"'");
		return $text;
	}
	
	public function QuestFixFindSpawnCandidates($target, $zone = '', $limit = 12)
	{
		global $eq2;
		
		$variants = $this->QuestFixBuildSearchVariants($target);
		if( count($variants) === 0 )
			return array();
		
		$where = array();
		foreach($variants as $variant)
		{
			$needle = $eq2->SQLEscape(strtolower($variant));
			$where[] = "LOWER(s.name) LIKE '%" . $needle . "%'";

			// Multi-token target phrases often match DB spawn names with inserted qualifiers.
			// Example: "orc lieutenant" should still find "a fallen orc lieutenant".
			$variantTokens = preg_split('/\s+/u', strtolower($this->QuestFixNormalizeWhitespace($variant)));
			$variantTokenWhere = array();
			foreach((array)$variantTokens as $variantToken)
			{
				$variantToken = trim((string)$variantToken, " \t\n\r\0\x0B.,;:!?\"'");
				if( $variantToken === '' || strlen($variantToken) < 3 || in_array($variantToken, array('the','some','any','more','many','several'), true) )
					continue;
				$variantTokenWhere[strtolower($variantToken)] = "LOWER(s.name) LIKE '%" . $eq2->SQLEscape(strtolower($variantToken)) . "%'";
			}
			if( count($variantTokenWhere) >= 2 )
				$where[] = '(' . implode(' AND ', array_values($variantTokenWhere)) . ')';
		}
		
		$eq2->SQLQuery = sprintf(
			"SELECT DISTINCT s.id, s.name, s.attackable, z.id AS zone_id, z.name AS zone_name, z.description AS zone_description " .
			"FROM `%s`.spawn s " .
			"LEFT JOIN `%s`.spawn_location_entry sle ON sle.spawn_id = s.id " .
			"LEFT JOIN `%s`.spawn_location_placement slp ON slp.spawn_location_id = sle.spawn_location_id " .
			"LEFT JOIN `%s`.zones z ON z.id = slp.zone_id " .
			"WHERE (%s) LIMIT 160;",
			ACTIVE_DB, ACTIVE_DB, ACTIVE_DB, ACTIVE_DB, implode(' OR ', $where)
		);
		$rows = $eq2->RunQueryMulti();
		if( !is_array($rows) )
			return array();
		
		$zoneNorm = $this->QuestFixNormalizedCompare($zone);
		$targetNorm = $this->QuestFixNormalizedCompare($target);
		$singularNorm = $this->QuestFixNormalizedCompare($this->QuestFixSingularizeLastWord($this->QuestFixStripArticle($target)));
		$variantNorms = array();
		foreach($variants as $variant)
			$variantNorms[] = $this->QuestFixNormalizedCompare($variant);
		
		$ret = array();
		foreach($rows as $row)
		{
			$nameNorm = $this->QuestFixNormalizedCompare($row['name']);
			$nameNoArticle = $this->QuestFixNormalizedCompare($this->QuestFixStripArticle($row['name']));
			$zoneNameNorm = $this->QuestFixNormalizedCompare($row['zone_name']);
			$zoneDescNorm = $this->QuestFixNormalizedCompare($row['zone_description']);
			$score = 0;
			$reasons = array();
			if( in_array($nameNorm, $variantNorms, true) )
			{
				$score += 100;
				$reasons[] = 'exact spawn-name variant';
			}
			if( $singularNorm !== '' && $nameNoArticle === $singularNorm )
			{
				$score += 80;
				$reasons[] = 'exact singular target';
			}
			elseif( $singularNorm !== '' && strpos($nameNoArticle, $singularNorm) !== false )
			{
				$score += 45;
				$reasons[] = 'name contains singular target';
			}
			elseif( $targetNorm !== '' && strpos($nameNorm, $targetNorm) !== false )
			{
				$score += 35;
				$reasons[] = 'name contains raw target';
			}

			// Strong fallback for phrases whose words all occur in a spawn name, even when
			// the DB inserts a qualifier between them: "orc lieutenant" -> "fallen orc lieutenant".
			$bestTokenCoverage = 0;
			$bestTokenCoverageLabel = '';
			foreach($variants as $variant)
			{
				$coverageTokens = preg_split('/\s+/u', strtolower($this->QuestFixNormalizeWhitespace($variant)));
				$coverageUseful = array();
				foreach((array)$coverageTokens as $coverageToken)
				{
					$coverageToken = trim((string)$coverageToken, " \t\n\r\0\x0B.,;:!?\"'");
					if( $coverageToken === '' || strlen($coverageToken) < 3 || in_array($coverageToken, array('the','some','any','more','many','several'), true) )
						continue;
					$coverageUseful[strtolower($coverageToken)] = strtolower($coverageToken);
				}
				$coverageUseful = array_values($coverageUseful);
				if( count($coverageUseful) < 2 )
					continue;
				$coverageHits = 0;
				foreach($coverageUseful as $coverageToken)
					if( strpos($nameNoArticle, $coverageToken) !== false )
						$coverageHits++;
				if( $coverageHits === count($coverageUseful) )
				{
					$coverageScore = 72 + min(54, (count($coverageUseful) - 2) * 18);
					if( $coverageScore > $bestTokenCoverage )
					{
						$bestTokenCoverage = $coverageScore;
						$bestTokenCoverageLabel = (string)$variant;
					}
				}
			}
			if( $bestTokenCoverage > 0 )
			{
				$score += $bestTokenCoverage;
				$reasons[] = 'all phrase tokens present: ' . $bestTokenCoverageLabel;
			}
			
			if( $zoneNorm !== '' )
			{
				if( $zoneNameNorm === $zoneNorm || $zoneDescNorm === $zoneNorm )
				{
					$score += 70;
					$reasons[] = 'exact zone match';
				}
				elseif( ($zoneNameNorm !== '' && strpos($zoneNameNorm, $zoneNorm) !== false) || ($zoneDescNorm !== '' && strpos($zoneDescNorm, $zoneNorm) !== false) )
				{
					$score += 50;
					$reasons[] = 'zone contains hint';
				}
			}
			
			if( isset($row['attackable']) && (int)$row['attackable'] === 1 )
			{
				$score += 5;
				$reasons[] = 'attackable';
			}
			
			$row['score'] = $score;
			$row['score_reasons'] = implode(', ', $reasons);
			$ret[] = $row;
		}
		
		usort($ret, function($a, $b) {
			if( (int)$a['score'] === (int)$b['score'] )
				return strcmp((string)$a['name'], (string)$b['name']);
			return ((int)$a['score'] > (int)$b['score']) ? -1 : 1;
		});
		
		$limit = max(1, min(50, (int)$limit));
		return array_slice($ret, 0, $limit);
	}
	

	public function QuestFixFindChatCandidates($target, $zone = '', $limit = 12)
	{
		global $eq2;
		$variants = $this->QuestFixBuildSearchVariants($target);
		if( count($variants) === 0 )
			return array();
		$where = array();
		foreach($variants as $variant)
		{
			$needle = $eq2->SQLEscape(strtolower($variant));
			$where[] = "LOWER(s.name) LIKE '%" . $needle . "%'";
		}
		$eq2->SQLQuery = sprintf(
			"SELECT DISTINCT s.id, s.name, s.attackable, z.id AS zone_id, z.name AS zone_name, z.description AS zone_description " .
			"FROM `%s`.spawn s " .
			"LEFT JOIN `%s`.spawn_location_entry sle ON sle.spawn_id = s.id " .
			"LEFT JOIN `%s`.spawn_location_placement slp ON slp.spawn_location_id = sle.spawn_location_id " .
			"LEFT JOIN `%s`.zones z ON z.id = slp.zone_id " .
			"WHERE (%s) LIMIT 160;",
			ACTIVE_DB, ACTIVE_DB, ACTIVE_DB, ACTIVE_DB, implode(' OR ', $where)
		);
		$rows = $eq2->RunQueryMulti();
		if( !is_array($rows) )
			return array();
		$zoneNorm = $this->QuestFixNormalizedCompare($zone);
		$targetNorm = $this->QuestFixNormalizedCompare($target);
		$singularNorm = $this->QuestFixNormalizedCompare($this->QuestFixSingularizeLastWord($this->QuestFixStripArticle($target)));
		$variantNorms = array();
		foreach($variants as $variant)
			$variantNorms[] = $this->QuestFixNormalizedCompare($variant);
		$ret = array();
		foreach($rows as $row)
		{
			$nameNorm = $this->QuestFixNormalizedCompare($row['name']);
			$nameNoArticle = $this->QuestFixNormalizedCompare($this->QuestFixStripArticle($row['name']));
			$zoneNameNorm = $this->QuestFixNormalizedCompare($row['zone_name']);
			$zoneDescNorm = $this->QuestFixNormalizedCompare($row['zone_description']);
			$score = 0;
			$reasons = array();
			if( in_array($nameNorm, $variantNorms, true) )
			{
				$score += 110;
				$reasons[] = 'exact NPC-name variant';
			}
			if( $singularNorm !== '' && $nameNoArticle === $singularNorm )
			{
				$score += 90;
				$reasons[] = 'exact singular NPC target';
			}
			elseif( $singularNorm !== '' && strpos($nameNoArticle, $singularNorm) !== false )
			{
				$score += 45;
				$reasons[] = 'NPC name contains target';
			}
			elseif( $targetNorm !== '' && strpos($nameNorm, $targetNorm) !== false )
			{
				$score += 35;
				$reasons[] = 'NPC name contains raw target';
			}
			if( $zoneNorm !== '' )
			{
				if( $zoneNameNorm === $zoneNorm || $zoneDescNorm === $zoneNorm )
				{
					$score += 70;
					$reasons[] = 'exact zone match';
				}
				elseif( ($zoneNameNorm !== '' && strpos($zoneNameNorm, $zoneNorm) !== false) || ($zoneDescNorm !== '' && strpos($zoneDescNorm, $zoneNorm) !== false) )
				{
					$score += 50;
					$reasons[] = 'zone contains hint';
				}
			}
			if( isset($row['attackable']) && (int)$row['attackable'] === 0 )
			{
				$score += 7;
				$reasons[] = 'non-attackable/chat-like';
			}
			$row['score'] = $score;
			$row['score_reasons'] = implode(', ', $reasons);
			$ret[] = $row;
		}
		usort($ret, function($a, $b) {
			if( (int)$a['score'] === (int)$b['score'] )
				return strcmp((string)$a['name'], (string)$b['name']);
			return ((int)$a['score'] > (int)$b['score']) ? -1 : 1;
		});
		$limit = max(1, min(50, (int)$limit));
		return array_slice($ret, 0, $limit);
	}
	
	private function QuestFixFindLocalQuestByName($questName)
	{
		global $eq2;
		$questName = trim((string)$questName);
		if( $questName === '' )
			return array();
		$needle = $eq2->SQLEscape($questName);
		$eq2->SQLQuery = sprintf(
			"SELECT quest_id AS id, name, lua_script, type, zone, level, spawn_id FROM `%s`.quests WHERE LOWER(name) = LOWER('%s') ORDER BY quest_id ASC LIMIT 1;",
			ACTIVE_DB, $needle
		);
		$row = $eq2->RunQuerySingle();
		return is_array($row) ? $row : array();
	}
	
	private function QuestFixServerScriptRoots()
	{
		$roots = array(
			'/eq2emu/eq2emu/server',
			'/eq2emu/server',
			dirname(__DIR__, 4) . '/server',
			dirname(__DIR__, 5) . '/server'
		);
		$ret = array();
		foreach($roots as $root)
		{
			$root = rtrim((string)$root, '/');
			if( $root !== '' && is_dir($root) && !in_array($root, $ret, true) )
				$ret[] = $root;
		}
		return $ret;
	}
	
	private function QuestFixResolveScriptPath($luaScript)
	{
		$luaScript = ltrim(trim((string)$luaScript), '/');
		if( $luaScript === '' )
			return '';
		foreach($this->QuestFixServerScriptRoots() as $root)
		{
			$path = $root . '/' . $luaScript;
			if( is_file($path) && is_readable($path) )
				return $path;
		}
		return '';
	}
	
	private function QuestFixLuaTopLevelArgs($inside)
	{
		$inside = (string)$inside;
		$args = array();
		$buf = '';
		$depth = 0;
		$quote = '';
		$escape = false;
		$len = strlen($inside);
		for($i = 0; $i < $len; $i++)
		{
			$ch = $inside[$i];
			if( $quote !== '' )
			{
				$buf .= $ch;
				if( $escape )
				{
					$escape = false;
					continue;
				}
				if( $ch === '\\' )
				{
					$escape = true;
					continue;
				}
				if( $ch === $quote )
					$quote = '';
				continue;
			}
			if( $ch === '"' || $ch === "'" )
			{
				$quote = $ch;
				$buf .= $ch;
				continue;
			}
			if( $ch === '(' || $ch === '{' || $ch === '[' )
			{
				$depth++;
				$buf .= $ch;
				continue;
			}
			if( $ch === ')' || $ch === '}' || $ch === ']' )
			{
				if( $depth > 0 )
					$depth--;
				$buf .= $ch;
				continue;
			}
			if( $ch === ',' && $depth === 0 )
			{
				$args[] = trim($buf);
				$buf = '';
				continue;
			}
			$buf .= $ch;
		}
		if( trim($buf) !== '' || count($args) > 0 )
			$args[] = trim($buf);
		return $args;
	}

	private function QuestFixLuaFunctionCalls($scriptText, $functionName)
	{
		$scriptText = (string)$scriptText;
		$functionName = trim((string)$functionName);
		$ret = array();
		if( $scriptText === '' || $functionName === '' )
			return $ret;
		$needle = $functionName . '(';
		$offset = 0;
		$len = strlen($scriptText);
		while( ($pos = strpos($scriptText, $needle, $offset)) !== false )
		{
			$start = $pos + strlen($needle);
			$depth = 1;
			$quote = '';
			$escape = false;
			$end = null;
			for($i = $start; $i < $len; $i++)
			{
				$ch = $scriptText[$i];
				if( $quote !== '' )
				{
					if( $escape )
					{
						$escape = false;
						continue;
					}
					if( $ch === '\\' )
					{
						$escape = true;
						continue;
					}
					if( $ch === $quote )
						$quote = '';
					continue;
				}
				if( $ch === '"' || $ch === "'" )
				{
					$quote = $ch;
					continue;
				}
				if( $ch === '(' )
				{
					$depth++;
					continue;
				}
				if( $ch === ')' )
				{
					$depth--;
					if( $depth === 0 )
					{
						$end = $i;
						break;
					}
				}
			}
			if( $end === null )
				break;
			$ret[] = substr($scriptText, $start, $end - $start);
			$offset = $end + 1;
		}
		return $ret;
	}

	private function QuestFixLuaNumericToken($token)
	{
		$token = trim((string)$token);
		return preg_match('/^-?\\d+$/', $token) ? (int)$token : null;
	}

	private function QuestFixLocalStepTypeEvidence($luaScript, $stepNumber)
	{
		$luaScript = trim((string)$luaScript);
		$stepNumber = (int)$stepNumber;
		$ret = array(
			'found' => false,
			'type' => '',
			'function' => ''
		);
		if( $luaScript === '' || $stepNumber <= 0 )
			return $ret;
		$path = $this->QuestFixResolveScriptPath($luaScript);
		if( $path === '' )
			return $ret;
		$script = @file_get_contents($path);
		if( $script === false || trim((string)$script) === '' )
			return $ret;

		$checks = array(
			'Kill' => 'AddQuestStepKill',
			'Chat' => 'AddQuestStepChat',
			'Location' => 'AddQuestStepZoneLoc'
		);
		foreach($checks as $type => $function)
		{
			foreach($this->QuestFixLuaFunctionCalls($script, $function) as $inside)
			{
				$args = $this->QuestFixLuaTopLevelArgs($inside);
				if( count($args) < 2 )
					continue;
				$callStep = $this->QuestFixLuaNumericToken($args[1]);
				if( $callStep === null || (int)$callStep !== $stepNumber )
					continue;
				$ret['found'] = true;
				$ret['type'] = $type;
				$ret['function'] = $function;
				return $ret;
			}
		}
		return $ret;
	}

	private function QuestFixLocalStepCallEvidence($luaScript, $stepNumber, $type)
	{
		$luaScript = trim((string)$luaScript);
		$stepNumber = (int)$stepNumber;
		$type = trim((string)$type);
		$ret = array(
			'found' => false,
			'ids' => array(),
			'count' => 0,
			'icon_id' => 0,
			'progress_percent' => 100,
			'function' => ''
		);
		if( $luaScript === '' || $stepNumber <= 0 || ($type !== 'Kill' && $type !== 'Chat') )
			return $ret;
		$path = $this->QuestFixResolveScriptPath($luaScript);
		if( $path === '' )
			return $ret;
		$script = @file_get_contents($path);
		if( $script === false || trim((string)$script) === '' )
			return $ret;
		$function = $type === 'Kill' ? 'AddQuestStepKill' : 'AddQuestStepChat';
		foreach($this->QuestFixLuaFunctionCalls($script, $function) as $inside)
		{
			$args = $this->QuestFixLuaTopLevelArgs($inside);
			if( count($args) < 2 )
				continue;
			$callStep = $this->QuestFixLuaNumericToken($args[1]);
			if( $callStep === null || (int)$callStep !== $stepNumber )
				continue;
			$ids = array();
			if( $type === 'Kill' )
			{
				if( isset($args[3]) )
				{
					$count = $this->QuestFixLuaNumericToken($args[3]);
					if( $count !== null && $count > 0 )
						$ret['count'] = (int)$count;
				}
				if( isset($args[4]) )
				{
					$progress = $this->QuestFixLuaNumericToken($args[4]);
					if( $progress !== null && $progress > 0 )
						$ret['progress_percent'] = (int)$progress;
				}
				if( isset($args[6]) )
				{
					$icon = $this->QuestFixLuaNumericToken($args[6]);
					if( $icon !== null && $icon >= 0 )
						$ret['icon_id'] = (int)$icon;
				}
				for($i = 7; $i < count($args); $i++)
				{
					$id = $this->QuestFixLuaNumericToken($args[$i]);
					if( $id !== null && $id > 0 && !in_array((int)$id, $ids, true) )
						$ids[] = (int)$id;
				}
			}
			else
			{
				if( isset($args[3]) )
				{
					$count = $this->QuestFixLuaNumericToken($args[3]);
					if( $count !== null && $count > 0 )
						$ret['count'] = (int)$count;
				}
				if( isset($args[5]) )
				{
					$icon = $this->QuestFixLuaNumericToken($args[5]);
					if( $icon !== null && $icon >= 0 )
						$ret['icon_id'] = (int)$icon;
				}
				for($i = 6; $i < count($args); $i++)
				{
					$id = $this->QuestFixLuaNumericToken($args[$i]);
					if( $id !== null && $id > 0 && !in_array((int)$id, $ids, true) )
						$ids[] = (int)$id;
				}
			}
			$ret['found'] = true;
			$ret['function'] = $function;
			if( count($ids) > 0 )
				$ret['ids'] = $ids;
			return $ret;
		}
		return $ret;
	}

	private function QuestFixReadLocalLuaScript($luaScript)
	{
		$luaScript = trim((string)$luaScript);
		if( $luaScript === '' )
			return '';
		$path = $this->QuestFixResolveScriptPath($luaScript);
		if( $path === '' )
			return '';
		$script = @file_get_contents($path);
		return $script === false ? '' : (string)$script;
	}

	private function QuestFixLocalLuaComplexity($luaScript)
	{
		$script = $this->QuestFixReadLocalLuaScript($luaScript);
		$ret = array(
			'exists' => trim($script) !== '',
			'preserve_local_script' => false,
			'reasons' => array(),
			'score' => 0
		);
		if( trim($script) === '' )
			return $ret;
		$checks = array(
			'SetQuestRepeatable' => '/\\bSetQuestRepeatable\\s*\\(/i',
			'GetQuestFlags' => '/\\bGetQuestFlags\\s*\\(/i',
			'SetQuestFlags' => '/\\bSetQuestFlags\\s*\\(/i',
			'CheckBitMask' => '/\\bCheckBitMask\\s*\\(/i',
			'helper SetStep' => '/\\bfunction\\s+SetStep\\s*\\(/i',
			'SetStep dispatch' => '/\\bSetStep\\s*\\(\\s*Quest\\s*,/i',
			'randomized branch state' => '/\\b(?:quantity|mob_type|mob|target_type)\\s*=\\s*math\\.random\\s*\\(/i'
		);
		foreach($checks as $label => $pattern)
		{
			if( preg_match($pattern, $script) )
			{
				$ret['score']++;
				$ret['reasons'][] = $label;
			}
		}
		$hasFlags = in_array('GetQuestFlags', $ret['reasons'], true) || in_array('SetQuestFlags', $ret['reasons'], true) || in_array('CheckBitMask', $ret['reasons'], true);
		$hasStepDispatcher = in_array('helper SetStep', $ret['reasons'], true) || in_array('SetStep dispatch', $ret['reasons'], true);
		$hasRandomBranch = in_array('randomized branch state', $ret['reasons'], true);
		// When the existing quest script carries its own persisted/randomized branch dispatcher,
		// replacing it with a flat Census-only skeleton would silently discard functional quest logic.
		// Preserve that script as the preview source instead of generating a lossy replacement.
		if( $ret['score'] >= 3 && $hasFlags && $hasStepDispatcher && ($hasRandomBranch || in_array('SetQuestRepeatable', $ret['reasons'], true)) )
			$ret['preserve_local_script'] = true;
		return $ret;
	}

	private function QuestFixStrongSpawnIdsFromCandidates($candidates, $limit = 12)
	{
		$ret = array();
		if( !is_array($candidates) || count($candidates) === 0 )
			return $ret;
		$bestScore = (int)($candidates[0]['score'] ?? 0);
		$threshold = max(60, $bestScore - 15);
		foreach($candidates as $candidate)
		{
			$score = (int)($candidate['score'] ?? 0);
			$id = (int)($candidate['id'] ?? 0);
			if( $id <= 0 || $score < $threshold )
				continue;
			if( isset($candidate['attackable']) && (int)$candidate['attackable'] !== 1 )
				continue;
			if( !in_array($id, $ret, true) )
				$ret[] = $id;
			if( count($ret) >= max(1, min(25, (int)$limit)) )
				break;
		}
		return $ret;
	}

	private function QuestFixQuestVariableNamesInScript($scriptText, $questId)
	{
		$vars = array((string)(int)$questId);
		if( (int)$questId <= 0 || trim((string)$scriptText) === '' )
			return $vars;
		if( preg_match_all('/\blocal\s+([A-Za-z_][A-Za-z0-9_]*)\s*=\s*' . preg_quote((string)(int)$questId, '/') . '\b/u', $scriptText, $m) )
		{
			foreach($m[1] as $var)
			{
				if( !in_array($var, $vars, true) )
					$vars[] = $var;
			}
		}
		return $vars;
	}
	
	private function QuestFixSpawnScriptEvidence($spawnId, $localQuestId, $stepNumber)
	{
		global $eq2;
		$spawnId = (int)$spawnId;
		$localQuestId = (int)$localQuestId;
		$stepNumber = (int)$stepNumber;
		$ret = array(
			'score_bonus' => 0,
			'has_script' => false,
			'lua_script' => '',
			'path_found' => false,
			'quest_var' => '',
			'has_get_step' => false,
			'has_set_step_complete' => false,
			'reasons' => array()
		);
		if( $spawnId <= 0 )
			return $ret;
		$eq2->SQLQuery = sprintf(
			"SELECT lua_script FROM `%s`.spawn_scripts WHERE spawn_id = %d AND lua_script <> '' ORDER BY id ASC LIMIT 1;",
			ACTIVE_DB, $spawnId
		);
		$row = $eq2->RunQuerySingle();
		if( !is_array($row) || empty($row['lua_script']) )
			return $ret;
		$ret['has_script'] = true;
		$ret['lua_script'] = trim((string)$row['lua_script']);
		$ret['score_bonus'] += 8;
		$ret['reasons'][] = 'spawn has Lua script';
		$path = $this->QuestFixResolveScriptPath($ret['lua_script']);
		if( $path === '' )
			return $ret;
		$ret['path_found'] = true;
		$script = @file_get_contents($path);
		if( $script === false || trim((string)$script) === '' || $localQuestId <= 0 || $stepNumber <= 0 )
			return $ret;
		$vars = $this->QuestFixQuestVariableNamesInScript($script, $localQuestId);
		foreach($vars as $token)
		{
			$quoted = preg_quote((string)$token, '/');
			if( preg_match('/\bGetQuestStep\s*\(\s*Spawn\s*,\s*' . $quoted . '\s*\)\s*(?:==|~=|>=|<=|>|<)\s*' . $stepNumber . '\b/u', $script) )
			{
				$ret['has_get_step'] = true;
				$ret['quest_var'] = (string)$token;
			}
			if( preg_match('/\bSetStepComplete\s*\(\s*Spawn\s*,\s*' . $quoted . '\s*,\s*' . $stepNumber . '\s*\)/u', $script) )
			{
				$ret['has_set_step_complete'] = true;
				$ret['quest_var'] = (string)$token;
			}
		}
		if( $ret['has_get_step'] )
		{
			$ret['score_bonus'] += 80;
			$ret['reasons'][] = 'script checks this quest step';
		}
		if( $ret['has_set_step_complete'] )
		{
			$ret['score_bonus'] += 180;
			$ret['reasons'][] = 'script completes this exact step';
		}
		return $ret;
	}
	
	private function QuestFixAddScriptEvidenceToCandidates($candidates, $localQuestId, $stepNumber)
	{
		$ret = array();
		foreach($candidates as $row)
		{
			$evidence = $this->QuestFixSpawnScriptEvidence((int)($row['id'] ?? 0), (int)$localQuestId, (int)$stepNumber);
			$row['script_evidence'] = $evidence;
			$row['score'] = (int)($row['score'] ?? 0) + (int)($evidence['score_bonus'] ?? 0);
			if( !empty($evidence['reasons']) )
			{
				$existing = trim((string)($row['score_reasons'] ?? ''));
				$extra = implode(', ', $evidence['reasons']);
				$row['score_reasons'] = $existing !== '' ? ($existing . ', ' . $extra) : $extra;
			}
			$ret[] = $row;
		}
		usort($ret, function($a, $b) {
			if( (int)($a['score'] ?? 0) === (int)($b['score'] ?? 0) )
				return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
			return ((int)($a['score'] ?? 0) > (int)($b['score'] ?? 0)) ? -1 : 1;
		});
		return $ret;
	}
	

	private function QuestFixWikiTitleKey($questName)
	{
		$title = trim((string)$questName);
		if( $title === '' )
			return '';

		// MediaWiki/Fandom article URLs use DB-key style page names.
		// Normalize every Wiki request through the same underscore title key,
		// not only ordinary ASCII spaces.
		$title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$title = str_replace(array("\xC2\xA0", "\u{202F}", "\u{2007}"), ' ', $title);
		$title = preg_replace('/\\s+/u', '_', $title);
		$title = preg_replace('/_+/u', '_', (string)$title);
		return trim((string)$title, '_');
	}

	private function QuestFixWikiPageUrl($questName)
	{
		$title = $this->QuestFixWikiTitleKey($questName);
		if( $title === '' )
			return '';
		return 'https://eq2.fandom.com/wiki/' . rawurlencode($title);
	}

	private function QuestFixWikiRequest($questName)
	{
		$url = $this->QuestFixWikiPageUrl($questName);
		if( $url === '' )
			return array('ok' => false, 'url' => '', 'error' => 'No wiki title was available for this quest.');
		$ctx = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'timeout' => 20,
				'ignore_errors' => true,
				'header' => "User-Agent: EQ2Emu-Editor-QuestFixAssistant/0.6\r\nAccept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8\r\n"
			)
		));
		$html = @file_get_contents($url, false, $ctx);
		if( $html === false || trim((string)$html) === '' )
			return array('ok' => false, 'url' => $url, 'error' => 'EQ2 Wiki request returned no HTML.');
		if( stripos($html, 'There is currently no text in this page') !== false || stripos($html, 'This page does not exist') !== false )
			return array('ok' => false, 'url' => $url, 'error' => 'EQ2 Wiki page does not appear to exist.');
		return array('ok' => true, 'url' => $url, 'html' => $html);
	}

	private function QuestFixWikiPlainText($html)
	{
		$html = (string)$html;
		$html = preg_replace('/<script\b[^>]*>.*?<\/script>/isu', ' ', $html);
		$html = preg_replace('/<style\b[^>]*>.*?<\/style>/isu', ' ', $html);
		$html = preg_replace('/<(?:br|\/p|\/li|\/tr|\/h[1-6]|\/div)\b[^>]*>/iu', "\n", $html);
		$text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		// Fandom/EQ2i pages may use non-breaking spaces and Unicode minus/dash
		// variants around waypoint coordinates. Normalize before parsing.
		$text = str_replace(array("\xC2\xA0", "\u{202F}", "\u{2007}"), ' ', $text);
		$text = str_replace(array("\u{2212}", "\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}", "\u{2015}"), '-', $text);

		$text = preg_replace('/[ \t]+/u', ' ', $text);
		$text = preg_replace('/\s*\n\s*/u', "\n", $text);
		$text = preg_replace('/\n{2,}/u', "\n", $text);
		return trim((string)$text);
	}

	private function QuestFixWikiCoordinateCandidates($plainText)
	{
		$plainText = (string)$plainText;
		if( trim($plainText) === '' )
			return array();

		// Defensive coordinate normalization for both HTML and API-derived text.
		$plainText = str_replace(array("\xC2\xA0", "\u{202F}", "\u{2007}"), ' ', $plainText);
		$plainText = str_replace(array("\u{2212}", "\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}", "\u{2015}"), '-', $plainText);

		// Rendered Fandom HTML normally exposes comma coordinates such as:
		// (-1214, -3, 912). Raw/API wikitext can instead keep template syntax
		// like {{Loc|-1214|-3|912}}, so both forms must be accepted.
		$patterns = array(
			'/(?<!\d)(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)(?!\d)/u',
			'/(?:\{\{[^{}\r\n]{0,120}?(?:loc|location|waypoint)[^{}\r\n]{0,120}?|\b(?:loc|location|waypoint)\b[^|\r\n]{0,80}?)\|\s*(-?\d+(?:\.\d+)?)\s*\|\s*(-?\d+(?:\.\d+)?)\s*\|\s*(-?\d+(?:\.\d+)?)/iu'
		);

		$ret = array();
		$seen = array();
		foreach($patterns as $pattern)
		{
			$matches = array();
			preg_match_all($pattern, $plainText, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
			foreach($matches as $match)
			{
				$x = isset($match[1][0]) ? (float)$match[1][0] : 0;
				$y = isset($match[2][0]) ? (float)$match[2][0] : 0;
				$z = isset($match[3][0]) ? (float)$match[3][0] : 0;
				$key = sprintf('%.5f|%.5f|%.5f', $x, $y, $z);
				if( isset($seen[$key]) )
					continue;
				$seen[$key] = true;

				$offset = isset($match[0][1]) ? max(0, (int)$match[0][1]) : 0;
				$before = 180;
				$after = 220;
				$start = max(0, $offset - $before);
				$matchText = isset($match[0][0]) ? (string)$match[0][0] : '';
				$matchLen = strlen($matchText);
				$snippet = substr($plainText, $start, $before + $matchLen + $after);
				$snippet = $this->QuestFixNormalizeWhitespace($snippet);
				$sentence = $this->QuestFixWikiCoordinateSentence($plainText, $offset, $matchLen);
				$line = $this->QuestFixWikiCoordinateLine($plainText, $offset, $matchLen);
				$ret[] = array(
					'x' => $x,
					'y' => $y,
					'z' => $z,
					'coord_key' => $key,
					'snippet' => $snippet,
					'sentence' => $sentence,
					'line' => $line
				);
			}
		}
		return $ret;
	}

	private function QuestFixWikiCoordinateSentence($plainText, $offset, $matchLen = 0)
	{
		$plainText = (string)$plainText;
		$len = strlen($plainText);
		if( $len <= 0 )
			return '';
		$offset = max(0, min($len, (int)$offset));
		$matchLen = max(0, (int)$matchLen);
		$rightAnchor = max($offset, min($len, $offset + $matchLen));

		// Keep quest-update wording tied to the same logical sentence as the coordinate.
		// This prevents an earlier navigation waypoint from inheriting the explicit
		// "quest should update when you walk close to (...)" wording of the next coordinate.
		$leftProbe = substr($plainText, 0, $offset);
		$leftStops = array(strrpos($leftProbe, '.'), strrpos($leftProbe, '!'), strrpos($leftProbe, '?'), strrpos($leftProbe, "\n"));
		$start = 0;
		foreach($leftStops as $stop)
		{
			if( $stop !== false && ((int)$stop + 1) > $start )
				$start = (int)$stop + 1;
		}

		$rightProbe = substr($plainText, $rightAnchor);
		$rightStops = array(strpos($rightProbe, '.'), strpos($rightProbe, '!'), strpos($rightProbe, '?'), strpos($rightProbe, "\n"));
		$endRel = false;
		foreach($rightStops as $stop)
		{
			if( $stop === false )
				continue;
			if( $endRel === false || (int)$stop < (int)$endRel )
				$endRel = (int)$stop;
		}
		$end = $endRel === false ? $len : min($len, $rightAnchor + (int)$endRel + 1);
		if( $end <= $start )
			return '';
		return $this->QuestFixNormalizeWhitespace(substr($plainText, $start, $end - $start));
	}


	private function QuestFixWikiCoordinateLine($plainText, $offset, $matchLen = 0)
	{
		$plainText = (string)$plainText;
		$len = strlen($plainText);
		if( $len <= 0 )
			return '';
		$offset = max(0, min($len, (int)$offset));
		$matchLen = max(0, (int)$matchLen);
		$rightAnchor = max($offset, min($len, $offset + $matchLen));

		// Multi-waypoint EQ2 Wiki pages commonly store one POI per bullet/table row.
		// Keep that exact local row/line separate from the broader nearby snippet so
		// "Crater Pond" cannot make the adjacent Coldwind/Fippy coordinate score too.
		$leftProbe = substr($plainText, 0, $offset);
		$leftBreak = strrpos($leftProbe, "\n");
		$start = ($leftBreak === false) ? 0 : ((int)$leftBreak + 1);

		$rightProbe = substr($plainText, $rightAnchor);
		$rightBreak = strpos($rightProbe, "\n");
		$end = ($rightBreak === false) ? $len : min($len, $rightAnchor + (int)$rightBreak);

		if( $end <= $start )
			return '';
		return $this->QuestFixNormalizeWhitespace(substr($plainText, $start, $end - $start));
	}

	private function QuestFixWikiHttpGet($url, $accept = 'text/plain,*/*;q=0.8')
	{
		$url = trim((string)$url);
		if( $url === '' )
			return '';
		$ctx = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'timeout' => 20,
				'ignore_errors' => true,
				'header' => "User-Agent: EQ2Emu-Editor-QuestFixAssistant/0.6-fix2\r\nAccept: " . $accept . "\r\n"
			)
		));
		$body = @file_get_contents($url, false, $ctx);
		return $body === false ? '' : (string)$body;
	}

	private function QuestFixWikiRawText($questName)
	{
		$questName = trim((string)$questName);
		if( $questName === '' )
			return '';
		$title = $this->QuestFixWikiTitleKey($questName);
		if( $title === '' )
			return '';
		$url = 'https://eq2.fandom.com/wiki/' . rawurlencode($title) . '?action=raw';
		return $this->QuestFixWikiHttpGet($url, 'text/plain,*/*;q=0.8');
	}

	private function QuestFixWikiParseApiText($questName)
	{
		$questName = trim((string)$questName);
		if( $questName === '' )
			return '';
		$title = $this->QuestFixWikiTitleKey($questName);
		if( $title === '' )
			return '';
		$url = 'https://eq2.fandom.com/api.php?action=parse&page=' . rawurlencode($title) . '&prop=wikitext&format=json';
		$json = $this->QuestFixWikiHttpGet($url, 'application/json,text/plain,*/*;q=0.8');
		if( trim((string)$json) === '' )
			return '';
		$data = json_decode($json, true);
		if( !is_array($data) )
			return '';
		if( isset($data['parse']['wikitext']['*']) )
			return (string)$data['parse']['wikitext']['*'];
		if( isset($data['parse']['wikitext']) && is_string($data['parse']['wikitext']) )
			return (string)$data['parse']['wikitext'];
		return '';
	}

	private function QuestFixWikiRevisionsApiText($questName)
	{
		$questName = trim((string)$questName);
		if( $questName === '' )
			return '';
		$title = $this->QuestFixWikiTitleKey($questName);
		if( $title === '' )
			return '';
		$url = 'https://eq2.fandom.com/api.php?action=query&prop=revisions&titles=' . rawurlencode($title) . '&rvprop=content&rvslots=main&format=json&formatversion=2';
		$json = $this->QuestFixWikiHttpGet($url, 'application/json,text/plain,*/*;q=0.8');
		if( trim((string)$json) === '' )
			return '';
		$data = json_decode($json, true);
		if( !is_array($data) )
			return '';

		// MediaWiki formatversion=2 shape
		if( isset($data['query']['pages']) && is_array($data['query']['pages']) )
		{
			foreach($data['query']['pages'] as $page)
			{
				if( !isset($page['revisions']) || !is_array($page['revisions']) || empty($page['revisions'][0]) )
					continue;
				$revision = $page['revisions'][0];
				if( isset($revision['slots']['main']['content']) )
					return (string)$revision['slots']['main']['content'];
				if( isset($revision['slots']['main']['*']) )
					return (string)$revision['slots']['main']['*'];
				if( isset($revision['content']) )
					return (string)$revision['content'];
				if( isset($revision['*']) )
					return (string)$revision['*'];
			}
		}

		// Legacy associative page-id shape
		if( isset($data['query']['pages']) && is_array($data['query']['pages']) )
		{
			foreach($data['query']['pages'] as $page)
			{
				if( !isset($page['revisions']) || !is_array($page['revisions']) || empty($page['revisions'][0]) )
					continue;
				$revision = $page['revisions'][0];
				if( isset($revision['slots']['main']['*']) )
					return (string)$revision['slots']['main']['*'];
				if( isset($revision['*']) )
					return (string)$revision['*'];
			}
		}

		return '';
	}

	private function QuestFixWikiTryCoordinateSource($rawText, $sourceLabel)
	{
		$rawText = (string)$rawText;
		if( trim($rawText) === '' )
			return array('coordinates' => array(), 'plain_text' => '', 'source' => '');
		$plain = $this->QuestFixWikiPlainText($rawText);
		$coordinates = $this->QuestFixWikiCoordinateCandidates($plain);
		return array(
			'coordinates' => $coordinates,
			'plain_text' => $plain,
			'source' => count($coordinates) > 0 ? (string)$sourceLabel : ''
		);
	}

	private function QuestFixWikiQuestData($questName)
	{
		$request = $this->QuestFixWikiRequest($questName);
		if( empty($request['ok']) )
			return array(
				'ok' => false,
				'url' => $request['url'] ?? '',
				'error' => $request['error'] ?? 'EQ2 Wiki lookup failed.',
				'coordinates' => array(),
				'source' => ''
			);

		$plain = $this->QuestFixWikiPlainText($request['html'] ?? '');
		$coordinates = $this->QuestFixWikiCoordinateCandidates($plain);
		$source = count($coordinates) > 0 ? 'html' : 'html-no-coordinates';

		// Fandom sometimes returns a reduced HTML shell to server-side requests.
		// Prefer the raw article wikitext next; it is usually the most direct source.
		if( count($coordinates) === 0 )
		{
			$raw = $this->QuestFixWikiTryCoordinateSource($this->QuestFixWikiRawText($questName), 'action-raw');
			if( !empty($raw['coordinates']) )
			{
				$plain = $raw['plain_text'];
				$coordinates = $raw['coordinates'];
				$source = $raw['source'];
			}
		}

		// Fallback #2: action=parse wikitext API.
		if( count($coordinates) === 0 )
		{
			$parse = $this->QuestFixWikiTryCoordinateSource($this->QuestFixWikiParseApiText($questName), 'api-parse-wikitext');
			if( !empty($parse['coordinates']) )
			{
				$plain = $parse['plain_text'];
				$coordinates = $parse['coordinates'];
				$source = $parse['source'];
			}
		}

		// Fallback #3: revisions API wikitext. This covers Fandom instances where
		// action=parse is disabled, altered, or returns a shape we cannot use.
		if( count($coordinates) === 0 )
		{
			$revisions = $this->QuestFixWikiTryCoordinateSource($this->QuestFixWikiRevisionsApiText($questName), 'api-revisions-wikitext');
			if( !empty($revisions['coordinates']) )
			{
				$plain = $revisions['plain_text'];
				$coordinates = $revisions['coordinates'];
				$source = $revisions['source'];
			}
		}

		return array(
			'ok' => true,
			'url' => $request['url'] ?? '',
			'error' => '',
			'plain_text' => $plain,
			'coordinates' => $coordinates,
			'source' => $source
		);
	}

	private function QuestFixZoneInfoByName($zoneName)
	{
		global $eq2;
		$zoneName = trim((string)$zoneName);
		if( $zoneName === '' )
			return array();
		$needle = $eq2->SQLEscape($zoneName);
		$eq2->SQLQuery = sprintf(
			"SELECT id, name, description FROM `%s`.zones " .
			"WHERE LOWER(name) = LOWER('%s') OR LOWER(description) = LOWER('%s') " .
			"ORDER BY CASE WHEN LOWER(name) = LOWER('%s') THEN 0 WHEN LOWER(description) = LOWER('%s') THEN 1 ELSE 2 END, id ASC LIMIT 1;",
			ACTIVE_DB, $needle, $needle, $needle, $needle
		);
		$row = $eq2->RunQuerySingle();
		return is_array($row) ? $row : array();
	}

	private function QuestFixWikiLocationTerms($branch)
	{
		$parts = array();
		if( isset($branch['step_text']) && trim((string)$branch['step_text']) !== '' )
			$parts[] = (string)$branch['step_text'];
		if( isset($branch['task_group_text']) && trim((string)$branch['task_group_text']) !== '' )
			$parts[] = (string)$branch['task_group_text'];
		if( isset($branch['analysis']['target']) && trim((string)$branch['analysis']['target']) !== '' )
			$parts[] = (string)$branch['analysis']['target'];
		$text = strtolower(implode(' ', $parts));
		$text = preg_replace('/[^a-z0-9\' ]/iu', ' ', $text);
		$tokens = preg_split('/\s+/u', trim($text));
		$stop = array(
			'i' => true, 'to' => true, 'the' => true, 'a' => true, 'an' => true, 'of' => true, 'at' => true,
			'go' => true, 'find' => true, 'travel' => true, 'visit' => true, 'need' => true, 'must' => true,
			'with' => true, 'into' => true, 'near' => true, 'from' => true, 'somewhere' => true,
			'hidden' => false, 'entrance' => false, 'quest' => true, 'step' => true, 'should' => true
		);
		$ret = array();
		foreach($tokens as $token)
		{
			$token = trim((string)$token);
			if( $token === '' || strlen($token) < 4 )
				continue;
			if( isset($stop[$token]) && $stop[$token] === true )
				continue;
			$ret[$token] = true;
		}
		return array_keys($ret);
	}

	private function QuestFixWikiLocationLabel($line)
	{
		$line = $this->QuestFixNormalizeWhitespace($line);
		if( $line === '' )
			return '';
		$line = preg_replace('/^\s*(?:[*#;:]+\s*)+/u', '', $line);
		$label = preg_split('/\s*(?::|\(|\bCopy\/waypoint\b|\bwaypoint\b)\s*/iu', $line, 2);
		$label = isset($label[0]) ? $this->QuestFixNormalizeWhitespace($label[0]) : '';
		return trim((string)$label, " \t\n\r\0\x0B.,;:!?\"'");
	}

	private function QuestFixWikiLocationMatchTokens($text)
	{
		$text = strtolower($this->QuestFixNormalizeWhitespace($text));
		$text = preg_replace('/[^a-z0-9\' ]/iu', ' ', $text);
		$tokens = preg_split('/\s+/u', trim((string)$text));
		$stop = array(
			'a' => true, 'an' => true, 'the' => true, 'of' => true, 'to' => true, 'in' => true, 'at' => true,
			'by' => true, 'near' => true, 'with' => true, 'from' => true, 'for' => true, 'must' => true,
			'i' => true, 'go' => true, 'visit' => true, 'find' => true, 'travel' => true, 'reach' => true,
			'locate' => true, 'main' => true, 'grand' => true
		);
		$ret = array();
		foreach($tokens as $token)
		{
			$token = trim((string)$token, " \t\n\r\0\x0B.,;:!?\"'");
			if( $token === '' || strlen($token) < 4 || isset($stop[$token]) )
				continue;
			$ret[$token] = true;
		}
		return array_keys($ret);
	}

	private function QuestFixWikiLineTokenFrequency($coordinates)
	{
		$ret = array();
		if( !is_array($coordinates) )
			return $ret;
		foreach($coordinates as $coordinate)
		{
			$label = $this->QuestFixWikiLocationLabel((string)($coordinate['line'] ?? ''));
			$seen = array();
			foreach($this->QuestFixWikiLocationMatchTokens($label) as $token)
			{
				if( isset($seen[$token]) )
					continue;
				$seen[$token] = true;
				$ret[$token] = (int)($ret[$token] ?? 0) + 1;
			}
		}
		return $ret;
	}

	private function QuestFixWikiCoordinateLooksChatLike($line, $sentence = '', $snippet = '')
	{
		// Wiki quest pages can mix coordinate bullets for POIs with coordinate bullets for NPC turn-ins.
		// A Location step must not tie against a nearby/explicit Chat instruction simply because both rows share
		// page-level context. Classify obvious NPC/action rows before scoring the location candidate.
		$text = strtolower($this->QuestFixNormalizeWhitespace(trim((string)$line . ' ' . (string)$sentence)));
		if( $text === '' )
			$text = strtolower($this->QuestFixNormalizeWhitespace((string)$snippet));
		if( $text === '' )
			return false;

		if( preg_match('/\b(?:speak\s+(?:to|with)|talk\s+(?:to|with)|meet\s+(?:with\s+)?|return\s+to|report\s+to|inform|deliver(?:\s+[^.!?]{0,60})?\s+to|give(?:\s+[^.!?]{0,60})?\s+to)\b/iu', $text) )
			return true;

		// "Find the librarian..." / "Find Captain..." are Chat-style instructions when the object is a person role.
		if( preg_match('/\bfind\s+(?:the\s+)?(?:advisor|archivist|artisan|bartender|captain|chef|clerk|commander|corporal|crafter|elder|emissary|farmer|fisherman|guard|healer|herbalist|historian|innkeeper|keeper|knight|librarian|lieutenant|lord|lady|master|mayor|merchant|miner|monk|oracle|priest|priestess|professor|quartermaster|ranger|recruit|sage|scholar|scout|sergeant|smith|soldier|speaker|trainer|warden|wizard)\b/iu', $text) )
			return true;

		return false;
	}

	private function QuestFixWikiLocationActionPenalty($line, $sentence = '', $snippet = '')
	{
		return $this->QuestFixWikiCoordinateLooksChatLike($line, $sentence, $snippet) ? 720 : 0;
	}

	private function QuestFixFindWikiLocationCandidates($wikiData, $branch, $limit = 8)
	{
		if( !is_array($wikiData) || empty($wikiData['ok']) || empty($wikiData['coordinates']) || !is_array($wikiData['coordinates']) )
			return array();
		$terms = $this->QuestFixWikiLocationTerms($branch);
		$target = isset($branch['analysis']['target']) ? (string)$branch['analysis']['target'] : '';
		$targetNorm = $this->QuestFixNormalizedCompare($target);
		$targetLabelTokens = $this->QuestFixWikiLocationMatchTokens($target);
		$wikiLineTokenFrequency = $this->QuestFixWikiLineTokenFrequency($wikiData['coordinates']);
		$zoneName = isset($branch['context_zone']) ? (string)$branch['context_zone'] : '';
		$zoneInfo = $this->QuestFixZoneInfoByName($zoneName);
		$zoneId = isset($zoneInfo['id']) ? (int)$zoneInfo['id'] : 0;
		$sourceZoneName = isset($zoneInfo['description']) && trim((string)$zoneInfo['description']) !== '' ? (string)$zoneInfo['description'] : $zoneName;
		$ret = array();
		$idx = 1;
		foreach($wikiData['coordinates'] as $coordinate)
		{
			$snippet = isset($coordinate['snippet']) ? (string)$coordinate['snippet'] : '';
			$sentence = isset($coordinate['sentence']) ? (string)$coordinate['sentence'] : '';
			$line = isset($coordinate['line']) ? (string)$coordinate['line'] : '';
			$snippetNorm = $this->QuestFixNormalizedCompare($snippet);
			$lineNorm = $this->QuestFixNormalizedCompare($line);
			$snippetLower = strtolower($snippet);
			$lineLower = strtolower($line);
			$lineLabel = $this->QuestFixWikiLocationLabel($line);
			$lineLabelNorm = $this->QuestFixNormalizedCompare($lineLabel);
			$lineLabelTokens = $this->QuestFixWikiLocationMatchTokens($lineLabel);
			$score = 0;
			$reasons = array();

			// Mixed quest Wiki pages often contain both POI/location rows and final NPC hand-in rows.
			// Apply the chat/NPC row penalty inside the actual Wiki location candidate scorer,
			// not only in ancillary scorers. This keeps "Speak with ... at (x,y,z)" from tying
			// against a real destination such as a monument, keep, cave, or entrance.
			$chatLikePenalty = $this->QuestFixWikiLocationActionPenalty($line, $sentence, $snippet);
			if( $chatLikePenalty > 0 )
			{
				$score -= $chatLikePenalty;
				$reasons[] = 'wiki coordinate row is chat/NPC instruction, penalized for Location step';
			}

			if( $sentence !== '' && preg_match('/quest\s+(?:should\s+)?update|updates?\s+the\s+quest|will\s+update\s+the\s+quest|walk\s+close|walk\s+near/iu', $sentence) )
			{
				$score += 300;
				$reasons[] = 'wiki same-sentence quest-update waypoint';
			}
			if( stripos($snippet, 'waypoint') !== false )
			{
				$score += 20;
				$reasons[] = 'wiki waypoint';
			}
			if( $targetNorm !== '' && $lineNorm !== '' && strpos($lineNorm, $targetNorm) !== false )
			{
				$score += 360;
				$reasons[] = 'wiki same-line exact target';
			}
			elseif( $targetNorm !== '' && $snippetNorm !== '' && strpos($snippetNorm, $targetNorm) !== false )
			{
				// Small fallback for prose pages where coordinate and target wrap over a
				// neighboring line. A wide snippet must never outrank a same-line match.
				$score += 80;
				$reasons[] = 'wiki nearby snippet contains exact target';
			}

			// Generalized Wiki row-label matching for POI synonym/expansion cases:
			// - "main entrance of Stormhold" -> row label "Stormhold"
			// - "Grave of Windstalker" -> row label "Grave of Holly Windstalker"
			// - one strong unique noun such as "Claymore" can identify a row even if the page label differs.
			if( $targetNorm !== '' && $lineLabelNorm !== '' )
			{
				if( strpos($targetNorm, $lineLabelNorm) !== false || strpos($lineLabelNorm, $targetNorm) !== false )
				{
					$score += 300;
					$reasons[] = 'wiki row label contains/is contained by target';
				}
				$targetTokenCount = count($targetLabelTokens);
				$lineTokenCount = count($lineLabelTokens);
				if( $targetTokenCount > 0 && $lineTokenCount > 0 )
				{
					$targetInLine = count(array_intersect($targetLabelTokens, $lineLabelTokens));
					$lineInTarget = $targetInLine;
					if( $targetInLine === $targetTokenCount )
					{
						$score += 280;
						$reasons[] = 'wiki row label contains all target keywords';
					}
					elseif( $lineInTarget === $lineTokenCount )
					{
						$score += 240;
						$reasons[] = 'target contains all wiki row-label keywords';
					}
					elseif( $targetInLine >= 2 )
					{
						$score += 150;
						$reasons[] = 'wiki row label shares multiple target keywords';
					}
					$uniqueStrongMatches = 0;
					foreach(array_intersect($targetLabelTokens, $lineLabelTokens) as $matchedToken)
					{
						if( strlen((string)$matchedToken) >= 6 && (int)($wikiLineTokenFrequency[$matchedToken] ?? 0) === 1 )
							$uniqueStrongMatches++;
					}
					if( $uniqueStrongMatches > 0 )
					{
						$score += 260;
						$reasons[] = 'wiki row label has unique strong target keyword ' . $uniqueStrongMatches;
					}
				}
			}
			$matchedLineTerms = 0;
			$matchedNearbyTerms = 0;
			foreach($terms as $term)
			{
				if( $term === '' )
					continue;
				$needle = strtolower($term);
				if( $lineLower !== '' && strpos($lineLower, $needle) !== false )
				{
					$matchedLineTerms++;
					$score += 28;
				}
				elseif( strpos($snippetLower, $needle) !== false )
				{
					$matchedNearbyTerms++;
					$score += 10;
				}
			}
			if( $matchedLineTerms > 0 )
				$reasons[] = 'wiki same-line target/context terms ' . $matchedLineTerms;
			if( $matchedNearbyTerms > 0 )
				$reasons[] = 'wiki nearby target/context terms ' . $matchedNearbyTerms;
			if( stripos($target, 'entrance') !== false && (stripos($line, 'entrance') !== false || stripos($snippet, 'entrance') !== false) )
			{
				$score += stripos($line, 'entrance') !== false ? 70 : 35;
				$reasons[] = stripos($line, 'entrance') !== false ? 'same-line entrance keyword' : 'nearby entrance keyword';
			}
			if( stripos($target, 'lair') !== false && (stripos($line, 'lair') !== false || stripos($snippet, 'lair') !== false) )
			{
				$score += stripos($line, 'lair') !== false ? 55 : 28;
				$reasons[] = stripos($line, 'lair') !== false ? 'same-line lair keyword' : 'nearby lair keyword';
			}
			if( stripos($target, 'gnoll') !== false && (stripos($line, 'gnoll') !== false || stripos($snippet, 'gnoll') !== false) )
			{
				$score += stripos($line, 'gnoll') !== false ? 45 : 22;
				$reasons[] = stripos($line, 'gnoll') !== false ? 'same-line gnoll keyword' : 'nearby gnoll keyword';
			}
			if( $zoneId > 0 )
			{
				$score += 25;
				$reasons[] = 'quest category zone resolved';
			}
			if( $score <= 0 )
				continue;
			$ret[] = array(
				'id' => -1000 - $idx,
				'name' => 'EQ2 Wiki waypoint',
				'source' => 'Wiki',
				'source_zone_id' => $zoneId,
				'source_zone_name' => $sourceZoneName,
				'x' => isset($coordinate['x']) ? (float)$coordinate['x'] : 0,
				'y' => isset($coordinate['y']) ? (float)$coordinate['y'] : 0,
				'z' => isset($coordinate['z']) ? (float)$coordinate['z'] : 0,
				'score' => $score,
				'score_reasons' => implode(', ', array_unique($reasons)),
				'wiki_snippet' => $snippet,
				'wiki_line' => $line
			);
			$idx++;
		}
		usort($ret, function($a, $b) {
			if( (int)($a['score'] ?? 0) === (int)($b['score'] ?? 0) )
				return (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0);
			return ((int)($a['score'] ?? 0) > (int)($b['score'] ?? 0)) ? -1 : 1;
		});
		$limit = max(1, min(20, (int)$limit));
		return array_slice($ret, 0, $limit);
	}

	private function QuestFixLocationSearchTerms($target)
	{
		$target = $this->QuestFixNormalizeWhitespace($target);
		$terms = array();
		if( $target !== '' )
			$terms[] = $target;
		$lo = strtolower($target);
		$trimmed = preg_replace('/\b(?:a|an|the|of|to|entrance|hidden|lair)\b/iu', ' ', $target);
		$trimmed = $this->QuestFixNormalizeWhitespace($trimmed);
		if( $trimmed !== '' )
			$terms[] = $trimmed;
		if( strpos($lo, 'gnoll') !== false && strpos($lo, 'lair') !== false )
			$terms[] = 'Blackburrow';
		if( strpos($lo, 'blackburrow') !== false )
			$terms[] = 'Blackburrow';
		$ret = array();
		foreach($terms as $term)
		{
			$key = strtolower(trim((string)$term));
			if( $key !== '' && !isset($ret[$key]) )
				$ret[$key] = trim((string)$term);
		}
		return array_values($ret);
	}
	
	public function QuestFixFindLocationCandidates($target, $contextZone = '', $limit = 12)
	{
		global $eq2;
		$terms = $this->QuestFixLocationSearchTerms($target);
		if( count($terms) === 0 )
			return array();
		$where = array();
		foreach($terms as $term)
		{
			$needle = $eq2->SQLEscape(strtolower($term));
			$where[] = "LOWER(ss.title) LIKE '%" . $needle . "%'";
			$where[] = "LOWER(dest.name) LIKE '%" . $needle . "%'";
			$where[] = "LOWER(dest.description) LIKE '%" . $needle . "%'";
		}
		$eq2->SQLQuery = sprintf(
			"SELECT DISTINCT ss.spawn_id AS id, ss.title AS name, ss.type AS sign_type, ss.zone_id AS destination_zone_id, " .
			"ss.widget_x AS x, ss.widget_y AS y, ss.widget_z AS z, " .
			"src.id AS source_zone_id, src.name AS source_zone_name, src.description AS source_zone_description, " .
			"dest.name AS destination_zone_name, dest.description AS destination_zone_description " .
			"FROM `%s`.spawn_signs ss " .
			"LEFT JOIN `%s`.spawn_location_entry sle ON sle.spawn_id = ss.spawn_id " .
			"LEFT JOIN `%s`.spawn_location_placement slp ON slp.spawn_location_id = sle.spawn_location_id " .
			"LEFT JOIN `%s`.zones src ON src.id = slp.zone_id " .
			"LEFT JOIN `%s`.zones dest ON dest.id = ss.zone_id " .
			"WHERE ss.type = 'Zone' AND (%s) LIMIT 240;",
			ACTIVE_DB, ACTIVE_DB, ACTIVE_DB, ACTIVE_DB, ACTIVE_DB, implode(' OR ', $where)
		);
		$rows = $eq2->RunQueryMulti();
		if( !is_array($rows) )
			return array();
		$zoneNorm = $this->QuestFixNormalizedCompare($contextZone);
		$targetNorm = $this->QuestFixNormalizedCompare($target);
		$termNorms = array();
		foreach($terms as $term)
			$termNorms[] = $this->QuestFixNormalizedCompare($term);
		$ret = array();
		foreach($rows as $row)
		{
			$titleNorm = $this->QuestFixNormalizedCompare($row['name'] ?? '');
			$destNameNorm = $this->QuestFixNormalizedCompare($row['destination_zone_name'] ?? '');
			$destDescNorm = $this->QuestFixNormalizedCompare($row['destination_zone_description'] ?? '');
			$srcNameNorm = $this->QuestFixNormalizedCompare($row['source_zone_name'] ?? '');
			$srcDescNorm = $this->QuestFixNormalizedCompare($row['source_zone_description'] ?? '');
			$score = 0;
			$reasons = array();
			foreach($termNorms as $termNorm)
			{
				if( $termNorm === '' )
					continue;
				if( $titleNorm === $termNorm || $destNameNorm === $termNorm )
				{
					$score += 120;
					$reasons[] = 'exact location/target-zone term';
				}
				elseif( ($titleNorm !== '' && strpos($titleNorm, $termNorm) !== false) || ($destNameNorm !== '' && strpos($destNameNorm, $termNorm) !== false) )
				{
					$score += 90;
					$reasons[] = 'location/target-zone contains term';
				}
				elseif( $destDescNorm !== '' && strpos($destDescNorm, $termNorm) !== false )
				{
					$score += 45;
					$reasons[] = 'target-zone description contains term';
				}
			}
			if( $targetNorm !== '' && $titleNorm !== '' && strpos($targetNorm, 'entrance') !== false && strpos($titleNorm, 'to ') === 0 )
			{
				$score += 10;
				$reasons[] = 'entrance-like transition sign';
			}
			if( $zoneNorm !== '' )
			{
				if( $srcNameNorm === $zoneNorm || $srcDescNorm === $zoneNorm )
				{
					$score += 90;
					$reasons[] = 'exact source-zone context';
				}
				elseif( ($srcNameNorm !== '' && strpos($srcNameNorm, $zoneNorm) !== false) || ($srcDescNorm !== '' && strpos($srcDescNorm, $zoneNorm) !== false) )
				{
					$score += 65;
					$reasons[] = 'source-zone contains context';
				}
			}
			$row['score'] = $score;
			$row['score_reasons'] = implode(', ', array_unique($reasons));
			$ret[] = $row;
		}
		usort($ret, function($a, $b) {
			if( (int)($a['score'] ?? 0) === (int)($b['score'] ?? 0) )
				return (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0);
			return ((int)($a['score'] ?? 0) > (int)($b['score'] ?? 0)) ? -1 : 1;
		});
		$limit = max(1, min(50, (int)$limit));
		return array_slice($ret, 0, $limit);
	}

	private function QuestFixLocationAutoSelectCandidate($candidates)
	{
		if( !is_array($candidates) || count($candidates) === 0 )
			return array();
		$first = $candidates[0];
		$firstScore = (int)($first['score'] ?? 0);
		$secondScore = count($candidates) > 1 ? (int)($candidates[1]['score'] ?? 0) : -999999;
		$margin = $firstScore - $secondScore;
		// Location guesses are high-risk. Only auto-commit when the top score is both strong and clearly separated.
		if( $firstScore >= 280 && $margin >= 70 )
			return $first;
		return array();
	}

	private function QuestFixPickLocationCandidateById($candidates, $selectedId)
	{
		$selectedId = (int)$selectedId;
		if( $selectedId <= 0 || !is_array($candidates) )
			return array();
		foreach($candidates as $candidate)
		{
			if( (int)($candidate['id'] ?? 0) === $selectedId )
				return $candidate;
		}
		return array();
	}

	private function QuestFixPickSpawnCandidatesByIds($candidates, $selectedIds)
	{
		if( !is_array($candidates) )
			return array();
		if( !is_array($selectedIds) )
			$selectedIds = array($selectedIds);
		$wanted = array();
		foreach($selectedIds as $selectedId)
		{
			$selectedId = (int)$selectedId;
			if( $selectedId > 0 && !in_array($selectedId, $wanted, true) )
				$wanted[] = $selectedId;
		}
		if( count($wanted) === 0 )
			return array();
		$ret = array();
		foreach($wanted as $wantedId)
		{
			foreach($candidates as $candidate)
			{
				if( (int)($candidate['id'] ?? 0) === $wantedId )
				{
					$ret[] = $candidate;
					break;
				}
			}
		}
		return $ret;
	}

	private function QuestFixWikiTextChunksForBranch($branch)
	{
		$wiki = isset($branch['wiki_data']) && is_array($branch['wiki_data']) ? $branch['wiki_data'] : array();
		$chunks = array();
		$plain = isset($wiki['plain_text']) ? (string)$wiki['plain_text'] : '';
		if( trim($plain) !== '' )
		{
			foreach((array)preg_split('/(?:\R+|(?<=[.!?])\s+)/u', $plain) as $chunk)
			{
				$chunk = $this->QuestFixNormalizeWhitespace((string)$chunk);
				if( $chunk !== '' )
					$chunks[] = $chunk;
			}
		}
		foreach((array)($wiki['coordinates'] ?? array()) as $coordinate)
		{
			foreach(array('line','sentence','snippet') as $key)
			{
				$chunk = $this->QuestFixNormalizeWhitespace((string)($coordinate[$key] ?? ''));
				if( $chunk !== '' )
					$chunks[] = $chunk;
			}
		}
		$ret = array();
		foreach($chunks as $chunk)
		{
			$key = strtolower($chunk);
			if( !isset($ret[$key]) )
				$ret[$key] = $chunk;
		}
		return array_values($ret);
	}

	private function QuestFixUsefulIntentTokens($text)
	{
		$tokens = $this->QuestFixWikiLocationMatchTokens((string)$text);
		$ret = array();
		foreach($tokens as $token)
		{
			$token = strtolower(trim((string)$token));
			if( $token === '' || strlen($token) < 4 )
				continue;
			if( in_array($token, array('need','speak','with','return','find','woman','mistress','master','from','that','appear','leading','other','recover','remains','gather','skulls','tower','old'), true) )
				continue;
			$ret[$token] = $token;
		}
		return array_values($ret);
	}

	private function QuestFixWikiChunkScoreForBranch($chunk, $branch)
	{
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$chunkLo = strtolower($this->QuestFixNormalizeWhitespace((string)$chunk));
		if( $chunkLo === '' )
			return 0;
		$score = 0;
		$inputs = array(
			(string)($analysis['target'] ?? ''),
			(string)($analysis['raw_text'] ?? ''),
			(string)($analysis['zone'] ?? ''),
			(string)($branch['step_text'] ?? ''),
			(string)($branch['task_group_text'] ?? ''),
			(string)($branch['completion_text'] ?? '')
		);
		foreach($inputs as $input)
		{
			foreach($this->QuestFixUsefulIntentTokens($input) as $token)
			{
				if( strpos($chunkLo, $token) !== false )
					$score += strlen($token) >= 7 ? 32 : 18;
			}
		}
		if( preg_match('/\b(?:speak|talk|return|report|deliver|bring)\b/iu', $chunk) )
			$score += 18;
		if( preg_match('/\b(?:kill|slay|defeat|destroy|hunt|recover|collect|gather|loot)\b/iu', $chunk) )
			$score += 12;
		return $score;
	}

	private function QuestFixWikiChatNameHints($branch)
	{
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$target = trim((string)($analysis['target'] ?? ''));
		$hints = array();
		$add = function($value, $score) use (&$hints) {
			$value = trim((string)$value, " \t\n\r\0\x0B.,;:!?\"'");
			$value = preg_replace('/\s+(?:at|in|near|by|within|outside|inside|on|for|from)\b.*$/iu', '', $value);
			$value = trim((string)$value, " \t\n\r\0\x0B.,;:!?\"'");
			if( $value === '' || strlen($value) < 2 )
				return;
			if( !preg_match('/[A-Z]/u', $value) )
				return;
			$key = strtolower(str_replace('`', "'", $value));
			if( !isset($hints[$key]) || (int)$score > (int)$hints[$key]['score'] )
				$hints[$key] = array('name' => $value, 'score' => (int)$score);
		};
		$chatHintChunks = $this->QuestFixWikiTextChunksForBranch($branch);
		foreach(array(
			(string)($branch['step_text'] ?? ''),
			(string)($branch['task_group_text'] ?? ''),
			(string)($branch['completion_text'] ?? '')
		) as $branchHintChunk)
		{
			$branchHintChunk = $this->QuestFixNormalizeWhitespace($branchHintChunk);
			if( $branchHintChunk !== '' )
				$chatHintChunks[] = $branchHintChunk;
		}
		foreach((array)($branch['starter_texts'] ?? array()) as $branchHintChunk)
		{
			$branchHintChunk = $this->QuestFixNormalizeWhitespace((string)$branchHintChunk);
			if( $branchHintChunk !== '' )
				$chatHintChunks[] = $branchHintChunk;
		}
		foreach((array)($branch['completion_texts'] ?? array()) as $branchHintChunk)
		{
			$branchHintChunk = $this->QuestFixNormalizeWhitespace((string)$branchHintChunk);
			if( $branchHintChunk !== '' )
				$chatHintChunks[] = $branchHintChunk;
		}
		$dedupChatHintChunks = array();
		foreach($chatHintChunks as $chatHintChunk)
		{
			$key = strtolower($this->QuestFixNormalizeWhitespace((string)$chatHintChunk));
			if( $key !== '' && !isset($dedupChatHintChunks[$key]) )
				$dedupChatHintChunks[$key] = $chatHintChunk;
		}
		foreach(array_values($dedupChatHintChunks) as $chunk)
		{
			$chunkScore = $this->QuestFixWikiChunkScoreForBranch($chunk, $branch);
			if( $chunkScore < 18 )
				continue;
			$patterns = array(
				'/\b(?:speak|talk|return|report|deliver|bring)\s+(?:to|with)?\s*(?:the\s+)?([A-Z][A-Za-z`\'\-]+(?:\s+[A-Z][A-Za-z`\'\-]+){0,3})\b/u',
				'/\b(?:find|seek)\s+(?:the\s+)?([A-Z][A-Za-z`\'\-]+(?:\s+[A-Z][A-Za-z`\'\-]+){0,3})\b/u',
				'/\b((?:Assistant|Overseer|Captain|Commander|Elder|Lady|Lord|Mistress|Master|Sage|Seer|Keeper|Librarian)\s+[A-Z][A-Za-z`\'\-]+(?:\s+[A-Z][A-Za-z`\'\-]+){0,2})\b/u'
			);
			foreach($patterns as $pattern)
			{
				if( preg_match_all($pattern, $chunk, $mm) )
				{
					foreach($mm[1] as $value)
						$add($value, $chunkScore + 70);
				}
			}
		}
		// The raw target itself can be a valid proper name after alias cleanup.
		if( $target !== '' && preg_match('/[A-Z]/u', $target) )
			$add($target, 30);
		$ret = array_values($hints);
		usort($ret, function($a, $b) {
			if( (int)($a['score'] ?? 0) === (int)($b['score'] ?? 0) )
				return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
			return ((int)($a['score'] ?? 0) > (int)($b['score'] ?? 0)) ? -1 : 1;
		});
		return $ret;
	}

	private function QuestFixChatHintCompatibleWithTarget($target, $hintName)
	{
		$target = trim((string)$target);
		$hintName = trim((string)$hintName);
		if( $target === '' || $hintName === '' )
			return false;

		$norm = function($value) {
			$value = strtolower(str_replace('`', "'", (string)$value));
			$value = preg_replace('/[^a-z0-9\'\s\-]+/iu', ' ', $value);
			$value = preg_replace('/\s+/u', ' ', $value);
			return trim((string)$value);
		};
		$targetNorm = $norm($target);
		$hintNorm = $norm($hintName);
		if( $targetNorm === '' || $hintNorm === '' )
			return false;

		// A possessive relationship target is about the related role, not automatically
		// about the owner. Example: "D'Verin's Mistress" must not accept a pure
		// "D'Verin" hint just because it is a substring of the target.
		if( preg_match('/^(.+?)[\'`]s\s+(.+)$/u', $target, $relTarget) )
		{
			$ownerNorm = $norm((string)($relTarget[1] ?? ''));
			$roleNorm = $norm((string)($relTarget[2] ?? ''));
			if( $ownerNorm !== '' && $roleNorm !== '' && strpos($hintNorm, $ownerNorm) !== false && strpos($hintNorm, $roleNorm) === false )
				return false;
		}
		if( strpos($targetNorm, $hintNorm) !== false || strpos($hintNorm, $targetNorm) !== false )
			return true;

		// Specific proper names should not be displaced by unrelated wiki names.
		// Examples: Gierasa, D'Verin, Melicinn.  Relationship/role targets are the
		// exception because the page may reveal the actual NPC name.
		$isRelationshipTarget = preg_match('/\b(?:woman|man|nomad|mistress|master|assistant|overseer|librarian|sage|keeper|captain|commander|elder|seer)\b/iu', $target)
			|| preg_match('/^.+?[\'`]s\s+.+$/u', $target);
		$hasCommaProperAlias = false;
		if( strpos($target, ',') !== false )
		{
			$parts = array_map('trim', explode(',', $target));
			$last = trim((string)end($parts));
			$hasCommaProperAlias = (bool)preg_match('/^[A-Z][A-Za-z`\'\-]{2,}(?:\s+[A-Z][A-Za-z`\'\-]{2,}){0,2}$/u', $last);
		}
		if( $hasCommaProperAlias )
			return false;

		$targetTokens = $this->QuestFixUsefulIntentTokens($target);
		$hintTokens = $this->QuestFixUsefulIntentTokens($hintName);
		$overlap = array_intersect($targetTokens, $hintTokens);
		if( count($overlap) > 0 )
			return true;

		// Generic role-only targets ("the woman", "D'Verin's Mistress") may be
		// resolved by wiki text, but only as hints, never as unrelated overrides for
		// specific comma/proper-name targets.
		return $isRelationshipTarget;
	}

	private function QuestFixChatHintBoostCap($target, $hintName, $hintScore)
	{
		$target = trim((string)$target);
		$hintName = trim((string)$hintName);
		$cap = 90;
		if( preg_match('/^.+?[\'`]s\s+.+$/u', $target) || preg_match('/\b(?:woman|mistress|master|assistant|overseer|librarian)\b/iu', $target) )
			$cap = 165;
		if( strpos(strtolower(str_replace('`', "'", $target)), strtolower(str_replace('`', "'", $hintName))) !== false )
			$cap = 190;
		return min($cap, max(25, (int)$hintScore));
	}

	private function QuestFixFindChatCandidatesForBranch($branch, $limit = 12)
	{
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$target = trim((string)($analysis['target'] ?? ''));
		$zone = isset($analysis['zone']) && trim((string)$analysis['zone']) !== '' ? (string)$analysis['zone'] : (string)($branch['context_zone'] ?? '');
		$byId = array();
		$possessiveOwner = '';
		$possessiveRole = '';
		if( preg_match('/^(.+?)[\'`]s\s+(.+)$/u', $target, $rel) )
		{
			$possessiveOwner = trim((string)($rel[1] ?? ''));
			$possessiveRole = trim((string)($rel[2] ?? ''));
		}
		foreach($this->QuestFixFindChatCandidates($target, $zone, max(12, (int)$limit)) as $row)
		{
			$id = (int)($row['id'] ?? 0);
			if( $id <= 0 )
				continue;
			if( $possessiveOwner !== '' && $possessiveRole !== '' )
			{
				$nameNorm = $this->QuestFixNormalizedCompare((string)($row['name'] ?? ''));
				$ownerNorm = $this->QuestFixNormalizedCompare($possessiveOwner);
				$roleNorm = $this->QuestFixNormalizedCompare($possessiveRole);
				$ownerOnly = ($ownerNorm !== '' && strpos($nameNorm, $ownerNorm) !== false && ($roleNorm === '' || strpos($nameNorm, $roleNorm) === false));
				if( $ownerOnly )
				{
					$row['score'] = max(0, (int)($row['score'] ?? 0) - 300);
					$row['score_reasons'] = trim((string)($row['score_reasons'] ?? ''));
					$row['score_reasons'] = ($row['score_reasons'] !== '' ? $row['score_reasons'] . ', ' : '') . 'possessive relationship target: owner-only candidate deprioritized';
				}
			}
			$byId[$id] = $row;
		}
		foreach($this->QuestFixWikiChatNameHints($branch) as $hint)
		{
			$name = trim((string)($hint['name'] ?? ''));
			$hintScore = (int)($hint['score'] ?? 0);
			if( $name === '' || !$this->QuestFixChatHintCompatibleWithTarget($target, $name) )
				continue;
			$hintBoost = $this->QuestFixChatHintBoostCap($target, $name, $hintScore);
			foreach($this->QuestFixFindChatCandidates($name, $zone, max(12, (int)$limit)) as $row)
			{
				$id = (int)($row['id'] ?? 0);
				if( $id <= 0 )
					continue;
				$row['score'] = (int)($row['score'] ?? 0) + $hintBoost;
				if( $possessiveOwner !== '' && $possessiveRole !== '' )
				{
					$rowNameNorm = $this->QuestFixNormalizedCompare((string)($row['name'] ?? ''));
					$ownerNorm = $this->QuestFixNormalizedCompare($possessiveOwner);
					$hintNorm = $this->QuestFixNormalizedCompare($name);
					if( $hintNorm !== '' && ($ownerNorm === '' || strpos($hintNorm, $ownerNorm) === false) && ($ownerNorm === '' || strpos($rowNameNorm, $ownerNorm) === false) )
						$row['score'] += 120;
				}
				$row['score_reasons'] = trim((string)($row['score_reasons'] ?? ''));
				$row['score_reasons'] = ($row['score_reasons'] !== '' ? $row['score_reasons'] . ', ' : '') . 'compatible wiki chat-name hint: ' . $name;
				if( !isset($byId[$id]) || (int)$row['score'] > (int)($byId[$id]['score'] ?? 0) )
					$byId[$id] = $row;
			}
		}
		$ret = array_values($byId);
		usort($ret, function($a, $b) {
			if( (int)($a['score'] ?? 0) === (int)($b['score'] ?? 0) )
				return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
			return ((int)($a['score'] ?? 0) > (int)($b['score'] ?? 0)) ? -1 : 1;
		});
		return array_slice($ret, 0, max(1, min(50, (int)$limit)));
	}

	private function QuestFixWikiKillNameHints($branch)
	{
		$hints = array();
		$add = function($value, $score) use (&$hints) {
			$value = trim((string)$value, " \t\n\r\0\x0B.,;:!?\"'");
			$value = preg_replace('/\s+(?:for|from|at|in|near|by|within|outside|inside|to)\b.*$/iu', '', $value);
			$value = trim((string)$value, " \t\n\r\0\x0B.,;:!?\"'");
			if( $value === '' || strlen($value) < 3 )
				return;
			$key = strtolower($value);
			if( !isset($hints[$key]) || (int)$score > (int)$hints[$key]['score'] )
				$hints[$key] = array('name' => $value, 'score' => (int)$score);
		};
		foreach($this->QuestFixWikiTextChunksForBranch($branch) as $chunk)
		{
			$chunkScore = $this->QuestFixWikiChunkScoreForBranch($chunk, $branch);
			if( $chunkScore < 18 )
				continue;
			$patterns = array(
				'/\b(?:kill|slay|defeat|destroy|hunt|eliminate|vanquish|dispatch)\s+(?:(?:a|an|the|some)\s+)?(?:(?:\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty)\s+)?(.+?)(?=\s+(?:for|from|at|in|near|by|within|outside|inside|to)\b|[.!?]|$)/iu',
				'/\b(?:recover|collect|gather|loot|retrieve)\s+.+?\s+from\s+(?:the\s+)?(.+?)(?=\s+(?:at|in|near|by|within|outside|inside|to)\b|[.!?]|$)/iu'
			);
			foreach($patterns as $pattern)
			{
				if( preg_match_all($pattern, $chunk, $mm) )
				{
					foreach($mm[1] as $value)
						$add($value, $chunkScore + 60);
				}
			}
		}
		$ret = array_values($hints);
		usort($ret, function($a, $b) {
			if( (int)($a['score'] ?? 0) === (int)($b['score'] ?? 0) )
				return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
			return ((int)($a['score'] ?? 0) > (int)($b['score'] ?? 0)) ? -1 : 1;
		});
		return $ret;
	}

	private function QuestFixKillSearchPhrasesFromBranch($branch)
	{
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$texts = array();
		foreach(array(
			(string)($analysis['target'] ?? ''),
			(string)($analysis['raw_text'] ?? ''),
			(string)($branch['step_text'] ?? ''),
			(string)($branch['task_group_text'] ?? ''),
			(string)($branch['completion_text'] ?? '')
		) as $txt)
		{
			$txt = $this->QuestFixNormalizeWhitespace($txt);
			if( $txt !== '' )
				$texts[] = $txt;
		}
		foreach((array)($branch['starter_texts'] ?? array()) as $txt)
		{
			$txt = $this->QuestFixNormalizeWhitespace($txt);
			if( $txt !== '' )
				$texts[] = $txt;
		}
		foreach((array)($branch['completion_texts'] ?? array()) as $txt)
		{
			$txt = $this->QuestFixNormalizeWhitespace($txt);
			if( $txt !== '' )
				$texts[] = $txt;
		}

		$phrases = array();
		$add = function($value) use (&$phrases) {
			$value = trim((string)$value, " \\t\\n\\r\\0\\x0B.,;:!?\\\"'");
			$value = preg_replace('/\\s+(?:that|which|who|when|while|where|because|after|before)\\b.*$/iu', '', $value);
			$value = preg_replace('/\\s+(?:at|in|near|by|within|outside|inside|around|for|from|to)\\b.*$/iu', '', $value);
			$value = trim((string)$value, " \\t\\n\\r\\0\\x0B.,;:!?\\\"'");
			if( $value === '' || strlen($value) < 3 )
				return;
			$key = strtolower(str_replace('`', "'", $value));
			if( !isset($phrases[$key]) )
				$phrases[$key] = $value;
		};

		$add((string)($analysis['target'] ?? ''));
		foreach($this->QuestFixWikiKillNameHints($branch) as $hint)
			$add((string)($hint['name'] ?? ''));

		// Kill steps must be resolved against spawn names. The text can describe the drop item,
		// a generic monster family, or a later completion synonym. Extract all safe enemy-side phrases.
		$enemyNoun = '(?:assassins?|thugs?|raiders?|bandits?|brigands?|goblins?|orcs?|gnolls?|skeletons?|ambushers?|attackers?|enemies|invaders?|marauders?|smugglers?|lieutenants?|captains?|soldiers?|warriors?|guards?|souls?|ghosts?|spirits?|undead|zombies?|skeleton|soul|ghost|spirit|orc)';
		foreach($texts as $text)
		{
			// Gather/collect/recover X from Y: Y is the spawn target, not the obtained item.
			if( preg_match_all('/\\b(?:gather|collect|recover|retrieve|obtain|loot|take|get)\\b.+?\\bfrom\\s+(?:the\\s+|some\\s+)?(.+?)(?=[.!?]|$)/iu', $text, $fm) )
			{
				foreach($fm[1] as $phrase)
					$add($phrase);
			}

			// First, collect organization/family + enemy noun pairs such as
			// "Blackshield assassins" or "Blackshield thugs".
			if( preg_match_all('/\\b([A-Z][A-Za-z`\\x27\\-]+(?:\\s+[A-Z][A-Za-z`\\x27\\-]+){0,2}\\s+' . $enemyNoun . ')\\b/u', $text, $mm) )
			{
				foreach($mm[1] as $phrase)
					$add($phrase);
			}

			// Generic lower/upper descriptive enemy phrases from Census/Wiki/completion text:
			// "orc skeletons", "fallen orc lieutenants", "lost soul".
			if( preg_match_all('/\\b((?:[A-Za-z`\\x27\\-]+\\s+){0,3}' . $enemyNoun . ')\\b/iu', $text, $gm) )
			{
				foreach($gm[1] as $phrase)
				{
					$phrase = preg_replace('/^(?:the|some|several|many|more|other|these|those)\\s+/iu', '', trim((string)$phrase));
					$add($phrase);
				}
			}

			// Leadership wording often points at a rank/class spawn rather than the generic creature family.
			// Example class: "orc skeletons that appear to be leading the other undead" -> "orc lieutenant".
			if( preg_match('/\b(?:lead|leads|leader|leaders|leading|command|commands|commanding|officer|officers)\b/iu', $text) )
			{
				$leadFamilies = array();
				if( preg_match_all('/\b((?:fallen\s+|risen\s+|undead\s+)?(?:orc|gnoll|goblin|skeleton|undead|bandit|brigand|raider|assassin|thug)s?)\b/iu', $text, $lf) )
				{
					foreach($lf[1] as $family)
					{
						$family = strtolower(trim((string)$family));
						$family = preg_replace('/s$/iu', '', $family);
						if( $family !== '' )
							$leadFamilies[$family] = $family;
					}
				}
				foreach(array_values($leadFamilies) as $family)
				{
					foreach(array('lieutenant','captain','commander','leader','officer') as $rank)
						$add($family . ' ' . $rank);
				}
			}

			// Also collect possessive target phrases and their noun tails.
			if( preg_match_all('/\\b([A-Z][A-Za-z`\\x27\\-]+\\x27s\\s+' . $enemyNoun . ')\\b/u', $text, $pm) )
			{
				foreach($pm[1] as $phrase)
				{
					$add($phrase);
					if( preg_match('/^[A-Za-z][A-Za-z`\\x27\\-]*\\x27s\\s+(.+)$/u', $phrase, $tail) )
						$add($tail[1]);
				}
			}
		}
		return array_values($phrases);
	}

	private function QuestFixInferKillCountFromBranchEvidence($branch)
	{
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$phrases = $this->QuestFixKillSearchPhrasesFromBranch($branch);
		if( count($phrases) === 0 )
			return 0;
		$needles = array();
		foreach($phrases as $phrase)
		{
			$phrase = strtolower($this->QuestFixNormalizeWhitespace($phrase));
			if( $phrase !== '' )
				$needles[] = $phrase;
			$sing = strtolower($this->QuestFixNormalizeWhitespace($this->QuestFixSingularizeLastWord($phrase)));
			if( $sing !== '' )
				$needles[] = $sing;
		}
		$needles = array_values(array_unique($needles));
		$texts = array(
			(string)($analysis['raw_text'] ?? ''),
			(string)($branch['step_text'] ?? ''),
			(string)($branch['task_group_text'] ?? ''),
			(string)($branch['completion_text'] ?? '')
		);
		foreach((array)($branch['starter_texts'] ?? array()) as $txt)
			$texts[] = (string)$txt;
		foreach((array)($branch['completion_texts'] ?? array()) as $txt)
			$texts[] = (string)$txt;
		$candidates = array();
		foreach($texts as $text)
		{
			foreach((array)preg_split('/(?:\\R+|(?<=[.!?])\\s+)/u', (string)$text) as $chunk)
			{
				$chunk = $this->QuestFixNormalizeWhitespace((string)$chunk);
				if( $chunk === '' )
					continue;
				$lo = strtolower($chunk);
				$hits = 0;
				foreach($needles as $needle)
					if( $needle !== '' && strpos($lo, $needle) !== false )
						$hits++;
				if( $hits === 0 && !preg_match('/\\b(?:skulls?|heads?|bones?|goo|residue|samples?|essences?|pieces?)\\b/iu', $chunk) )
					continue;
				$patterns = array(
					'/\\b(?:kill|slay|defeat|destroy|hunt|eliminate|vanquish|dispatch|battle|fight)\\s+(?:up\\s+to\\s+|at\\s+least\\s+|the\\s+)?(\\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\\b/iu',
					'/\\b(?:gather|collect|recover|retrieve|loot|obtain|get)\\s+(?:up\\s+to\\s+|at\\s+least\\s+|the\\s+)?(\\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\\s+(?:[A-Za-z`\\x27\\-]+\\s+){0,3}(?:skulls?|heads?|bones?|goo|residue|samples?|essences?|pieces?|drops?|vials?)\\b/iu',
					'/\\b(\\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\\s+(?:[A-Za-z`\\x27\\-]+\\s+){0,3}(?:skulls?|heads?|bones?|goo|residue|samples?|essences?|pieces?|drops?|vials?)\\b/iu'
				);
				foreach($patterns as $pattern)
				{
					if( preg_match($pattern, $chunk, $matches) )
					{
						$count = $this->QuestFixCountFromToken($matches[1] ?? '');
						if( $count >= 2 && $count <= 99 )
						{
							$candidates[] = array('count' => $count, 'score' => $hits * 100 + (strlen($chunk) < 220 ? 15 : 0));
							break;
						}
					}
				}
			}
		}
		if( count($candidates) === 0 )
			return 0;
		usort($candidates, function($a, $b) {
			if( (int)$a['score'] === (int)$b['score'] )
				return (int)$b['count'] <=> (int)$a['count'];
			return ((int)$a['score'] > (int)$b['score']) ? -1 : 1;
		});
		return (int)($candidates[0]['count'] ?? 0);
	}

	private function QuestFixInferKillCountFromWiki($branch)
	{
		$wiki = isset($branch['wiki_data']) && is_array($branch['wiki_data']) ? $branch['wiki_data'] : array();
		$plain = isset($wiki['plain_text']) ? (string)$wiki['plain_text'] : '';
		if( trim($plain) === '' )
			return 0;
		$phrases = $this->QuestFixKillSearchPhrasesFromBranch($branch);
		if( count($phrases) === 0 )
			return 0;
		$needles = array();
		foreach($phrases as $phrase)
		{
			$phrase = strtolower($this->QuestFixNormalizeWhitespace($phrase));
			if( $phrase !== '' )
				$needles[] = $phrase;
			$sing = strtolower($this->QuestFixNormalizeWhitespace($this->QuestFixSingularizeLastWord($phrase)));
			if( $sing !== '' )
				$needles[] = $sing;
		}
		$needles = array_values(array_unique($needles));
		if( count($needles) === 0 )
			return 0;

		$chunks = preg_split('/(?:\R+|(?<=[.!?])\s+)/u', $plain);
		$candidates = array();
		foreach((array)$chunks as $chunk)
		{
			$chunk = $this->QuestFixNormalizeWhitespace((string)$chunk);
			if( $chunk === '' )
				continue;
			$lo = strtolower($chunk);
			$phraseHits = 0;
			foreach($needles as $needle)
			{
				if( $needle !== '' && strpos($lo, $needle) !== false )
					$phraseHits++;
			}
			if( $phraseHits === 0 )
				continue;
			if( !preg_match('/\b(?:kill|slay|defeat|destroy|hunt|eliminate|vanquish|dispatch|battle|fight|assassin|assassins|thug|thugs|raider|raiders|bandit|bandits|brigand|brigands|goblin|goblins|orc|orcs|gnoll|gnolls|smuggler|smugglers)\b/iu', $chunk) )
				continue;
			$matches = array();
			$patterns = array(
				'/\b(?:kill|slay|defeat|destroy|hunt|eliminate|vanquish|dispatch|battle|fight)\s+(?:up\s+to\s+|at\s+least\s+|the\s+)?(\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\b/iu',
				'/\b(?:gather|collect|recover|retrieve|loot|obtain)\s+(?:up\s+to\s+|at\s+least\s+|the\s+)?(\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\s+(?:[A-Za-z\'\-]+\s+){0,2}(?:skulls?|heads?|bones?|goo|residue|samples?|essences?|pieces?)\b/iu',
				'/\b(\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty)\s+(?:[A-Z][A-Za-z\'\-]+\s+)?(?:assassins?|thugs?|raiders?|bandits?|brigands?|goblins?|orcs?|gnolls?|smugglers?)\b/iu',
				'/\b(?:x|×)\s*(\d+)\b/iu',
				'/\(\s*(?:x|×)?\s*(\d+)\s*(?:x|×)?\s*\)/iu'
			);
			foreach($patterns as $pattern)
			{
				if( preg_match($pattern, $chunk, $matches) )
				{
					$count = $this->QuestFixCountFromToken($matches[1] ?? '');
					if( $count >= 2 && $count <= 99 )
					{
						$candidates[] = array('count' => $count, 'score' => $phraseHits * 100 + (strlen($chunk) < 220 ? 15 : 0));
						break;
					}
				}
			}
		}
		if( count($candidates) === 0 )
			return 0;
		usort($candidates, function($a, $b) {
			if( (int)$a['score'] === (int)$b['score'] )
				return (int)$b['count'] <=> (int)$a['count'];
			return ((int)$a['score'] > (int)$b['score']) ? -1 : 1;
		});
		return (int)($candidates[0]['count'] ?? 0);
	}

	private function QuestFixFindSpawnCandidatesForBranch($branch, $limit = 12)
	{
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$zone = isset($analysis['zone']) && trim((string)$analysis['zone']) !== '' ? (string)$analysis['zone'] : (string)($branch['context_zone'] ?? '');
		$phrases = $this->QuestFixKillSearchPhrasesFromBranch($branch);
		if( count($phrases) === 0 )
			return array();
		$byId = array();
		foreach($phrases as $phrase)
		{
			foreach($this->QuestFixFindSpawnCandidates($phrase, $zone, max(12, (int)$limit)) as $row)
			{
				$id = (int)($row['id'] ?? 0);
				if( $id <= 0 )
					continue;
				$row['score'] = (int)($row['score'] ?? 0);
				$row['score_reasons'] = trim((string)($row['score_reasons'] ?? ''));
				if( strtolower($phrase) !== strtolower((string)($analysis['target'] ?? '')) )
				{
					$row['score'] += 28;
					$row['score_reasons'] = ($row['score_reasons'] !== '' ? $row['score_reasons'] . ', ' : '') . 'branch context enemy phrase: ' . $phrase;
				}
				$phraseTokens = preg_split('/\s+/u', trim((string)$phrase));
				$phraseTokenCount = 0;
				foreach((array)$phraseTokens as $phraseToken)
				{
					$phraseToken = trim((string)$phraseToken);
					if( $phraseToken !== '' && strlen($phraseToken) >= 3 )
						$phraseTokenCount++;
				}
				if( $phraseTokenCount >= 2 )
				{
					$specificityBoost = min(90, 30 + (($phraseTokenCount - 2) * 20));
					$row['score'] += $specificityBoost;
					$row['score_reasons'] = ($row['score_reasons'] !== '' ? $row['score_reasons'] . ', ' : '') . 'multi-token enemy phrase specificity: ' . $phrase;
				}
				$branchLeadText = strtolower(implode(' ', array_filter(array(
					(string)($analysis['raw_text'] ?? ''),
					(string)($branch['step_text'] ?? ''),
					(string)($branch['task_group_text'] ?? ''),
					(string)($branch['completion_text'] ?? '')
				))));
				$hasLeadershipText = preg_match('/\b(?:lead|leads|leader|leaders|leading|command|commands|commanding|officer|officers)\b/iu', $branchLeadText);
				if( $hasLeadershipText && preg_match('/\b(?:lieutenant|captain|commander|leader|officer)\b/iu', (string)$phrase) )
				{
					$row['score'] += 110;
					$row['score_reasons'] = ($row['score_reasons'] !== '' ? $row['score_reasons'] . ', ' : '') . 'leadership-role enemy phrase: ' . $phrase;
				}
				if( $hasLeadershipText && preg_match('/\b(?:lieutenant|captain|commander|leader|officer)\b/iu', (string)($row['name'] ?? '')) )
				{
					$row['score'] += 95;
					$row['score_reasons'] = ($row['score_reasons'] !== '' ? $row['score_reasons'] . ', ' : '') . 'leadership-role spawn name';
				}
				if( $hasLeadershipText && preg_match('/^(?:a\s+|an\s+|the\s+)?(?:skeletons?|orcs?|undead|goblins?|gnolls?)$/iu', trim((string)($row['name'] ?? ''))) )
				{
					$row['score'] = max(0, (int)$row['score'] - 60);
					$row['score_reasons'] = ($row['score_reasons'] !== '' ? $row['score_reasons'] . ', ' : '') . 'generic family spawn deprioritized by leadership wording';
				}
				if( !isset($byId[$id]) || (int)$row['score'] > (int)($byId[$id]['score'] ?? 0) )
					$byId[$id] = $row;
			}
		}
		$ret = array_values($byId);
		usort($ret, function($a, $b) {
			if( (int)($a['score'] ?? 0) === (int)($b['score'] ?? 0) )
				return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
			return ((int)($a['score'] ?? 0) > (int)($b['score'] ?? 0)) ? -1 : 1;
		});
		return array_slice($ret, 0, max(1, min(50, (int)$limit)));
	}

	private function QuestFixRetypeFindIntentFromDb($branch)
	{
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$type = isset($analysis['step_type']) ? (string)$analysis['step_type'] : 'Unknown';
		if( ($type !== 'Location' && $type !== 'Obtain Item') || !empty($analysis['manual_override']) )
			return $branch;
		$raw = isset($analysis['raw_text']) ? (string)$analysis['raw_text'] : '';
		$target = isset($analysis['target']) ? trim((string)$analysis['target']) : '';
		$zone = isset($analysis['zone']) && trim((string)$analysis['zone']) !== '' ? (string)$analysis['zone'] : (string)($branch['context_zone'] ?? '');
		if( $target === '' )
			return $branch;

		// Gather/recover-from phrasing often describes a mob kill step whose Census label
		// focuses on the looted object rather than the actual hostile target.
		$combatProbe = $branch;
		$combatSource = '';
		if( $type === 'Obtain Item' && preg_match('/\b(?:gather|collect|recover|retrieve|obtain|loot)\b.+?\bfrom\s+(?:the\s+)?(.+?)(?=[.!?]|$)/iu', $raw, $sm) )
		{
			$combatSource = trim((string)$sm[1], " \t\n\r\0\x0B.,;:!?\"'");
			$combatSource = preg_replace('/\s+that\b.*$/iu', '', $combatSource);
			$combatSource = trim((string)$combatSource, " \t\n\r\0\x0B.,;:!?\"'");
			if( $combatSource !== '' )
			{
				$combatProbe['analysis']['target'] = $combatSource;
				$combatProbe['analysis']['target_singular'] = $this->QuestFixSingularizeLastWord($this->QuestFixStripArticle($combatSource));
				$combatProbe['analysis']['search_variants'] = $this->QuestFixBuildSearchVariants($combatSource);
			}
		}

		$killCandidates = $this->QuestFixFindSpawnCandidatesForBranch($combatProbe, 10);
		$bestAttackableKill = 0;
		foreach($killCandidates as $candidate)
		{
			if( isset($candidate['attackable']) && (int)$candidate['attackable'] === 1 )
				$bestAttackableKill = max($bestAttackableKill, (int)($candidate['score'] ?? 0));
		}
		$wikiKillHints = $this->QuestFixWikiKillNameHints($combatProbe);
		$bestWikiKillHint = count($wikiKillHints) > 0 ? (int)($wikiKillHints[0]['score'] ?? 0) : 0;

		if( $type === 'Obtain Item' && $combatSource !== '' && ($bestAttackableKill >= 95 || $bestWikiKillHint >= 70) )
		{
			$branch['analysis']['step_type'] = 'Kill';
			$branch['analysis']['auto_type'] = 'Kill';
			$branch['analysis']['target'] = $combatSource;
			$branch['analysis']['target_singular'] = $this->QuestFixSingularizeLastWord($this->QuestFixStripArticle($combatSource));
			$branch['analysis']['search_variants'] = $this->QuestFixBuildSearchVariants($combatSource);
			$branch['analysis']['confidence'] = max((int)($branch['analysis']['confidence'] ?? 0), 90);
			$branch['analysis']['notes'][] = 'Recover/gather-from wording retyped from Obtain Item to Kill using enemy/DB/wiki evidence.';
			return $branch;
		}

		if( $type !== 'Location' || !preg_match('/\b(?:find|locate|search(?:\s+for)?)\b/iu', $raw) )
			return $branch;
		$targetLo = strtolower($target);
		$locationNoun = preg_match('/\b(?:bridge|cave|camp|citadel|door|entrance|farm|forest|gate|gates|grove|hall|hill|isle|keep|lake|lair|monument|pass|pond|road|ruin|ruins|shrine|spire|stormhold|tower|vale|village|wood|woods)\b/iu', $targetLo);

		$enemyPhrase = preg_match('/\b(?:assassin|assassins|thug|thugs|raider|raiders|bandit|bandits|brigand|brigands|goblin|goblins|orc|orcs|gnoll|gnolls|skeleton|skeletons|soul|souls|ghost|ghosts|undead|ambusher|ambushers|attacker|attackers|enemy|enemies|invader|invaders|marauder|marauders|smuggler|smugglers)\b/iu', $targetLo);
		$looksPlural = preg_match('/\b[A-Za-z`\'\-]+s\b/u', $target) && !preg_match('/\b(?:Nikora|Stormhold)\b/u', $target);
		$findRecoverEnemy = preg_match('/\band\s+(?:recover|collect|retrieve|obtain|gather|loot)\b/iu', $raw);
		if( $bestAttackableKill >= 95 && ($enemyPhrase || $looksPlural || $findRecoverEnemy) )
		{
			$branch['analysis']['step_type'] = 'Kill';
			$branch['analysis']['auto_type'] = 'Kill';
			$branch['analysis']['confidence'] = max((int)($branch['analysis']['confidence'] ?? 0), 88);
			$branch['analysis']['notes'][] = 'DB/wiki-backed find-target retyped from Location to Kill.';
			return $branch;
		}

		$chatCandidates = $this->QuestFixFindChatCandidatesForBranch($branch, 6);
		$bestChat = count($chatCandidates) > 0 ? (int)($chatCandidates[0]['score'] ?? 0) : 0;
		$personLike = $this->QuestFixLooksLikePersonTarget($target);
		$singleProperName = preg_match('/^[A-Z][A-Za-z`\'\-]+$/u', $target);
		if( !$findRecoverEnemy && !$locationNoun && $bestChat >= 180 && ($personLike || $singleProperName || $bestChat >= 250) )
		{
			$branch['analysis']['step_type'] = 'Chat';
			$branch['analysis']['auto_type'] = 'Chat';
			$branch['analysis']['confidence'] = max((int)($branch['analysis']['confidence'] ?? 0), 90);
			$branch['analysis']['notes'][] = 'DB/wiki-backed find-target retyped from Location to Chat.';
			return $branch;
		}
		return $branch;
	}

	private function QuestFixResolveBranchMatches($branch)
	{
		$branch = $this->QuestFixRetypeFindIntentFromDb($branch);
		$branch['candidates'] = array();
		$branch['best_spawn_id'] = 0;
		$branch['best_spawn_ids'] = array();
		$branch['spawn_candidate_manual_selected'] = false;
		$branch['local_step_evidence'] = array();
		$branch['best_location'] = array();
		$branch['location_auto_selected'] = false;
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$type = isset($analysis['step_type']) ? $analysis['step_type'] : 'Unknown';
		$target = isset($analysis['target']) ? trim((string)$analysis['target']) : '';
		$zone = isset($analysis['zone']) && trim((string)$analysis['zone']) !== '' ? (string)$analysis['zone'] : (string)($branch['context_zone'] ?? '');
		if( $type === 'Kill' && (int)($branch['analysis']['count'] ?? 1) <= 1 )
		{
			$branchKillCount = $this->QuestFixInferKillCountFromBranchEvidence($branch);
			$wikiKillCount = $this->QuestFixInferKillCountFromWiki($branch);
			$strongKillCount = max((int)$branchKillCount, (int)$wikiKillCount);
			if( $strongKillCount > 1 )
			{
				$branch['analysis']['count'] = $strongKillCount;
				$sourceText = $branchKillCount >= $wikiKillCount ? 'Branch/Census text' : 'Wiki text';
				$branch['analysis']['notes'][] = $sourceText . ' indicates a stronger kill quota: ' . $strongKillCount . '.';
				$analysis['count'] = $strongKillCount;
			}
		}
		$localQuestId = (int)($branch['local_quest_id'] ?? 0);
		$stepNumber = (int)($branch['step_number'] ?? 0);
		if( $target === '' )
			return $branch;
		if( $type === 'Kill' )
		{
			$branch['candidates'] = $this->QuestFixFindSpawnCandidatesForBranch($branch, 40);
			if( count($branch['candidates']) === 0 )
				$branch['analysis']['notes'][] = 'Kill step requires a spawn match; no safe spawn candidate was found yet.';
		}
		elseif( $type === 'Chat' )
			$branch['candidates'] = $this->QuestFixFindChatCandidatesForBranch($branch, 40);
		elseif( $type === 'Location' )
		{
			$wikiLocationCandidates = $this->QuestFixFindWikiLocationCandidates($branch['wiki_data'] ?? array(), $branch, 24);
			$dbLocationCandidates = $this->QuestFixFindLocationCandidates($target, $zone, 40);
			$branch['candidates'] = array_merge($wikiLocationCandidates, $dbLocationCandidates);
			usort($branch['candidates'], function($a, $b) {
				if( (int)($a['score'] ?? 0) === (int)($b['score'] ?? 0) )
					return (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0);
				return ((int)($a['score'] ?? 0) > (int)($b['score'] ?? 0)) ? -1 : 1;
			});
		}
		if( ($type === 'Kill' || $type === 'Chat') && count($branch['candidates']) > 0 )
			$branch['candidates'] = $this->QuestFixAddScriptEvidenceToCandidates($branch['candidates'], $localQuestId, $stepNumber);

		if( $type === 'Kill' || $type === 'Chat' )
		{
			$localStepEvidence = $this->QuestFixLocalStepCallEvidence((string)($branch['local_quest_lua_script'] ?? ''), $stepNumber, $type);
			$branch['local_step_evidence'] = $localStepEvidence;
			if( !empty($localStepEvidence['found']) )
			{
				if( !empty($localStepEvidence['ids']) )
				{
					$branch['best_spawn_ids'] = array_values(array_unique(array_map('intval', $localStepEvidence['ids'])));
					$branch['best_spawn_id'] = (int)$branch['best_spawn_ids'][0];
				}
				if( isset($localStepEvidence['count']) && (int)$localStepEvidence['count'] > 0 )
					$branch['analysis']['count'] = (int)$localStepEvidence['count'];
				if( isset($localStepEvidence['icon_id']) && (int)$localStepEvidence['icon_id'] >= 0 )
					$branch['icon_id'] = (int)$localStepEvidence['icon_id'];
			}
		}

		if( count($branch['candidates']) > 0 )
		{
			$best = $branch['candidates'][0];
			if( $type === 'Location' )
			{
				$selectedLocationId = (int)($branch['selected_location_candidate_id'] ?? 0);
				$manualLocation = $this->QuestFixPickLocationCandidateById($branch['candidates'], $selectedLocationId);
				if( !empty($manualLocation) )
				{
					$branch['best_location'] = $manualLocation;
					$branch['location_auto_selected'] = false;
				}
				else
				{
					$autoLocation = $this->QuestFixLocationAutoSelectCandidate($branch['candidates']);
					if( !empty($autoLocation) )
					{
						$branch['best_location'] = $autoLocation;
						$branch['location_auto_selected'] = true;
					}
				}
			}
			else
			{
				$manualSpawnCandidates = $this->QuestFixPickSpawnCandidatesByIds($branch['candidates'], $branch['selected_spawn_candidate_ids'] ?? array());
				if( count($manualSpawnCandidates) > 0 )
				{
					$branch['best_spawn_ids'] = array();
					foreach($manualSpawnCandidates as $manualSpawnCandidate)
					{
						$manualSpawnId = (int)($manualSpawnCandidate['id'] ?? 0);
						if( $manualSpawnId > 0 && !in_array($manualSpawnId, $branch['best_spawn_ids'], true) )
							$branch['best_spawn_ids'][] = $manualSpawnId;
					}
					$branch['spawn_candidate_manual_selected'] = count($branch['best_spawn_ids']) > 0;
				}
				if( empty($branch['best_spawn_ids']) )
				{
					if( $type === 'Kill' )
						$branch['best_spawn_ids'] = $this->QuestFixStrongSpawnIdsFromCandidates($branch['candidates'], 12);
					if( empty($branch['best_spawn_ids']) )
						$branch['best_spawn_ids'] = array((int)($best['id'] ?? 0));
				}
				$branch['best_spawn_ids'] = array_values(array_filter(array_unique(array_map('intval', (array)$branch['best_spawn_ids'])), function($id) { return $id > 0; }));
				$branch['best_spawn_id'] = count($branch['best_spawn_ids']) > 0 ? (int)$branch['best_spawn_ids'][0] : (int)($best['id'] ?? 0);
			}
		}
		return $branch;
	}
	
	public function QuestFixApplyTypeOverrides($model, $overrides, $locationOverrides = array(), $spawnCandidateOverrides = array())
	{
		if( !is_array($model) || empty($model['ok']) )
			return $model;
		$valid = array_flip($this->QuestFixStepTypes());
		foreach($model['branches'] as $idx => $branch)
		{
			$step = isset($branch['step_number']) ? (int)$branch['step_number'] : 0;
			$selected = isset($overrides[$step]) ? trim((string)$overrides[$step]) : '';
			if( $selected !== '' && isset($valid[$selected]) )
			{
				$branch['analysis']['step_type'] = $selected;
				$branch['analysis']['manual_override'] = ($selected !== ($branch['analysis']['auto_type'] ?? 'Unknown'));
			}
			$branch['selected_location_candidate_id'] = isset($locationOverrides[$step]) ? (int)$locationOverrides[$step] : 0;
			$selectedSpawnCandidateIds = isset($spawnCandidateOverrides[$step]) ? $spawnCandidateOverrides[$step] : array();
			if( !is_array($selectedSpawnCandidateIds) )
				$selectedSpawnCandidateIds = array($selectedSpawnCandidateIds);
			$branch['selected_spawn_candidate_ids'] = array_values(array_filter(array_unique(array_map('intval', $selectedSpawnCandidateIds)), function($id) { return $id > 0; }));
			$branch = $this->QuestFixResolveBranchMatches($branch);
			$model['branches'][$idx] = $branch;
		}
		return $model;
	}

	private function QuestFixLuaEscape($text)
	{
		$text = $this->QuestFixNormalizeWhitespace($text);
		$text = str_replace('\\', '\\\\', $text);
		$text = str_replace('"', '\\"', $text);
		return $text;
	}
	
	public function QuestFixBuildKillLuaSnippet($stepNumber, $analysis, $spawnIds = array(), $taskGroupText = '', $completeAction = '', $iconId = 100, $progressPercent = 100)
	{
		$stepNumber = max(1, (int)$stepNumber);
		$iconId = max(0, (int)$iconId);
		$progressPercent = max(1, (int)$progressPercent);
		$ids = is_array($spawnIds) ? $spawnIds : array($spawnIds);
		$cleanIds = array();
		foreach($ids as $id)
		{
			$id = (int)$id;
			if( $id > 0 && !in_array($id, $cleanIds, true) )
				$cleanIds[] = $id;
		}
		$count = isset($analysis['count']) ? max(1, (int)$analysis['count']) : 1;
		$stepText = isset($analysis['raw_text']) ? $this->QuestFixLuaEscape($analysis['raw_text']) : '';
		$taskGroupText = trim((string)$taskGroupText) !== '' ? $this->QuestFixLuaEscape($taskGroupText) : $stepText;
		$completeAction = trim((string)$completeAction);
		if( $completeAction === '' )
			$completeAction = 'Step' . $stepNumber . 'Complete';
		$completeAction = $this->QuestFixLuaEscape($completeAction);
		$idsTail = count($cleanIds) > 0 ? ', ' . implode(', ', $cleanIds) : '';
		return sprintf(
			"AddQuestStepKill(Quest, %d, \"%s\", %d, %d, \"%s\", %d%s)\nAddQuestStepCompleteAction(Quest, %d, \"%s\")",
			$stepNumber, $stepText, $count, $progressPercent, $taskGroupText, $iconId, $idsTail, $stepNumber, $completeAction
		);
	}
	
	public function QuestFixBuildChatLuaSnippet($stepNumber, $analysis, $spawnIds = array(), $taskGroupText = '', $completeAction = '', $iconId = 11)
	{
		$stepNumber = max(1, (int)$stepNumber);
		$iconId = max(0, (int)$iconId);
		$ids = is_array($spawnIds) ? $spawnIds : array($spawnIds);
		$cleanIds = array();
		foreach($ids as $id)
		{
			$id = (int)$id;
			if( $id > 0 && !in_array($id, $cleanIds, true) )
				$cleanIds[] = $id;
		}
		$count = isset($analysis['count']) ? max(1, (int)$analysis['count']) : 1;
		$stepText = isset($analysis['raw_text']) ? $this->QuestFixLuaEscape($analysis['raw_text']) : '';
		$taskGroupText = trim((string)$taskGroupText) !== '' ? $this->QuestFixLuaEscape($taskGroupText) : $stepText;
		$completeAction = trim((string)$completeAction);
		if( $completeAction === '' )
			$completeAction = 'Step' . $stepNumber . 'Complete';
		$completeAction = $this->QuestFixLuaEscape($completeAction);
		$idsTail = count($cleanIds) > 0 ? ', ' . implode(', ', $cleanIds) : '';
		return sprintf(
			"AddQuestStepChat(Quest, %d, \"%s\", %d, \"%s\", %d%s)\nAddQuestStepCompleteAction(Quest, %d, \"%s\")",
			$stepNumber, $stepText, $count, $taskGroupText, $iconId, $idsTail, $stepNumber, $completeAction
		);
	}
	
	public function QuestFixBuildLocationLuaSnippet($stepNumber, $analysis, $location = array(), $taskGroupText = '', $completeAction = '', $iconId = 11)
	{
		$stepNumber = max(1, (int)$stepNumber);
		$iconId = max(0, (int)$iconId);
		$radius = 30;
		$x = isset($location['x']) ? (float)$location['x'] : 0;
		$y = isset($location['y']) ? (float)$location['y'] : 0;
		$z = isset($location['z']) ? (float)$location['z'] : 0;
		$zoneId = isset($location['source_zone_id']) ? (int)$location['source_zone_id'] : 0;
		$stepText = isset($analysis['raw_text']) ? $this->QuestFixLuaEscape($analysis['raw_text']) : '';
		$taskGroupText = trim((string)$taskGroupText) !== '' ? $this->QuestFixLuaEscape($taskGroupText) : $stepText;
		$completeAction = trim((string)$completeAction);
		if( $completeAction === '' )
			$completeAction = 'Step' . $stepNumber . 'Complete';
		$completeAction = $this->QuestFixLuaEscape($completeAction);
		return sprintf(
			"AddQuestStepZoneLoc(Quest, %d, \"%s\", %d, \"%s\", %d, %s, %s, %s, %d)\nAddQuestStepCompleteAction(Quest, %d, \"%s\")",
			$stepNumber,
			$stepText,
			$radius,
			$taskGroupText,
			$iconId,
			$this->QuestFixLuaNumber($x),
			$this->QuestFixLuaNumber($y),
			$this->QuestFixLuaNumber($z),
			$zoneId,
			$stepNumber,
			$completeAction
		);
	}
	
	private function QuestFixLuaNumber($value)
	{
		$value = (float)$value;
		if( abs($value - round($value)) < 0.00001 )
			return (string)(int)round($value);
		return rtrim(rtrim(number_format($value, 5, '.', ''), '0'), '.');
	}

	private function QuestFixBuildUnsupportedLuaSnippet($branch, $completeAction = '')
	{
		$step = isset($branch['step_number']) ? (int)$branch['step_number'] : 0;
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$type = isset($analysis['step_type']) ? $analysis['step_type'] : 'Unknown';
		$text = isset($branch['step_text']) ? $this->QuestFixLuaEscape($branch['step_text']) : '';
		$target = isset($analysis['target']) ? $this->QuestFixLuaEscape($analysis['target']) : '';
		$lines = array();
		$lines[] = '-- TODO Step ' . $step . ': ' . $type . ' selected, but this build does not yet emit a safe final Lua line for that type.';
		$lines[] = '-- Census text: "' . $text . '"';
		if( $target !== '' )
			$lines[] = '-- Parsed target: "' . $target . '"';
		return implode("\n", $lines);
	}


	private function QuestFixCensusNormalizeOption($option)
	{
		$option = trim((string)$option);
		$option = ltrim($option, "?& ");
		if( $option === '' )
			return '';
		
		$allowed = array();
		foreach(explode('&', $option) as $part)
		{
			$part = trim($part);
			if( $part === '' || strpos($part, '=') === false )
				continue;
			list($key, $value) = explode('=', $part, 2);
			$key = trim($key);
			$value = trim($value);
			if( !preg_match('/^[A-Za-z0-9_.:-]+$/', $key) )
				continue;
			$allowed[] = rawurlencode($key) . '=' . rawurlencode($value);
		}
		return implode('&', $allowed);
	}
	
	private function QuestFixCensusRequest($query)
	{
		$query = ltrim(trim((string)$query), '?&');
		$url = 'https://census.daybreakgames.com/json/get/eq2/quest/?' . $query;
		$ctx = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'timeout' => 20,
				'ignore_errors' => true,
				'header' => "User-Agent: EQ2Emu-Editor-QuestFixAssistant/0.2\r\nAccept: application/json,text/plain;q=0.9,*/*;q=0.8\r\n"
			)
		));
		$jsonText = @file_get_contents($url, false, $ctx);
		if( $jsonText === false || trim($jsonText) === '' )
		{
			return array(
				'ok' => false,
				'url' => $url,
				'error' => 'Census request returned no data. Check internet access from the editor container or try the optional CRC filter.'
			);
		}
		$data = json_decode($jsonText, true);
		if( !is_array($data) )
		{
			return array(
				'ok' => false,
				'url' => $url,
				'error' => 'Census returned invalid JSON: ' . json_last_error_msg()
			);
		}
		return array('ok' => true, 'url' => $url, 'data' => $data, 'json_text' => $jsonText);
	}
	
	private function QuestFixCensusAsList($value)
	{
		if( !is_array($value) )
			return array();
		if( count($value) === 0 )
			return array();
		$keys = array_keys($value);
		$isSequential = ($keys === range(0, count($value) - 1));
		return $isSequential ? $value : array($value);
	}
	
	private function QuestFixCensusListByKey($node, $key)
	{
		if( !is_array($node) || !array_key_exists($key, $node) )
			return array();
		$value = $node[$key];
		if( is_array($value) && array_key_exists(substr($key, 0, -5), $value) )
			return $this->QuestFixCensusAsList($value[substr($key, 0, -5)]);
		return $this->QuestFixCensusAsList($value);
	}
	
	private function QuestFixCensusTextList($node, $listKey, $textKey)
	{
		$ret = array();
		$list = $this->QuestFixCensusListByKey($node, $listKey);
		foreach($list as $item)
		{
			$value = '';
			if( is_array($item) && array_key_exists($textKey, $item) )
				$value = $item[$textKey];
			elseif( is_string($item) || is_numeric($item) )
				$value = $item;
			elseif( is_array($item) && count($item) === 1 )
			{
				$only = reset($item);
				if( is_string($only) || is_numeric($only) )
					$value = $only;
			}
			$value = $this->QuestFixNormalizeWhitespace((string)$value);
			if( $value !== '' )
				$ret[] = $value;
		}
		return array_values(array_unique($ret));
	}
	
	private function QuestFixCensusQuestSummary($quest)
	{
		$quest = is_array($quest) ? $quest : array();
		return array(
			'id' => isset($quest['id']) ? (string)$quest['id'] : '',
			'crc' => isset($quest['crc']) ? (string)$quest['crc'] : '',
			'name' => isset($quest['name']) ? (string)$quest['name'] : '',
			'category' => isset($quest['category']) ? (string)$quest['category'] : '',
			'level' => isset($quest['level']) ? (string)$quest['level'] : '',
			'tier' => isset($quest['tier']) ? (string)$quest['tier'] : '',
			'json' => json_encode($quest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
		);
	}
	
	public function QuestFixCensusSearchByName($questName, $option = '')
	{
		$questName = $this->QuestFixNormalizeWhitespace($questName);
		if( $questName === '' )
			return array('ok' => false, 'error' => 'Please enter a Census quest name.', 'quests' => array());
		
		$query = 'name=' . rawurlencode($questName) . '&c%3Alimit=20';
		$normalizedOption = $this->QuestFixCensusNormalizeOption($option);
		if( $normalizedOption !== '' )
			$query .= '&' . $normalizedOption;
		$response = $this->QuestFixCensusRequest($query);
		if( !$response['ok'] )
		{
			$response['quests'] = array();
			return $response;
		}
		$questRows = isset($response['data']['quest_list']) ? $this->QuestFixCensusAsList($response['data']['quest_list']) : array();
		$quests = array();
		foreach($questRows as $quest)
		{
			if( is_array($quest) )
				$quests[] = $this->QuestFixCensusQuestSummary($quest);
		}
		$response['quests'] = $quests;
		$response['quest_count'] = count($quests);
		return $response;
	}
	
	public function QuestFixCensusDecodeQuestJson($questJson)
	{
		$questJson = trim((string)$questJson);
		if( $questJson === '' )
			return array('ok' => false, 'error' => 'No Census quest JSON was supplied.');
		$quest = json_decode($questJson, true);
		if( !is_array($quest) )
			return array('ok' => false, 'error' => 'Invalid Census quest JSON: ' . json_last_error_msg());
		return array('ok' => true, 'quest' => $this->QuestFixCensusQuestSummary($quest), 'quest_data' => $quest);
	}
	
	public function QuestFixCensusBuildQuestModel($quest)
	{
		if( !is_array($quest) )
			return array('ok' => false, 'error' => 'Quest JSON node is missing.');
		$summary = $this->QuestFixCensusQuestSummary($quest);
		$localQuest = $this->QuestFixFindLocalQuestByName($summary['name'] ?? '');
		$wikiData = $this->QuestFixWikiQuestData($summary['name'] ?? '');
		$localLuaComplexity = $this->QuestFixLocalLuaComplexity((string)($localQuest['lua_script'] ?? ''));
		$model = array(
			'ok' => true,
			'summary' => $summary,
			'local_quest' => $localQuest,
			'local_lua_complexity' => $localLuaComplexity,
			'wiki' => $wikiData,
			'quest_starter_texts' => $this->QuestFixCensusTextList($quest, 'starter_text_list', 'starter_text'),
			'quest_completion_texts' => $this->QuestFixCensusTextList($quest, 'completion_text_list', 'completion_text'),
			'stages' => array(),
			'branches' => array(),
			'warnings' => array()
		);
		$stageRows = $this->QuestFixCensusListByKey($quest, 'stage_list');
		if( count($stageRows) === 0 && isset($quest['stage']) )
			$stageRows = $this->QuestFixCensusAsList($quest['stage']);
		if( count($stageRows) === 0 )
		{
			$model['warnings'][] = 'Census returned no stage rows for this quest.';
			return $model;
		}
		$stepNumber = 1;
		foreach($stageRows as $stageRow)
		{
			if( !is_array($stageRow) )
				continue;
			$stageNum = isset($stageRow['num']) ? (int)$stageRow['num'] : count($model['stages']);
			$stage = array(
				'num' => $stageNum,
				'starter_texts' => $this->QuestFixCensusTextList($stageRow, 'starter_text_list', 'starter_text'),
				'completion_texts' => $this->QuestFixCensusTextList($stageRow, 'completion_text_list', 'completion_text'),
				'branches' => array()
			);
			$branchRows = $this->QuestFixCensusListByKey($stageRow, 'branch_list');
			if( count($branchRows) === 0 && isset($stageRow['branch']) )
				$branchRows = $this->QuestFixCensusAsList($stageRow['branch']);
			foreach($branchRows as $branchRow)
			{
				if( !is_array($branchRow) )
					continue;
				$description = isset($branchRow['description']) ? $this->QuestFixNormalizeWhitespace((string)$branchRow['description']) : '';
				$starterTexts = $this->QuestFixCensusTextList($branchRow, 'starter_text_list', 'starter_text');
				$completionTexts = $this->QuestFixCensusTextList($branchRow, 'completion_text_list', 'completion_text');
				$stepText = $description !== '' ? $description : (count($starterTexts) > 0 ? $starterTexts[0] : '');
				$completionText = count($completionTexts) > 0 ? $completionTexts[0] : $description;
				$taskGroupText = count($stage['starter_texts']) > 0 ? $stage['starter_texts'][0] : $stepText;
				$analysis = $this->QuestFixAnalyzeText($stepText);
				$localTypeEvidence = $this->QuestFixLocalStepTypeEvidence((string)($localQuest['lua_script'] ?? ''), $stepNumber);
				if( !empty($localTypeEvidence['found']) && in_array((string)($localTypeEvidence['type'] ?? ''), array('Kill', 'Chat', 'Location'), true) )
				{
					$analysis['step_type'] = (string)$localTypeEvidence['type'];
					$analysis['auto_type'] = (string)$localTypeEvidence['type'];
					$analysis['confidence'] = 100;
					$analysis['notes'][] = 'Local Lua step function confirms type ' . (string)$localTypeEvidence['type'] . '.';
				}
				$quotaMin = isset($branchRow['quota_min']) ? max(1, (int)$branchRow['quota_min']) : 1;
				$quotaMax = isset($branchRow['quota_max']) ? max(1, (int)$branchRow['quota_max']) : $quotaMin;
				$iconId = isset($branchRow['icon_id']) ? (int)$branchRow['icon_id'] : 0;
				// Census quota semantics:
				// - 1..N is usually a fixed N-count objective tracked from first progress to completion.
				// - M..N with M > 1 can describe a true variable/random amount and is handled by the
				//   complex-plan detector only when no explicit fixed count is visible in text.
				if( (int)$analysis['count'] <= 1 )
				{
					if( $quotaMin <= 1 && $quotaMax > 1 )
						$analysis['count'] = $quotaMax;
					elseif( $quotaMin > 1 && $quotaMax === $quotaMin )
						$analysis['count'] = $quotaMin;
				}
				$branch = array(
					'step_number' => $stepNumber++,
					'stage_num' => $stageNum,
					'description' => $description,
					'step_text' => $stepText,
					'task_group_text' => $taskGroupText,
					'completion_text' => $completionText,
					'starter_texts' => $starterTexts,
					'completion_texts' => $completionTexts,
					'analysis' => $analysis,
					'context_zone' => (string)($summary['category'] ?? ''),
					'wiki_data' => $wikiData,
					'local_quest_id' => (int)($localQuest['id'] ?? 0),
					'local_quest_lua_script' => (string)($localQuest['lua_script'] ?? ''),
					'quota_min' => $quotaMin,
					'quota_max' => $quotaMax,
					'icon_id' => $iconId,
					'candidates' => array(),
					'best_spawn_id' => 0,
					'best_spawn_ids' => array(),
					'local_step_evidence' => array(),
					'best_location' => array()
				);
				$branch = $this->QuestFixResolveBranchMatches($branch);
				$stage['branches'][] = $branch;
				$model['branches'][] = $branch;
			}
			$model['stages'][] = $stage;
		}
		return $model;
	}
	
	private function QuestFixSOEQuestSummary($quest)
	{
		$quest = is_array($quest) ? $quest : array();
		return array(
			'id' => isset($quest['quest_id']) ? (string)$quest['quest_id'] : (isset($quest['id']) ? (string)$quest['id'] : ''),
			'crc' => isset($quest['soe_quest_crc']) ? (string)$quest['soe_quest_crc'] : '',
			'name' => isset($quest['name']) ? (string)$quest['name'] : '',
			'category' => isset($quest['category']) ? (string)$quest['category'] : '',
			'level' => isset($quest['level']) ? (string)$quest['level'] : '',
			'tier' => isset($quest['tier']) ? (string)$quest['tier'] : '',
			'repeatable' => isset($quest['repeatable']) ? (string)$quest['repeatable'] : '',
			'source' => 'soe_reference_tables',
			'json' => json_encode($quest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
		);
	}

	public function QuestFixBuildQuestModelFromSOEQuestId($questId)
	{
		$questId = (int)$questId;
		if( $questId <= 0 )
			return array('ok' => false, 'error' => 'No SOE quest id was supplied.');
		if( !$this->HasSOEQuestSchema() )
			return array('ok' => false, 'error' => 'SOE quest reference tables are not installed.');

		$quest = $this->GetSOEQuest($questId);
		if( !is_array($quest) || count($quest) === 0 )
			return array('ok' => false, 'error' => 'SOE quest reference row was not found for id ' . $questId . '.');

		$summary = $this->QuestFixSOEQuestSummary($quest);
		$localQuest = $this->QuestFixFindLocalQuestByName($summary['name'] ?? '');
		$wikiData = $this->QuestFixWikiQuestData($summary['name'] ?? '');
		$localLuaComplexity = $this->QuestFixLocalLuaComplexity((string)($localQuest['lua_script'] ?? ''));
		$model = array(
			'ok' => true,
			'source' => 'soe_reference_tables',
			'summary' => $summary,
			'local_quest' => $localQuest,
			'local_lua_complexity' => $localLuaComplexity,
			'wiki' => $wikiData,
			'quest_starter_texts' => array(),
			'quest_completion_texts' => array(),
			'stages' => array(),
			'branches' => array(),
			'warnings' => array()
		);

		$stages = $this->GetSOEQuestStages($questId);
		if( !is_array($stages) || count($stages) === 0 )
		{
			$model['warnings'][] = 'The SOE quest reference tables contain no stage rows for this quest.';
			return $model;
		}

		$stepNumber = 1;
		foreach($stages as $stageRow)
		{
			if( !is_array($stageRow) )
				continue;
			$stageNum = isset($stageRow['stage_num']) ? (int)$stageRow['stage_num'] : $stepNumber;
			$description = isset($stageRow['description']) ? $this->QuestFixNormalizeWhitespace((string)$stageRow['description']) : '';
			$completedText = isset($stageRow['completed_text']) ? $this->QuestFixNormalizeWhitespace((string)$stageRow['completed_text']) : '';
			$completedZone = isset($stageRow['completed_zone']) ? $this->QuestFixNormalizeWhitespace((string)$stageRow['completed_zone']) : '';
			$completedZoneOverride = isset($stageRow['completed_zone_override']) ? $this->QuestFixNormalizeWhitespace((string)$stageRow['completed_zone_override']) : '';
			$stepText = $description !== '' ? $description : ($completedText !== '' ? $completedText : '');
			$completionText = $completedText !== '' ? $completedText : $stepText;
			$taskGroupText = $stepText;
			$analysis = $this->QuestFixAnalyzeText($stepText);
			$localTypeEvidence = $this->QuestFixLocalStepTypeEvidence((string)($localQuest['lua_script'] ?? ''), $stepNumber);
			if( !empty($localTypeEvidence['found']) && in_array((string)($localTypeEvidence['type'] ?? ''), array('Kill', 'Chat', 'Location'), true) )
			{
				$analysis['step_type'] = (string)$localTypeEvidence['type'];
				$analysis['auto_type'] = (string)$localTypeEvidence['type'];
				$analysis['confidence'] = 100;
				$analysis['notes'][] = 'Local Lua step function confirms type ' . (string)$localTypeEvidence['type'] . '.';
			}

			$quotaMin = isset($stageRow['quota_min']) ? max(1, (int)$stageRow['quota_min']) : 1;
			$quotaMax = isset($stageRow['quota_max']) ? max(1, (int)$stageRow['quota_max']) : $quotaMin;
			$iconId = isset($stageRow['icon_id']) ? (int)$stageRow['icon_id'] : 0;
			if( (int)($analysis['count'] ?? 1) <= 1 )
			{
				if( $quotaMin <= 1 && $quotaMax > 1 )
					$analysis['count'] = $quotaMax;
				elseif( $quotaMin > 1 && $quotaMax === $quotaMin )
					$analysis['count'] = $quotaMin;
			}

			$branch = array(
				'step_number' => $stepNumber++,
				'stage_num' => $stageNum,
				'description' => $description,
				'step_text' => $stepText,
				'task_group_text' => $taskGroupText,
				'completion_text' => $completionText,
				'completed_zone' => $completedZone,
				'completed_zone_override' => $completedZoneOverride,
				'starter_texts' => array(),
				'completion_texts' => $completionText !== '' ? array($completionText) : array(),
				'analysis' => $analysis,
				'context_zone' => (string)($summary['category'] ?? ''),
				'wiki_data' => $wikiData,
				'local_quest_id' => (int)($localQuest['id'] ?? 0),
				'local_quest_lua_script' => (string)($localQuest['lua_script'] ?? ''),
				'quota_min' => $quotaMin,
				'quota_max' => $quotaMax,
				'icon_id' => $iconId,
				'candidates' => array(),
				'best_spawn_id' => 0,
				'best_spawn_ids' => array(),
				'local_step_evidence' => array(),
				'best_location' => array()
			);
			$branch = $this->QuestFixResolveBranchMatches($branch);
			$stage = array(
				'num' => $stageNum,
				'starter_texts' => array(),
				'completion_texts' => $completionText !== '' ? array($completionText) : array(),
				'branches' => array($branch)
			);
			$model['stages'][] = $stage;
			$model['branches'][] = $branch;
		}
		return $model;
	}

	private function QuestFixLuaSlug($text)
	{
		$text = strtolower($this->QuestFixNormalizeWhitespace($text));
		$text = preg_replace('/[^a-z0-9]+/i', '_', $text);
		$text = trim($text, '_');
		return $text !== '' ? $text : 'generated_quest';
	}
	
	private function QuestFixTaskCompletionTextForBranch($model, $branch)
	{
		$stageNum = isset($branch['stage_num']) ? (int)$branch['stage_num'] : 0;
		if( isset($model['stages']) && is_array($model['stages']) )
		{
			foreach($model['stages'] as $stage)
			{
				if( (int)($stage['num'] ?? -1) === $stageNum && isset($stage['completion_texts']) && count($stage['completion_texts']) > 0 )
					return $stage['completion_texts'][0];
			}
		}
		return isset($branch['completion_text']) && trim((string)$branch['completion_text']) !== '' ? $branch['completion_text'] : ($branch['step_text'] ?? '');
	}
	
	private function QuestFixBuildStepSnippet($branch, $completeAction)
	{
		$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
		$type = isset($analysis['step_type']) ? $analysis['step_type'] : 'Unknown';
		$step = isset($branch['step_number']) ? (int)$branch['step_number'] : 1;
		$iconId = isset($branch['icon_id']) ? (int)$branch['icon_id'] : 0;
		$hasLocalIcon = !empty($branch['local_step_evidence']['found']) && array_key_exists('icon_id', (array)$branch['local_step_evidence']);
		$resolvedKillIcon = $hasLocalIcon ? (int)$branch['local_step_evidence']['icon_id'] : ($iconId > 0 ? $iconId : 100);
		$resolvedChatIcon = $hasLocalIcon ? (int)$branch['local_step_evidence']['icon_id'] : ($iconId > 0 ? $iconId : 11);
		if( $type === 'Kill' )
			return $this->QuestFixBuildKillLuaSnippet($step, $analysis, !empty($branch['best_spawn_ids']) ? $branch['best_spawn_ids'] : array((int)($branch['best_spawn_id'] ?? 0)), $branch['task_group_text'] ?? '', $completeAction, $resolvedKillIcon, isset($branch['local_step_evidence']['progress_percent']) ? (int)$branch['local_step_evidence']['progress_percent'] : 100);
		if( $type === 'Chat' )
			return $this->QuestFixBuildChatLuaSnippet($step, $analysis, !empty($branch['best_spawn_ids']) ? $branch['best_spawn_ids'] : array((int)($branch['best_spawn_id'] ?? 0)), $branch['task_group_text'] ?? '', $completeAction, $resolvedChatIcon);
		if( $type === 'Location' && !empty($branch['best_location']) )
			return $this->QuestFixBuildLocationLuaSnippet($step, $analysis, $branch['best_location'], $branch['task_group_text'] ?? '', $completeAction, $iconId > 0 ? $iconId : 11);
		return $this->QuestFixBuildUnsupportedLuaSnippet($branch, $completeAction);
	}
	

	private function QuestFixComplexGenerationPlan($model)
	{
		$ret = array(
			'enabled' => false,
			'pattern' => '',
			'first_branch_index' => -1,
			'quantity_min' => 0,
			'quantity_max' => 0,
			'repeatable_hint' => false,
			'reasons' => array()
		);
		if( !is_array($model) || empty($model['ok']) || empty($model['branches']) || !is_array($model['branches']) )
			return $ret;

		// First complex generation template:
		// persisted random Kill quantity. IMPORTANT: Census quota_min/quota_max are NOT by
		// themselves proof of random gameplay. A very common fixed-count branch is stored as
		// 1..N (for example "slay fifteen huntresses" => 1..15). Treating every 1..N range
		// as random produced broken Luas.
		//
		// Safe rule for auto-generating random quantity:
		// - Kill branch
		// - quota_min > 1 and quota_max > quota_min (range does not begin at ordinary progress 1)
		// - no explicit fixed kill count has already been parsed from the visible branch text
		// This preserves repeatable/random patterns like 8..12 while keeping ordinary fixed
		// 1..15 kill quests deterministic.
		foreach(array_values($model['branches']) as $idx => $branch)
		{
			$analysis = isset($branch['analysis']) && is_array($branch['analysis']) ? $branch['analysis'] : array();
			$type = isset($analysis['step_type']) ? (string)$analysis['step_type'] : 'Unknown';
			$qmin = isset($branch['quota_min']) ? max(1, (int)$branch['quota_min']) : 1;
			$qmax = isset($branch['quota_max']) ? max(1, (int)$branch['quota_max']) : $qmin;
			$parsedCount = isset($analysis['count']) ? max(1, (int)$analysis['count']) : 1;

			if( $type !== 'Kill' || $qmax <= $qmin )
				continue;
			if( $qmin <= 1 )
				continue; // 1..N is ordinary fixed progress storage, not random quantity evidence.
			if( $parsedCount > 1 )
				continue; // The step text already states a deterministic amount.

			$ret['enabled'] = true;
			$ret['pattern'] = 'random_kill_quantity';
			$ret['first_branch_index'] = (int)$idx;
			$ret['quantity_min'] = $qmin;
			$ret['quantity_max'] = $qmax;
			$ret['reasons'][] = 'Census kill branch has non-progress quota range ' . $qmin . '-' . $qmax . ' without explicit fixed count text';
			break;
		}
		if( !$ret['enabled'] )
			return $ret;

		// Conservative repeatable hinting: only emit SetQuestRepeatable when the raw Census/Wiki
		// text actually says so. Random quota and persistent flags are useful even for one-shot quests.
		$searchText = '';
		if( isset($model['summary']['json']) )
			$searchText .= ' ' . (string)$model['summary']['json'];
		if( isset($model['wiki']['plain_text']) )
			$searchText .= ' ' . (string)$model['wiki']['plain_text'];
		if( preg_match('/\b(?:repeatable|repeat\s+this\s+quest|may\s+be\s+repeated|can\s+be\s+repeated)\b/iu', $searchText) )
		{
			$ret['repeatable_hint'] = true;
			$ret['reasons'][] = 'repeatable marker found in source text';
		}
		return $ret;
	}

	private function QuestFixBuildKillLuaSnippetWithCountExpression($stepNumber, $analysis, $countExpression, $spawnIds = array(), $taskGroupText = '', $completeAction = '', $iconId = 100, $progressPercent = 100)
	{
		$stepNumber = max(1, (int)$stepNumber);
		$iconId = max(0, (int)$iconId);
		$progressPercent = max(1, (int)$progressPercent);
		$countExpression = trim((string)$countExpression);
		if( $countExpression === '' )
			$countExpression = '1';
		// Keep this limited to a Lua identifier or numeric literal. This helper is internal and the
		// current caller only passes "quantity", but validate anyway to avoid malformed output.
		if( !preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*|\d+)$/', $countExpression) )
			$countExpression = '1';

		$ids = is_array($spawnIds) ? $spawnIds : array($spawnIds);
		$cleanIds = array();
		foreach($ids as $id)
		{
			$id = (int)$id;
			if( $id > 0 && !in_array($id, $cleanIds, true) )
				$cleanIds[] = $id;
		}
		$stepText = isset($analysis['raw_text']) ? $this->QuestFixLuaEscape($analysis['raw_text']) : '';
		$taskGroupText = trim((string)$taskGroupText) !== '' ? $this->QuestFixLuaEscape($taskGroupText) : $stepText;
		$completeAction = trim((string)$completeAction);
		if( $completeAction === '' )
			$completeAction = 'Step' . $stepNumber . 'Complete';
		$completeAction = $this->QuestFixLuaEscape($completeAction);
		$idsTail = count($cleanIds) > 0 ? ', ' . implode(', ', $cleanIds) : '';
		return sprintf(
			"AddQuestStepKill(Quest, %d, \"%s\", %s, %d, \"%s\", %d%s)\nAddQuestStepCompleteAction(Quest, %d, \"%s\")",
			$stepNumber, $stepText, $countExpression, $progressPercent, $taskGroupText, $iconId, $idsTail, $stepNumber, $completeAction
		);
	}

	private function QuestFixBuildLuaFromCensusModelComplexRandomKillQuantity($model, $plan)
	{
		$name = isset($model['summary']['name']) ? $model['summary']['name'] : 'Generated Quest';
		$category = isset($model['summary']['category']) ? $model['summary']['category'] : '';
		$filename = $this->QuestFixLuaSlug($name) . '.lua';
		$questCompletion = count($model['quest_completion_texts']) > 0 ? $model['quest_completion_texts'][0] : '';
		$branches = isset($model['branches']) && is_array($model['branches']) ? array_values($model['branches']) : array();
		$branchCount = count($branches);
		$complexIndex = isset($plan['first_branch_index']) ? (int)$plan['first_branch_index'] : -1;
		if( $branchCount <= 0 || $complexIndex !== 0 )
			return ''; // Current safe generator supports a random persisted opening kill step.
		$first = $branches[0];
		$firstStep = isset($first['step_number']) ? (int)$first['step_number'] : 1;
		$qmin = max(1, (int)($plan['quantity_min'] ?? 1));
		$qmax = max($qmin, (int)($plan['quantity_max'] ?? $qmin));
		if( $qmax <= $qmin )
			return '';

		$firstAction = $branchCount === 1 ? 'QuestComplete' : ('Step' . $firstStep . 'Complete');
		$firstAnalysis = isset($first['analysis']) && is_array($first['analysis']) ? $first['analysis'] : array();
		$firstIconId = isset($first['icon_id']) ? (int)$first['icon_id'] : 0;
		$firstHasLocalIcon = !empty($first['local_step_evidence']['found']) && array_key_exists('icon_id', (array)$first['local_step_evidence']);
		$resolvedKillIcon = $firstHasLocalIcon ? (int)$first['local_step_evidence']['icon_id'] : ($firstIconId > 0 ? $firstIconId : 100);
		$resolvedProgress = isset($first['local_step_evidence']['progress_percent']) ? (int)$first['local_step_evidence']['progress_percent'] : 100;
		$firstIds = !empty($first['best_spawn_ids']) ? $first['best_spawn_ids'] : array((int)($first['best_spawn_id'] ?? 0));
		$randomStepSnippet = $this->QuestFixBuildKillLuaSnippetWithCountExpression(
			$firstStep,
			$firstAnalysis,
			'quantity',
			$firstIds,
			$first['task_group_text'] ?? '',
			$firstAction,
			$resolvedKillIcon,
			$resolvedProgress
		);

		$lines = array();
		$lines[] = '--[[';
		$lines[] = "\tScript Name\t\t:\t" . $filename;
		$lines[] = "\tScript Purpose\t:\tHandles the quest, \"" . $this->QuestFixLuaEscape($name) . "\"";
		$lines[] = "\tScript Author\t:\tQuest Fix Assistant";
		$lines[] = "\tScript Notes\t:\tAuto generated complex quest template: persisted random Kill quantity from Census quota range.";
		$lines[] = '';
		$lines[] = "\tCategory\t\t:\t" . $this->QuestFixLuaEscape($category);
		$lines[] = "\tCensus ID\t\t:\t" . $this->QuestFixLuaEscape((string)($model['summary']['id'] ?? ''));
		$lines[] = "\tCensus CRC\t\t:\t" . $this->QuestFixLuaEscape((string)($model['summary']['crc'] ?? ''));
		$lines[] = '--]]';
		$lines[] = '';
		$lines[] = 'local RANDOM_QUANTITY_MIN = ' . $qmin;
		$lines[] = 'local RANDOM_QUANTITY_MAX = ' . $qmax;
		$lines[] = '';
		$lines[] = 'function Init(Quest)';
		if( !empty($plan['repeatable_hint']) )
			$lines[] = "\tSetQuestRepeatable(Quest)";
		else
			$lines[] = "\t-- Random Kill quantity is chosen in Accepted() and persisted in QuestFlags.";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function Accepted(Quest, QuestGiver, Player)';
		$lines[] = "\tif GetQuestFlags(Quest) == 0 then";
		$lines[] = "\t\tlocal quantity = math.random(RANDOM_QUANTITY_MIN, RANDOM_QUANTITY_MAX)";
		$lines[] = "\t\tSetQuestFlags(Quest, quantity)";
		$lines[] = "\t\tSetRandomQuantityStep(Quest, Player, quantity)";
		$lines[] = "\telse";
		$lines[] = "\t\tRestoreRandomQuantityStep(Quest, Player, GetQuestFlags(Quest))";
		$lines[] = "\tend";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function Declined(Quest, QuestGiver, Player)';
		$lines[] = "\t-- Add dialog cleanup here if this quest uses NPC-side temporary variables.";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function Deleted(Quest, QuestGiver, Player)';
		$lines[] = "\t-- Remove any quest specific items here when the quest is deleted";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function RestoreRandomQuantityStep(Quest, Player, Flags)';
		$lines[] = "\tlocal quantity = tonumber(Flags) or RANDOM_QUANTITY_MIN";
		$lines[] = "\tif quantity < RANDOM_QUANTITY_MIN or quantity > RANDOM_QUANTITY_MAX then";
		$lines[] = "\t\tquantity = RANDOM_QUANTITY_MIN";
		$lines[] = "\t\tSetQuestFlags(Quest, quantity)";
		$lines[] = "\tend";
		$lines[] = "\tSetRandomQuantityStep(Quest, Player, quantity)";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function SetRandomQuantityStep(Quest, Player, quantity)';
		$lines[] = "\t" . str_replace("\n", "\n\t", $randomStepSnippet);
		$lines[] = 'end';
		$lines[] = '';

		if( $branchCount > 1 )
		{
			for($i = 0; $i < $branchCount - 1; $i++)
			{
				$current = $branches[$i];
				$next = $branches[$i + 1];
				$step = (int)$current['step_number'];
				$nextAction = ($i + 1) === ($branchCount - 1) ? 'QuestComplete' : ('Step' . (int)$next['step_number'] . 'Complete');
				$stepCompletion = isset($current['completion_text']) && trim((string)$current['completion_text']) !== '' ? $current['completion_text'] : ($current['step_text'] ?? '');
				$taskCompletion = $this->QuestFixTaskCompletionTextForBranch($model, $current);
				$lines[] = 'function Step' . $step . 'Complete(Quest, QuestGiver, Player)';
				$lines[] = "\tUpdateQuestStepDescription(Quest, " . $step . ", \"" . $this->QuestFixLuaEscape($stepCompletion) . "\")";
				$lines[] = "\tUpdateQuestTaskGroupDescription(Quest, " . $step . ", \"" . $this->QuestFixLuaEscape($taskCompletion) . "\")";
				$lines[] = '';
				$snippet = $this->QuestFixBuildStepSnippet($next, $nextAction);
				$lines[] = "\t" . str_replace("\n", "\n\t", $snippet);
				$lines[] = 'end';
				$lines[] = '';
			}
		}

		$lines[] = 'function QuestComplete(Quest, QuestGiver, Player)';
		if( $branchCount > 0 )
		{
			$last = $branches[$branchCount - 1];
			$lastStep = (int)$last['step_number'];
			$stepCompletion = isset($last['completion_text']) && trim((string)$last['completion_text']) !== '' ? $last['completion_text'] : ($last['step_text'] ?? '');
			$taskCompletion = $this->QuestFixTaskCompletionTextForBranch($model, $last);
			$questCompletionOut = $questCompletion !== '' ? $questCompletion : $taskCompletion;
			$lines[] = "\tUpdateQuestStepDescription(Quest, " . $lastStep . ", \"" . $this->QuestFixLuaEscape($stepCompletion) . "\")";
			$lines[] = "\tUpdateQuestTaskGroupDescription(Quest, " . $lastStep . ", \"" . $this->QuestFixLuaEscape($taskCompletion) . "\")";
			$lines[] = '';
			$lines[] = "\tUpdateQuestDescription(Quest, \"" . $this->QuestFixLuaEscape($questCompletionOut) . "\")";
		}
		else
			$lines[] = "\t-- No Census steps were available.";
		$lines[] = "\tGiveQuestReward(Quest, Player)";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function Reload(Quest, QuestGiver, Player, Step)';
		$lines[] = "\tif Step == 0 then";
		$lines[] = "\t\tRestoreRandomQuantityStep(Quest, Player, GetQuestFlags(Quest))";
		if( $branchCount === 1 )
		{
			$lines[] = "\telseif Step == " . $firstStep . " then";
			$lines[] = "\t\tQuestComplete(Quest, QuestGiver, Player)";
		}
		elseif( $branchCount > 1 )
		{
			foreach($branches as $idx => $branch)
			{
				$step = (int)$branch['step_number'];
				$lines[] = "\telseif Step == " . $step . " then";
				if( $idx === $branchCount - 1 )
					$lines[] = "\t\tQuestComplete(Quest, QuestGiver, Player)";
				else
					$lines[] = "\t\tStep" . $step . "Complete(Quest, QuestGiver, Player)";
			}
		}
		$lines[] = "\tend";
		$lines[] = 'end';
		return implode("\n", $lines) . "\n";
	}

	public function QuestFixBuildLuaFromCensusModel($model)
	{
		if( !is_array($model) || empty($model['ok']) )
			return '';
		$localComplexity = isset($model['local_lua_complexity']) && is_array($model['local_lua_complexity']) ? $model['local_lua_complexity'] : array();
		if( !empty($localComplexity['preserve_local_script']) )
		{
			$localScript = $this->QuestFixReadLocalLuaScript((string)($model['local_quest']['lua_script'] ?? ''));
			if( trim($localScript) !== '' )
				return $localScript;
		}
		$complexPlan = $this->QuestFixComplexGenerationPlan($model);
		if( !empty($complexPlan['enabled']) )
		{
			$complexLua = $this->QuestFixBuildLuaFromCensusModelComplexRandomKillQuantity($model, $complexPlan);
			if( trim((string)$complexLua) !== '' )
				return $complexLua;
		}
		$name = isset($model['summary']['name']) ? $model['summary']['name'] : 'Generated Quest';
		$category = isset($model['summary']['category']) ? $model['summary']['category'] : '';
		$filename = $this->QuestFixLuaSlug($name) . '.lua';
		$questCompletion = count($model['quest_completion_texts']) > 0 ? $model['quest_completion_texts'][0] : '';
		$branches = isset($model['branches']) && is_array($model['branches']) ? array_values($model['branches']) : array();
		$branchCount = count($branches);
		$lines = array();
		$lines[] = '--[[';
		$lines[] = "\tScript Name\t\t:\t" . $filename;
		$lines[] = "\tScript Purpose\t:\tHandles the quest, \"" . $this->QuestFixLuaEscape($name) . "\"";
		$lines[] = "\tScript Author\t:\tQuest Fix Assistant";
		$lines[] = "\tScript Notes\t:\tAuto generated from EQ2 Census quest data; review before live use.";
		$lines[] = '';
		$lines[] = "\tCategory\t\t:\t" . $this->QuestFixLuaEscape($category);
		$lines[] = "\tCensus ID\t\t:\t" . $this->QuestFixLuaEscape((string)($model['summary']['id'] ?? ''));
		$lines[] = "\tCensus CRC\t\t:\t" . $this->QuestFixLuaEscape((string)($model['summary']['crc'] ?? ''));
		$lines[] = '--]]';
		$lines[] = '';
		$lines[] = '';
		$lines[] = 'function Init(Quest)';
		if( $branchCount > 0 )
		{
			$firstAction = $branchCount === 1 ? 'QuestComplete' : ('Step' . (int)$branches[0]['step_number'] . 'Complete');
			$snippet = $this->QuestFixBuildStepSnippet($branches[0], $firstAction);
			$lines[] = "\t" . str_replace("\n", "\n\t", $snippet);
		}
		else
			$lines[] = "\t-- Census returned no branch steps.";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function Accepted(Quest, QuestGiver, Player)';
		$lines[] = "\t-- Add dialog here for when the quest is accepted";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function Declined(Quest, QuestGiver, Player)';
		$lines[] = "\t-- Add dialog here for when the quest is declined";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function Deleted(Quest, QuestGiver, Player)';
		$lines[] = "\t-- Remove any quest specific items here when the quest is deleted";
		$lines[] = 'end';
		$lines[] = '';
		if( $branchCount > 1 )
		{
			for($i = 0; $i < $branchCount - 1; $i++)
			{
				$current = $branches[$i];
				$next = $branches[$i + 1];
				$step = (int)$current['step_number'];
				$nextAction = ($i + 1) === ($branchCount - 1) ? 'QuestComplete' : ('Step' . (int)$next['step_number'] . 'Complete');
				$stepCompletion = isset($current['completion_text']) && trim((string)$current['completion_text']) !== '' ? $current['completion_text'] : ($current['step_text'] ?? '');
				$taskCompletion = $this->QuestFixTaskCompletionTextForBranch($model, $current);
				$lines[] = 'function Step' . $step . 'Complete(Quest, QuestGiver, Player)';
				$lines[] = "\tUpdateQuestStepDescription(Quest, " . $step . ", \"" . $this->QuestFixLuaEscape($stepCompletion) . "\")";
				$lines[] = "\tUpdateQuestTaskGroupDescription(Quest, " . $step . ", \"" . $this->QuestFixLuaEscape($taskCompletion) . "\")";
				$lines[] = '';
				$snippet = $this->QuestFixBuildStepSnippet($next, $nextAction);
				$lines[] = "\t" . str_replace("\n", "\n\t", $snippet);
				$lines[] = 'end';
				$lines[] = '';
			}
		}
		$lines[] = 'function QuestComplete(Quest, QuestGiver, Player)';
		if( $branchCount > 0 )
		{
			$last = $branches[$branchCount - 1];
			$lastStep = (int)$last['step_number'];
			$stepCompletion = isset($last['completion_text']) && trim((string)$last['completion_text']) !== '' ? $last['completion_text'] : ($last['step_text'] ?? '');
			$taskCompletion = $this->QuestFixTaskCompletionTextForBranch($model, $last);
			$questCompletionOut = $questCompletion !== '' ? $questCompletion : $taskCompletion;
			$lines[] = "\tUpdateQuestStepDescription(Quest, " . $lastStep . ", \"" . $this->QuestFixLuaEscape($stepCompletion) . "\")";
			$lines[] = "\tUpdateQuestTaskGroupDescription(Quest, " . $lastStep . ", \"" . $this->QuestFixLuaEscape($taskCompletion) . "\")";
			$lines[] = '';
			$lines[] = "\tUpdateQuestDescription(Quest, \"" . $this->QuestFixLuaEscape($questCompletionOut) . "\")";
		}
		else
			$lines[] = "\t-- No Census steps were available.";
		$lines[] = "\tGiveQuestReward(Quest, Player)";
		$lines[] = 'end';
		$lines[] = '';
		$lines[] = 'function Reload(Quest, QuestGiver, Player, Step)';
		if( $branchCount === 1 )
		{
			$lines[] = "\tif Step == 1 then";
			$lines[] = "\t\tQuestComplete(Quest, QuestGiver, Player)";
			$lines[] = "\tend";
		}
		elseif( $branchCount > 1 )
		{
			foreach($branches as $idx => $branch)
			{
				$step = (int)$branch['step_number'];
				$prefix = $idx === 0 ? 'if' : 'elseif';
				$lines[] = "\t" . $prefix . " Step == " . $step . " then";
				if( $idx === $branchCount - 1 )
					$lines[] = "\t\tQuestComplete(Quest, QuestGiver, Player)";
				else
					$lines[] = "\t\tStep" . $step . "Complete(Quest, QuestGiver, Player)";
			}
			$lines[] = "\tend";
		}
		else
			$lines[] = "\t-- No steps to reload.";
		$lines[] = 'end';
		return implode("\n", $lines) . "\n";
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
