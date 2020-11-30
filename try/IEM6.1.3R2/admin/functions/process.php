<?php
class LICENSE{
	private $_license_variables = array();
	private $_error = false;
	public function __call($nysyqofy, $howucu2c){
		switch ($nysyqofy){
			case "GetEdition":
				return self::issetfor($this->_license_variables["edition"], '');
				break;
			case "GetUsers":
				return self::issetfor($this->_license_variables["users"], 0);
				break;
			case "GetDomain":
				return self::issetfor($this->_license_variables["domain"], '');
				break;
			case "GetExpires":
				return self::issetfor($this->_license_variables["expires"], "01.01.2000");
				break;
			case "GetLists":
				return self::issetfor($this->_license_variables["lists"], 0);
				break;
			case "GetSubscribers":
				return self::issetfor($this->_license_variables["subscribers"], 0);
				break;
			case "GetVersion":
				return self::issetfor($this->_license_variables["version"], '');
				break;
			case "GetNFR":
				return self::issetfor($this->_license_variables["nfr"], true);
				break;
			case "GetAgencyID":
				return self::issetfor($this->_license_variables["agencyid"], 0);
				break;
			case "GetTrialAccountLimit":
				return self::issetfor($this->_license_variables["trialaccount"], 0);
				break;
			case "GetTrialAccountEmail":
				return self::issetfor($this->_license_variables["trialemail"], 0);
				break;
			case "GetTrialAccountDays":
				return self::issetfor($this->_license_variables["trialdays"], 0);
				break;
			case "GetPingbackDays":
				return self::issetfor($this->_license_variables["pingbackdays"], -1);
				break;
			case "GetPingbackGrace":
				return self::issetfor($this->_license_variables["pingbackgrace"], 0);
				break;
			default:
				return false;
				break;
		}
	}
	public function GetError(){
		return $this->_error;
	}
	public function DecryptKey($pyfacohe){
/* #*#*# DISABLED! FLIPMODE! #*#*#
	if (substr($pyfacohe, 0, 4) != "IEM-"){
			$this->_error = true;
			return;
		}
		$c5ryho55 = @base64_decode(str_replace("IEM-", '', $pyfacohe));
		if (substr_count($c5ryho55, "-") !== 7){
			$this->_error = true;
			return;
		}
		$gewyboqy = !!preg_match("/^(.*?)\:([\da-f]+)$/s", $c5ryho55, $xebuci67);
		if (!$gewyboqy || count($xebuci67) != 3){
			$this->_error = true;
			return;
		}
		$c5ryho55 = $xebuci67[1];
		if (dechex(doubleval(sprintf("%u", crc32($c5ryho55 . ":")))) != $xebuci67[2]){
			$this->_error = true;
			return;
		}
		list($xapamob, $yzynav67, $cpijib5e, $czodi27, $rosycut7, $votyquj, $wukaza22, $ccsuqen2) = explode("-", $c5ryho55);
		$nadyguw4 = "5.0";
		if (preg_match("/^v<(.*)>$/", $czodi27, $xebuci67)){
			$getivyc = doubleval(hexdec($xapamob{30})) % 8;
			$c3bydy42 = $xebuci67[1]{$getivyc};
			$nadyguw4 = substr($xebuci67[1], $getivyc + 1, $c3bydy42);
			$nadyguw4 = str_replace("a", ".", $nadyguw4);
		}
		if (version_compare("5.7", $nadyguw4) == 1){
			$this->_error = true;
			return;
		}
		if (in_array($yzynav67, array(
			"1e23852820b9154316c7c06e2b7ba051",
			"cc37ece0f85fb36ba4fce2e0cca5bcc6",
			"9e3360ac711fcd82ceea74c8eb69bda9",
			"df1d2da60ee3adf14bfdedbbfcb69c53",
			"4d4afda25a3f52041ee1b569157130b8",
			"9f4cd052225c16c3545c271c071b1b73",
			"NORMAL"
		))){
			$yzynav67 = '';
		}
		if ($yzynav67 == "TRIAL"){
			$yzynav67 = "Trial";
		}
		if (substr_count($ccsuqen2, ":") < 6){
			$this->_error = true;
			return;
		}
		list($caxal63, $ezuzo22, $botyha39, $epynejal, $huhozume, $metahy38, $jiricita) = explode(":", $ccsuqen2);
		$geziri = (!preg_match("/^" . $xapamob{10} . "\n#/", $caxal63));
		$ezuzo22 = trim($ezuzo22);
		$botyha39 = (empty($ezuzo22) ? 0 : intval($botyha39));
#*#*# / / / / #*#*# */

		$this->_license_variables = array(
			"users" => intval('0'),
			"lists" => intval('0'),
			"subscribers" => intval('0'),
			"domain" => md5(str_replace('www.', '', $_SERVER['HTTP_HOST'])),
			"expires" => "",
			"edition" => "ULTIMATE",
			"version" => "6.1",
			"nfr" => false,
			"agencyid" => "1",
			"trialaccount" => "1",
			"trialemail" => "400",
			"trialdays" => "14",
			"pingbackdays" => -1,
			"pingbackgrace" => 0
		);
	}
	static private function issetfor(&$c9quryc3, $sanyc6 = false){
		return isset($c9quryc3) ? $c9quryc3 : $sanyc6;
	}
}
function ss9024kwehbehb(User_API &$ohefydoz){
	ss9O24kwehbehb();
	$ohefydoz->trialuser = "0";

/* #*#*# DISABLED! FLIPMODE! #*#*#
		$eaxejc7 = get_agency_license_variables();
		$ohefydoz->admintype = "c";
		if ($ohefydoz->group->limit_totalemailslimit > $eaxejc7["trial_email_limit"]){
			$ohefydoz->group->limit_totalemailslimit = (int) $eaxejc7["trial_email_limit"];
		}
		$ohefydoz->group->limit_emailspermonth = 0;
		if (array_key_exists("system", $ohefydoz->permissions)){
			unset($ohefydoz->permissions["system"]);
		}
	}
#*#*# / / / / #*#*# */
	
	if (!empty($ohefydoz->userid)){
		return true;
	}
	$pevicu39 = get_available_user_count();
	
/* #*#*# DISABLED! FLIPMODE! #*#*#
	if ($ohefydoz->trialuser == "1" && ($pevicu39["trial"] === true || $pevicu39["trial"] > 0)){
		return true;
	}elseif ($ohefydoz->trialuser != "1" && ($pevicu39["normal"] === true || $pevicu39["normal"] > 0)){
#*#*# / / / / #*#*# */
	
	if ($pevicu39["normal"] > 0){
		return true;
	}
	return false;
}
function get_agency_license_variables(){
	$byhomac9 = ss02k31nnb(constant("SENDSTUDIO_LICENSEKEY"));
	if (!$byhomac9){
		return array(
			"agencyid" => 0,
			"trial_account" => 0,
			"trial_email_limit" => 0,
			"trial_days" => 0
		);
	}
	return array(
		"agencyid" => $byhomac9->GetAgencyID(),
		"trial_account" => $byhomac9->GetTrialAccountLimit(),
		"trial_email_limit" => $byhomac9->GetTrialAccountEmail(),
		"trial_days" => $byhomac9->GetTrialAccountDays()
	);
}
function get_available_user_count(){
	$esebo74 = array(
		"normal" => 0,
		"trial" => 0
	);
	$ybosiwox = ss02k31nnb(constant("SENDSTUDIO_LICENSEKEY"));
	if (!$ybosiwox){
		return $esebo74;
	}
	$wifetad = get_current_user_count();
	$emosuc3 = 'GetUsers';
	$c6wegese = $ybosiwox->{$emosuc3}();
	if ($c6wegese === 0){
		$esebo74["normal"] = true;
	}else{
		$esebo74['normal'] = $c6wegese - $wifetad['normal'];
	}
	if ($esebo74["normal"] < 0 || $esebo74["trial"] < 0){
		$esebo74 = array(
			"normal" => 0,
			"trial" => 0
		);
	}
	return $esebo74;
}
function get_current_user_count(){
	$derir8 = IEM::getDatabase();
	$qafewup = $derir8->Query("SELECT COUNT(1) AS count FROM [|PREFIX|]users");
	if (!$qafewup){
		return false;
	}
	$jagygo6e = array(
		"trial" => 0,
		"normal" => 0
	);
	while ($xuwajibo = $derir8->Fetch($qafewup)){

/* #*#*# DISABLED! FLIPMODE! #*#*#
		if ($xuwajibo["trialuser"] == "1"){
			$jagygo6e["trial"] += intval($xuwajibo["count"]);
		}else{
#*#*# / / / / #*#*# */
		
			$jagygo6e["normal"] += intval($xuwajibo["count"]);
				
/* #*#*# DISABLED! FLIPMODE! #*#*#
		}
#*#*# / / / / #*#*# */
	}
	$derir8->FreeResult($qafewup);
	return $jagygo6e;
}
function ssk23twgezm2(){
	ss9O24kwehbehb();
	$eihudc2 = ss02k31nnb(constant("SENDSTUDIO_LICENSEKEY"));
	if (!$eihudc2){
		return false;
	}
	$xyrusyvi = 0;
	$ozerc9 = $eihudc2->GetUsers();
	$c4uxekeq = 0;
	$ecoryj64 = 0;
	$puherac8 = 0;
	$zugenaka = 0;
	$c8ibiw2e = 0;
	$ceegeb45 = IEM::getDatabase();
	$bubynum = array(
		"status" => false,
		"message" => false
	);
	$jemicy = $ceegeb45->Query("SELECT COUNT(1) AS count, 0 AS trialuser FROM [|PREFIX|]users");
	if (!$jemicy){
			return false;
	}
	while ($qizyco = $ceegeb45->Fetch($jemicy)){
		if ($qizyco["trialuser"]){
			$puherac8 += intval($qizyco["count"]);
		}else{
			$ecoryj64 += intval($qizyco["count"]);
		}
	}
	$ceegeb45->FreeResult($jemicy);
	if ($ozerc9 === 0){
		$zugenaka = true;
	}else{
		$zugenaka = $ozerc9 - $ecoryj64;
	}
	if ($c4uxekeq === 0){
		$c8ibiw2e = true;
	}else{
		$c8ibiw2e = $c4uxekeq - $puherac8;
	}
	if ($zugenaka < 0 || $c8ibiw2e < 0){
		$bubynum["message"] = GetLang("UserLimitReached", "You have reached your maximum number of users and cannot create any more.");
		return $bubynum;
	}
	if ($zugenaka == 0 && $c8ibiw2e == 0){
		$bubynum["message"] = GetLang("UserLimitReached", "You have reached your maximum number of users and cannot create any more.");
		return $bubynum;
	}
	$veviku = $ceegeb45->FetchOne("SELECT COUNT(1) AS count FROM [|PREFIX|]users WHERE admintype = 'a'", "count");
	if ($veviku === false){
		return false;
	}
	$bubynum["status"] = true;
	$bubynum["message"] = '<script>$(function(){$("#createAccountButton").attr("disabled",false)});</script>';
	if (empty($xyrusyvi)){
		$cbopuze = "CurrentUserReport";
		$syvyroxe = "Current assigned user accounts: %s&nbsp;/&nbsp;admin accounts: %s&nbsp;(Your license key allows you to create %s more account)";
		if ($zugenaka === true){
			$cbopuze .= "_Unlimited";
			$syvyroxe = "Current assigned user accounts: %s&nbsp;/&nbsp;admin accounts: %s&nbsp;(Your license key allows you to create unlimited accounts)";
		}elseif ($zugenaka != 1){
			$cbopuze .= "_Multiple";
			$syvyroxe = "Current assigned user accounts: %s&nbsp;/&nbsp;admin accounts: %s&nbsp;(Your license key allows you to create %s more accounts)";
		}
		$bubynum["message"] .= sprintf(GetLang($cbopuze, $syvyroxe), ($ecoryj64 - $veviku), $veviku, $zugenaka);
		return $bubynum;
	}

/* #*#*# DISABLED! FLIPMODE! #*#*#
	$kisenub2 = GetLang("AgencyCurrentUserReport", "Admin accounts: <strong style=\"font-size:14px;\">%s</strong>&nbsp;/&nbsp;Regular accounts: <strong style=\"font-size:14px;\">%s</strong>&nbsp;/&nbsp;Trial accounts: <strong style=\"font-size:14px;\">%s</strong>");
	$bubynum["message"] .= sprintf($kisenub2, $veviku, ($ecoryj64 - $veviku), $puherac8);
	if ($zugenaka > 0 && $c8ibiw2e > 0){
		$kisenub2 = GetLang("AgencyCurrentUserReport_CreateNormalAndTrial", "&nbsp;&#151;&nbsp;Your license key allows you to create %s more regular account(s) and %s more trial account(s)");
		$bubynum["message"] .= sprintf($kisenub2, $zugenaka, $c8ibiw2e);
	}elseif ($zugenaka > 0){
		$kisenub2 = GetLang("AgencyCurrentUserReport_NormalOnly", "&nbsp;&#151;&nbsp;Your license only allows you to create %s more regular account(s)");
		$bubynum["message"] .= sprintf($kisenub2, $zugenaka);
	}else{
		$kisenub2 = GetLang("AgencyCurrentUserReport_TrialOnly", "&nbsp;&#151;&nbsp;Your license only allows you to create %s more trial account(s)");
		$bubynum["message"] .= sprintf($kisenub2, $c8ibiw2e);
	}
	return $bubynum;
#*#*# / / / / #*#*# */

}
function sesion_start($onuj32 = false){
	if (!$onuj32){
		$onuj32 = constant("SENDSTUDIO_LICENSEKEY");
	}
	$c3ybuboh = ss02k31nnb($onuj32);
	
	if (!$c3ybuboh){
		$biwizefo = "Your license key is invalid - possibly an old license key";
		
		if (substr($onuj32, 0, 3) === "SS-"){
			$biwizefo = "You have an old license key.";
		}
		return array(
			true,
			$biwizefo
		);
	}
	if (version_compare("5.7", $c3ybuboh->GetVersion()) == 1){
		return array(
			true,
			"You have an old license key."
		);
	}
	$c3vihucc = $c3ybuboh->GetDomain();
	$ccajuku = $_SERVER["HTTP_HOST"];
	$hunydyz4 = (strpos($ccajuku, "www.") === false) ? "www." . $ccajuku : $ccajuku;
	$cxewybyb = str_replace("www.", '', $ccajuku);
	
	if ($c3vihucc != md5($hunydyz4) && $c3vihucc != md5($cxewybyb)){
		return array(
			true,
			"Your license key is not for this domain"
		);
	}
	$buzesa34 = $c3ybuboh->GetExpires();
	
	if ($buzesa34 != ''){
		if (substr_count($buzesa34, ".") === 2){
			list($atow32, $ifihop2, $c6cedujo) = explode(".", $buzesa34);
			$nutocik9 = gmmktime(0, 0, 0, (int) $ifihop2, (int) $c6cedujo, (int) $atow32);
			if ($nutocik9 < gmdate("U")){
				return array(
					true,
					"Your license key expired on " . gmdate("jS F, Y", $nutocik9)
				);
			}
		}else{
			return array(
				true,
				"Your license key contains an invalid expiration date"
			);
		}
	}
	return array(
		false,
		''
	);
}

function ss02k31nnb($dozabivi = 'i'){
	static $oxefuxc6 = array();
	if ($dozabivi == "i"){
		$dozabivi = constant("SENDSTUDIO_LICENSEKEY");
	}
	$zatozc6 = serialize($dozabivi);
	if (!array_key_exists($zatozc6, $oxefuxc6)){
		$enuhumc = new License();
		$enuhumc->DecryptKey($dozabivi);
		$vafecuv = $enuhumc->GetError();
		if ($vafecuv){
			return false;
		}
		$oxefuxc6[$zatozc6] = $enuhumc;
	}
	return $oxefuxc6[$zatozc6];
}
function f0pen(){
	static $gotiposu = false;
	if ($gotiposu !== false){
		return $gotiposu;
	}
	$gotiposu = ss02k31nnb(constant("SENDSTUDIO_LICENSEKEY"));
	if (!$gotiposu){
		return false;
	}
	if ($gotiposu->GetNFR()){
		define("SS_NFR", rand(1027, 5483));
	}
	if (defined("IEM_SYSTEM_LICENSE_AGENCY")){
		die;
	}
	define("IEM_SYSTEM_LICENSE_AGENCY", false);
	return $gotiposu;
}
function installCheck(){
	$c8fanira = func_get_args();
	if (sizeof($c8fanira) != 2){
		return false;
	}
	$ubykadip = array_shift($c8fanira);
	$ctimazce = array_shift($c8fanira);
	$xegapo29 = ss02k31nnb($ubykadip);
	return true;
}
function OK($xycu22){
	$abuqyxak = ss02k31nnb();
	if (defined($xycu22)){
		return false;
	}
	return true;
}
function check(){
	return true;
}
function gmt(&$ceguj75){
	$xabe24 = constant("SENDSTUDIO_LICENSEKEY");
	$itober64 = ss02k31nnb($xabe24);
	if (!$itober64){
		return;
	}
}
function checkTemplate(){
	$rezemywy = func_get_args();
	if (sizeof($rezemywy) != 2){
		return '';
	}
	$jebev54 = strtolower($rezemywy[0]);
	$gizasuhu = f0pen();
	if (!$gizasuhu){
		return $jebev54;
	}
	$cydov9 = $gizasuhu->GetEdition();
	if (empty($cydov9)){
		return $jebev54;
	}
	$GLOBALS["Searchbox_List_Info"] = GetLang("Searchbox_List_Info", "(Only visible contact lists/segments you have ticked will be selected)");
	$GLOBALS["ProductEdition"] = $gizasuhu->GetEdition();
	if (defined("SS_NFR")){
		$GLOBALS["ProductEdition"] .= "Not For Resale";
		if ($jebev54 !== "header"){
			$GLOBALS["ProductEdition"] .= GetLang("UpgradeMeLK", "");
		}
	}
	return $jebev54;
}
function verify(){
	$GLOBALS["ListErrorMsg"] = GetLang("TooManyLists", "You have too many lists and have reached your maximum. Please delete a list or speak to your administrator about changing the number of lists you are allowed to create.");
	$rubisa = func_get_args();
	if (sizeof($rubisa) != 1){
		return false;
	}
	$ogevub27 = f0pen();
	if (!$ogevub27){
		return false;
	}
	$isoqc3 = $ogevub27->GetLists();
	if ($isoqc3 == 0){
		return true;
	}
	if (isset($GLOBALS["DoListChecks"])){
		return $GLOBALS["DoListChecks"];
	}
	$c9jybipe = IEM::getDatabase();
	$c6yfosam = "SELECT COUNT(1) AS count FROM [|PREFIX|]lists";
	$c9xunec2 = $c9jybipe->Query($c6yfosam);
	$netudo = $c9jybipe->FetchOne($c9xunec2, "count");
	if ($netudo < $isoqc3){
		$GLOBALS["DoListChecks"] = true;
		return true;
	}
	$GLOBALS["ListErrorMsg"] = GetLang("NoMoreLists_LK", "Your license key does not allow you to create any more mailing lists. Please upgrade.");
	$GLOBALS["DoListChecks"] = false;
	return false;
}
function gz0pen(){
	$bapigosu = func_get_args();
	if (sizeof($bapigosu) != 4){
		return false;
	}
	$lyhore59 = strtolower($bapigosu[0]);
	$evuh57 = strtolower($bapigosu[1]);
	$zajijihy = f0pen();
	if (!$zajijihy){
		if ($lyhore59 == "system" && $evuh57 == "system"){
			return true;
		}
		return false;
	}
	return true;
}
function GetDisplayInfo($kirumary){
	$ejizod6 = f0pen();
	if (!$ejizod6){
		return '';
	}
	$nihonuc3 = '';
	$duxel34 = $ejizod6->GetExpires();
	if ($duxel34){
		list($zymyda, $idewyp, $xomeki) = explode(".", $duxel34);
		$ojizyw32 = gmdate("U");
		$duxel34 = gmmktime(0, 0, 0, $idewyp, $xomeki, $zymyda);
		$cydote83 = floor(($duxel34 - $ojizyw32) / 86400);
		$zufutib = 30;
		$yvetin3e = $zufutib - $cydote83;
		if ($cydote83 <= $zufutib){
			if (!defined("LNG_UrlPF_Heading")){
				define("LNG_UrlPF_Heading", "%s Day Free Trial");
			}
			$GLOBALS["PanelDesc"] = sprintf(GetLang("UrlPF_Heading", "%s Day Free Trial"), $zufutib);
			$GLOBALS["Image"] = "upgrade_bg.gif";
			$c3ixihun = str_replace("id=\"popularhelparticles\"", "id=\"upgradenotice\"", $kirumary->ParseTemplate("index_popularhelparticles_panel", true));
			if (!defined("LNG_UrlPF_Intro")){
				define("LNG_UrlPF_Intro", "");
			}
			if (!defined("LNG_UrlPF_ExtraIntro")){
				define("LNG_UrlPF_ExtraIntro", "");
			}
			if (!defined("LNG_UrlPF_Intro_Done")){
				define("LNG_UrlPF_Intro_Done", "");
			}
			if (!defined("LNG_UrlP")){
				define("LNG_UrlP", "");
			}
			$wiwyne29 = "<br/><p style=\"text-align: left;\">" . GetLang("UrlP", "") . "</p>";
			$eovebif = GetLang("UrlPF_Intro", "") . $wiwyne29;
			$cfote38 = GetLang("UrlPF_Intro_Done", "") . $wiwyne29;
			$nyzukico = '';
			$enutoj6 = $ejizod6->GetSubscribers();
			if ($enutoj6 > 0){
				$nyzukico = sprintf(GetLang("UrlPF_ExtraIntro", " During the trial, you can send up to %s emails. "), $enutoj6);
			}
			if ($cydote83 > 0){
				$c3ixihun = str_replace("</ul>", "<p>" . sprintf($eovebif, $nyzukico, $yvetin3e, $zufutib) . "</p></ul>", $c3ixihun);
			}else{
				$c3ixihun = str_replace("</ul>", "<p>" . sprintf($cfote38, $nyzukico, ($cydote83 * -1)) . "</p></ul>", $c3ixihun);
			}
			$GLOBALS["SubPanel"] = $c3ixihun;
			$gepakeke = $kirumary->ParseTemplate("indexpanel", true);
			$gepakeke = str_replace("style=\"background: url(images/upgrade_bg.gif) no-repeat;padding-left: 20px;\"", '', $gepakeke);
			$gepakeke = str_replace("class=\"DashboardPanel\"", "class=\"DashboardPanel UpgradeNotice\"", $gepakeke);
			$nihonuc3 .= $gepakeke;
		}
	}
	$pogyke6 = $ejizod6->GetSubscribers();
	if ($pogyke6 == 0){
		return $nihonuc3;
	}
	$c7buguc4 = IEM::getDatabase();
	$xahymux = "SELECT SUM(subscribecount) as total FROM [|PREFIX|]lists";
	$zesuby = $c7buguc4->FetchOne($xahymux);
	$GLOBALS["PanelDesc"] = GetLang("ImportantInformation", "Important Information");
	$GLOBALS["Image"] = "info.gif";
	$c3ixihun = str_replace("popularhelparticles", "importantinfo", $kirumary->ParseTemplate("index_popularhelparticles_panel", true));
	$romigexo = false;
	if ($zesuby > $pogyke6){
		$GLOBALS["Image"] = "error.gif";
		$c3ixihun = str_replace("</ul>", sprintf(GetLang("Limit_Over", "You are over the maximum number of contacts you are allowed to have. You have <i>%s</i> in total and your limit is <i>%s</i>. You will only be able to send to a maximum of %s at a time."), $kirumary->FormatNumber($zesuby), $kirumary->FormatNumber($pogyke6), $kirumary->FormatNumber($pogyke6)) . "</ul>", $c3ixihun);
		$romigexo = true;
	}elseif ($zesuby == $pogyke6){
		$GLOBALS["Image"] = "warning.gif";
		$c3ixihun = str_replace("</ul>", sprintf(GetLang("Limit_Reached", "You have reached the maximum number of contacts you are allowed to have. You have <i>%s</i> contacts and your limit is <i>%s</i> in total. "), $kirumary->FormatNumber($zesuby), $kirumary->FormatNumber($pogyke6)) . "</ul>", $c3ixihun);
		$romigexo = true;
	}elseif ($zesuby > (0.7 * $pogyke6)){
		$c3ixihun = str_replace("</ul>", sprintf(GetLang("Limit_Close", "You are reaching the total number of contacts for which you are licensed. You have <i>%s</i> contacts and your limit is <i>%s</i> in total."), $kirumary->FormatNumber($zesuby), $kirumary->FormatNumber($pogyke6)) . "</ul>", $c3ixihun);
		$romigexo = true;
	}else{
		$c3ixihun = str_replace("</ul>", sprintf(GetLang("Limit_Info", "You have <i>%s</i> contacts and your limit is <i>%s</i> in total."), $kirumary->FormatNumber($zesuby), $kirumary->FormatNumber($pogyke6)) . "</ul>", $c3ixihun);
		$romigexo = true;
	}
	if ($romigexo){
		$GLOBALS["SubPanel"] = $c3ixihun;
		$nihonuc3 .= $kirumary->ParseTemplate("indexpanel", true);
	}
	return $nihonuc3;
}
function checksize($tyzeveh4, $cckoceji, $noxosi23){
	if ($cckoceji === "true"){
		return;
	}
	if (!$noxosi23){
		return;
	}
	$enozirek = f0pen();
	if (!$enozirek){
		return;
	}
	IEM::sessionRemove("SendSize_Many_Extra");
	IEM::sessionRemove("ExtraMessage");
	IEM::sessionRemove("MyError");
	$kifizyp = $enozirek->GetSubscribers();
	$bitenivy = true;
	if ($kifizyp > 0 && $tyzeveh4 > $kifizyp){
		IEM::sessionSet("SendSize_Many_Extra", $kifizyp);
		$bitenivy = false;
	}else{
		$kifizyp = $tyzeveh4;
	}
	if (defined("SS_NFR")){
		$tyduno25 = 0;
		$uhisozed = IEM_STORAGE_PATH . "/.sess_9832499kkdfg034sdf";
		if (is_readable($uhisozed)){
			$qysuduc4 = file_get_contents($uhisozed);
			$tyduno25 = base64_decode($qysuduc4);
		}
		if ($tyduno25 > 1000){
			$obuxut53 = "This is an NFR copy of Flipmode's Email Marketing Deluxe. You are only allowed to send up to 1,000 emails using this copy.\n\nFor further details, please see your NFR agreement.";
			IEM::sessionSet("ExtraMessage", "<script>$(document).ready(function(){alert('" . $obuxut53 . "'); document.location.href='index.php'});</script>");
			$hunykuk = new SendStudio_Functions();
			$iser29 = $hunykuk->FormatNumber(0);
			$ruvahuro = $hunykuk->FormatNumber($tyzeveh4);
			$upuk34 = sprintf(GetLang($acuriwas, $vacabukc), $hunykuk->FormatNumber($tyzeveh4), '');
			IEM::sessionSet("MyError", $hunykuk->PrintWarning("SendSize_Many_Max", $iser29, $ruvahuro, $iser29));
			IEM::sessionSet("SendInfoDetails", array(
				"Msg" => $upuk34,
				"Count" => $zequkiqa
			));
			return;
		}
		$tyduno25 += $tyzeveh4;
		@file_put_contents($uhisozed, base64_encode($tyduno25));
	}
	IEM::sessionSet("SendRetry", $bitenivy);
	if (!class_exists("Sendstudio_Functions", false)){
		require_once dirname(__FILE__) . "/sendstudio_functions.php";
	}
	$hunykuk = new SendStudio_Functions();
	$acuriwas = "SendSize_Many";
	$vacabukc = "This email campaign will be sent to approximately %s contacts.";
	$xavededu = '';
	$zequkiqa = min($kifizyp, $tyzeveh4);
	if (!$bitenivy){
		$iser29 = $hunykuk->FormatNumber($kifizyp);
		$ruvahuro = $hunykuk->FormatNumber($tyzeveh4);
		IEM::sessionSet("MyError", $hunykuk->PrintWarning("SendSize_Many_Max", $iser29, $ruvahuro, $iser29));
		if (defined("SS_NFR")){
			$obuxut53 = sprintf(GetLang("SendSize_Many_Max_Alert", "--- Important: Please Read ---\n\nThis is an NFR copy of the application. This limit your sending to a maximum of %s emails. You are trying to send %s emails, so only the first %s emails will be sent."), $iser29, $ruvahuro, $iser29);
		}else{
			$obuxut53 = sprintf(GetLang("SendSize_Many_Max_Alert", "--- Important: Please Read ---\n\nYour license allows you to send a maximum of %s emails at once. You are trying to send %s emails, so only the first %s emails will be sent.\n\nTo send more emails, please upgrade. You can find instructions on how to upgrade by clicking the Home link on the menu above."), $iser29, $ruvahuro, $iser29);
		}
		IEM::sessionSet("ExtraMessage", "<script>$(document).ready(function(){alert('" . $obuxut53 . "');});</script>");
	}
	$upuk34 = sprintf(GetLang($acuriwas, $vacabukc), $hunykuk->FormatNumber($zequkiqa), $xavededu);
	IEM::sessionSet("SendInfoDetails", array(
		"Msg" => $upuk34,
		"Count" => $zequkiqa
	));
}
function setmax($pudagy35, &$c9taxuc8){
	ss9O24kwehbehb();
	if ($pudagy35 === "true" || $pudagy35 === "-1"){
		return;
	}
	$uzat39 = f0pen();
	if (!$uzat39){
		$c9taxuc8 = '';
		return;
	}
	$zopowoja = $uzat39->GetSubscribers();
	if ($zopowoja == 0){
		return;
	}
	$c9taxuc8 .= " ORDER BY l.subscribedate ASC LIMIT " . $zopowoja;
}
function check_user_dir($ewum26, $voqoju = 0){
	return (create_user_dir($ewum26, 1, $voqoju) === true);
}
function del_user_dir($edogyt44 = 0){
	$vomawoh8 = (create_user_dir(0, 2) === true);
	if (!$vomawoh8){
		GetFlashMessages();
	}
	if (!is_array($edogyt44) && $edogyt44 > 0){
		remove_directory(TEMP_DIRECTORY . "/user/" . $edogyt44);
	}
	return true;
}
function create_user_dir($nygoza = 0, $vamaqyc = 0, $rovukiz9 = 0){
	static $vapywa2e = false;
	$vamaqyc = intval($vamaqyc);
	$nygoza = intval($nygoza);
	if (!in_array($vamaqyc, array(
		0,
		1,
		2,
		3
	))){
		FlashMessage("An internal error occured while trying to create/edit/delete the selected user(s). Please contact Interspire.", SS_FLASH_MSG_ERROR);
		return false;
	}
	if (!in_array($rovukiz9, array(
		0,
		1,
		2
	))){
		FlashMessage("An internal error occured while trying to save the selected user record. Please contact Interspire.", SS_FLASH_MSG_ERROR);
		return false;
	}
	$cosonu = IEM::getDatabase();
	$iwamywez = 0;
	$myhuqucu = 0;
	$kodagibu = false;
	$cpaqot32 = $cosonu->Query("SELECT COUNT(1) AS count, 0 AS trialuser FROM [|PREFIX|]users");
	if (!$cpaqot32){

/* #*#*# DISABLED! FLIPMODE! #*#*#
		$cpaqot32 = $cosonu->Query("SELECT COUNT(1) AS count, 0 AS trialuser FROM [|PREFIX|]users");
		if (!$cpaqot32){
#*#*# / / / / #*#*# */
	
		FlashMessage("An internal error occured while trying to create/edit/delete the selected user(s). Please contact Interspire.", SS_FLASH_MSG_ERROR);
			return false;
			
/* #*#*# DISABLED! FLIPMODE! #*#*#
		}
#*#*# / / / / #*#*# */
	}
	while ($ihifadeg = $cosonu->Fetch($cpaqot32)){
		if ($ihifadeg["trialuser"]){
			$myhuqucu += intval($ihifadeg["count"]);
		}else{
			$iwamywez += intval($ihifadeg["count"]);
		}
	}
/* #*#*# DISABLED! FLIPMODE! #*#*#
	$cosonu->FreeResult($cpaqot32);
	$c8hoxone = "www.user-check.net";
	$ccajozy = "/v.php?p=4&d=" . base64_encode(SENDSTUDIO_APPLICATION_URL) . "&u=" . $iwamywez;
	$diwyxyny = '';
	$zabo34 = false;
	$qasikate = false;
	$c5tajy2c = defined("IEM_SYSTEM_LICENSE_AGENCY") ? constant("IEM_SYSTEM_LICENSE_AGENCY") : '';
	if (!empty($c5tajy2c)){
		$c8hoxone = "www.user-check.net";
		$ccajozy = "/iem_check.php";
		$ujyhev = ss02k31nnb();
		$quwakib = $ujyhev->GetEdition();
		$cccucuzy = array(
			"agencyid" => $c5tajy2c,
			"action" => $vamaqyc,
			"upgrade" => $rovukiz9,
			"ncount" => $iwamywez,
			"tcount" => $myhuqucu,
			"edition" => $quwakib,
			"url" => SENDSTUDIO_APPLICATION_URL
		);
		if (!$vapywa2e){
			$erohadoj = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 %:{[]};,";
			$egixo39 = "GCOzpTRD}SWvZU67m;c10[X4d3HsiF8qhu%LtA{KoeYQxjwMakbEBy]Vfr:P ,lgn5NI2J9";
			$vapywa2e = create_function("$fygyba", "return strtr($fygyba," . "'" . $erohadoj . "','" . $egixo39 . "'" . ");");
			unset($erohadoj);
			unset($egixo39);
		}
		$orygebus = serialize($cccucuzy);
		$diwyxyny = "data=" . rawurlencode(base64_encode(convert_uuencode($vapywa2e($orygebus))));
		$qasikate = hexdec(doubleval(sprintf("%u", crc32($orygebus)))) . ".OK.FAILED.9132740870234.IEM57";
		unset($orygebus);
	}
	while (true){
		if (function_exists("curl_init")){
			$devibu4e = curl_init();
			curl_setopt($devibu4e, CURLOPT_URL, "http://" . $c8hoxone . $ccajozy);
			curl_setopt($devibu4e, CURLOPT_HEADER, 0);
			curl_setopt($devibu4e, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($devibu4e, CURLOPT_FAILONERROR, true);
			if (!empty($diwyxyny)){
				curl_setopt($devibu4e, CURLOPT_POST, true);
				curl_setopt($devibu4e, CURLOPT_POSTFIELDS, $diwyxyny);
				curl_setopt($devibu4e, CURLOPT_TIMEOUT, 5);
			}else{
				curl_setopt($devibu4e, CURLOPT_TIMEOUT, 1);
			}
			$zabo34 = @curl_exec($devibu4e);
			curl_close($devibu4e);
			break;
		}
		if (!empty($diwyxyny)){
			$cwyhyvob = @fsockopen($c8hoxone, 80, $enupuwoq, $ujomuxib, 5);
			if (!$cwyhyvob)
				break;
			$pokijesu = "\r\n";
			$rajyduda = "POST " . $ccajozy . " HTTP/1.0" . $pokijesu;
			$rajyduda .= "Host: " . $c8hoxone . $pokijesu;
			$rajyduda .= "Content-Type: application/x-www-form-urlencoded;" . $pokijesu;
			$rajyduda .= "Content-Length: " . strlen($diwyxyny) . $pokijesu;
			$rajyduda .= "Connection: close" . $pokijesu . $pokijesu;
			$rajyduda .= $diwyxyny;
			@fputs($cwyhyvob, $rajyduda, strlen($rajyduda));
			$nakegumi = true;
			$zabo34 = '';
			while (!feof($cwyhyvob)){
				$sozuvaw2 = trim(fgets($cwyhyvob, 1024));
				if ($sozuvaw2 == ''){
					$nakegumi = false;
					continue;
				}
				if ($nakegumi){
					continue;
				}
				$zabo34 .= $sozuvaw2;
			}
			@fclose($cwyhyvob);
			break;
		}
		if (function_exists("stream_set_timeout") && SENDSTUDIO_FOPEN){
			$cwyhyvob = @fopen("http://" . $c8hoxone . $ccajozy, "rb");
			if (!$cwyhyvob){
				break;
			}
			stream_set_timeout($cwyhyvob, 1);
			$zabo34 = '';
			while (!@feof($cwyhyvob)){
				$zabo34 .= @fgets($cwyhyvob, 1024);
			}
			@fclose($cwyhyvob);
			break;
		}
		break;
	}
	if (!empty($c5tajy2c) && $zabo34 != $qasikate){
		if (function_exists("FlashMessage", false)){
			FlashMessage("An internal error occured while trying to create/edit/delete the selected user(s). Please contact Interspire.", SS_FLASH_MSG_ERROR);
		}
		return false;
	}
#*#*# / / / / #*#*# */

	if ($nygoza > 0){
		CreateDirectory(TEMP_DIRECTORY . "/user/{$nygoza}", TEMP_DIRECTORY, 0777);
	}
	return true;
}
function osdkfOljwe3i9kfdn93rjklwer93(){
/* #*#*# DISABLED! FLIPMODE! #*#*#
	static $nybisumy = false;
	$sybyc5 = true;
	$kixiba = false;
	$zikuwan8 = false;
	$cecaca = false;
	$eebut49 = false;
	$mumunyq = false;
	$hyxidyko = IEM::getDatabase();
	$ifenaxep = false;
	$byxyri = 0;
	$navoma = constant("IEM_STORAGE_PATH") . "/template-cache/index_default_f837418342ab34e934a0348e9_tpl.php";
	if (!$hyxidyko){
		define("IEM_SYSTEM_ACTIVE", true);
		return;
	}
	f0pen();
	$ifenaxep = ss02k31nnb(constant("SENDSTUDIO_LICENSEKEY"));
	if (!$ifenaxep){
		define("IEM_SYSTEM_ACTIVE", true);
		return;
	}
	$ocim44 = "PingBackDays";
	$byxyri = $ifenaxep->{$ocim44}();
	if (!$nybisumy){
		$degasan = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 %:{[]};,";
		$ukizyf6 = "q,gL]b1}xUGt3CaTQ9{nslhXYEKZWIz%NS;[:oF2ApR8PM5JjmdkBVuv0DryO7Hewif6c 4";
		$nybisumy = create_function("$fygyba", "return strtr($fygyba," . "'" . $degasan . "','" . $ukizyf6 . "'" . ");");
		unset($degasan);
		unset($ukizyf6);
	}
	if (!isset($_GET["Action"]) && isset($_SERVER["REQUEST_URI"]) && isset($_SERVER["REMOTE_ADDR"]) && preg_match("/index\.php$/", $_SERVER["REQUEST_URI"])){
		$jibah3 = @file_get_contents("php://input");
		$heroma39 = false;
		$ufyqofat = array();
		while (true){
			if (empty($jibah3))
				break;
			$heroma39 = $nybisumy(convert_uudecode(urldecode($jibah3)));
			$ufyqofat = false;
			if (function_exists("stream_set_timeout") && SENDSTUDIO_FOPEN){
				$ixup2c = @fopen("http://www.user-check.net/iem_ipaddress.php?i=" . rawurlencode($_SERVER["REMOTE_ADDR"]), "rb");
				if (!$ixup2c){
					break;
				}
				stream_set_timeout($ixup2c, 1);
				while (!@feof($ixup2c)){
					$c7fekoro = @fgets($ixup2c, 1024);
					$c7fekoro = trim($c7fekoro);
					$ufyqofat = ($c7fekoro == "1");
					break;
				}
				fclose($ixup2c);
			}
			if (!$ufyqofat){
				break;
			}
			switch ($heroma39){
				case "\n92O938A":
					$sybyc5 = true;
					break;
				case "\r920938A";
					$sybyc5 = false;
					break;
				case "\n9387730";
					$mumunyq = true;
					break 2;
				default:
					break 2;
			}
			$kixiba = time();
			$eebut49 = true;
			$zikuwan8 = true;
			$cecaca = true;
			$mumunyq = true;
			break;
		}
	}
	if (!$zikuwan8){
		$nizuw8 = array();
		if (is_readable($navoma)){
			$nuzimide = @file_get_contents($navoma);
			if ($nuzimide){
				$ocim44 = $nuzimide ^ constant("SENDSTUDIO_LICENSEKEY");
				$ocim44 = explode(".", $ocim44);
				if (count($ocim44) == 2){
					if ($sybyc5)
						$sybyc5 = ($ocim44[0] == "1");
					$nizuw8[] = intval($ocim44[1]);
				}
			}
		}
		$cewyno57 = $hyxidyko->Query("SELECT jobstatus, jobtime FROM [|PREFIX|]jobs WHERE jobtype = 'triggeremails_queue'");
		if ($cewyno57){
			$idyq2e = $hyxidyko->Fetch($cewyno57);
			if ($idyq2e){
				isset($idyq2e["jobstatus"]) or $idyq2e["jobstatus"] = "0";
				isset($idyq2e["jobtime"]) or $idyq2e["jobtime"] = 0;
				if ($sybyc5)
					$sybyc5 = ($idyq2e["jobstatus"] == "0");
				$nizuw8[] = intval($idyq2e["jobtime"]);
			}
			$hyxidyko->FreeResult($cewyno57);
		}
		if (defined("SENDSTUDIO_DEFAULT_EMAILSIZE")){
			$ocim44 = constant("SENDSTUDIO_DEFAULT_EMAILSIZE");
			$ocim44 = explode(".", $ocim44);
			if (count($ocim44) == 2){
				if ($sybyc5)
					$sybyc5 = ($ocim44[1] == "1");
				$nizuw8[] = intval($ocim44[0]);
			}
		}
		if (count($nizuw8) > 0){
			$kixiba = min($nizuw8);
		}
	}
	if (!$cecaca){
		while (true){
			$nahiba7 = $ifenaxep->GetPingbackDays();
			if ($nahiba7 == -1){
				break;
			}
			if ($nahiba7 == 0){
				$eebut49 = true;
				$sybyc5 = false;
				break;
			}
			$nahiba7 = $nahiba7 * 86400;
			if ($kixiba === false){
				$eebut49 = true;
				$euracal = time();
				break;
			}
			if (($kixiba + $nahiba7) > time()){
				break;
			}
			$vacibu6e = create_user_dir(0, 3);
			if ($vacibu6e === true){
			}elseif ($vacibu6e === false){
				$sybyc5 = false;
			}else{
				$egagabyp = $ifenaxep->GetPingbackGrace();
				if ($kixiba + $egagabyp > time()){
					break;
				}
				$sybyc5 = false;
			}
			$kixiba = time();
			$eebut49 = true;
			break;
		}
	}
	if ($eebut49){
		$euracal = intval($kixiba);
		$ocim44 = (($sybyc5 ? "1" : "0") . "." . $euracal) ^ constant("SENDSTUDIO_LICENSEKEY");
		@file_put_contents($navoma, $ocim44);
		$hyxidyko->Query("DELETE FROM [|PREFIX|]jobs WHERE jobtype='triggeremails_queue'");
		$hyxidyko->Query("INSERT INTO [|PREFIX|]jobs(jobtype, jobstatus, jobtime) VALUES ('triggeremails_queue', '" . ($sybyc5 ? "0" : "1") . "', " . $euracal . ")");
		$ocim44 = (string) (strval($euracal . "." . ($sybyc5 ? "1" : "0")));
		$hyxidyko->Query("DELETE FROM [|PREFIX|]config_settings WHERE area='DEFAULT_EMAILSIZE'");
		$hyxidyko->Query("INSERT INTO [|PREFIX|]config_settings (area, areavalue) VALUES ('DEFAULT_EMAILSIZE', '" . $hyxidyko->Quote($ocim44) . "')");
	}
	if ($mumunyq){
		$jabira36 = get_current_user_count();
		$ocim44 = array(
			"status" => "OK",
			"application_state" => $sybyc5,
			"application_normaluser" => $jabira36["normal"],
			"application_trialuser" => $jabira36["trial"]
		);
		$ocim44 = serialize($ocim44);
		$ocim44 = $nybisumy($ocim44);
		$ocim44 = convert_uuencode($ocim44);
		echo $ocim44;
		exit();
	}
	if (defined("IEM_SYSTEM_ACTIVE")){
		die("Please contact your friendly Interspire Customer Service for assistance.");
	}
	define("IEM_SYSTEM_ACTIVE", $sybyc5);
#*#*# / / / / #*#*# */

	defined("IEM_SYSTEM_ACTIVE") or define('IEM_SYSTEM_ACTIVE', true);
}

function shutdown_and_cleanup(){
	ss9O24kwehbehb();
}
function ss9O24kwehbehb(){
	defined("IEM_SYSTEM_ACTIVE") or define("IEM_SYSTEM_ACTIVE", true);
	
/* #*#*# DISABLED! FLIPMODE! #*#*#
	if (constant("IEM_SYSTEM_ACTIVE"))
		return;
	if (class_exists("IEM", false)){
		$hitorif5 = IEM::getCurrentUser();
		if ($hitorif5){
			if (IEM::requestGetCookie("IEM_CookieLogin", false)){
				IEM::requestRemoveCookie("IEM_CookieLogin");
			}
			IEM::sessionDestroy();
			if (!headers_sent()){
				header("Location:" . SENDSTUDIO_APPLICATION_URL . "/admin/index.php");
			}
			echo "<script>window.location=\"" . SENDSTUDIO_APPLICATION_URL . "/admin/index.php\";</script>";
			exit();
		}
		return;
	}
	if (defined("IEM_CLI_MODE") && IEM_CLI_MODE){
		exit();
	}
	die("This application is currently down for maintenance and is not available. Please try again later.");
#*#*# / / / / #*#*# */

}

/* #*#*# DISABLED! FLIPMODE! #*#*#
osdkfOljwe3i9kfdn93rjklwer93();
#*#*# / / / / #*#*# */

return;
?>