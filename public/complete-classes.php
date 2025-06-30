<?php
require_once '../vendor/autoload.php';

use App\Controllers\ClassCompletionController;

$controller = new ClassCompletionController();
$controller->handleCompletionUrl();