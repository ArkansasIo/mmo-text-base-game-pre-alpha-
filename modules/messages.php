<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);

$db    = $s->db_link;
$myUID = (int)$_SESSION['userid'];
$atype = $_REQUEST['atype'] ?? 'inbox';
$id    = (int)($_REQUEST['id'] ?? 0);
$msg   = '';

// ── ACTIONS ─────────────────────────────────────────────────────────────────

// Send (POST)
if ($atype === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $toUID   = (int)($_POST['toUID'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['message'] ?? '');
    $replyTo = (int)($_POST['replyToMid'] ?? 0);

    if ($toUID <= 0 || $toUID === $myUID) {
        $msg = '<p class="msg-err">Invalid recipient.</p>';
        $atype = 'compose';
    } elseif ($body === '') {
        $msg = '<p class="msg-err">Message body cannot be empty.</p>';
        $atype = 'compose';
    } else {
        $subject = $subject ?: '(no subject)';
        $time    = date("M j H:i");
        $stmt    = $db->prepare("INSERT INTO messages (fromUID,toUID,subject,message,timeSent,isRead,isDeleted,replyToMid) VALUES (?,?,?,?,?,0,0,?)");
        $stmt->bind_param("iisssi", $myUID, $toUID, $subject, $body, $time, $replyTo);
        if ($stmt->execute()) {
            $newMid = (int)$db->insert_id;
            echo '<style>' . msgStyles() . '</style>';
            echo '<div class="msg-shell"><p class="msg-ok">&#10003; Message sent.</p>';
            echo '<p><a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'' . $newMid . '\',\'sent\')">View in Sent</a></p></div>';
            echo "Query Count: " . $s->queryCount . "<br>";
            $pagegen->stop(); print('page generation time: '.$pagegen->gen()); exit;
        } else {
            $msg = '<p class="msg-err">Send failed — please try again.</p>';
            $atype = 'compose';
        }
    }
}

// Delete single
if ($atype === 'delete' && $id > 0) {
    $stmt = $db->prepare("UPDATE messages SET isDeleted=1 WHERE mid=? AND (toUID=? OR fromUID=?) LIMIT 1");
    $stmt->bind_param("iii", $id, $myUID, $myUID);
    $stmt->execute();
    $atype = 'inbox';
    $msg   = '<p class="msg-ok">Message deleted.</p>';
}

// Delete all inbox
if ($atype === 'deleteAll') {
    $stmt = $db->prepare("UPDATE messages SET isDeleted=1 WHERE toUID=?");
    $stmt->bind_param("i", $myUID);
    $stmt->execute();
    $atype = 'inbox';
    $msg   = '<p class="msg-ok">All messages deleted.</p>';
}

// Mark read
if ($atype === 'read' && $id > 0) {
    $db->query("UPDATE messages SET isRead=1 WHERE mid=" . $id . " AND toUID=" . $myUID . " LIMIT 1");
}

// ── UNREAD COUNT ─────────────────────────────────────────────────────────────
$unreadQ = $db->prepare("SELECT COUNT(*) AS c FROM messages WHERE toUID=? AND isRead=0 AND isDeleted=0");
$unreadQ->bind_param("i", $myUID);
$unreadQ->execute();
$unread = (int)($unreadQ->get_result()->fetch_object()->c ?? 0);

// ── CSS + SHELL ───────────────────────────────────────────────────────────────
echo '<style>' . msgStyles() . '</style>';
echo '<div class="msg-shell">';

// ── TAB BAR ──────────────────────────────────────────────────────────────────
$tabs = [
    'inbox'   => 'Inbox' . ($unread > 0 ? ' <span class="msg-badge">' . $unread . '</span>' : ''),
    'sent'    => 'Sent',
    'compose' => '&#x2709; Compose',
];
echo '<div class="msg-tabs">';
foreach ($tabs as $tab => $label) {
    $active = ($atype === $tab || ($atype === 'read' && $tab === 'inbox')) ? ' active' : '';
    echo '<a class="msg-tab' . $active . '" href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'0\',\'' . $tab . '\')">' . $label . '</a>';
}
echo '</div>';

echo $msg;

// ── COMPOSE ──────────────────────────────────────────────────────────────────
if ($atype === 'compose' || $atype === 'reply') {
    $toName = '';
    $toUIDPre = 0;
    $subjectPre = '';
    $replyToMid = 0;

    if ($atype === 'reply' && $id > 0) {
        $stmt = $db->prepare("SELECT fromUID, subject FROM messages WHERE mid=? AND toUID=? LIMIT 1");
        $stmt->bind_param("ii", $id, $myUID);
        $stmt->execute();
        $orig = $stmt->get_result()->fetch_object();
        if ($orig) {
            $toUIDPre   = (int)$orig->fromUID;
            $subjectPre = preg_match('/^Re:/i', $orig->subject) ? $orig->subject : 'Re: ' . $orig->subject;
            $replyToMid = $id;
            $stmt2 = $db->prepare("SELECT uname FROM users WHERE uid=? LIMIT 1");
            $stmt2->bind_param("i", $toUIDPre);
            $stmt2->execute();
            $u = $stmt2->get_result()->fetch_object();
            $toName = $u ? $u->uname : '';
        }
    } elseif ($id > 0) {
        $stmt = $db->prepare("SELECT uname FROM users WHERE uid=? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_object();
        if ($u) { $toName = $u->uname; $toUIDPre = $id; }
    }

    echo '<div class="msg-compose">';
    echo '<h3>New Message</h3>';
    echo '<form method="post" action="messages.php?time=' . time() . '" onsubmit="return validateMsg(this)">';
    echo '<input type="hidden" name="atype" value="send">';
    echo '<input type="hidden" name="replyToMid" value="' . $replyToMid . '">';
    echo '<table class="msg-form-tbl"><tbody>';
    echo '<tr><td>To:</td><td>';
    echo '<input type="text" id="toUser1" name="toUserDisplay" value="' . htmlspecialchars($toName, ENT_QUOTES) . '" autocomplete="off" onkeyup="autocomplete(this,event)" placeholder="Player name..." required>';
    echo '<input type="hidden" id="userID2" name="toUID" value="' . $toUIDPre . '">';
    echo '</td></tr>';
    echo '<tr><td>Subject:</td><td><input type="text" name="subject" value="' . htmlspecialchars($subjectPre, ENT_QUOTES) . '" placeholder="(optional)" maxlength="255"></td></tr>';
    echo '<tr><td valign="top">Message:</td><td><textarea name="message" rows="10" cols="60" placeholder="Type your message here..." required></textarea></td></tr>';
    echo '<tr><td></td><td><button type="submit" class="msg-btn">Send Message</button></td></tr>';
    echo '</tbody></table>';
    echo '</form>';
    echo '<script>function validateMsg(f){var uid=document.getElementById("userID2").value;if(!uid||uid==0){alert("Please select a valid player from the autocomplete list.");return false;}return true;}</script>';
    echo '</div>';

// ── INBOX ─────────────────────────────────────────────────────────────────────
} elseif ($atype === 'inbox') {
    $stmt = $db->prepare(
        "SELECT m.mid, m.fromUID, u.uname AS sender, m.subject, m.timeSent, m.isRead
         FROM messages m INNER JOIN users u ON u.uid=m.fromUID
         WHERE m.toUID=? AND m.isDeleted=0
         ORDER BY m.mid DESC LIMIT 100");
    $stmt->bind_param("i", $myUID);
    $stmt->execute();
    $rows = $stmt->get_result();

    echo '<div class="msg-toolbar">';
    echo '<a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'0\',\'compose\')" class="msg-btn-sm">&#x2709; Compose</a>';
    echo ' &nbsp; <a href="javascript:void(0)" onclick="if(confirm(\'Delete all messages?\'))sendData(\'messages\',\'get\',\'0\',\'deleteAll\')" class="msg-btn-sm danger">Delete All</a>';
    echo '</div>';

    if ($rows->num_rows === 0) {
        echo '<p class="msg-empty">Your inbox is empty.</p>';
    } else {
        echo '<table class="msg-list"><thead><tr><th></th><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead><tbody>';
        while ($row = $rows->fetch_object()) {
            $unreadMark = !$row->isRead ? '&#9679; ' : '';
            $rowClass   = !$row->isRead ? 'unread' : '';
            echo '<tr class="' . $rowClass . '">';
            echo '<td>' . $unreadMark . '</td>';
            echo '<td><a href="javascript:void(0)" onclick="sendData(\'user\',\'get\',\'' . (int)$row->fromUID . '\')">' . htmlspecialchars($row->sender, ENT_QUOTES) . '</a></td>';
            echo '<td><a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'' . (int)$row->mid . '\',\'read\')">' . htmlspecialchars($row->subject, ENT_QUOTES) . '</a></td>';
            echo '<td>' . htmlspecialchars($row->timeSent, ENT_QUOTES) . '</td>';
            echo '<td><a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'' . (int)$row->mid . '\',\'delete\')" class="del-link" title="Delete">&#x1F5D1;</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

// ── SENT ──────────────────────────────────────────────────────────────────────
} elseif ($atype === 'sent') {
    $stmt = $db->prepare(
        "SELECT m.mid, m.toUID, u.uname AS recipient, m.subject, m.timeSent, m.isRead
         FROM messages m INNER JOIN users u ON u.uid=m.toUID
         WHERE m.fromUID=? AND m.isDeleted=0
         ORDER BY m.mid DESC LIMIT 100");
    $stmt->bind_param("i", $myUID);
    $stmt->execute();
    $rows = $stmt->get_result();

    echo '<div class="msg-toolbar">';
    echo '<a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'0\',\'compose\')" class="msg-btn-sm">&#x2709; Compose</a>';
    echo '</div>';

    if ($rows->num_rows === 0) {
        echo '<p class="msg-empty">No sent messages.</p>';
    } else {
        echo '<table class="msg-list"><thead><tr><th>To</th><th>Subject</th><th>Sent</th><th>Read</th><th></th></tr></thead><tbody>';
        while ($row = $rows->fetch_object()) {
            echo '<tr>';
            echo '<td><a href="javascript:void(0)" onclick="sendData(\'user\',\'get\',\'' . (int)$row->toUID . '\')">' . htmlspecialchars($row->recipient, ENT_QUOTES) . '</a></td>';
            echo '<td>' . htmlspecialchars($row->subject, ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($row->timeSent, ENT_QUOTES) . '</td>';
            echo '<td>' . ($row->isRead ? '<span style="color:#6f6">&#10003;</span>' : '<span style="color:#888">&#8213;</span>') . '</td>';
            echo '<td><a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'' . (int)$row->mid . '\',\'delete\')" class="del-link" title="Delete">&#x1F5D1;</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

// ── READ ──────────────────────────────────────────────────────────────────────
} elseif ($atype === 'read' && $id > 0) {
    $stmt = $db->prepare(
        "SELECT m.*, u.uname AS senderName, u2.uname AS recipientName
         FROM messages m
         INNER JOIN users u  ON u.uid  = m.fromUID
         INNER JOIN users u2 ON u2.uid = m.toUID
         WHERE m.mid=? AND (m.toUID=? OR m.fromUID=?) AND m.isDeleted=0 LIMIT 1");
    $stmt->bind_param("iii", $id, $myUID, $myUID);
    $stmt->execute();
    $message = $stmt->get_result()->fetch_object();

    if (!$message) {
        echo '<p class="msg-err">Message not found.</p>';
    } else {
        // Thread: collect reply chain
        $thread = [$message];
        $parentId = (int)$message->replyToMid;
        while ($parentId > 0) {
            $stmt2 = $db->prepare("SELECT m.*, u.uname AS senderName FROM messages m INNER JOIN users u ON u.uid=m.fromUID WHERE m.mid=? AND (m.toUID=? OR m.fromUID=?) LIMIT 1");
            $stmt2->bind_param("iii", $parentId, $myUID, $myUID);
            $stmt2->execute();
            $parent = $stmt2->get_result()->fetch_object();
            if (!$parent) break;
            array_unshift($thread, $parent);
            $parentId = (int)$parent->replyToMid;
            if (count($thread) >= 20) break;
        }

        echo '<div class="msg-read">';
        echo '<div class="msg-read-header">';
        echo '<h3>' . htmlspecialchars($message->subject, ENT_QUOTES) . '</h3>';
        echo '<a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'0\',\'inbox\')" class="msg-btn-sm">&#8592; Inbox</a> &nbsp;';
        echo '<a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'' . $id . '\',\'reply\')" class="msg-btn-sm">&#x21A9; Reply</a> &nbsp;';
        echo '<a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'' . $id . '\',\'delete\')" class="msg-btn-sm danger">&#x1F5D1; Delete</a>';
        echo '</div>';

        foreach ($thread as $t) {
            $isMe = ((int)$t->fromUID === $myUID);
            $bubbleClass = $isMe ? 'bubble-out' : 'bubble-in';
            echo '<div class="msg-bubble ' . $bubbleClass . '">';
            echo '<div class="bubble-meta">';
            echo '<strong>' . htmlspecialchars($t->senderName, ENT_QUOTES) . '</strong>';
            echo '<span>' . htmlspecialchars($t->timeSent, ENT_QUOTES) . '</span>';
            echo '</div>';
            echo '<div class="bubble-body">' . nl2br(htmlspecialchars($t->message, ENT_QUOTES)) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
}

echo '</div>';
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());

// ── STYLES ────────────────────────────────────────────────────────────────────
function msgStyles(): string {
    return '
.msg-shell { font-family: inherit; max-width: 860px; }
.msg-tabs  { display:flex; gap:4px; margin-bottom:14px; border-bottom:2px solid #444; padding-bottom:6px; }
.msg-tab   { padding:6px 16px; background:#222; color:#aaa; border-radius:4px 4px 0 0; text-decoration:none; font-size:.9em; }
.msg-tab.active, .msg-tab:hover { background:#2a5; color:#fff; }
.msg-badge { background:#e44; color:#fff; border-radius:10px; padding:1px 6px; font-size:.75em; margin-left:4px; }
.msg-toolbar { margin-bottom:10px; }
.msg-btn-sm { padding:4px 12px; background:#336; color:#adf; border:1px solid #55a; border-radius:3px; text-decoration:none; font-size:.85em; cursor:pointer; }
.msg-btn-sm.danger { background:#422; border-color:#844; color:#f99; }
.msg-btn    { padding:6px 18px; background:#2a5; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:.9em; }
.msg-list   { width:100%; border-collapse:collapse; font-size:.9em; }
.msg-list th { background:#222; padding:6px 8px; text-align:left; color:#888; border-bottom:1px solid #444; }
.msg-list td { padding:6px 8px; border-bottom:1px solid #2a2a2a; vertical-align:middle; }
.msg-list tr.unread td { color:#fff; font-weight:bold; }
.msg-list tr:hover td { background:#1a1a2e; }
.del-link   { color:#f77; text-decoration:none; font-size:1.1em; }
.msg-empty  { color:#666; padding:20px 0; }
.msg-ok     { color:#6f6; }
.msg-err    { color:#f66; }
.msg-compose h3 { margin-top:0; color:#adf; }
.msg-form-tbl { width:100%; border-collapse:collapse; }
.msg-form-tbl td { padding:6px 8px; vertical-align:top; }
.msg-form-tbl td:first-child { width:80px; color:#888; padding-top:10px; }
.msg-form-tbl input[type=text], .msg-form-tbl textarea { width:100%; background:#111; color:#ddd; border:1px solid #444; border-radius:3px; padding:6px; box-sizing:border-box; font-family:inherit; }
.msg-form-tbl textarea { resize:vertical; min-height:160px; }
.msg-read-header { margin-bottom:14px; }
.msg-read-header h3 { display:inline; margin-right:12px; color:#adf; }
.msg-bubble { margin:10px 0; max-width:80%; }
.bubble-in  { margin-right:auto; }
.bubble-out { margin-left:auto; }
.bubble-meta { font-size:.8em; color:#888; display:flex; justify-content:space-between; margin-bottom:4px; }
.bubble-body { background:#1a1a2e; border:1px solid #336; border-radius:4px; padding:10px 14px; line-height:1.5; font-size:.9em; white-space:pre-wrap; word-break:break-word; }
.bubble-out .bubble-body { background:#1a2e1a; border-color:#363; }
';
}
?>
