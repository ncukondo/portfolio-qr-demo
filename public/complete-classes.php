<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ClassCompletionController;

$controller = new ClassCompletionController();
$controller->handleCompletionUrl();