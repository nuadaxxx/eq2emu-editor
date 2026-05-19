<?php
/*  
    EQ2Editor:  Everquest II Database Editor v1.0
    Copyright (C) 2008-2013  EQ2Emulator Development Team (http://eq2emulator.net)

    This file is part of EQ2Editor.

    EQ2Editor is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    EQ2Editor is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with EQ2Editor.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('IN_EDITOR')) { die("Hack attempt recorded."); }

require "../class/dotenv.php";
DotEnv::load("../.env");

require "../config.php";

if (isset($_REQUEST['cmd'])) {
switch($_REQUEST['cmd']) 
{
	case "Set Password":
		$eq2->SavePassword();
		break;
		
	case "Login":
		if( !empty($_POST['lName']) && !empty($_POST['lPass']) )
		{
			if( $eq2->LoginUser() )
				header("Location: index.php"); /* Redirect browser */
		}
		else
			$eq2->AddStatus("Invalid login information.");
		break;

	case "Logout":
		unset($eq2->userdata);
		$eq2->DeleteCookie();
		header("Location: index.php"); /* Redirect browser */
		break;

    case "SetWorld":
        $datasource_query = "SELECT db_name FROM `datasources` WHERE id=" . $_POST['datasource'];
        $datasources_data = $eq2->RunQuerySingle($datasource_query);
        $config_query = "UPDATE `config` SET config_value='" . $datasources_data['db_name'] . "' WHERE config_name='active_datasource'";
        $_SESSION['current_database'] = $datasources_data['db_name'];
        header("Location: index.php");
        break;
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EQ2DB Editor</title>

<?php
$cssInclude = "";
if($_SERVER['SERVER_NAME'] == "dbedit.zeklabs.com")
{
	$cssInclude = "<link rel='stylesheet' href='../css/eq2.css?md5='" . md5_file("../css/eq2.css") ." />\n";
}else{
	$cssInclude = "<link rel='stylesheet' href='../css/eq2alt.css?md5='" . md5_file("../css/eq2.css") ." />\n";
}
print($cssInclude);
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>
    <script src="../js/eq2editor.js?md5=<?php echo md5_file("../js/eq2editor.js"); ?>"></script>
    <link rel="icon" href="../images/favicon.ico"/>
</head>
<?php

if (isset($_COOKIE['eq2db'])) {
	$eq2->userdata = $eq2->GetCookie();
	if ($eq2->userdata['reset_password'] == 0)
		$eq2->user_role = intval($eq2->userdata['role']);
	else
		$eq2->user_role = 0;
}
//print_r($GLOBALS['config']);
//print_r($eq2->role_list);
//print_r($eq2->userdata);

if( $GLOBALS['config']['debug_forms'] && isset($_POST['cmd']) )
	$eq2->AddDebugForm($_POST);

?>
<body>
    <div id="site-container">
    <div id="site-banner"><?php printf("%s %s", $GLOBALS['config']['app_name'], $GLOBALS['config']['app_version']); ?>
        <div id="user-info">
            <?php
            if( is_array($eq2->userdata) )
            {
                $strHTML = "\n";
                $strHTML .= "Logged in as: <b>" . (strlen($eq2->userdata['displayname']) > 0?$eq2->userdata['displayname']:$eq2->userdata['username']) . "</b>" . $eq2->userdata['title'] . "<br/>\n";
                $strHTML .= "<a href='settings.php' target='_self'><u>My Settings</u></a><br />\n";
                $strHTML .= "<form method='post'>\n";
                $strHTML .= "<input type='hidden' name='cmd' value='SetWorld'>\n";
                $strHTML .= "  World:\n";
                $strHTML .= "  <select name='datasource' onchange='this.form.submit()'>\n";
                $datasources_query = "SELECT id, db_display_name, db_name FROM datasources WHERE is_active=1";
                $datasources_data = $eq2->RunQueryMulti($datasources_query);
                foreach($datasources_data as $datasource)
                {
                    $isSelected = ($_SESSION['current_database']==$datasource['db_name']?"selected":"");
                    $strHTML .= "    <option value='" . $datasource['id'] . "' " . $isSelected . ">" . $datasource['db_display_name'] . "(" . $datasource['db_name'] . ")</option>\n";
                }
                $strHTML .= "  </select>\n";
                $strHTML .= "</form>\n";
                //$strHTML .= "<br/>" . $_SESSION['current_database'] . "<br>\n";
                if( $GLOBALS['config']['readonly'] )
                    $strHTML .= "<font class='warning'>READ-ONLY Mode!</font>";
                if (env("DEBUG"))
                    $strHTML .= "&nbsp;<font class='warning'>Debug ON!</font>";

                print($strHTML);
                
            }
    ?>
        </div>
        <div id="db-picker">
    <?php 
            print($eq2->DBPicker()); 
            //$db_name = isset($_SESSION['current_database']) ? $_SESSION['current_database'] : "eq2dev";		
    ?>
        </div>
    </div>
    <?php /*?>do not show menu unless user is validated<?php */?>
    <?php if (!empty($eq2->userdata)) { ?>
        <!-- top menu -->
        <div id="top-menu">
        <table width="100%" cellspacing="0" border="0">
            <tr align="center">
                <?php 
                $current_script = $eq2->GetPHPScriptName();
                // Always display Home tab
                printf('<td class="%s"><a href="index.php">Home</a></td>', ( $current_script == "index.php" ) ? "tabOn" : "tabOff");
                
                $devTabs = [
                    M_CHARACTERS => "Characters|characters.php",
                    M_GUILDS     => "Guilds|guilds.php",
                    M_ITEMS      => "Items|items.php",
                    M_QUESTS     => "Quests|quests.php",
                    M_SCRIPTS    => "Scripts|scripts.php",
                    M_SPELLS     => "Spells|spells.php",
                    M_SPAWNS     => "Spawns|spawns.php",
                    M_SERVER     => "Server|server.php",
                    M_ZONES      => "Zones|zones.php",
                    M_ADMIN      => "Admin|_admin.php",
                    M_RECIPES    => "Recipes|server.php?page=recipes",
                    M_RECIPECOMPONENTS   => "Recipe Components|server.php?page=recipe_comp"
                ];
                
                $empty_cell = 0;

                foreach ($devTabs as $flag=>$tab) {
                    if ($eq2->user_role & $flag) {
                        $info = explode('|', $tab);
                        $strHTML = "<td class=" . ($current_script == $info[1] ? "tabOn" : "tabOff") . ">\n";
                        $strHTML .= "  <a href=" . $info[1] . ">" . $info[0] . "</a>\n";
                        $strHTML .= "</td>\n";
                        print($strHTML);

                        // Quest-AI is a separate cloned workspace for AI-assisted quest work.
                        // The original Quests tab/page remains untouched.
                        if ($info[0] == "Quests") {
                            $questAiClass = ($current_script == "quest_ai.php") ? "tabOn" : "tabOff";
                            print('<td class="' . $questAiClass . '"><a href="quest_ai.php">Quest-AI</a></td>');
                        }
                    }
                    else $empty_cell++;
                }
                    
                if( $empty_cell ) {
                    for( $i = 0; $i < $empty_cell; $i++ ) {
                        print('<td class="tabOff">&nbsp;</td>');
                    }
                }
                ?>
                <td class="tabOff">
                    <a href="index.php?cmd=Logout">&nbsp;Logout</a>&nbsp;
                </td>
            </tr>
        </table>
        </div>
    <?php } else { ?>
        <div id="login-box">
        <table cellspacing="0" align="center">
            <?php if (!empty($eq2->Status)) { ?>
                <tr>
                    <td colspan="2" class="warning"><?= $eq2->DisplayStatus(); ?></td>
                </tr>
            <?php } ?>
            <form action="index.php" method="post" name="Login">
            <tr>
                <td colspan="2" class="title">EQ2DB Login</td>
            </tr>
            <tr>
                <td class="label">Username:</td>
                <td><input type="text" name="lName" value="" class="text" /></td>
            </tr>
            <tr>
                <td class="label">Password:</td>
                <td><input type="password" name="lPass" value="" class="text" /></td>
            </tr>
            <tr>
                <td align="center" colspan="2"><input type="submit" name="cmd" value="Login" class="submit" /></td>
            </tr>
            <?php /*?><tr>
                <td align="center" colspan="2">( <a href="index.php">Guest</a> )</td>
            </tr><?php */?>
            </form>
        </table>
        </div>
    <?php
        include('footer.php');
        exit;
    }

    /* Reset Password check */
    if( $eq2->userdata['reset_password'] == 1 )
    {
        $eq2->ResetPasswordForm();
        include('footer.php');
        exit;
    }

    /* if logged in, continue with main body */
    ?>
    <div id="main-body">

