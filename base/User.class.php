<?php
// Base::User.class.php
require_once __DIR__ . '/PopulationModel.class.php';
require_once __DIR__ . '/RaceGovernmentPolicy.class.php';

class User extends Chive
{
	public ?string $userName = null;
	public ?string $password = null;
	public int $access = 0;
	public bool $loggedIn = false;
	public ?int $userid = null;
	public ?int $raceID = null;
	public ?int $progress = null;

	/**
	 * Constructor for User
	 * @param string $userName Name of user
	 * @param string $password Password of user
	 *
	 */
	public function __construct(string $userName = "", string $password = "")
	{
		parent::__construct();
		$this->connectToDB(); // Ensure the database connection is established
		if(isset($userName) && !empty($userName) || isset($_SESSION['username']))
		{
			if(isset($_SESSION['username']) && isset($_SESSION['password']))
			{
				$this->userName	= $_SESSION['username'];
				$this->password	= $_SESSION['password'];
				$this->access	= $_SESSION['access'];
				$this->userid	= $_SESSION['userid'];
				$this->raceID	= $_SESSION['raceID'];
				$this->progress = $_SESSION['progress'];
			} else {
				$this->userName = $this->clean_sql($userName, 0);
				$this->password = $this->clean_sql($this->salt($password), 0);
			}
						
			if($this->isRealUser())
			{
				$this->loggedIn = true;
				$_SESSION['username']	= $this->userName;
				$_SESSION['password']	= $this->password;
				$_SESSION['access']		= $this->access;
				$_SESSION['userid']		= $this->userid;
				$_SESSION['raceID']		= $this->raceID;
				$_SESSION['progress']   = $this->progress;
				$time = time();
				$query = "UPDATE users SET lastLogin=? WHERE uid=? LIMIT 1";
				$stmt = $this->db_link->prepare($query);
				$stmt->bind_param("ii", $time, $this->userid);
				if ($stmt->execute()){
					Debug::printMsg(__CLASS__, __FUNCTION__, "UserID is:".$this->userid." lastLogin Updated");
				}else{
					Debug::printMsg(__CLASS__, __FUNCTION__, "UserID is:".$this->userid." lastLogin Not Updated");
				}
				
				Debug::printMsg(__CLASS__, __FUNCTION__, "UserID is:".$this->userid);
				Debug::printMsg(__CLASS__, __FUNCTION__, "Logged In");
			} else {
				$this->loggedIn = false;
				$this->access = 0;
			}
		} else {
			$this->loggedIn = false;
			$this->access = 0;
		}
		Debug::printMsg(__CLASS__, __FUNCTION__, "Class created with <b>\$userName</b> ".$this->userName);
	}
	
	/**
	 * Checks if the user is authentic
	 *
	 * @return bool
	 */
	public function isRealUser(): bool
	{
		if(!$this->connected()) {
			return false;
		}

		$query = "
			SELECT users.uid, users.alevel, userdata.rid, userdata.progress
			FROM ".$this->db_prefix."users AS users
			LEFT JOIN ".$this->db_prefix."userdata AS userdata ON userdata.uid = users.uid
			WHERE (users.email=? OR users.uname=?)
			AND users.password=?
			LIMIT 1
			";
		$stmt = $this->db_link->prepare($query);
		if(!$stmt) {
			Debug::printMsg(__CLASS__, __FUNCTION__, "Could not prepare user validation query");
			return false;
		}
		$stmt->bind_param("sss", $this->userName, $this->userName, $this->password);
		$stmt->execute();
		$q = $stmt->get_result();
		if($q->num_rows)
		{
			$row = $q->fetch_object();
			$this->access = (int)$row->alevel;
			$this->userid = (int)$row->uid;
			$this->raceID = isset($row->rid) ? (int)$row->rid : 1;
			$this->progress = isset($row->progress) ? (int)$row->progress : 0;
			Debug::printMsg(__CLASS__, __FUNCTION__, "Validated '$this->userName'");
			return true;
		}
		Debug::printMsg(__CLASS__, __FUNCTION__, "Could not validate user '$this->userName'");
		return false;
	}
	
	public function isAllowed(int $reqAcc): bool
	{
		return (bool)((int)$reqAcc & $this->access);
	}
	
	/**
	 * Logs out user
	 *
	 */
	public static function logOut(): void
	{
		$_SESSION['username'] = null;
		$_SESSION['password'] = null;
		$_SESSION['access'] = null;
		$_SESSION['userid'] = null;
		session_unset();
		session_destroy();
	}
	
	/**
	 * Puts a salt on the encryption method
	 *
	 * @param string $value
	 * @return string
	 */
	public function salt(string $value): string
	{
		return md5(crypt($value, '.u55ybcbC,ufzQu2'));
	}
	
	/**
	 * Adds user to the database
	 *
	 * @param string $userName
	 * @param string $password
	 * @param int $access
	 * @param string $email
	 * @param int $rid
	 * @param string $hpname
	 * @param string $ip
	 * @return bool
	 */
	public function addUser(string $userName, string $password, int $access = 1, string $email, int $rid, string $hpname, string $ip, int $governmentId = 1): bool
	{
		if(!$this->connected()) {
			$this->connectToDB();
		}
		if(!$this->connected()) {
			echo "Registration failed: could not connect to database.";
			return false;
		}

		$userName = trim($userName);
		$email = trim($email);
		$hpname = trim($hpname);
		$passwordHash = $this->salt($password);
			$rid = (int)$rid;
			$access = (int)$access;
			$governmentId = RaceGovernmentPolicy::validGovernment((int)$governmentId) ? (int)$governmentId : 1;
		if($rid <= 0) {
			$rid = 1;
		}
		
		// Ensure account creation uses a player-selectable race only.
		$raceGroupCheck = $this->db_link->query("SHOW COLUMNS FROM race LIKE 'r_group'");
		if ($raceGroupCheck && $raceGroupCheck->num_rows > 0) {
			$raceStmt = $this->db_link->prepare("SELECT rid FROM race WHERE rid=? AND r_group='player' LIMIT 1");
			$raceStmt->bind_param("i", $rid);
			$raceStmt->execute();
			$raceQ = $raceStmt->get_result();
			if (!$raceQ || !$raceQ->num_rows) {
				$fallbackQ = $this->db_link->query("SELECT rid FROM race WHERE r_group='player' ORDER BY rid ASC LIMIT 1");
				if ($fallbackQ && $fallbackQ->num_rows) {
					$rid = (int)($fallbackQ->fetch_object()->rid ?? 1);
				} else {
					$rid = 1;
				}
			}
		}

		if($userName === '' || $email === '' || $hpname === '' || $password === '') {
			echo "Registration failed: missing required fields.";
			return false;
		}
		if(strlen($password) < 8) {
				echo "Registration failed: password must contain at least 8 characters.";
				return false;
			}
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			echo "Registration failed: invalid email address.";
			return false;
		}

		$ipNumber = is_numeric($ip) ? (int)$ip : (int)sprintf('%u', ip2long($ip));
		if($ipNumber < 0) {
			$ipNumber = 0;
		}

		// Keep legacy one-account-per-IP rule, but do not block on placeholder IP 0.
		if($ipNumber > 0) {
			$query = "SELECT uid FROM ".$this->db_prefix."users WHERE ip=? LIMIT 1";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $ipNumber);
			$stmt->execute();
			$q = $stmt->get_result();
			if($q->num_rows) {
				echo "Your IP is used by another account only 1 account per IP";
				return false;
			}
		}

		$query = "SELECT uid FROM ".$this->db_prefix."users WHERE uname=? OR email=? LIMIT 1";
		$stmt = $this->db_link->prepare($query);
		$stmt->bind_param("ss", $userName, $email);
		$stmt->execute();
		$q = $stmt->get_result();
		if($q->num_rows) {
			echo "Registration failed: username or email already in use.";
			return false;
		}

		$link = $this->genUniqueLink();
		$lastLogin = time();

		$this->db_link->begin_transaction();
		try {
			$query = "
				INSERT INTO ".$this->db_prefix."users
				(uname, email, allyid, lastLogin, arank, ip, password, alevel)
				VALUES (?, ?, 0, ?, 0, ?, ?, ?)
			";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("ssiisi", $userName, $email, $lastLogin, $ipNumber, $passwordHash, $access);
			$stmt->execute();

			$uid = (int)$this->db_link->insert_id;

			$query = "INSERT INTO ".$this->db_prefix."bank (uid, inbank, onHand) VALUES (?, 0, 250000)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$startingUntrained = PopulationModel::STARTING_UNTRAINED_UNITS;
			$query = "
				INSERT INTO ".$this->db_prefix."units
				(uid, attack, superAttack, attackMercs, defense, superDefense, defenseMercs, untrained, miners, lifers, covert, superCovert, anticovert, superAnticovert)
				VALUES (?, 0, 0, 0, 0, 0, 0, ".$startingUntrained.", 0, 0, 0, 0, 0, 0)
			";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$query = "
				INSERT INTO ".$this->db_prefix."technology
				(uid, income, unitProd, uppl, cov_lvl, anti_lvl, covert, anticovert, attack, defense, auEffect, auRes, auSteal, acuEffect, acuRes, duSteal, cuEffect, cuRes, duEffect, duRes, ascend, galaxy, pDef, puCap, pmCap)
				VALUES (?, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
			";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$query = "INSERT INTO ".$this->db_prefix."power (uid, overall, mil_atk, mil_def, mil_cov, mil_anti, mil_total) VALUES (?, 0, 0, 0, 0, 0, 0)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$query = "INSERT INTO ".$this->db_prefix."rank (uid, overall, mil_atk, mil_def, mil_cov, mil_anti, mil_total) VALUES (?, 0, 0, 0, 0, 0, 0)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$homePopulation = PopulationModel::randomPlanet(5, 70);
			$query = "INSERT INTO ".$this->db_prefix."planets (uid, text, plnt_name, income_bonus, up_bonus, isHome, pid, plnt_size, population, pop_cap) VALUES (?, '', ?, 0, 0, 1, 0, 0, ".$homePopulation.", 5000000)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("is", $uid, $hpname);
			$stmt->execute();

			$query = "INSERT INTO ".$this->db_prefix."userdata (uid, link, actionTurns, rid, uname, cid, progress, government_id) VALUES (?, ?, 250, ?, ?, 0, 0, ?)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("isisi", $uid, $link, $rid, $userName, $governmentId);
			$stmt->execute();

			$this->db_link->commit();
			echo "Registration Complete";
			return true;
		} catch (Throwable $e) {
			$this->db_link->rollback();
			Debug::printMsg(__CLASS__, __FUNCTION__, "Registration failed: " . $e->getMessage());
			echo "Registration failed. Please try again.";
			return false;
		}
	}
	
	public function genUniqueLink(): string
	{
		$time = time();
		$uniqID = "";
		for ($i = 0; $i < strlen((string)$time) / 2; $i++){
			$uniqID .= chr(rand(ord('a'), ord('z')));
		}
		$uniqID .= $time;
		return $uniqID;	
	}
}
?>