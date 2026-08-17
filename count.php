<?php
// ...existing code...
?>
<BODY onload='parent.bb_done_loading();'>
<?php $count = (string)($_GET['count'] ?? '0'); ?>
<div id="divFrameCount"><?= htmlspecialchars($count, ENT_QUOTES, 'UTF-8'); ?></div>