<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$id=(int)($_GET['id']??0);
$pdo->prepare("DELETE FROM expenses WHERE id=?")->execute([$id]);
flash('success','Expense deleted.');
header('Location: index.php');
