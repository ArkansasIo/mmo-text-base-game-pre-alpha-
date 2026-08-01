<form method="post" action="/index.php" class="auth-card" id="login-form">
  <h3>Pilot Login</h3>
  <p>Access your command center and continue your campaign.</p>

  <label for="email">Email or Username</label>
  <input type="text" name="user" id="email" required />

  <label for="password">Password</label>
  <input type="password" name="pass" id="password" required />

  <input type="submit" class="auth-submit" name="submit" value="Login" />

  <p class="auth-hint">New pilot? Use Create Account from the top menu.</p>
</form>