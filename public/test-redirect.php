<?php
echo "<h1>Redirect Test</h1>";
echo "<p>This page tests URL rewriting and parameter passing.</p>";
echo "<h2>Current URL Information:</h2>";
echo "<ul>";
echo "<li><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "</li>";
echo "<li><strong>QUERY_STRING:</strong> " . ($_SERVER['QUERY_STRING'] ?? 'not set') . "</li>";
echo "<li><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "</li>";
echo "<li><strong>REQUEST_METHOD:</strong> " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "</li>";
echo "</ul>";

echo "<h2>GET Parameters:</h2>";
if (!empty($_GET)) {
    echo "<ul>";
    foreach ($_GET as $key => $value) {
        echo "<li><strong>$key:</strong> " . htmlspecialchars($value) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No GET parameters received.</p>";
}

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='test-redirect.php?param=with_extension'>With .php extension</a></li>";
echo "<li><a href='test-redirect?param=without_extension'>Without .php extension</a></li>";
echo "<li><a href='generate-qr-code?class_id=123'>Generate QR Code Test</a></li>";
echo "</ul>";
?>