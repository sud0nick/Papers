registerController('PapersController', ['$api', '$scope', '$sce', function($api, $scope, $sce) {

	$scope.certDaysValid		= "365";
	$scope.certBitSize 		= "2048";
	$scope.certSigAlgo		= "sha256";
	$scope.certKeyName		= "";
	$scope.certInfoCountry		= "";
	$scope.certInfoState		= "";
	$scope.certInfoLocality		= "";
	$scope.certInfoOrganization	= "";
	$scope.certInfoSection		= "";
	$scope.certInfoCN		= "";
	$scope.certEncryptKeysBool	= false;
	$scope.certEncryptAlgo 		= "aes256";
	$scope.certEncryptPassword	= "";
	$scope.certExportPKCS12		= false;
	$scope.certEncryptPKCS12Algo	= "aes256";
	$scope.certContainerPassword	= "";
	$scope.certificates		= "";
	$scope.SSLStatus		= "";
	$scope.showCertThrobber		= false;
	$scope.showRemoveSSLButton	= true;
	$scope.showUnSSLThrobber	= false;
	$scope.logs			= "";
	$scope.changelogs		= "";
	$scope.currentLogTitle		= "";
	$scope.currentLogData		= "";
	$scope.dependsInstalled		= true;
	$scope.dependsProcessing	= false;

	$scope.checkDepends = (function(){
		$api.request({
			module: 'Papers',
			action: 'checkDepends'
		},function(response){
			if (response.success === true) {
				$scope.dependsInstalled = true;
			} else {
				$scope.dependsInstalled = false;
			}
		});
	});

	$scope.installDepends = (function(){
		$scope.dependsProcessing = true;
		$api.request({
			module: 'Papers',
			action: 'installDepends'
		},function(response){
			$scope.dependsProcessing = false;
			$scope.checkDepends();
			if (response.success === false) {
				alert("Failed to install dependencies.  Make sure you are connected to the internet.");
			}
		});
	});

	$scope.removeDepends = (function(){
		$scope.dependsProcessing = true;
		$api.request({
			module: 'Papers',
			action: 'removeDepends'
		},function(response){
			$scope.dependsProcessing = false;
			$scope.checkDepends();
		});
	});

	$scope.buildCertificate = (function() {
		var params = {};
		params['bitSize'] = $scope.certBitSize;
		params['sigalgo'] = $scope.certSigAlgo;

		if ($scope.certInfoCountry != ''){
			params['country'] = $scope.certInfoCountry;
		}
		if ($scope.certInfoState != '') {
			params['state'] = $scope.certInfoState;
		}
		if ($scope.certInfoLocality != ''){
			params['city'] = $scope.certInfoLocality;
		}
		if ($scope.certInfoOrganization != ''){
			params['organization'] = $scope.certInfoOrganization;
		}
		if ($scope.certInfoSection != ''){
			params['section'] = $scope.certInfoSection;
		}
		if ($scope.certInfoCN != ''){
			params['commonName'] = $scope.certInfoCN;
		}
		if ($scope.certDaysValid != ''){
			params['days'] = $scope.certDaysValid;
		}
		if ($scope.certKeyName != ''){
			params['keyName'] = $scope.certKeyName;
		} else {
			alert("You must enter a name for the keys!");
			return;
		}
		if ($scope.certEncryptKeysBool === true) {
			params['encrypt'] = "";
			params['algo'] = $scope.certEncryptAlgo;
			if (!$scope.certEncryptPassword) {
				alert("You must set a password for the private key!");
				return;
			}
			params['pkey_pass'] = $scope.certEncryptPassword;
		}
		if ($scope.certExportPKCS12 === true) {
                        params['container'] = "pkcs12";
                        params['c_algo'] = $scope.certEncryptPKCS12Algo;
                        if (!$scope.certContainerPassword) {
                                alert("You must enter a password for the exported container!");
                                return;
                        }
                        params['c_pass'] = $scope.certContainerPassword;
                }
		
		$('#buildKeysButton').css("display", "none");
		$('#papersThrobber').css("display", "block");
		$api.request({
			module: 'Papers',
			action: 'buildCert',
			parameters: params
		},function(response) {
			$scope.getLogs();
			if (response.success === true) {
				$('#papersThrobber').css("display", "none");
				$('#buildKeysButton').css("display", "block");
				$scope.loadCertificates();
			} else {
				console.log(response.message);
				// Add a message in the HTML to show responses
			}
			$api.reloadNavbar();
		});
	});

	$scope.clearForm = (function() {
		$scope.certDaysValid            = "365";
	        $scope.certBitSize              = "2048";
	        $scope.certSigAlgo              = "sha256";
	        $scope.certKeyName              = "";
	        $scope.certInfoCountry          = "";
	        $scope.certInfoState            = "";
	        $scope.certInfoLocality         = "";
	        $scope.certInfoOrganization     = "";
	        $scope.certInfoSection          = "";
	        $scope.certInfoCN               = "";
	        $scope.certEncryptKeysBool      = false;
	        $scope.certEncryptAlgo          = "aes256";
	        $scope.certEncryptPassword      = "";
	        $scope.certExportPKCS12         = false;
	        $scope.certEncryptPKCS12Algo    = "aes256";
	        $scope.certContainerPassword    = "";
	});

	$scope.loadCertificates = (function() {
                $api.request({
                        module: 'Papers',
                        action: 'loadCertificates'
                },function(response){
                        if (response.success === true) {
                                // Display certificate information
                                $scope.certificates = response.data;
                        } else {
                                // Display error
				console.log("Failed to load certificates.");
                        }
                });
        });

	$scope.downloadKeys = (function(name) {
		$scope.showCertThrobber = true;
		$api.request({
			module: 'Papers',
			action: 'downloadKeys',
			parameters: name
		},function(response){
			$scope.showCertThrobber = false;
			if (response.success === true) {
				window.location = '/api/?download=' + response.data;
			} else {
				console.log(response.message);
			}
			// Clear the download archive to keep things clean
			//$scope.clearDownloadArchive();
		});
	});

	$scope.clearDownloadArchive = (function(){
		$api.request({
			module: 'Papers',
			action: 'clearDownloadArchive'
		},function(response) {
			if (response.success === false) {
				console.log(response.message);
			}
		});
	});

        $scope.deleteKeys = (function(cert) {
		if (confirm("Confirm key deletion by pressing OK") == false) {return;}
		$scope.showCertThrobber = true;
                $api.request({
                        module: 'Papers',
                        action: 'removeCertificate',
                        certificate: cert
                },function(response){
			$scope.showCertThrobber = false;
			$scope.getLogs();
                        if (response.success === true) {
                                $scope.loadCertificates();
                        }
                });
        });

	$scope.sslPineapple = (function(cert) {
		$scope.showCertThrobber = true;
		$api.request({
			module: 'Papers',
			action: 'sslPineapple',
			certificate: cert
		},function(response) {
			$scope.showCertThrobber = false;
			if (response.success === true) {
				// Alert success
				$scope.getNginxSSLCerts();
				//alert("SSL configured successfully!\nPlease change the protocol in your address bar to https.");
			} else {
				// Alert error
				alert(response.message);
			}
		});
	});

	$scope.unSSLPineapple = (function(){
		$scope.showRemoveSSLButton = false;
		$scope.showUnSSLThrobber = true;
		$api.request({
			module: 'Papers',
			action: 'unSSLPineapple'
		},function(response){
			$scope.showUnSSLThrobber = false;
			$scope.showRemoveSSLButton = true;
			$scope.refresh();
			
			if (response.success === true) {
			} else {
			}
		});
	});

	$scope.getNginxSSLCerts = (function(){
		$api.request({
			module: 'Papers',
			action: 'getNginxSSLCerts'
		},function(response){
			if (response.success === true) {
				$scope.SSLStatus = response.data;
			} else {
				$scope.SSLStatus = response.message;
			}
		});
	});

	$scope.getLogs = (function(){
		$api.request({
			module: 'Papers',
			action: 'getLogs',
			type: 'error'
		},function(response){
			$scope.logs = response.data;
		});
	});

	$scope.getChangeLogs = (function(){
		$api.request({
			module: 'Papers',
			action: 'getLogs',
			type: 'changelog'
		},function(response){
			$scope.changelogs = response.data;
		});
	});

	$scope.readLog = (function(log,type){
		$scope.currentLogTitle = log;
		$api.request({
			module: 'Papers',
			action: 'readLog',
			parameters: log,
			type: type
		},function(response){
			if (response.success === true) {
				$scope.currentLogData = $sce.trustAsHtml(response.data);
			}
		});
	});

	$scope.deleteLog = (function(log){
		$api.request({
			module: 'Papers',
			action: 'deleteLog',
			parameters: log
		},function(response){
			$scope.getLogs();
			if (response === false) {
				alert(response.message);
			}
		});
	});

	$scope.refresh = (function(){
		$scope.getLogs();
		$scope.getChangeLogs();
		$scope.clearDownloadArchive();
	        $scope.getNginxSSLCerts();
	        $scope.loadCertificates();
	});

	$scope.checkDepends();
	$scope.refresh();
}])
