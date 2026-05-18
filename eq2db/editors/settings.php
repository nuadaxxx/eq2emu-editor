<?php
/*
    EQ2Editor: My Settings – completed self-service page
    Drop-in replacement for: eq2db/editors/settings.php
*/
define('IN_EDITOR', true);
include('header.php');

function settings_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function settings_date($timestamp)
{
    $timestamp = (int)$timestamp;
    return $timestamp > 0 ? date('M-d-Y H:i:s', $timestamp) : 'Never.';
}

$userId = (int)($eq2->userdata['id'] ?? 0);
$userRole = (int)($eq2->userdata['role'] ?? 0);

/*
 * This page is only reachable after editors/header.php has validated the login.
 * Keep this explicit guard in case the header logic is changed later.
 */
if ($userId <= 0) {
    die('Access denied.');
}

/*
 * Self-service profile save.
 * Only the currently authenticated user's record is updated.
 */
if (($_POST['settings_cmd'] ?? '') === 'save_profile') {
    $displayname = trim((string)($_POST['displayname'] ?? ''));
    $title = trim((string)($_POST['title'] ?? ''));

    // Match the users table schema: varchar(64) for both fields.
    $displayname = substr($displayname, 0, 64);
    $title = substr($title, 0, 64);

    $displaynameSql = $eq2->SQLEscape($displayname);
    $titleSql = $eq2->SQLEscape($title);

    $eq2->SQLQuery = sprintf(
        "UPDATE users SET displayname = '%s', title = '%s' WHERE id = %d LIMIT 1",
        $displaynameSql,
        $titleSql,
        $userId
    );

    if ($eq2->RunQuery(false)) {
        $eq2->userdata['displayname'] = $displayname;
        $eq2->userdata['title'] = $title;
        $eq2->AddStatus('Profile settings saved.');
    } else {
        $eq2->AddStatus('Profile settings could not be saved.');
    }
}

/*
 * Self-service password change.
 * The original editor password flow does not validate the old password for this page.
 * This page does, and it never trusts a posted user id.
 */
if (($_POST['settings_cmd'] ?? '') === 'change_password') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $storedHash = strtolower((string)($eq2->userdata['password'] ?? ''));
    $currentHash = md5($currentPassword);

    if ($currentPassword === '' || !hash_equals($storedHash, $currentHash)) {
        $eq2->AddStatus('Current password is incorrect.');
    } elseif (strlen($newPassword) < 3) {
        // Keep the same minimum length the editor currently uses internally.
        $eq2->AddStatus('Invalid password. Minimum length is 3 characters.');
    } elseif ($newPassword !== $confirmPassword) {
        $eq2->AddStatus('New password and confirmation do not match.');
    } else {
        $newHash = md5($newPassword);
        $newHashSql = $eq2->SQLEscape($newHash);

        $eq2->SQLQuery = sprintf(
            "UPDATE users SET password = '%s', reset_password = 0 WHERE id = %d LIMIT 1",
            $newHashSql,
            $userId
        );

        if ($eq2->RunQuery(false)) {
            // Refresh the editor cookie using the existing login/cookie path.
            $_POST['lName'] = (string)$eq2->userdata['username'];
            $_POST['lPass'] = $newPassword;
            $eq2->LoginUser();
            $eq2->AddStatus('Password updated.');
        } else {
            $eq2->AddStatus('Password could not be updated.');
        }
    }
}

/* Refresh current user record for display after a save. */
$currentUser = $eq2->RunQuerySingle(sprintf(
    'SELECT id, username, displayname, title, role, is_active, last_visited, reset_password FROM users WHERE id = %d LIMIT 1',
    $userId
));

if (!is_array($currentUser)) {
    $currentUser = $eq2->userdata;
}

/* Role list for display. */
$roles = $eq2->RunQueryMulti(
    "SELECT role_type, role_name, role_value, role_description FROM roles WHERE is_global = 1 ORDER BY role_type, role_value, id"
);

/* Recent messages addressed to this user. */
$messages = $eq2->RunQueryMulti(sprintf(
    "SELECT m.id, m.subject, m.message_text, m.from_user_id, m.message_date, m.active, u.username AS from_username, u.displayname AS from_displayname\n" .
    "FROM messages m\n" .
    "LEFT JOIN users u ON u.id = m.from_user_id\n" .
    "WHERE m.to_user_id = %d AND m.active = 1\n" .
    "ORDER BY m.message_date DESC, m.id DESC\n" .
    "LIMIT 10",
    $userId
));

/* Recent editor log entries created by this user. */
$logs = $eq2->RunQueryMulti(sprintf(
    "SELECT id, object_id, table_name, update_date, archived\n" .
    "FROM log\n" .
    "WHERE user_id = %d\n" .
    "ORDER BY update_date DESC, id DESC\n" .
    "LIMIT 20",
    $userId
));
?>
<div id="Editor">
    <table class="SubPanel" cellspacing="0" width="100%">
        <tr>
            <td id="EditorStatus" colspan="2"><?php if (!empty($eq2->Status)) { $eq2->DisplayStatus(); } ?></td>
        </tr>
        <tr>
            <td class="Title" colspan="2">
                My Settings: <?= settings_h($currentUser['username'] ?? '') ?> (<?= (int)($currentUser['id'] ?? 0) ?>)
            </td>
        </tr>
        <tr>
            <td valign="top" width="50%">
                <fieldset><legend>Account</legend>
                    <form method="post" name="ProfileSettings">
                        <table cellspacing="0" width="100%">
                            <tr>
                                <td class="Label" width="180">User ID:</td>
                                <td><?= (int)($currentUser['id'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td class="Label">Username:</td>
                                <td><?= settings_h($currentUser['username'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td class="Label">Display Name:</td>
                                <td><input type="text" name="displayname" value="<?= settings_h($currentUser['displayname'] ?? '') ?>" maxlength="64" /></td>
                            </tr>
                            <tr>
                                <td class="Label">Title:</td>
                                <td><input type="text" name="title" value="<?= settings_h($currentUser['title'] ?? '') ?>" maxlength="64" /></td>
                            </tr>
                            <tr>
                                <td class="Label">Last Login:</td>
                                <td><?= settings_h(settings_date($currentUser['last_visited'] ?? 0)) ?></td>
                            </tr>
                            <tr>
                                <td class="Label">Account Active:</td>
                                <td><?= !empty($currentUser['is_active']) ? 'Yes' : 'No' ?></td>
                            </tr>
                            <tr>
                                <td class="Label">Password Reset Flag:</td>
                                <td><?= !empty($currentUser['reset_password']) ? 'Enabled' : 'Disabled' ?></td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center">
                                    <input type="hidden" name="settings_cmd" value="save_profile" />
                                    <input type="submit" value="Save Profile" class="submit" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </fieldset>

                <br />

                <fieldset><legend>Change Password</legend>
                    <form method="post" name="PasswordSettings">
                        <table cellspacing="0" width="100%">
                            <tr>
                                <td class="Label" width="180">Current Password:</td>
                                <td><input type="password" name="current_password" value="" autocomplete="current-password" /></td>
                            </tr>
                            <tr>
                                <td class="Label">New Password:</td>
                                <td><input type="password" name="new_password" value="" autocomplete="new-password" /></td>
                            </tr>
                            <tr>
                                <td class="Label">Confirm Password:</td>
                                <td><input type="password" name="confirm_password" value="" autocomplete="new-password" /></td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center">
                                    <input type="hidden" name="settings_cmd" value="change_password" />
                                    <input type="submit" value="Change Password" class="submit" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </fieldset>
            </td>
            <td valign="top" width="50%">
                <fieldset><legend>Roles</legend>
                    <table cellspacing="0" width="100%">
                        <tr>
                            <td class="Label" width="180">Role Bitmask:</td>
                            <td><?= (int)($currentUser['role'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="Label">Resolved Role:</td>
                            <td><?= settings_h($eq2->GetUserRoleName()) ?></td>
                        </tr>
                    </table>
                    <br />
                    <table cellspacing="0" width="100%">
                        <tr>
                            <td class="SectionTitle">Type</td>
                            <td class="SectionTitle">Role</td>
                            <td class="SectionTitle">Description</td>
                        </tr>
                        <?php
                        $activeRoleCount = 0;
                        if (is_array($roles)) {
                            foreach ($roles as $role) {
                                $roleValue = (int)($role['role_value'] ?? 0);
                                $isActiveRole = ($roleValue === 0)
                                    ? ($userRole === 0)
                                    : (($userRole & $roleValue) === $roleValue);

                                if (!$isActiveRole) {
                                    continue;
                                }
                                $activeRoleCount++;
                                ?>
                                <tr>
                                    <td><?= settings_h($role['role_type'] ?? '') ?></td>
                                    <td><?= settings_h($role['role_name'] ?? '') ?></td>
                                    <td><?= settings_h($role['role_description'] ?? '') ?></td>
                                </tr>
                                <?php
                            }
                        }
                        if ($activeRoleCount === 0) {
                            ?>
                            <tr><td colspan="3">No global roles resolved for this user.</td></tr>
                            <?php
                        }
                        ?>
                    </table>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="SectionBody">
                <fieldset><legend>Messages</legend>
                    <table cellspacing="0" width="100%">
                        <tr>
                            <td class="SectionTitle" width="180">Date</td>
                            <td class="SectionTitle" width="180">From</td>
                            <td class="SectionTitle" width="220">Subject</td>
                            <td class="SectionTitle">Message</td>
                        </tr>
                        <?php
                        if (is_array($messages) && count($messages) > 0) {
                            foreach ($messages as $message) {
                                $fromName = trim((string)($message['from_displayname'] ?? ''));
                                if ($fromName === '') {
                                    $fromName = (string)($message['from_username'] ?? 'System');
                                }
                                ?>
                                <tr valign="top">
                                    <td><?= settings_h(settings_date($message['message_date'] ?? 0)) ?></td>
                                    <td><?= settings_h($fromName) ?></td>
                                    <td><?= settings_h($message['subject'] ?? '') ?></td>
                                    <td><?= nl2br(settings_h($message['message_text'] ?? '')) ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr><td colspan="4">No active messages found.</td></tr>
                            <?php
                        }
                        ?>
                    </table>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="SectionBody">
                <fieldset><legend>My Recent Edits</legend>
                    <table cellspacing="0" width="100%">
                        <tr>
                            <td class="SectionTitle" width="180">Date</td>
                            <td class="SectionTitle" width="180">Table</td>
                            <td class="SectionTitle">Object</td>
                            <td class="SectionTitle" width="90">Archived</td>
                        </tr>
                        <?php
                        if (is_array($logs) && count($logs) > 0) {
                            foreach ($logs as $log) {
                                ?>
                                <tr>
                                    <td><?= settings_h(settings_date($log['update_date'] ?? 0)) ?></td>
                                    <td><?= settings_h($log['table_name'] ?? '') ?></td>
                                    <td><?= settings_h($log['object_id'] ?? '') ?></td>
                                    <td><?= !empty($log['archived']) ? 'Yes' : 'No' ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr><td colspan="4">No editor log entries found for this user.</td></tr>
                            <?php
                        }
                        ?>
                    </table>
                </fieldset>
            </td>
        </tr>
    </table>
</div>
<?php
include('footer.php');
?>
