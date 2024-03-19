<?php

$plugin['version'] = '0.3';
$plugin['author'] = 'Robert Wetzlmayr';
$plugin['author_uri'] = 'https://wetzlmayr.at/awasteofwords/wet_plugout-textpattern-plugin';
$plugin['description'] = 'One button plugin state save and restore.';
$plugin['type'] = 1;

if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001);
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);

$plugin['flags'] = 0;

@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h3. Find conflicting Textpattern plugins with ease

*wet_plugout* saves and restores the 'active' state of all plugins at the click of a button.

h4. usage:

# Install, enable, enjoy - no configuration required. Disable and re-enable all active plugins on the extension tab labelled *Plugout*.

h4. Licence and Disclaimer

This plug-in is released under the "Gnu General Public Licence":http://www.gnu.org/licenses/gpl.txt.

# --- END PLUGIN HELP ---

<?php
}

# --- BEGIN PLUGIN CODE ---

if (@txpinterface == 'admin') {
    add_privs('wet_plugout', '1,2');
    register_tab('extensions', 'wet_plugout', 'Plugout');
    register_callback('wet_plugout', 'wet_plugout');
}

function wet_plugout($event, $step)
{
    global $prefs;
    function my_gTxt($text)
    {
        $gTxt = array(
            'disable' => 'Disable',
            'enable' => 'Enable',
            'no_active_plugins' => 'No active plugins.',
            'about_to_disable' => 'These plugins will be disabled:',
            'about_to_enable' => 'These plugins will be enabled:',
            'file_error' => 'A problem occured while writing to file ',
            'manage_plugins' => 'Manage plugins'
        );
        return $gTxt[$text];
    }

    function fout($f, $s)
    {
        $r = fopen($f, 'w');
        if (!$r) return false;
        $out = fputs($r, $s);
        fclose($r);
        return $out;
    }

    function fin($f)
    {
        if (!file_exists($f)) return false;
        $r = fopen($f, 'r');
        if (!$r) return false;
        $out = fgets($r);
        fclose($r);
        return $out;
    }

    $f = $prefs['tempdir'] . '/' . __FUNCTION__ . '.txt';
    $fileerror = false;

    pagetop('Plugout', (!empty($step) ? gTxt('preferences_saved') : ''));

    if ($step == 'disable') {
        // currently active plugins?
        $plugins = safe_column('name', 'txp_plugin', "status='1' and name <> '" . __FUNCTION__ . "' order by name");
        safe_update('txp_plugin', 'status = \'0\'', 'name in (\'' . join("', '", $plugins) . '\')');
        $plugins = join('|', $plugins);
        if (!fout($f, $plugins)) $filerror = true;
    } elseif ($step == 'enable') {
        // previously saved plugin state?
        $plugins = @fin($f);
        if (!empty($plugins)) {
            $plugins = explode('|', $plugins);
            safe_update('txp_plugin', 'status = \'1\'', 'name in (\'' . join("', '", $plugins) . '\')');
            unlink($f);
        }
    }

    $plugins = @fin($f);
    if (empty($plugins)) {
        $step = 'disable';
        $plugins = safe_column('name', 'txp_plugin', "status='1' and name <> '" . __FUNCTION__ . "' order by name");
        $plugins = join(', ', $plugins);
    } else {
        $step = 'enable';
        $plugins = join(', ', explode('|', $plugins));
    }

    echo
    '<div style="margin:auto;width:30em">';

    if (empty($plugins)) {
        echo my_gTxt('no_active_plugins');
    } elseif ($fileerror) {
        echo my_gTxt('file_error') . $f;
    } else {
        echo form(
            n . hed(($step == 'disable') ? my_gTxt('about_to_disable') : my_gTxt('about_to_enable'), 3) .
            n . graf($plugins) .
            n . eInput(__FUNCTION__) .
            n . sInput($step) .
            n . graf(fInput('submit', '', my_gTxt($step), 'smallerbox')) .
            n . graf(href(my_gTxt('manage_plugins'), '?event=plugin'))
        );
    }
    echo '</div>';
}

# --- END PLUGIN CODE ---

?>
