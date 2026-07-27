<?php
require __DIR__ . '/config/session.php';
session_start();

require_once __DIR__ . '/helpers/WhatsNew.php';
$whatsNew = WhatsNew::sync();

require __DIR__ . '/views/app.php';
