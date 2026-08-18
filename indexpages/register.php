<?php
include_once("../config.php");
$s = new Game();

if (!$s->loggedIn)
{
?>
<form method="post" action="/index.php">
<div class="auth-card" id="register-form">
  <h3>Found Your Civilization</h3>
  <p>Establish your civilization and begin expansion across the galaxy.</p>

  <label for="username">Username</label>
  <input type="text" name="user" id="username" required />

  <label for="hpname">Home Planet Name</label>
  <input type="text" name="hpname" id="hpname" required />

  <label for="password">Password</label>
  <input type="password" name="pass" id="password" required />

  <label for="email">E-mail Address</label>
  <input type="email" name="email" id="email" required />

  <label for="race">Race</label>
  <select name="rid" id="race" required>
    <?php
		$list = $s->getRaces();
		for ($x = 0; $x < count($list); $x++)
		{
			echo "<option value='".$list[$x]["id"]."'>".$list[$x]["name"]."</option>\r\n";
		}
	?>
  </select>

  <label for="government_id">Government</label>
  <select name="government_id" id="government_id" required>
    <?php foreach (RaceGovernmentPolicy::GOVERNMENTS as $governmentId => $governmentName): ?>
      <option value="<?= (int)$governmentId ?>"><?= htmlspecialchars($governmentName, ENT_QUOTES, 'UTF-8') ?></option>
    <?php endforeach; ?>
  </select>

  <p class="auth-hint">Type the validation text shown in the image.</p>
  <input name="number" type="text" id="number" required />
  <p style="text-align:center; margin-top:8px;"><img src="image.php?mt=<?= microtime();?>" alt="Validation code"></p>

  <input type="submit" class="auth-submit" name="submit" value="Register" />
</div>
</form>
<?php
}
else
{
	echo "You are already logged in and cannot register another account from this session.";
}
?>