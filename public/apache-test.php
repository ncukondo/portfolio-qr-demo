<?php
echo '<h1>Apache Module Test</h1>';
echo '<h2>mod_rewrite status:</h2>';
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo '<p style="color: green;">✓ mod_rewrite is enabled</p>';
    } else {
        echo '<p style="color: red;">❌ mod_rewrite is NOT enabled</p>';
    }
    echo '<h3>All Apache modules:</h3>';
    echo '<ul>';
    foreach ($modules as $module) {
        echo '<li>' . $module . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>Cannot detect Apache modules (function not available)</p>';
}

echo '<h2>Request Information:</h2>';
echo '<ul>';
echo '<li><strong>REQUEST_URI:</strong> ' . ($_SERVER['REQUEST_URI'] ?? 'not set') . '</li>';
echo '<li><strong>REQUEST_METHOD:</strong> ' . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . '</li>';
echo '<li><strong>QUERY_STRING:</strong> ' . ($_SERVER['QUERY_STRING'] ?? 'not set') . '</li>';
echo '<li><strong>SCRIPT_NAME:</strong> ' . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . '</li>';
echo '</ul>';
?>
