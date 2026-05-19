<?php
error_reporting(E_ALL);
define('IN_EDITOR', true);
include("header.php");

//printf("My Role: %s - Role Value: %s - Eval: %s<br />", $eq2->user_role, G_ADMIN, (G_ADMIN & $eq2->user_role) );

if ( !$eq2->CheckAccess(M_ADMIN) )
	die(); // no access to non-admins

// Instantiate the eq2Admin class, which also instantiates the eq2Spawns, eq2Spells, and eq2Zones classes
include("../class/eq2.admin.php");
$admin = new eq2Admin();

if( isset($_POST['cmd']) ) 
{
	// do updates/deletes here
	switch(strtolower($_POST['cmd'])) {
		case "insert":
			$admin->PreInsert();
			$insert_res = $eq2->ProcessInserts();
			$admin->PostInsert($insert_res);
			if($_POST['eq2news_items|type']== 4)
			{
				shell_exec("php ../discord-message.php \"" . $eq2->userdata['displayname'] . "\" \"" . $_POST['eq2news_items|description'] . "\" \"" . $_POST['eq2news_items|title'] . "\" ContentUpdate " . env('WH_CONTENTUPDATE'));
			}
		break;
		case "update":
			$admin->PreUpdate();
			$eq2->ProcessUpdates();
			$admin->PostUpdate();
			break;
		case "delete": 
            $eq2->ProcessDeletes();
			$admin->PostDeletes(); 
            break;
		case "multiinsert":
			$eq2->ProcessMultiInsert();
			break;
	}
	
} 


$link = sprintf("%s",$_SERVER['SCRIPT_NAME']);
$page = (isset($_GET['page'])?$_GET['page']:"");
?>
<div id="Editor">
<table class="SubPanel" cellspacing="0" border="0">
	<tr>
		<td id="EditorStatus" colspan="2"><?php if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
	</tr>

	<tr>
		<td class="Title" colspan="2">Administration</td>
	</tr>

	<tr>
		<td valign="top"><!-- Left Menu -->
		<?php print($admin->GenerateNavigationMenu()); ?>
<!--NAV HTML WAS HERE -->

		<td width="100%" valign="top"><!-- Main Page -->
			<table class="SectionMainFloat" cellspacing="0" border="0" width='90%'>
				<tr>
					<td class="SectionBody">&nbsp;
					<?php
					switch($page)
					{
						//Project
						case "stats"				:	GetServerStats(); break;
						case "soequests"			:	DisplaySOEQuestData(); break;
						case "logs"					:	getLogs(); break;
						case "changes"				:	getChangeLogs(); break;
						case "scripts"				:	getScripts(); break;
						case "sql"					:	adhoc(); break;
						//Migrate
						case "migrate"				:	Migrate(); break;
						case "purgezone"			:	PurgeZone(); break;
						case "popspells"			:	popSpells(); break;
						case "syncspells"			:	SyncRawSpells(); break;
						case "location"				:	Locations(); break;
						//Scripting
						case "dialogs"				:	buildDialogs(); break;
						case "movement"				:	buildMovement(); break;
						case "voiceovers"			:	listVoiceovers(); break;
						//Administration
						case "users"				:	UserManager(); break;
						case "reset"				:	RebootLogin(); RebootServer(); break;
						case "compile"				:	CompileServer(); break;
						//In Dev
						case "pets"					:	Pets(); break;
						case "merchants"			:	compareMerchants(); break;
						case "popmerchant"			:	popMerchants(); break;
						//Editor Admin
						//case "updateItemVals"		:	updateEditorItemVals();break;
						case "editor_news"			:   editor_news(); break;
						case "editor_configs"		:	editor_configs(); break;
						case "editor_datasources"	:	editor_datasources(); break;
						//DEV TOOLS
						case "bugreports"			:	bugreports(); break;
						//NOT LINKED?
						case "ls"					:	LoginServer(); break;
						case "popzone"				:	PopZone(); break;
						case "soecompare"			: 	SOEDataCompare(); break;
						/*case "reset"				:	ServerReset(); break;*/
						default						:	Welcome(); break;
					}
					?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>

<?php
include("footer.php");
exit; 

/*
 * Admin Functions
 */

function RebootLogin()
{
	// convert to its own file to check for in progress restart
	$file = "force-lsrestart";

	if( strlen($_POST['force-lsrestart']) > 0 ) {
		if( file_exists($file) )
			die("You only need to submit the command once.");

			$myfile = fopen($file, 'w') or die("Unable to open file!");
			$txt = "#!/bin/bash\n#\nkillall eq2login\necho \"Stopping eq2world...\" > reboot.log\n\nSleep 15\nrm -f /var/www/html/editors/eq2db/editors/force-lsrestart\necho \"Resetting reboot command...\" >> reboot.log\necho \"Done. May take up to 5 minutes to fully boot up\" >> reboot.log\nexit\n";
			fwrite($myfile, $txt);
			fclose($myfile);

			shell_exec('chmod +x force-lsrestart');
			die("Restart command successful. Please allow up to 5 minutes for restart to occur.");

	}
	else {
		if( file_exists($file) )
			die("There is already a pending restart. Please wait.");
	}

	?>

	<table width="700" cellspacing="0" border="0">
		<tr>
			<td><h3>Using this form, you can queue a command to the EQ2DB<br>Server's cron to force a restart of the login server. Use with caution!</h3></td>
		</tr>

		<tr>
			<td><h4>Login Server Reset</h4></td>
		</tr>

		<form method="post" name="resetLogin">
			<tr>
					<td><input type="submit" name="force-lsrestart" value="Submit" class="submit" /></td>
			</tr>
		</form>
	</table>

	<?php
}

function RebootServer()
{
	/*
	$file = "force-restart";

	if( strlen($_POST['force-restart']) > 0 ) {
		if( file_exists($file) )
			die("You only need to submit the command once.");

			$myfile = fopen($file, 'w') or die("Unable to open file!");
			$txt = "#!/bin/bash\n#\nkillall eq2world\necho \"Stopping eq2world...\"\nSleep 60\nrm -f /var/www/html/editors/eq2db/editors/force-restart\nexit\n";
			fwrite($myfile, $txt);
			fclose($myfile);

			shell_exec('chmod +x force-restart');
			die("Restart command successful. Please allow up to 5 minutes for restart to occur.");

		}
	else {
		if( file_exists($file) )
				die("There is already a pending restart. Please wait.");
	}
	*/

  
  //Code above is 1st gen? REALLY old keeping for now.
  //Code below is from the "Cynnar" server. 
	// creates a file that alterts cron to compile server
/*	$lockfile = ".rbtlock";

    if( isset($_POST['force-restart']) ) {
        if( file_exists($lockfile) )
            die("Anoter Reboot has been issued. Please wait up to 5 minuets for compile to finish.");

		if ( file_exists(".force-reboot") ) {
			rename( ".force-reboot", ".running-reboot" );
		}


        //shell_exec('chmod +x .cmplock');

        die("Reboot command successful cron will exectute in about 1 minute. Please allow up to 5 minutes for the reboot to complete.");
    }
    else {
        if( file_exists(".running-reboot") )
            die("There is already a reboot in progress. Please wait up to 10 minuets for the reboot to finish.");
    }*/
  
  //below currently uses screenz(the eq2emu server restart script) to do the heavy lifting.
  //all we need to do is tell it to handle things by creating a file. multiple clicks wont hurt.
  //at some point should probably move this to an option so users can set this with other options.
if( isset($_POST['force-restart']) ) {
        $restartfile = '/home/eq2emu_server/forcerestart.maint';

        //If we already have a restart marked lets just do as above and Die.
        if (file_exists($filename)) {
                die("There is already a reboot in progress. Please wait up to 10 mins for the reboot to finish.");
        }

        exec("touch $restartfile");
}

	?>

	<table width="700" cellspacing="0" border="0">
		<tr>
			<td>
				<h4>
					World Server Reset
				</h4>
			</td>
		</tr>
		<!-- <tr>
			<td><h3>Using this form, you can queue a command to the EQ2DB</br>Server's cron to force a restart of the world server. Use with caution!</h3></td>
		</tr> -->
		<form method="post" name="resetServer">
			<tr>
				<td>
					<input type="submit" name="force-restart" value="Submit" class="submit" />
				</td>
			</tr>
		</form>
	</table>

	<?php
}

function CompileServer()
{

	// creates a file that alterts cron to compile server
	$lockfile = ".cmplock";

    if( isset($_POST['force-compile']) ) {
        if( file_exists($lockfile) )
            die("Anoter compile has been issued. Please wait up to 10 minuets for compile to finish.");

		if ( file_exists(".force-compile") ) {
			rename( ".force-compile", ".running-compile" );
		}


        //shell_exec('chmod +x .cmplock');

        die("Compile command successful cron will exectute in about 1 minute. Please allow up to 10 minutes for compile to complete.");
    }
    else {
        if( file_exists($lockfile) )
            die("There is already a compile in progress. Please wait up to 10 minuets for compile to finish.");
    }
	
	?>
		
	<table width="700" cellspacing="0" border="0">
		<tr>
			<td>
				<h3>
					Using this form, you can queue a command to the EQ2DB<br>
					Server's cron to force a compile of the server.<br>
					Make sure all MySQL changes (if any) are entered before running this command.<br>
					Not implemented yet.
				</h3>
			</td>
		</tr>

		<tr>
			<td>
				<h4>
					Compile Server
				</h4>
			</td>
		</tr>

		<form method="post" name="compileServer">
			<tr>
				<td>
					<input type="submit" name="force-compile" value="Submit" class="submit" />
				</td>
			</tr>
		</form>
	</table>
		
	<?php
}


function ServerReset() {
	$file = $GLOBALS['config']['log_path'] . $GLOBALS['config']['world_folder'] . "/.force-restart";
	
	if( strlen($_POST['force-restart']) > 0 ) {
		if( file_exists($file) )
			die("You only need to submit the command once.");

		if( !$f = fopen($file,'w') ) 
			die("Could not queue the world to restart");

		else
			die("Restart command successful. Please allow up to 5 minutes for restart to occur.");

		fclose($file);
	}
	else {
		if( file_exists($file) )
			die("There is already a pending restart. Please wait.");
	?>

	<table width="1000" cellspacing="0" border="0">
		<tr>
			<td>
				<h3>
					Server Reset
				</h3>
			</td>
		</tr>

		<tr>
			<td>
				Using this form, you can queue a command to the EQ2DB Server's cron to force a restart of the server. Use with caution!
			</td>
		</tr>

		<form method="post" name="resetServer">
			<tr>
				<td>
					<input type="submit" name="force-restart" value="Submit" class="submit" />
				</td>
			</tr>
		</form>
	</table>

	<?php
	}
}

function Welcome()
{
	global $eq2;
	
	$eq2->SQLQuery = "SELECT * FROM eq2news_items WHERE (type = 'welcome' AND subtype = 'admin') AND (is_active = 1 OR is_sticky = 1) ORDER BY created_date DESC";
	$results = $eq2->RunQueryMulti();
	
	$news_articles = "";
	foreach($results as $data)
		$news_articles .= sprintf('<tr><td><h%s by %s on %s</td></tr><tr><td>%s</td></tr>', $data['title'], $data['author'], date('M-d-Y', $data['created_date']), $data['description']);

	?>

	<table width="1000" cellspacing="0" border="0">
		<tr>
			<td>
				<h3>
					News &amp; Stuff
				</h3>
			</td>
		</tr>

		<?= $news_articles ?>

	</table>

	<?php
}

function BuildDialogs() 
{
	global $eq2, $admin;

	// shorthand
	$a = $admin;
	$s = $a->spawns;
	$z = $s->zones;

	$id = $_GET['id'] ?? null;

	$zone = $_GET['zone'] ?? null;

	if ($id != null) {
		$spawnName = $a->GetDialogNPCName($id);
	}
	
	switch(strtolower($_POST['cmd'] ?? "")) 
	{
		case "build all": 
			//$a->BuildAllSpawnScripts();
			break;
			
		case "create script":
			$eq2->DBInsertSpawnScript(); // db insert first so the validations can run
			$eq2->SaveLUAScript();
			break;
	}
	
	$zoneOptions = $a->GetDialogOptionsByZone();
	
	if( $zone != null ) 
		$spawnOptions = $a->GetDialogOptionsBySpawn();
?>
	<table class="SectionMain">
		<tr>
			<td><strong>Note:</strong></td>
			<td><em>Dialogs are conversations you have with NPCs where players are offered response choices.</em></td>
		</tr>

		<tr>
			<td>&nbsp;</td>
			<td>This tool will attempt to render all known Raw Dialogs for a given NPC in the zone of your choice, saving it to a Lua script for editing via the Spawns editor.<br />CLEANUP IS REQUIRED, as this is RAW data.
			</td>
		</tr>
	</table>
	<br />
	<table class="SectionMain">
	<form method="post" name="build-all-spawnscripts">
		<tr>
			<td width="100" align="right">Select a Zone:</td>
			<td><select name="zone_id" onchange="dosub(this.options[this.selectedIndex].value)" style="width:400px;">
					<option value="_admin.php?page=dialogs">---</option>
					<?= $zoneOptions ?>
				</select>&nbsp;
				<a href="_admin.php?page=dialogs<?= ( isset($_GET['zone']) ) ? sprintf("&zone=%s", $_GET['zone']) : "" ?><?= ( isset($_GET['id']) ) ? sprintf("&id=%s",$_GET['id']) : "" ?>">Reload Page</a>
			</td>
		</tr>

		<tr>
			<td align="right">Select a Spawn:</td>
			<td>
				<select name="spawn_id" onchange="dosub(this.options[this.selectedIndex].value)" style="width:400px;">
					<option value="_admin.php?page=dialogs">---</option>
					<?= $spawnOptions ?>
				</select>
			</td>
		</tr>
	</form>
	
	<?php

	if( $id ) 
	{
		$script_path = sprintf("SpawnScripts/%s", $zone);
		$script_name = sprintf("%s/%s.lua", $script_path, $spawnName);
		
		$pattern[0]="/ /";
		$pattern[1]="/'/";
		$pattern[2]="/`/";
		$pattern[3]="/\"/";
		
		$script_full_name = preg_replace($pattern, "", $script_name);
		
	?>
	<tr>
		<td colspan="2">You may make initial edits before copying the script below.</td>
	</tr>
	<?php
		// script header
		$scriptHeader = sprintf("--[[\n");
		$scriptHeader .= sprintf("\tScript Name\t\t: %s\n", $script_full_name);
		$scriptHeader .= sprintf("\tScript Purpose\t: %s\n", $spawnName);
		$scriptHeader .= sprintf("\tScript Author\t: %s\n", ( strlen($eq2->userdata['displayname']) > 0 ) ? $eq2->userdata['displayname'] : $eq2->userdata['username']);
		$scriptHeader .= sprintf("\tScript Date\t\t: %s\n", date("Y.m.d",time()));
		$scriptHeader .= sprintf("\tScript Notes\t: Auto-Generated Conversation from PacketParser Data\n");
		$scriptHeader .= sprintf("--]]\n\n");
	
		$functionBlock = $a->BuildDialogFunctions($id);

		if ($functionBlock) {
			$scriptHeader .= "require \"SpawnScripts/Generic/DialogModule\"\n\n";
		}

		$hailBlock = "function spawn(NPC)\nend\n\n";
		$hailBlock .= "function respawn(NPC)\n";
		$hailBlock .= "\tspawn(NPC)\n";
		$hailBlock .= "end\n\n";
		$greeting = $a->BuildRandomGreeting($id);

		$hailBlock .= "function hailed(NPC, Spawn)\n";
		if ($greeting) {
			$hailBlock .= "\tRandomGreeting(NPC, Spawn)\n";
		}
		$hailBlock .= "end";
			
		$pattern[0]="/\\t/i";
		$pattern[1]="/\\n/i";
		$replace[0]="&nbsp;&nbsp;";
		$replace[1]="<br />";

		$randomGreeting = "";
		if ($greeting) {
			$randomGreeting = sprintf("\n\nfunction RandomGreeting(NPC, Spawn)\n%s\nend", $greeting);
		}

		// print to textarea and offer option to create new script file
		$script_text = $scriptHeader . $hailBlock . $randomGreeting;
		if ($functionBlock){
			$script_text .= "\n\n" . $functionBlock;
		}

	?>
	<form method="post" name="ssForm">
		<tr>
			<td colspan="2">
			<input type="hidden" name="script_text" id="LuaScript" />
			//<div id="scripteditor" style="height:400px;width:960px;"><?= $script_text ?></div>
			<script src="../ace/src-noconflict/ace.js" charset="utf-8"></script>
			<script src="../ace/src-noconflict/ext-language_tools.js"></script>	
			<script>
				var lang_tools = ace.require("../ace/ext/language_tools");
				var editor = ace.edit("scripteditor");
				editor.setTheme("../ace/theme/textmate");
				editor.session.setMode("../ace/mode/lua"); 
				lang_tools.setCompleters([lang_tools.snippetCompleter, lang_tools.keyWordCompleter]);
				editor.setOptions({
					enableLiveAutocompletion: true
				});
			</script>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<br />
				<input type="hidden" name="script_path" value="<?= $script_path ?>" />
				<input type="hidden" name="script_name" value="<?= $script_full_name ?>" />
				<input type="hidden" name="spawn_name" value="<?= $s->spawn_name ?>" />
				<input type="hidden" name="table_name" value="spawn_scripts" />
				<input type="hidden" name="object_id" value="<?= $s->spawn_name ?>|RawID:<?= $s->spawn_id ?>|Generate Dialog" />
			</td>
		</tr>
	</form>
	<?php
	}
?>
</table>
<?php
}


function DisplaySOEQuestData()
{
	global $eq2, $admin;
	
	$hasSoeSchema = $admin->HasSOEQuestSchema();
	if( !$hasSoeSchema )
	{
		?>
		<?php ShowSOEQuestFixAssistant(0); ?>
		<br />
		<fieldset>
			<legend>SOE / DBG Quest Reference Database</legend>
			<table width="100%" cellpadding="4" cellspacing="0" border="0">
				<tr>
					<td class="Detail">
						The legacy SOE quest browser cannot load because the configured SOE quest reference tables are not installed in <strong><?= htmlspecialchars(SOE_DATA, ENT_QUOTES, 'UTF-8') ?></strong>.
						The integrated <strong>Quest Fix Assistant</strong> normally reuses the existing SOE quest reference browser, stage rows, active world database, and SpawnScripts. Because the reference tables are missing here, the standalone Census fallback appears above.
					</td>
				</tr>
			</table>
		</fieldset>
		<?php
		return;
	}
	
	$categoryValue = $_GET['category'] ?? '';
	$idValue = (int)($_GET['id'] ?? 0);
	?>
	<!-- Filters -->
	<table border="0">
		<tr>
			<td class="filter_labels">Filters:</td>
			<td valign="top">
				<?php
				$catOptions = '';
				$categories = $admin->GetSOEQuestCategories();
				foreach($categories as $category)
					$catOptions .= sprintf(
						'<option value="_admin.php?page=soequests&category=%s"%s>%s</option>',
						urlencode($category['category']),
						($categoryValue === $category['category']) ? " selected" : "",
						htmlspecialchars($category['category'], ENT_QUOTES, 'UTF-8')
					);
				?>
				<select name="category" onchange="dosub(this.options[this.selectedIndex].value)" class="combo">
				<option value="_admin.php?page=soequests"<?php if( strlen($categoryValue)==0 ) echo " selected" ?>>Pick a Category</option>
				<?= $catOptions ?>
				</select>&nbsp;
				<a href="_admin.php?<?= htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES, 'UTF-8') ?>">Reload Page</a>
				<?php if( $idValue > 0 ) { ?>
				&bull;&nbsp;<a href="_admin.php?page=soequests<?php if( strlen($categoryValue) > 0 ) echo "&category=".urlencode($categoryValue) ?>">Back to List</a>
				<?php } ?>
			</td>
		</tr>
	</table>
	<br />
	<?php if( $idValue == 0 ) { ?>
	<div id="Editor">
	<table width="1000" cellpadding="4" cellspacing="0" border="0">
		<tr bgcolor="#cccccc">
			<td><strong>Quest ID</strong></td>
			<td><strong>Name</strong></td>
			<td><strong>Category</strong></td>
			<td><strong>Tier</strong></td>
			<td><strong>Level</strong></td>
		</tr>
		<?php
		$querystring = sprintf("_admin.php?page=soequests");
		if( strlen($categoryValue) > 0 )
			$querystring .= sprintf("&category=%s", urlencode($categoryValue));
		$quest_data = $admin->GetSOEQuestData();
		$i = 0;
		foreach($quest_data as $row)
		{
			$row_class = ( $i++ % 2 ) ? " bgcolor=\"#eeeeee\"" : "";
			?>
		<tr<?= $row_class ?> valign="top">
			<td><a href="<?= $querystring ?>&id=<?= (int)$row['quest_id'] ?>&tab=quests"><?= (int)$row['quest_id'] ?></a></td>
			<td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($row['tier'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($row['level'], ENT_QUOTES, 'UTF-8') ?></td>
		</tr>
	<?php } ?>
		<tr>
			<td colspan="5"><?= (int)$admin->total_rows ?> rows available...</td>
		</tr>
	</table>
	<br />
	<?php 
	}
	else
	{ 
		$querystring = sprintf("_admin.php?page=soequests");
		if( strlen($categoryValue) > 0 )
			$querystring .= sprintf("&category=%s&id=%d", urlencode($categoryValue), $idValue);
		else
			$querystring .= sprintf("&id=%d", $idValue);
		
		$current_tab_idx = ( isset($_GET['tab']) ) ? $_GET['tab'] : 'quests';
		$tab_array = array(
			'quests'	=> 'Quest',
			'stages'	=> 'Stages',
			'rewards'	=> 'Rewards',
			'assistant'	=> 'Quest Fix Assistant',
			'fixer'		=> 'Quest Fixer'
		);
		
		$eq2->TabGenerator($current_tab_idx, $tab_array, $querystring, false);
		
		switch($current_tab_idx)
		{
			case "quests" : ShowSOEQuestDetails(); break;
			case "stages" : ShowSOEQuestStages(); break;
			case "rewards": ShowSOEQuestRewards(); break;
			case "assistant" : ShowSOEQuestFixAssistant($idValue); break;
			case "fixer" : ShowSOEQuestFixer(); break;
		}
	} 
	?>
	</div>
	<?php	
}

function ShowSOEQuestFixAssistant($soeQuestId = 0)
{
	global $admin;
	$soeQuestId = (int)$soeQuestId;
	$isIntegratedSOE = $soeQuestId > 0;
	$formAction = $isIntegratedSOE ? ('_admin.php?page=soequests&id=' . $soeQuestId . '&tab=assistant') : '_admin.php?page=soequests';
	$stepTypes = $admin->QuestFixStepTypes();
	$questName = $_POST['quest_fix_census_name'] ?? 'Hunting the Huntresses';
	$questOption = $_POST['quest_fix_census_option'] ?? '';
	$census = null;
	$model = null;
	$lua = '';
	$selectedQuestJson = '';
	$typeOverrides = isset($_POST['quest_fix_step_type']) && is_array($_POST['quest_fix_step_type']) ? $_POST['quest_fix_step_type'] : array();
	$locationOverrides = isset($_POST['quest_fix_location_candidate']) && is_array($_POST['quest_fix_location_candidate']) ? $_POST['quest_fix_location_candidate'] : array();
	$spawnCandidateOverrides = isset($_POST['quest_fix_spawn_candidate']) && is_array($_POST['quest_fix_spawn_candidate']) ? $_POST['quest_fix_spawn_candidate'] : array();
	if( isset($_POST['quest_fix_spawn_candidate_text']) && is_array($_POST['quest_fix_spawn_candidate_text']) )
	{
		foreach($_POST['quest_fix_spawn_candidate_text'] as $stepNo => $rawSpawnIds)
		{
			$ids = array();
			if( preg_match_all('/\b(\d+)\b/', (string)$rawSpawnIds, $mm) )
			{
				foreach($mm[1] as $idText)
				{
					$id = (int)$idText;
					if( $id > 0 && !in_array($id, $ids, true) )
						$ids[] = $id;
				}
			}
			if( count($ids) > 0 )
				$spawnCandidateOverrides[(int)$stepNo] = $ids;
		}
	}
	if( $isIntegratedSOE )
	{
		$model = $admin->QuestFixBuildQuestModelFromSOEQuestId($soeQuestId);
		if( is_array($model) && !empty($model['ok']) )
		{
			if( (is_array($typeOverrides) && count($typeOverrides) > 0) || (is_array($locationOverrides) && count($locationOverrides) > 0) || (is_array($spawnCandidateOverrides) && count($spawnCandidateOverrides) > 0) )
				$model = $admin->QuestFixApplyTypeOverrides($model, $typeOverrides, $locationOverrides, $spawnCandidateOverrides);
			$lua = $admin->QuestFixBuildLuaFromCensusModel($model);
		}
	}
	elseif( isset($_POST['quest_fix_census_parse']) )
	{
		$census = $admin->QuestFixCensusSearchByName($questName, $questOption);
		if( !empty($census['ok']) && isset($census['quests']) && count($census['quests']) === 1 )
			$selectedQuestJson = $census['quests'][0]['json'];
	}
	elseif( isset($_POST['quest_fix_census_accept']) && !empty($_POST['quest_fix_census_json']) )
	{
		$decodedJson = base64_decode((string)$_POST['quest_fix_census_json'], true);
		if( $decodedJson !== false )
			$selectedQuestJson = $decodedJson;
		else
			$census = array('ok' => false, 'error' => 'The selected Census JSON payload could not be decoded.');
	}
	elseif( isset($_POST['quest_fix_update_lua']) && !empty($_POST['quest_fix_selected_json']) )
	{
		$decodedJson = base64_decode((string)$_POST['quest_fix_selected_json'], true);
		if( $decodedJson !== false )
			$selectedQuestJson = $decodedJson;
		else
			$census = array('ok' => false, 'error' => 'The selected Census JSON payload could not be decoded for Lua update.');
	}
	if( !$isIntegratedSOE && $selectedQuestJson !== '' )
	{
		$decoded = $admin->QuestFixCensusDecodeQuestJson($selectedQuestJson);
		if( !empty($decoded['ok']) )
		{
			$model = $admin->QuestFixCensusBuildQuestModel($decoded['quest_data']);
			if( (is_array($typeOverrides) && count($typeOverrides) > 0) || (is_array($locationOverrides) && count($locationOverrides) > 0) || (is_array($spawnCandidateOverrides) && count($spawnCandidateOverrides) > 0) )
				$model = $admin->QuestFixApplyTypeOverrides($model, $typeOverrides, $locationOverrides, $spawnCandidateOverrides);
			$lua = $admin->QuestFixBuildLuaFromCensusModel($model);
		}
		else
			$census = $decoded;
	}
	?>
	<script>
	function QuestFixLocalQuestLookupAJAX() {
		var input = document.getElementById('quest_fix_census_name');
		var suggest = document.getElementById('quest_fix_census_suggest');
		if (!input || !suggest) return;
		if (input.value.length < 1) {
			suggest.innerHTML = '';
			return;
		}
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			var str = escape(input.value);
			txtSearchAjaxInput = 'quest_fix_census_name';
			searchSuggestDiv = 'quest_fix_census_suggest';
			ajaxSelectCallback = null;
			// Reuse the exact same active-world quest lookup used by the existing Quests editor.
			searchReq.open('GET', '../ajax/eq2Ajax.php?type=luQ&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest;
			searchReq.send(null);
		}
	}
	function QuestFixSpawnLookupAJAX(stepNo) {
		var input = document.getElementById('quest_fix_spawn_lookup_' + stepNo);
		if (!input || input.value.length < 1) return;
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			var str = escape(input.value);
			txtSearchAjaxInput = 'quest_fix_spawn_lookup_' + stepNo;
			searchSuggestDiv = 'quest_fix_spawn_suggest_' + stepNo;
			ajaxSelectCallback = function() { QuestFixAddSpawnOverride(stepNo); };
			searchReq.open('GET', '../ajax/eq2Ajax.php?type=questFixSpawn&search=' + str, true);
			searchReq.onreadystatechange = handleSearchSuggest;
			searchReq.send(null);
		}
	}
	function QuestFixAddSpawnOverride(stepNo) {
		var lookup = document.getElementById('quest_fix_spawn_lookup_' + stepNo);
		var hidden = document.getElementById('quest_fix_spawn_ids_' + stepNo);
		var label = document.getElementById('quest_fix_spawn_manual_' + stepNo);
		if (!lookup || !hidden || !label) return;
		var match = lookup.value.match(/\((\d+)\)\s*$/);
		if (!match) return;
		var id = match[1];
		var ids = hidden.value ? hidden.value.split(/\s*,\s*/) : [];
		if (ids.indexOf(id) === -1) ids.push(id);
		hidden.value = ids.join(', ');
		label.innerHTML = '<strong>Manual ID override:</strong> ' + hidden.value;
		lookup.value = '';
		ajaxSelectCallback = null;
	}
	function QuestFixClearSpawnOverride(stepNo) {
		var hidden = document.getElementById('quest_fix_spawn_ids_' + stepNo);
		var label = document.getElementById('quest_fix_spawn_manual_' + stepNo);
		var suggest = document.getElementById('quest_fix_spawn_suggest_' + stepNo);
		if (hidden) hidden.value = '';
		if (label) label.innerHTML = '<strong>Manual ID override:</strong> none / auto ranking active';
		if (suggest) suggest.innerHTML = '';
	}
	</script>
	<fieldset>
		<legend>Quest Fix Assistant - BUILD6 FIX23: existing quest-name ghosting + SOE-stage integrated + DB/Wiki Lua preview</legend>
		<?php if( $isIntegratedSOE ) { ?>
		<table width="100%" cellpadding="4" cellspacing="0" border="0">
			<tr><td class="Label" width="180">Data Source:</td><td class="Detail">Existing SOE quest reference tables, existing stage rows, active world DB, Wiki Assist, and SpawnScripts.</td></tr>
			<tr><td class="Label">SOE Quest ID:</td><td class="Detail"><?= (int)$soeQuestId ?></td></tr>
		</table>
		<?php } else { ?>
		<form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
			<table width="100%" cellpadding="4" cellspacing="0" border="0">
				<tr>
					<td class="Label" width="180">Census Quest Name:</td>
					<td class="Detail">
						<input type="text" id="quest_fix_census_name" name="quest_fix_census_name" value="<?= htmlspecialchars($questName, ENT_QUOTES, 'UTF-8') ?>" onkeyup="QuestFixLocalQuestLookupAJAX();" autocomplete="off" style="width:520px;" />
						<div id="quest_fix_census_suggest"></div>
						<span style="font-size:11px;">Ghosted matches reuse the existing active-world <em>Quests</em> lookup. Clicking one only fills the name; press “Census suchen + parsen” to load its Census data.</span>
					</td>
				</tr>
				<tr>
					<td class="Label">Optional Census Filter:</td>
					<td class="Detail"><input type="text" name="quest_fix_census_option" value="<?= htmlspecialchars($questOption, ENT_QUOTES, 'UTF-8') ?>" style="width:320px;" /> Example: <code>crc=2378636280</code> or <code>&amp;crc=2378636280</code></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="submit" name="quest_fix_census_parse" value="Census suchen + parsen" /></td>
				</tr>
			</table>
		</form>
		<?php } ?>
		<?php if( is_array($census) && empty($census['ok']) ) { ?>
		<hr />
		<table width="100%" cellpadding="4" cellspacing="0" border="0">
			<tr bgcolor="#f5cccc"><td><strong>Census error:</strong> <?= htmlspecialchars($census['error'] ?? 'Unknown Census error.', ENT_QUOTES, 'UTF-8') ?></td></tr>
		</table>
		<?php } ?>
		<?php if( is_array($census) && !empty($census['ok']) && isset($census['quests']) && count($census['quests']) === 0 ) { ?>
		<hr />
		<table width="100%" cellpadding="4" cellspacing="0" border="0">
			<tr bgcolor="#fff0cc"><td>No Census quest matched this name/filter. Try exact spelling or add the optional CRC filter.</td></tr>
		</table>
		<?php } ?>
		<?php if( is_array($census) && !empty($census['ok']) && isset($census['quests']) && count($census['quests']) > 1 ) { ?>
		<hr />
		<table width="100%" cellpadding="4" cellspacing="0" border="0">
			<tr bgcolor="#cccccc"><td colspan="7"><strong>Multiple Census quests found — choose one with “Übernehmen”</strong></td></tr>
			<tr bgcolor="#dddddd"><td><strong>Name</strong></td><td><strong>ID</strong></td><td><strong>CRC</strong></td><td><strong>Category</strong></td><td><strong>Level</strong></td><td><strong>Tier</strong></td><td><strong>Action</strong></td></tr>
			<?php foreach($census['quests'] as $questRow) { ?>
			<tr>
				<td><?= htmlspecialchars($questRow['name'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['id'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['crc'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['category'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['level'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($questRow['tier'], ENT_QUOTES, 'UTF-8') ?></td>
				<td>
					<form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" style="display:inline;">
						<input type="hidden" name="quest_fix_census_name" value="<?= htmlspecialchars($questName, ENT_QUOTES, 'UTF-8') ?>" />
						<input type="hidden" name="quest_fix_census_option" value="<?= htmlspecialchars($questOption, ENT_QUOTES, 'UTF-8') ?>" />
						<input type="hidden" name="quest_fix_census_json" value="<?= htmlspecialchars(base64_encode($questRow['json']), ENT_QUOTES, 'UTF-8') ?>" />
						<input type="submit" name="quest_fix_census_accept" value="Übernehmen" />
					</form>
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php } ?>
		<?php if( is_array($model) && !empty($model['ok']) ) { ?>
		<hr />
		<table width="100%" cellpadding="4" cellspacing="0" border="0">
			<tr bgcolor="#cccccc"><td colspan="2"><strong>Selected Quest</strong></td></tr>
			<tr><td class="Label" width="180">Name:</td><td class="Detail"><?= htmlspecialchars($model['summary']['name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
			<tr><td class="Label">ID / CRC:</td><td class="Detail"><?= htmlspecialchars($model['summary']['id'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($model['summary']['crc'], ENT_QUOTES, 'UTF-8') ?></td></tr>
			<tr><td class="Label">Category / Level / Tier:</td><td class="Detail"><?= htmlspecialchars($model['summary']['category'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($model['summary']['level'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($model['summary']['tier'], ENT_QUOTES, 'UTF-8') ?></td></tr>
			<tr><td class="Label">Quest Branches:</td><td class="Detail"><?= (int)count($model['branches']) ?></td></tr>
			<tr><td class="Label">Assistant Source:</td><td class="Detail"><?= !empty($model['source']) && $model['source'] === 'soe_reference_tables' ? 'SOE reference tables + existing quest browser structures' : 'Direct Census fallback' ?></td></tr>
			<tr><td class="Label">Local EQ2Emu Quest:</td><td class="Detail"><?php if(!empty($model['local_quest'])) { ?>ID <?= (int)$model['local_quest']['id'] ?> — <?= htmlspecialchars($model['local_quest']['name'], ENT_QUOTES, 'UTF-8') ?><?php } else { ?>Not found by exact name in active world DB<?php } ?></td></tr>
			<tr><td class="Label">EQ2 Wiki Assist:</td><td class="Detail"><?php if(!empty($model['wiki']['ok'])) { ?>Loaded<?= !empty($model['wiki']['source']) ? ' [' . htmlspecialchars($model['wiki']['source'], ENT_QUOTES, 'UTF-8') . ']' : '' ?> — <?= (int)count($model['wiki']['coordinates'] ?? array()) ?> waypoint coordinate(s) parsed<?php } else { ?>Not available<?php if(!empty($model['wiki']['error'])) { ?> — <?= htmlspecialchars($model['wiki']['error'], ENT_QUOTES, 'UTF-8') ?><?php } ?><?php } ?></td></tr>
		</table>
		<br />
		<form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
			<input type="hidden" name="quest_fix_census_name" value="<?= htmlspecialchars($questName, ENT_QUOTES, 'UTF-8') ?>" />
			<input type="hidden" name="quest_fix_census_option" value="<?= htmlspecialchars($questOption, ENT_QUOTES, 'UTF-8') ?>" />
			<?php if( $isIntegratedSOE ) { ?>
			<input type="hidden" name="quest_fix_soe_id" value="<?= (int)$soeQuestId ?>" />
			<?php } else { ?>
			<input type="hidden" name="quest_fix_selected_json" value="<?= htmlspecialchars(base64_encode($selectedQuestJson), ENT_QUOTES, 'UTF-8') ?>" />
			<?php } ?>
			<table width="100%" cellpadding="4" cellspacing="0" border="0">
				<tr bgcolor="#cccccc"><td colspan="13"><strong>Auto Parsed Quest Steps — Type can be changed, then press “Update Lua Preview”</strong></td></tr>
				<tr bgcolor="#dddddd"><td><strong>Step</strong></td><td><strong>Reference Text</strong></td><td><strong>Auto Type</strong></td><td><strong>Selected Type</strong></td><td><strong>Count</strong></td><td><strong>Target</strong></td><td><strong>Zone</strong></td><td><strong>Best ID</strong></td><td><strong>Best Match</strong></td><td><strong>Score</strong></td><td><strong>Script Evidence</strong></td><td><strong>Candidate / Location Override</strong></td><td><strong>Override?</strong></td></tr>
				<?php foreach($model['branches'] as $branch) { $best = count($branch['candidates']) > 0 ? $branch['candidates'][0] : null; $selectedType = $branch['analysis']['step_type']; $autoType = $branch['analysis']['auto_type'] ?? $selectedType; $bestId = $selectedType === 'Location' ? 0 : (int)$branch['best_spawn_id']; $scriptEvidence = $best !== null && !empty($best['script_evidence']) ? $best['script_evidence'] : array(); $locationText = $selectedType === 'Location' && !empty($branch['best_location']) ? (($branch['best_location']['source_zone_name'] ?? '') . ' @ ' . ($branch['best_location']['x'] ?? 0) . ', ' . ($branch['best_location']['y'] ?? 0) . ', ' . ($branch['best_location']['z'] ?? 0)) : 'Unresolved'; $selectedLocationCandidateId = (int)($branch['best_location']['id'] ?? 0); $selectedSpawnCandidateIds = isset($branch['selected_spawn_candidate_ids']) && is_array($branch['selected_spawn_candidate_ids']) ? array_values(array_unique(array_map('intval', $branch['selected_spawn_candidate_ids']))) : array(); ?>
				<tr<?= $best !== null ? ' bgcolor="#d8f0d8"' : '' ?>>
					<td><?= (int)$branch['step_number'] ?></td>
					<td><?= htmlspecialchars($branch['step_text'], ENT_QUOTES, 'UTF-8') ?></td>
					<td><?= htmlspecialchars($autoType, ENT_QUOTES, 'UTF-8') ?></td>
					<td>
						<select name="quest_fix_step_type[<?= (int)$branch['step_number'] ?>]">
						<?php foreach($stepTypes as $stepType) { ?>
							<option value="<?= htmlspecialchars($stepType, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedType === $stepType ? ' selected="selected"' : '' ?>><?= htmlspecialchars($stepType, ENT_QUOTES, 'UTF-8') ?></option>
						<?php } ?>
						</select>
					</td>
					<td><?= (int)$branch['analysis']['count'] ?></td>
					<td><?= htmlspecialchars($branch['analysis']['target'], ENT_QUOTES, 'UTF-8') ?></td>
					<td><?= htmlspecialchars($branch['analysis']['zone'], ENT_QUOTES, 'UTF-8') ?></td>
					<td><?= (int)$bestId ?></td>
					<td><?= $best !== null ? htmlspecialchars($best['name'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
					<td><?= $best !== null ? (int)$best['score'] : 0 ?></td>
					<td><?php if(!empty($scriptEvidence['has_set_step_complete'])) { ?>Exact step complete<?php } elseif(!empty($scriptEvidence['has_get_step'])) { ?>Quest step check<?php } elseif(!empty($scriptEvidence['has_script'])) { ?>Script present<?php } else { ?>—<?php } ?></td>
					<td>
						<?php if( $selectedType === 'Location' ) { ?>
							<div><strong><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></strong><?= !empty($branch['location_auto_selected']) ? ' <em>(auto)</em>' : '' ?></div>
							<select name="quest_fix_location_candidate[<?= (int)$branch['step_number'] ?>]" style="width:520px; max-width:100%; margin-top:4px;">
								<option value="0"<?= $selectedLocationCandidateId === 0 ? ' selected="selected"' : '' ?>>unresolved / choose later</option>
								<?php foreach(array_slice($branch['candidates'], 0, 25) as $locationCandidate) { $candidateId = (int)($locationCandidate['id'] ?? 0); $candidateSource = $locationCandidate['source'] ?? 'DB'; $candidateLabel = '[' . $candidateSource . '] ' . ($locationCandidate['name'] ?? 'Location') . ' | ' . ($locationCandidate['source_zone_name'] ?? '') . ' @ ' . ($locationCandidate['x'] ?? 0) . ', ' . ($locationCandidate['y'] ?? 0) . ', ' . ($locationCandidate['z'] ?? 0) . ' | score ' . (int)($locationCandidate['score'] ?? 0); ?>
									<option value="<?= $candidateId ?>"<?= $selectedLocationCandidateId === $candidateId ? ' selected="selected"' : '' ?>><?= htmlspecialchars($candidateLabel, ENT_QUOTES, 'UTF-8') ?></option>
								<?php } ?>
							</select>
						<?php } elseif( $selectedType === 'Kill' || $selectedType === 'Chat' ) { $resolvedSpawnIds = !empty($branch['best_spawn_ids']) ? implode(', ', array_map('intval', $branch['best_spawn_ids'])) : ((int)($branch['best_spawn_id'] ?? 0) > 0 ? (string)(int)$branch['best_spawn_id'] : 'unresolved'); $manualSpawnText = count($selectedSpawnCandidateIds) > 0 ? implode(', ', $selectedSpawnCandidateIds) : ''; $stepNo = (int)$branch['step_number']; ?>
							<div><strong>Resolved spawn ID(s): <?= htmlspecialchars($resolvedSpawnIds, ENT_QUOTES, 'UTF-8') ?></strong><?= !empty($branch['spawn_candidate_manual_selected']) ? ' <em>(manual)</em>' : ' <em>(auto)</em>' ?></div>
							<input type="hidden" id="quest_fix_spawn_ids_<?= $stepNo ?>" name="quest_fix_spawn_candidate_text[<?= $stepNo ?>]" value="<?= htmlspecialchars($manualSpawnText, ENT_QUOTES, 'UTF-8') ?>" />
							<div id="quest_fix_spawn_manual_<?= $stepNo ?>" style="font-size:11px; margin-top:2px;"><strong>Manual ID override:</strong> <?= $manualSpawnText !== '' ? htmlspecialchars($manualSpawnText, ENT_QUOTES, 'UTF-8') : 'none / auto ranking active' ?></div>
							<div style="margin-top:4px;">
								<input type="text" id="quest_fix_spawn_lookup_<?= $stepNo ?>" autocomplete="off" class="box" style="width:420px;" placeholder="Search spawn name or ID; click a ghosted result to add it" onkeyup="QuestFixSpawnLookupAJAX(<?= $stepNo ?>);" />
								<input type="button" value="Clear manual IDs" onclick="QuestFixClearSpawnOverride(<?= $stepNo ?>);" />
								<div id="quest_fix_spawn_suggest_<?= $stepNo ?>"></div>
							</div>
							<div style="font-size:11px; margin-top:2px;">Uses the existing editor ghosting style and the active spawn DB. Multiple clicked results are stored as comma-separated manual IDs for Lua preview.</div>
							<?php if( count($branch['candidates']) > 0 ) { ?>
							<div style="font-size:11px; margin-top:4px;"><strong>Top ranked auto candidates:</strong> <?php $preview = array(); foreach(array_slice($branch['candidates'], 0, 5) as $spawnCandidate) { $preview[] = '#' . (int)($spawnCandidate['id'] ?? 0) . ' ' . ($spawnCandidate['name'] ?? 'Spawn'); } echo htmlspecialchars(implode(' | ', $preview), ENT_QUOTES, 'UTF-8'); ?></div>
							<?php } ?>
						<?php } else { ?>
							—
						<?php } ?>
					</td>
					<td><?= !empty($branch['analysis']['manual_override']) ? 'Yes' : 'No' ?></td>
				</tr>
				<?php } ?>
			</table>
			<p><input type="submit" name="quest_fix_update_lua" value="Update Lua Preview" /></p>
			<table width="100%" cellpadding="4" cellspacing="0" border="0">
				<tr bgcolor="#cccccc"><td><strong>Generated Lua Preview</strong></td></tr>
				<tr><td><textarea rows="36" style="width:99%; font-family:monospace;"><?= htmlspecialchars($lua, ENT_QUOTES, 'UTF-8') ?></textarea></td></tr>
			</table>
		</form>
		<?php } ?>
	</fieldset>
	<br />
	<?php
}

function ShowSOEQuestFixer()
{
	global $admin;
	
	$quest = $admin->GetSOEQuest((int)($_GET['id'] ?? 0));
	$stages = $admin->GetSOEQuestStages((int)($_GET['id'] ?? 0));
	?>
	<br />
	<fieldset>
		<legend>Quest Fixer: Automatic Stage Parsing</legend>
		<table width="100%" cellpadding="4" cellspacing="0" border="0">
			<tr><td class="Label" width="160">Quest:</td><td class="Detail"><?= htmlspecialchars($quest['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr>
			<tr><td class="Label">Mode:</td><td class="Detail">Test Build 1 auto-parses Kill stages and proposes EQ2Emu spawn IDs from the active world database.</td></tr>
		</table>
	</fieldset>
	<?php
	if( !is_array($stages) || count($stages) === 0 )
	{
		print('<p>No SOE quest stage data found.</p>');
		return;
	}
	
	foreach($stages as $stage)
	{
		$analysis = $admin->QuestFixAnalyzeText($stage['description'] ?? '');
		$candidates = array();
		$bestSpawnId = 0;
		$lua = '';
		if( $analysis['step_type'] === 'Kill' && trim($analysis['target']) !== '' )
		{
			$candidates = $admin->QuestFixFindSpawnCandidates($analysis['target'], $analysis['zone'], 8);
			if( count($candidates) > 0 )
				$bestSpawnId = (int)$candidates[0]['id'];
			$lua = $admin->QuestFixBuildKillLuaSnippet((int)($stage['stage_num'] ?? 1), $analysis, $bestSpawnId, $stage['description'] ?? '');
		}
		?>
		<br />
		<fieldset>
			<legend>Stage #<?= (int)($stage['stage_num'] ?? 0) ?></legend>
			<table width="100%" cellpadding="4" cellspacing="0" border="0">
				<tr><td class="Label" width="160">Stage Text:</td><td class="Detail"><?= htmlspecialchars($stage['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr>
				<tr><td class="Label">Detected:</td><td class="Detail"><?= htmlspecialchars($analysis['step_type'], ENT_QUOTES, 'UTF-8') ?> | Count <?= (int)$analysis['count'] ?> | Target <?= htmlspecialchars($analysis['target'], ENT_QUOTES, 'UTF-8') ?> | Zone <?= htmlspecialchars($analysis['zone'], ENT_QUOTES, 'UTF-8') ?></td></tr>
				<tr><td class="Label">Best Spawn:</td><td class="Detail"><?= $bestSpawnId > 0 ? (int)$bestSpawnId : 'No automatic match' ?></td></tr>
			</table>
			<?php if( count($candidates) > 0 ) { ?>
			<br />
			<table width="100%" cellpadding="4" cellspacing="0" border="0">
				<tr bgcolor="#dddddd"><td><strong>ID</strong></td><td><strong>Name</strong></td><td><strong>Zone</strong></td><td><strong>Score</strong></td><td><strong>Reason</strong></td></tr>
				<?php foreach($candidates as $candidate) { ?>
				<tr><td><?= (int)$candidate['id'] ?></td><td><?= htmlspecialchars($candidate['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars(($candidate['zone_description'] ?: $candidate['zone_name']), ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$candidate['score'] ?></td><td><?= htmlspecialchars($candidate['score_reasons'], ENT_QUOTES, 'UTF-8') ?></td></tr>
				<?php } ?>
			</table>
			<?php } ?>
			<?php if( trim($lua) !== '' ) { ?>
			<br />
			<textarea rows="4" style="width:98%; font-family:monospace;"><?= htmlspecialchars($lua, ENT_QUOTES, 'UTF-8') ?></textarea>
			<?php } ?>
		</fieldset>
		<?php
	}
}

function ShowSOEQuestDetails()
{
	global $eq2, $admin;
	
	$row = $admin->GetSOEQuest($_GET['id']);

	?>
	<br />
	<table class="SectionMain" cellpadding="4" cellspacing="0" border="0">
		<tr>
			<td class="SectionTitle" colspan="2">Quest Detail
				<div style="float:right">
					<a href="http://census.daybreakgames.com/xml/get/eq2/quest/?id=<?= $row['quest_id_fk'] ?>" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
				</div>
			</td>
		</tr>
		<tr>
			<td width="150" class="Label">id:</td>
			<td class="ReadOnlyDetail"><?= $row['id'] ?></td>
		</tr>
		<tr>
			<td class="Label">quest_id:</td>
			<td class="ReadOnlyDetail"><a href="http://census.daybreakgames.com/xml/get/eq2/quest/?id=<?= $row['quest_id'] ?>" target="_blank"><?= $row['quest_id'] ?></a></td>
		</tr>
		<tr>
			<td class="Label">category:</td>
			<td class="ReadOnlyDetail"><?= $row['category'] ?></td>
		</tr>
		<tr>
			<td class="Label">name:</td>
			<td class="ReadOnlyDetail"><?= $row['name'] ?></td>
		</tr>
		<tr>
			<td valign="top" class="Label">description:</td>
			<td class="ReadOnlyDetail"><?= $row['description'] ?></td>
		</tr>
		<tr>
			<td class="Label">level:</td>
			<td class="ReadOnlyDetail"><?= $row['level'] ?></td>
		</tr>
		<tr>
			<td class="Label">tier:</td>
			<td class="ReadOnlyDetail"><?= $row['tier'] ?></td>
		</tr>
		<tr>
			<td class="Label">is_tradeskill:</td>
			<td class="ReadOnlyDetail"><?= $row['is_tradeskill'] ?></td>
		</tr>
		<tr>
			<td class="Label">shareable:</td>
			<td class="ReadOnlyDetail"><?= $row['shareable'] ?></td>
		</tr>
		<tr>
			<td class="Label">complete_shareable:</td>
			<td class="ReadOnlyDetail"><?= $row['complete_shareable'] ?></td>
		</tr>
		<tr>
			<td class="Label">repeatable:</td>
			<td class="ReadOnlyDetail"><?= $row['repeatable'] ?></td>
		</tr>
		<tr>
			<td class="Label">soe_quest_crc:</td>
			<td class="ReadOnlyDetail"><a href="http://census.daybreakgames.com/xml/get/eq2/quest/?crc=<?= $row['soe_quest_crc'] ?>" target="_blank"><?= $row['soe_quest_crc'] ?></a></td>
		</tr>
		<tr>
			<td class="Label">ts:</td>
			<td class="ReadOnlyDetail"><?= date("M d, Y h:i:s", $row['ts']) ?> (<?= $row['ts'] ?>)</td>
		</tr>
		<tr>
			<td class="Label">last_update:</td>
			<td class="ReadOnlyDetail"><?= date("M d, Y h:i:s", $row['last_update']) ?> (<?= $row['last_update'] ?>)</td>
		</tr>
	</table>
	<?php
}


function ShowSOEQuestStages()
{
	global $eq2, $admin;
	
	$stages 	= $admin->GetSOEQuestStages($_GET['id']);
	$branches	= $admin->GetSOEQuestStageBranches($_GET['id']);
	?>
	<br />
	<table class="SectionMain" cellpadding="4" cellspacing="0" border="0">
		<tr>
			<td class="SectionTitle" colspan="6">Stage Data
				<div style="float:right">
					<a href="http://census.daybreakgames.com/xml/get/eq2/quest/?id=<?= $row['quest_id_fk'] ?>" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
				</div>
			</td>
		</tr>
		<?php
		foreach($stages as $row)
		{
		?>
		<tr>
			<td colspan="6" style="border-top:3px solid #678;border-bottom:1px solid #678;">Stage #<?= $row['stage_num'] ?></td>
		</tr>
		<tr>
			<td width="150" class="Label">id:</td>
			<td class="Detail"><?= $row['id'] ?></td>
			<td width="150" class="Label">icon_id:</td>
			<td class="Detail"><?= $row['icon_id'] ?></td>
			<td width="150" class="Label">quota_min:</td>
			<td class="Detail"><?= $row['quota_min'] ?></td>
		</tr>
		<tr>
			<td class="Label">quest_id:</td>
			<td class="Detail"><?= $row['quest_id_fk'] ?></td>
			<td class="Label">icon_name:</td>
			<td class="Detail"><?= $row['icon_name'] ?></td>
			<td class="Label">quota_max:</td>
			<td class="Detail"><?= $row['quota_max'] ?></td>
		</tr>
		<tr>
			<td class="Label">completed_text:</td>
			<td class="ReadOnlyDetail" colspan="5"><?= $row['completed_text'] ?></td>
		</tr>
		<?php if( strlen($row['completed_zone']) > 0 ) { ?>
		<tr>
			<td class="Label">completed_zone:</td>
			<td class="ReadOnlyDetail" colspan="5"><?= $row['completed_zone'] ?></td>
		</tr>
		<?php } ?>
		<?php if( strlen($row['completed_zone_override']) > 0 ) { ?>
		<tr>
			<td class="Label">completed_zone_override:</td>
			<td class="ReadOnlyDetail" colspan="5"><?= $row['completed_zone_override'] ?></td>
		</tr>
		<?php } ?>
		<tr>
			<td class="Label">description:</td>
			<td class="ReadOnlyDetail" colspan="5"><?= $row['description'] ?></td>
		</tr>
		<?php
		}
		?>
	</table>
	<?php if( is_array($branches) ) { ?>
	<hr style="margin-top:30px; margin-bottom:30px;" />
	<table class="SectionMain" cellpadding="4" cellspacing="0" border="0">
		<tr>
			<td class="SectionTitle" colspan="6">Branch Data
				<div style="float:right">
					<a href="http://census.daybreakgames.com/xml/get/eq2/quest/?id=<?= $row['quest_id_fk'] ?>" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
				</div>
			</td>
		</tr>
		<?php
		foreach($branches as $row)
		{
		?>
		<tr>
			<td colspan="6" style="border-top:3px solid #678;border-bottom:1px solid #678;">Branch for Stage #<?= $row['stage_num'] ?></td>
		</tr>
		<tr>
			<td width="150" class="Label">id:</td>
			<td class="Detail"><?= $row['id'] ?></td>
			<td width="150" class="Label">quest_id:</td>
			<td class="Detail"><?= $row['quest_id_fk'] ?></td>
		</tr>
		<tr>
			<td class="Label">branch_item_crc:</td>
			<td class="Detail"><a href="http://census.daybreakgames.com/xml/get/eq2/item/?id=<?= $row['branch_item_crc'] ?>" target="_blank"><?= $row['branch_item_crc'] ?></a></td>
			<td class="Label">branch_item_qty:</td>
			<td class="Detail"><?= $row['branch_item_qty'] ?></td>
		</tr>
		<?php
		}
		?>
	</table>
	<?php 
	} 
}


function ShowSOEQuestRewards()
{
	global $eq2, $admin;
	
	$rewards	= $admin->GetSOEQuestRewards($_GET['id']);
	
	if( is_array($rewards) )
	{
	?>
	<br />
	<table class="SectionMain" cellpadding="4" cellspacing="0" border="0">
		<tr>
			<td class="SectionTitle" colspan="6">Reward Data
				<div style="float:right">
					<a href="http://census.daybreakgames.com/xml/get/eq2/quest/?id=<?= $_GET['id'] ?>" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
				</div>
			</td>
		</tr>
		<?php
		$i=1;
		foreach($rewards as $row)
		{
			$reward_id = $row['id'];
		?>
		<tr>
			<td colspan="6" style="border-top:3px solid #678;border-bottom:1px solid #678;">Reward #<?= $i++ ?></td>
		</tr>
		<tr>
			<td width="150" class="Label">id:</td>
			<td class="ReadOnlyDetail"><?= $row['id'] ?></td>
			<td width="150" class="Label">quest_id:</td>
			<td class="ReadOnlyDetail"><?= $row['quest_id_fk'] ?></td>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td class="Label">coin_min:</td>
			<td class="ReadOnlyDetail"><?= $row['coin_min'] ?></td>
			<td class="Label">coin_max:</td>
			<td class="ReadOnlyDetail"><?= $row['coin_max'] ?></td>
			<td class="Label">exp:</td>
			<td class="ReadOnlyDetail"><?= $row['exp'] ?></td>
		</tr>
		<?php
		}
		?>
	</table>
	<?php 
	}

	$items		= $admin->GetSOEQuestRewardItems($reward_id);

	if( is_array($items) ) 
	{ 
	?>
	<hr style="margin-top:30px; margin-bottom:30px;" />
	<table class="SectionMain" cellpadding="4" cellspacing="0" border="0">
		<tr>
			<td class="SectionTitle" colspan="6">Reward Item Data
				<div style="float:right">
					<a href="http://census.daybreakgames.com/xml/get/eq2/quest/?id=<?= $_GET['id'] ?>" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
				</div>
			</td>
		</tr>
		<?php
		$i=1;
		foreach($items as $row)
		{
		?>
		<tr>
			<td colspan="6" style="border-top:3px solid #678;border-bottom:1px solid #678;">Rewarded Item #<?= $i++ ?></td>
		</tr>
		<tr>
			<td width="150" class="Label">id:</td>
			<td class="ReadOnlyDetail"><?= $row['id'] ?></td>
			<td width="150" class="Label">quest_id:</td>
			<td class="ReadOnlyDetail"><?= $row['quest_id_fk'] ?></td>
		</tr>
		<tr>
			<td class="Label">item_crc:</td>
			<td class="Detail"><a href="http://census.daybreakgames.com/xml/get/eq2/item/?id=<?= $row['item_crc'] ?>" target="_blank"><?= $row['item_crc'] ?></a></td>
			<td class="Label">quantity:</td>
			<td class="Detail"><?= $row['quantity'] ?></td>
		</tr>
		<tr>
			<td class="Label">item_type:</td>
			<td class="Detail"><?= $row['item_type'] ?></td>
			<td class="Label">item_table_id:</td>
			<td class="Detail"><a href="http://census.daybreakgames.com/xml/get/eq2/itemtable/?id=<?= $row['item_table_id'] ?>" target="_blank"><?= $row['item_table_id'] ?></a></td>
		</tr>
		<?php
		}
		?>
	</table>
	<?php 
	} 
	
	$factions	= $admin->GetSOEQuestRewardFactions($reward_id);

	if( is_array($factions) ) 
	{ 
	?>
	<hr style="margin-top:30px; margin-bottom:30px;" />
	<table class="SectionMain" cellpadding="4" cellspacing="0" border="0">
		<tr>
			<td class="SectionTitle" colspan="6">Reward Faction Data
				<div style="float:right">
					<a href="http://census.daybreakgames.com/xml/get/eq2/quest/?id=<?= $_GET['id'] ?>" target="_blank"><img src="../images/soe.png" border="0" align="top" title="SOE" alt="SOE" height="20" /></a>
				</div>
			</td>
		</tr>
		<?php
		$i=1;
		foreach($factions as $row)
		{
		?>
		<tr>
			<td colspan="6" style="border-top:3px solid #678;border-bottom:1px solid #678;">Rewarded Faction #<?= $i++ ?></td>
		</tr>
		<tr>
			<td width="150" class="Label">id:</td>
			<td class="ReadOnlyDetail"><?= $row['id'] ?></td>
			<td width="150" class="Label">quest_id:</td>
			<td class="ReadOnlyDetail"><?= $_GET['id'] ?></td>
		</tr>
		<tr>
			<td class="Label">faction_id:</td>
			<td class="Detail"><a href="http://census.daybreakgames.com/xml/get/eq2/faction/?id=<?= $row['faction_id'] ?>" target="_blank"><?= $row['faction_id'] ?></a></td>
			<td class="Label">change:</td>
			<td class="Detail"><?= $row['change'] ?></td>
		</tr>
		<?php
		}
	}
}


function GetServerStats() 
{
	global $eq2, $admin;
	
	// shorthand
	$a = $admin;
	$s = $a->spawns;
	$z = $s->zones;
	$spells = $a->spells;
	
	// build player stats array
	$player_stats['Total Accounts'] 	= $a->GetTotalAccounts();
	$player_stats['Total Players'] 		= $a->GetTotalCharacters();
	$player_stats['Average Level'] 		= $a->GetAverageLevel($player_stats['Total Players']);	
	?>
	<p>This page displays a quick overview of the player and server stats, and content data available on this server.</p>
	<fieldset><legend>Test Center Server : Player Stats</legend>
	<table width="1000" cellpadding="4" border="0">
		<tr>
			<td valign="top" width="25%">
				<fieldset><legend>Quick Totals</legend>
				<table width="100%" cellpadding="2" cellspacing="0" border="0">
					<tr bgcolor="#cccccc">
						<td width="50%"><strong>Stat</strong></td>
						<td width="50%"><strong>Value</strong></td>
					</tr>
					<?php
					$i = 0;
					foreach($player_stats as $key=>$val)
					{
						$row_class = ( $i % 2 ) ? " bgcolor=\"#eeeeee\"" : "";
					?>
					<tr<?php print($row_class) ?>>
						<td><?php print($key) ?></td>
						<td><?php print(round($val)) ?></td>
					</tr>
					<?php
						$i++;
					}
					?>
					<tr>
						<td height="135"></td>
					</tr>
				</table>
				</fieldset>
			</td>
			<td valign="top" width="40%">
				<fieldset><legend>Most Experienced Players</legend>
				<table width="100%" cellpadding="2" cellspacing="0" border="0">
					<tr bgcolor="#cccccc">
						<td><strong>Player</strong></td>
						<td><strong>Class</strong></td>
						<td align="right"><strong>Levels</strong></td>
						<td align="right"><strong>Quests</strong></td>
					</tr>
					<?php
					$results = $a->GetMostExperiencedPlayers();
					
					if( is_array($results) )
					{
						$i = 0;
						foreach($results as $data)
						{
							$row_class = ( $i % 2 ) ? " bgcolor=\"#eeeeee\"" : "";
					?>
					<tr<?php print($row_class) ?>>
						<td><?php print($data['name']) ?></td>
						<td><?php print($eq2->eq2Classes[$data['class']]) ?></td>
						<td align="right"><?php printf("%d / %d", $data['level'], $data['tradeskill_level']) ?></td>
						<td align="right"><?php print($data['quests']) ?></td>
					</tr>
					<?php
							$i++;
						}
					}
					?>
				</table>
				</fieldset>
			</td>
			<td valign="top" width="35%">
				<fieldset><legend>Most Active Quests</legend>
				<table width="100%" cellpadding="2" cellspacing="0" border="0">
					<tr bgcolor="#cccccc">
						<td><strong>QID</strong></td>
						<td><strong>Quest Name</strong></td>
						<td align="right"><strong>Completed</strong></td>
					</tr>
					<?php
					$results = $a->GetMostActiveQuests();
					
					if( is_array($results) )
					{
						$i = 0;
						foreach($results as $data)
						{
							$row_class = ( $i % 2 ) ? " bgcolor=\"#eeeeee\"" : "";
							$pattern[0] = "/Quests\/.*?\/(.*?).lua/";
							$pattern[1] = "/_/";
							$replace[0] = "$1";
							$replace[1] = " ";
							$quest_name = preg_replace($pattern, $replace, $data['lua_script']);
					?>
					<tr<?php print($row_class) ?>>
						<td><?php print($data['quest_id']) ?></td>
						<td><?php print($quest_name) ?></td>
						<td align="right"><?php print($data['num_completed']) ?></td>
					</tr>
					<?php
						$i++;
					}
				}
				?>
				</table>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td valign="top" colspan="3">
				<fieldset><legend>Last 10 Players</legend>
				<table width="100%" cellpadding="2" cellspacing="0" border="0">
					<tr bgcolor="#cccccc">
						<td><strong>ID</strong></td>
						<td><strong>Account</strong></td>
						<td><strong>Player</strong></td>
						<td><strong>Class</strong></td>
						<td align="right"><strong>Levels</strong></td>
						<td align="right"><strong>Zone</strong></td>
						<td align="right"><strong>Date</strong></td>
					</tr>
					<?php
					$results = $a->GetTopPlayers(10);
					
					if( is_array($results) )
					{
						$i = 0;
						foreach($results as $data)
						{
							$row_class = ( $i % 2 ) ? " bgcolor=\"#dddddd\"" : "";
							
							if( $data['admin_status'] >= 200 ) 
								$player_name_class = " style=\"color:#ff0000; font-weight:bold;\"";
							else
								$player_name_class = "";
					?>
					<tr<?php print($row_class) ?>>
						<td><?php print($data['id']) ?></td>
						<td><?php print($data['account_id']) ?></td>
						<td<?php print($player_name_class) ?>><?php print($data['name']) ?></td>
						<td><?php print($eq2->eq2Classes[$data['class']]) ?></td>
						<td align="right"><?php printf("%d / %d", $data['level'], $data['tradeskill_level']) ?></td>
						<td align="right"><?php print($z->GetZoneName($data['current_zone_id'])) ?></td>
						<td align="right"><?php print($data['last_played']) ?></td>
					</tr>
					<?php
						$i++;
					}
				}
					?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	</fieldset>
	<br />
	<fieldset><legend>EQ2 DB Project : Server Stats</legend>
	<table width="100%" cellpadding="4">
		<tr>
			<td valign="top" width="25%">
				<fieldset><legend>Table Data</legend>
				<table width="100%" cellpadding="2" cellspacing="0" border="0">
					<tr bgcolor="#cccccc">
						<td width="50%"><strong>Table</strong></td>
						<td width="50%"><strong>Records</strong></td>
					</tr>
					<?php
					$table_stats = $a->GetServerStats();
					
					if( is_array($table_stats) )
					{
						$i = 0;
						
						foreach($table_stats as $key=>$val)
						{
							$row_class = ( $i % 2 ) ? " bgcolor=\"#dddddd\"" : "";
						?>
						<tr<?php print($row_class) ?>>
							<td><?php print($key) ?></td>
							<td><?php print($val) ?></td>
						</tr>
						<?php
							$i++;
						}
					}
					else
						print('<tr><td>Currently Disabled</td></tr>');
					?>
				</table>
				</fieldset>
			</td>
			<td valign="top" colspan="2">
				<fieldset><legend>Zones Populated</legend>
				<table width="100%" cellpadding="2" cellspacing="0" border="0">
					<tr bgcolor="#cccccc">
						<td><strong>Zone</strong></td>
						<td><strong>NPCs</strong></td>
						<td><strong>Objects</strong></td>
						<td><strong>Signs</strong></td>
						<td><strong>Widgets</strong></td>
						<td><strong>Ground</strong></td>
						<td><strong>Loot</strong></td>
						<td><strong>Quests</strong></td>
					</tr>
				<?php
				$popped_zones = $z->GetPopulatedZones();

				if( is_array($popped_zones) )
				{
					$i = 0;
					
					foreach($popped_zones as $data)
					{
						$row_class = ( $i % 2 ) ? " bgcolor=\"#dddddd\"" : "";
						$num_npcs					= $a->GetSpawnTypeTotalsByZone('npcs', $data['zid']);
						$num_objects			= $a->GetSpawnTypeTotalsByZone('objects', $data['zid']);
						$num_signs				= $a->GetSpawnTypeTotalsByZone('signs', $data['zid']);
						$num_widgets			= $a->GetSpawnTypeTotalsByZone('widgets', $data['zid']);
						$num_groundspawns	= $a->GetSpawnTypeTotalsByZone('ground', $data['zid']);
						$num_loots				= $a->GetSpawnTypeTotalsByZone('loot', $data['zid']);
						$num_quests				= $a->GetTotalQuestsByZone($data['name']); // barbarian! since there's no way to link a quest to a zone except by it's fookin path! :/
					?>
					<tr<?php print($row_class) ?>>
						<td><?php printf("%s (%d)", $data['description'], $data['zid']) ?></td>
						<td><?php print($num_npcs) ?></td>
						<td><?php print($num_objects) ?></td>
						<td><?php print($num_signs) ?></td>
						<td><?php print($num_widgets) ?></td>
						<td><?php print($num_groundspawns) ?></td>
						<td><?php print($num_loots) ?></td>
						<td><?php print($num_quests) ?></td>
					</tr>
					<?php
					$i++;
					} // end foreach
				} // end is_array
				?>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	</fieldset>
	<br />
	<fieldset><legend>Active Spells</legend>
	<table width="1000" class="SectionMainFloat" cellspacing="0">
		<tr bgcolor="#cccccc">
			<td><strong>ID</strong></td>
			<td><strong>Name</strong></td>
			<td><strong>Level</strong></td>
			<td><strong>Type</strong></td>
			<td><strong>LUAScript</strong></td>
		</tr>
	<?php
	$active_spells = $spells->GetActiveSpells();

	if( is_array($active_spells) )
	{
		$i = 0;
		
		foreach($active_spells as $data)
		{
			$row_class = ( $i % 2 ) ? " bgcolor=\"#dddddd\"" : "";
			
			if( $current_class != $data['adventure_class_id'] )
			{
				$current_class = $data['adventure_class_id'];
				printf('<tr><td colspan="5" class="SectionTitle">%s</td></tr>', $eq2->eq2Classes[$data['adventure_class_id']]);
			}
			
		?>
		<tr<?php print($row_class) ?>>
			<td><a href="spells.php?type=all&classification=all&class=<?= $data['adventure_class_id'] ?>&id=<?= $data['id'] ?>" target="_blank"><?php print($data['id']) ?></a></td>
			<td><?php print($data['name']) ?></td>
			<td><?php print($data['level']) ?></td>
			<td><?php print($spells->eq2SOESpellTypes[$data['type']]) ?></td>
			<td><?php print($data['lua_script']) ?></td>
		</tr>
		<?php
		$i++;
		} // end foreach
	} // end is_array
	?>
	</table>
	</fieldset>
<?php
}


function ShowMigrateAllButton($val) {
	?>
	<table border="0" cellspacing="4" class="SectionTogglesFloat" align="center">
		<form method="post" id="migrate-all">
		<tr>
			<td><input type="submit" name="submit" value="<?php print($val) ?>" /></td>
		</tr>
		<input type="hidden" name="id-list" value="<?= $a->migrate_id_list ?>" />
		<input type="hidden" name="spawn_id" value="singles" />
		<input type="hidden" name="cmd" value="migrate" />
		</form>
	</table>
	<?php
}


// porting vgo migrate to eq2
function Migrate() {
	global $eq2, $admin;
	
	// shorthand
	$a = $admin;
	$s = $a->spawns;
	$z = $s->zones;

	switch(strtolower($_POST['cmd'])) {
		case "migrate"			:
		case "spawn these"	: $a->ProcessMigrateSpawns(); break;
		case "spawn this"		: $a->ProcessSingleSpawnLocation(); break;
		case "hide these"		: $a->HideSpawnFromMigration(2); break;
		case "unhide these"	: $a->HideSpawnFromMigration(0); break;
		case "hide this"		: $a->HideSingleSpawnLocation(2); break;
		case "unhide this"	: $a->HideSingleSpawnLocation(0); break;
		case "set": 
			// store in session so reloads and navigation do not lose the settings
			$_SESSION['radius'] = ( $_POST['min-dupe'] > 0 ) ? $_POST['min-dupe'] : "";
			$_SESSION['version'] = ( $_POST['data-version'] > 0 ) ? $_POST['data-version'] : "";
			break;
	}
	
	// build the list of <options> for the Zone Picker
	$zoneOptions = $a->GetRawZoneOptions();
	
	?>
<table width="1280" cellspacing="0" cellpadding="4" border="0" bgcolor="#ccddee">
	<tr>
  	<td><strong>Zone</strong></td>
    <?php if( $z->zone_id > 0 ) { ?>
    <td><strong>Spawn Type</strong></td>
    <?php } ?>
    <?php if( strlen($s->spawn_type) > 0 ) { ?>
    <td><strong>Spawn Filter</strong></td>
    <td><strong>Advanced Filters</strong></td>
    <td align="center"><strong>Min Dupe Radius</strong></td>
    <td align="center"><strong>DataVer</strong></td>
    <?php } ?>
    <td colspan="2">&nbsp;</td>
  </tr>
	<tr>
		<td width="300" valign="top">
			<select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:300px">
				<option>Pick a Zone</option>
				<?= $zoneOptions ?>
			</select>
		</td>
		<?php
		// now that a zone is picked, display the spawn Types
		if( $z->zone_id > 0 ) 
		{
			$link = sprintf("_admin.php?page=%s&zone=%s&count=%s", $_GET['page'], $z->zone_id, $a->pCount);
			
			foreach($a->eq2SpawnTypes as $type)
			{
				$typeOptions .= sprintf('<option value="_admin.php?page=%s&zone=%s&count=%s&type=%s"%s>%s</option>', 
																$_GET['page'], 
																$z->zone_id,
																$a->pCount,
																strtolower($type),
																( $s->spawn_type == strtolower($type) ) ? " selected" : "",
																$type);
			}
		?>
		<td width="100" valign="top">
			<select name="spawnType" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:100px;">
				<option>Pick a Type</option>
				<?= $typeOptions ?>
			</select>
		</td>
		<?php 
		}
		
		// now that a type is picked, display the optional spawn filter
		if( strlen($s->spawn_type) > 0 ) 
		{
			$spawnOptions = $a->GetRawSpawnOptions();
			
			$link = sprintf("_admin.php?page=%s&zone=%s&count=%s&type=%s", $_GET['page'], $s->zone_id, $a->pCount, $s->spawn_type);
		?>
		<td width="180" valign="top">
			<select name="spawnId" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:200px;">
				<option value="<?= $link ?>">-- NPC Filter --</option>
				<?= $spawnOptions ?>
			</select>
    </td>
    <td width="160" valign="top">
			<select name="filters" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:150px;">
				<option value="<?= $link ?>">-- Advanced Filters --</option>
        <option value="_admin.php?page=<?= $_GET['page'] ?>&zone=<?= $_GET['zone'] ?>&count=<?= $_GET['count'] ?>&type=<?= $_GET['type'] ?>&hidden=1"<?php if( $_GET['hidden'] == 1 ) echo " selected" ?>>Show Hidden</option>
        <option value="_admin.php?page=<?= $_GET['page'] ?>&zone=<?= $_GET['zone'] ?>&count=<?= $_GET['count'] ?>&type=<?= $_GET['type'] ?>&singles=1"<?php if( $_GET['singles'] == 1 ) echo " selected" ?>>Show Singles</option>
			</select>
    </td>
    <form method="post" name="filter-dupes">
    <td width="100" align="center">
    	<input type="text" name="min-dupe" value="<?= $_SESSION['radius'] ?>" style="width:50px;" onclick="this.value='';" />
    </td>
    <td width="60" align="center">
    	<input type="text" name="data-version" value="<?= $_SESSION['version'] ?>" style="width:50px;" onclick="this.value='';" />
    </td>
    <td width="60" align="center">
      <input type="submit" name="cmd" value="Set" class="submit" />
    </td>
    </form>
		<?php 
		}
		?>
		<td valign="top">&nbsp;<a href="_admin.php?<?= $_SERVER['QUERY_STRING'] ?>">Reload Page</a></td>
	</tr>
	<?php
	if( strlen($s->spawn_type) > 0 ) {
		// 2015.08.26 - moved to new function
		$results = $a->GetMigrateData();

	// displays control options for migration scripts
	?>
	<tr height="30">
		<td colspan="7"><strong>Below are a list of spawn and placement IDs that do not yet exist in the "Live" DB. Populate the live database by using the button(s) below. Carefully. :)</strong></td>
	</tr>
  <tr height="30">
    <td width="150" align="right">To pop entire (filtered) zone:</td>
    <form method="post" name="migrate-all">
    <td width="100">
      <input type="submit" name="submit" value="Migrate All Displayed" />
      <input type="hidden" name="id-list" value="<?= $admin->migrate_id_list ?>" />
      <input type="hidden" name="spawn_id" value="singles" />
      <input type="hidden" name="cmd" value="migrate" />
    </td>
    </form>
    <td colspan="5">&nbsp;</td>
  </tr>
</table>
<br />
<table cellspacing="1" cellpadding="2" border="1" style="background-color:#cde">
	<?php
	if( is_array($results) ) {
		
		$unique_count = 0;
		$total_count = 0;
		
		foreach($results as $data)
		{
			if( !$showHidden && ($data['proc1'] == 2 || $data['proc2'] == 2) )
				continue;
			if( $showHidden && $data['proc2'] <> 2 )
				continue;
			
			//$new_spawn_id = $admin->GetNewSpawnID($data);
			//$new_spawn_id_style = ( $new_spawn_id != $data['spawn_id'] ) ? " background-color:#ff0; border-color:#f00;" : "";

			if( $s->spawn_type =="npcs" )
				$colHeader = "Levels";
			else if( $s->spawn_type == "objects" || $s->spawn_type == "ground" )
				$colHeader = "Visual";
			else
				$colHeader = "WidgetID";
		?>
		<tr style="background-color:#000; color:#fff;">
			<td width="125" colspan="2">&nbsp;<strong>Spawn ID</strong></td>
			<td width="250">&nbsp;<strong>Name</strong></td>
			<td width="250"&nbsp;><strong>Sub-Title</strong></td>
			<td width="50">&nbsp;<strong>Race</strong></td>
			<td width="50">&nbsp;<strong>Model</strong></td>
			<td width="50">&nbsp;<strong><?= $colHeader ?></strong></td>
			<td width="100" align="center">&nbsp;<strong>DataVer</strong></td>
			<td width="200">&nbsp;</td>
		</tr>
		<form method="post" name="spawn|<?= $data['id'] ?>">
		<tr valign="top">
			<td colspan="2" align="right"><?= $data['id'] ?></td>
			<td><strong>
				<?= $data['name'] ?>
				</strong></td>
			<td><?= $data['sub_title'] ?></td>
			<td><?= $data['race'] ?></td>
			<td rowspan="2"><?= $data['model_type'] ?></td>
			<?php
			switch($s->spawn_type)
			{
				case "npcs":
					printf('<td>%s/%s/%s</td>', $data['min_level'], $data['max_level'], $data['enc_level']);
					break;
				
				case "objects":
				case "ground":
					printf('<td>%s</td>', $data['visual_state']);
					break;
					
				case "widgets":
				case "signs":
					printf('<td>%s</td>', $data['widget_id']);
					break;
				
			}
			?>
			<!--<td align="right"><input type="text" name="new_spawn_id" value="<?= $new_spawn_id ?>" style="width:70px;<?= $new_spawn_id_style ?>" /></td>-->
			<td align="center"><?= $data['data_version'] ?></td>
			<td nowrap align="right">
			<?php 
			if( $showHidden && $data['proc1'] == 0 ) { 
			?>
				&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Spawn These" class="submit-wide" />
				&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Hide These" class="submit-wide" />
			<?php 
				$parentHidden = 0;
			}
			elseif ( $showHidden && $data['proc1'] == 2 )
			{
			?>
				&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Spawn These" class="submit-wide" />
				&nbsp;<input type="submit" name="cmd" value="Unhide These" class="submit-wide" />
			<?php 
				$parentHidden = 1;
			}
			else 
			{
			?>
				&nbsp;<input type="submit" name="cmd" value="Spawn These" class="submit-wide" />
				&nbsp;<input type="submit" name="cmd" value="Hide These" class="submit-wide" />
			<?php
			}
			?>
			</td>
		</tr>
		<tr>
			<td colspan="5">&nbsp;</td>
			<td colspan="3"><span style="font-size:10px;"><?= $data['appearance_name'] ?></span></td>
		</tr>
		<input type="hidden" name="spawn_id" value="<?= $data['id'] ?>" />
		</form>
		<tr>
			<td colspan="2" align="right"><strong>Locations:</strong></td>
			<td colspan="7" valign="top" style="background-color:#fff">
				<table width="100%" cellspacing="1" cellpadding="2" border="1">
					<tr style="background-color:#000; color:#fff;">
						<td width="100">&nbsp;<strong>LocationID</strong></td>
						<td width="100">&nbsp;<strong>X</strong></td>
						<td width="100">&nbsp;<strong>Y</strong></td>
						<td width="100">&nbsp;<strong>Z</strong></td>
						<td width="100">&nbsp;<strong>H</strong></td>
						<td width="100">&nbsp;<strong>group_id</strong></td>
						<td>&nbsp;</td>
					</tr>
				<?php
				$placements = $a->GetSpawnPlacements($data['id'], $showHidden);
				
				if( is_array($placements) )
				{
					$spawn_count = count($placements); // per section
					
					foreach($placements as $data2)
					{
						if( !$showHidden && $data2['processed']==2 )
							continue;
						
						if( !isset($group_id_color[$data2['group_id']]) && $data2['group_id'] > 0 )
							$group_id_color[$data2['group_id']] = $eq2->SetRandomColor();
							
						$total_count++; // overall
						
						$Proximity = sprintf("popup_functions.php?page=proximity&zone=%s&x=%s&z=%s&distance=5", $z->zone_id, $data2['x'], $data2['z']);
					?>
					<form method="post" name="spawn|location_<?= $data2['spawn_location_id'] ?>">
					<tr>
						<td>&nbsp;<?= $data2['spawn_location_id'] ?></td>
						<td>&nbsp;<?= $data2['x'] ?></td>
						<td>&nbsp;<?= $data2['y'] ?></td>
						<td>&nbsp;<?= $data2['z'] ?></td>
						<td>&nbsp;<?= $data2['heading'] ?></td>
						<td<?php if( isset($group_id_color[$data2['group_id']]) ) printf(' bgcolor="#%s"', $group_id_color[$data2['group_id']]); ?>>&nbsp;<?= $data2['group_id'] ?></td>
						<td colspan="2" align="right" nowrap="nowrap">
						<?php
						/* Proximity spawning needs to be re-thought out... 
						 * Currently, migrating ALL spawns of anything close to the 1 selected
						 * Needs to only migrate spawn_location_id's close to the 1 selected
						*/
						if( $s->spawn_type=="npcs" )
							printf('&nbsp;<input type="button" value="Proximity" class="submit" onclick="javascript:window.open(\'%s\', target=\'_blank\');" disabled />', $Proximity);
							
						if( $parentHidden) { ?>
							&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Spawn This" class="submit" />
							&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Hide This" class="submit" />
						<?php } elseif( !$parentHidden && $data2['processed']==2 ) { ?>
							&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Spawn This" class="submit" />
							&nbsp;<input type="submit" name="cmd" value="Unhide This" class="submit" />
						<?php } else { ?>
							&nbsp;<input type="submit" name="cmd" value="Spawn This" class="submit" />
							&nbsp;<input type="submit" name="cmd" value="Hide This" class="submit" />
						<?php } ?>
						</td>
					</tr>
					<input type="hidden" name="spawn_location_id" value="<?= $data2['spawn_location_id'] ?>" />
					<input type="hidden" name="spawn_id" value="<?= $data['id'] ?>" />
					<input type="hidden" name="group_id" value="<?= ( $data2['group_id'] > 0 ) ? $data2['group_id'] : "" ?>" />
					<input type="hidden" name="spawn_count" value="<?= $spawn_count ?>" />
					</form>
					<?php
					}
				}
				?>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="9">&nbsp;<strong><?= $spawn_count ?> Spawn Points</strong>
				<span style="font-size:10px; float:right;">
					select * from <?= RAW_DB ?>.spawn s1, <?= RAW_DB ?>.spawn_<?= $s->spawn_type ?> s2 where s1.id = s2.spawn_id AND `name` = '<?= $data['name'] ?>' AND s1.id LIKE '<?= $z->zone_id ?>____';
				</span>
			</td>
		</tr>
		<tr style="background-color:#fff;">
			<td colspan="9">&nbsp;</td>
		</tr>
		<?php 
			$unique_count++;
			}
		}
		
		if( $unique_count > 0 ) 
		{ 
		?>
		<tr>
			<td colspan="9">&nbsp;<strong><?php print($unique_count) ?> Spawns, <?php print($total_count) ?> Placements found.</strong></td>
		</tr>
		<?php
		} 
		?>
	</table>
	<?php
	}
}



function PopZone() {
	global $eq2, $admin;
	
	// 2015.08.22 - trying to only show placements outside the min-dupe proximity
	$min_duplicate_radius = 0;
	
	// shorthand
	$a = $admin;
	$s = $a->spawns;
	$z = $s->zones;

	switch(strtolower($_POST['cmd'])) 
	{
		case "migrate"			:
		case "spawn these"	: $a->ProcessMigrateSpawns(); break;
		case "spawn this"		: $a->ProcessSingleSpawnLocation(); break;
		case "hide these"		: $a->HideSpawnFromMigration(2); break;
		case "unhide these"	: $a->HideSpawnFromMigration(0); break;
		case "hide this"		: $a->HideSingleSpawnLocation(2); break;
		case "unhide this"	: $a->HideSingleSpawnLocation(0); break;
		
		case "set": 
			$min_duplicate_radius = ( $_POST['min-dupe'] > 0 ) ? $_POST['min-dupe'] : 0;
			break;
	}

	// does user want to see hidden spawn records?
	if( $_GET['hidden'] > 0 )
		$showHidden = 1;
	// does user want to see only single placement spawn records?
	if( $_GET['singles'] > 0 )
		$showSingles = 1;
	
	// build the list of <options> for the Zone Picker
	$zoneOptions = $a->GetRawZoneOptions();
	
	?>
<table width="1280" cellspacing="0" cellpadding="4" border="1" bgcolor="#ccddee">
	<tr>
		<td width="300" valign="top">
			<select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:300px">
				<option>Pick a Zone</option>
				<?= $zoneOptions ?>
			</select>
		</td>
		<?php
		// now that a zone is picked, display the spawn Types
		if( $z->zone_id > 0 ) 
		{
			$link = sprintf("_admin.php?page=popzone&zone=%s&count=%s", $z->zone_id, $a->pCount);
			
			foreach($a->eq2SpawnTypes as $type)
			{
				$typeOptions .= sprintf('<option value="_admin.php?page=popzone&zone=%s&count=%s&type=%s"%s>%s</option>', 
																$z->zone_id,
																$a->pCount,
																strtolower($type),
																( $s->spawn_type == strtolower($type) ) ? " selected" : "",
																$type);
			}
		?>
		<td width="100" valign="top">
			<select name="spawnType" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:100px;">
				<option>Pick a Type</option>
				<?= $typeOptions ?>
			</select>
		</td>
		<?php 
		}
		
		// now that a type is picked, display the optional spawn filter
		if( strlen($s->spawn_type) > 0 ) 
		{
			$spawnOptions = $a->GetRawSpawnOptions();
			
			$link = sprintf("_admin.php?page=popzone&zone=%s&count=%s&type=%s", $s->zone_id, $a->pCount, $s->spawn_type);
		?>
		<td width="200" valign="top">
			<select name="spawnId" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:200px;">
				<option value="<?= $link ?>">-- NPC Filter --</option>
				<?= $spawnOptions ?>
			</select>
    </td>
    <td width="150" valign="top">
			<select name="filters" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:150px;">
				<option value="<?= $link ?>">-- Advanced Filters --</option>
        <option value="_admin.php?page=popzone&zone=<?= $_GET['zone'] ?>&count=<?= $_GET['count'] ?>&type=<?= $_GET['type'] ?>&hidden=1"<?php if( $_GET['hidden'] == 1 ) echo " selected" ?>>Show Hidden</option>
        <option value="_admin.php?page=popzone&zone=<?= $_GET['zone'] ?>&count=<?= $_GET['count'] ?>&type=<?= $_GET['type'] ?>&singles=1"<?php if( $_GET['singles'] == 1 ) echo " selected" ?>>Show Singles</option>
			</select>
    </td>
    <form method="post" name="filter-dupes">
    <td width="250"><strong>Min Dupe Radius:</strong>
    	<input type="text" name="min-dupe" value="<?= $min_duplicate_radius ?>" style="width:50px;" />
      <input type="submit" name="cmd" value="Set" class="submit" />
    </td>
    </form>
		<?php 
		}
		?>
		<td valign="top">&nbsp;<a href="_admin.php?<?= $_SERVER['QUERY_STRING'] ?>">Reload Page</a></td>
	</tr>
	<?php
	if( strlen($s->spawn_type) > 0 ) 
	{
		// displays control options for migration scripts
	?>
	<tr height="30">
		<td colspan="7"><strong>Below are a list of spawn and placement IDs that do not yet exist in the "Live" DB. Populate the live database by using the button(s) below. Carefully. :)</strong></td>
	</tr>
  <tr height="30">
    <td width="150" align="right">To pop entire (filtered) zone:</td>
    <form method="post" name="migrate-all">
    <td width="100">
      <input type="submit" name="cmd" value="Migrate" class="submit" />
      <input type="hidden" name="spawn_id" value="all" />
    </td>
    </form>
    <td colspan="4">&nbsp;</td>
  </tr>
</table>
<br />
<table cellspacing="1" cellpadding="2" border="1" style="background-color:#cde">
	<?php
		$selectColumns = sprintf("rs.data_version, s1.id, s1.name, sub_title, s1.race, s1.model_type, s1.processed as proc1, s4.processed as proc2, a.name as appearance_name");
		
		switch($s->spawn_type)
		{
			case "npcs":
				$selectColumns .= ", s2.min_level, s2.max_level, s2.enc_level, s2.class_, s2.gender";
				break;
				
			case "objects":
			case "ground":
				$selectColumns .= ", s1.visual_state";
				break;
				
			case "widgets":
			case "signs":
				$selectColumns .= ", s2.widget_id";
				break;
		}
		
		// more than 1000, force user to use name filter
		// less than 1000, normal use
		if( $_GET['count'] > 1000 )
			$filterData = ( isset($_GET['filter']) ) ? sprintf(" AND s1.name = '%s'", $eq2->SQLEscape($_GET['filter'])) : " AND s1.name = 'filter_me'"; // hack to force setting a filter before displaying anything
		else
			$filterData = ( isset($_GET['filter']) ) ? sprintf(" AND s1.name = '%s'", $eq2->SQLEscape($_GET['filter'])) : "";
		
		// if Show Only Grouped Spawns checkbox is set, run an entirely different query, order by
		if( ($showSingles) )
			$results = $a->GetSingleSpawnsToMigrate($selectColumns, $filterData);
		else
			$results = $a->GetSpawnsToMigrate($selectColumns, $filterData);
			
		if( is_array($results) )
		{
			$unique_count = 0;
			$total_count = 0;
			
			foreach($results as $data)
			{
				if( !$showHidden && ($data['proc1'] == 2 || $data['proc2'] == 2) )
					continue;
				if( $showHidden && $data['proc2'] <> 2 )
					continue;
				
				//$new_spawn_id = $admin->GetNewSpawnID($data);
				//$new_spawn_id_style = ( $new_spawn_id != $data['spawn_id'] ) ? " background-color:#ff0; border-color:#f00;" : "";
	
				if( $s->spawn_type =="npcs" )
					$colHeader = "Levels";
				else if( $s->spawn_type == "objects" || $s->spawn_type == "ground" )
					$colHeader = "Visual";
				else
					$colHeader = "WidgetID";
			?>
		<tr style="background-color:#000; color:#fff;">
			<td width="125" colspan="2">&nbsp;<strong>Spawn ID</strong></td>
			<td width="250">&nbsp;<strong>Name</strong></td>
			<td width="250"&nbsp;><strong>Sub-Title</strong></td>
			<td width="50">&nbsp;<strong>Race</strong></td>
			<td width="50">&nbsp;<strong>Model</strong></td>
			<td width="50">&nbsp;<strong><?= $colHeader ?></strong></td>
			<td width="100" align="center">&nbsp;<strong>DataVer</strong></td>
			<td width="200">&nbsp;</td>
		</tr>
		<form method="post" name="spawn|<?= $data['id'] ?>">
		<tr valign="top">
			<td colspan="2" align="right"><?= $data['id'] ?></td>
			<td><strong>
				<?= $data['name'] ?>
				</strong></td>
			<td><?= $data['sub_title'] ?></td>
			<td><?= $data['race'] ?></td>
			<td rowspan="2"><?= $data['model_type'] ?></td>
			<?php
			switch($s->spawn_type)
			{
				case "npcs":
					printf('<td>%s/%s/%s</td>', $data['min_level'], $data['max_level'], $data['enc_level']);
					break;
				
				case "objects":
				case "ground":
					printf('<td>%s</td>', $data['visual_state']);
					break;
					
				case "widgets":
				case "signs":
					printf('<td>%s</td>', $data['widget_id']);
					break;
				
			}
			?>
			<!--<td align="right"><input type="text" name="new_spawn_id" value="<?= $new_spawn_id ?>" style="width:70px;<?= $new_spawn_id_style ?>" /></td>-->
			<td align="center"><?= $data['data_version'] ?></td>
			<td nowrap align="right">
			<?php 
			if( $showHidden && $data['proc1'] == 0 ) { 
			?>
				&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Spawn These" class="submit-wide" />
				&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Hide These" class="submit-wide" />
			<?php 
				$parentHidden = 0;
			}
			elseif ( $showHidden && $data['proc1'] == 2 )
			{
			?>
				&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Spawn These" class="submit-wide" />
				&nbsp;<input type="submit" name="cmd" value="Unhide These" class="submit-wide" />
			<?php 
				$parentHidden = 1;
			}
			else 
			{
			?>
				&nbsp;<input type="submit" name="cmd" value="Spawn These" class="submit-wide" />
				&nbsp;<input type="submit" name="cmd" value="Hide These" class="submit-wide" />
			<?php
			}
			?>
			</td>
		</tr>
		<tr>
			<td colspan="5">&nbsp;</td>
			<td colspan="3"><span style="font-size:10px;"><?= $data['appearance_name'] ?></span></td>
		</tr>
		<input type="hidden" name="spawn_id" value="<?= $data['id'] ?>" />
		</form>
		<tr>
			<td colspan="2" align="right"><strong>Locations:</strong></td>
			<td colspan="7" valign="top" style="background-color:#fff">
				<table width="100%" cellspacing="1" cellpadding="2" border="1">
					<tr style="background-color:#000; color:#fff;">
						<td width="100">&nbsp;<strong>LocationID</strong></td>
						<td width="100">&nbsp;<strong>X</strong></td>
						<td width="100">&nbsp;<strong>Y</strong></td>
						<td width="100">&nbsp;<strong>Z</strong></td>
						<td width="100">&nbsp;<strong>H</strong></td>
						<td width="100">&nbsp;<strong>group_id</strong></td>
						<td>&nbsp;</td>
					</tr>
				<?php
				$placements = $s->GetSpawnPlacements($data['id'], $showHidden);
				
				if( is_array($placements) )
				{
					$spawn_count = count($placements); // per section
					
					foreach($placements as $data2)
					{
						if( !$showHidden && $data2['processed']==2 )
							continue;
						/*if( $showHidden && $data2['processed'] < 2 )
							continue;*/
						
						if( !isset($group_id_color[$data2['group_id']]) && $data2['group_id'] > 0 )
							$group_id_color[$data2['group_id']] = $eq2->SetRandomColor();
							
						$total_count++; // overall
						
						$Proximity = sprintf("popup_functions.php?page=proximity&zone=%s&x=%s&z=%s&distance=5", $z->zone_id, $data2['x'], $data2['z']);
					?>
					<form method="post" name="spawn|location_<?= $data2['spawn_location_id'] ?>">
					<tr>
						<td>&nbsp;<?= $data2['spawn_location_id'] ?></td>
						<td>&nbsp;<?= $data2['x'] ?></td>
						<td>&nbsp;<?= $data2['y'] ?></td>
						<td>&nbsp;<?= $data2['z'] ?></td>
						<td>&nbsp;<?= $data2['heading'] ?></td>
						<td<?php if( isset($group_id_color[$data2['group_id']]) ) printf(' bgcolor="#%s"', $group_id_color[$data2['group_id']]); ?>>&nbsp;<?= $data2['group_id'] ?></td>
						<td colspan="2" align="right" nowrap="nowrap">
						<?php
						/* Proximity spawning needs to be re-thought out... 
						 * Currently, migrating ALL spawns of anything close to the 1 selected
						 * Needs to only migrate spawn_location_id's close to the 1 selected
						*/
						if( $s->spawn_type=="npcs" )
							printf('&nbsp;<input type="button" value="Proximity" class="submit" onclick="javascript:window.open(\'%s\', target=\'_blank\');" disabled />', $Proximity);
							
						if( $parentHidden) { ?>
							&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Spawn This" class="submit" />
							&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Hide This" class="submit" />
						<?php } elseif( !$parentHidden && $data2['processed']==2 ) { ?>
							&nbsp;<input disabled="disabled" type="submit" name="cmd" value="Spawn This" class="submit" />
							&nbsp;<input type="submit" name="cmd" value="Unhide This" class="submit" />
						<?php } else { ?>
							&nbsp;<input type="submit" name="cmd" value="Spawn This" class="submit" />
							&nbsp;<input type="submit" name="cmd" value="Hide This" class="submit" />
						<?php } ?>
						</td>
					</tr>
					<input type="hidden" name="spawn_location_id" value="<?= $data2['spawn_location_id'] ?>" />
					<input type="hidden" name="spawn_id" value="<?= $data['id'] ?>" />
					<input type="hidden" name="group_id" value="<?= ( $data2['group_id'] > 0 ) ? $data2['group_id'] : "" ?>" />
					<input type="hidden" name="spawn_count" value="<?= $spawn_count ?>" />
					</form>
					<?php
					}
				}
				?>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="9">&nbsp;<strong><?= $spawn_count ?> Spawn Points</strong>
				<span style="font-size:10px; float:right;">
					select * from `<?= RAW_DB ?>`.spawn s1, `<?= RAW_DB ?>`.spawn_<?= $s->spawn_type ?> s2 where s1.id = s2.spawn_id AND `name` = '<?= $data['name'] ?>' AND s1.id BETWEEN <?= $z->zone_id ?>0000 AND <?= $z->zone_id ?>9999;
				</span>
			</td>
		</tr>
		<tr style="background-color:#fff;">
			<td colspan="9">&nbsp;</td>
		</tr>
		<?php 
			$unique_count++;
			}
		}
		
		if( $unique_count > 0 ) 
		{ 
		?>
		<tr>
			<td colspan="9">&nbsp;<strong><?php print($unique_count) ?> Spawns, <?php print($total_count) ?> Placements found.</strong></td>
		</tr>
		<?php
		} 
		?>
	</table>
	<?php
	}
}



function PurgeZone()
{
	global $eq2, $admin;

	// shorthand
	$z = $admin->spawns->zones;

	// Process commands
	switch($_POST['cmd'])
	{
		case "Purge Zone":
			$z->ProcessPurgeZone($_POST['zone_id']);
			break;
		
		case "Purge All":
			$z->ProcessPurgeZone(0);
			break;
	}
	
	$zoneOptions = $z->GetPopulatedZoneOptions();
	?>
	<table width="1000" cellspacing="0" border="0">
		<tr>
			<td width="300">
				<select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)" style="max-width:300px;">
					<option value="_admin.php?page=purgezone">Pick a Zone</option>
					<option value="_admin.php?page=purgezone&zone=all"<?php if( $z->zone_id == "all" ) echo " selected" ?>>--Purge ALL Zones --</option>
					<?= $zoneOptions ?>
				</select>
			</td>
			<td>&nbsp;<a href="_admin.php?<?= $_SERVER['QUERY_STRING'] ?>">Reload Page</a></td>
		</tr>
	</table>
	<br />
	<table>
		<form method="post">
	<?php
	
	if( $_GET['z']=="all" )
	{
		// are you sure?
		print('<tr><td colspan="2" align="center"><input type="submit" name="cmd" value="Purge All" style="width:70px; font-size:9px" /><input type="hidden" name="0" value="%d" /></td></tr>');
	}
	elseif( $z->zone_id > 0 )
	{
		// display items to purge
		$eq2->SQLQuery = sprintf("SELECT id, name FROM `".ACTIVE_DB."`.spawn WHERE id LIKE '%s____'", $z->zone_id);
		$results = $eq2->RunQueryMulti();
		
		if( is_array($results) )
		{
			foreach($results as $data)
			{
			?>
		<tr>
			<td><?= $data['id'] ?></td>
			<td><?= $data['name'] ?></td>
		</tr>
			<?php
			}
		}
		?>
		<tr>
			<td colspan="2" align="center">
				<input type="submit" name="cmd" value="Purge Zone" class="submit" />
				<input type="hidden" name="zone_id" value="<?= $z->zone_id ?>" />
			</td>
		</tr>
		<?php
	}
	?>
		</form>
	</table>
	<?php
}

function UserManager()
{
	global $eq2, $admin;

	?>
	<!-- Start UserSelect -->
	<div id="Editor">
	<table width="1000" cellspacing="0" border="0">
		<tr>
			<td width="75" align="right"><strong>Pick User:</strong>&nbsp;</td>
			<td class="select">
				<select name="userID" onchange="dosub(this.options[this.selectedIndex].value)" style="min-width:300px;">
					<option value="_admin.php?page=users">Pick a User</option>
					<option value="_admin.php?page=users&id=add">Add User</option>
					<?= $admin->GetUserOptions(); ?>
				</select>&nbsp;
				<a href="_admin.php?<?= $_SERVER['QUERY_STRING'] ?>">Reload Page</a>
			</td>
		</tr>
	</table>
	</div>
	<!-- End UserSelect -->
	<!--
	<script>
	//Called from keyup on the search textbox.
	//Starts the AJAX request.
	function UserSearch() {
		if (searchReq.readyState == 4 || searchReq.readyState == 0) {
			let str = escape(document.getElementById('txtSearch').value);
			let uname = document.cookie.match(/eq2db\[name\]=(\w+)/);
			let sess = document.cookie.match(/PHPSESSID=(\w+)/);
			searchReq.open("GET", `../ajax/eq2Ajax.php?type=user&search=${str}&user=${uname[1]}&session=${sess[1]}`, true);
			searchReq.onreadystatechange = handleSearchSuggest; 
			searchReq.send(null);
		}		
	}
	</script>
	<div id="SearchAll">
		<table cellspacing="0" id="SearchAll" border="0">
			<tr>
				<td width="75" align="right" valign="top"><strong>Search All:</strong>&nbsp;</td>
				<td>
					<form action="_admin.php?page=users" id="frmSearch" method="post">
						<input type="text" id="txtSearch" name="txtSearch" alt="Search Criteria" onkeyup="UserSearch();" autocomplete="off" class="box" style="width:296px;" />
						&nbsp;<input type="submit" id="cmdSearch" name="cmdSearch" value="Search" alt="Run Search" class="submit" />&nbsp;
						&nbsp;<input type="button" value="Clear" class="submit" onclick="dosub('_admin.php?page=users');" />
						&nbsp;<input type="button" value="Add" class="submit" onclick="dosub('_admin.php?page=users&id=add');" />
						<input type="hidden" name="cmd" value="UserByName" />
						<div id="search_suggest">
						</div>
					</form>
				</td>
			</tr>
		</table>
		<script>
		document.getElementById('txtSearch').focus = true;
		</script>
	</div>
	-->
	<?php
	// Check if searchText was used to find a user
	if( isset($_POST['txtSearch']) )
	{
		$arr = $admin->GetUserByName($_POST['txtSearch']);
		if( is_array($arr) )
		{
			$this->DisplayUserGrid($arr);
		}
		else
		{
			$eq2->AddStatus('No users match your search.');
		}
		$ret = true;
	}
	else if( ($_POST['cmd'] ?? "") == 'UserByName' && $_GET['id'] != "add" )
	{
		//$eq2->AddStatus('Search must contain at least 1 letter/number.');
		//$ret = true;
		$arr = $this->db->GetUserByName("all");
		if( is_array($arr) )
		{
			$this->DisplayUserGrid($arr);
		}
		else
		{
			$eq2->AddStatus('No users match your search.');
		}
		$ret = true;
	}
	// If a zone is selected, display quests associated with the zone
	else if( isset($_GET['id']) )
	{
		UserEditor();
		$ret = true;
	}
	?>
	<div id="EditorStatus">
		<?php 
		if( !empty($eq2->Status) ) $eq2->DisplayStatus(); 
		?>
	</div><!-- End EditorStatus -->
	<?php
	if ( isset( $ret ) )
		return $ret;
}

function UserEditor()
{
	global $eq2, $admin;
	
	if( !$eq2->CheckAccess(G_SUPERADMIN) )
		die("Access Denied. I'm telling that you are hacking the editor!");
	
	// Perform updates here
	if( isset($_POST['cmd']) )
	{
		// Parse Roles array and reset
		if( is_array($_POST['users|role']) )
		{
			$new_role = 0;
			foreach($_POST['users|role'] as $role)
				$new_role = $new_role + $role;
			$_POST['users|role'] = $new_role;
		}
		else
			$_POST['users|role'] = 0;
		
		
		if( empty($_POST['users|is_active']) && $_POST['orig_is_active'] )
			$_POST['users|is_active'] = 0;

		if( empty($_POST['users|reset_password']) && $_POST['orig_reset_password'] )
			$_POST['users|reset_password'] = 0;

		if (strlen($_POST['users|password'] ?? "") > 0) {
			$_POST['users|password'] = md5($_POST['users|password']);
		}

		if (strlen($_POST['password2'] ?? "") > 0) {
			$_POST['password2'] = md5($_POST['password2']);
		}

		// using $admin database processors
		switch(strtolower($_POST['cmd'])) 
		{
			case "insert": 
				$eq2->ProcessInserts(env("DB_NAME"));
				$inserted_id = $eq2->RunQuerySingle("SELECT max(`id`) as `id` FROM users")['id'];
				//Redirect to the newly inserted entry
				printf("<script>location.search='page=users&id=%s';</script>", $inserted_id);
				exit;
			case "update": 
				if ($_POST['users|password'] != $_POST['password2']) {
					$eq2->AddStatus("Mismatching passwords. Try again.");
					break;
				}
				$eq2->ProcessUpdates(env("DB_NAME")); 
				break;
			case "delete": $eq2->ProcessDeletes(env("DB_NAME")); break;
		}

	}

	// Load User Info
	if ($_GET['id'] != "add") {
		$user = $admin->GetUserInfo();
		if( !is_array($user) )
		{
			$eq2->AddStatus("No user data found.");
			return;
		}
	}
?>
	<!-- Start UserEditor -->
	<div id="Editor">
	<form method="post" name="user-form">
	<table class="SubPanel" cellspacing="0">
		<tr>
			<td class="Title" colspan="2">Editing User: <?= $user['username'] ?></td>
		</tr>
		<tr>
			<td colspan="2">
				<table width="1000" class="SectionMainFloat" cellspacing="2" border="0">
					<tr>
						<td class="SectionTitle">User Info</td>
						<td class="SectionTitle">Role Info</td>
					</tr>
					<tr>
						<td class="SectionBody" valign="top">
							<fieldset><legend>Text</legend> 
							<table cellspacing="0">
								<tr>
									<td width="200" class="Label">id:</td>
									<td>
									<?php if ($_GET['id'] == "add") : ?>
										<strong>new</strong>
									<?php else : ?>
										<input type="text" name="users|id" value="<?= $user['id'] ?>" readonly />
										<input type="hidden" name="orig_id" value="<?= $user['id'] ?>" />
									<?php endif; ?>
									</td>
								</tr>
								<tr>
									<td class="Label">Name:</td>
									<td>
										<input type="text" name="users|username" value="<?= $eq2->SQLEscape($user['username']) ?>" />
										<input type="hidden" name="orig_username" value="<?= $eq2->SQLEscape($user['username']) ?>" />
									</td>
								</tr>
								<?php if ($_GET['id'] != "add") : ?>
								<tr>
									<td class="Label">Display Name:</td>
									<td>
										<input type="text" name="users|displayname" value="<?= $eq2->SQLEscape($user['displayname']) ?>" />
										<input type="hidden" name="orig_displayname" value="<?= $eq2->SQLEscape($user['displayname']) ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">Change Password:</td>
									<td><input type="password" name="users|password" value="" /></td>
								</tr>
								<tr>
									<td class="Label">Verify Password:</td>
									<td><input type="password" name="password2" value="" /></td>
								</tr>
								<tr>
									<td class="Label">Title:</td>
									<td>
										<input type="text" name="users|title" value="<?= $eq2->SQLEscape($user['title']) ?>" />
										<input type="hidden" name="orig_title" value="<?= $eq2->SQLEscape($user['title']) ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">Default Datasource:</td>
									<td>
										<select name="users|datasource" disabled="disabled">
											<option value="-1">Not Set</option>
										<?php //$eq2->SelectDataSource($user['datasource_id']); ?>
										</select>
										<input type="hidden" name="orig_datasource" value="<?= $user['datasource_id'] ?? "" ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">Account Enabled:</td>
									<td>
										<input type="checkbox" name="users|is_active" value="1"<?php if( $user['is_active'] ) print(" checked") ?> />
										<input type="hidden" name="orig_is_active" value="<?= $user['is_active'] ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">Reset Pwd:</td>
									<td>
										<input type="checkbox" name="users|reset_password" value="1"<?php if( $user['reset_password'] ) print(" checked") ?> />
										<input type="hidden" name="orig_reset_password" value="<?= $user['reset_password'] ?>" />
									</td>
								</tr>
								<tr>
									<td class="Label">Last Login:</td>
									<td><?= ( $user['last_visited'] > 0 ) ? date("M-d-Y h:i:s", $user['last_visited']) : "Never." ?></td>
								</tr>
								<?php endif; ?>
							</table>
							</fieldset>
						</td>
						<?php if ($_GET['id'] != "add") : ?>
						<td valign="top">
							<?php $admin->DisplayRoleOptions($user); ?>
						</td>
						<?php endif; ?>
					</tr>
					<tr>
						<td colspan="3" align="center">
							<?php if ($_GET['id'] == "add") : ?>
								<input type="submit" name="cmd" value="Insert" class="submit" />&nbsp;
							<?php else : ?>
								<input type="submit" name="cmd" value="Update" class="submit" />&nbsp;
							<?php endif; ?>
							<input type="submit" name="cmd" value="Delete" class="submit" />&nbsp;
							<input type="hidden" name="table_name" value="users" />
							<input type="hidden" name="object_id" value="<?= $user['username'] ?>|<?= $user['id'] ?>" />
							<input type="hidden" name="orig_role" value="<?= $user['role'] ?>" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</form>
	</div>
	<!-- End UserEditor -->
<?php
}


function SyncRawSpells()
{
	global $eq2;
	
	if( $_POST['cmd'] == "Sync" )
		$eq2->ReSyncAllFromRawData();

	?>
	<form method="post">
		<input type="submit" name="cmd" value="Sync" />
	</form>
	<?php
}

function SOEDataSuck()
{
	global $eq2;

	?>
	<table width="1000">
		<tr>
			<td valign="top">
				<fieldset><legend>Compare SOE Data</legend>
				<table border="0">
					<tr>
						<td colspan="2"><p>This will compare known SOE data with the data we have in our tables, and attempt to expose missing data in our collections.</p></td>
					</tr>
					<tr>
						<td><strong>Pick a Collection:</strong>&nbsp;
							<?php $eq2->SOECollectionSelector() ?>
						</td>
						<?php if( isset($_GET['collection']) ) { ?>
						<td><strong>Pick a Type:</strong>&nbsp;
							<?php $eq2->SOECollectionTypeSelector() ?>
						</td>
						<?php } ?>
					</tr>
					<form method="post" name="getSOEData">
					<tr>
						<td colspan="2">
							<strong>Count:</strong>
							&nbsp;<a href="http://census.daybreakgames.com/xml/get/eq2/<?= $_GET['collection'] ?>/?type=<?= $_GET['type'] ?>&c:limit=100&c:sort=name" target="_blank"><?= $eq2->GetCollectionCount($_GET['collection'], $_GET['type']); ?></a>
							&nbsp;<input type="submit" name="cmd" value="Collect" style="width:90px; font-size:9px;" />
							&nbsp;<a href="?<?= $_SERVER['QUERY_STRING'] ?>" target="_self">Reload Page</a>
						</td>
					</tr>
					</form>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	<br />
	<?php
}

function SOEDataCompare()
{
	global $eq2;

	?>
	<table width="1000">
		<tr>
			<td valign="top">
				<fieldset><legend>Compare SOE Data</legend>
				<table border="0">
					<tr>
						<td colspan="2"><p>This will compare known SOE data with the data we have in our tables, and attempt to expose missing data in our collections.</p></td>
					</tr>
					<tr>
						<td><strong>Pick a Collection:</strong>&nbsp;
							<?php $eq2->SOECollectionSelector() ?>
						</td>
						<?php if( isset($_GET['collection']) ) { ?>
						<td><strong>Pick a Type:</strong>&nbsp;
							<?php $eq2->SOECollectionTypeSelector() ?>
						</td>
						<?php } ?>
					</tr>
				</table>
				</fieldset>
			</td>
		</tr>
	</table>
	<br />
	<?php
}


function Pets()
{
	global $eq2;
	
	// TODO - seems this isn't finished either
	
	$Pets = $eq2->GetPets();
	if( !is_array($Pets) )
		DIE("No Pets data!");
	?>
	<fieldset><legend>Convert NPCs to Pets</legend>
	<table>
		<tr>
			<td>Filter: </td>
			<td>
				<select onchange="dosub(this.options[this.selectedIndex].value)" style="width:150px;">
					<option value="?p=pets">Type</option>
					<?php
					foreach($Pets as $pet)
					{
						if( strlen($pet['sub_title']) < 1 )
							continue;
							
						$start = strpos($pet['sub_title'], '\'')+3;
						if( $start > 0 )
						{
							$type = substr( $pet['sub_title'], $start );
							$unique_types[$type] = 1;
						}
					}
					print_r($unique_types);
					
					if( is_array($unique_types) )
					{
						$i = 1;
						foreach($unique_types as $key=>$val)
							printf('<option value="?p=pets&t=%s"%s>%s</option>', $key, $spawns->spawn_type==$key ? " selected" : "", $key);
					}
					?>
				</select>
			</td>
			<td>
				<select onchange="dosub(this.options[this.selectedIndex].value)" style="width:150px;">
					<option value="">Class</option>
					<option value="?p=pets&t=<?= $spawns->spawn_type ?>&c=1">Conjuror</option>
				</select>
			</td>
			<td>
				<select>
					<option>Model</option>
				</select>
			</td>
			<td>
				<select>
					<option>Level</option>
				</select>
			</td>
		</tr>
	</table>
	<table cellspacing="0" cellpadding="4">
		<tr bgcolor="#eeeeee">
			<th width="10">id</th>
			<th width="200">name</th>
			<th width="300">sub_title</th>
			<th width="50">model_type</th>
			<th width="200">appearance</th>
			<th width="50">Level</th>
			<th width="50">version</th>
		</tr>
		<?php
		if( is_array($Pets) )
		{
			foreach($Pets as $data)
			{
			?>
				<tr>
					<td><?= $data['id'] ?></td>
					<td nowrap="nowrap"><?= $data['spawn_name'] ?></td>
					<td nowrap="nowrap"><?= $data['sub_title'] ?></td>
					<td><?= $data['model_type'] ?></td>
					<td nowrap="nowrap"><?= substr($data['appearance'], strrpos($data['appearance'], "/")+1) ?></td>
					<td align="center" nowrap="nowrap"><?= $data['min_level'] ?> / <?= $data['max_level'] ?> / <?= $data['difficulty'] ?></td>
					<td align="right"><?= $data['version'] ?></td>
				</tr>
			<?php
				$row_count++;
			}
		?>
		<tr bgcolor="#eeeeee">
			<td colspan="7">Records Found: <?= $row_count ?></td>
		</tr>
		<?php
		}
		?>
	</table>
	</fieldset>
	<?php
}

function CleanRawData()
{
	global $eq2;
	
	// first, get all raw_zone data
	$sql = "SELECT id, zone_file, zone_desc FROM `".PARSER_DB."`.raw_zones ORDER BY zone_file, zone_desc";
	if( !$result = $eq2->db->sql_query($sql) )
	{
		$error = $eq2->db->sql_error();
		$message = sprintf('<p align="center"><b>Error Code: %s<br />Error Message: %s<br />%s</p>', $error['code'], $error['message'], $sql);
		die($message);
	}
	while( $row = $eq2->db->sql_fetchrow($result) )
		$raw_zone_data[$row['id']] = array($row['zone_file'], $row['zone_desc']);

	// then, get all raw zone data that is a duplicate within raw_zones
	$sql = "SELECT id, zone_file, zone_desc FROM `".PARSER_DB."`.raw_zones WHERE zone_desc NOT IN (SELECT description FROM `".RAW_DB."`.zones) GROUP BY zone_file HAVING COUNT(zone_file) > 1 ORDER BY zone_file, zone_desc";
	if( !$result = $eq2->db->sql_query($sql) )
	{
		$error = $eq2->db->sql_error();
		$message = sprintf('<p align="center"><b>Error Code: %s<br />Error Message: %s<br />%s</p>', $error['code'], $error['message'], $sql);
		die($message);
	}
	while( $row = $eq2->db->sql_fetchrow($result) )
		$dupe_zone_data[$row['id']] = array($row['zone_file'], $row['zone_desc']);

	// then, get all raw zone data that does not exist in destination DB
	$sql = "SELECT rz.* FROM `".PARSER_DB."`.raw_zones rz LEFT JOIN `".RAW_DB."`.zones z ON rz.zone_desc = z.description AND rz.zone_file = z.file WHERE z.description IS NULL ORDER BY zone_file, zone_desc";
	if( !$result = $eq2->db->sql_query($sql) )
	{
		$error = $eq2->db->sql_error();
		$message = sprintf('<p align="center"><b>Error Code: %s<br />Error Message: %s<br />%s</p>', $error['code'], $error['message'], $sql);
		die($message);
	}
	while( $row = $eq2->db->sql_fetchrow($result) )
		$bad_zone_data[$row['id']] = array($row['zone_file'], $row['zone_desc']);
	
	// funally, get all zones from desintation DB
	$sql = "SELECT id, file, description FROM `".RAW_DB."`.zones";
	if( !$result = $eq2->db->sql_query($sql) )
	{
		$error = $eq2->db->sql_error();
		$message = sprintf('<p align="center"><b>Error Code: %s<br />Error Message: %s<br />%s</p>', $error['code'], $error['message'], $sql);
		die($message);
	}
	while( $row = $eq2->db->sql_fetchrow($result) )
		$live_zone_data[$row['id']] = array($row['file'], $row['description']);
	
	if( is_array($bad_zone_data) )
	{
		?>
		<select name="from_raw" size="20" multiple="multiple">
		<?php
		foreach($dupe_zone_data as $key=>$val)
			printf('<option value="%s">%s (%s)</option>', $key, $val[0], $val[1]);
		?>
		</select>
		<?php
	}
	else
	{
		print("All Clean!");
	}
	
	//printf("%s<br />", $row['zone_desc']);
}


function adhoc()
{
	global $eq2;

	?>
<form method="post">
<table>
	<tr>
		<td><strong>Adhoc SQL Reporting:</strong> Choose tables to query and set criteria below.</td>
	</tr>
	<tr>
		<td valign="top">
		<fieldset><legend>Required</legend>
			<table>
				<tr>
					<td>Type:</td>
					<td>
						<select name="querytype">
							<option>SELECT</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Data:</td>
					<td><input type="text" name="queryselect" value="<?= isset($_POST['queryselect']) ? $_POST['queryselect'] : "*" ?>" size="100" /></td>
				</tr>
				<tr>
					<td colspan="2"><strong>FROM</strong></td>
				</tr>
				<tr>
					<td>Table:</td>
					<td>
					<?php
					$eq2->SQLQuery = "SHOW TABLES FROM " . ACTIVE_DB;
					$results = $eq2->RunQueryMulti();
					?>
						<select name="querytable">
							<?php
							foreach($results as $row)
								printf("<option%s>%s</option>", ( $row["Tables_in_".ACTIVE_DB] == ($_POST['querytable'] ?? "") ) ? " selected" : "", $row["Tables_in_".ACTIVE_DB]);
							?>
						</select>
					</td>
			</table>
		</fieldset>
		<br />
		<fieldset><legend>Optional</legend>
			<table>
				<tr>
					<td><strong>WHERE</strong></td>
					<td><input type="text" name="querywhere" value="<?= $_POST['querywhere'] ?>" size="100" />&nbsp;{field} [= &lt; &gt; RLIKE] {value}</td>
				</tr>
				<tr>
					<td><strong>ORDER BY:</strong></td>
					<td><input type="text" name="queryorder" value="<?= $_POST['queryorder'] ?>" size="50" />&nbsp;{field} {ASC|DESC}</td>
				</tr>
				<tr>
					<td><strong>LIMIT:</strong></td>
					<td><input type="text" name="querylimit" value="<?= $_POST['querylimit'] ?>" size="5" />&nbsp;max rows to return (max 100)</td>
				</tr>
			</table>
		</fieldset>
	</tr>
	<tr>
		<td><input type="submit" name="cmd" value="Run Query" style="font-size:10px; width:100px;" />&nbsp;<input type="button" value="Clear" style="font-size:10px; width:100px;" onclick="dosub('_admin.php?p=sql');" /></td>
	</tr>
</table>
</form>
<?php	
	if( isset($_POST['cmd']) )
	{
		$query = sprintf("SELECT %s FROM `".ACTIVE_DB."`.%s", $_POST['queryselect'], $_POST['querytable']);

		if( !empty( $_POST['querywhere'] ) )
			$query .= " WHERE ".$_POST['querywhere'];
			
		if( !empty( $_POST['queryorder'] ) )
			$query .= " ORDER BY ".$_POST['queryorder'];
			
		if( !empty( $_POST['querylimit'] ) )
			$query .= " LIMIT 0,".$_POST['querylimit'];
		else
			$query .= " LIMIT 0,100";
	
		printf("<strong>Query:</strong><br />%s<br />", stripslashes($query));
		
		$eq2->SQLQuery = $query;
		$results = $eq2->RunQueryMulti();
		
		if( is_array($results) )
		{
			foreach($results as $data)
			{
				print_r($data);
				//printf("%s<br />", implode("\t", $data));
			}
		}
	}

}

function Locations()
{
	global $eq2;

	$sql = sprintf("SELECT DISTINCT log_file FROM %s.raw_locations ORDER BY log_file", PARSER_DB);
	if( !$result=$eq2->db->sql_query($sql) )
	{
		$eq2->sqlError($sql);
	}
	
	while( $data = $eq2->db->sql_fetchrow($result) )
	{
		$selected = ( $data['log_file'] == $_GET['log'] ) ? " selected" : "";
		$logOptions .= sprintf("<option value=\"_admin.php?p=location&log=%s\"%s>%s</option>\n", 
			$data['log_file'], $selected, $data['log_file']);
	}

	?>
<table>
	<tr>
		<td valign="top" height="30"><select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)">
				<option>Pick a Log</option>
				<?= $logOptions ?>
			</select>
			&nbsp; </td>
	</tr>
</table>
<?php
	if( isset($_GET['log']) )
	{
	?>
<table>
	<tr>
		<th>POI Name</th>
	</tr>
	<?php
	$sql = sprintf("SELECT DISTINCT poi_name FROM %s.raw_locations WHERE log_file = '%s'", PARSER_DB, $_GET['log']);
	if( !$result=$eq2->db->sql_query($sql) )
	{
		$eq2->sqlError($sql);
	}
	
	while( $data = $eq2->db->sql_fetchrow($result) )
	{
		printf("<tr><td>%s</td></tr>\n", $data['poi_name']);
	}
	?>
</table>
<hr />
<br />
These locations do not necessarily line up with the above listed POI's - just
showing the raw locations by log:<br />
<br />
<strong>Instructions:</strong> Take one of the below x,y,z coords, go in-game,
/move to it, and best-guess what POI the coordinates belong to :(<br />
<br />
<table width="300" border="1">
	<tr>
		<th>#</th>
		<th>x</th>
		<th>y</th>
		<th>z</th>
	</tr>
	<?php
	$sql = sprintf("SELECT * FROM %s.raw_poi_locations WHERE log_file = '%s'", PARSER_DB, $_GET['log']);
	if( !$result=$eq2->db->sql_query($sql) )
	{
		$eq2->sqlError($sql);
	}
	
	while( $data = $eq2->db->sql_fetchrow($result) )
	{
		if( $currNumLocs != $data['num_locations'] )
		{
			$currNumLocs = $data['num_locations'];
			printf("<tr><td colspan=\"4\">Number of locations in this set: %s</td></tr>\n", $data['num_locations']);
			$i = 1;
		}
		printf("<tr align=\"right\"><td>%s.</td><td>%s</td><td>%s</td><td>%s</td></tr>\n", $i, $data['x'], $data['y'], $data['z']);
		$i++;
	}
	?>
</table>
<?php
	}
}


function getChangeLogs() {
	print("Check each systems 'changelog' links for now. I will eventually compile them all here by author.");
}


function getLogs() {
	global $eq2;

	if( !defined("LOG_PATH") || !defined("LOGIN_FOLDER") || !defined("PATCH_FOLDER") || !defined("WORLD_FOLDER") )
		die("All PATH constants not set in config.php");
	?>
<p>Session logs are kept for the sole purpose of updating the DB Update Server
	with relative ease. Each edit is logged to your session file and stored.<br>
	You can choose to save/download your own log, clean it up and submit it for
	update to the live server, or just wait til it gets processed automatically
	(TBD)</p>
<p>Each log is stored as &quot;session_username_week##.txt&quot; where ## is
	the week number of the year. If the logs get too large, we can make them unique
	by day.</p>
<p>Along with logging to a flat file for updating, edits are also logged to the
	`dbeditor_log` table of the
	<?= ACTIVE_DB ?>
	database. This is for reporting. We'll get to that later.</p>
<p><a href="../logs">Show DBEditor Logs</a></p>
<p align="center">
<hr />
<strong>System Logs:</strong>
<select name="type"  onchange="dosub(this.options[this.selectedIndex].value)">
	<option value="">Pick Log Type</option>
	<option value="_admin.php?page=logs&type=<?= LOGIN_FOLDER?>"<?php if ($_GET['type'] == LOGIN_FOLDER) echo " selected" ?>>LoginServer</option>
	<option value="_admin.php?page=logs&type=<?= PATCH_FOLDER?>"<?php if ($_GET['type'] == PATCH_FOLDER) echo " selected" ?>>PatchServer</option>
	<option value="_admin.php?page=logs&type=<?= WORLD_FOLDER?>"<?php if ($_GET['type'] == WORLD_FOLDER) echo " selected" ?>>WorldServer</option>
</select>
<?php
	if( isset($_GET['type']) )
	{
		$logs = $eq2->loglist($_GET['type']);
		if( !empty($logs) )
		{
			print('&nbsp;<strong>Logs:</strong><select name="log" onchange="dosub(this.options[this.selectedIndex].value)">');
			print('<option value="">Pick Log Date</option>');
			print($logs);
			print('</select>');
		}
	}
	?>
<br />
&nbsp;
<?php
	if( isset($_GET['log']) )
	{
	?>
<fieldset style="width:940px; text-align:center; margin-left:30px;">
	<legend>Server Log (Last 100 lines)</legend>
	<p>
		<button onClick="getLog('start');">Start Log</button>
		<button onClick="stopTail();">Stop Log</button>
	<div id="log" style="border:solid 1px #dddddd; margin-left:25px; font-size:9px; padding-left:5px; padding-right:10px; padding-top:10px; padding-bottom:20px; margin-top:10px; margin-bottom:10px; width:90%; text-align:left; max-height:300px; overflow:auto;"> This
		is the Log Viewer. To begin viewing the log live in this window, click Start
		Viewer. To stop the window refreshes, click Pause Viewer. </div>
	</p>
	<p></p>
</fieldset>
</p>
<?php
	}
	exit;
	?>
<select name="logtime" onchange="dosub(this.options[this.selectedIndex].value)">
	<option value="_admin.php?page=logs">---</option>
	<option value="_admin.php?page=logs&time=7200"<?php if( $_GET['time'] == 7200 ) print(" selected") ?>>2
	hours</option>
	<option value="_admin.php?page=logs&time=21600"<?php if( $_GET['time'] == 21600 ) print(" selected") ?>>6
	hours</option>
	<option value="_admin.php?page=logs&time=43200"<?php if( $_GET['time'] == 43200 ) print(" selected") ?>>12
	hours</option>
	<option value="_admin.php?page=logs&time=86400"<?php if( $_GET['time'] == 86400 ) print(" selected") ?>>1
	day</option>
	<option value="_admin.php?page=logs&time=604800"<?php if( $_GET['time'] == 604800 ) print(" selected") ?>>1
	week</option>
</select>
Extended Log View
<?php	
}


function getScripts() {
	global $eq2;
	?>
<table width="100%">
	<tr>
		<td valign="top">Get a quick overview of the scripts that are assigned or orphaned
			on the server. <br />
			<strong>Note:</strong> To add, edit or delete scripts, use the appropriate
			Scripts editor.<br />
			&nbsp;
			<fieldset>
				<legend>Spawn Scripts</legend>
				<table width="100%" cellpadding="2" cellspacing="0" border="0">
					<tr>
						<th width="40%">In Database</th>
						<th width="50%">On Server</th>
						<th>&nbsp;</th>
					</tr>
					<?php
				$query="select distinct lua_script from " . LIVE_DB . ".spawn_scripts order by lua_script;";
				$result=$eq2->db->sql_query($query);
				$i=0;
				while($data=$eq2->db->sql_fetchrow($result)) {
					$rowStyle=( $i % 2 ) ? " bgcolor=\"#ffffff\"" : " bgcolor=\"#dddddd\"";
					?>
					<tr<?= $rowStyle ?>>
						<td><?= $data['lua_script'] ?></td>
						<td><?= $eq2->CheckScriptExists($data['lua_script']) ?></td>
						<td><!--<input type="button" value="Edit" style="width:70px; font-size:9px" onclick="javascript:window.open('scripts.php?t=spawn_scripts&', target='_self')" />-->
							&nbsp;</td>
					</tr>
					<?php
					$i++;
				}
				?>
					<tr>
						<td>&nbsp;</td>
						<td height="30" valign="bottom"><strong>Orphaned Files</strong></td>
						<td>&nbsp;</td>
					</tr>
					<?php $eq2->getOrphanedScripts("SpawnScripts") ?>
				</table>
			</fieldset></td>
	</tr>
	<tr>
		<td width="100%" valign="top"><br />
			<fieldset>
				<legend>Quest Scripts</legend>
				<table width="100%" cellpadding="2" cellspacing="0">
					<tr>
						<th width="40%">In Database</th>
						<th width="50%">On Server</th>
						<th>&nbsp;</th>
					</tr>
					<?php
				$query="select distinct lua_script from quests order by lua_script;";
				$result=$eq2->db->sql_query($query);
				$i=0;
				while($data=$eq2->db->sql_fetchrow($result)) {
					$rowStyle=( $i % 2 ) ? " bgcolor=\"#ffffff\"" : " bgcolor=\"#dddddd\"";
					?>
					<tr<?= $rowStyle ?>>
						<td><?= $data['lua_script'] ?></td>
						<td><?= $eq2->CheckScriptExists($data['lua_script']) ?></td>
						<td><!--<input type="button" value="Edit" style="width:70px; font-size:9px" onclick="javascript:window.open('scripts.php?t=spawn_scripts&', target='_self')" />--></td>
					</tr>
					<?php
					$i++;
				}
				?>
					<tr>
						<td>&nbsp;</td>
						<td height="30" valign="bottom"><strong>Orphaned Files</strong></td>
						<td>&nbsp;</td>
					</tr>
					<?php $eq2->getOrphanedScripts("Quests") ?>
				</table>
			</fieldset></td>
	</tr>
	<tr>
		<td valign="top"><br />
			<fieldset>
				<legend>Spell Scripts</legend>
				<table width="100%" cellpadding="2" cellspacing="0">
					<tr>
						<th width="40%">In Database</th>
						<th width="50%">On Server</th>
						<th>&nbsp;</th>
					</tr>
					<?php
				$query="select distinct lua_script from " . LIVE_DB . ".spells where lua_script IS NOT NULL order by lua_script;";
				$result=$eq2->db->sql_query($query);
				$i=0;
				while($data=$eq2->db->sql_fetchrow($result)) {
					$rowStyle=( $i % 2 ) ? " bgcolor=\"#ffffff\"" : " bgcolor=\"#dddddd\"";
					?>
					<tr<?= $rowStyle ?>>
						<td><?= $data['lua_script'] ?></td>
						<td><?= $eq2->CheckScriptExists("Spells/".$data['lua_script']) ?></td>
						<td><!--<input type="button" value="Edit" style="width:70px; font-size:9px" onclick="javascript:window.open('scripts.php?t=spawn_scripts&', target='_self')" />--></td>
					</tr>
					<?php
					$i++;
				}
				?>
					<tr>
						<td>&nbsp;</td>
						<td height="30" valign="bottom"><strong>Orphaned Files</strong></td>
						<td>&nbsp;</td>
					</tr>
					<?php $eq2->getOrphanedScripts("Spells") ?>
				</table>
			</fieldset></td>
	</tr>
</table>
<?php
}




function popSpells() 
{
	global $eq2;

	switch($_POST['cmd'])
	{
		case "Insert"			: $eq2->processMigrateSingleSpell($_POST['orig_spell_id'], $_POST['new_spell_id']); break;
		case "Insert All"	: $eq2->processMigrateAllClassSpells(); break;
		case "Hide"				: $eq2->processHideSingleSpell(); break;
	}
/*
?>

<script language="javascript">
<!--
//Called from keyup on the search textbox.
//Starts the AJAX request.
function SpellClassesLookupAJAX() {
	if (searchReq.readyState == 4 || searchReq.readyState == 0) {
		var str = escape(document.getElementById('txtSearch').value);
		searchReq.open("GET", '../ajax/eq2Ajax.php?type=luSC&search=' + str, true);
		searchReq.onreadystatechange = handleSearchSuggest; 
		searchReq.send(null);
	}		
}

function SpellLookupAJAX() {
	if (searchReq.readyState == 4 || searchReq.readyState == 0) {
		var str = escape(document.getElementById('txtSearch').value);
		searchReq.open("GET", '../ajax/eq2Ajax.php?type=luS&search=' + str, true);
		searchReq.onreadystatechange = handleSearchSuggest; 
		searchReq.send(null);
	}		
}
-->
</script>
<?php
*/
?>
<fieldset>
<legend>Update Spell Data</legend>
<table width="100%" cellpadding="4" border="0">
	<?php
	/*<tr>
		<td class="LabelRight"><strong>Lookup Class/Type:</strong>&nbsp;
			<form action="_admin.php?p=popspells" id="frmSearch" method="post">
				<input type="text" id="txtSearch" name="txtSearch" alt="Search Criteria" onkeyup="SpellClassesLookupAJAX();" autocomplete="off" class="box" value="<?= $_POST['txtSearch'] ?>" onclick="this.value='';" />
				<input type="button" value="Search" class="submit" onclick="dosub('_admin.php?p=popspells&class=' + document.getElementById('txtSearch').value);" />
				<input type="button" value="Clear" class="submit" onclick="dosub('_admin.php?p=popspells');" />
				<input type="hidden" name="cmd" value="SpellClassByName" />
				<div id="search_suggest">
				</div>
			</form>
			<script language="JavaScript">
			<!--
			document.getElementById('txtSearch').focus = true;
			//-->
			</script>
		</td>
	</tr>*/
	//Above is commented out to be used to refactor Spell editor lookups - need to figure out how to do arrays in ajax search 
	?>
	<tr>
		<td>Choose <strong><u>one</u></strong> of the following filters
			<?php if ( $_GET['class']==99 ) print("( unless Racials* )") ?>
			:&nbsp;
			<select name="class" onchange="dosub(this.options[this.selectedIndex].value)" style="width:150px;">
				<option value="_admin.php?p=popspells">---</option>
				<option value="_admin.php?p=popspells&class=100"<?php if( $_GET['class']==100 ) print(" selected") ?>>Traditions</option>
				<option value="_admin.php?p=popspells&class=50"<?php if( $_GET['class']==50 ) print(" selected") ?>>Traits</option>
				<?php
					foreach($eq2->eq2Classes as $key=>$val) {
						$selected = ( !empty($_GET['class']) && $_GET['class'] == $key ) ? " selected" : "";
						printf("<option value=\"_admin.php?p=popspells&class=%d\"%s>%s</option>", $key, $selected, ucfirst(strtolower($val)));
					}
					?>
			</select>
			&nbsp;
			<?php 
			if( $_GET['class'] == 100 ) 
			{ 
			?>
				<select name="race" onchange="dosub(this.options[this.selectedIndex].value)" style="width:150px;">
					<option value="_admin.php?p=popspells&class=99"<?php if( empty($_GET['race']) ) print(" selected") ?>>---</option>
				<?php
					foreach($eq2->eq2Races as $key=>$val) {
						if( !empty($_GET['race']) ) 
							$selected = ( $_GET['race'] == $val ) ? " selected" : "";
						printf("<option value=\"_admin.php?p=popspells&class=99&race=%s\"%s>%s</option>", $val, $selected, ucfirst(strtolower($val)));
					}
					?>
				</select>
			<?php
			}
			elseif( $_GET['class'] > 50 )
			{
			?>
			<select name="skill" onchange="dosub(this.options[this.selectedIndex].value)" style="width:150px;">
				<option value="0">---</option>
				<?php
						$query="select id,name from skills order by name";
						//$query="select * from skills where description rlike 'spells'";
						$result=$eq2->db->sql_query($query);
						while($data=$eq2->db->sql_fetchrow($result)) {
							$selected=( $_GET['skill'] == $data['id'] ) ? " selected" : "";
							printf("<option value=\"_admin.php?p=popspells&skill=%s\"%s>%s</option>\n", $data['id'], $selected, $data['name']);
						}
					?>
			</select>
			<?php
			}
			?>
			&nbsp;&nbsp;<a href="_admin.php?<?= $_SERVER['QUERY_STRING'] ?>">Reload
			Page</a></td>
	</tr>
	<?php if ( $_GET['class']==100 ) { ?>
	<tr>
		<td>Racials will offer a second choice to attempt to narrow the search, but
			is very inaccurate. Check the full Racials list when done to ensure all were
			migrated.</td>
	</tr>
	<?php 
	} 
	
	if( isset($_GET['class']) || isset($_GET['skill']) ) 
	{ 
		if( isset($_GET['class']) ) 
		{
			if( $_GET['class'] == 50 ) // Show Tradeskills
			{
				$query = "SELECT DISTINCT s1.spell_id, s1.name, s1.description, adventure_class_id, tradeskill_class_id, s4.name as class, s5.name as mastery, round(spell_level / 10) as spell_level, s1.tier
									FROM `".PARSER_DB."`.raw_spells s1 
									LEFT JOIN `".PARSER_DB."`.raw_spell_levels s2 ON s1.spell_id = s2.spell_id
									LEFT JOIN `".ACTIVE_DB."`.spells s3 ON s1.name = s3.name 
									LEFT JOIN `".ACTIVE_DB."`.skills s4 on s1.class_skill = s4.id
									LEFT JOIN `".ACTIVE_DB."`.skills s5 on s1.mastery_skill = s5.id
									WHERE 
										s3.id IS NULL AND 
										s1.processed = 0 AND 
										tradeskill_class_id = 1 
									ORDER BY 
										s1.class_skill, 
										s1.mastery_skill, 
										spell_level, 
										s1.name;";
			}
			else if( $_GET['class'] == 100 ) // Show Traditions
			{
				$query = sprintf("SELECT DISTINCT s1.spell_id, s1.name, s1.description, s4.name as class, s5.name as mastery, s1.tier
													FROM `".PARSER_DB."`.raw_spells s1 
													JOIN `".PARSER_DB."`.raw_traditions s2 ON s1.spell_id = s2.tradition_id
													LEFT JOIN `".ACTIVE_DB."`.spells s3 ON s1.name = s3.name 
													LEFT JOIN `".ACTIVE_DB."`.skills s4 on s1.class_skill = s4.id
													LEFT JOIN `".ACTIVE_DB."`.skills s5 on s1.mastery_skill = s5.id
													WHERE 
														s3.id IS NULL AND 
														s1.processed = 0
														%s 
													ORDER BY 
														s1.name;", ( isset($_GET['race']) ) ? sprintf(" AND s1.description rlike '%s'", substr($_GET['race'],0,4)) : "");
			}
			else // Normal Spells
			{
				if( $_GET['class'] == 255 )
					die("Cannot select ALL classes when popping spells");
				
				$query = sprintf("SELECT s1.spell_id, s1.name, s1.description, s4.name as class, s5.name as mastery, round(spell_level / 10) as spell_level
									FROM `".PARSER_DB."`.raw_spells s1 
									LEFT JOIN `".PARSER_DB."`.raw_spell_levels s2 ON s1.spell_id = s2.spell_id
									LEFT JOIN `".ACTIVE_DB."`.skills s4 on s1.class_skill = s4.id
									LEFT JOIN `".ACTIVE_DB."`.skills s5 on s1.mastery_skill = s5.id
									LEFT JOIN `".ACTIVE_DB."`.spells s3 ON s1.name = s3.name 
									WHERE 
										s3.id IS NULL
										AND s1.processed = 0
										AND LENGTH(s1.name) > 0
										AND s4.name = '%s'
									GROUP BY s1.spell_id
									ORDER BY
										s2.spell_level, 
										s1.name,
										spell_level;", $eq2->eq2Classes[$_GET['class']]);
			}
		} // end $_GET['class']
		else if( isset($_GET['skill']) ) // By Skill
		{
			$query = sprintf("SELECT DISTINCT s1.spell_id, s1.name, s1.description, adventure_class_id, tradeskill_class_id, s4.name as class, s5.name as mastery, round(spell_level / 10) as spell_level, s1.tier
												FROM `".PARSER_DB."`.raw_spells s1 
												LEFT JOIN `".PARSER_DB."`.raw_spell_levels s2 ON s1.spell_id = s2.spell_id
												LEFT JOIN `".ACTIVE_DB."`.spells s3 ON s1.name = s3.name 
												LEFT JOIN `".ACTIVE_DB."`.skills s4 on s1.class_skill = s4.id
												LEFT JOIN `".ACTIVE_DB."`.skills s5 on s1.mastery_skill = s5.id
												WHERE 
													s3.id IS NULL AND 
													s1.processed = 0 AND 
													(s1.class_skill = %s OR s1.mastery_skill = %s)
													ORDER BY 
														spell_level, 
														s1.name;", $_GET['skill'], $_GET['skill']);
		}
		
		if( !$result=$eq2->db->sql_query($query) ) 
			$eq2->SQLError($query);
		
		while($data = $eq2->db->sql_fetchrow($result))
		{
			$spell_data[] = $data;
			$migrate_spell_ids .= sprintf('<input type="hidden" name="migrate[]" value="%s" />', $data['spell_id']);
		}
		?>
		<tr>
			<td valign="top"><fieldset>
					<legend>Spells Not In Live DB</legend>
					<table width="100%" cellpadding="4" cellspacing="0" border="0">
						<form method="post" id="move_all">
						<tr bgcolor="#cccccc">
							<td width="100"><strong>SOE Data</strong></td>
							<td><strong>Wikia</strong></td>
							<td><strong>Description</strong></td>
							<td width="200"><strong>Skill/Mastery</strong></td>
							<td width="50"><strong>Level</strong></td>
							<td><strong>New_ID</strong></td>
							<td width="120"><input type="submit" name="cmd" value="Insert All" style="font-size:9px;" /></td>
							<?= $migrate_spell_ids ?>
						</tr>
						</form>
						<?php
						$new_spell_id = 0;
						$i = 0;
						if( is_array($spell_data) )
						{
							foreach($spell_data as $data)
							{
								// too slow, and hits it every time the grid loads... boo. but, it worked. moved it into the Insert single script.
								//if( !$eq2->CheckSOESpellExists($data['spell_id']) )
								//	continue;
								
								if( $new_spell_id == 0 || $current_class != $_GET['class'] )
								{
									$current_class = $_GET['class'];
									$new_spell_id = $eq2->GetNextIDX("spells", $current_class);
								}
								else
									$new_spell_id++;
								
								$row_class = ( $i % 2 ) ? " bgcolor=\"#dddddd\"" : "";
								$description = ( strlen($data['description']) > 90 ) ? substr($data['description'],0,90).'...' : $data['description'];
							?>
							<form method="post" name="spells|<?php print($data['id']) ?>">
								<tr<?php print($row_class) ?>>
									<td><a href="http://census.daybreakgames.com/xml/get/eq2/spell/?crc=<?= $data['spell_id'] ?>&c:limit=100&c:sort=tier" target="_blank"><?= $data['spell_id'] ?></a></td>
									<td nowrap><a href="http://eq2.wikia.com/wiki/<?= $data['name'] ?>" target="_blank"><?= $data['name'] ?></a>
										<?php //= ( CountSpellClasses($data['spell_id']) ) ? "*" : "" ?></td>
									<td nowrap><?php print($description) ?></td>
									<td nowrap><?php printf("%s / %s", $data['class'], $data['mastery']) ?></td>
									<td><?= ( $data['spell_level'] ) ? $data['spell_level'] : "AA" ?></td>
									<td><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
										<input type="text" name="new_spell_id" value="<?= $new_spell_id; //( isset($_POST['new_spell_id']) ) ? ++$_POST['new_spell_id'] : $new_spell_id ?>" style="width:40px; font-size:9px;" />
										<?php } ?></td>
									<td align="right"><?php if( $eq2->CheckAccess(G_DEVELOPER) ) { ?>
										<input type="submit" name="cmd" value="Insert" style="width:50px; font-size:9px;" />
										<input type="submit" name="cmd" value="Hide" style="width:50px; font-size:9px;" />
										<input type="hidden" name="orig_spell_id" value="<?php print($data['spell_id']) ?>" />
										<?php } ?></td>
								</tr>
							</form>
							<?php
								$i++;
							}
						}
						?>
					</table>
				</fieldset></td>
		</tr>
		<tr>
			<td><?= $i ?>
				rows found...</td>
		</tr>
		<tr>
			<td>
				<table width="100%" border="0"><!-- extra stuff -->
					<tr>
						<td>
						<?php 
						if( $eq2->total_time > 0 )
							printf('SOE Time: %.2f seconds, Our Time: %.2f seconds, Total time: %.2f seconds...', $eq2->soe_time, $eq2->our_time, $eq2->total_time);
						?>
						</td>
						<form method="post" name="manualFetch">
						<td align="right" width="50%">This will attempt to parse a spell we do not have from SOE Data -- <strong>CRC</strong>:&nbsp;
							<input type="text" name="orig_spell_id" value="" style="background-color:#FF9; width:100px; font-size:11px;" />&nbsp;
							<input type="text" name="new_spell_id" value="<?= $eq2->GetNextIDX("spells", $_GET['class']) ?>" style="width:100px; font-size:11px;" readonly="readonly" />
							<input type="submit" name="cmd" value="Insert" style="width:50px; font-size:11px;" /><br />
							<strong>NOTE:</strong> ONLY manually parse spells for the selected class above! 
						</td>
						</form>
					</tr>
				</table>
		<?php 
	} 
	?>
</table>
<?php
}

function CountSpellClasses($id)
{
	global $eq2;
	
	$query = sprintf("SELECT COUNT(DISTINCT spell_id) AS cnt FROM `".PARSER_DB."`.raw_spell_levels WHERE spell_id = %s GROUP BY adventure_class_id;", $id);
	$result=$eq2->db->sql_query($query);
	if( $eq2->db->sql_numrows($result) > 1 )
		return true;
	return false;
}


function compareMerchants() {
	global $eq2;

	$eq2->CompareMerchants();
}

// old pop merchants routine
function popMerchants() {
	global $eq2;

	if( isset($_POST['cmd']) )
	{
		$next_inventory_id = ( $_POST['inventory_id'] ) ? $_POST['inventory_id'] : $eq2->getNextPK("merchants", "inventory_id");
		$next_merchant_id = $eq2->getNextPK("merchants", "merchant_id");
		$objectName = $eq2->db->sql_escape($_POST['inventory_name']);
		if( $_POST['inventory_id'] == 0 && strlen($_POST['inventory_name']) > 0 )
		{
			$sql = sprintf("INSERT INTO merchants (merchant_id, inventory_id, description) VALUES (%s, %s, '%s')", $next_merchant_id, $next_inventory_id, $objectName);
			//printf('%s<br>', $sql);
			$eq2->runQuery($sql);
			$eq2->logQuery($sql);
			$p = array("merchants",$objectName,$sql);
			$eq2->dbEditorLog($p);
			
			$objectName = $eq2->db->sql_escape($_POST['merchant_name']);
			$sql = sprintf("UPDATE spawn SET merchant_id = %s, command_primary = 6, command_secondary = 101 WHERE name = '%s'", $next_merchant_id, $objectName);
			$eq2->runQuery($sql);
			$eq2->logQuery($sql);
			$p = array("spawns",$objectName,$sql);
			$eq2->dbEditorLog($p);
		}
		
		$sql = '';
		if( $_POST['inventory_id'] > 0 || $next_inventory_id > 0 )
		{
			$query = sprintf("INSERT INTO merchant_inventory (inventory_id, item_id) VALUES ");
			foreach($_POST as $key=>$val) 
			{
				$myArr = explode("|",$key);
				if( $myArr[0]=="inventory" ) 
				{
					if( empty($values) )
						$values = sprintf("(%s, %s)", $next_inventory_id, $myArr[1]);
					else
						$values .= sprintf(",(%s, %s)", $next_inventory_id, $myArr[1]);
				}
			}
			if( isset($values) )
			{
				$sql .= $query . $values . ";";
				//printf('%s<br>', $sql);
				$eq2->runQuery($sql);
				$eq2->logQuery($sql);
				$p = array("merchant_inventory",$objectName,$sql);
				$eq2->dbEditorLog($p);
			}
		}
	}

	$link = sprintf("%s?%s",$_SERVER['SCRIPT_NAME'], $_SERVER['QUERY_STRING']);

	$query=sprintf("select distinct rz.id,zone_file,zone_desc
									from `".PARSER_DB."`.raw_zones rz
									join `".PARSER_DB."`.raw_spawns rs on rz.id = rs.zone_id
									join `".PARSER_DB."`.raw_merchant_items rmi on rs.spawn_id = rmi.spawn_id
									order by zone_desc;"); //echo $query;
	$result=$eq2->db->sql_query($query);
	while($data=$eq2->db->sql_fetchrow($result)) {
		$selected=( $_GET['z'] == $data['id'] ) ? " selected" : "";
		$zoneOptions.='<option value="_admin.php?p=merchant&z='.$data['id'].'"'.$selected.'>'.$data['zone_desc'].' ('.$data['zone_file'].')</option>\n';
		$zoneName=( $_GET['z'] == $data['id'] ) ? $data['zone_file'] : "";	
	}

	// once a zone is picked, display it's known merchant spawns
	if( isset($_GET['z']) ) {
		$query=sprintf("select distinct rsi.id, rsi.name, rsi.guild 
										from `".PARSER_DB."`.raw_spawn_info rsi
										join `".PARSER_DB."`.raw_spawns rs on rsi.id = rs.spawn_id
										join `".PARSER_DB."`.raw_merchant_items rmi on rsi.id = rmi.spawn_id
										where zone_id = %d
										order by rsi.name;",$_GET['z']); //echo $query;
		$result=$eq2->db->sql_query($query);
	
		$spawnOptions = "<option>Pick a Merchant</option>\n";
		while($data=$eq2->db->sql_fetchrow($result)) 
		{
			if( $_GET['s'] == $data['id'] )
			{
				$selected = " selected";
				$merchant_name = $data['name'];
			}
			else
			{
				$selected = "";
			}
			$sub_title=preg_replace("/<(.*)>/"," - &lt;$1&gt;",$data['guild']);
			$spawnOptions .= sprintf("<option value=\"_admin.php?p=merchant&z=%d&s=%s\"%s>%s %s (%d)</option>\n",$_GET['z'],$data['id'],$selected,$data['name'],$sub_title,$data['id']);
		}
	}
	
?>
<table>
	<tr>
		<td valign="top"><select name="zoneID" onchange="dosub(this.options[this.selectedIndex].value)" style="width:300px;">
				<option>Pick a Zone</option>
				<?= $zoneOptions ?>
			</select></td>
		<?php 
			if( isset($_GET['z']) ) { 
			?>
		<td valign="top"><select name="spawnID" onchange="dosub(this.options[this.selectedIndex].value)" style="width:500px;">
				<?= $spawnOptions ?>
			</select>
			<a href="<?= $link ?>">Reload Page</a></td>
		<?php } ?>
	</tr>
</table>
<?php 
	if( isset($_GET['s']) ) {
	
		// process commands
		/*switch($_POST['cmd']) {
			case "Load All": $eq2->processMerchant($_POST['merchant_id']); break;
			default: 
		}*/
	
	
?>
<table border="1" cellspacing="0" cellpadding="2">
	<form method="post" name="load-merchant">
		<tr>
			<td colspan="7"><strong>Below are a list of `merchants` and their goods that
					do not yet exist in the TessEQ2 DB. Populate the database by using the button(s)
					below.</strong></td>
		</tr>
		<tr height="50">
			<td colspan="7" valign="middle"><select name="inventory_id" style="width:200px; font-size:10px;">
					<option value="0">Create New Inventory</option>
					<?php
				$query = "select * from merchants order by merchant_id, inventory_id;";
				$result = $eq2->db->sql_query($query);
				while($data=$eq2->db->sql_fetchrow($result)) {
					$selected = $data['inventory_id']==$_POST['inventory_id'] ? " selected" : "";
					printf('<option value="%s"%s>%s</option>\r\n', $data['inventory_id'], $selected, $data['description']);
				}
				?>
				</select>
				<input type="text" name="inventory_name" value="<?php print($_POST['inventory_name']) ?>" style="width:200px; font-size:10px;" />
				&nbsp;
				<input type="hidden" name="merchant_name" value="<?= $merchant_name ?>" />
				<input type="submit" name="cmd" value="Build Inventory" style="width:100px; font-size:10px;" /></td>
		</tr>
		<tr>
			<th>&nbsp;</th>
			<th>id</th>
			<th>item_type</th>
			<th width="250">name</th>
			<th width="450">description <span style="font-weight:normal; font-size:9px;">(mouse-over
					for full desc)</span></th>
			<th>price</th>
			<th>exists</th>
		</tr>
		<?php
		$query=sprintf("select i.id, i.item_type, i.name, i.description, rmi.price 
										from `".ACTIVE_DB."`.items i
										join `".PARSER_DB."`.raw_items ri on i.name = ri.name
										join `".PARSER_DB."`.raw_merchant_items rmi on ri.item_id = rmi.item_id 
										where rmi.spawn_id = %s
										group by i.name;",$_GET['s']); //echo $query;
		$result=$eq2->db->sql_query($query);
		$row_count = $eq2->db->sql_numrows($result);

		while($data=$eq2->db->sql_fetchrow($result)) {
			$description = ( strlen($data['description'])>75 ) ? substr($data['description'],0,75)."..." : $data['description'];
			if( strlen($data['name']) > 2 ) {
				$name=preg_replace("/ /","'",$data['name']);
				?>
		<tr align="left">
			<td><input type="checkbox" name="inventory|<?= $data['id'] ?>" value="1" checked /></td>
			<td><?= $data['id'] ?>&nbsp;</td>
			<td><?= $data['item_type'] ?>&nbsp;</td>
			<td align="left"><?= $name ?>
				&nbsp;</td>
			<td title="<?= $data['description'] ?>"><?= $description ?>
				&nbsp;</td>
			<td align="right"><?= $data['price'] ?>cp&nbsp;</td>
			<td align="center">&nbsp;<?= print($eq2->existsMerchantItem($data['id'])) ?></td>
		</tr>
		<?php
			}
		}
		?>
		<tr>
			<td colspan="7" align="right"><strong>
				<?= $row_count ?>
				records found.</strong></td>
		</tr>
	</form>
</table>
<?php 
	}
}

function copyZone() 
{
	global $eq2;

	if( isset($_POST['doCopyZone']) ) {
		$eq2->processCopyZone($_GET['to']);
	}
	?>
<table cellspacing="4" cellpadding="6">
	<tr>
		<td colspan="2" height="30">
			<strong>WARNING:</strong> This script copies the current spawns in EQ2 DB Project (<strong><?= strtoupper(ACTIVE_DB) ?></strong>) over to EQ2 Test Center (<strong><?= strtoupper(LIVE_DB) ?></strong>). It can be used to keep updating LIVE with DEV work.<br />
			<strong>NOTE:</strong> This WILL PURGE the <?= strtoupper(LIVE_DB) ?> zone and overwrite existing content! Be careful.</td>
	</tr>
	<tr>
		<td align="right">Select <?= strtoupper(ACTIVE_DB) ?> Zone to Copy to <?= strtoupper(LIVE_DB) ?>:</td>
		<td><select name="zoneFrom" onchange="dosub(this.options[this.selectedIndex].value)" style="width:400px;">
				<option>---</option>
				<?php
				$query = sprintf("SELECT DISTINCT z.id,z.name,z.description
												FROM `".ACTIVE_DB."`.zones z
												JOIN `".ACTIVE_DB."`.spawn_location_placement slp ON z.id = slp.zone_id
												ORDER BY z.description;");
				if( !$result=$eq2->db->sql_query($query) ) 
					$eq2->SQLError($query);
			
				while($data=$eq2->db->sql_fetchrow($result)) 
					printf('<option value="_admin.php?p=copyzone&from=%s&to=%s"%s>%s (%s)</option>', $data['id'], $data['id'], ( $_GET['from'] == $data['id'] ) ? " selected" : "", $data['description'], $data['name']);
				?>
			</select></td>
	</tr>
	<?php 
	if ( isset($_GET['from']) ) 
	{ 
		$to = ( isset($_GET['to']) ) ? $_GET['to'] : $_GET['from']; // intially, make the TO zone the same as FROM - allowing changes below
	?>
	<tr>
		<td align="right">Select <?= strtoupper(LIVE_DB) ?> Zone:</td>
		<td><select name="zoneTo" onchange="dosub(this.options[this.selectedIndex].value)" style="width:400px;" disabled="disabled">
				<option>---</option>
				<?php
				$query = sprintf("SELECT DISTINCT z.id, z.name, z.description
												FROM `".LIVE_DB."`.zones z
												LEFT JOIN `".LIVE_DB."`.spawn_location_placement slp ON z.id = slp.zone_id
												ORDER BY z.description;");
				
				if( !$result=$eq2->db->sql_query($query) )
					$eq2->SQLError($query);
			
				while($data=$eq2->db->sql_fetchrow($result))
				{
					if( $to == $data['id'] )
					{
						$selected = " selected";
						$zone_name = $data['description'];
					}
					else
						$selected = "";
						
					printf('<option value="_admin.php?p=copyzone&from=%s&to=%s"%s>%s (%s)</option>', $_GET['from'], $data['id'], $selected, $data['description'], $data['name']);
				}
				?>
			</select></td>
	</tr>
	<?php 
	} 

	if( isset($_GET['from']) && isset($to) ) 
	{
		//$has_spawns = ;
		
		if( $eq2->isPopulated(LIVE_DB, $to)===1 )
		{
			?>
			<tr>
				<td colspan="2" align="center" style="font-size:16px; color:#F00; font-weight:bold;">THIS WILL ERASE ALL SPAWNS IN <?= sprintf("%s - %s", LIVE_DB, $zone_name) ?>!!!</td>
			</tr>
			<?php
		}
		?>
		<form method="post" name="copyForm">
			<tr>
				<td colspan="2" align="center"><input type="submit" name="doCopyZone" value="Copy" style="width:100px" /></td>
			</tr>
		</form>
		<?php 
	} 
	?>
</table>
<?php

}




function listVoiceovers() {
	global $eq2;

?>
<table>
<form method="post">
	<tr>
		<td height="50" valign="top"><strong>Purpose:</strong> This tool is a way to
			lookup generic Hail responses by keyword, for building completed random hail
			options. For instance, a Conversation on a particular Freeport Darkelf guard
			may only have 1 Hail response associated by his spawn_id, but searching the
			entire database of Freeport Darkelf Guards, you may find more (or all) possible
			options. Keep in mind that SOE uses abbreviations sometimes (gm = generic
			male, gf = generic female, etc) so the more keywords you use, the more accurate
			your results. </td>
	</tr>
	<tr>
		<td>Enter keywords in the box below, separated by comma (eg., darkelf, guard):&nbsp;&nbsp;
			[ with hail
			<input type="radio" name="hail" value="1"<?php $_POST['hail']==1 ? print(" checked") : "" ?> />
			&nbsp;&nbsp;
			without hail
			<input type="radio" name="hail" value="0"<?php $_POST['hail']==0 ? print(" checked") : "" ?> />
			&nbsp;] <br />
			<input type="text" name="criteria" value="<?= $_POST['criteria'] ?>" size="100" />
			&nbsp;
			<input type="submit" name="submit" value="Search" style="font-size:10px; width:70px" />
			&nbsp;
			<input type="button" name="reset" value="Clear" onclick="javascript:window.open('_admin.php?p=voiceovers', target='_self');" style="font-size:10px; width:70px" /></td>
	</tr>
</form>
<?php
	if( !empty($_POST['criteria']) ) 
	{
		$criteria = preg_replace("/, /",",",$_POST['criteria']);
		$criteria = preg_replace("/ ,/",",",$criteria);
		$criteria = explode(",",$criteria);
		foreach($criteria as $key=>$val) {
			$where .= sprintf("(sound_file rlike '%s')", $eq2->db->sql_escape($val));
			$where2 .= sprintf("(chat_text rlike '%s')", $eq2->db->sql_escape($val));
		}
		$where = preg_replace("/\)\(/",") AND (", $where);
		$where2 = preg_replace("/\)\(/",") AND (", $where2);
		$query = sprintf("select distinct sound_file, chat_text, emote, key1, key2 from `".PARSER_DB."`.raw_conversations where ((%s) OR (%s)) order by sound_file;", $where, $where2); 
		//echo $query;
	}
	
	if( !empty($query) ) {
		$result=$eq2->db->sql_query($query);
		$rows = $eq2->db->sql_numrows($result);
		if( $rows > 0 ) {
			$text = '<tr><td>';
			if( $_POST['hail'] ) 
			{
				$text .= "function hailed(NPC, Spawn)<br />";
				$text .= "&nbsp;&nbsp;FaceTarget(NPC, Spawn)<br /><br />";
				$text .= "&nbsp;&nbsp;-- Use this math.random template based on the number of your search results.<br />";
				$text .= "&nbsp;&nbsp;-- Paste your PlayFlavors between the elses, depending on how many you have.<br />";
				$text .= sprintf("&nbsp;&nbsp;local choice = math.random(1,%d)<br /><br />",$rows);
				$text .= "&nbsp;&nbsp;if choice == 1 then<br />";
				$text .= "&nbsp;&nbsp;elseif choice == 1 then<br />";
				$text .= "&nbsp;&nbsp;else<br />";
				$text .= "&nbsp;&nbsp;end<br /><br />";
				$text .= "&nbsp;&nbsp;-- Here are all the PlayFlavors that match your criteria:<br />";
				$text .= "&nbsp;&nbsp;-- Too many? Consider adding more criteria based on what you see below.<br /><br />";
				while($data=$eq2->db->sql_fetchrow($result)) {
					$text .= sprintf("&nbsp;&nbsp;&nbsp;&nbsp;PlayFlavor(NPC, \"%s\", \"%s\", \"%s\", %s, %s, Spawn)<br /><br />",$data['sound_file'],$data['chat_text'],$data['emote'],$data['key1'],$data['key2']);
				}
			}
			else
			{
				$text .= "&nbsp;&nbsp;-- Here are all the PlayFlavors that match your criteria:<br />";
				$text .= "&nbsp;&nbsp;-- Too many? Consider adding more criteria based on what you see below.<br /><br />";
				while($data=$eq2->db->sql_fetchrow($result)) {
					$text .= sprintf("PlayFlavor(NPC, \"%s\", \"%s\", \"%s\", %s, %s, Spawn)<br /><br />",$data['sound_file'],$data['chat_text'],$data['emote'],$data['key1'],$data['key2']);
				}
			}
		
			$pattern[0]="/\"/i";
			$replace[0]="&quot;";
			print(preg_replace($pattern,$replace,$text)); 
		} 
		else 
		{
			print("No results. Did you separate your criteria with comma's?\n");
		}
	}
}


function buildMovement() {
	global $eq2;

	$speed = isset($_POST['speed']) ? $_POST['speed'] : 3;
	$pause = isset($_POST['pause']) ? $_POST['pause'] : 0;
?>
<table>
	<tr>
		<td> This tool will parse your pasted "/loc" data into Movement scripts that
			you can insert into a spawn's LUA. It is not very robust or fancy, <strong>so
			ONLY past /loc lines</strong>, no other chat lines, for the best output. You
			can also set Speed and Pause optionally at the bottom, or a general walking
			speed with no pause is assumed. </td>
	</tr>
	<?php
if( !empty($_POST['movement']) && $_POST['submit'] == "Submit" )
{
	$pattern[0] = "/.*?\] Your location is (.*?).  .*?[\r\n]+/";
	$replace[0] = sprintf("\tMovementLoopAddLocation(NPC, $1, %d, %d)\r\n",$speed, $pause);
	$scriptContent = preg_replace($pattern, $replace, $_POST['movement']);
?>
	<form method="post" name="ssForm">
	
	<tr>
		<td>Parsed Locations:<br />
			<textarea name="parsed" style="height:300px; width:960px; border:2px dotted #999;" wrap="off"><?= $scriptContent ?>
</textarea></td>
	</tr>
	<tr>
		<td align="center"><input type="submit" name="submit" value="Retry" style="width:100px" />
			<input type="button" value="Clear" style="width:100px" onclick="javascript:window.open('_admin.php?p=movement', target='_self');" />
			<input type="hidden" name="movement" value="<?= $_POST['movement'] ?>" />
			<input type="hidden" name="speed" value="<?= $_POST['speed'] ?>" />
			<input type="hidden" name="pause" value="<?= $_POST['pause'] ?>" /></td>
	</tr>
	<?php
}
else
{
?>
	<form method="post" name="ssForm">
		<tr>
			<td>Paste your Locations:<br />
				<textarea name="movement" style="height:300px; width:960px; border:2px dotted #999;" wrap="off"><?= $_POST['movement'] ?>
</textarea></td>
		</tr>
		<tr>
			<td valign="middle" height="30"><strong>Params:</strong>&nbsp;&nbsp;
				Speed:&nbsp;
				<input type="text" name="speed" value="<?= $speed ?>" maxlength="2" size="2" />
				&nbsp;&nbsp;
				Pause Delay:&nbsp;
				<input type="text" name="pause" value="<?= $pause ?>" maxlength="3" size="2" /></td>
		</tr>
		<tr>
			<td align="center"><input type="submit" name="submit" value="Submit" style="width:100px" /></td>
		</tr>
	</form>
	<?php
	}
?>
</table>
<?php
}

function updateEditorItemVals()
{
	$types = array(
		0=>'item_slot',
		1=>'item_flag',
		2=>'wield_type',
		3=>'item_type',
		4=>'item_menu_type',
		5=>'item_tag',
		6=>'item_stat',
		7=>'item_disp_flag'
	);
	$ih_url = "https://git.eq2emu.com/devn00b/EQ2EMu/raw/master/EQ2/source/WorldServer/Items/Items.h";
	$ih_lines = file($ih_url);
	print("<br>");
	$query = "INSERT INTO eq2meta ('id','type','subtype','value','name') VALUES ";
	foreach($ih_lines as $line)
	{
		$line_start = substr($line, 0, 7);
		if($line_start == "#define" AND !strpos($line,'__'))
		{
			$regex = '/[\s]+|[\/\/]+/';
			$stat = preg_split($regex, $line);

			switch (substr($stat[1],0,7))
			{
				case "ITEM_TY":
					$query .= "(NULL,0,''," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//print("[item_type]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					break;
				case "ITEM_ME":
					$query .= "(NULL,4,'current'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],15)))) . "'),\n";
					//print("[menu_type]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					break;
				case "ORIG_IT":
					$query .= "(NULL,4,'original'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],15)))) . "'),\n";
					//print("[menu_type]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					break;
				case "ITEM_TA":
					$query .= "(NULL,5,''," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],9)))) . "'),\n";
					//print("[item_tag]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					break;
				case "ITEM_BR":
					if(strpos($stat[1],'STAT'))
					{
						//print("[broker_stat]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					}elseif(strpos($stat[1],'TYPE'))
					{
						//print("[broker_type]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					}elseif(strpos($stat[1],'SLOT'))
					{
						//print("[broker_slot]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					}else{
						//print("[broker_unknown]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					}
					
					break;
				case "ITEM_ST":
					//BELOW 100 (Attributes)
					if($stat[2] < 100)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//100-199 (unknown name)
					}elseif($stat[2] > 99 AND $stat[2] < 200)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//200-299 (Resists)
					}elseif($stat[2] > 199 AND $stat[2] < 300)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//300-399 (Damage)
					}elseif($stat[2] > 299 AND $stat[2] < 400)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//400-499 (Unknown)
					}elseif($stat[2] > 399 AND $stat[2] < 500)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//500-599 (Pools)
					}elseif($stat[2] > 499  AND $stat[2] < 600)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//6[00]-6[99] (Item Stats)
					}elseif($stat[2] > 599  AND $stat[2] < 700)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//6[100]-6[199](Item Stats)
					}elseif($stat[2] > 6099  AND $stat[2] < 6200)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//700-799 (Spell Damage)
					}elseif($stat[2] > 699  AND $stat[2] < 800)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					//800-899 (Non-Visable)
					}elseif($stat[2] > 799  AND $stat[2] < 900)
					{
						$query .= "(NULL,6,'stat_subtype'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],10)))) . "'),\n";
					}else{
						print("[item_stat]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					}
					break;
				case "DISPLAY":
					$query .= "(NULL,7,''," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],13)))) . "'),\n";
					//print("[item_disp_flag]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					break;
				case "ITEM_WI":
					$query .= "(NULL,2,''," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],16)))) . "'),\n";
					//print("[wield_type]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
					break;
				default:
					if($stat[1] == 'OVERFLOW_SLOT' OR $stat[1] == 'SLOT_INVALID' OR strpos($stat[1],'SLOT'))
					{
						if(substr($stat[1],0,2)=='EQ')
						{
							$query .= "(NULL,0,'decimal'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",substr($stat[1],4)))) . "'),\n";
						}else{
							$query .= "(NULL,0,'binary'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",$stat[1]))) . "'),\n";
							//print("[slots]:" . $stat[1] . "-" .$stat[2]. "<br>\n");
						}
					}elseif(substr($stat[1],0,7) == 'BASE_EQ' OR substr($stat[1],0,7) == 'MAX_EQU' OR $stat[1] == 'APPEARANCE_EQUIPMENT'){
						//print("[Drop this]: " . $stat[1] . "-" .$stat[2]. "<br>\n");
					}else{
						$query .= "(NULL,1,'***'," . $stat[2] . ",'" . ucwords(strtolower(str_replace("_"," ",$stat[1]))) . "'),\n";
						//print("[flags]: " . $stat[1] . "-" .$stat[2]. "<br>\n");
					}
					break;
			}
		}
	}
	$strHTML = "";
	$strHTML .= "<fieldset>";
	$strHTML .= "<legend>NOTICE</legend>";
	$strHTML .= "This page pulls the items.h file from <a href='https://git.eq2emu.com/devn00b/EQ2EMu/raw/master/EQ2/source/WorldServer/Items/Items.h'>here</a>, and builds a SQL query that can be run against eq2editor.eq2meta.  This will help keep the labels used in the editor consistent with what is in live. ";
	$strHTML .= "</fieldset>";
	$strHTML .= "<fieldset>";
	$strHTML .= "<legend>SQL QUERY</legend>";
	$strHTML .= $query;
	$strHTML .= "</fieldset>";
	print($strHTML);
}

function editor_configs()
{
	global $eq2;
	$strHTML = "<fieldset>\n";
	$strHTML .= "  <legend>Configs</legend>\n";
	$strHTML .= "  <table class='ContrastTable'>\n";
	$strHTML .= "    <tr>\n";
	$strHTML .= "      <th>Config Name</td>\n";
	$strHTML .= "      <th>Config Value</td>\n";
	$strHTML .= "      <th>Config Explain</td>\n";
	$strHTML .= "    </tr>\n";
	$config_query = "SELECT * FROM config";
	$config_data = $eq2->RunQueryMulti($config_query);
	foreach($config_data as $config)
	{
		$strHTML .= "    <tr>\n";
		$strHTML .= "      <td>\n";
		$strHTML .= "        " . $config['config_name'] . "\n";
		$strHTML .= "      </td>\n";
		$strHTML .= "      <td>\n";
		$strHTML .= "        " . $config['config_value'] . "\n";
		$strHTML .= "      </td>\n";
		$strHTML .= "      <td>\n";
		$strHTML .= "        " . $config['config_explain'] . "\n";
		$strHTML .= "      </td>\n";
		$strHTML .= "    </tr>\n";
	}
	$strHTML .= "  </table>\n";
	$strHTML .= "</fieldset>\n";

	print($strHTML);
}

function editor_datasources()
{
	global $eq2;
	$strHTML = "<fieldset>\n";
	$strHTML .= "  <legend>Datasources</legend>\n";
	$strHTML .= "  <table class='ContrastTable'>\n";
	$strHTML .= "    <tr>\n";
	$strHTML .= "      <th>id</td>\n";
	$strHTML .= "      <th>Display Name</td>\n";
	$strHTML .= "      <th>DB Name</td>\n";
	$strHTML .= "      <th>DB Host</td>\n";
	$strHTML .= "      <th>DB Port</td>\n";
	$strHTML .= "      <th>DB UserName</td>\n";
	$strHTML .= "      <th>DB Password</td>\n";
	$strHTML .= "      <th>DB Description</td>\n";
	$strHTML .= "      <th>DB World ID</td>\n";
	$strHTML .= "      <th>Active</td>\n";
	$strHTML .= "      <th></td>\n";
	$strHTML .= "    </tr>\n";
	$datasources_query = "SELECT * FROM datasources";
	$datasources_data = $eq2->RunQueryMulti($datasources_query);
	foreach($datasources_data as $datasources)
	{
		$strHTML .= "    <form method='post' name='datasources|" . $datasources['id'] . "'>\n";
		$strHTML .= "      <tr>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|id' value='" . $datasources['id'] . "' style='width:50px' readonly>\n";
		$strHTML .= "          <input type='hidden' name='orig_id' value='" . $datasources['id'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|db_display_name' value='" . $datasources['db_display_name'] . "' style='width:170px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_db_display_name' value='" . $datasources['db_display_name'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|db_name' value='" . $datasources['db_name'] . "' style='width:80px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_db_name' value='" . $datasources['db_name'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|db_host' value='" . $datasources['db_host'] . "' style='width:120px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_db_host' value='" . $datasources['db_host'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|db_port' value='" . $datasources['db_port'] . "' style='width:50px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_db_port' value='" . $datasources['db_port'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|db_user' value='" . $datasources['db_user'] . "' style='width:90px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_db_user' value='" . $datasources['db_user'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='password' name='datasources|db_password' value='" . $datasources['db_password'] . "' style='width:120px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_db_password' value='" . $datasources['db_password'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|db_description' value='" . $datasources['db_description'] . "' style='width:250px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_db_description' value='" . $datasources['db_description'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|db_world_id' value='" . $datasources['db_world_id'] . "' style='width:50px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_db_world_id' value='" . $datasources['db_world_id'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='text' name='datasources|is_active' value='" . $datasources['is_active'] . "' style='width:50px'>\n";
		$strHTML .= "          <input type='hidden' name='orig_is_active' value='" . $datasources['is_active'] . "'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "        <td>\n";
		$strHTML .= "          <input type='hidden' name='table_name' value='datasources'>\n";
		$strHTML .= "          <input type='hidden' name='idx_name' value='id'>\n";
		$strHTML .= "          <input type='submit' name='cmd' value='Update'>\n";
		$strHTML .= "          <input type='submit' name='cmd' value='Delete'>\n";
		$strHTML .= "        </td>\n";
		$strHTML .= "      </tr>\n";
		$strHTML .= "    </form>\n";
	}  
	$strHTML .= "  </table>\n";
	$strHTML .= "</fieldset>\n";

	print($strHTML);
}


function bugreports()
{
	global $eq2;

	$id = $_GET['id'] ?? null;
	$search = $_GET['search'] ?? null;
    $searchtype = $_GET['searchtype'] ?? null;
	$action = $_GET['action'] ?? null;
	$type = $_GET['type'] ?? null;
    $strOffset = str_repeat("\x20",22);
	$search_data=null;

    $strHTML = "";
	$strHTML .= $strOffset . "<fieldset>\n";
	$strHTML .= $strOffset . "  <legend>Select Search Type</legend>\n";
	$strHTML .= $strOffset . "  <form method='post' name='FormBugSearch'>\n";
	$strHTML .= $strOffset . "    <input type='radio' id='name' name='searchtype' value='_admin.php?page=bugreports&action=search&type=name' onchange='dosub(this.value)'>By Name\n";
	$strHTML .= $strOffset . "    <input type='radio' id='category' name='searchtype' value='_admin.php?page=bugreports&action=search&type=category' onchange='dosub(this.value)'>By Category\n";
	$strHTML .= $strOffset . "    <input type='radio' id='all' name='searchtype' value='_admin.php?page=bugreports&action=search&type=all' onchange='dosub(this.value)'>All Bug Reports\n";
	$strHTML .= $strOffset . "  </form>\n";
	$strHTML .= $strOffset . "</fieldset>\n";

	switch($action)
	{
		case "search":

			break;
		case "all":
		default:
			$search_query = "SELECT * FROM `" . ACTIVE_DB . "`.`bugs`";
			$search_data = $eq2->RunQueryMulti($search_query);
			break;
	}
	
	if($search_data)
	{
		$strHTML .= $strOffset . "<fieldset>\n";
		$strHTML .= $strOffset . "  <legend>Search Results</legend>\n";
		$strHTML .= $strOffset . "  <table class='ContrastTable'>\n";
		$strHTML .= $strOffset . "    <tr>\n";
		$strHTML .= $strOffset . "    <th>ID</th>\n";
		$strHTML .= $strOffset . "    <th>Accnt</th>\n";
		$strHTML .= $strOffset . "    <th>Player</th>\n";
		$strHTML .= $strOffset . "    <th>Category</th>\n";
		$strHTML .= $strOffset . "    <th>Subcategory</th>\n";
		$strHTML .= $strOffset . "    <th>Causes Crash</th>\n";
		$strHTML .= $strOffset . "    <th>Reproducible</th>\n";
		$strHTML .= $strOffset . "    <th>Summary</th>\n";
		$strHTML .= $strOffset . "    <th>Description</th>\n";
		$strHTML .= $strOffset . "    <th>Version</th>\n";
		$strHTML .= $strOffset . "    <th>Spawn Name</th>\n";
		$strHTML .= $strOffset . "    <th>Spawn ID</th>\n";
		$strHTML .= $strOffset . "    <th>Date/Time</th>\n";
		$strHTML .= $strOffset . "    <th>Zone ID</th>\n";
		$strHTML .= $strOffset . "    <th>Copied</th>\n";
		$strHTML .= $strOffset . "    <th>DB Ver</th>\n";
		$strHTML .= $strOffset . "    <th>World Ver</th>\n";
		$strHTML .= $strOffset . "    </tr>\n";
		foreach($search_data as $search_result)
		{
			
			$strHTML .= $strOffset . "  <tr>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['id'] . "</td>\n";
			$strHTML .= $strOffset . "    <td><a href='characters.php?page=search&action=ListByAcct&type=acct&form=sel&id=" . $search_result['account_id'] . "' target='_new'>" . $search_result['account_id'] . "</a></td>\n";
			$charid_query = "SELECT id FROM `" .  ACTIVE_DB . "`.`characters` WHERE name = '" . $search_result['player'] . "' AND account_id = '" . $search_result['account_id'] . "'";
			$charid_data = $eq2->RunQuerySingle($charid_query);
			$strHTML .= $strOffset . "    <td><a href='characters.php?id=" . $charid_data['id'] . "' target='_new'>" . $search_result['player'] . "</a></td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['category'] . "</td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['subcategory'] . "</td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['causes_crash'] . "</td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['reproducible'] . "</td>\n";
			$strHTML .= $strOffset . "    <td>\n";
			$strHTML .= $strOffset . "      <ul>\n";
			$summaryItem = explode(" ",$search_result['summary']);
			foreach($summaryItem as $item)
			{
				$strHTML .= $strOffset . "        <li>" . $item . "</li>\n";
			}
			$strHTML .= $strOffset . "      </ul>\n";
			$strHTML .= $strOffset . "    </td>\n";
			$strHTML .= $strOffset . "    <td>\n";
			$strHTML .= $strOffset . "      <ul>\n";
			$descriptionItem = explode(" ",$search_result['description']);
			foreach($descriptionItem as $item)
			{
				$strHTML .= $strOffset . "        <li>" . $item . "</li>\n";
			}
			$strHTML .= $strOffset . "      </ul>\n";
			$strHTML .= $strOffset . "    </td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['version'] . "</td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['spawn_name'] . "</td>\n";
			$strHTML .= $strOffset . "    <td><a href='spawns.php?zone=" . $search_result['zone_id'] . "&type=npcs&id=" . $search_result['spawn_id'] . "' target='_new'>" . $search_result['spawn_id'] . "</a></td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['bug_datetime'] . "</td>\n";
			$strHTML .= $strOffset . "    <td><a href='zones.php?zone=" . $search_result['zone_id'] . "' target='_new'>" . $search_result['zone_id'] .  "</a></td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['copied'] . "</td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['dbversion'] . "</td>\n";
			$strHTML .= $strOffset . "    <td>" . $search_result['worldversion'] . "</td>\n";
			$strHTML .= $strOffset . "  </tr>\n";			
		}
		$strHTML .= $strOffset . "  </table>\n";
		$strHTML .= $strOffset . "</fieldset>\n";
	}
	print($strHTML);
}

function editor_news()
{
	global $eq2, $admin;

	$id = $_GET['id'] ?? null;
	$search = $_GET['search'] ?? null;
    $searchtype = $_GET['searchtype'] ?? null;
	$action = $_GET['action'] ?? null;
	$type = $_GET['type'] ?? null;
    $strOffset = str_repeat("\x20",22);
	$search_data=null;
	$list_data=null;
	$results_data=null;
	$showTypes = null;

	$strHTML = "";
	$strHTML .= $strOffset . "<fieldset>\n";
	$strHTML .= $strOffset . "  <legend>Select Search Type</legend>\n";
	$strHTML .= $strOffset . "    <table width='100%'>\n";
	$strHTML .= $strOffset . "      <tr>\n";
	$strHTML .= $strOffset . "        <td>\n";
	$strHTML .= $strOffset . "          <form method='post' name='FormNewsSearch'>\n";
	$strHTML .= $strOffset . "            <input type='radio' id='name' name='searchtype' value='_admin.php?page=editor_news&action=search&type=date' onchange='dosub(this.value)'>By Date\n";
	$strHTML .= $strOffset . "            <input type='radio' id='category' name='searchtype' value='_admin.php?page=editor_news&action=search&type=status' onchange='dosub(this.value)'>By Status\n";
	$strHTML .= $strOffset . "            <input type='radio' id='category' name='searchtype' value='_admin.php?page=editor_news&action=search&type=type' onchange='dosub(this.value)'>By Type\n";
	$strHTML .= $strOffset . "            <input type='radio' id='all' name='searchtype' value='_admin.php?page=editor_news&action=search&type=all' onchange='dosub(this.value)'>All\n";
	$strHTML .= $strOffset . "          </form>\n";
	$strHTML .= $strOffset . "        </td>\n";
	$strHTML .= $strOffset . "        <td align='right'>\n";
	$strHTML .= $strOffset . "        <button onclick=\"location.href='_admin.php?page=editor_news&action=edit&id=type'\">Edit Types</button>\n";
	$strHTML .= $strOffset . "        <button onclick=\"location.href='_admin.php?page=editor_news&action=edit&id=new'\">New News</button>\n";
	$strHTML .= $strOffset . "        </td>\n";
	$strHTML .= $strOffset . "      </tr>\n";
	$strHTML .= $strOffset . "    </table>\n";
	$strHTML .= $strOffset . "</fieldset>\n";
	
	switch($action)
	{
		case "search":
			switch($type)
			{
				case "date":
					$search_query = "SELECT DISTINCT(created_date) FROM `eq2news_items` ORDER BY created_date DESC";
					$date_data = $eq2->RunQueryMulti($search_query);
					$search_data = array();
					foreach($date_data as $date)
					{
						$search_data[$date['created_date']]= array(
							"option"=>gmdate("Y-m-d", $date['created_date']),
							"value"=>$date['created_date']
						);
					}
					break;
				case "status":
					$search_data = array(
						array(
							"option"=>"Active",
							"value"=>"1"
						),
						array(
							"option"=>"Inactive",
							"value"=>"0"
						)
					);
					break;
				case "type":
					$search_query = "SELECT DISTINCT(ni.type) as value, nt.emu_name as option FROM `eq2news_items` AS ni JOIN `eq2news_types` AS nt ON ni.type = nt.emu_type";
					$search_data = $eq2->RunQueryMulti($search_query);
					break;
				case "all":
					$search_query = "SELECT * FROM `eq2news_items` ORDER BY created_date DESC";
					$list_data = $eq2->RunQueryMulti($search_query);
					break;
				default:
					break;
			}
			break;
		case "list":
			switch($type)
			{
				case "date":
					$list_query = "SELECT * FROM `eq2news_items` WHERE created_date='" . $id . "'";
					$list_data = $eq2->RunQueryMulti($list_query);
					break;
				case "status":
					$list_query = "SELECT * FROM `eq2news_items` WHERE is_active='" . $id . "' ORDER BY created_date DESC";
					$list_data = $eq2->RunQueryMulti($list_query);
					break;
				case "type":
					$list_query = "SELECT * FROM `eq2news_items` WHERE type='" . $id . "' ORDER BY created_date DESC";
					$list_data = $eq2->RunQueryMulti($list_query);
					break;
				case "all":
					$search_query = "SELECT * FROM `eq2news_items` ORDER BY created_date DESC";
					$list_data = $eq2->RunQueryMulti($search_query);
					break;
				default:
					break;
			}
			break;
		case "view":
			$results_query="SELECT * FROM `eq2news_items` WHERE id=" . $id;
			$results_data=$eq2->RunQuerySingle($results_query);
			break;
		case "edit":
			switch($id)
			{
				case "new":
					$results_data = true;
					break;

				case "type":
					$showTypes = true;
					break;
			}
			break;
		default:
			break;
	}

	if($search_data)
	{
		$strHTML .= $strOffset . "<fieldset>\n";
		$strHTML .= $strOffset . "  <legend>Search By " . ucwords($type) . "</legend>\n";
		$strHTML .= $strOffset . "    <select onchange=\"dosub(this.options[this.selectedIndex].value)\">\n";
		$strHTML .= $strOffset . "      <option>Select Type</option>\n";
		foreach($search_data as $result)
		{
			//$strHTML .= $strOffset . "      <option value=" . $result['value'] . ">" . $result['option'] . "</option>\n";
			$strHTML .= $strOffset . "    <option value='_admin.php?page=editor_news&action=list&type=" . $type . "&id=" . $result['value'] . "'>" . $result['option'] ." (" . $result['value'] . ")</option>\n";
		}
		$strHTML .= $strOffset . "    </select>\n";
		$strHTML .= $strOffset . "</fieldset>\n";
	}

	if($list_data)
	{
		$strHTML .= $strOffset . "<fieldset>\n";
		$strHTML .= $strOffset . "  <legend>Search Results</legend>\n";
		$strHTML .= $strOffset . "  <table class='ContrastTable'>\n";
		$strHTML .= $strOffset . "    <tr>\n";
		$strHTML .= $strOffset . "      <th>ID</th>\n";
		$strHTML .= $strOffset . "      <th>Date</th>\n";
		$strHTML .= $strOffset . "      <th>Title</th>\n";
		$strHTML .= $strOffset . "      <th>Type</th>\n";
		$strHTML .= $strOffset . "      <th>Sub-Type</th>\n";
		$strHTML .= $strOffset . "      <th>Author</th>\n";
		$strHTML .= $strOffset . "      <th>Is Sticky</th>\n";
		$strHTML .= $strOffset . "      <th>Is Active</th>\n";
		$strHTML .= $strOffset . "      <th>Badge</th>\n";
		$strHTML .= $strOffset . "      <th></th>\n";
		$strHTML .= $strOffset . "    </tr>\n";
		foreach($list_data as $news_story)
		{
			$strHTML .= $strOffset . "    <tr>\n";
			$strHTML .= $strOffset . "      <td>" . $news_story['id'] . "</td>\n";
			$strHTML .= $strOffset . "      <td>" . $news_story['created_date'] . "</td>\n";
			$strHTML .= $strOffset . "      <td>" . $news_story['title'] . "</td>\n";
			$strHTML .= $strOffset . "      <td>" . $admin->GetNewsTypeNameByID($news_story['type']) . "</td>\n";
			$strHTML .= $strOffset . "      <td>" . $admin->GetNewsSubTypeNameByID($news_story['subtype']) . "</td>\n";
			$strHTML .= $strOffset . "      <td>" . $admin->GetUserNameByID($news_story['author']) . "(" . $news_story['author'] . ")</td>\n";
			$strHTML .= $strOffset . "      <td>" . $news_story['is_sticky'] . "</td>\n";
			$strHTML .= $strOffset . "      <td>" . $news_story['is_active'] . "</td>\n";
			$strHTML .= $strOffset . "      <td>" . $news_story['badge'] . "</td>\n";
			$strHTML .= $strOffset . "      <td>\n";
			$strHTML .= $strOffset . "        <button onclick=\"location.href='_admin.php?page=editor_news&action=view&id=" . $news_story['id'] . "'\">View</button>\n";
			$strHTML .= $strOffset . "      </td>\n";
			$strHTML .= $strOffset . "    </tr>\n";
		}
		$strHTML .= $strOffset . "  </table>\n";
		$strHTML .= $strOffset . "</fieldset>\n";
	}

	if($results_data)
	{
		$strHTML .= $strOffset . "<form method='post' name='news'>\n";
		$strHTML .= $strOffset . "  <fieldset>\n";
		$strHTML .= $strOffset . "    <legend>Search Results</legend>\n";
		$strHTML .= $strOffset . "    <table class='ContrastTable'>\n";
		$strHTML .= $strOffset . "      <tr>\n";
		$strHTML .= $strOffset . "        <th>ID</th>\n";
		$strHTML .= $strOffset . "        <th>Date</th>\n";
		$strHTML .= $strOffset . "        <th>Type</th>\n";
		$strHTML .= $strOffset . "        <th>Sub-Type</th>\n";
		$strHTML .= $strOffset . "        <th>Author</th>\n";
		$strHTML .= $strOffset . "        <th>Is Sticky</th>\n";
		$strHTML .= $strOffset . "        <th>Is Active</th>\n";
		$strHTML .= $strOffset . "      </tr>\n";
		$strHTML .= $strOffset . "      <tr>\n";
		$strHTML .= $strOffset . "        <td>\n";
		if($id=='new')
		{
			$strHTML .= $strOffset . "          New\n";
		}else{
			$strHTML .= $strOffset . "          <input type='text' name='eq2news_items|id' value='" . $results_data['id'] . "' style='width:45px;' readonly/>\n";
			$strHTML .= $strOffset . "          <input type='hidden' name='orig_id' value='" . $results_data['id'] . "' />\n";
		}
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "        <td>\n";
		$date_val = (isset($news_story['created_date'])?$news_story['created_date']:date("Y-m-d"));
		$strHTML .= $strOffset . "          <input type='text' name='eq2news_items|created_date' value='" . $date_val . "' style='width:95px;' readonly/>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_created_date' value='" . $results_data['created_date'] . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "        <td>\n";
		$strHTML .= $strOffset . "          <select name='eq2news_items|type'>\n";
		$strHTML .= $strOffset . "            <option value='0'></option>\n";
		foreach($admin->GetNewsTypes() as $type)
		{
			$isSelected = ($results_data['type']==$type['id']?"selected":"");
			$strHTML .= $strOffset . "            <option value='" .$type['id'] . "' " . $isSelected . ">" . $type['name'] . "</option>\n";
		}
		$strHTML .= $strOffset . "          </select>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_type' value='" . $results_data['type'] . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "        <td>\n";
		$strHTML .= $strOffset . "          <select name='eq2news_items|subtype'>\n";
		$strHTML .= $strOffset . "            <option value='0'>None</option>\n";
		foreach($admin->GetNewsSubTypes() as $type)
		{
			$isSelected = ($results_data['subtype']==$type['id']?"selected":"");
			$strHTML .= $strOffset . "            <option value='" . $type['id'] . "' " . $isSelected . ">" . $type['name'] . "</option>\n";
		}
		$strHTML .= $strOffset . "          <select>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_subtype' value='" . $results_data['subtype'] . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "        <td>\n";
		$strHTML .= $strOffset . "          " . $admin->GetUserNameByID($eq2->userdata['id']) . "\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='eq2news_items|author' value='" . $eq2->userdata['id'] . "' />\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_author' value='" . $results_data['author'] . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "        <td align='center'>\n";
		$strHTML .= $strOffset . "          <select name='eq2news_items|is_sticky'>\n";
		$strHTML .= $strOffset . "            <option value='0' " . (intval($results_data['is_sticky'])==0?"selected":"") . ">No</option>\n";
		$strHTML .= $strOffset . "            <option value='1' " . (intval($results_data['is_sticky'])==1?"selected":"") . ">Yes</option>\n";
		$strHTML .= $strOffset . "          </select>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_is_sticky' value='" . $results_data['is_sticky'] . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "        <td align='center'>\n";
		$strHTML .= $strOffset . "          <select name='eq2news_items|is_active'>\n";
		$activeNo = "";
		$activeYes = "";
		if(isset($results_data['is_active']))
		{
			if (intval($results_data['is_active']) == 0)
			{
				$activeNo = "selected";
			}else{
				$activeYes = "selected";	
			}
		}else{
			$activeYes = "selected";
		}
		$strHTML .= $strOffset . "            <option value='1' " . $activeYes . ">Yes</option>\n";
		$strHTML .= $strOffset . "            <option value='0' " . $activeNo . ">No</option>\n";
		$strHTML .= $strOffset . "          </select>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_is_active' value='" . $results_data['is_active'] . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "      </tr>\n";
		$strHTML .= $strOffset . "      <tr>\n";
		$strHTML .= $strOffset . "        <th colspan='6'>Title</th>\n";
		$strHTML .= $strOffset . "        <th colspan='6'>Badge</th>\n";
		$strHTML .= $strOffset . "      </tr>\n";
		$strHTML .= $strOffset . "      <tr>\n";
		$strHTML .= $strOffset . "        <td colspan='6'>\n";
		$strHTML .= $strOffset . "          <input type='text' name='eq2news_items|title' value='" . $results_data['title'] . "' style='width:500px;' />\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_title' value='" . $results_data['title'] . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "        <td>\n";
		$strHTML .= $strOffset . "          <select name='eq2news_items|badge'>\n";
		$strHTML .= $strOffset . "            <option value='norm' " . ($results_data['badge']=="norm"?"selected":"") . ">Normal</option>\n";
		$strHTML .= $strOffset . "            <option value='green' " . ($results_data['badge']=="green"?"selected":"") . ">Green</option>\n";
		$strHTML .= $strOffset . "            <option value='yellow' " . ($results_data['badge']=="yellow"?"selected":"") . ">Yellow</option>\n";
		$strHTML .= $strOffset . "            <option value='red' " . ($results_data['badge']=="red"?"selected":"") . ">Red</option>\n";
		$strHTML .= $strOffset . "          </select>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_badge' value='" . $results_data['badge'] . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "      </tr>\n";
		$strHTML .= $strOffset . "      <tr>\n";
		$strHTML .= $strOffset . "        <th colspan='7'>Text</th>\n";
		$strHTML .= $strOffset . "      </tr>\n";
		$strHTML .= $strOffset . "      <tr>\n";
		$strHTML .= $strOffset . "        <td colspan='7'>\n";
		$strHTML .= $strOffset . "          <textarea name='eq2news_items|description'>" . htmlentities($results_data['description']) . "</textarea>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='orig_description' value='" . htmlentities($results_data['description']) . "' />\n";
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "      </tr>\n";
		$strHTML .= $strOffset . "      <tr>\n";
		$strHTML .= $strOffset . "        <td colspan='7' align='center'>\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='idx_name' value='id' />\n";
		$strHTML .= $strOffset . "          <input type='hidden' name='table_name' value='eq2news_items' />\n";
		if($id=='new')
		{
			$strHTML .= $strOffset . "          <input type='submit' name='cmd' value='Insert' style='font-size:10px; width:60px' />\n";
		}else{
			$strHTML .= $strOffset . "          <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px' />\n";
			$strHTML .= $strOffset . "          <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px' />\n";
		}
		$strHTML .= $strOffset . "        </td>\n";
		$strHTML .= $strOffset . "      </tr>\n";
		$strHTML .= $strOffset . "    </table>\n";
		$strHTML .= $strOffset . "  </fieldset>\n";
		$strHTML .= $strOffset . "</form>\n";

	}

	if($showTypes)
	{
		$strHTML .= $strOffset . "  <fieldset>\n";
		$strHTML .= $strOffset . "    <legend>News Types & SubTypes</legend>\n";
		$strHTML .= $strOffset . "    <table class='ContrastTable'>\n";
		$strHTML .= $strOffset . "      <tr>\n";
		$strHTML .= $strOffset . "        <th>ID</th>\n";
		$strHTML .= $strOffset . "        <th>Type</th>\n";
		$strHTML .= $strOffset . "        <th>Child of</th>\n";
		$strHTML .= $strOffset . "        <th>Name</th>\n";
		$strHTML .= $strOffset . "        <th>Action</th>\n";
		$strHTML .= $strOffset . "      </tr>\n";

		$type_query = "SELECT * FROM `eq2news_types`";
		$type_data = $eq2->RunQueryMulti($type_query);
		foreach($type_data as $type)
		{
			$strHTML .= $strOffset . "      <form method='post'>\n";
			$strHTML .= $strOffset . "        <tr>\n";
			$strHTML .= $strOffset . "          <td>\n";
			$strHTML .= $strOffset . "            <input type='text' name='eq2news_types|id' value='" . $type['id'] . "' style='width:45px;' readonly/>\n";
			$strHTML .= $strOffset . "            <input type='hidden' name='orig_id' value='" . $type['id'] . "'/>\n";
			$strHTML .= $strOffset . "          </td>\n";
			$strHTML .= $strOffset . "          <td>\n";
			$strHTML .= $strOffset . "            <input type='text' name='eq2news_types|emu_type' value='" . $type['emu_type'] . "' style='width:45px;' readonly/>\n";
			$strHTML .= $strOffset . "            <input type='hidden' name='orig_emu_type' value='" . $type['emu_type'] . "'/>\n";
			$strHTML .= $strOffset . "          </td>\n";
			$strHTML .= $strOffset . "          <td>\n";
			$strHTML .= $strOffset . "            <input type='text' name='eq2news_types|emu_parent' value='" . $type['emu_parent'] . "' style='width:45px;'/>\n";
			$strHTML .= $strOffset . "            <input type='hidden' name='orig_emu_parent' value='" . $type['emu_parent'] . "'/>\n";
			$strHTML .= $strOffset . "          </td>\n";
			$strHTML .= $strOffset . "          <td>\n";
			$strHTML .= $strOffset . "            <input type='text' name='eq2news_types|emu_name' value='" . $type['emu_name'] . "'/>\n";
			$strHTML .= $strOffset . "            <input type='hidden' name='orig_emu_name' value='" . $type['emu_name'] . "'/>\n";
			$strHTML .= $strOffset . "          </td>\n";
			$strHTML .= $strOffset . "          <td>\n";
			$strHTML .= $strOffset . "            <input type='hidden' name='idx_name' value='id' />\n";
			$strHTML .= $strOffset . "            <input type='hidden' name='table_name' value='eq2news_types' />\n";
			$strHTML .= $strOffset . "            <input type='submit' name='cmd' value='Update' style='font-size:10px; width:60px'/>\n";
			$strHTML .= $strOffset . "            <input type='submit' name='cmd' value='Delete' style='font-size:10px; width:60px'/>\n";
			$strHTML .= $strOffset . "          </td>\n";
			$strHTML .= $strOffset . "        </tr>\n";
			$strHTML .= $strOffset . "      </form>\n";
			
		}
		$strHTML .= $strOffset . "      <form method='post'>\n";
		$strHTML .= $strOffset . "        <tr>\n";
		$strHTML .= $strOffset . "          <td>New</td>\n";
		$strHTML .= $strOffset . "          <td>\n";
		$strHTML .= $strOffset . "            <input type='text' name='eq2news_types|emu_type' value='' style='width:45px;' />\n";
		$strHTML .= $strOffset . "          </td>\n";
		$strHTML .= $strOffset . "          <td>\n";
		$strHTML .= $strOffset . "            <input type='text' name='eq2news_types|emu_parent' value='' style='width:45px;'/>\n";
		$strHTML .= $strOffset . "          </td>\n";
		$strHTML .= $strOffset . "          <td>\n";
		$strHTML .= $strOffset . "            <input type='text' name='eq2news_types|emu_name' value=''/>\n";
		$strHTML .= $strOffset . "          </td>\n";
		$strHTML .= $strOffset . "          <td>\n";
		$strHTML .= $strOffset . "            <input type='hidden' name='idx_name' value='id' />\n";
		$strHTML .= $strOffset . "            <input type='hidden' name='table_name' value='eq2news_types' />\n";
		$strHTML .= $strOffset . "            <input type='submit' name='cmd' value='Insert' style='font-size:10px; width:60px'/>\n";
		$strHTML .= $strOffset . "          </td>\n";
		$strHTML .= $strOffset . "        </tr>\n";
		$strHTML .= $strOffset . "      </form>\n";
		$strHTML .= $strOffset . "  </fieldset>\n";
	}

	print($strHTML);
}

?>
