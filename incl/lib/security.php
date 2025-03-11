<?php
class Security {
	public static function GJP2FromPassword($password) {
		return sha1($password."mI29fmAnxgTs");
	}
	
	public static function hashPassword($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	public function loginToAccountWithID($accountID, $key, $type) {
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/enums.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$account = Library::getAccountByID($accountID);
		if(!$account) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => (string)$accountID, "IP" => $IP];
		
		switch($type) {
			case 1:
				if(!password_verify($key, $account["password"])) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => (string)$accountID, "IP" => $IP];
				break;
			case 2:
				if(!password_verify($key, $account["gjp2"])) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => (string)$accountID, "IP" => $IP];
				break;
		}		
		if($account["isActive"] == "0") return ["success" => false, "error" => LoginError::AccountIsNotActivated, "accountID" => (string)$accountID, "IP" => $IP];
		
		$userID = Library::getUserID($accountID);
		
		if(empty($account['salt']) && openssl_random_pseudo_bytes(2) !== false) {
			$salt = Library::randomString(32);
			self::assignSaltToAccount($accountID, $salt);
			if(file_exists(__DIR__.'/../../data/accounts/'.$accountID)) $this->encryptFile(__DIR__.'/../../data/accounts/'.$accountID, $salt);
		}
		
		$userName = $account["userName"];
		
		self::updateLastPlayed($userID);
		
		$udid = isset($_POST['udid']) ? Escape::text($_POST['udid']) : '';
		if($udid) self::assignUDIDToRegisteredAccount($userID, $udid, $userName);
		
		return ["success" => true, "accountID" => (string)$accountID, "userID" => (string)$userID, "userName" => (string)$userName, "IP" => $IP];
	}
	
	public function loginToAccountWithUserName($userName, $key, $type) {
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/enums.php";
		
		$accountID = Library::getAccountIDWithUserName($userName);
		if(!$accountID) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => "0"];
		
		return $this->loginToAccountWithID($accountID, $key, $type);
	}
	
	public static function assignSaltToAccount($accountID, $salt) {
		require __DIR__."/connection.php";
		
		$assignSalt = $db->prepare("UPDATE accounts SET salt = :salt WHERE accountID = :accountID");
		return $assignSalt->execute([':accountID' => $accountID, ':salt' => $salt]);
	}
	
	public static function getMainCipherMethod() {
		$cipherMethods = openssl_get_cipher_methods();
		switch(true) {
			case in_array("chacha20", $cipherMethods):
				return "chacha20";
			case in_array("aes-128-cbc", $cipherMethods):
				return "aes-128-cbc";
			default:
				return $cipherMethods[0];
		}
	}
	
	public function encryptFile($filePath, $salt) {
		$file = file_get_contents($filePath);
		$fileEncrypted = self::encryptData($file, $salt);
		file_put_contents($filePath, $fileEncrypted);
	}
	
	public static function encryptData($data, $salt) {
		$cipherMethod = self::getMainCipherMethod();
		$fileEncrypted = openssl_encrypt($data, $cipherMethod, $salt);
		return $fileEncrypted;
	}
	
	public function decryptFile($filePath, $salt) {
		$cipherMethod = self::getMainCipherMethod();
		$file = file_get_contents($filePath);
		$fileDecrypted = openssl_decrypt($file, $cipherMethod, $salt);
		return $fileDecrypted;
	}
	
	public static function getLoginType() {
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/XOR.php";
		
		switch(true) {
			case isset($_POST['gjp2']):
				$key = $_POST['gjp2'];
				$type = 2;
				break;
			case isset($_POST['password']):
			case isset($_POST['gjp']):
				$key = !isset($_POST['gjp']) ? $_POST['password'] : XORCipher::cipher(Escape::url_base64_decode($_POST['gjp']), 37526);
				$type = 1;
				break;
			default:
				return false;
		}
		return ["key" => $key, "type" => $type];
	}
	
	public function loginPlayer() {
		require __DIR__."/../../config/security.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/enums.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		if(self::isTooManyAttempts()) {
			$logPerson = [
				'accountID' => (string)Escape::number($_POST['accountID']),
				'userID' => (string)Library::getUserID(Escape::number($_POST['accountID'])),
				'userName' => '',
				'IP' => $IP
			];
			
			Library::logAction($logPerson, Action::FailedLogin);
			return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => (string)Escape::number($_POST['accountID']), "IP" => $IP];
		}
		
		switch(true) {
			case !empty($_POST['uuid']) && (!empty($_POST['password']) || !empty($_POST['gjp']) || !empty($_POST['gjp2']) || !empty($_POST['auth'])):
				$userID = Escape::number($_POST['uuid']);
				$accountID = Library::getAccountID($userID);
				break;
			case empty($_POST['password']) && empty($_POST['gjp']) && empty($_POST['gjp2']) && empty($_POST['auth']) && $unregisteredSubmissions:
				$udid = isset($_POST['udid']) ? Escape::text($_POST['udid']) : '';
				$userName = isset($_POST['userName']) ? Escape::latin($_POST['userName']) : "Undefined";
				$accountID = isset($_POST['accountID']) ? Escape::number($_POST['accountID']) : 0;
				
				if(empty($_POST['uuid']) && !empty($accountID)) $userID = Library::getUserID($accountID);
				else $userID = Escape::number($_POST['uuid']) ?: 0;
				
				$verifyUDID = self::verifyUDID($userID, $udid, $userName);
				if(!$verifyUDID) {
					$logPerson = [
						'accountID' => 0,
						'userID' => $userID,
						'userName' => $userName,
						'IP' => $IP
					];
					
					if(!empty($udid)) Library::logAction($logPerson, Action::FailedLogin);
					return ["success" => true, "accountID" => "0", "userID" => "0", "userName" => "Undefined", "IP" => $IP];
				}
				
				return ["success" => true, "accountID" => (!$accountID ? (string)$verifyUDID['unregisteredID'] : $accountID), "userID" => (string)$verifyUDID['userID'], "userName" => (string)$verifyUDID["userName"], "IP" => $IP];
				break;
			case !empty($_POST['userName']):
				$userName = Escape::latin($_POST['userName']);
				$accountID = Library::getAccountIDWithUserName($userName);
				break;
			default:
				$accountID = Escape::number($_POST['accountID']);
				break;
		}
		
		$loginType = self::getLoginType();
		if(!$loginType) return ["success" => false, "error" => LoginError::GenericError, "accountID" => $accountID];

		$loginToAccount = $this->loginToAccountWithID($accountID, $loginType["key"], $loginType["type"]);
		if(!$loginToAccount['success']) {
			$logPerson = [
				'accountID' => $accountID,
				'userID' => $userID ?: Library::getUserID($accountID),
				'userName' => '',
				'IP' => $IP
			];
			
			Library::logAction($logPerson, Action::FailedLogin);
			return ["success" => false, "error" => $loginToAccount['error'], "accountID" => $accountID];
		}
		return ["success" => true, "accountID" => $loginToAccount['accountID'], "userID" => $loginToAccount['userID'], "userName" => $loginToAccount["userName"], "IP" => $loginToAccount['IP']];
	}
	
	public static function updateLastPlayed($userID) {
		require __DIR__."/connection.php";

		$updateLastPlayed = $db->prepare("UPDATE users SET lastPlayed = :lastPlayed WHERE userID = :userID");
		return $updateLastPlayed->execute([':lastPlayed' => time(), ':userID' => $userID]);
	}
	
	public static function generateLevelsHash($levelsStatsArray) {
		$hash = "";
		foreach($levelsStatsArray as $level) {
			$id = strval($level['levelID']);
			$hash = $hash.$id[0].$id[strlen($id)-1].$level["stars"].$level["coins"];
		}
		
		return sha1($hash."xI25fpAapCQg");
	}
	
	public static function generateFirstHash($levelString) {
		$len = strlen($levelString);
		if($len < 41) return sha1($levelString."xI25fpAapCQg");
		
		$hash = '????????????????????????????????????????xI25fpAapCQg';
		$m = intdiv($len, 40);
		$i = 40;
		
		while($i) $hash[--$i] = $levelString[$i * $m];
		
		return sha1($hash);
	}
	
	public static function generateSecondHash($levelString) {
		return sha1($levelString."xI25fpAapCQg");
	}
	
	public static function generateThirdHash($levelString) {
		return sha1($levelString."oC36fpYaPtdg");
	}
	
	public static function generateFourthHash($levelString) {
		return sha1($levelString."pC26fpYaQCtg");
	}
	
	public static function verifyUDID($userID, $udid, $userName) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		if(!$udid) return false;
		
		$hashedUDID = sha1($udid."PUH7d3v6hDjAa2bfuM9r");
		
		$unregistered = $db->prepare("SELECT * FROM udids WHERE userID = :userID OR udids REGEXP :udid");
		$unregistered->execute([':userID' => $userID, ':udid' => $hashedUDID]);
		$unregistered = $unregistered->fetch();
		
		if(!$unregistered) {
			$unregistered = self::hashUDID($userID, $udid, $userName);
			
			return ['unregisteredID' => "u".$unregistered['unregisteredID'], 'userID' => $unregistered['userID'], 'userName' => $userName];
		} else {
			$udidVerified = false;
			$udidsArray = explode(",", $unregistered['udids']);
			
			foreach($udidsArray AS &$udid) {
				if($udid == $hashedUDID) {
					$udidVerified = true;
					break;
				}
			}
			
			if(!$udidVerified) return false;
			
			return ['unregisteredID' => "u".$unregistered['ID'], 'userID' => $unregistered['userID'], 'userName' => $userName];
		}
	}
	
	public static function hashUDID($userID, $udid, $userName = "Undefined") {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		$hashedUDID = sha1($udid."PUH7d3v6hDjAa2bfuM9r");
		
		$registerUDID = $db->prepare("INSERT INTO udids (userID, udids)
			VALUES (:userID, :udid)");
		$registerUDID->execute([':userID' => $userID, ':udid' => $hashedUDID]);
		$unregisteredID = $db->lastInsertId();
		
		if($userID == 0) {
			$userID = Library::createUser($userName, "u".$unregisteredID, $IP);
		
			$registerUDID = $db->prepare("UPDATE udids SET userID = :userID WHERE ID = :unregisteredID");
			$registerUDID->execute([':userID' => $userID, ':unregisteredID' => $unregisteredID]);
		} else {
			$updateUser = $db->prepare("UPDATE users SET extID = :unregisteredID WHERE userID = :userID AND isRegistered = 0");
			$updateUser->execute([':userID' => $userID, ':unregisteredID' => "u".$unregisteredID]);
		}
		
		return ['unregisteredID' => $unregisteredID, 'userID' => $userID];
	}
	
	public static function assignUDIDToRegisteredAccount($userID, $udid, $userName) {
		require __DIR__."/connection.php";
		
		$unregistered = $db->prepare("SELECT * FROM udids WHERE userID = :userID");
		$unregistered->execute([':userID' => $userID]);
		$unregistered = $unregistered->fetch();
		if(!$unregistered) return self::hashUDID($userID, $udid, $userName);
		
		$hashedUDID = sha1($udid."PUH7d3v6hDjAa2bfuM9r");
		
		$udidsArray = explode(",", $unregistered['udids']);
		
		foreach($udidsArray AS &$assignedUDID) if($assignedUDID == $hashedUDID) return true;
		
		$udidsArray[] = $hashedUDID;
		
		$udids = implode(",", $udidsArray);
		
		$updateUnregistered = $db->prepare("UPDATE udids SET udids = :udids WHERE userID = :userID");
		$updateUnregistered->execute([':udids' => $udids, ':userID' => $userID]);
		
		return true;
	}
	
	public static function isTooManyAttempts() {
		require __DIR__."/../../config/security.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/ip.php";
		require_once __DIR__."/enums.php";
		
		$IP = IP::getIP();
		
		$searchPerson = [
			'accountID' => 0,
			'IP' => $IP
		];
		
		$filters = ['type = '.Action::FailedLogin, 'timestamp >= '.time() - 3600];
		
		$failedLogins = Library::getPersonActions($searchPerson, $filters);
		
		return count($failedLogins) > $maxLoginTries;
	}
}
?>