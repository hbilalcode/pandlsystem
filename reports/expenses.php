<?php
// Legacy URL — redirect to unified Reports hub.
$qs = $_GET;
$qs['type'] = 'expenses';
header('Location: index.php?' . http_build_query($qs));
exit;
