<?php

/*

==================================================================
     DOKUWIKI SETUP - based on Pivot Setup
     
Creator:                Hans Fredrik Nordhaug
E-mail:                 hans@nordhaug.priv.no
License:                GPL                                        */

$version =              "0.3 (2008.03.16)";                       /*

==================================================================

For more info launch the script in a browser and read the help page.
*/

// CONFIGURATION

// Don't use a valuable password since it's sent in clear text.
// A blank password ("") will disable DokuWiki Setup completely.
$password = "vrcave";      
$tar_executable = "tar"; // Normally just "tar" is OK.
$tar_options = "-zxvf"; // Used when extracting (gzip, not bzip2).
$tar_stripoption = "--strip-components"; // Very old tar version uses "--strip-path"
$zip_executable = "zip"; // Normally just "zip" is OK.
$zip_quiet = "-q"; // Set to an empty string if you want messages from zip/unzip
$unzip_executable = "unzip"; // Normally just "unzip" is OK.
$unzip_overwrite_arg = "-o"; // Used when upgrading
$download_url = "http://www.splitbrain.org/_media/projects/dokuwiki/dokuwiki-%version%.tgz";
$filelist_url = "http://www.splitbrain.org/projects/dokuwiki";
$direct_install = true; // Whether the install/upgrade should offer an direct from SF option
$backup = true; // Should we make a back-up when upgrading?

// END CONFIGURATION

$required_php_version = "4.3.3";

// Default files and dirs in a DokuWiki install (at least for version 1.30).
$dokuwiki_dirs = array("bin/", "conf/", "data/", "inc/", "lib/");
$dokuwiki_files = array(".htaccess", "COPYING", "README", "VERSION", "doku.php", "feed.php", "index.php", "install.php");
$dokuwiki_tpl_dir = "lib/tpl/";

// Don't display notices and warnings
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

if (basename($_SERVER["PHP_SELF"]) == "dokuwiki-setup-safemode.php") {
    $safe_mode_2nd_run = true;
    $title = "DokuWiki Setup (for safe-mode)";
} else {
    $safe_mode_2nd_run = false;
    $title = "DokuWiki Setup";
}

$this_dir = dirname(__FILE__);
$wiki_url = "http://wiki.splitbrain.org";
$direct_install_text = "";

// Disabling direct install if allow_url_fopen is false or 
// if installing templates.
if (!ini_get('allow_url_fopen') || tpl_action()) {
    $direct_install = false;
}

if (!function_exists('file_get_contents')) {
    function file_get_contents($filename, $use_include_path = 0) {
        $file = @fopen($filename, 'rb', $use_include_path);
        if ($file) {
            if ($fsize = @filesize($filename)) {
                $data = fread($file, $fsize);
            } else {
                while (!feof($file)) {
                    $data .= fread($file, 1024);
                }
            }
            fclose($file);
        }
        return $data;
    }
}

if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $data) {
        $file = @fopen($filename, 'wb');
        if ($file) {
            $size = fwrite($file, $data);
            fclose($file);
        }
        return $size;
    }
}

$menu = '
 <a class="menu_links" href="dokuwiki-setup.php">Frontpage</a>
 <a class="menu_links" href="dokuwiki-setup.php?action=install">Install</a>
 <a class="menu_links" href="dokuwiki-setup.php?action=upgrade">Upgrade</a>
 <a class="menu_links" href="dokuwiki-setup.php?action=uninstall">Uninstall</a>
 <a class="menu_links" href="dokuwiki-setup.php?action=extras">Extras</a>
 <a class="menu_links" href="dokuwiki-setup.php?action=chmod">Chmod</a>
 <a class="menu_links" href="dokuwiki-setup.php?action=test">Test</a>
 <a class="menu_links" href="dokuwiki-setup.php?action=help">Help</a>
';

$header = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <title>'.$title.' '.$version.'</title>

        <link rel="shortcut icon" href="'.$wiki_url.'/lib/tpl/default/images/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="'.$wiki_url.'/lib/exe/css.php" />
        <style type="text/css">
        .info { background-color: #999 }
        .warn { background-color: yellow }
        .error { background-color: red }
        .menu_links { padding: 0 5px 0 5px; font-weight: bold;}
        tt {font-size: 120%; }
        </style>
        <meta http-equiv="Cache-Control" content="no-cache" />

        <meta http-equiv="Expires" content="0" />
        <meta http-equiv="Pragma" content="no-cache" />
        <script type="text/javascript">
function clearOpposites(id) {
    if (id == "version") {
        document.getElementById("url").value = "";
        var field1 = document.getElementById("file");
        if (field1.value != "") {
            alert("Empty the upload field if you don\'t want to upload a file!");
        }
    } else if (id == "file") {
        document.getElementById("version").selectedIndex = 0;
        document.getElementById("url").value = "";
    } else if (id == "url") {
        document.getElementById("version").selectedIndex = 0;
        var field1 = document.getElementById("file");
        if (field1.value != "") {
            alert("Empty the upload field if you don\'t want to upload a file!");
        }
    }
}
        </script>
    </head>
    <body>
    <!-- menu starts here -->
    <div class="dokuwiki">
    <div class="stylehead">
        <div class="header">
        <div class="pagename">
        %current_action%
        </div>

        <div class="logo">
            <a href="%self%"  name="dokuwiki__top" id="dokuwiki__top" accesskey="h" title="[ALT+H]">DokuWiki Setup</a>      </div>
            <div class="clearer"></div>
        </div>

        <div class="bar" id="bar__top">
            <div class="bar-left" id="bar__topleft">
            %menu%
            </div>
            <div class="clearer"></div>
        </div>
    </div>
    <!-- menu ends here -->
    <div class="page">
    <h1><a name="dokuwiki" id="dokuwiki">'.$title.'</a></h1>
    <div class="level1">
';

$footer = '
    </div>
    </div>
    </div>
    </body>
</html>';

// Building the static parts of the page
$header = str_replace("%menu%",$menu,$header);
echo str_replace("%current_action%",htmlspecialchars($_REQUEST["action"]),$header);

if (!version_ok()) {
    echo $footer;
    die();
}

if (empty($password)) {
    echo '<h2 class="error">Password setting is blank - DokuWiki Setup is disabled.</h2>';
    // Access is only allowed to the frontpage and help page.
    if (!empty($_REQUEST["action"]) && ($_REQUEST["action"] != "help") && ($_REQUEST["action"] != "test")) {
        unset($_REQUEST["action"]);
        echo '<center><p class="warn">Only the help, test and frontpage is available.</p></center>';
    }
} elseif ($safe_mode_2nd_run) {
    echo '<p class="warn">When you are done with the second step of unzipping 
    or with uninstall, go back to the 
    <a href="dokuwiki-setup.php">standard DokuWiki Setup</a>.</p>';
}
if ($_REQUEST["action"] == "install") {
    if (tpl_action()) {
        echo '<h2>Install DokuWiki Templates</h2>';
    } else {
        echo '<h2>Install DokuWiki</h2>';
    }
    if (!is_writable($this_dir) && !$safe_mode_2nd_run) {
        echo "<p class=\"error\">Can not write to this directory ($this_dir) and hence can not install.</p>";
    } elseif (submit_ok()) {
        if ($safe_mode_2nd_run) {
            echo '<p>The second (and last) step of unzipping.</p>';
            // Must set global variable $filetype detected in the first step
            $filetype = $_REQUEST['filetype'];
            install($_REQUEST['name']);
        } elseif ($direct_install && ($_POST['version'] != "Select a version")) {
            echo '<h3>Handle direct install</h3>';
            echo "<p>Version: ".$_POST['version']."</p>\n";
            $name = ds_tempnam();
            $download_url = str_replace('%version%',$_POST['version'],$download_url);
            $path_parts = pathinfo($download_url);
            $filetype = $path_parts['extension'];
            $size = file_put_contents($name, file_get_contents($download_url));
            if ($size != 0) {
                echo "<p>Fetching from the DokuWiki homepage - file size: $size</p>\n";
                install($name);
            } else {
                echo '<p class="warn">File was empty - problem with the DokuWiki homepage?</p>';
            }
        } elseif (!empty($_POST["url"])) {
            echo '<h3>Handle file URL</h3>';
            $name = ds_tempnam();
            $size = file_put_contents($name, file_get_contents($_REQUEST['url']));
            if ($size != 0) {
                echo "<p>Fetching \"".$_REQUEST['url']."\" - file size: $size</p>\n";
                preg_match('|dokuwiki-([a-z0-9-]*)\.tgz|',$_POST["url"],$file);
                $_REQUEST['version'] = $file[1];
                $url_parts = parse_url($_REQUEST['url']);
                $path_parts = pathinfo($url_parts['path']);
                $filetype = $path_parts['extension'];
                install($name);
            } else {
                echo '<p class="warn">File was empty - wrong (or incomplete) URL?</p>';
            }
        } elseif (!empty($_FILES['file']['name'])) {
            echo '<h3>Handle uploaded file</h3>';
            $name = $_FILES['file']['name'];
            $tmpname = $_FILES['file']['tmp_name'];
            $size = $_FILES['file']['size'];
        
            if (move_uploaded_file($tmpname, $name)) {
                echo "<p>File Name: $name</p>\n";
                echo "<p>File Size: $size</p>\n";
                echo "<p>Your file was successfully uploaded!</p>\n";
                preg_match('|dokuwiki-([a-z0-9-]*)\.tgz|',$name,$file);
                $_REQUEST['version'] = $file[1];
                $path_parts = pathinfo($name);
                $filetype = $path_parts['extension'];
                install($name);
            } else {
                echo "<p>Your file could not be uploaded.</p>";
            }
        } else {
            echo '<p class="warn">No file upload, file location or version selected - nothing installed.</p>';
        }

    } else { 
        if (!check()) {
            echo '<p class="warn">There might be a problem with unzipping/extraction - have you '.
                'visited the <a href="?action=test">test page</a>?';
        }
        if (!tpl_action()) {
            foreach ($dokuwiki_dirs as $dir) {
                if (file_exists($dir)) {
                    echo '<p class="warn">Directory "'.$dir.'" which might be part of
                    the DokuWiki installation, already exists - consider renaming or removing.</p>';
                }
            }
            foreach ($dokuwiki_files as $file) {
                if (file_exists($file)) {
                    echo '<p class="warn">File "'.$file.'" which might be part of
                    the DokuWiki installation, already exists - consider renaming or removing.</p>';
                }
            }
        }
        check_submit();
        if ($direct_install) {
            $webpage = file_get_contents($filelist_url);
            preg_match_all('|/dokuwiki-([a-z0-9-]*)\.tgz|',$webpage,$files,PREG_PATTERN_ORDER);
            $versions = $files[1]; // rsort($versions);
            $direct_install_text = '
                <tr><td>Choose version from the DokuWiki homepage: </td><td>
                <select name="version" id="version" onfocus="clearOpposites(this.id)">
                <option>Select a version</option>';
            foreach($versions as $version) {
                $direct_install_text .= "\n<option>$version</option>";
            }
            $direct_install_text.= "\n</select></td></tr>";
        }

        echo '
        <p>Either upload a file from your computer';
        if ($direct_install) {
            echo ', give an URL to a zip file, or select a version from the pulldown menu, 
            that will be automatically fetched from 
            <a href="'.$filelist_url.'">the DokuWiki homepage</a> - quite convenient...</p>';
        } else {
            echo ' or give an URL to a zip file.';
        }
        echo '
        <form enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'" method="POST">
        <input type="hidden" name="action" value="install" />';
        if (tpl_action()) {
            echo '<input type="hidden" name="type" value="templates" />';
        }
        echo '
        <table>
        <tr><th colspan="2" align="left">DokuWiki zip-file</th></tr>
        '.$direct_install_text.'
        <tr><td>Choose file to upload: </td><td><input type="file" id="file" name="file" 
            onfocus="clearOpposites(this.id)" /></td></tr>
        <tr><td>Choose file location: </td><td><input type="text" id="url" name="url" size="20" 
            onfocus="clearOpposites(this.id)" /> (Complete URL.)</td></tr>
        <tr><th colspan="2" align="left">&nbsp;</th></tr>
        <tr><th align="left">Password </th><td><input type="password" name="password" size="20" /><input 
            type="submit" name="submit" value="Install Files" />
        </td></tr>
        </table>
        </form>';
    }
} elseif ($_REQUEST["action"] == "chmod") {
    echo '<h2>Change permission on a file used by DokuWiki (chmod)</h2>';
    if (!dokuwiki_exists()) {
        echo '<p class="error">DokuWiki not found - expected to find DokuWiki in "'.
            $this_dir.'". Have you placed "dokuwiki-setup.php" in
            the wrong directory.</p>';
    }
    if (!is_writable($this_dir)) {
        echo "<p class=\"error\">Can not write to this directory ($this_dir) and hence can not chmod.</p>";
    } elseif (submit_ok() && (isset($_POST["filepath"]) && file_exists($_POST["filepath"])) ) {
        if (!isset($_POST["mode"])) {
            echo '<p class="warn">Mode not set, using 777.</p>';
            $mode = octdec('0777');
        } else {
            $mode = octdec('0'.$_POST["mode"]);
        }
        if (chmod($_POST["filepath"], $mode)) {
            echo '<p>Changed permission on '
                .htmlspecialchars($_POST["filepath"]).' to '
                .htmlspecialchars($_POST["mode"]). '</p>';
        } else {
            echo '<p class="error">Couldn\'t chang permission on '
                .htmlspecialchars($_POST["filepath"]).' to '
                .htmlspecialchars($_POST["mode"]).'</p>';
        }
    } else { 
        if (!submit_ok()) {
            echo '<p class="warn">Wrong password given.</p>';
        } else if (isset($_POST["filepath"]) && file_exists($_POST["filepath"])) {
            echo '<p class="warn">The selected file, '.
                htmlspecialchars($_POST["filepath"]).'. doesn\'t exist.</p>';
        }
        echo '
        <p>Select the file you want to chmod.</p>
        <form action="'.$_SERVER['PHP_SELF'].'" method="POST">
        <input type="hidden" name="action" value="chmod" />
        <table>
        <tr><td>Filename (with path)</td><td><input type="text" name="filepath" size="20" /> 
        (Relative from this directory.)</td></tr>
        <tr><td>Mode</td><td><input type="text" name="mode" size="20" />
        (Use a numeric value, i.e., 777 for world writable.)</td></tr>
        <tr><td colspan="2" align="left">&nbsp;</td></tr>
        <tr><th align="left">Password </th><td><input type="password" name="password" size="20" /><input 
            type="submit" name="submit" value="Chmod File" />
        </td></tr>
        </table>
        </form>';
    }
} elseif ($_REQUEST["action"] == "upgrade") {
    echo '<h2>Upgrade DokuWiki and it\'s core plugins</h2>';
    if (!dokuwiki_exists()) {
        echo '<p class="error">DokuWiki not found - expected to find DokuWiki in "'.
            $this_dir.'". Have you placed "dokuwiki-setup.php" in
            the wrong directory.</p>';
    }
    if (!is_writable($this_dir)) {
        echo "<p class=\"error\">Can not write to this directory ($this_dir) and hence can not upgrade.</p>";
    } elseif (submit_ok()) {
        if ($direct_install && ($_POST["version"] != "Select a version")) {
            echo '<h3>Handle direct install</h3>';
            echo "<p>Version: ".$_POST["version"]."</p>\n";
            $name = ds_tempnam();
            $download_url = str_replace('%version%',$_POST["version"],$download_url);
            $path_parts = pathinfo($download_url);
            $filetype = $path_parts['extension'];
            $size = file_put_contents($name, file_get_contents($download_url));
            if ($size != 0) {
                echo "<p>Fetching from the DokuWiki homepage - file size: $size</p>\n";
                install($name, true);
            } else {
                echo '<p class="warn">File was empty - problem with the DokuWiki homepage mirror?</p>';
            }
        } elseif (!empty($_POST["url"])) {
            echo '<h3>Handle file URL</h3>';
            $name = ds_tempnam();
            $size = file_put_contents($name, file_get_contents($_POST["url"]));
            if ($size != 0) {
                echo "<p>Fetching \"".$_POST["url"]."\" - file size: $size</p>\n";
                preg_match('|dokuwiki-([a-z0-9-]*)\.tgz|',$_POST["url"],$file);
                $_REQUEST['version'] = $file[1];
                install($name, true);
            } else {
                echo '<p class="warn">File was empty - wrong (or incomplete) URL?</p>';
            }
        } elseif (!empty($_FILES['file']['name'])) {
            echo '<h3>Handle uploaded file</h3>';
            $name = $_FILES['file']['name'];
            $tmpname = $_FILES['file']['tmp_name'];
            $size = $_FILES['file']['size'];

            if (move_uploaded_file($tmpname, $name)) {
                echo "<p>File Name: $name</p>\n";
                echo "<p>File Size: $size</p>\n";
                echo "<p>Your file was successfully uploaded!</p>\n";
                preg_match('|dokuwiki-([a-z0-9-]*)\.tgz|',$name,$file);
                $_REQUEST['version'] = $file[1];
                install($name, true);
            } else {
                echo "<p>Your file could not be uploaded.</p>";
            }
        }
        if (!empty($_FILES['anyfile']['name'])) {
            echo '<h3>Handle uploaded single file</h3>';
            $anyfilepath = $_POST["anyfilepath"];
            $name = $_FILES['anyfile']['name'];
            $tmpname = $_FILES['anyfile']['tmp_name'];
            $size = $_FILES['anyfile']['size'];

            if (empty($anyfilepath)) {
                echo '<p class="error">File path/name is not set!</p>';
            } elseif ($anyfilepath{0} == '/') {
                echo '<p class="error">File path/location should be relative, not absolute!</p>';
            } else {
                if (move_uploaded_file($tmpname, $_POST["anyfilepath"])) {
                    echo "<p>File Name: $name</p>\n";
                    echo "<p>File Size: $size</p>\n";
                    echo "<p>Your file was successfully uploaded and moved to \"$anyfilepath\"!</p>\n";
                } else {
                    echo "<p>Your file could not be uploaded.</p>";
                }
            }
        }
    } else { 
        if (!check()) {
            echo '<p class="warn">There might be a problem with unzipping/extraction - have you '.
                'visited the <a href="?action=test">test page</a>?';
        }
        check_submit();
        // Display current version of DokuWiki (if not running safe-mode)
        if (!ini_get('safe_mode')) {
            $build = file_get_contents('VERSION');
            echo "<p>You are currently using <span class='warn'>$build</span></p>";
        }
        if ($direct_install) {
            $webpage = file_get_contents($filelist_url);
            /* There are no update/patch packages for DokuWiki
            $update_pattern = '#/([^/>]*(upgrade_from_*?|updater?(_from_.*?)?).zip)[^>]*>#';
            preg_match_all($update_pattern,$webpage,$files,PREG_PATTERN_ORDER);
            */
            preg_match_all('|/dokuwiki-([a-z0-9-]*)\.tgz|',$webpage,$files,PREG_PATTERN_ORDER);
            $versions = $files[1]; // rsort($versions);
            $direct_install_text = '
                <tr><td>Choose version from the DokuWiki homepage: </td><td>
                <select name="version" id="version" onfocus="clearOpposites(this.id)">
                <option>Select a version</option>';
            foreach($versions as $version) {
                $direct_install_text .= "\n<option>$version</option>";
            }
            $direct_install_text.= "\n</select></td></tr>";
        }

        echo '
        <p>Either upload a file from your computer';
        if ($direct_install) {
            echo ', give an URL to a zip file, or select a version from the pulldown menu, 
            that will be automatically fetched from 
            <a href="'.$filelist_url.'">the DokuWiki homepage</a> - quite convenient...</p>';
        } else {
            echo ' or give an URL to a zip file.';
        }
        echo '
        <form enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'" method="POST">
        <input type="hidden" name="action" value="upgrade" />
        <table>
        <tr><th colspan="2" align="left">DokuWiki zip-file</th></tr>
        '.$direct_install_text.'
        <tr><td>Choose zip-file to upload: </td><td><input type="file" id="file" name="file"
            onfocus="clearOpposites(this.id)" /></td></tr>
        <tr><td>Choose zip-file location: </td><td><input type="text" id="url" name="url" size="20" 
            onfocus="clearOpposites(this.id)" /> (Complete URL.)</td></tr>
        <tr><th colspan="2" align="left">Any file (not zipped)</th></tr>
        <tr><td>Path (with filename)</td><td><input type="text" name="anyfilepath" size="20" /> 
        (Relative from this directory.)</td></tr>
        <tr><td>Choose file to upload: </td><td><input type="file" name="anyfile" /></td></tr>
        <tr><th colspan="2" align="left">&nbsp;</th></tr>
        <tr><th align="left">Password </th><td><input type="password" name="password" size="20" /><input 
            type="submit" name="submit" value="Upgrade Files" />
        </td></tr>
        </table>
        </form>';
    }
} elseif ($_REQUEST["action"] == "uninstall"){
    if (tpl_action()) {
        echo '<h2>Uninstall DokuWiki templates</h2>';
    } else {
        echo '<h2>Uninstall DokuWiki and/or any related files</h2>';
    }
    if (ini_get('safe_mode') && !$safe_mode_2nd_run) {
        // Always recreate dokuwiki-setup-safemode.php to ensure correct user id
        if (file_exists("dokuwiki-setup-safemode.php")) {
            // If we can't delete it, it is owned by the webserver user - no problem
            @unlink("dokuwiki-setup-safemode.php");
        }
        file_put_contents("dokuwiki-setup-safemode.php", file_get_contents("dokuwiki-setup.php"));
        echo '<p>Because of safe-mode you must 
            <a href="dokuwiki-setup-safemode.php?action=uninstall&amp;type='.$_REQUEST['type'].'">go here to delete</a>';
        echo $footer;
        return;
    } elseif (!is_writable($this_dir) && !$safe_mode_2nd_run) {
        echo "<p class=\"error\">Can not write to this directory ($this_dir) and hence can not uninstall.</p>";
    } elseif (submit_ok()) {
        if (tpl_action()) {
            foreach ($_REQUEST['tpl_delete'] as $value) {
                $entry = $dokuwiki_tpl_dir.$value;
                if (is_dir($entry)) {
                    delete($entry,true);
                } else {
                    delete($entry);
                }
            }
        } else {
            // Deleting any directory and/or file 
            delete($_REQUEST["dir"],true);
            delete($_REQUEST["file"]);
            // Deleting DokuWiki directories and/or files 
            foreach ($dokuwiki_dirs as $dir) {
                if ($_REQUEST[$dir] == "on") delete($dir,true);
            }
            foreach ($dokuwiki_files as $file) { 
                if ($_REQUEST[str_replace(".","_",$file)] == "on") delete($file);
            }
        }
    } else {
        check_submit();
        $formstart = '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">
            <input type="hidden" name="action" value="uninstall" />';
        if (tpl_action()) {
            $formstart .= '<input type="hidden" name="type" value="templates" />';
            echo '<p>By default nothing is selected. Just select the template, directory or files, you want to remove.</p>';
            $list = "";
            $list_format = "\n".'<tr><td>%s</td><td><input type="checkbox" name="tpl_delete[]" value="%s"></td></tr>';
            $dh = opendir($dokuwiki_tpl_dir) or die("Couldn't open $dokuwiki_tpl_dir.\n");
            while(false !== ($entry = readdir($dh))) {
                if ( ($entry != ".") && ($entry != "..") ) {
                    if (is_dir($dokuwiki_tpl_dir.$entry)) {
                        $entry .= '/';
                    }
                    $list .= sprintf($list_format,$entry,$entry);
                }
            }
         } else {
            if (!dokuwiki_exists()) {
                echo '<p class="warn">DokuWiki doesn\'t seem to be installed - is
                "dokuwiki-setup.php" in the correct directory?.</p>';
            }
            echo '<p>By default everything is selected. Use the &quot;Any directory&quot; 
            or &quot;Any file&quot; field to remove anything owned by the webserver user.</p>';
            $list = "";
            foreach ($dokuwiki_dirs as $dir) {
                if (file_exists($dir)) {
                    $list .= "\n".'<tr><td>'.$dir.
                    '</td><td><input type="checkbox" checked="checked" name="'.$dir.'"></td></tr>';
                }
            }
            foreach ($dokuwiki_files as $file) {
                if (file_exists($file)) {
                    $list .= "\n".'<tr><td>'.$file.
                    '</td><td><input type="checkbox" checked="checked" name="'.str_replace(".","_",$file).'"></td></tr>';
                }
            }
            if (!empty($list)) {
                $list = '<tr><th colspan="2" align="left">DokuWiki files/directories</th></tr>' . $list;
            }
            $formend = '
            <tr><th colspan="2" align="left">&nbsp;</th></tr>
            <tr><td>Any directory</td><td><input type="text" name="dir" size="20" /> 
            (Relative path from this directory.)</td></tr>
            <tr><td>Any file</td><td><input type="text" name="file" size="20" /> 
            (Relative path from this directory.)</td></tr>';
        }
        $formend .= '<tr><th colspan="2" align="left">&nbsp;</th></tr>
        <tr><th align="left">Password </td><td><input type="password" name="password" size="20" /><input 
            type="submit" name="submit" value="Delete Files" /></td></tr>
        </table>
        </form>';
        echo $formstart . '<table>' . $list . $formend;
    }
} elseif ($_REQUEST["action"] == "test"){
    echo '
    <h2>Test</h2>
    <p>DokuWiki is normally packed as a gzipped tar archive, while many templated are 
    zip archives. The test below tell you if this script is likely to work.</p>
    <h3>Unzipping</h3>
    <p>Read the text below to see if unzipping, is likely to work on your server.</p>';
    check_zip(true);
    echo '
    <h3>Tar extraction</h3>
    <p>Read the text below to see if tar extraction, is likely to work on your server.</p>';
    check_tgz(true);
    echo '
    <h3>Other problem areas</h3>
    <p>Checking safe mode and similar problems... 
    If there is nothing reported below, you are OK.</p>';
    check_safe_mode();
    check_open_basedir();
    check_allow_url_fopen();
} elseif ($_REQUEST["action"] == "extras"){
    echo '
    <h2>Extras</h2>
    <p>There is a lot of contributed software to DokuWiki - plugins and templates - that 
    also needs to be installed. Luckily recent versions of DokuWiki comes with a Plugin
    Manager that makes installing very easy. However, according to the DokuWiki 
    <a href="http://wiki.splitbrain.org/wiki:tpl:install">documentation</a> the installation
    of templates is 100% manual. That\'s why I have extended this setup script to so you use
    it to install/update/uninstall templates too.</p>
    <ul>
    <li class="level1"><div class="li"><a href="?action=install&amp;type=templates">Install templates</a></div></li>
    <li class="level1"><div class="li"><a href="?action=install&amp;type=templates">Upgrade templates</a> - 
        currently uses the install action ...</div></li>
    <li class="level1"><div class="li"><a href="?action=uninstall&amp;type=templates">Uninstall templates</a></div></li>
    </ul>
    ';
 } elseif ($_REQUEST["action"] == "help"){
    echo '
    <h2>Help</h2>
    <p style="text-align: center; text-width: 80%"> [ 
    <a href="?action=help#security">Security</a> | 
    <a href="?action=help#fileperm">File permissions</a> | 
    <a href="?action=help#actions">Actions</a> | 
    <a href="?action=help#dir_structure">Directory structure</a> | 
    <a href="?action=help#safe_mode">Safe-mode</a> | 
    <a href="?action=help#config">Configuration of this script</a> ] </p>
    <p class="warn">If you install DokuWiki with this script, you need to use this script to uninstall 
    too since the files are owned by the webserver user.</p>
    <!-- p>This script has it\'s own page in the
    <a href="http://www.pivotlog.net/docs/doku.php?id=pivot_setup">Pivot documentation</a>.
    In addition it has it\'s own thread in the DokuWiki forum - 
    <a href="http://forum.pivotlog.net/viewtopic.php?t=10713">DokuWiki Setup discussion</a>.
    If you still have any questions after reading the documentation or this help page, ask 
    your questions in the forum thread.</p -->
    <a name="security"></a>
    <h3>Security</h3>
    <p>This script is password protected. <em>'; 
    if (empty($password)) {
        echo '<span class="warn">Currently the password is blank and hence the script is disabled.</span> ';
    }
    echo '
    Open the script in a text-editor to change/set the password in the 
    configuration section in the beginning of this file. </em>
    Remember that the password is not
    encrypted when sent to the server so don\'t use a valuable one. </p>
    <a name="fileperm"></a>
    <h3>File permissions</h3>
    <p>When installing with this script you need this directory ('.$this_dir.') to be writable by 
    the server, i.e, world-writable. You can change the permission on this directory in a shell on the 
    webserver (if you have access) or using a
    FTP client - "chmod 777" to make it world-writable or "chmod 755" to make it secure (after install/uninstall).</p>
    <p class="warn">You should always make the directory secure - make it NOT
    world-writable after you are done installing/uninstalling. If you don\'t do
    that this solution is as bad as the current way of installing DokuWiki
    (security-wise).</p>
    <a name="actions"></a>
    <h3>Actions</h3>
    <p>As can be seen from the menu, the script has five actions - install,
    upgrade, uninstall, chmod and test. These do the obvious things. However, some comments are
    in place:</p>
    <ul>
    <li class="level1"><div class="li">You can install/upgrade by uploading from your local machine or by
    selecting the wanted version from the list in the pulldown menu. (The list is
    fetched from the DokuWiki homepage in real time.)</div></li>
    <li class="level1"><div class="li">To add new extensions use the install action. The new extension must
    be in a zip archive that follows the same directory structure as the standard
    DokuWiki zip files.</div></li>
    <li class="level1"><div class="li">To update a single file for DokuWiki or some of the extensions, just
    upload that single file with the correct path using the upgrade action. It\'s
    no longer neccessary to make a zip with just that file (that follows the 
    same directory structure as the standard DokuWiki zip files). The templates 
    and similar should of course still be
    edit through the DokuWiki web interface.</div></li>
    <li class="level1"><div class="li">You can use the delete action to delete any directory or file 
    owned by the webserver user. </div></li>
    <li class="level1"><div class="li">The purpose of chmodding is to allow people to edit their
    templates (and such) outside DokuWiki (and upload the modified files with
    FTP).</div></li>
    </ul>
    <p>After you have installed DokuWiki with this script, no more needs to be done over FTP - just 
    <a href="'.dirname($_SERVER['PHP_SELF']).'/install.php">configure DokuWiki</a> and start Wiki-ing.</p>
    <a name="dir_structure"></a>
    <h3>Directory structure</h3>
    <p>This script assumes that DokuWiki should be and is installed in this directory. If that is not the case,
    move this file/script to the correct directory.</p>    
    <a name="safe_mode"></a>
    <h3>Safe-mode</h3>
    <p>When you have <a href="http://php.net/features.safe-mode">Safe-mode</a>
    enabled, you <em>must</em> install using DokuWiki Setup. If you don\'t do that, DokuWiki will not be able to write
    the pages or install plugins automatically.</p>
    <h3>Configuration of this script</h3>
    <p>The following variables, with their current values shown, can be change by 
    the user by opening this script in a text editor:</p>
    <pre>
$tar_executable = "'.$tar_executable.'"
$tar_options = "'.$tar_options.'"
$tar_stripoption = "'.$tar_stripoption.'"
$zip_executable = "'.$zip_executable.'"
$unzip_executable = "'.$unzip_executable.'"
$unzip_overwrite_arg = "'.$unzip_overwrite_arg.'"
$download_url = "'.$download_url.'"
$filelist_url = "'.$filelist_url.'"
$direct_install = '.($direct_install?"true":"false").'</pre>
    <p>Most of these variables are self-explanatory... 
    <tt>$download_url</tt> is the URL to a tar-bal for a given version of DokuWiki and
    <tt>$filelist_url</tt> is the URL to the current list of DokuWiki versions on
    the DokuWiki homepage. <tt>$direct_install</tt> is used to disable the &quot;direct
    install&quot;-feature because checking with the DokuWiki homepage slows down the loading
    of the install/update page of this script.
    </p>'; 
} else {
    if (isset($_REQUEST["action"]) && !empty($_REQUEST["action"])) {
        echo '<p class="error">Unknown action.</p>';
    }
    echo '<p>This PHP script enables you to install DokuWiki:</p>
    <ul>
    <li class="level1"><div class="li">Directly from the <a href="http://www.splitbrain.org/projects/dokuwiki">DokuWiki homepage</a> 
        (without downloading to your local machine and then uploading the files to your host with FTP). </div></li>
    <li class="level1"><div class="li">In a safe manner (without world-writeable files/directories).</div></li>
    <li class="level1"><div class="li">On a server which have <a href="http://www.php.net/features.safe-mode">Safe Mode</a> enabled. 
    You no longer need to use any safemode hacks (as you do after doing a manual install).</div></li>
    </ul>
    <p>You can also install DokuWiki templates with this script - visit the <a href="?action=extras">Extras page</a>.</p>
    <p>Read more on the <a href="?action=help">help page</a>.</p>
    <h3>Status</h3>';
    if (is_writable($this_dir)) {
        echo '<p class="warn">This directory ('.$this_dir.')
        is writable by the webserver which may indicate that the directory is world-writable.
        You normally do NOT want this unless you are about to install, upgrade
        or uninstall DokuWiki (using DokuWiki Setup)!</p>';
    } else {
        echo '<p>This directory ('.$this_dir.')
        is NOT writable by the webserver which indicate that the directory is NOT world-writable.
        This is how it should be unless you are about to install, upgrade or
        uninstall DokuWiki (using DokuWiki Setup)!</p>';
    }
    check_safe_mode();
    echo '<p>Also check the <a href="?action=test">test page</a> to see if any
    known problems are detected and if / how your server supports unzippingg/extraction.</p>';

    echo '<h3>About</h3>
    <p>This script is written by Hans Nordhaug, aka "hansfn" most places online. 
    This is version '.$version.'. I wrote it as service for some people attending 
    a small talk about Wikis, and it\'s based on my script Pivot Setup - for the blogging
    engine <a href="http://pivotlog.net/">Pivot</a> / <a href="http://pivotx.net/">PivotX</a>.
    The code is ugly and not tested enough, but the script did the job for a lot of Pivot users...</p>';
}

echo $footer;

function unpackZip($file) {
    global $this_dir;
    if ($zip = zip_open($file)) {
        if ($zip) {
            while ($zip_entry = zip_read($zip)) {
                $name = zip_entry_name($zip_entry);
                if (zip_entry_open($zip,$zip_entry,"r")) {
                    if (strrpos($name,"/") == (strlen($name)-1)) {
                        $dir_name = $name;
                    } else { 
                        $dir_name = dirname($name);
                    }
                    if ($dir_name != ".") {
                        $dir_op = $this_dir."/";
                        foreach ( explode("/",$dir_name) as $k) {
                            $dir_op = $dir_op . $k;
                            if (is_file($dir_op)) unlink($dir_op);
                            if (!is_dir($dir_op)) mkdir($dir_op);
                            $dir_op = $dir_op . "/" ;
                        }
                    }
                    if (!is_dir($this_dir."/".$name)) {
                        $len = zip_entry_filesize($zip_entry);
                        $buf = zip_entry_read($zip_entry, $len);
                        $fp=fopen($this_dir."/".$name,"wb");
                        fwrite($fp,$buf,$len);
                        fclose($fp);
                    }
                    zip_entry_close($zip_entry);
                } else {
                    return false;
                }
            }
            zip_close($zip);
        }
    }
    return true;
}

function rmdirRecursive($path,$followLinks=false) {
    if (!file_exists($path)) return false;
    if (!is_readable($path)) return false;
    if (!is_dir($path)) return false;
    $dir = opendir($path) ;
    while ( false !== ($entry = readdir($dir))) {
        if ( is_file( "$path/$entry" ) || ((!$followLinks) && is_link("$path/$entry")) ) {
            unlink( "$path/$entry" );
        } elseif ( is_dir( "$path/$entry" ) && $entry!='.' && $entry!='..' ) {
            rmdirRecursive( "$path/$entry" ) ;
        }
    }
    closedir($dir) ;
    return rmdir($path);
}

function install($name, $upgrade = false) {
    global $filetype, $dokuwiki_tpl_dir, $this_dir;
    if (tpl_action()) {
        chdir($dokuwiki_tpl_dir);
    }
    if (!ini_get('safe_mode')) {
        $name = "$this_dir/$name";
    }
    if ($filetype == 'zip') {
        install_zip($name, $upgrade);
    } else {
        install_tgz($name, $upgrade);
    }
    if (tpl_action()) {
        chdir($this_dir);
    }
}

function install_tgz($name, $upgrade = false) {
    global $filetype, $tar_executable, $tar_options, $tar_stripoption,
        $safe_mode_2nd_run, $password, $zipfile, $this_dir,
        $dokuwiki_dirs, $dokuwiki_files;
    // Backing up everything before upgrading	
    if ($upgrade) {
        backup();
    }
    $success = false;
    exec("$tar_executable --version", $output, $retval);
    if (isset($retval) && ($retval == 0)) {
        if (isset($_REQUEST['version'])) {
            $strip = 1;
        } else {
            $strip = 0;
        }
        echo '<pre>';
        system("$tar_executable $tar_stripoption=$strip $tar_options $name", $retval); 
        echo '</pre>';
        if ($retval != 0) {
            echo '
            <p class="warn">Extracting (with the tar executable) failed with exit code '.$retval.'. 
            Maybe the webservers error log has more info.</p>';
        } else {
            echo "<p>File successfully extracted (with the tar executable)!</p>\n";
            $success = true;
        }
    }
    if (!$success) {
        if (!file_exists("$this_dir/Tar.php")) {
            file_put_contents("$this_dir/Tar.php",
               file_get_contents("http://pivotlog.net/filebase/Misc_Files/pivot-setup/Tar.phps"));
            echo "<p>(Downloaded the Pear Archive_Tar class and using it for extraction.)</p>\n";
        }
        if (!file_exists("$this_dir/PEAR.php")) {
            file_put_contents("$this_dir/PEAR.php",
               file_get_contents("http://pivotlog.net/filebase/Misc_Files/pivot-setup/PEAR.phps"));
            echo "<p>(Downloaded the Pear base class needed by the Pear Archive_Tar class.)</p>\n";
        }
        require_once("$this_dir/Tar.php");
        $archive = new Archive_Tar($name, true);
        if (isset($_REQUEST['version'])) {
            $remove_path = 'dokuwiki-'.$_REQUEST['version'].'/';
        } else {
            $remove_path = '';
        }

        // TODO - add check for template install
        // $archive->extractModify('.',$remove_path);

        if (ini_get('safe_mode')) {
            // If safe mode: 
            // 1) extract the files at the root and create the directories in the first run.
            // 2) extract the directories contents in the second run.
            if (!$safe_mode_2nd_run) {
                foreach ($dokuwiki_dirs as $dir) {
                    mkdir("$this_dir/$dir");
                }
                $filelist = array();
                foreach ($dokuwiki_files as $file) {
                    $filelist[] = "$remove_path$file";
                }
                $archive->extractList($filelist, '.', $remove_path);
            }  else {
                $filelist = array();
                foreach ($dokuwiki_dirs as $dir) {
                    $filelist[] = "$remove_path$dir";
                }
                if ($archive->extractList($filelist,'.',$remove_path)) {
                    echo "<p>File successfully extracted (with Archive_Tar)!</p>\n";
                    $success = true;
                } else {
                    echo "<p>Couldn't extract file (with Archive_Tar)!</p>\n";
                }
            }
        } else {
            if ($archive->extractModify('.',$remove_path)) {
                echo "<p>File successfully extracted (with Archive_Tar)!</p>\n";
                $success = true;
            } else {
                echo "<p>Couldn't extract file (with Archive_Tar)!</p>\n";
            }
        }
    }
    if (ini_get('safe_mode') && !$safe_mode_2nd_run) {
        // Always recreate dokuwiki-setup-safemode.php to ensure correct user id
        if (file_exists("$this_dir/dokuwiki-setup-safemode.php")) {
            // If we can't delete it, it is owned by the webserver user - no problem
            @unlink("$this_dir/dokuwiki-setup-safemode.php");
        }
        file_put_contents("$this_dir/dokuwiki-setup-safemode.php", file_get_contents("$this_dir/dokuwiki-setup.php"));
        echo '<p class="warn">Because of safe mode the unzipping is done in two steps. 
            The first step is completed.  Do <a href="dokuwiki-setup-safemode.php?password='
            .$password.'&amp;action=install&amp;name='.$name.'&amp;filetype='.$filetype.'&amp;version='.
            $_REQUEST['version'].'&amp;type='.$_REQUEST['type'].'&amp;submit=doit">the second step</a> now.</p>';
    } elseif ($success) {
        if ($upgrade) {
            echo '<p>Done - upgraded successfully!</p>';
        } else {
            echo '<p>Done - installed successfully!</p>
                <p><a href="install.php">Click here</a> to configure DokuWiki!</p>';
        }
        unlink($name);
    }
}

function install_zip($name, $upgrade = false) {
    global $filetype, $zip_quiet, $unzip_executable, $unzip_overwrite_arg, 
        $safe_mode_2nd_run, $password, $zipfile, $this_dir;
    // Backing up everything before upgrading	
    if ($upgrade) {
        backup();
    }
    $success = false;
    if (!$success && function_exists('zip_open')) {
        if (@unpackZip($name)) {
            echo "<p>File successfully unzipped (with zip_open)!</p>\n";
            $success = true;
        } else {
            echo "<p>Couldn't unzip file (with zip_open)!</p>\n";
        }
    } 
    if (!$success) {
        exec("$unzip_executable -v", $output, $retval);
        if (isset($retval) && ($retval == 0)) {
            echo '<pre>';
            if ($upgrade) {
                system("$unzip_executable $zip_quiet $unzip_overwrite_arg $name", $retval);
            } else {
                system("$unzip_executable $zip_quiet $name", $retval); 
            }
            echo '</pre>';
            if ($retval != 0) {
                echo '
                    <p class="warn">Unzipping (with the unzip executable) failed with exit code '.$retval.'. 
                    Maybe the webservers error log has more info.</p>';
            } else {
                echo "<p>File successfully unzipped (with the unzip executable)!</p>\n";
                $success = true;
            }
        } 
    }
    if (!$success && function_exists('gzopen')) {
        if (!file_exists("$this_dir/ds-pclzip.lib.php")) {
            file_put_contents("$this_dir/ds-pclzip.lib.php",
               file_get_contents("http://pivotlog.net/filebase/Misc_Files/pivot-setup/ps-pclzip.lib.phps"));
            echo "<p>(Downloaded the PclZip library and using it for unzipping.)</p>\n";
        }
        require_once("$this_dir/ds-pclzip.lib.php");
        $archive = new PclZip($name);
        if ($archive->extract() == 0) {
            echo "<p>Couldn't unzip file (with gzopen/PclZip)!</p>\n";
        } else {
            echo "<p>File successfully unzipped (with gzopen/PclZip)!</p>\n";
            $success = true;
        }
    }
    if (ini_get('safe_mode') && !$safe_mode_2nd_run) {
        // Always recreate dokuwiki-setup-safemode.php to ensure correct user id
        if (file_exists("$this_dir/dokuwiki-setup-safemode.php")) {
            // If we can't delete it, it is owned by the webserver user - no problem
            @unlink("$this_dir/dokuwiki-setup-safemode.php");
        }
        file_put_contents("$this_dir/dokuwiki-setup-safemode.php", file_get_contents("$this_dir/dokuwiki-setup.php"));
        echo '<p class="warn">Because of safe mode the unzipping is done in two steps. 
            The first step is completed.  Do <a href="dokuwiki-setup-safemode.php?password='
            .$password.'&amp;action=install&amp;name='.$name.'&amp;filetype='.$filetype.
            '&amp;type='.$_REQUEST['type'].'&amp;submit=doit">the second step</a> now';
    } elseif ($success) {
        if ($upgrade) {
            echo '<p>Done - upgraded successfully!</p>';
        } else {
            if (tpl_action()) {
                echo '<p>Done - template successfully installed!</p>';
            } else {
                echo '<p>Done - installed successfully!</p>
                <p><a href="install.php">Click here</a> to configure DokuWiki!</p>';
            }
        }
        unlink($name);
    }
}

function check($disp_stat=false) {
    return check_zip($disp_stat) && check_tgz($disp_stat);
}

function check_tgz($disp_stat = false) {
    global $tar_executable;
    exec("$tar_executable --version", $output, $retval);
    if (!isset($retval) || ($retval != 0)) {
        $text .= '<p class="warn">It seems you don\'t have shell access (or the shell don\'t have a tar executable).</p>';
        $text .= '<p>We will use the Archive_Tar class from Pear in stead.</p>';
    }
    if ($disp_stat) {
        echo $text;
        echo "<p>Status: OK</p>\n";
    }
    return true;
}

function check_zip($disp_stat = false) {
    $ok = true;
    global $unzip_executable;
    if (!function_exists('zip_open')) {
        $text .= '<p class="warn">It seems your PHP doesn\'t have the "zip_open" function.</p>';
        exec("$unzip_executable -v", $output, $retval);
        if (!isset($retval) || ($retval != 0)) {
            if (!function_exists('gzopen')) {
                $text .= '<p>It also seems it doesn\'t have the "gzopen" function. 
                You might be in trouble since the unzip executable returned
                a non-zero error code (which normally means it\'s not
                found). You <em>may</em> resolve this by modifying the value for
                $unzip_executable in the header of this script.</p>';
                $ok = false;
            } else {
                $text .= '<p>I\'ll use the "gzopen" function (PclZip library) in stead.</p>';
            }
        } else {
            $text .= '<p>I\'ll use the unzip executable in the shell of your server in stead.</p>';
        }
    } else {
        $text .= '<p>It seems your PHP have the "zip_open" function - I\'ll use that.</p>';
    }
    if (!function_exists('zip_open') && !function_exists('gzopen')) {
        if (ini_get('safe_mode')) {
            if (ini_get('safe_mode_exec_dir') == "") {
                $text .= '<p class="error">Safe-Mode execute directory not set. The script
                    can not install unless you happen to have a "unzip" executable in the
                    root directory - which is *very* unlikely.</p>';
            } else {
                $text .= '<p>In addition you are running Safe-Mode. If unzip is not found in
                   the Safe-Mode execute directory, <b>'.ini_get('safe_mode_exec_dir').'</b>, 
                   there is no way the script can install for you - sorry. (You\'ll probably get 
                   error code 127 if unzip is not found.)</p>';
            }
        }
    }
    if ($disp_stat) {
        echo $text;
        if (!$ok) {
            echo "<p>Status: NOT OK</p>\n";
        } else {
            echo "<p>Status: OK</p>\n";
        }
    }
    return $ok;
}

function check_safe_mode() {
    if (ini_get('safe_mode')) {
        echo '<p class="warn">Your server is running PHP
        <a href="http://php.net/features.safe-mode">Safe-mode</a>, but
        DokuWiki Setup knows how to handle it.
        <a href="?action=help#safe_mode">Read</a> what you need to do.</p>';
    }
}

function check_open_basedir() {
    if ("x".ini_get("open_basedir") != "x") {
        echo '<p class="warn">Your server has
        <a href="http://php.net/features.safe-mode">open_basedir</a>
        restriction in action.
        <a href="?action=help#open_basedir">Read</a> how to work around it.</p>';
    }
}

function check_allow_url_fopen() {
    if (!ini_get('allow_url_fopen')) {
        echo '<p class="warn">Your server has disabled URL-aware fopen
        wrappers - read more about
        <a href="http://php.net/manual/en/ref.filesystem.php#ini.allow-url-fopen">allow_url_fopen</a>.
        Direct download from the DokuWiki homepage has been disabled. ($direct_install is
        false.)';
    }
}

// Taken from pvlib.php in Pivot.
function version_ok() {
   global $required_php_version;
   list($majorC, $minorC, $editC) = explode('[/.-]', PHP_VERSION);
   list($majorR, $minorR, $editR) = explode('[/.-]', $required_php_version);
   $ok = false;
   if ($majorC > $majorR) {  
       $ok = true; 
   } elseif ($majorC == $majorR) {
       if ($minorC > $minorR) { 
           $ok = true; 
       } elseif ($minorC == $minorR) {
           if ($editC  >= $editR) { 
               $ok = true; 
           }
       }
   }
   if (!$ok) {
        echo '<h2 class="error">Your PHP version is '.PHP_VERSION.'. DokuWiki 
        requires at least PHP version '.$required_php_version.' to run properly!</h2>
        <p>We are sorry, but until you (or your hosting provider) have
        upgraded PHP, DokuWiki will not work for you. There is no way to work
        around this version requirement.</p>';
   }
   return $ok;
}

function dokuwiki_exists() {
    if (ini_get('safe_mode')) {
        if (ini_get('allow_url_fopen')) {
            $pv_core_url = "http://".$_SERVER['HTTP_HOST'].
                dirname($_SERVER["PHP_SELF"])."/doku.php";
            $found = file_get_contents($pv_core_url);
            if($found === false) {
                return false;
            } else {
                return true;
            }
        } else {
            if (!file_exists("doku.php")) {
                return false;
            } else {
                return true;
            }
        }
    } else {
        if (!file_exists("doku.php")) {
            return false;
        } else {
            return true;
        }
    }
}

function ds_tempnam() {
    global $this_dir;
    if (ini_get('safe_mode')) {
        return tempnam($this_dir, "DS");
    } else {
        return tempnam("","");
    }
}

function submit_ok() {
    global $password;
    if (isset($_REQUEST["submit"]) && ($_REQUEST["password"] !="")) {
        if ($password != $_REQUEST["password"]) {
            return false;
        } else {
            return true;
        }
    }
}

function check_submit() {
    global $password;
    if (isset($_REQUEST["submit"])) { 
        if ($_REQUEST["password"] == "") {
            echo '<p class="warn">Empty password given.</p>';
        } elseif ($password != $_REQUEST["password"]) {
            echo '<p class="warn">Wrong password given.</p>';
        }
    }
}

function tpl_action() {
    if (isset($_REQUEST['type']) && ($_REQUEST['type'] == 'templates')) {
        return true;
    } else {
        return false;
    }
}

function delete($name, $dir = false) {
    if ($dir) {
        $type = "Directory";
        $funk = "rmdirRecursive";
    } else { 
        $type = "File";
        $funk = "unlink";
    }
    if (empty($name)) {
        // Silently ignore - the user knows what he is doing...
    } elseif ($name{0} == '/') {
        echo '<p class="error">"'.$name.'" is illegal - only relative paths are allowed!</p>';
    } else {
        if (file_exists($name)) {
            if ($funk($name)) {
                echo "<p>$type \"".$name."\" successfully deleted!</p>\n";
            } else {
                echo "<p class=\"warn\">Error while deleting ".strtolower($type)." \"".$name."\"!</p>\n";
            }
        } else {
            echo "<p class=\"warn\">$type \"".$name."\" doesn't exist!</p>\n";
        }
    }
}

function backup_not_used() {
    // FIXME - zip_open (and ps-pclzip.lib.php) supported too?
    global $zip_executable, $zip_quiet, $backup;
    if (!$backup) {
        echo '<p>Back-up disabled in config.</p>';
        return;
    }
    $backupname="pivot_base_and_extensions_".date("Ymd").".zip";
    unlink($backupname);
    $what_to_backup = "pivot";
    if (is_dir("extensions")) {
        $what_to_backup .= " extensions";
    }
    echo "<p>Trying to backing up old site as <em>$backupname</em> ".
        "(in the same directory as &quot;dokuwiki-setup.php&quot;) ...</p>";
    $success = false;
    if (!$success) {
        exec("$zip_executable -v", $output, $retval);
        if (isset($retval) && ($retval == 0)) {
            echo '<pre>';
            system("$zip_executable -r $backupname $what_to_backup", $retval);
            echo '</pre>';
            if ($retval != 0) {
                echo '<p class="warn">Packing (with the zip executable) exited with code '.$retval.
                    ' (Code 18 means that some files weren\'t readable and could\'t be included.)'.
                    ' Maybe the webservers error log has more info.</p>';
            } else {
                echo "<p>Back-up successful (packed with the zip executable).</p>\n";
                $success = true;
            }
        } 
    }
}

function backup() {
    global $zipfile, $backup;
    
    echo '<p>Back-up disabled in current version.</p>';
    return;
    
    if (!$backup) {
        echo '<p>Back-up disabled in config.</p>';
        return;
    }
    require_once 'pivot/modules/zip.lib.php';
    $backupname="pivot_base_and_extensions_".date("Ymd").".zip";
    echo "<p>Trying to backing up old site as <em>$backupname</em> ".
        "(in the same directory as &quot;dokuwiki-setup.php&quot;) ...</p>";
    $zipfile = new zipfile();
    ds_adddirtozip("pivot/");
    if (is_dir("extensions")) {
        ds_adddirtozip("extensions/");
    }
    unlink($backupname);
    file_put_contents($backupname, $zipfile->file());
    echo "<p>Back-up successful.</p>\n";
}

// Taken form pvlib.php
function ds_adddirtozip($dirname) {
    global $zipfile;
    $d = dir($dirname);
    while (false !== ($entry = $d->read())) {
        if ( ($entry != ".") && ($entry != "..") ) {
            if (is_dir($dirname.$entry)) {
                ds_adddirtozip($dirname.$entry."/");
            } else {
                $zipfile->addFile(file_get_contents($dirname.$entry), $dirname.$entry);
            }
        }
    }
    $d->close();
}

// ===========================================================================
// *** End of DokuWiki Setup - the following functions are support classes
// ===========================================================================

?>
