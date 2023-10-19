<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/setup.php
 * \ingroup doliup
 * \brief   DoliUP setup page
 */

// Load DoliUP environment
if (file_exists('../doliup.main.inc.php')) {
    require_once __DIR__ . '/../doliup.main.inc.php';
} elseif (file_exists('../../doliup.main.inc.php')) {
    require_once __DIR__ . '/../../doliup.main.inc.php';
} else {
    die('Include of doliup main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load Multicompany libraries
require_once __DIR__ . '/../../multicompany/class/actions_multicompany.class.php';

// Load DoliUP libraries
require_once __DIR__ . '/../lib/doliup.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$actionsMulticompany = new ActionsMulticompany($db);
$userTmp             = new User($db);

// Initialize view objects
$form = new Form($db);

// Security check - Protection if external user
$permissionToRead = $user->rights->doliup->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'set_config') {
    $entityCount                = GETPOST('entity_count');
    $entityName                 = GETPOST('entity_name');
    $login                      = GETPOST('login');
    $password                   = GETPOST('password');
    $countryInfo                = explode(':', $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
    $_POST['country_id']        = $countryInfo[0];
    $_POST['main_lang_default'] = $conf->global->MAIN_LANG_DEFAULT;
    $_POST['visible']           = 1;
    $_POST['active']            = 1;

    for ($i = 1; $i <= $entityCount; $i++) {
        $_POST['label'] = $entityName . ' ' . $i;
        $_POST['name']  = $entityName . ' ' . $i;

        $addAction = 'add';
        $actionsMulticompany->doAdminActions($addAction);
        $lastEntityInfo = end($actionsMulticompany->dao->entities);

        $userTmp->lastname = $login . $i;
        $userTmp->login    = $login . $i;
        $userTmp->admin    = 1;
        // We need to add 1 because $actionsMulticompany don't return last entity created but all previous entities infos
        $userTmp->entity = $lastEntityInfo->id + 1;

        $userTmpID = $userTmp->create($user);

        $conf->global->USER_PASSWORD_GENERATED = 'none';
        $userTmp->id = $userTmpID;
        $userTmp->setPassword($user, $password);
    }

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}



/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'DoliUP');
$help_url = 'FR:Module_DoliUP';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = doliup_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $title, -1, 'doliup_color@doliup');

print load_fiche_titre($langs->trans('Config'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_config">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="entity_count">' . $langs->trans('EntityCount') . '</label></td>';
print '<td>' . $langs->trans('EntityCountDescription') . '</td>';
print '<td><input type="number" name="entity_count"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="entity_name">' . $langs->trans('EntityName') . '</label></td>';
print '<td>' . $langs->trans('EntityNameDescription') . '</td>';
print '<td><input type="text" name="entity_name"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="login">' . $langs->trans('Login') . '</label></td>';
print '<td>' . $langs->trans('Login') . '</td>';
print '<td><input type="text" name="login"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="password">' . $langs->trans('Password') . '</label></td>';
print '<td>' . $langs->trans('Password') . '</td>';
print '<td><input type="password" name="password"></td>';
print '</td></tr>';

print '</table>';
print $form->buttonsSaveCancel('Save', '');
print '</form>';

$db->close();
llxFooter();
