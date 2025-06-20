<?php
class EQ2Server
{
	public $EQ2ServerTables = array("appearance","collections","commands","conditionals","emotes","entity_commands","factions","faction_alliances",
																	 "groundspawns","groundspawn_items","guild_ranks_defaults","guild_event_defaults","languages","map_data","merchants",
																	 "name_filter","opcodes","recipes","revive_points","rules","skills",
																	 "spawn_npc_equipment","spawn_npc_skills","spawn_npc_spells",
																	 "starting_details","starting_factions","starting_items","starting_languages","starting_skillbar","starting_skills","starting_spells","starting_titles","starting_zones",
																	 "table_versions","titles","transporters","variables","visual_states","zones");

	private $OpcodeArray 	= array();
	private $opcode_id 		= NULL;
	public $Link = NULL;
	public $Page = NULL;

	private $NavigationSections = array(
		"User Experience"=>array(
			"NPC Spell Lists"=>"npc_spells",
			"Starting Spells"=>"starting_spells",
			"Starting Factions"=>"starting_factions",
			"Heroic Opportunity"=>"heroic_ops",
			"Commands"=>"commands"
		),
		"Object Editors"=>array(
			"Collections"=>"collections",
			"Ground Spawns"=>"groundspawns",
			"Recipes"=>"recipes",
			"Recipe Components"=>"recipe_comp",
			"Loot Tables"=>"loot_table",
			"Loot Global"=>"loot_global",
			"Entity Commands"=>"entity_commands",
			"House Editor"=>"house_editor",
			"Revive Points"=>"revive_points",
			"NPC Equipment"=>"spawn_npc_equipment",
			"Factions"=>"factions",
			"Transporters"=>"transporters",
			"Flight Paths"=>"flight_paths",
			"Merchants"=>"merchants"
		),
		"World Settings"=>array(
			"Variables"=>"variables",
		),
        "Editor Settings"=>array(
			"Editor Lists"=>"editor_lists",
			"Misc. Lua Scripts"=>"misc_scripts",
			"Icons"=>"icons",
			"Lua Blocks"=>"lua_blocks"
		)
		//"Player Defaults"=>array(
		//	"Starting Locations"=>"locations"
		//)
	);
	
	
	public function __construct() 
	{
		$this->Page = sprintf("page=%s", $_GET['page']);
		$this->Link = sprintf("%s?%s",$_SERVER['SCRIPT_NAME'], $this->Page);
	}

	// List of ALL chunks for ChunkList()
	public function GetStartingLocations()
	{
		global $eq2;

		$eq2->SQLQuery = "SELECT * FROM `".ACTIVE_DB."`.starting_locations";
		return $eq2->RunQueryMulti();
	}

	public function GetServerVariables()
	{
		global $eq2;

		$eq2->SQLQuery = "SELECT * FROM `".ACTIVE_DB."`.variables ORDER BY variable_name";
		return $eq2->RunQueryMulti();
	}


	private function GetOpcodeList($val = '')
	{
		global $eq2;
		
		if( strlen($val) == 0 ) {
			$eq2->SQLQuery = "SELECT DISTINCT name FROM `". ACTIVE_DB ."`.opcodes ORDER BY name;";
			return $eq2->RunQueryMulti();
		}
		else {
			$eq2->SQLQuery = "SELECT * FROM ". ACTIVE_DB .".opcodes WHERE `opcode` RLIKE '".$val."' OR `name` RLIKE '".$val."' ORDER BY name;";
			return $eq2->RunQueryMulti('', 1);
		}
		
	}
	
	private function OpcodeLookup()
	{
		global $eq2;
		
		$this->OpcodeArray = $this->GetOpcodeList();
		
		if( is_array($this->OpcodeArray) )
		{
		?>
			<table width="800" border="0">
				<tr>
					<td width="100" class="filter_labels">Filters:</td>
					<td width="300" nowrap="nowrap">
						<select name="opcodeID" onchange="dosub(this.options[this.selectedIndex].value)" style="min-width:300px;" />
						<option value="<?= $this->Link ?>"<?php if( strlen($_GET['opcode_id']) == 0 ) echo " selected" ?>>Pick an Opcode</option>
						<?php
						foreach($this->OpcodeArray as $opcode)
							printf('<option value="%s&opcode=%s"%s>%s</option>', $this->Link, $opcode['name'], ( $opcode['name'] == $_GET['opcode'] )  ? " selected" : "", $opcode['name']);
						?>
						</select> <a href="<?= $this->Link ?>">Reload Page</a>
					</td>
					<td>&nbsp;</td>
				</tr>
				<script language="javascript">
				function OpcodeLookupAJAX() {
					if (searchReq.readyState == 4 || searchReq.readyState == 0) {
						var str = escape(document.getElementById('txtSearch').value);
						searchReq.open("GET", '../ajax/eq2Ajax.php?type=luO&search=' + str, true);
						searchReq.onreadystatechange = handleSearchSuggest; 
						searchReq.send(null);
					}		
				}
				</script>
				<form action="<?= $this->Link ?>" id="frmSearch" method="post">
				<tr>
					<td class="filter_labels">Lookup:</td>
					<td colspan="3">
							<input type="text" id="txtSearch" name="txtSearch" alt="Search Criteria" onkeyup="OpcodeLookupAJAX();" autocomplete="off" class="box" style="width:295px;" value="<?= $_POST['txtSearch'] ?>" /><!--onclick="this.value='';"-->
							<input type="submit" id="cmdSearch" name="cmdSearch" value="Search" alt="Run Search" class="submit" />
							<input type="button" value="Clear" class="submit" onclick="dosub('<?= $this->Link ?>');" />
							<div id="search_suggest">
							</div>
					</td>
				</tr>
				</form>
			</table>
			<br />
			<?php
			// once the filters are set, show the spell selector grid
			if( $_POST['cmdSearch'] == 'Search' ) {
				$opcodes = $this->GetOpcodeList($_POST['txtSearch']);
				
				if( is_array($opcodes) ) {
					$i = 0;
					foreach($opcodes as $row) { // hack in the hyperlink to edit the object
						$opcodes[$i][0] = sprintf('<a href="%s&opcode=%s">%s</a>', $this->Link, $opcodes[$i][0], $opcodes[$i][0]);
						$i++;
					}
																							 
					$columns = array('name' => '150', 'version_range1' => '50', 'version_range2' => '50', 'opcode' => '50' );
					$eq2->GridGenerator($columns, $opcodes);
				}
				else
					print("&nbsp;No data found for set filters. Try by name?");
			}
		}
		else
			print("Could not load the list of opcodes");
			
	}
	
	public function OpcodeEditor()
	{
		global $eq2;
		
		$this->OpcodeLookup();

		if( $this->opcode_id != NULL )
		{
			$opcode = $this->OpcodeArray[$this->opcode_id];
		?>
		<table width="800" class="SubPanel" cellspacing="0" border="0">
			<tr>
				<td id="EditorStatus" colspan="2"><? if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
			</tr>
			<tr>
				<td class="Title" colspan="2">Edit Opcodes</td>
			</tr>
			<tr>
				<td valign="top">
					<table width="100%"  class="SectionMainFloat" cellspacing="0" border="0">
						<tr>
							<td colspan="11" class="SectionTitle">Opcode <?php print($opcode['opcode_name']) ?></td>
						</tr>
						<tr>
							<td width="405"><strong>opcode_name</strong></td>
							<td width="65"><strong>opcode_id</strong></td>
							<td>&nbsp;</td>
						</tr>
						<form method="post" name="multiForm|<?php print($opcode['opcode_id']); ?>" />
						<tr bgcolor="#dddddd">
							<td>
								<input type="text" name="opcodes|opcode_name" value="<?php print($opcode['opcode_name']) ?>"  style="width:400px;" />
								<input type="hidden" name="orig_opcode_name" value="<?php print($opcode['opcode_name']) ?>" />
							</td>
							<td valign="top">
								<input type="text" name="opcodes|opcode_id" value="<?php print($opcode['opcode_id']) ?>"  style="width:60px;" readonly="readonly" />
								<input type="hidden" name="orig_opcode_id" value="<?php print($opcode['opcode_id']) ?>" />
							</td>
							<td>
								<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
								<input type="submit" name="cmd" value="Update" class="submit" />
								<input type="hidden" name="orig_id" value="<?= $opcode['opcode_id'] ?>" />
								<input type="hidden" name="idx_name" value="opcode_id" />
								<input type="hidden" name="object_id" value="o:<?= $opcode['opcode_name'] ?>,i:<?= $opcode['opcode_id'] ?>" />
								<input type="hidden" name="table_name" value="opcodes" />
								<?php } ?>
							&nbsp;
							</td>
						</tr>
						</form>
					</table>
				</td>
			</tr>
		</table>
		<?php
		}
	}

	public function ServerVariables() 
	{
		global $eq2;
	
		?>
		<table class="SubPanel ContrastTable" cellspacing="0" border="0">
			<tr>
				<td id="EditorStatus" colspan="2"><? if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
			</tr>
			<tr>
				<td class="Title" colspan="2">Server Variables</td>
			</tr>
			<tr>
				<td valign="top">
					<table width="100%"  class="SectionMainFloat" cellspacing="0" border="0">
						<tr>
							<td colspan="11" class="SectionTitle">Settings</td>
						</tr>
						<tr>
							<td width="155">variable_name</td>
							<td width="355">variable_value</td>
							<td width="330">comment</td>
							<td>&nbsp;</td>
						</tr>
						<?php
						$rows = $this->GetServerVariables();
						
						if( is_array($rows) )
						{
							foreach($rows as $row) 
							{
							?>
						<form method="post" name="multiForm|<?php print($rows['variable_name']); ?>" />
						<tr bgcolor="#dddddd">
							<td valign="top">
								<input type="text" name="variables|variable_name" value="<?php print($row['variable_name']) ?>"  style="width:150px;" />
								<input type="hidden" name="orig_variable_name" value="<?php print($row['variable_name']) ?>" />
							</td>
							<td>
								<textarea name="variables|variable_value" style="width:350px; height:50px;"><?php print($row['variable_value']) ?></textarea>
								<input type="hidden" name="orig_variable_value" value="<?php print($row['variable_value']) ?>" />
							</td>
							<td>
								<textarea name="variables|comment" style="width:325px; height:50px;"><?php print($row['comment']) ?></textarea>
								<input type="hidden" name="orig_comment" value="<?php print($row['comment']) ?>" />
							</td>
							<td>
								<?php if($eq2->CheckAccess(G_DEVELOPER)) : ?>
								<input type="submit" name="cmd" value="Update" class="submit" />
								<input type="submit" name="cmd" value="Delete" class="submit" />
								<input type="hidden" name="object_id" value="o:variables,i:<?= $row['variable_name'] ?>" />
								<input type="hidden" name="table_name" value="variables" />
								<input type="hidden" name="idx_name" value="variable_name" />
								<input type="hidden" name="orig_id" value="'<?php echo $row['variable_name']; ?>'" />
								<?php endif; ?>
							&nbsp;
							</td>
						</tr>
						</form>
						<?php 
						} // end foreach
					} // end is_array
					?>
					</table>
				</td>
			</tr>
			<?php if($eq2->CheckAccess(G_DEVELOPER)) : ?>
			<tr>
				<td height="50" valign="bottom">Click &quot;Insert&quot; to add a new <em>starting location</em> for a race/class combo</td>
			</tr>
			<tr>
				<td valign="top">
					<table width="100%" class="SectionMainFloat" cellspacing="0" border="0">
						<tr>
							<td colspan="11" class="SectionTitle">Add New Variable</td>
						</tr>
						<tr bgcolor="#dddddd">
							<td width="155">variable_name</td>
							<td width="355">variable_value</td>
							<td width="330">comment</td>
							<td>&nbsp;</td>
						</tr>
						<form method="post" name="sdForm|new" />
						<tr bgcolor="#dddddd">
							<td valign="top"><input type="text" name="variables|variable_name|new" value=""  style="width:150px;" /></td>
							<td><textarea name="variables|variable_value|new" style="width:350px; height:50px;"></textarea></td>
							<td><textarea name="variables|comment|new" style="width:325px; height:50px;"></textarea></td>
							<td>
								<input type="submit" name="cmd" value="Insert" class="submit" />
								<input type="hidden" name="object_id" value="o:variables,i:new" />
								<input type="hidden" name="table_name" value="variables" />
							</td>
						</tr>
						</form>
					</table>
				</td>
			</tr>
			<?php endif; ?>
		</table>
		<?php
	}
	
	public function StartingLocations()
	{
		global $eq2;
		
		?>
		<table class="SubPanel" cellspacing="0" border="0">
			<tr>
				<td id="EditorStatus" colspan="2"><? if( !empty($eq2->Status) ) $eq2->DisplayStatus(); ?></td>
			</tr>
			<tr>
				<td class="Title" colspan="2">
					Player Racial Starting Locations<br />
					<span class="instruction">Leave the location fields blank to use the defaults from the `chunks` table.</span>
				</td>
			</tr>
			<tr>
				<td valign="top">
					<table width="100%"  class="SectionMainFloat" cellspacing="0" border="0">
						<tr>
							<td colspan="11" class="SectionTitle">Race/Class combination starting locations</td>
						</tr>
						<tr bgcolor="#dddddd">
							<td colspan="4">&nbsp;</td>
							<td colspan="7" style="border-left:1px solid #999; border-bottom:1px solid #999; text-align:center;"><strong>Locations</strong></td>
						</tr>
						<tr bgcolor="#dddddd">
							<td width="50"><strong>id</strong></td>
							<td width="130"><strong>race_id</strong></td>
							<td width="130"><strong>class_id</strong></td>
							<td width="75"><strong>chunk_id</strong></td>
							<td width="50" style="border-left:1px solid #999;"><strong>x</strong></td>
							<td width="50"><strong>y</strong></td>
							<td width="50"><strong>z</strong></td>
							<td width="50"><strong>pitch</strong></td>
							<td width="50"><strong>yaw</strong></td>
							<td width="50"><strong>roll</strong></td>
							<td>&nbsp;</td>
						</tr>
						<?php
						$rows = $this->GetStartingLocations();
						$chunks_array = $this->c->GetChunksArray();
						
						if( is_array($rows) )
						{
							foreach($rows as $row) 
							{
						?>
						<form method="post" name="slForm|<?php print($row['id']); ?>" />
						<tr bgcolor="#dddddd">
							<td>
								<strong><?php print($row['id']); ?></strong>
								<input type="hidden" name="orig_id" value="<?php print($row['id']); ?>" />
							</td>
							<td>
								<?php $options = $eq2->GetPlayerRaceOptions($row['race_id']); ?>
								<select name="starting_locations|race_id" style="width:125px;">
									<?php print($options); ?>
								</select>
								<input type="hidden" name="orig_race_id" value="<?php print($row['race_id']); ?>" />
							</td>
							<td>
								<?php $options = $eq2->GetPlayerClassOptions($row['class_id']); ?>
								<select name="starting_locations|class_id" style="width:125px;">
									<?php print($options); ?>
								</select>
								<input type="hidden" name="orig_class_id" value="<?php print($row['class_id']); ?>" />
							</td>
							<td>
								<select name="starting_locations|chunk_id" style="min-width:200px;">
									<option value="0">Pick a Chunk</option>
								<?php 
								if( is_array($chunks_array) )
									foreach($chunks_array as $chunk)
										printf('<option value="%s"%s>%s (%s,%s)</option>', $chunk['id'], ( $chunk['id'] == $row['chunk_id'] ) ? " selected" : "", $chunk['displayname'], $chunk['coord_x'], $chunk['coord_y']);
								?>
								</select>
								<input type="hidden" name="orig_chunk_id" value="<?php print($row['chunk_id']); ?>" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_x" value="<?php print($row['starting_x']); ?>" style="width:60px;" />
								<input type="hidden" name="orig_starting_x" value="<?php print($row['starting_x']); ?>" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_y" value="<?php print($row['starting_y']); ?>" style="width:60px;" />
								<input type="hidden" name="orig_starting_x" value="<?php print($row['starting_x']); ?>" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_z" value="<?php print($row['starting_z']); ?>" style="width:60px;" />
								<input type="hidden" name="orig_starting_x" value="<?php print($row['starting_x']); ?>" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_pitch" value="<?php print($row['starting_pitch']); ?>" style="width:60px;" />
								<input type="hidden" name="orig_starting_x" value="<?php print($row['starting_x']); ?>" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_yaw" value="<?php print($row['starting_yaw']); ?>" style="width:60px;" />
								<input type="hidden" name="orig_starting_x" value="<?php print($row['starting_x']); ?>" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_roll" value="<?php print($row['starting_roll']); ?>" style="width:60px;" />
								<input type="hidden" name="orig_starting_x" value="<?php print($row['starting_x']); ?>" />
							</td>
							<td>
							<?php if($eq2->CheckAccess(G_DEVELOPER)) { ?>
								<input type="submit" name="cmd" value="Update" class="submit" />&nbsp;
								<input type="submit" name="cmd" value="Delete" class="submit" />&nbsp;
								<input type="hidden" name="object_id" value="o:starting_locations,i:<?= $row['id'] ?>" />
								<input type="hidden" name="table_name" value="starting_locations" />
							<?php } ?>
							</td>
						</tr>
						</form>
						<?php 
							} // end foreach
						} // end is_array
						?>
					</table>
				</td>
			</tr>
			<?php
			if($eq2->CheckAccess(G_DEVELOPER)) 
			{ 
			?>
			<tr>
				<td height="50" valign="bottom">Click &quot;Insert&quot; to add a new <em>starting location</em> for a race/class combo</td>
			</tr>
			<tr>
				<td valign="top">
					<form method="post" name="sdForm|new" />
					<table width="100%" class="SectionMainFloat" cellspacing="0" border="0">
						<tr>
							<td colspan="11" class="SectionTitle">Add New Location</td>
						</tr>
						<tr bgcolor="#dddddd">
							<td colspan="4">&nbsp;</td>
							<td colspan="7" style="border-left:1px solid #999; border-bottom:1px solid #999; text-align:center;"><strong>Locations</strong></td>
						</tr>
						<tr bgcolor="#dddddd">
							<td width="50"><strong>id</strong></td>
							<td width="130"><strong>race_id</strong></td>
							<td width="130"><strong>class_id</strong></td>
							<td width="75"><strong>chunk_id</strong></td>
							<td width="50" style="border-left:1px solid #999;"><strong>x</strong></td>
							<td width="50"><strong>y</strong></td>
							<td width="50"><strong>z</strong></td>
							<td width="50"><strong>pitch</strong></td>
							<td width="50"><strong>yaw</strong></td>
							<td width="50"><strong>roll</strong></td>
							<td>&nbsp;</td>
						</tr>
						<tr bgcolor="#dddddd">
							<td><strong>new</strong></td>
							<td>
								<?php $options = $eq2->GetPlayerRaceOptions(255); ?>
								<select name="starting_locations|race_id" style="width:125px;">
									<?php print($options); ?>
								</select>
							</td>
							<td>
								<?php $options = $eq2->GetPlayerClassOptions(255); ?>
								<select name="starting_locations|class_id" style="width:125px;">
									<?php print($options); ?>
								</select>
							</td>
							<td>
								<select name="starting_locations|chunk_id" style="min-width:200px;">
									<option value="0">Pick a Chunk</option>
								<?php 
								if( is_array($chunks_array) )
									foreach($chunks_array as $chunk)
										printf('<option value="%s"%s>%s (%s,%s)</option>', $chunk['id'], ( $chunk['id'] == 1 ) ? " selected" : "", $chunk['displayname'], $chunk['coord_x'], $chunk['coord_y']);
								?>
								</select>
								<input type="hidden" name="orig_chunk_id" value="<?php print($row['chunk_id']); ?>" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_x" value="0" style="width:60px;" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_y" value="0" style="width:60px;" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_z" value="0" style="width:60px;" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_pitch" value="0" style="width:60px;" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_yaw" value="0" style="width:60px;" />
							</td>
							<td>
								<input type="text" name="starting_locations|starting_roll" value="0" style="width:60px;" />
							</td>
							<td><input type="submit" name="cmd" value="Insert" class="submit" /></td>
						</tr>
						<input type="hidden" name="object_id" value="o:starting_locations,i:new" />
						<input type="hidden" name="table_name" value="starting_locations" />
						</form>
					</table>
				</td>
			</tr>
		<?php } ?>
		</table>
		<?php
	}
	
	public function GenerateNavigationMenu() {
		$link = $_SERVER['SCRIPT_NAME'];
		?>

		<table class="SectionMenuLeft" cellspacing="0" border="0">
		<tr>
			<td class="SectionTitle">Navigation</td>
		</tr>
		<tr>
			<td class="SectionBody">
				<ul class="menu-list">
					<li class="menu-list">&raquo; <a href="<?php printf("%s?%s", $link, $_SERVER['QUERY_STRING']) ?>">Reload Page</a></li>
					<li class="<?= ( ($_GET['cl'] ?? "") == "history" ) ? "active-menu-list" : "menu-list" ?>">&raquo; <a href="<?php print($link) ?>?cl=history">ChangeLogs</a></li>
				</ul>
			</td>
		</tr>
		<?php foreach ($this->NavigationSections as $title=>$body) : ?>
		<tr>
			<td class="SectionTitle"><?php echo $title ?></td>
		</tr>
			<tr>
				<td class="SectionBody">
				<?php foreach ($body as $name=>$page) : ?>
					<ul class="menu-list">
						<li class="<?php echo $page == ($_GET['page'] ?? "") ? "active-menu-list" : "menu-list"?>">
						&raquo; <a href="<?php printf("%s?page=%s", $link, $page) ?>"><?php echo $name ?></a>
						</li>
					</ul>
				<?php endforeach ?>
				</td>
			</tr>
		<?php endforeach ?>
	</table>

	<?php
	}

	public function GetNextEntityCommandListID() {
		global $eq2;

		$data = $eq2->RunQuerySingle('SELECT MAX(`command_list_id`)+1 as nxt FROM `'.ACTIVE_DB.'`.entity_commands;');
		return $data['nxt'];
	}
	
	public function NotImplemented()
	{
		if( strlen($_GET['page']) > 0 ) {
		?>
		<table width="1000" cellspacing="0" border="0">
			<tr>
				<td><h3>Not Implemented</h3></td>
			</tr>
		</table>
		<?php
		}
	}
	
	public function PrintStartingFactions() {
		global $eq2;
		?>
		<fieldset><legend>Starting Factions</legend>
				<table>
				<?php if($eq2->CheckAccess(G_DEVELOPER)) : ?>
				<tr>
					<th width="55">id</th>
					<th width="75">city_id</th>
					<th width="55">faction_id</th>
					<th width="55">value</th>
					<th colspan="2"></th>
				</tr>
				<form method="post" name="newEntry">
					<td>
						<strong>new</strong>
					</td>
					<td>
						<select name="starting_factions|starting_city">
							<?php echo $eq2->getStartingCity(0); ?>
						</select>
					</td>
					<td>
						<input type="text" name="starting_factions|faction_id" value=""  style="width:50px;" />
					</td>
					<td>
						<input type="text" name="starting_factions|value" value=""  style="width:50px;" />
					</td>
					<td colspan="2" align="center">
						<input type="hidden" name="table_name" value="starting_factions"/>
						<input type="submit" name="cmd" value="Insert" style="font-size:10px; width:60px" />
					</td>
				</form>
				<?php endif; ?>
				<?php
				$query=sprintf("select * from %s.starting_factions order by starting_city, id", ACTIVE_DB);
				$i = 0;
				foreach ($eq2->RunQueryMulti($query) as $data) :
				?>
				<?php if ($i++ % 10 == 0) : ?>
				<tr>
					<th width="55">id</th>
					<th width="75">city_id</th>
					<th width="55">faction_id</th>
					<th width="55">value</th>
					<th colspan="2"></th>
				</tr>
				<?php endif; ?>
				<tr>
				<form method="post" name="multiForm|<?php print($data['id']); ?>">
					<td>
						<input type="text" name="starting_factions|id" value="<?php print($data['id']) ?>"  style="width:50px;  background-color:#ddd;" readonly />
						<input type="hidden" name="orig_id" value="<?php print($data['id']) ?>" />
					</td>
					<td>
						<select name="starting_factions|starting_city">
							<?php echo $eq2->getStartingCity($data['starting_city']); ?>
						</select>
						<input type="hidden" name="orig_starting_city" value="<?php print($data['starting_city']) ?>" />
					</td>
					<td>
						<input type="text" name="starting_factions|faction_id" value="<?php print($data['faction_id']) ?>"  style="width:50px;" />
						<input type="hidden" name="orig_faction_id" value="<?php print($data['faction_id']) ?>" />
					</td>
					<td>
						<input type="text" name="starting_factions|value" value="<?php print($data['value']) ?>"  style="width:50px;" />
						<input type="hidden" name="orig_value" value="<?php print($data['value']) ?>" />
					</td>
					<?php if($eq2->CheckAccess(G_DEVELOPER)) : ?>
					<td>
						<input type="hidden" name="table_name" value="starting_factions" />
						<input type="submit" name="cmd" value="Update" style="font-size:10px; width:60px" />
					</td>
					<td>
						<input type="submit" name="cmd" value="Delete" style="font-size:10px; width:60px" />
					</td>
					<?php else : ?>
					<td colspan="2"></td>
					<?php endif; ?>
				</form>
				</tr>
				<?php endforeach; ?>
				</table>
		<?php
	}

	public function PreInsert() {
		global $eq2;
		$page = $_GET['page'] ?? "";

		if ($page == "npc_spells") {
			if (($_GET['list'] ?? "") != "new") {
				$_POST['spawn_npc_spells|on_spawn_cast'] = isset($_POST['spawn_npc_spells|on_spawn_cast']) ? 1 : 0;
				$_POST['spawn_npc_spells|on_aggro_cast'] = isset($_POST['spawn_npc_spells|on_aggro_cast']) ? 1 : 0;
				return;
			}

			$desc = $_POST['new_spell_list_desc'];
			$cat = $_POST['new_spell_list_cat'];

			$query = sprintf("INSERT INTO %s.spawn_npc_spell_lists (`description`, `category`) VALUES ('%s','%s')", ACTIVE_DB, $eq2->SQLEscape($desc), $eq2->SQLEscape($cat));
			$eq2->RunQuery(true, $query);
			$id = $eq2->db->sql_last_insert_id();

			header("Location: server.php?page=npc_spells&cat=".$cat."&list=".$id);
			exit;
		}
	}

	//redirects browser after delete
	public function PostDeletes(){
		global $eq2;
		$page = $_GET['page'] ?? "";
		
		if ($page == "lua_blocks")
		{
			header("Location: server.php?page=lua_blocks");
			exit;
		}
	}

	//redirects browser after insert
	public function PostInsert($insert_res) {
		global $eq2;

		$page = $_GET['page'] ?? "";
		if ($page == "groundspawns" && ($insert_res ?? 0) == 1) {
			//no idea who setup this table with 2 id fields..
			$new_gs_id = $eq2->db->sql_last_insert_id();
			$eq2->RunQuery(false, sprintf('UPDATE %s.groundspawns SET groundspawn_id = %s WHERE id = %s', ACTIVE_DB, $new_gs_id, $new_gs_id));
		}
		else if ($page == "collections" && isset($_POST['collections|collection_category|new']) && ($insert_res ?? 0) == 1) {
			$new_col_id = $eq2->db->sql_last_insert_id();
			$cat = $_POST['collections|collection_category|new'];
			if ($cat == "") $cat = "No Category";
			printf("<script>location.search='?page=collections&id=%s&cat=%s'</script>", $new_col_id, $cat);
			exit;
		}
		else if ($page == "recipe_comp") {
			if (isset($_GET['new'])) {
				$id = $eq2->db->sql_last_insert_id();
				header("Location: server.php?page=recipe_comp&id=".$id);
				exit;
			}
			else {
				$query = sprintf("UPDATE %s.recipe_comp_list SET bEmpty = 0 WHERE id = %s", ACTIVE_DB, $_GET['id']);
				$eq2->RunQuery(true, $query);
			}
		}
		else if ($page == "lua_blocks") {
			$id = $eq2->db->sql_last_insert_id();
			header("Location: server.php?page=lua_blocks");
			exit;
		}
		else if ($page == "loot_table" && ($_POST['table_name'] ?? "") == "loottable") {
			$id = $eq2->db->sql_last_insert_id();
			header("Location: server.php?page=loot_table&id=".$id);
			exit;
		}
		else if ($page == "recipes" && ($_POST['table_name'] ?? "") == "recipe") {
			$id = $eq2->db->sql_last_insert_id();
			header("Location: server.php?page=recipes&id=".$id);
			exit;
		}
		else if ($page == "editor_lists" && ($_POST['table_name'] ?? "") == "eq2list_values") {
			$id = $eq2->db->sql_last_insert_id();
			header("Location: server.php?page=editor_lists");
			exit;
		}
	}

	//redirects browser after update
	public function PostUpdate() {
		global $eq2;
		$page = $_GET['page'] ?? "";

		if ($page == "npc_spells") {
			if (isset($_POST['spawn_npc_spell_lists|category'])) {
				$cat = $_POST['spawn_npc_spell_lists|category'];
				if ($cat != $_GET['cat']) {
					$link = sprintf("server.php?page=npc_spells&cat=%s&list=%s", $cat, $_GET['list']);
					header("Location: ".$link);
					exit;
				}
			}
		}else if ($page == "lua_blocks") {
			header("Location: server.php?page=lua_blocks");
			exit;
		}
	}

	public function PreUpdate() {
		$page = $_GET['page'] ?? "";

		if ($page == "recipes") {
			if (isset($_POST['detailsSection'])) 
			{
			//Handle the bitmask boxes
			$tsClasses = 0;
			foreach($_POST as $key=>$val) 
			{
				$myArray = explode("|",$key);
				if (count($myArray) < 3) {
					//Orig values
					continue;
				}
				else if($myArray[1]=="ts_classes") 
				{
					$tsClasses |= 1 << intval($myArray[2]);
					$_POST[$key] = NULL; // delete form value so it doesn't repeat in update
				}
			}
			$_POST['recipe|ts_classes'] = $tsClasses;

			$_POST['recipe|can_commission'] = isset($_POST['recipe|can_commission']) ? 1 : 0;
			}
			elseif (isset($_POST['productsSection'])) {
				$bAllProducts = 1;
				if (!is_numeric($_POST['recipe|stage0_id']) || !!is_numeric($_POST['recipe|stage1_id'])
				|| !is_numeric($_POST['recipe|stage2_id']) || !is_numeric($_POST['recipe|stage3_id'])
				|| !is_numeric($_POST['recipe|stage4_id'])) {
					$bAllProducts = 0;
				}

				$_POST['recipe|bHaveAllProducts'] = $bAllProducts;
			}
		}
		else if ($page == "loot_table" && ($_POST['table_name'] ?? "") == "loottable") {
			$minCoin = $this->CombineCoinValue("min");
			$maxCoin = $this->CombineCoinValue("max");

			$_POST['loottable|mincoin'] = $minCoin;
			$_POST['loottable|maxcoin'] = $maxCoin;
		}
		else if ($page == "npc_spells") {
			$_POST['spawn_npc_spells|on_spawn_cast'] = isset($_POST['spawn_npc_spells|on_spawn_cast']) ? 1 : 0;
			$_POST['spawn_npc_spells|on_aggro_cast'] = isset($_POST['spawn_npc_spells|on_aggro_cast']) ? 1 : 0;
		}
	}

	public function PrintRecipeComponentRow($comp, $table, $idField, $qtyField) {
		global $eq2;
		$id = $comp[$idField] ?? 0;
		$qty = $comp[$qtyField] ?? 1;
		$query = sprintf("SELECT * FROM %s.recipe_comp_list WHERE id = %s", ACTIVE_DB, $id);
		$data = $eq2->RunQuerySingle($query);
		?>
		<tr class="RecipeDataRow" style="border-right:2px solid #3b271c;border-left:2px solid #3b271c"<? if ($id != 0 && $data['bEmpty']) echo " incomplete" ?>>
		<form method="post" name="<?php printf("Form|%s|%s", $table, $comp['id'])?>">
			<td>
				<input type="hidden" name="table_name" value="<?php echo $table?>"/>
				<input type="hidden" name="orig_id" value="<?php echo $comp['id']?>"/>
				&nbsp;id: <input style="width:80px" type="text" <?php printf("name=\"%s|%s\"", $table, $idField) ?> value="<?php echo $id ?>"/>
				<input type="hidden" name="orig_<?php echo $idField?>" value="<?php echo $id?>"/>
			</td>
			<td>
				&nbsp;qty: <input style="width:25px" type="text" <?php printf("name=\"%s|%s\"", $table, $qtyField) ?> value="<?php echo $qty ?>"/>
				<input type="hidden" name="orig_<?php echo $qtyField?>" value="<?php echo $qty?>"/>
			</td>
			<td width="200px">
				<?php if ($id != 0) {
                          printf("<a href=\"server.php?page=recipe_comp&id=%s&retid=%s&compname=%s\">%s</a>", $id, $_GET['id'], $data['name'], $data['name']);
				} 
				?>
			</td>
			<td align="right">
				<?php if (isset($comp['new'])) : ?>
					<input type="hidden" name="recipe_secondary_comp|index" value="<?=$comp['index']?>"/>
					<input type="hidden" name="recipe_secondary_comp|recipe_id" value="<?=$_GET['id']?>"/>
					<input type="image" src="../images/upload_arrow.png" height="16" name="InsertComponent" title="Insert" alt="Insert" />
				<?php else : ?>
					<input type="image" src="../images/save.png" height="16" name="UpdateComponent" title="Update" alt="Update" />
					<?php if ($table == "recipe_secondary_comp") :?>
					<input type="image" src="../images/cross.png" height="16" name="DeleteComponent" title="Delete" alt="Delete" onclick="return confirm('Are you sure you want to delete this?');" />
					<?php endif; ?>
				<?php endif; ?>
			</td>
		</form>
		</tr>
		<tr style="border-left:2px solid #3b271c;border-right:2px solid #3b271c"><td></br></td></tr>
		<?php
	}

	function PrintTSKnowledgeOptions($val) {
		global $eq2; ?>

		<option value="0">None</option>
		<?php foreach($eq2->eq2TSKnowledge as $name=>$id) : ?>
			<option value="<?=$id?>"<?php if ($id == $val) echo " selected"; ?>><?= $name?></option>
		<?php endforeach;
	}

	function PrintTSTechniqueOptions($val) {
		global $eq2; ?>

		<option value="0">None</option>
		<?php foreach($eq2->eq2TSTechniques as $name=>$id) : ?>
			<option value="<?=$id?>"<?php if ($id == $val) echo " selected"; ?>><?= $name?></option>
		<?php endforeach;
	}

	function PrintTSDeviceOptions($val) {
		global $eq2; ?>

		<option value="">None</option>
		<?php foreach($eq2->eq2TSDevices as $dev) : ?>
			<option value="<?=$dev?>"<?php if ($dev == $val) echo " selected"; ?>><?= $dev?></option>
		<?php endforeach;
	}

	function PrintRecipeForm($id) {
		global $eq2, $s;

		$query = sprintf("SELECT * FROM %s.recipe WHERE id = %s", ACTIVE_DB, $id);
		$r = $eq2->RunQuerySingle($query);
		?>
		<fieldset>
			<legend>Recipe</legend>
			<table style="background:#eee">
				<tr>
					<td colspan="2" align="center">
						<?php //Overview ?>
						<table>
							<tr>
							<td><?php printf("<img src=\"eq2Icon.php?type=item&id=%s\"/>", $r['icon']); ?></td>
							<td><strong style="font-size:200%"><?php echo $r['name'] ?></strong></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<?php //Components ?>
						<fieldset style="white-space:nowrap">
							<legend>Components</legend>
							<table class="RecipeDataTable">
								<tr style="border-top:2px solid #3b271c;border-left:2px solid #3b271c;border-right:2px solid #3b271c"><td align="right" colspan="4"><strong>Primary&nbsp;</strong></td></tr>
								<?php $s->PrintRecipeComponentRow($r, "recipe", "primary_comp_list", "primary_comp_qty"); ?>
								<tr style="border-top:2px solid #3b271c;border-left:2px solid #3b271c;border-right:2px solid #3b271c"><td align="right" colspan="4"><strong>Secondary&nbsp;</strong></td></tr>
								<?php
								$query = sprintf("SELECT * FROM %s.recipe_secondary_comp WHERE recipe_id = %s ORDER BY `index`", ACTIVE_DB, $id);
								$secComps = $eq2->RunQueryMulti($query);
								$secCount = 0;
								foreach ($secComps as $comp) {
									$secCount++;
									$s->PrintRecipeComponentRow($comp, "recipe_secondary_comp", "comp_list", "qty");
								}
								$newComp = array("id"=>0, "comp_list"=>0, "qty"=>1, "new"=>true, "index"=>$secCount);
								$s->PrintRecipeComponentRow($newComp, "recipe_secondary_comp", "comp_list", "qty");
								?>
								<tr style="border-top:2px solid #3b271c;border-left:2px solid #3b271c;border-right:2px solid #3b271c"><td align="right" colspan="4"><strong>Fuel&nbsp;</strong></td></tr>
								<?php $s->PrintRecipeComponentRow($r, "recipe", "fuel_comp_list", "fuel_comp_qty"); ?>
								<tr style="border-bottom: 2px solid #3b271c;border-left:2px solid #3b271c;border-right:2px solid #3b271c"><td></br></td></tr>
							</table>
						</fieldset>
					</td>
					<td valign="top" rowspan="2">
						<fieldset>
							<legend>Products</legend>
							<form method="post" name="ProductForm">
							<input type="hidden" name="table_name" value="recipe" />
							<input type="hidden" name="orig_id" value="<?=$id?>" />
							<input type="hidden" name="productSection" value="1"/>
							<input type="hidden" name="orig_bHaveAllProducts" value="<?=$r['bHaveAllProducts']?>"/>
							<table class="RecipeDataTable">
								<tr>
								<td>
								<table class="RecipeDataTable" valign="top">
									<tr><th></th><th colspan="2">Products</th><th colspan="2">By-Products</th></tr>
									<?php for ($i = 0; $i <= 4; $i++) : ?>
									<tr class="RecipeProductRow">
									<?php //additional for loop to do by-products with the same code ?>
									<?php for ($x = 0; $x < 2; $x++) : ?>
										<?php if ($x == 0) : ?>
										<td align="center" style="border-right:2px solid #3b271c">
											<?php printf("&nbsp;Stage %s&nbsp;", $i) ?>
										</td>
										<?php endif; ?>
										<?php 
											$byp = $x == 1 ? "byp_" : "";
											$stageID = sprintf("stage%s_%sid", $i, $byp);
											$stageQty = sprintf("stage%s_%sqty", $i, $byp);
										?>
										<td align="right">
										<table>
											<tr>
											<td align="right">id:</td>
											<td>
											<?php printf('<input style="width:80px" type="text" name="recipe|%s" value="%s"/>', $stageID, $r[$stageID]) ?>
											<?php printf('<input type="hidden" name="orig_%s" value="%s"/>', $stageID, $r[$stageID]) ?>
											</td>
											</tr>
											<tr>
											<td>qty:</td>
											<td><?php printf('<input style="width:25px" type="text" name="recipe|%s" value="%s"/>', $stageQty, $r[$stageQty]) ?>
												<?php printf('<input type="hidden" name="orig_%s" value="%s"/>', $stageQty, $r[$stageQty]) ?>
											</td></tr>
										</table>
										</td>
										<td align="center" style="border-right:2px solid #3b271c">
										<?php if ($r[$stageID] != 0) {
											$query = sprintf("SELECT name, icon, crafted, tier FROM %s.items WHERE id = %s", ACTIVE_DB, $r[$stageID]);
											$data = $eq2->RunQuerySingle($query);
											printf("<table>");
											printf('<tr><td align="center"><img src="eq2Icon.php?type=item&id=%s&tier=%s%s"/></td></tr>', $data['icon'], $data['tier'], $data['crafted'] != 0 ? "crafted" : "");
											printf('<tr><td align="center"><a href="items.php?id=%s">%s</a></td></tr>', $r[$stageID], $data['name']);
											printf("</table>");
										}
										?>
										</td>
									<?php endfor; ?>
									</tr>
									<?php endfor;?>
									<tr class="RecipeProductRow" style="border-top:0px">
									<td align="center" colspan="5" style="padding:5px">
									<input type="submit" name="cmd" value="Update" />
									</td>	
								</tr>
								</table>
								</td>
								</tr>
							</table>
							</form>
						</fieldset>
					</td>
				</tr>
				<tr>
					<td align="center">
						<?php //Details ?>
						<form method="post" name="FormRecipeDetails">
						<input type="hidden" name="detailsSection" value="1"/>
						<input type="hidden" name="table_name" value="recipe"/>
						<input type="hidden" name="orig_id" value="<?=$id?>"/>
						<fieldset>
							<legend>Details</legend>
						<table>
						<tr>
						<td>
							<? //Misc Values ?>
							<table>
								<tr>
									<td>
										<table cellspacing="5" style="text-align:right;white-space:nowrap">
											<tr>
											<td colspan="3" align="center">
											<label>name: </label>
											<input type="text" name="recipe|name" value="<?=$r['name']?>"/>
											<input type="hidden" name="orig_name" value="<?=$r['name']?>"/>
											</td>
											</tr>
											<tr>
											<td colspan="3" align="center">
											<table>
												<tr>
												<td>
												<label>desc: </label>
												</td>
												<td>
												<textarea style="resize:none;width:170px" name="recipe|description"><?=$r['description']?></textarea>
												<input type="hidden" name="orig_description" value="<?=$r['description']?>"/>
												</td>
												</tr>
											<table>
											</td>
											</tr>
											<tr align="center">
											<td>
											<label>level: </label><input style="width:25px" type="text" name="recipe|level" value="<?=$r['level']?>"/>
											<input type="hidden" name="orig_level" value="<?=$r['level']?>"/>
											</td>
											<td align="center">
											<label>skill_level: </label><input style="width:25px" type="text" name="recipe|skill_level" value="<?=$r['skill_level']?>"/>
											<input type="hidden" name="orig_skill_level" value="<?=$r['skill_level']?>"/>
											</td>
											<td>
											<label>icon: </label><input style="width:35px" type="text" name="recipe|icon" value="<?=$r['icon']?>"/>
											<input type="hidden" name="orig_icon" value="<?=$r['icon']?>"/>
											</td>
											</tr>
											<tr>
											<td colspan="3">
											<table>
												<tr>
												<td align="center">
											<div>
											<label>knowledge </label></br>
											<select name="recipe|knowledge"><?php $s->PrintTSKnowledgeOptions($r['knowledge'])?></select>
											<input type="hidden" name="orig_knowledge" value="<?=$r['knowledge']?>"/>
											</div>
												</td>
												<td align="center">
											<div>
											<label>technique</label></br>
											<select name="recipe|technique"><?php $s->PrintTSTechniqueOptions($r['technique'])?></select>	
											<input type="hidden" name="orig_technique" value="<?=$r['technique']?>"/>
											</div>
												</td>
												</tr>
											</table>
											</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="3" align="center">
										<label>device: </label>
										<select name="recipe|bench"><?php $s->PrintTSDeviceOptions($r['bench'])?></select>
										<input type="hidden" name="orig_bench" value="<?= $r['bench']?>"/>
									</td>
								</tr>
								<tr>
									<td colspan="3" align="center">
										<table>
											<tr>
										<td><label>can_commission: </label></td>
										<td>
										<?php $eq2->GenerateBlueCheckbox("recipe|can_commission", $r['can_commission'])?>
										<input type="hidden" name="orig_can_commission" value="<?= $r['can_commission']?>"/>
										</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
								<td>
						<fieldset style="width:min-content"><legend>Classes</legend>
							<div id="itemTSClassesGrid">
							<?php $tsClasses = $r['ts_classes'] ?>
							<input type="hidden" name="orig_ts_classes" value="<?=$tsClasses?>" />
							<?php foreach($eq2->eq2ArchetypeSortedTSClasses as $arch=>$classes) : ?>
							<?php foreach ($classes as $classID=>$className) : ?>
							<?php if ($className == "Unskilled") : ?>
							<strong style="background:#EEDFB8">value: <?= $tsClasses ?></strong>
							<div></div>
							<?php endif; ?>
							<table>
								<tr>
									<td align="right"><?= $className ?>:</td>
									<td>
									<?php 
									$eq2->GenerateBlueCheckbox(sprintf('recipe|ts_classes|%s', $classID),  $tsClasses & (1 << $classID));
									?>
									</td>
								</tr>
								</table>
								<?php endforeach; ?>
								<?php endforeach; ?>
								</div>
								</fieldset>
								</td>
								</tr>
							</table>
						</td>
						</tr>
						<tr>
							<td align="center"><input type="submit" name="cmd" value="Update"/></td>
						</tr>
						</table>
						</fieldset>
						</form>
						</tr>
					</td>
				</tr>
			</table>
		</fieldset>
		<?php
	}

	function PrintCompListRow($item, $withForm = true) {
		global $eq2, $eq2Items;
		$id = $item['item_id'] ?? null;
		if ($id != null) {
			$data = $eq2->RunQuerySingle(sprintf("SELECT icon, tier, crafted, name FROM %s.items WHERE id = %s", ACTIVE_DB, $id));
		}
?>
        <?php if ($withForm) { ?>
		    <form method="post" name="CompListForm<?=$id?>">
        <?php } ?>
            <tr>
                <td>
                    <?php if ($withForm) { ?>
                        <input type="hidden" name="orig_id" value="<?=$item['id']?>">
                        <input type="hidden" name="table_name" value="recipe_comp_list_item">
                        <input type="text" name="recipe_comp_list_item|item_id" style="width:80px" required value="<?php echo $id; ?>">
                        <input type="hidden" name="orig_item_id" value="<?=$id?>">
                    <?php } ?>
                </td>
                <td>
                    <?php if ($id != null) printf('<img src="%s"/>', $eq2Items->GetItemIconLink($data)) ?>
                </td>
                <td>
                    <?php if ($id != null) printf('<a href="items.php?id=%s">%s</a>', $id, $data['name']) ?>
                </td>
                <td align="center">
                    <?php if (isset($item['new']) && $withForm) { ?>
                        <input type="hidden" name="recipe_comp_list_item|comp_list" value="<?=$item['comp_list']?>">
                        <input type="submit" name="cmd" value="Insert">
                    <?php } else { ?>
                        <?php if ($withForm) { ?>
                            <input type="submit" name="cmd" value="Update">
                        <?php } ?>

                        <?php if (!empty($id)) { ?>
                            <input type="submit" name="cmd" value="Delete" onclick="return confirm('Are you sure you want to delete this?');">
                        <?php } ?>
                    <?php } ?>
                </td>
            </tr>
		</form>
		<?php
	}
	function PrintCompListRowClass($item) {
		global $eq2, $eq2Items;
		$id = $item['item_id'] ?? null;
		if ($id != null) {
			$data = $eq2->RunQuerySingle(sprintf("SELECT icon, tier, crafted, name FROM %s.items WHERE id = %s", ACTIVE_DB, $id));
		}
        ?>
		<form method="post" name="CompListForm<?=$id?>">
		<tr>
			<td>
				<input type="hidden" name="orig_id" value="<?=$item['id']?>"/>
				<input type="hidden" name="table_name" value="recipe_comp_list_item"/>
				<input type="text" name="recipe_comp_list_item|item_id" style="width:80px" value="<?=$id?>"/>
				<input type="hidden" name="orig_item_id" value="<?=$id?>"/>
			</td>
			<td>
				<?php if ($id != null) printf('<img src="%s"/>', $eq2Items->GetItemIconLink($data)) ?>
			</td>
			<td>
				<?php if ($id != null) printf('<a href="items.php?id=%s">%s</a>', $id, $data['name']) ?>
			</td>
			
		</tr>
		</form>
		<?php
    }
	function PrintLootTableRow($data, $options, $rowcnt) {
		$id = $data['id'] ?? null;

		$return_string = "";
        $strOffset = str_repeat("\x20",22);

        if($options == "simple"){
            $return_string .= $strOffset . "  <tr>\n";
            $return_string .= $strOffset . "    <td align='center'>" .  $rowcnt . "\n";
            $return_string .= $strOffset . "      <input type='checkbox' name='chkVal!" . $data['id'] . "' id='loottable' value='" . $data['id'] . "'>\n";
            $return_string .= $strOffset . "    </td>\n";
            $return_string .= $strOffset . "    <td align='center'><input type='radio' name='viewTrigger' value='server.php?page=" . $_GET['page'] . "&searchtype=" . $_GET['searchtype'] . "&z_id=" . (!empty($_GET['z_id'])?$_GET['z_id']: '') . "&e_list=" . (!empty($_GET['e_list'])?$_GET['e_list']:'') . "&id=" . $id . "' onchange='dosub(this.value)'/>\n";
            $return_string .= $strOffset . "    <td>" . $id . "</td>\n";
            $return_string .= $strOffset . "    <td>" . $data['name'] . "</td>\n";
            $return_string .= $strOffset . "    <td>" . $data['maxlootitems'] . "</td>\n";
            $return_string .= $strOffset . "    <td>" . $data['lootdrop_probability'] . "</td>\n";
            $return_string .= $strOffset . "    <td>" . $data['coin_probability'] . "</td>\n";
            $mcoin = $this->SplitCoinValue($data['mincoin']);
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('min', 'Plat', $mcoin['Plat'], 'simple');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('min', 'Gold', $mcoin['Gold'], 'simple');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('min', 'Silver', $mcoin['Silver'], 'simple');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('min', 'Bronze', $mcoin['Bronze'], 'simple');
            
            $mcoin = $this->SplitCoinValue($data['maxcoin']);
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('max', 'Plat', $mcoin['Plat'], 'simple');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('max', 'Gold', $mcoin['Gold'], 'simple');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('max', 'Silver', $mcoin['Silver'], 'simple');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('max', 'Bronze', $mcoin['Bronze'], 'simple');
        }else{
            $return_string .= $strOffset . "<form method='post' name='LootTableForm'>\n";
            $return_string .= $strOffset . "  <tr>\n";
            $return_string .= $strOffset . "    <td align='center'><input type='radio' name='viewTrigger' value='server.php?page=" . (!empty($_GET['page'])?$_GET['page']:'') . "&searchtype=" . (!empty($_GET['searchtype'])?$_GET['searchtype']:'') . "&z_id=" . (!empty($_GET['z_id'])?$_GET['z_id']: '') . "&e_list=" . $_GET['e_list'] . "&id=" . $id . "' onchange='dosub(this.value)'/>\n";
            $return_string .= $strOffset . "    <td>\n";
            $return_string .= $strOffset . "      " . $id . "\n";
            $return_string .= $strOffset . "    </td>\n";
            $return_string .= $strOffset . "    <td>\n";
            $return_string .= $strOffset . "      <input type='hidden' name='orig_id' value='" . $id . "'/>\n";
            $return_string .= $strOffset . "      <input type='hidden' name='table_name' value='loottable'/>\n";
            $return_string .= $strOffset . "      <input type='text' name='loottable|name' style='width:200px' value='" . $data['name'] . "'/>\n";
            $return_string .= $strOffset . "      <input type='hidden' name='orig_name' value='" . $data['name'] . "'/>\n";
            $return_string .= $strOffset . "    </td>\n";
            $return_string .= $strOffset . "    <td>\n";
            $return_string .= $strOffset . "      <input type='text' name='loottable|maxlootitems' style='width:35px' value='" . $data['maxlootitems'] . "'/>\n";
            $return_string .= $strOffset . "      <input type='hidden' name='orig_maxlootitems' value='" . $data['maxlootitems'] . "'/>\n";
            $return_string .= $strOffset . "    </td>\n";
            $return_string .= $strOffset . "    <td>\n";
            $return_string .= $strOffset . "      <input type='text' name='loottable|lootdrop_probability' style='width:35px' value='" . $data['lootdrop_probability'] . "'/>\n";
            $return_string .= $strOffset . "      <input type='hidden' name='orig_lootdrop_probability' value='" . $data['lootdrop_probability'] . "'/>\n";
            $return_string .= $strOffset . "    </td>\n";
            $return_string .= $strOffset . "    <td>\n";
            $return_string .= $strOffset . "      <input type='text' name='loottable|coin_probability' style='width:35px' value='" . $data['coin_probability'] . "'/>\n";
            $return_string .= $strOffset . "      <input type='hidden' name='orig_coin_probability' value='" . $data['coin_probability'] . "'/>\n";
            $return_string .= $strOffset . "      <input type='hidden' name='orig_mincoin' value='" . $data['mincoin'] . "'/>\n";
            $return_string .= $strOffset . "      <input type='hidden' name='orig_maxcoin' value='" . $data['maxcoin'] . "'/>\n";
            $return_string .= $strOffset . "    </td>\n";

            $mcoin = $this->SplitCoinValue($data['mincoin']);
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('min', 'Plat', $mcoin['Plat'], '');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('min', 'Gold', $mcoin['Gold'], '');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('min', 'Silver', $mcoin['Silver'], '');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('min', 'Bronze', $mcoin['Bronze'], '');
            
            $mcoin = $this->SplitCoinValue($data['maxcoin']);
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('max', 'Plat', $mcoin['Plat'], '');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('max', 'Gold', $mcoin['Gold'], '');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('max', 'Silver', $mcoin['Silver'], '');
            $return_string .= $strOffset . $this->PrintLootTableCoinCell('max', 'Bronze', $mcoin['Bronze'], '');
                
            $return_string .= $strOffset . "    <td align='center' style='white-space:nowrap'>\n";
            
            if (isset($data['new'])){
                $return_string .= $strOffset . "      <input type='submit' name='cmd' value='Insert'/>\n";
            }else{
                $return_string .= $strOffset . "      <input type='submit' name='cmd' value='Update'/>\n";
                $return_string .= $strOffset . "      <input type='submit' name='cmd' value='Delete' onclick='return confirm('Are you sure you want to delete this?');'/>\n";
            }
            $return_string .= $strOffset . "    </td>\n";
            $return_string .= $strOffset . "</form>\n";
            $return_string .= $strOffset . "<form>\n";
            $return_string .= $strOffset . "    <td>\n";
            $return_string .= $strOffset . $this->PrintAvailableLists('select', $_GET['page'], 'assign', $id);
            $return_string .= $strOffset . "    </td>\n";
            $return_string .= $strOffset . "  </tr>\n";
            $return_string .= $strOffset . "</form>\n";
        }
        return($return_string);
	}

    	function PrintLootDropRow($data) {
		global $eq2Items;
		$id = $data['id'] ?? null;
		
        $return_string = "";
        $strOffset = str_repeat("\x20",22);

		$return_string .= $strOffset . "<tr>\n";
		$return_string .= $strOffset . "<form method='post' name='LootDropForm|" . $id . "'>\n";
        $return_string .= $strOffset . "<td>\n";
        $return_string .= $strOffset . "<input type='hidden' name='orig_id' value='" . $id . "'/>\n";
        $return_string .= $strOffset . "<input type='hidden' name='table_name' value='lootdrop'/>\n";
        $return_string .= $strOffset . "<input type='text' name='lootdrop|item_id' style='width:80px' value='" . $data['item_id'] . "'/>\n";
        $return_string .= $strOffset . "<input type='hidden' name='orig_item_id' value='" . $data['item_id'] . "'/>\n";
        $return_string .= $strOffset . "</td>\n";
        $return_string .= $strOffset . "<td>\n";
		
        if(!isset($data['new'])){
            $return_string .= $strOffset . "<table>\n";
            $return_string .= $strOffset . "<tr>\n";
            $return_string .= $strOffset . "<td>\n";
            $return_string .= $strOffset . "<img src='" . $eq2Items->GetItemIconLink($data) . "'/>\n";
            $return_string .= $strOffset . "</td>\n";
            $return_string .= $strOffset . "<td>\n";
            $return_string .= $strOffset . "<a href='items.php?id=" . $data['item_id'] . "'>" . $data['item_name'] . "</a>\n";
            $return_string .= $strOffset . "</td>\n";
            $return_string .= $strOffset . "</tr>\n";
            $return_string .= $strOffset . "</table>\n";
		}else{
            $return_string .= $strOffset . "<strong>New Row</strong>\n";
		}
            $return_string .= $strOffset . "</td>\n";
			$return_string .= $strOffset . "<td>\n";
            $return_string .= $strOffset . "<input type='text' name='lootdrop|probability' style='width:60px' value='" . $data['probability'] . "'/>\n";
            $return_string .= $strOffset . "<input type='hidden' name='orig_probability' value='" . $data['probability'] . "'/>\n";
			$return_string .= $strOffset . "<input type='hidden' name='lootdrop|loot_table_id' value='" . $_GET['id'] . "'/>\n";
			$return_string .= $strOffset . "</td>\n";
			$return_string .= $strOffset . "<td>\n";
            $return_string .= $strOffset . "<input type='text' name='lootdrop|item_charges' style='width:35px' value='" . $data['item_charges'] . "'/>\n";
            $return_string .= $strOffset . "<input type='hidden' name='orig_item_charges' value='" . $data['item_charges'] . "'/>\n";
			$return_string .= $strOffset . "</td>\n";
			$return_string .= $strOffset . "<td align='center'>\n";
            $return_string .= $strOffset . "<input type='text' name='lootdrop|equip_item' style='width:35px' value='" . $data['equip_item'] . "'/>\n";
            $return_string .= $strOffset . "<input type='hidden' name='orig_equip_item' value='" . $data['equip_item'] . "'/>\n";
			$return_string .= $strOffset . "</td>\n";
			$return_string .= $strOffset . "<td align='center'>\n";
            $return_string .= $strOffset . "<input type='text' name='lootdrop|no_drop_quest_completed' style='width:35px' value='" . $data['no_drop_quest_completed'] . "'/>\n";
            $return_string .= $strOffset . "<input type='hidden' name='orig_no_drop_quest_completed' value='" . $data['no_drop_quest_completed'] . "'/>\n";
			$return_string .= $strOffset . "</td>\n";
			$return_string .= $strOffset . "<td align='center' style='white-space:nowrap'>\n";
		if(isset($data['new'])){
            $return_string .= $strOffset . "<input type='submit' name='cmd' value='Insert'/>\n";
        }else{
            $return_string .= $strOffset . "<input type='submit' name='cmd' value='Update'/>\n";
            $return_string .= $strOffset . "<input type='submit' name='cmd' value='Delete'/>\n";
        }
        $return_string .= $strOffset . "</td>\n";
		$return_string .= $strOffset . "</form>\n";
		$return_string .= $strOffset . "</tr>\n";

        Return($return_string);
	}

	function PrintLootTableCoinCell($prefix, $coin, $val, $disp) {
        
        $return_string = "";
        $strOffset = str_repeat("\x20",22);
		
        if($disp == "simple"){
            $return_string .= $strOffset . "<td style='padding:0px'>\n";
            $return_string .= $strOffset . "  <table class='ContrastTable' width='100%'>\n";
            $return_string .= $strOffset . "    <tr>\n";
            $return_string .= $strOffset . "      <td style='padding:0px'><img src='../images/coin" . $coin . ".png' height='16px' width='16px'/></td>\n";
            $return_string .= $strOffset . "      <td style='padding:0px'>" . $val ."</td>\n";
            $return_string .= $strOffset . "    </tr>\n";
            $return_string .= $strOffset . "  </table>\n";
            $return_string .= $strOffset . "</td>\n";
        }else{
            $return_string .= $strOffset . "<td style='padding:0px'>\n";
            $return_string .= $strOffset . "  <table class='ContrastTable' width='100%'>\n";
            $return_string .= $strOffset . "    <tr>\n";
            $return_string .= $strOffset . "      <td style='padding:0px'><img src='../images/coin" . $coin . ".png' height='16px' width='16px'/></td>\n";
            $return_string .= $strOffset . "      <td style='padding:0px'><input type='text' name='" . $prefix.$coin ."' style='width:25px' value='" . $val ."'/></td>\n";
            $return_string .= $strOffset . "    </tr>\n";
            $return_string .= $strOffset . "  </table>\n";
            $return_string .= $strOffset . "</td>\n";
        }

        Return($return_string);
	}

	function SplitCoinValue($val) {
		$ret = array();
		$val = intval($val);
		$ret['Bronze'] = $val % 100;
		$ret['Silver'] = intdiv($val % 10000, 100);
		$ret['Gold'] = intdiv($val % 1000000, 10000);
		$ret['Plat'] = intdiv($val, 1000000);
		return $ret;
	}

	function CombineCoinValue($prefix) {
		$b = $_POST[$prefix."Bronze"];
		$s = $_POST[$prefix."Silver"];
		$g = $_POST[$prefix."Gold"];
		$p = $_POST[$prefix."Plat"];
		return $p * 1000000 + $g * 10000 + $s * 100 + $b;
	}

	function PrintLootGlobalRow($data) {
		$id = $data['id'] ?? 0;
		?>
		<tr>
		<form method="post" name="LootGlobalForm|<?=$id?>">
			<td>
				<input type="hidden" name="orig_id" value="<?=$id?>"/>
				<input type="hidden" name="table_name" value="loot_global"/>
				<input type="hidden" name="orig_type" value="<?=$data['type']?>"/>
				<select id="lgtype|<?=$id?>" name="loot_global|type" onchange="LootGlobalTypeChange(this, '<?=$id?>')">
					<option value="Racial"<?php if ($data['type'] == "Racial") echo " selected" ?>>Racial</option>
					<option value="Zone"<?php if ($data['type'] == "Zone") echo " selected" ?>>Zone</option>
					<option value="Level"<?php if ($data['type'] == "Level") echo " selected" ?>>Level</option>
				</select>
			</td>
			<td>
				<label id="v1Tag|<?=$id?>"></label>
				<input type="text" style="width:50px" id="v1|<?=$id?>" name="loot_global|value1" value="<?=$data['value1']?>"/>
				<input type="hidden" name="orig_value1" value="<?=$data['value1']?>"/>
			</td>
			<td>
				<input type="text" style="width:80px" name="loot_global|loot_table" value="<?=$data['loot_table']?>"/>
				<input type="hidden" name="orig_loot_table" value="<?=$data['loot_table']?>"/>
			</td>
			<td>
				<label id="v2Tag|<?=$id?>"></label>
				<input type="text" style="width:50px" id="v2|<?=$id?>" name="loot_global|value2" value="<?=$data['value2']?>"/>
				<input type="hidden" name="orig_value2" value="<?=$data['value2']?>"/>
			</td>
			<td>
				<label id="v3Tag|<?=$id?>"></label>
				<input type="text" style="width:50px" id="v3|<?=$id?>" name="loot_global|value3" value="<?=$data['value3']?>"/>
				<input type="hidden" name="orig_value3" value="<?=$data['value3']?>"/>
			</td>
			<td>
				<label id="v4Tag|<?=$id?>"></label>
				<input type="text" style="width:50px" id="v4|<?=$id?>" name="loot_global|value4" value="<?=$data['value4']?>"/>
				<input type="hidden" name="orig_value4" value="<?=$data['value4']?>"/>
			</td>
			<td align="center" style="white-space:nowrap">	
			<?php if (isset($data['new'])) : ?>			
				<input type="submit" name="cmd" value="Insert"/>
			<?php else : ?>
				<input type="submit" name="cmd" value="Update"/>
				<input type="submit" name="cmd" value="Delete"/>
			<?php endif; ?>
			<script>LootGlobalTypeChange(document.getElementById("<?php printf("lgtype|%s", $id) ?>"), '<?=$id?>')</script>
			</td>
		</form>
		</tr>
		<?php
	}

	function PrintNewRecipeForm() {
		?>
		<fieldset style="width:max-content">
		<legend>New Recipe</legend>
		<form name="FormNewRecipe" method="post">
		<table>
			<tr>
				<td align="center">
					<strong style="font-size:large">Add A New Recipe</strong>
				</td>
			</tr>
			<tr>
				<td>
					<label>name:</label>
					<input type="text" name="recipe|name"/>
					<input type="hidden" name="table_name" value="recipe"/>
				</td>
			</tr>
			<tr>
				<td align="center">
					<input type="submit" name="cmd" value="Insert"/>
				</td>
			</tr>
		</table>
		</form>
		</fieldset>
	<?php
	}

	function PrintNewRecipeCompForm() {
		?>
		<fieldset style="width:max-content">
		<legend>New Component List</legend>
		<form name="FormNewRecipeComp" method="post">
		<table>
			<tr>
				<td align="center">
					<strong style="font-size:large">Add New Component List</strong>
				</td>
			</tr>
			<tr>
				<td>
					<label>name:</label>
					<input type="text" name="recipe_comp_list|name"/>
					<input type="hidden" name="table_name" value="recipe_comp_list"/>
				</td>
			</tr>
			<tr>
				<td align="center">
					<input type="submit" name="cmd" value="Insert"/>
				</td>
			</tr>
		</table>
		</form>
		</fieldset>
	<?php
	}

    function PrintAvailableLists($disp, $list_type, $action, $list_id){
		global $eq2;
		$strOffset = str_repeat("\x20",28);
		$return_string = "";

		if($disp == "table")
		{
			if($list_id == 'owner_only')
			{
				$query = "SELECT id, name, user_id, shared, list_type FROM eq2lists WHERE user_id='" . $eq2->userdata['id'] . "';";
				$data = $eq2->RunQueryMulti($query);
				
                $return_string  .= $strOffset . "<table cellpadding='5' id='EditorTable'>\n";
				$return_string  .= $strOffset . "  <tr style='font-weight:bold'>\n";
				$return_string  .= $strOffset . "    <th width='20'>ID</th>\n";
				$return_string  .= $strOffset . "    <th width='200'>List Name</th>\n";
				$return_string  .= $strOffset . "    <th width='40'>Shared</th>\n";
				$return_string  .= $strOffset . "    <th width='60'>Type</th>\n";
				$return_string  .= $strOffset . "    <th width='100'>Owner</th>\n";
				$return_string  .= $strOffset . "    <th width='100'>Actions</th>\n";
				$return_string  .= $strOffset . "  </tr>\n";
			}elseif($list_id == 'shared_only'){
				$query = "SELECT id, name, user_id, shared, list_type FROM eq2lists WHERE user_id<>'" . $eq2->userdata['id'] . "' AND shared = 1;";
				$data = $eq2->RunQueryMulti($query);
				
                $return_string .= $strOffset . "<table cellpadding='5' id='EditorTable'>\n";
				$return_string .= $strOffset . "  <tr style='font-weight:bold'>\n";
				$return_string .= $strOffset . "    <th width='20'>ID</th>\n";
				$return_string .= $strOffset . "    <th width='200'>List Name</th>\n";
				$return_string .= $strOffset . "    <th width='40'>Shared</th>\n";
				$return_string .= $strOffset . "    <th width='60'>Type</th>\n";
				$return_string .= $strOffset . "    <th width='100'>Owner</th>\n";
				$return_string .= $strOffset . "    <th width='100'>Actions</th>\n";
				$return_string .= $strOffset . "  </tr>\n";
			}
	
			foreach($data as $row) 
			{
				$return_string  .= $strOffset . "<form>\n";
				$query_listtype = "SELECT type_name FROM eq2list_types WHERE id = " . $row['list_type'];
				$data_listype = $eq2->RunQuerySingle($query_listtype);
				
				$return_string .= $strOffset . "  <tr>\n";
				$return_string .= $strOffset . "    <td>\n";
                $return_string .= $strOffset .        $row['id'] . "\n";
                $return_string .= $strOffset . "    </td>\n";
				$return_string .= $strOffset . "    <td>\n";
                $return_string .= $strOffset .        $row['name'] . "\n";
                $return_string .= $strOffset . "</td>\n";
				
				$YesNo = ($row['shared'] == 1 )? "Yes" : "No";
				$return_string .= $strOffset . "    <td>" . $YesNo . "</td>\n";
				$return_string .= $strOffset . "    <td>" . $data_listype['type_name'] . "</td>\n";
				
				$query_username = "SELECT username FROM users WHERE id = " . $row['user_id'];
				$data_username = $eq2->RunQuerySingle($query_username);
				
				$return_string .= $strOffset . "    <td>" . $data_username['username'] . "</td>\n";
				
				$return_string .= $strOffset . "    <td>\n";
				
				if ($eq2->userdata['id'] == $row['user_id']){
					$return_string .= $strOffset . "      <a href='server.php?page=editor_lists&e_list=" . $row['id'] . "&action=view'>EDIT</a>\n";

				}else{
					$return_string .= $strOffset . "      <a href='server.php?page=editor_lists&e_list=" . $row['id'] . "&action=view'>View</a>\n";
				}
				
				$return_string .= $strOffset . "    </td>\n";
				$return_string .= $strOffset . "  </tr>\n";
				$return_string .= $strOffset . "</form>\n";
				
			}
			$return_string .= $strOffset . "</table>\n";
		}elseif($disp == "editform"){

			//TOP FORM HEADER
			$return_string .= $strOffset . "  <form method='post' name='multiForm|EditList'>\n";
			$return_string .= $strOffset . "    <tr style='font-weight:bold'>\n";
			$return_string .= $strOffset . "      <th width='50'>ID</th>\n";
			$return_string .= $strOffset . "      <th width='200'>List Name</th>\n";
			$return_string .= $strOffset . "      <th width='40'>Shared</th>\n";
			$return_string .= $strOffset . "      <th width='60'>Type</th>\n";
			$return_string .= $strOffset . "      <th width='100'>Owner</th>\n";
			$return_string .= $strOffset . "      <th width='150'>Actions</th>\n";
			$return_string .= $strOffset . "    </tr>\n";
;
		
			//QUERY FOR SINGLE LIST
			$query = "SELECT id, name, user_id, shared, list_type FROM eq2lists WHERE id=" . $_GET['e_list'];
			$data = $eq2->RunQuerySingle($query);
            if($data)
            {
			
                //QUERY FOR NAME OF LIST TYPE
                $query_listtype = "SELECT type_name FROM eq2list_types WHERE id = " . $data['list_type'];
                $data_listype = $eq2->RunQuerySingle($query_listtype);
                $return_string .= $strOffset . "    <tr>\n";
                $return_string .= $strOffset . "      <td>" . $data['id'] . "\n";
                $return_string .= $strOffset . "        <input type='hidden' name='eq2lists|id' value='" . $data['id'] . "'/></td>\n";
                $return_string .= $strOffset . "        <input type='hidden' name='orig_id' value='" . $data['id'] . "'/></td>\n";
                $return_string .= $strOffset . "      <td>\n";
                $return_string .= $strOffset . "        <input type='text' name='eq2lists|name' value='" . $data['name'] . "'/>\n";
                $return_string .= $strOffset . "        <input type='hidden' name='orig_name' value='" . $data['name'] . "'/>\n";
                $return_string .= $strOffset . "      </td>\n";
                
                $YesNo = ($data['shared'] == 1 )? "Yes" : "No";
                if($data['shared'] == 1)
                {
                    $selYes = " selected ";
                    $selNo = "";
                }else{
                    $selYes = "";
                    $selNo = " selected ";
                }

                $return_string .= $strOffset . "      <td>\n";
                $return_string .= $strOffset . "        <select name='eq2lists|shared'>\n";
                $return_string .= $strOffset . "          <option value='1'"  . $selYes . ">Yes</option>\n";
                $return_string .= $strOffset . "          <option value='0'"  . $selNo . ">No</option>\n";
                $return_string .= $strOffset . "        </select>\n";
                $return_string .= $strOffset . "        <input type='hidden' name='orig_shared' value='" . $data['shared'] . "'/>\n";
                $return_string .= $strOffset . "      </td>\n";
                $return_string .= $strOffset . "      <td>" . $data_listype['type_name'] . "</td>\n";
                
                //QUERY TO GET USERNAME ASSIGNED TO LIST
                $query_username = "SELECT username FROM users WHERE id = " . $data['user_id'];
                $data_username = $eq2->RunQuerySingle($query_username);

                $return_string .= $strOffset . "      <td>" . $data_username['username'] . "</td>\n";
                
                $return_string .= $strOffset . "      <td>\n";
                if ($eq2->userdata['id'] == $data['user_id']){
                    $return_string .= $strOffset . "        <input type='hidden' name='table_name' value='eq2lists' />\n";
                    $return_string .= $strOffset . "        <input type='hidden' name='idx_name' value='id' />\n";
                    $return_string .= $strOffset . "        <input type='submit' name='cmd' value='Update'>\n";
                    $return_string .= $strOffset . "        <input type='submit' name='cmd' value='Delete'>\n";
                }
                $return_string .= $strOffset . "      </td>\n";
                $return_string .= $strOffset . "    </tr>\n";
                $return_string .= $strOffset . "  </table>\n";
                $return_string .= $strOffset . "</form>\n";

                $query2 = "SELECT id, list_id, value";
                $query2 .= " FROM eq2list_values ";
                $query2 .= " WHERE list_id=" . $_GET['e_list'];
                $data2 = $eq2->RunQueryMulti($query2);

                $return_string .= $strOffset . "<table cellpadding='5' id='EditorTable'>\n";
                $return_string .= $strOffset . "<tr>\n";
                $return_string .= $strOffset . "  <th>List ID</th>\n";
                $return_string .= $strOffset . "  <th>LootTable ID</th>\n";
                $return_string .= $strOffset . "  <th>Loot Table Name</th>\n";
                $return_string .= $strOffset . "  <th colspan='4'>Action</th>\n";
                $return_string .= $strOffset . "</tr>\n";

                foreach($data2 as $row) 
                {
                    $return_string .= $strOffset . "<form method='post' name='multiForm|RemoveLootTableFromList'>\n";
                    $return_string .= $strOffset . "  <tr>\n";
                    $return_string .= $strOffset . "    <td>" . $row['id'] . "<input type='hidden' name='orig_id' value='" . $row['id'] . "'></td>\n";

                    $query_loottable_name = "SELECT id, name FROM `" . ACTIVE_DB . "`.`loottable` WHERE id = " . $row['value'];
                    $data_loottable_name = $eq2->RunQuerySingle($query_loottable_name);
                    $return_string .= $strOffset . "    <td>" . $data_loottable_name['id'] . "</td>\n";
                    $return_string .= $strOffset . "    <td>" . $data_loottable_name['name'] . "</td>\n";
                    
                    $return_string .= $strOffset . "    <td colspan='3'>\n";
                    if ($eq2->userdata['id'] == $data['user_id'])
                    {
                        $return_string .= $strOffset . "        <input type='hidden' name='table_name' value='eq2list_values' />\n";
                        $return_string .= $strOffset . "        <input type='hidden' name='idx_name' value='id' />\n";
                        $return_string .= $strOffset . "        <input type='submit' name='cmd' value='Delete'>\n";
                    }
                    $return_string .= $strOffset . "    </td>\n";
                    $return_string .= $strOffset . "  </tr>\n";
                    $return_string .= $strOffset . "</form>\n";
                    
                }
			    $return_string .= $strOffset . "</table>\n";
            }
		//DISP = SELECT MEANS WERE RETURNING A FORM INPUT OF SELECT TYPE
		}elseif($disp == "select"){
			//ACTION IS ASSIGNMULTI WHICH IS A SPECIAL CASE WHERE WE PUT ALL ROWS IN A SINGLE FORM ELEMENT
            if($action == 'assignMulti'){
			    $return_string .= $strOffset . "<select name='e_list_id'>\n";
			//ALL OTHER ACIONS UNDER DISP=SELECT PRINT A SELECT WITH A ONCHANGE ACTION
            }else{
                $return_string .= $strOffset . "<select name='loottable_list' onchange='dosub(this.options[this.selectedIndex].value)'>\n";
            }
			$return_string .= $strOffset . "  <option value=''>Select a List</option>\n";

			$query = "SELECT id, name, user_id, shared, list_type FROM eq2lists WHERE user_id='" . $eq2->userdata['id'] . "' AND list_type=(SELECT id FROM eq2list_types WHERE type_value='" . $list_type . "') OR shared=1;";
			$data = $eq2->RunQueryMulti($query);
	
			//HERE WE START TO BUILD THE OPTION ELEMENTS
			////IF WE ALLOW ASSIGN THEN WE NEED A TOP ROW TO ASSIGN THE ENTRY TO A NEW LIST
			if($action == 'assign')
			{
				$return_string .= $strOffset .  "  <option value='server.php?page=editor_lists&action=new&type=loot_table&id=" . $list_id . "'>Add to(New List)</option>\n";
			}
			////HERE WE START TO BUILD THE ROWS BASED ON THE ARRAY SENT TO THIS FUNCTION
			foreach($data as $row) 
			{
				//DROPDOWN FOR INSIDE THE LOOTTABLE LIST
				if($action == 'assign')
				{
					if($row['user_id'] == $eq2->userdata['id']){
						if($_GET['e_list'] == $row['id'])
						{
							//$return_string .= $strOffset .  "  <option value='server.php?page=editor_lists&searchtype=list&action=remove&e_list=" . $_GET['z_id'] . "&id=" . $row['id'] . "'>" . $row['name'] . "(Remove)</option>\n";
						}else{
							$return_string .= $strOffset .  "  <option value='server.php?page=editor_lists&searchtype=list&action=add&e_list=" . $row['id'] . "&id=" . $list_id . "'>" . $row['name'] . "(Add to)</option>\n";
						}
					}
				//DROPDOWN FOR THE SPECIAL "ALL LOOTABLES" CASE
                }elseif($action == 'assignMulti'){
                    $isSelected = "";
                    if(isset($_GET['e_list']))
                    {
                        
					    if($_GET['e_list'] == $row['id'])
					    {
    						$isSelected = "selected";
                        }
					}
						$return_string .= $strOffset .  "  <option value='" . $row['id'] . "' " . $isSelected . ">" . $row['name'] . "</option>\n";

                //DROPDOWN FOR TOP LEVEL SEARCH
				}else{
                    $isSelected = "";
                    if(isset($_GET['e_list']))
                    {
                        
					    if($_GET['e_list'] == $row['id'])
					    {
	    					$isSelected = "selected";
                        }
					}
						$return_string .= $strOffset .  "  <option value='server.php?page=loot_table&searchtype=list&e_list=" . $row['id'] . "' " . $isSelected . ">" . $row['name'] . "</option>\n";
				}
			}
			$return_string .= $strOffset .  "</select>\n";
		}
		

		return($return_string);
	}

    function PrintListLootTable($data){
		global $eq2;
		$return_string = "";

		foreach($data as $row) 
		{
			$loottablelistitems = "SELECT * FROM `".ACTIVE_DB."`.loottable WHERE id=". $row['listitem'];
			$data2 = $eq2->RunQueryMulti($loottablelistitems);
			foreach($data2 as $row2)
			{
				$return_string .= $this->PrintLootTableRow($row2, '', '');
			}
		}

		Return($return_string);
	}

	function GenerateFileList($dir1,$filetypes=null,$recurse=1){
		global $eq2;
		$return_string = "";
		$fileset = array();
		$t1Dir = SCRIPT_PATH . $dir1;
		$t2DirList = scandir($t1Dir);
		$indexDir = 0;
		foreach($t2DirList as $t2Dir) 
		{
			$fileset[$indexDir]['Files'] = array();
			$indexFile = 0;
			if($t2Dir === '.' OR $t2Dir === '..') continue;
			//print($t1Dir . $t2Dir . "<br>\n");
			//print("[-" . is_dir($t1Dir . $t2Dir) . "-]<br>");
			if(is_dir($t1Dir . $t2Dir))
			{
				$fileset[$indexDir]['Directory']['Name'] = $dir1 . $t2Dir;
				$fileset[$indexDir]['Directory']['Path'] = $t1Dir . $t2Dir;
				$fileList = scandir($t1Dir . $t2Dir);
				foreach($fileList as $file)
				{
					if($file != "." AND $file != "..")
					{
						$fileset[$indexDir]['Files'][$indexFile] = $file;
						$indexFile++;
					}
				}
				
			}else{
				$fileset[0]['Directory']['Name'] = $dir1;
				$fileset[0]['Files'][$indexDir] = $t2Dir;
				$indexFile++;
			}
			$indexDir++;
		
		}
		//print(var_dump($fileset));
		return($fileset);
	}
}
?>