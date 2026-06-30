<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$id=(int)($_GET['id']??0);
$pdo->prepare("DELETE FROM investors WHERE id=?")->execute([$id]); // users + distributions cascade
flash('success','Investor removed.');
header('Location: index.php');
