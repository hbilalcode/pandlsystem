<?php
// Legacy URL — redirect to unified Reports hub.
$qs = $_GET;
$qs['type'] = 'individual';
header('Location: index.php?' . http_build_query($qs));
exit;
