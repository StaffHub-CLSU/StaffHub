<?php
require_once __DIR__ . '/includes/bootstrap.php';

$auth = new Authentication();
$auth->logout();

header('Location: index.php');
exit;
