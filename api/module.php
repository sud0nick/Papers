<?php

namespace pineapple;
define('__INCLUDES__', "/pineapple/modules/Papers/includes/");
define('__SCRIPTS__', __INCLUDES__ . "scripts/");
define('__SSLSTORE__', __INCLUDES__ . "ssl/");
define('__SSHSTORE__', __INCLUDES__ . "ssh/");
define('__LOGS__', __INCLUDES__ . "logs/");
define('__CHANGELOGS__', __INCLUDES__ . "changelog/");
define('__HELPFILES__', __INCLUDES__ . "help/");
define('__DOWNLOAD__', __INCLUDES__ . "download/");

class Papers extends Module
{
	public function route() {
		switch ($this->request->action) {
			case 'checkDepends':
				$this->checkDepends();
				break;
			case 'installDepends':
				$this->installDepends();
				break;
			case 'removeDepends':
				$this->removeDepends();
				break;
			case 'buildCert':
				$this->buildCert($this->request->parameters);
				break;
			case 'genSSHKeys':
				$this->genSSHKeys($this->request->parameters);
				break;
			case 'loadCertificates':
				$this->loadCertificates();
				break;
			case 'downloadKeys':
				$this->downloadKeys($this->request->parameters->name, $this->request->parameters->type);
				break;
			case 'clearDownloadArchive':
				$this->clearDownloadArchive();
				break;
			case 'removeCertificate':
				$this->removeCertificate($this->request->params->cert, $this->request->params->type);
				break;
			case 'securePineapple':
				$this->securePineapple($this->request->params->cert, $this->request->params->type);
				break;
			case 'getNginxSSLCerts':
				$this->getNginxSSLCerts();
				break;
			case 'unSSLPineapple':
				$this->unSSLPineapple();
				break;
			case 'revokeSSHKey':
				$this->revokeSSHKey($this->request->key);
				break;
			case 'getLogs':
				$this->getLogs($this->request->type);
				break;
			case 'readLog':
				$this->retrieveLog($this->request->parameters, $this->request->type);
				break;
			case 'deleteLog':
				$this->deleteLog($this->request->parameters);
				break;
		}
	}
	private function checkDepends() {
		$retData = array();
		exec(__SCRIPTS__ . "checkDepends.sh", $retData);
		if (implode(" ", $retData) == "Installed") {
			$this->respond(true);
		} else {
			$this->respond(false);
		}
	}
	private function installDepends() {
		$retData = array();
		exec(__SCRIPTS__ . "installDepends.sh", $retData);
		if (implode(" ", $retData) == "Complete") {
			$this->respond(true);
		} else {
			$this->respond(false);
		}
	}
	private function removeDepends() {
		// removeDepends.sh doesn't return anything whether successful or not
		exec(__SCRIPTS__ . "removeDepends.sh");
		$this->respond(true);
	}
	private function genSSHKeys($paramsObj) {
		$keyInfo = array();
		$params = (array)$paramsObj;
		
		$keyInfo['-k'] = $params['keyName'];
		$keyInfo['-b'] = $params['bitSize'];
		if (array_key_exists('pass', $params)) {
			$keyInfo['-p'] = $params['pass'];
		}
		
		// Build the argument string to pass to buildCert.sh
		foreach ($keyInfo as $k => $v) {
			$argString .= $k . " \"" . $v . "\" ";
		}
		$argString = rtrim($argString);
		
		$resData = array();
		exec(__SCRIPTS__ . "genSSHKeys.sh " . $argString, $resData);
		$res = implode("\n", $resData);
		if ($res != "") {
			$this->logError("Build SSH Key Error", "Failed to build SSH keys.  The following data was returned:\n" . $res);
			$this->respond(false);
			return;
		}
		$this->respond(true);
	}
	private function buildCert($paramsObj) {
		$certInfo = array();
		$params = (array)$paramsObj;

		$keyName = (array_key_exists('keyName', $params)) ? $params['keyName'] : "newCert";
		$certInfo['-k'] = $keyName;

		if (array_key_exists('days', $params)) {
			$numberofdays = intval($params['days']);
			$certInfo['-d'] = $numberofdays;
		}
		if (array_key_exists('sigalgo', $params)) {
			$certInfo['-sa'] = $params['sigalgo'];
		}
		if (array_key_exists('bitSize', $params)) {
			$certInfo['-b'] = $params['bitSize'];
		}
		if (array_key_exists('country', $params)) {
			$certInfo['-c'] = $params['country'];
		}
		if (array_key_exists('state', $params)) {
			$certInfo['-st'] = $params['state'];
		}
		if (array_key_exists('city', $params)) {
			$certInfo['-l'] = $params['city'];
		}
		if (array_key_exists('organization', $params)) {
			$certInfo['-o'] = $params['organization'];
		}
		if (array_key_exists('section', $params)) {
			$certInfo['-ou'] = $params['section'];
		}
		if (array_key_exists('commonName', $params)) {
			$certInfo['-cn'] = $params['commonName'];
		}
		if (array_key_exists('email', $params)) {
			$certInfo['-email'] = $params['email'];
		}
		
		// Build the argument string to pass to buildCert.sh
		foreach ($certInfo as $k => $v) {
			$argString .= $k . " \"" . $v . "\" ";
		}
		$argString = rtrim($argString);
		
		$resData = array();
		exec(__SCRIPTS__ . "buildCert.sh " . $argString, $resData);
		$res = implode("\n", $resData);
		if ($res != "Complete") {
			$this->logError("Build Certificate Error", "The key pair failed with the following error from the console:\n\n" . $res);
			$this->respond(false, "Failed to build key pair.  Check the logs for details.");
			return;
		}

		if (array_key_exists('container', $params) || array_key_exists('encrypt', $params)) {
			$cryptInfo = array();
			$argString = "";

			$cryptInfo['-k'] = $keyName;			

			// Check if the certificate should be encrypted
			if (array_key_exists('encrypt', $params)) {
				$argString = "--encrypt ";
	
				$cryptInfo['-a'] = (array_key_exists('algo', $params)) ? $params['algo'] : False;
				$cryptInfo['-p'] = (array_key_exists('pkey_pass', $params)) ? $params['pkey_pass'] : False;
	
				if (!$cryptInfo['-a'] || !$cryptInfo['-p']) {
					$this->logError("Build Certificate Error", "The public and private keys were generated successfully but an algorithm or password were not supplied for encryption.  The certs can still be found in your SSL store.");
					$this->respond(false, "Build finished with errors.  Check the logs for details.");
					return;
				}
			}
			// Check if the certificates should be placed into an encrypted container
			if (array_key_exists('container', $params)) {
				$cryptInfo['-c'] = (array_key_exists('container', $params)) ? $params['container'] : False;
				$cryptInfo['-calgo'] = (array_key_exists('c_algo', $params)) ? $params['c_algo'] : False;
				$cryptInfo['-cpass'] = (array_key_exists('c_pass', $params)) ? $params['c_pass'] : False;
			}
				
			// Build an argument string with all available arguments
			foreach ($cryptInfo as $k => $v) {
				if (!$v) {continue;}
				$argString .= $k . " \"" . $v . "\" ";
			}
			$argString = rtrim($argString);

			// Execute encryptKeys.sh with the parameters and check for errors
			$resData = array();
			exec(__SCRIPTS__ . "encryptKeys.sh " . $argString, $resData);
			$res = implode("\n", $resData);
			if ($res != "Complete") {
				$this->logError("Certificate Encryption Error", "The public and private keys were generated successfully but encryption failed with the following error:\n\n" . $res);
				$this->respond(false, "Build finished with errors.  Check the logs for details.");
				return;
			}
		}
		$this->respond(true, "Keys created successfully!");
	}

	private function loadCertificates() {
		$certs = $this->getKeys(__SSLSTORE__);
		$certs = array_merge($certs, $this->getKeys(__SSHSTORE__));
		$this->respond(true,null,$certs);
	}
	
	private function getKeys($dir) {
		$keyType = ($dir == __SSLSTORE__) ? "TLS/SSL" : "SSH";
		$keys = scandir($dir);
		$certs = array();
		foreach ($keys as $key) {
			if ($key == "." || $key == "..") {continue;}

			$parts = explode(".", $key);
			$fname = $parts[0];
			$type = "." . $parts[1];

			// Check if the object name already exists in the array
			if ($this->objNameExistsInArray($fname, $certs)) {
				foreach ($certs as &$obj) {
					if ($obj->Name == $fname) {
						$obj->Type .= ", " . $type;
					}
				}
			} else {
				// Add a new object to the array
				$enc = ($this->keyIsEncrypted($fname, $keyType)) ? "Yes" : "No";
				array_push($certs, (object)array('Name' => $fname, 'Type' => $type, 'Encrypted' => $enc, 'KeyType' => $keyType, 'Authorized' => $this->checkSSHKeyAuth($fname, $keyType)));
			}
		}
		return $certs;
	}
	
	private function checkSSHKeyAuth($keyName, $keyType) {
		if ($keyType != "SSH") {return false;}
		$res = exec(__SCRIPTS__ . "checkSSHKey.sh -k " . $keyName);
		if ($res == "TRUE") {
			return true;
		}
		return false;
	}
	
	private function revokeSSHKey($keyName) {
		exec(__SCRIPTS__ . "revokeSSHKey.sh -k " . $keyName);
		$this->respond(true);
	}

	private function keyIsEncrypted($keyName, $keyType) {
		$data = array();
		$keyDir = ($keyType == "SSH") ? __SSHSTORE__ : __SSLSTORE__;
		exec(__SCRIPTS__ . "testEncrypt.sh -k " . $keyName . " -d " . $keyDir . " 2>&1", $data);
		if ($data[0] == "writing RSA key") {
			return false;
		} else if ($data[0] == "unable to load Private Key") {
			return true;
		}
	}

	private function downloadKeys($keyName, $keyType) {
		$argString = "-o " . $keyName . ".zip -f \"";

		// Grab all of the keys, certs, and containers
		$keyDir = ($keyType == "SSH") ? __SSHSTORE__ : __SSLSTORE__;
		$contents = scandir($keyDir);
		$certs = array();
		foreach ($contents as $cert) {
			if ($cert == "." || $cert == "..") {continue;}
			$parts = explode(".", $cert);
			$fname = $parts[0];
			$type = "." . $parts[1];
			
			if ($fname == $keyName) {
				$argString .= $cert ." ";
			}
		}
		$argString = rtrim($argString);
		$argString .= "\"";

		// Pack them into an archive
		exec(__SCRIPTS__ . "packKeys.sh " . $keyDir . " " . $argString);

		// Check if the files were archived properly
		$archiveExists = False;
		foreach (scandir(__DOWNLOAD__) as $file) {
			if ($file == $keyName . ".zip") {
				$archiveExists = True;
			}
		}

		// Begin downloading the archive
		if ($archiveExists) {
			$this->respond(true, null, $this->downloadFile(__DOWNLOAD__ . $keyName . ".zip"));
		} else {
			$this->respond(false, "Failed to create archive.");
		}
	}

	private function clearDownloadArchive() {
		foreach (scandir(__DOWNLOAD__) as $file) {
			if ($file == "." || $file == "..") {continue;}
			unlink(__DOWNLOAD__ . $file);
		}
		$files = glob(__DOWNLOAD__ . "*");
		if (count($files) > 0) {
			$this->respond(false, "Failed to clear archive.");
		}
		$this->respond(true);
	}

	private function objNameExistsInArray($name, $arr) {
		foreach ($arr as $x) {
			if ($x->Name == $name) {
				return True;
			}
		}
		return False;
	}

	private function removeCertificate($delCert, $keyType) {
		$res = True;
		$msg = "Failed to delete the following files:";
		$keyDir = ($keyType == "SSH") ? __SSHSTORE__ : __SSLSTORE__;
		foreach (scandir($keyDir) as $cert) {
			if ($cert == "." || $cert == "..") {continue;}
			if (explode(".",$cert)[0] == $delCert) {
				if (!unlink($keyDir . $cert)) {
					$res = False;
					$msg .= " " . $cert;
				}
			}
		}
		$this->respond($res, $msg);
	}

	private function respond($success, $msg = null, $data = null, $error = null) {
		$this->response = array("success" => $success,"message" => $msg, "data" => $data, "error" => $error);
	}

	private function getNginxSSLCerts() {
		$res = $this->checkSSLConfig();
		if ($res == "") {
			$this->respond(false, array("[!] SSL keys not configured in nginx.conf"));
		} else {
			$res = str_replace("/", "", $res);
			$this->respond(true, null, explode(" ", $res));
		}
	}

	private function checkSSLConfig() {
		$retData = array();
                exec("cat /etc/nginx/nginx.conf | grep -Eo '/[a-zA-Z0-9]*.cer|/[a-zA-Z0-9]*.pem'", $retData);
                $res = implode(" ", $retData);
		return $res;
	}

	private function unSSLPineapple() {
		// First check if SSL is configured
		if ($this->checkSSLConfig() == "") {
			$this->respond(true);
			return;
		}

		// This function removethe ssl/ directory from /etc/nginx/
		// and replaces the current nginx.conf with the original
		$status = True;
		if (!$this->removeKeysFromNginx()) {
			$status = False;
			$this->logError("UnSSLPineapple", "Failed to remove keys from /etc/nginx/ssl/.");
		}
		if (!rmdir("/etc/nginx/ssl/")) {
			$status = False;
			$this->logError("UnSSLPineapple", "Failed to remove /etc/nginx/ssl/ directory.");
		}

		// Have to do this because for some reason copy() doesn't work
		// and neither does using a shell script to copy to /etc/nginx/
		$old_conf = fopen("/etc/nginx/nginx.conf", "w");
		$new_conf = file_get_contents(__INCLUDES__ . "nginx.conf");
		fwrite($old_conf, $new_conf);
		fclose($old_conf);

		if ($this->checkSSLConfig() != "") {
			$status = False;
			$this->logError("UnSSLPineapple", "Failed to copy original nginx.conf file back to /etc/nginx/.  At this point the Nginx SSL directory has been deleted so it is important to get the original configuration file back in its proper place.  Go to /pineapple/modules/Papers/includes/ and copy nginx.conf to /etc/nginx/ then issue the command '/etc/init.d/nginx reload'.");
		}
		exec("/etc/init.d/nginx reload");
		$this->respond($status);
	}

	private function securePineapple($certName, $keyType) {
		// Check the key type to determine whether we are adding an SSH key or SSL keys
		if ($keyType == "SSH") {
			// Modify authorized_keys file
			exec(__SCRIPTS__ . "addSSHKey.sh -k " . $certName);
			$this->respond(true);
		} else {
			// Update SSL configs
			$this->SSLPineapple($certName);
		}
	}
	
	private function SSLPineapple($certName) {
		// Check if nginx SSL directory exists
		$nginx_ssl_dir = "/etc/nginx/ssl/";
		if (!file_exists($nginx_ssl_dir)) {
			if (!mkdir($nginx_ssl_dir)) {
				$this->logError("SSL Config Failure", "nginx SSL directory does not exist and it could not be created.");
				$this->respond(false, "An error occurred.  Check the logs for details.");
				return;
			}
		}

		// Check if SSL is already configured, if so simply replace the keys
		// and skip the rest of this function
		if ($this->checkSSLConfig() != "") {
			$this->replaceSSLCerts($certName);
			return;
		}

		// Copy selected key pair to the SSL directory
		if (!$this->copyKeysToNginx($certName)) {
			$this->respond(false, "An error occurred.  Check the logs for details.");
			return;
		}

		// Call the nginx configuration script cfgNginx.py
		$retData = array();
		$res = exec("python " . __SCRIPTS__ . "cfgNginx.py " . $certName, $retData);
		if ($res == "Complete") {
			$this->respond(true);
			return;
		}
	
		// Log whatever message came from cfgNginx.py and return False
		$this->logError("SSL Config Failure", $retData);
		$this->respond(false, "An error occurred.  Check the logs for details.");
	}

	private function replaceSSLCerts($certName) {
		// Remove the old keys from the SSL store
		$this->removeKeysFromNginx();

		// Copy selected key pair to the SSL directory
		if (!$this->copyKeysToNginx($certName)) {
			$this->respond(false, "An error occurred.  Check the logs for details.");
			return;
		}

		$retData = array();
		$res = exec(__SCRIPTS__ . "replaceKeys.sh " . $certName, $retData);
		if (!$res) {
			$this->respond(true);
			return;
		}
		$this->logError("Replace SSL Cert Failure", $retData);
		$this->respond(false);
		return;
	}
	private function copyKeysToNginx($certName) {
		// Copy selected key pair to the SSL directory
		$retData = array();
		$res = exec(__SCRIPTS__ . "copyKeys.sh " . __SSLSTORE__ . $certName, $retData);
		if ($res) {
			$this->logError("Replace SSL Cert Failure", $retData);
			return False;
		}
		return True;
	}
	private function removeKeysFromNginx() {
		$retData = array();
		$res = exec(__SCRIPTS__ . "removeKeys.sh", $retData);
		if ($res) {
			$this->logError("Key Removal Failed", "Old keys may still exist in /etc/nginx/ssl/.  Continuing process anyway...");
			return False;
		}
		return True;
	}
	private function getLogs($type) {
		$dir = ($type == "error") ? __LOGS__ : __CHANGELOGS__;
		$contents = array();
		foreach (scandir($dir) as $log) {
			if ($log == "." || $log == "..") {continue;}
			array_push($contents, $log);
		}
		$this->respond(true, null, $contents);
	}
	private function logError($filename, $data) {
		$time = exec("date +'%H_%M_%S'");
		$fh = fopen(__LOGS__ . str_replace(" ","_",$filename) . "_" . $time . ".txt", "w+");
		fwrite($fh, $data);
		fclose($fh);
	}
	private function retrieveLog($logname, $type) {
		$dir = ($type == "error") ? __LOGS__ : ($type == "help") ? __HELPFILES__ : __CHANGELOGS__;
		$data = file_get_contents($dir . $logname);
		if (!$data) {
			$this->respond(false);
			return;
		}
		$this->respond(true, null, $data);
	}
	private function deleteLog($logname) {
		$data = unlink(__LOGS__ . $logname);
		if (!$data) {
			$this->respond(false, "Failed to delete log.");
			return;
		}
		$this->respond(true);
	}
}
?>
