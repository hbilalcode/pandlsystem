<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT status FROM investors WHERE id=?"); $st->execute([$id]);
$cur=$st->fetchColumn();
if($cur){
    $new = $cur==='active' ? 'inactive' : 'active';
    $pdo->prepare("UPDATE investors SET status=? WHERE id=?")->execute([$new,$id]);
    flash('success','Investor status updated.');
}
header('Location: index.php');
