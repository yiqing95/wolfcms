<?php

/**
 * Wolf CMS - Content Management Simplified. <http://www.wolfcms.org>
 * Copyright (C) 2009 Martijn van der Kleijn <martijn.niji@gmail.com>
 * Copyright (C) 2008 Philippe Archambault <philippe.archambault@gmail.com>
 *
 * This file is part of Wolf CMS.
 *
 * Wolf CMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Wolf CMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Wolf CMS.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Wolf CMS has made an exception to the GNU General Public License for plugins.
 * See exception.txt for details and the full text.
 */

define('DEFAULT_ADMIN_USER', 'admin');
define('CORE_ROOT', dirname(__FILE__).'/../wolf');

$config_file = '../config.php';

include 'Template.php';

if (file_exists($config_file))
    include $config_file;

$msg = '';
$PDO = false;

// lets install this nice little CMS
if ( ! defined('DEBUG') && isset($_POST['commit']) && (file_exists($config_file) && is_writable($config_file))) {
    define('INSTALL_SEQUENCE', true);
    $admin_name = DEFAULT_ADMIN_USER;
    $admin_passwd = '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8';
    $admin_passwd_precrypt = 'password';

    $_POST['config']['use_pdo'] = class_exists('PDO');

    // check if the PDO driver we need is installed
    if ($_POST['config']['use_pdo'] and !in_array($_POST['config']['db_driver'], PDO::getAvailableDrivers()))
        $_POST['config']['use_pdo'] = false;

    $config_tmpl = new Template('config.tmpl');

    $config_tmpl->assign($_POST['config']);
    $config_content = $config_tmpl->fetch();

    file_put_contents($config_file, $config_content);
    $msg .= "<ul><li>Config file successfully written.</li>\n";

    include $config_file;

    // setup admin name (default to 'admin')
    if (isset($_POST['config']['admin_username'])) {
        $admin_name = $_POST['config']['admin_username'];
        $admin_name = trim($admin_name);

        $admin_passwd_precrypt = '12'.dechex(rand(100000000, 4294967295)).'K';
        $admin_passwd = sha1($admin_passwd_precrypt);
    }

    if (USE_PDO) {
        try {
            $PDO = new PDO(DB_DSN, DB_USER, DB_PASS);
        } catch (PDOException $e) {
            $msg = 'Wolf has not been installed properly.<br />The following error has occured: <p><strong>'. $e->getMessage() ."</strong></p>\n";
            file_put_contents($config_file, '');
        }
    }
    else if ($_POST['config']['db_driver'] == 'mysql') {
            require_once CORE_ROOT . '/libraries/DoLite.php';
            $PDO = new DoLite(DB_DSN, DB_USER, DB_PASS);
        }
        else {
            $msg = "Wolf has not been installed properly.<br />You need PDO and SQLite 3 drive to use SQLite 3.<br />\n";
        }

    if ($PDO) {
        $msg .= '<li>Database connection successfull.</li>';

        include 'schema_'.$_POST['config']['db_driver'].'.php';
        include 'sql_data.php';

        $msg .= '<li>Tables loaded successfully</li></ul><p>You can login with: <br /><br /><strong>login</strong>: '.$admin_name.'<br /><strong>password</strong>: '.$admin_passwd_precrypt.'<br /><br />

        <strong>at</strong>: <a href="../admin/">login page</a></p>

        <p>Please be aware: the password is generated by Wolf, please use it to login to Wolf and <strong>change your password</strong>!</p>';
    }
    else $error = 'Unable to connect to the database! Tables are not loaded!';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <title>Wolf CMS - Install</title>
        <link href="../admin/stylesheets/admin.css" media="screen" rel="Stylesheet" type="text/css" />
        <script type="text/javascript" charset="utf-8" src="../admin/javascripts/prototype.js"></script>
        <script type="text/javascript" charset="utf-8" src="../admin/javascripts/effects.js"></script>
    </head>
    <body id="installation">
        <div id="header">
            <div id="site-title">Wolf CMS - Install</div>
        </div>
        <div id="main">
            <div id="content-wrapper">
                <div id="content">
                    <!-- Content -->
                    <div>
                        <img src="install-logo.png" alt="Wolf CMS logo" class="logo" />
                        <h1>Wolf Installation</h1>
                        <p style="color: red">
<?php if ( ! file_exists($config_file)) { ?>
                            <strong>Error</strong>: config.php doesn't exist<br />
<?php } else if ( ! is_writable($config_file)) { ?>
                            <strong>Error</strong>: config.php must be writable<br />
    <?php } else { echo '<br />'; } ?>
                            <?php if ( ! is_writable('../public/')) { ?>
                            <strong>Error</strong>: public/ folder must be writable<br />
                            <?php } else { echo '<br />'; } ?>
                        </p>
                    </div>

<?php if ( ! defined('DEBUG')): ?>
                    <form action="index.php" method="post">
                        <table class="fieldset" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td colspan="3"><h3>Database information</h3></td>
                            </tr>
                            <tr>
                                <td class="label"><label for="config_db_driver">Database driver</label></td>
                                <td class="field">
                                    <select id="config_db_driver" name="config[db_driver]" onchange="db_driver_change(this[this.selectedIndex].value);">
                                        <option value="mysql">MySQL</option>
                                        <option value="sqlite">SQLite 3</option>
                                    </select>
                                    <script type="text/javascript" language="javascript">
                                        function db_driver_change(driver)
                                        {
                                            Element.toggle('row-db-host');
                                            Element.toggle('row-db-port');
                                            Element.toggle('row-db-user');
                                            Element.toggle('row-db-pass');
                                            Element.toggle('row-table-prefix');

                                            if (driver == 'sqlite')
                                            {
                                                $('help-db-name').innerHTML = 'Required. Enter the <strong>absolute</strong> path to the database file.';
                                            }
                                            else if (driver == 'mysql')
                                            {
                                                $('help-db-name').innerHTML = 'Required. You have to create a database manually and enter its name here.';
                                            }
                                        }
                                    </script>
                                </td>
                                <td class="help">Required. PDO support and the SQLite 3 plugin are required to use SQLite 3.</td>
                            </tr>
                            <tr id="row-db-host">
                                <td class="label"><label for="config_db_host">Database server</label></td>
                                <td class="field"><input class="textbox" id="config_db_host" maxlength="100" name="config[db_host]" size="100" type="text" value="localhost" /></td>
                                <td class="help">Required.</td>
                            </tr>
                            <tr id="row-db-port">
                                <td class="label"><label for="config_db_port">Port</label></td>
                                <td class="field"><input class="textbox" id="config_db_port" maxlength="10" name="config[db_port]" size="100" type="text" value="3306" /></td>
                                <td class="help">Optional. Default: 3306</td>
                            </tr>
                            <tr id="row-db-user">
                                <td class="label"><label for="config_db_user">Database user</label></td>
                                <td class="field"><input class="textbox" id="config_db_user" maxlength="255" name="config[db_user]" size="255" type="text" value="root" /></td>

                                <td class="help">Required.</td>
                            </tr>
                            <tr id="row-db-pass">
                                <td class="label"><label class="optional" for="config_db_pass">Database password</label></td>
                                <td class="field"><input class="textbox" id="config_db_pass" maxlength="40" name="config[db_pass]" size="40" type="password" value="" /></td>
                                <td class="help">Optional. If there is no database password, leave it blank.</td>
                            </tr>
                            <tr id="row-db-name">
                                <td class="label"><label for="config_db_name">Database name</label></td>
                                <td class="field"><input class="textbox" id="config_db_name" maxlength="40" name="config[db_name]" size="40" type="text" value="wolf" /></td>
                                <td class="help" id="help-db-name">Required. You have to create a database manually and enter its name here.</td>
                            </tr>
                            <tr id="row-table-prefix">
                                <td class="label"><label class="optional" for="config_table_prefix">Table prefix</label></td>
                                <td class="field"><input class="textbox" id="config_table_prefix" maxlength="40" name="config[table_prefix]" size="40" type="text" value="" /></td>
                                <td class="help">Optional. Usefull to prevent conflicts if you have, or plan to have, multiple Wolf installations with a single database.</td>
                            </tr>
                            <tr>
                                <td colspan="3"><h3>Other information</h3></td>
                            </tr>
                            <tr>
                                <td class="label"><label class="optional" for="config_admin_username">Administrator username</label></td>
                                <td class="field"><input class="textbox" id="config_admin_username" maxlength="40" name="config[admin_username]" size="40" type="text" value="<?php echo DEFAULT_ADMIN_USER; ?>" /></td>
                                <td class="help">Required. Allows you to specify a custom username for the administrator. Default: admin</td>
                            </tr>
                            <tr>
                                <td class="label"><label class="optional" for="config_url_suffix">URL suffix</label></td>
                                <td class="field"><input class="textbox" id="config_url_suffix" maxlength="40" name="config[url_suffix]" size="40" type="text" value=".html" /></td>
                                <td class="help">Optional. Add a suffix to simulate static html files.</td>
                            </tr>
                        </table>

                        <p class="buttons">
                            <button class="button" name="commit" type="submit"> Install now </button>
                        </p>

                    </form>

<?php else: ?>
    <?php echo $msg; ?>

    <?php if (isset($error)): ?>
                    <p style="color: red;"><?php echo $error; ?></p>
                    <p><a href="index.php">Click here and try again</a></p>
                        <?php else: ?>
                    <p>
                        <strong>Wolf CMS</strong> is installed, <b>you should now:</b><br />
                        1. delete the <em>install/</em> folder!<br />
                        2. remove all write permissions from the <em>config.php</em> file!<br />
                        3. remove changelog.txt and similar files to enhance security.
                    </p>
    <?php endif; ?>

<?php endif; ?>

                </div>
            </div>
        </div>

        <div id="footer">
            <p>Powered by <a href="http://www.wolfcms.org/">Wolf CMS</a></p>
        </div>
    </body>
</html>
