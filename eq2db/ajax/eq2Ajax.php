<?php
define('IN_EDITOR', true);

//Send some headers to keep the user's browser from caching the response.
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
header("Cache-Control: no-cache, must-revalidate" ); 
header("Pragma: no-cache" );
header("Content-Type: text/xml; charset=utf-8");

//Get our database abstraction file
require "../class/dotenv.php";
DotEnv::load("../.env");
require "../config.php";

$dataset = NULL;

if ($user = $_GET['user'] ?? NULL) {
	$session = $_GET['session'];
	$roleData = $eq2->RunQuerySingle(sprintf("SELECT role FROM users WHERE username = '%s' AND `session_id` = '%s'", $user, $session));
	if (isset($roleData)) {
		$eq2->user_role = intval($roleData['role']);
	}
}

if( strlen(($_GET['search'] ?? "")) && isset($_GET['type']) ) 
{	
	$search = addslashes(strtolower($_GET['search']));

	switch($_GET['type'])
	{
		case "spell":
			if( $search < 1000000 )
			{
				$range = intval($search / 1000);
				$eq2->SQLQuery = "SELECT MAX(id)+1 as search_text FROM `".ACTIVE_DB."`.spells WHERE id LIKE '".$range."___';";
			}
			else
			{
				$range = intval($search / 10000);
				$eq2->SQLQuery = "SELECT MAX(id)+1 as search_text FROM `".ACTIVE_DB."`.spells WHERE id LIKE '".$range."____';";
			}
			$dataset = getData();
			if( empty($dataset[0]) )
				$dataset[0] = $search;
			//echo $dataset[0];
			//exit;
			break;
			
		case "class":
			$eq2->SQLQuery = "SELECT MAX(id)+1 as search_text FROM `".ACTIVE_DB."`.spells WHERE id LIKE '".$search."____';";
			$dataset = getData();
			if( empty($dataset[0]) )
				$dataset[0] = $search * 10000;
			//echo $dataset[0];
			//exit;
			break;
			
		case "luSC":
			$dataset = array();
			foreach($eq2->eq2SpellClasses as $key=>$val)
			{
				$class = strtolower($val);
				if( strstr($class, $search) )
					array_push($dataset, $val."\n");
			}
			break;
			
		case "luS":
			$eq2->SQLQuery = "SELECT distinct(name) as search_text FROM `".RAW_DB."`.raw_spells WHERE name LIKE '".$search."%' ORDER BY name LIMIT 0, 10";
			$dataset = getData();
			break;
			
		case "luSE":
			$eq2->SQLQuery = "SELECT distinct(name) as search_text FROM `".ACTIVE_DB."`.spells WHERE name LIKE '".$search."%' ORDER BY name LIMIT 0, 10";
			$dataset = getData();
			break;
			
		case "luVE":
			$eq2->SQLQuery = "SELECT distinct(name) as search_text FROM `".ACTIVE_DB."`.reference_spell_effects WHERE (category RLIKE '".$search."') OR (type RLIKE '".$search."') OR (name RLIKE '".$search."') OR (misc RLIKE '".$search."') ORDER BY category, type, name, misc LIMIT 0,10";
			$dataset = getData();
			break;
		
		case "luSpawn":
			$eq2->SQLQuery = "SELECT distinct(name) as search_text FROM `".ACTIVE_DB."`.spawn WHERE name RLIKE '".$search."' ORDER BY name LIMIT 0,10";
			$dataset = getData();
			break;

		case "questFixSpawn":
			$eq2->SQLQuery = "SELECT CONCAT(name, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.spawn WHERE name RLIKE '".$search."' OR id = '".$search."' ORDER BY LENGTH(name), name, id LIMIT 0,40";
			$dataset = getData();
			break;
			
		case "luO":
			$eq2->SQLQuery = "SELECT distinct(name) as search_text FROM `".ACTIVE_DB."`.opcodes WHERE (name RLIKE '".$search."') OR (opcode RLIKE '".$search."') ORDER BY name LIMIT 0,10";
			$dataset = getData();
			break;
			
		case "luQ":
			$eq2->SQLQuery = "SELECT distinct(name) as search_text FROM `".ACTIVE_DB."`.quests WHERE (name RLIKE '".$search."') OR (description RLIKE '".$search."') OR (type RLIKE '".$search."') OR (zone RLIKE '".$search."') OR (lua_script RLIKE '".$search."') ORDER BY name LIMIT 0, 10";
			$dataset = getData();
			break;
			
		case "luZ":
			$eq2->SQLQuery = "SELECT distinct(name) as search_text FROM `".ACTIVE_DB."`.zones WHERE (name RLIKE '".$search."') OR (description RLIKE '".$search."') OR (file RLIKE '".$search."') OR (lua_script RLIKE '".$search."') ORDER BY name LIMIT 0, 10";
			$dataset = getData();
			break;
			
		case "user":
			if ($eq2->CheckAccess(G_SUPERADMIN)) {
				$eq2->SQLQuery = "SELECT distinct(username) as search_text FROM users WHERE username RLIKE '".$search."' ORDER BY username";
				$dataset = getData();
			}
			break;

		case "luCh":
			$eq2->SQLQuery = "SELECT CONCAT(`name`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.characters WHERE `name` RLIKE '".$search."' ORDER BY LENGTH(`name`), `name` LIMIT 0,10";
			$dataset = getData();
			break;

		case "luI":
			$eq2->SQLQuery = "SELECT CONCAT(`name`, ' (', LOWER(item_type), ') ', '(', id, ')') as search_text FROM `".ACTIVE_DB."`.`items` WHERE bPvpDesc = 0 AND (`name` RLIKE '".$search."' OR id = '".$search."') ORDER BY name LIMIT 0,10";
			$dataset = getData();
			break;

		case "luEc":
			$filter = isset($_GET['single']) ? " HAVING COUNT(command_list_id) = 1" : "";
			$eq2->SQLQuery = "SELECT DISTINCT command_list_id FROM `".ACTIVE_DB."`.entity_commands WHERE command_text RLIKE '".$search."' GROUP BY command_list_id".$filter." LIMIT 0,10";
			$dataset = getDataEntityCommand();
			break;
			
		case "luLt":
			$eq2->SQLQuery = "SELECT CONCAT(`name`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.loottable WHERE `name` RLIKE '".$search."' ORDER BY LENGTH(`name`), `name` LIMIT 0,10";
			$dataset = getData();
			break;

		case "luH":
			$eq2->SQLQuery = "SELECT CONCAT(`name`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.`houses` WHERE `name` RLIKE '".$search."' OR id = '".$search."' ORDER BY LENGTH(`name`), `name` LIMIT 0,10";
			$dataset = getData();
			break;

		case "eq2emu_revive_point":
			$eq2->SQLQuery = "SELECT CONCAT(`location_name`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.`revive_points` WHERE `location_name` RLIKE '".$search."' OR id = '".$search."' ORDER BY LENGTH(`location_name`), `location_name` LIMIT 0,10";
			$dataset = getData();
			break;

		case "eq2emu_factions":
			$eq2->SQLQuery = "SELECT CONCAT(`name`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.`factions` WHERE `name` RLIKE '".$search."' OR id = '".$search."' ORDER BY LENGTH(`name`), `name` LIMIT 0,10";
			$dataset = getData();
			break;
		case "eq2emu_transporters":
			$eq2->SQLQuery = "SELECT CONCAT(`display_name`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.`transporters` WHERE `display_name` RLIKE '".$search."' OR id = '".$search."' ORDER BY LENGTH(`display_name`), `display_name` LIMIT 0,10";
			$dataset = getData();
			break;
		case "eq2emu_flight_paths":
			$eq2->SQLQuery = "SELECT CONCAT(`name`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.`flight_paths` WHERE `name` RLIKE '".$search."' OR id = '".$search."' ORDER BY LENGTH(`name`), `name` LIMIT 0,10";
			$dataset = getData();
			break;
		case "eq2emu_merchants":
			$eq2->SQLQuery = "SELECT CONCAT(`description`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.`merchants` WHERE `description` RLIKE '".$search."' OR id = '".$search."' ORDER BY LENGTH(`description`), `description` LIMIT 0,10";
			$dataset = getData();
			break;
		case "eq2emu_guildname":
			$eq2->SQLQuery = "SELECT CONCAT(`name`, ' (', id, ')') as search_text FROM `".ACTIVE_DB."`.`guilds` WHERE `name` RLIKE '".$search."' OR id = '".$search."' ORDER BY LENGTH(`name`), `name` LIMIT 0,10";
			$dataset = getData();
		default:
			break;
	}
}

if( is_array($dataset) )
{
	foreach($dataset as $data )
	{
		echo htmlspecialchars($data) . "\n";
	}
}
else
	echo "<strong>No matches.</strong>\n";

function getData()
{
	global $eq2;

	$rows = $eq2->RunQueryMulti();
	
	if( is_array($rows) )
	{
		foreach( $rows as $row )
		{
			$ret[] = $row['search_text'];
		}
	}
	
	return $ret;
}

function getDataEntityCommand()
{
	global $eq2;

	$rows = $eq2->RunQueryMulti();

	$ret = null;
	
	if( is_array($rows) )
	{
		if (count($rows) == 0) return null;
		
		$bFirst = true;
		$inList = "(";
		foreach ($rows as $data) {
			if ($bFirst) $bFirst = false;
			else $inList .= ",";
			$inList .= $data['command_list_id'];
		}
		$inList .= ")";

		$eq2->SQLQuery = "SELECT command_list_id, command_text FROM `".ACTIVE_DB."`.entity_commands WHERE command_list_id IN ".$inList;
		$rows = $eq2->RunQueryMulti();
		$cmds = array();
		foreach($rows as $data) 
		{
			$cmd = $data['command_list_id'];
			if (isset($cmds[$cmd])) {
				$cmds[$cmd] .= ", ";
			}
			$cmds[$cmd] .= $data['command_text'];
		}

		//Sort the cmds by the command text values alphabetically
		asort($cmds, SORT_NATURAL | SORT_FLAG_CASE);

		$ret = array();
		$ret[] = "---NONE--- (0)";
		foreach ($cmds as $k=>$v) {
			$val = sprintf("%s (%s)", $v, $k);
			$ret[] = $val;
		}
	}
	
	return $ret;
}
?>