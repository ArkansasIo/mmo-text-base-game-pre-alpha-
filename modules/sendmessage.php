<?php
// Redirect shim: forward to the new messaging system compose view
include("../config.php");
$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$id = (int)($_GET['id'] ?? 0);
?>
<script type="text/javascript">
(function(){ sendData('messages','get','<?= $id ?>','compose'); })();
</script>
