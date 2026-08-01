<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);

$db = $s->db_link;
$myUID = (int)$_SESSION['userid'];

// Ensure market_listings table exists
$db->query("CREATE TABLE IF NOT EXISTS `market_listings` (
    `lid` int(11) NOT NULL AUTO_INCREMENT,
    `uid` int(11) NOT NULL,
    `resource` varchar(32) NOT NULL,
    `amount` int(11) NOT NULL DEFAULT 0,
    `price_per` float NOT NULL DEFAULT 0,
    `created` int(11) NOT NULL DEFAULT 0,
    `active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`lid`),
    KEY `idx_active` (`active`,`resource`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

$msg = '';
$atype = $_GET['atype'] ?? $_POST['atype'] ?? '';

// Handle POST: post a new listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $atype === 'post') {
    $resource = $_POST['resource'] ?? '';
    $amount   = (int)($_POST['amount'] ?? 0);
    $price    = (float)($_POST['price'] ?? 0);
    $allowed  = ['naquadah', 'metal', 'crystal', 'deuterium'];

    if (!in_array($resource, $allowed)) {
        $msg = '<p class="err">Invalid resource type.</p>';
    } elseif ($amount <= 0 || $price <= 0) {
        $msg = '<p class="err">Amount and price must be greater than zero.</p>';
    } else {
        // Check seller has enough of the resource
        $hasEnough = false;
        if ($resource === 'naquadah') {
            $stmt = $db->prepare("SELECT onHand FROM bank WHERE uid=? LIMIT 1");
            $stmt->bind_param("i", $myUID);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_object();
            if ($row && $row->onHand >= $amount) { $hasEnough = true; }
        } else {
            $stmt = $db->prepare("SELECT $resource FROM player_resources WHERE uid=? LIMIT 1");
            $stmt->bind_param("i", $myUID);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_object();
            if ($row && $row->$resource >= $amount) { $hasEnough = true; }
        }

        if (!$hasEnough) {
            $msg = '<p class="err">You do not have enough ' . htmlspecialchars($resource) . ' to list that amount.</p>';
        } else {
            // Reserve the resource from seller
            $db->begin_transaction();
            try {
                if ($resource === 'naquadah') {
                    $stmt = $db->prepare("UPDATE bank SET onHand=onHand-? WHERE uid=? LIMIT 1");
                } else {
                    $stmt = $db->prepare("UPDATE player_resources SET `$resource`=`$resource`-? WHERE uid=? LIMIT 1");
                }
                $stmt->bind_param("ii", $amount, $myUID);
                $stmt->execute();

                $stmt = $db->prepare("INSERT INTO market_listings (uid,resource,amount,price_per,created,active) VALUES (?,?,?,?,?,1)");
                $now = time();
                $stmt->bind_param("issii", $myUID, $resource, $amount, $price, $now);
                $stmt->execute();
                $db->commit();
                $msg = '<p class="ok">Listing posted! ' . number_format($amount) . ' ' . htmlspecialchars($resource) . ' @ ' . number_format($price, 2) . ' Naq each.</p>';
            } catch (Throwable $e) {
                $db->rollback();
                $msg = '<p class="err">Failed to post listing. Please try again.</p>';
            }
        }
    }
}

// Handle GET: buy a listing
if ($atype === 'buy') {
    $lid = (int)($_GET['id'] ?? 0);
    if ($lid > 0) {
        $stmt = $db->prepare("SELECT * FROM market_listings WHERE lid=? AND active=1 LIMIT 1");
        $stmt->bind_param("i", $lid);
        $stmt->execute();
        $listing = $stmt->get_result()->fetch_object();

        if (!$listing) {
            $msg = '<p class="err">Listing not found or already sold.</p>';
        } elseif ($listing->uid === $myUID) {
            $msg = '<p class="err">You cannot buy your own listing.</p>';
        } else {
            $totalCost = (int)ceil($listing->amount * $listing->price_per);
            $stmt = $db->prepare("SELECT onHand FROM bank WHERE uid=? LIMIT 1");
            $stmt->bind_param("i", $myUID);
            $stmt->execute();
            $buyerBank = $stmt->get_result()->fetch_object();

            if (!$buyerBank || $buyerBank->onHand < $totalCost) {
                $msg = '<p class="err">Not enough Naquadah. Need ' . number_format($totalCost) . '.</p>';
            } else {
                $db->begin_transaction();
                try {
                    // Mark listing sold
                    $stmt = $db->prepare("UPDATE market_listings SET active=0 WHERE lid=? LIMIT 1");
                    $stmt->bind_param("i", $lid);
                    $stmt->execute();

                    // Deduct Naq from buyer
                    $stmt = $db->prepare("UPDATE bank SET onHand=onHand-? WHERE uid=? LIMIT 1");
                    $stmt->bind_param("ii", $totalCost, $myUID);
                    $stmt->execute();

                    // Pay seller (5% market fee)
                    $sellerGets = (int)floor($totalCost * 0.95);
                    $stmt = $db->prepare("UPDATE bank SET onHand=onHand+? WHERE uid=? LIMIT 1");
                    $stmt->bind_param("ii", $sellerGets, $listing->uid);
                    $stmt->execute();

                    // Give resource to buyer
                    $resource = $listing->resource;
                    if ($resource === 'naquadah') {
                        $stmt = $db->prepare("UPDATE bank SET onHand=onHand+? WHERE uid=? LIMIT 1");
                        $stmt->bind_param("ii", $listing->amount, $myUID);
                        $stmt->execute();
                    } else {
                        $stmt = $db->prepare("INSERT INTO player_resources (uid,`$resource`) VALUES (?,?) ON DUPLICATE KEY UPDATE `$resource`=`$resource`+?");
                        $stmt->bind_param("iii", $myUID, $listing->amount, $listing->amount);
                        $stmt->execute();
                    }

                    $db->commit();
                    $msg = '<p class="ok">Purchase complete! You bought ' . number_format($listing->amount) . ' ' . htmlspecialchars($resource) . ' for ' . number_format($totalCost) . ' Naq.</p>';
                } catch (Throwable $e) {
                    $db->rollback();
                    $msg = '<p class="err">Transaction failed. Please try again.</p>';
                }
            }
        }
    }
}

// Handle GET: cancel own listing
if ($atype === 'cancel') {
    $lid = (int)($_GET['id'] ?? 0);
    if ($lid > 0) {
        $stmt = $db->prepare("SELECT * FROM market_listings WHERE lid=? AND uid=? AND active=1 LIMIT 1");
        $stmt->bind_param("ii", $lid, $myUID);
        $stmt->execute();
        $listing = $stmt->get_result()->fetch_object();
        if ($listing) {
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("UPDATE market_listings SET active=0 WHERE lid=? LIMIT 1");
                $stmt->bind_param("i", $lid);
                $stmt->execute();
                // Refund reserved resource
                $resource = $listing->resource;
                if ($resource === 'naquadah') {
                    $stmt = $db->prepare("UPDATE bank SET onHand=onHand+? WHERE uid=? LIMIT 1");
                    $stmt->bind_param("ii", $listing->amount, $myUID);
                    $stmt->execute();
                } else {
                    $stmt = $db->prepare("INSERT INTO player_resources (uid,`$resource`) VALUES (?,?) ON DUPLICATE KEY UPDATE `$resource`=`$resource`+?");
                    $stmt->bind_param("iii", $myUID, $listing->amount, $listing->amount);
                    $stmt->execute();
                }
                $db->commit();
                $msg = '<p class="ok">Listing cancelled and resources returned.</p>';
            } catch (Throwable $e) {
                $db->rollback();
                $msg = '<p class="err">Could not cancel listing.</p>';
            }
        } else {
            $msg = '<p class="err">Listing not found or already closed.</p>';
        }
    }
}

// Fetch my bank balance
$stmt = $db->prepare("SELECT onHand FROM bank WHERE uid=? LIMIT 1");
$stmt->bind_param("i", $myUID);
$stmt->execute();
$myBank = $stmt->get_result()->fetch_object();
$myNaq  = $myBank ? (int)$myBank->onHand : 0;

// Fetch active listings (exclude own for buy list)
$stmt = $db->prepare(
    "SELECT ml.lid, ml.uid, u.uname, ml.resource, ml.amount, ml.price_per,
            (ml.amount * ml.price_per) AS total_cost
     FROM market_listings ml
     INNER JOIN users u ON u.uid = ml.uid
     WHERE ml.active=1
     ORDER BY ml.resource ASC, ml.price_per ASC
     LIMIT 200");
$stmt->execute();
$listings = $stmt->get_result();

// Fetch my own active listings
$stmt = $db->prepare(
    "SELECT lid, resource, amount, price_per, created
     FROM market_listings WHERE uid=? AND active=1 ORDER BY created DESC");
$stmt->bind_param("i", $myUID);
$stmt->execute();
$myListings = $stmt->get_result();

?>
<style>
.market-section { margin-bottom: 20px; }
.market-section h3 { border-bottom: 1px solid #555; padding-bottom: 4px; margin-bottom: 8px; }
table.market-tbl { width: 100%; border-collapse: collapse; }
table.market-tbl th, table.market-tbl td { padding: 4px 8px; text-align: left; border: 1px solid #444; font-size: 0.9em; }
table.market-tbl th { background: #222; }
.ok  { color: #6f6; }
.err { color: #f66; }
.mkt-form label { display: inline-block; width: 110px; }
.mkt-form select, .mkt-form input[type=number] { width: 140px; }
</style>

<div class="market-section">
  <h3>&#9733; Black Market Exchange</h3>
  <p>Your Naquadah on hand: <strong><?= number_format($myNaq) ?></strong> &nbsp; (5% market fee applies to all sales)</p>
  <?= $msg ?>
</div>

<div class="market-section">
  <h3>Post a Listing</h3>
  <form method="post" action="market.php?time=<?= time() ?>" class="mkt-form">
    <input type="hidden" name="atype" value="post">
    <label>Resource:</label>
    <select name="resource">
      <option value="naquadah">Naquadah</option>
      <option value="metal">Metal</option>
      <option value="crystal">Crystal</option>
      <option value="deuterium">Deuterium</option>
    </select><br><br>
    <label>Amount:</label>
    <input type="number" name="amount" min="1" value="1000" required><br><br>
    <label>Price / unit (Naq):</label>
    <input type="number" name="price" min="1" step="0.01" value="1" required><br><br>
    <button type="submit">Post Listing</button>
  </form>
</div>

<div class="market-section">
  <h3>Your Active Listings</h3>
  <?php if ($myListings->num_rows === 0): ?>
    <p>You have no active listings.</p>
  <?php else: ?>
  <table class="market-tbl">
    <tr><th>ID</th><th>Resource</th><th>Amount</th><th>Price/Unit</th><th>Total</th><th>Action</th></tr>
    <?php while ($l = $myListings->fetch_object()): ?>
    <tr>
      <td><?= $l->lid ?></td>
      <td><?= htmlspecialchars($l->resource) ?></td>
      <td><?= number_format($l->amount) ?></td>
      <td><?= number_format($l->price_per, 2) ?> Naq</td>
      <td><?= number_format($l->amount * $l->price_per, 0) ?> Naq</td>
      <td><a href="javascript:void(0)" onclick="sendData('market','get','<?= $l->lid ?>','cancel')">Cancel</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
  <?php endif; ?>
</div>

<div class="market-section">
  <h3>Available Listings</h3>
  <?php if ($listings->num_rows === 0): ?>
    <p>No listings available. Be the first to post one!</p>
  <?php else: ?>
  <table class="market-tbl">
    <tr><th>Resource</th><th>Seller</th><th>Amount</th><th>Price/Unit</th><th>Total Cost</th><th>Action</th></tr>
    <?php while ($l = $listings->fetch_object()): ?>
    <tr>
      <td><?= htmlspecialchars($l->resource) ?></td>
      <td><a href="javascript:void(0)" onclick="sendData('user','get','<?= $l->uid ?>')">  <?= htmlspecialchars($l->uname) ?></a></td>
      <td><?= number_format($l->amount) ?></td>
      <td><?= number_format($l->price_per, 2) ?> Naq</td>
      <td><?= number_format($l->total_cost, 0) ?> Naq</td>
      <td>
        <?php if ($l->uid !== $myUID): ?>
          <a href="javascript:void(0)" onclick="sendData('market','get','<?= $l->lid ?>','buy')">Buy</a>
        <?php else: ?>
          <em>Your listing</em>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
  <?php endif; ?>
</div>

<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
