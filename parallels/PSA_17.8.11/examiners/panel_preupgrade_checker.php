#!/usr/local/psa/admin/bin/php
<?php
/**
 * Due to the changes in business model in PP10 release, not all previous accounts settings 
 * will be portable from the previous Plesk releases. Some business schemas will be lost for hosters 
 * and their clients. This tool could be launched prior to upgrade for the purpose of getting 
 * a report on potential problems with the upgrade. Based on the report a hoster can decide 
 * whether upgrade to PP10 is suitable for him.
 *
 * Requirements: script supports PHP4/PHP5 in case where installed PHP4 only
 */

define('APP_PATH', dirname(__FILE__));
define('DEBUG', 0); // allow to dump sql logs to output
define('PRE_UPGRADE_SCRIPT_VERSION', '17.8.11.0'); //script version
define('PLESK_VERSION', '17.8.11'); // latest Plesk version
@date_default_timezone_set(@date_default_timezone_get());

if (!defined('PHP_EOL')) {
    define('PHP_EOL', "\n", true);
}

define('LOG_PATH', APP_PATH . '/plesk_preupgrade_checker.log');
define('LOG_JSON', 'plesk_preupgrade_checker.json');

$phpType = php_sapi_name();
if (substr($phpType, 0, 3) == 'cgi') {
    //:INFO: set max execution time 1hr
    @set_time_limit(3600);
}

class Plesk10KnownIssues
{
    function validate()
    {
        if (!PleskInstallation::isInstalled()) {
            //:INFO: Plesk installation is not found. You will have no problems with upgrade, go on and install Plesk 10
            return;
        }

        //:INFO: Validate known OS specific issues with recommendation to avoid bugs in Plesk
        if (Util::isLinux()
            && Util::isVz()
            && Util::getArch() == 'x86_64'
            && PleskOS::isSuse103()
        ) {
            $this->_diagnoseKavOnPlesk10x();
        }

        if (Util::isLinux()) {

            $this->_checkMainIP(); //:INFO: Checking for main IP address https://support.plesk.com/hc/articles/213918705
            $this->_checkMySQLDatabaseUserRoot(); //:INFO: Plesk user "root" for MySQL database servers have not access to phpMyAdmin https://support.plesk.com/hc/articles/115000201714
            $this->_checkPAMConfigsInclusionConsistency();  //:INFO: Check that non-existent PAM services are not referenced by other services, i.e. that PAM configuration array is consistent http://kb.plesk.com/en/112944
            //:INFO: FIXED in 11.0.0 $this->_checkZendExtensionsLoadingOrder(); //:INFO:#100253 Wrong order of loading Zend extensions ionCube declaraion in php.ini can cause to Apache fail http://kb.plesk.com/en/1520
            $this->_checkDumpTmpDvalue(); //:INFO: #101168 If DUMP_TMP_D in /etc/psa/psa.conf contains extra space character at the end of string backup procedure will fails permanently https://support.plesk.com/hc/articles/213407289
            $this->_checkProftpdIPv6(); //:INFO: #94489 FTP service proftpd cannot be started by xinetd if IPv6 is disabled https://support.plesk.com/hc/articles/213397289
            //:INFO: FIXED in 11.0.0 $this->_checkModificationOfCustomizedDNSzones(); //:INFO: # https://support.plesk.com/hc/articles/115000199793
            //:INFO: FIXED in 11.0.0 $this->_checkBindInitScriptPermission(); //:INFO: #105806 If there is no execute permission on named(bind) init file upgrade will fail
            $this->_checkMysqlclient15Release(); //:INFO: #105256 https://support.plesk.com/hc/articles/213375109
            $this->_checkNsRecordInDnsTemplate(); //:INFO: #94544  http://kb.plesk.com/en/113119
            $this->_checkMysqlOdbcConnectorVersion(); //:INFO: #102516 http://kb.plesk.com/en/113620
            $this->_checkSwCollectdIntervalSetting(); //:INFO: #105405 https://support.plesk.com/hc/articles/213362569
            $this->_checkApacheStatus();
            $this->_checkClientPasswordInMyCnf(); //:INFO: #PPPM-1153
            $this->_checkForCryptPasswordsForMailAccounts(); //:INFO: PPPM-1405 https://support.plesk.com/hc/articles/213396449
            $this->_checkImmutableBitOnPleskFiles(); //:INFO: #128414 https://support.plesk.com/hc/articles/115000775209
            $this->_checkAbilityToChownInDumpd(); //:INFO: #138655 https://support.plesk.com/hc/articles/213376909

            if (PleskVersion::is_below_17_0()) {
                $this->_checkShmOpenIssue(); //:INFO: #PPP-10228 https://support.plesk.com/hc/articles/213923185
            }

            if (PleskVersion::is10_2_or_above()) {
                $this->_checkIpcollectionReference(); //:INFO: #72751 https://support.plesk.com/hc/articles/213414469
            }

            if (PleskVersion::is10_3_or_above()) {
                $this->_checkApsApplicationContext(); //:INFO: Broken contexts of the APS applications can lead to errors at building Apache web server configuration  https://support.plesk.com/hc/articles/213952505
                $this->_checkApsTablesInnoDB();
            }

            if (!PleskVersion::is10_4_or_above()) {
                $this->_checkCustomPhpIniOnDomains(); //:INFO: Check for custom php.ini on domains https://support.plesk.com/hc/articles/115001152233
                $this->_checkCustomVhostSkeletonStatisticsSubdir();
                
            }
            
            if (PleskVersion::is10_4_or_above()) {
                $this->_checkCustomWebServerConfigTemplates(); //:INFO: #PPPM-1195 https://support.plesk.com/hc/articles/213399669
            }
            
            if (PleskOS::isDebLike()) {
                $this->_checkSymLinkToOptPsa(); //:INFO: Check that symbolic link /usr/local/psa actually exists on Debian-like OSes https://support.plesk.com/hc/articles/115000198773
            }

            if (PleskOS::isRedHatLike()) {
                $this->_checkMailDriversConflictWithSendmail(); //:INFO: #PPPM-1082 https://support.plesk.com/hc/articles/213922525
            }
            
            if (PleskOS::isCentOS5()) {
                $this->_checkMixedPackagesPhp5Configurators(); //:INFO: #PPPM-1658 http://kb.plesk.com/
            }
            
            if (Util::isVz()) {
                $this->_checkUserBeancounters(); //:INFO: Checking that limits are not exceeded in /proc/user_beancounters http://kb.plesk.com/en/112522
                $this->_checkShmPagesLimit(); //:INFO: PSA service does not start. Unable to allocate shared memory segment. https://support.plesk.com/hc/articles/213946085
                
                if (PleskOS::isRedHatLike()) {
                    $this->_checkMailDriversConflict(); //:INFO: #PPPM-955 https://support.plesk.com/hc/articles/213955065
                }
            }

            if (PleskVersion::is_below_12_1()) {
                $this->_checkDbUsersTableCollation();
            }
        }

        $this->_checkForCryptPasswords();
        //:INFO: FIXED in 11.0.0 $this->_checkForCryptPasswords(); //:INFO: https://support.plesk.com/hc/articles/115001450829
        $this->_checkMysqlServersTable(); //:INFO: Checking existing table mysql.servers
        //:INFO: FIXED in 11.5.30      $this->_checkUserHasSameEmailAsAdmin(); //:INFO: If user has the same address as the admin it should be changed to another https://support.plesk.com/hc/articles/213959005
        //:INFO: FIXED in 11.0.0       $this->_checkDefaultMySQLServerMode(); //:INFO:#66278,70525 Checking SQL mode of default client's MySQL server https://support.plesk.com/hc/articles/213959605
        //:INFO: FIXED in 10.4.4 MU#19 $this->_checkUserHasSameEmailAsEmailAccount(); //:INFO: Users with same e-mails as e-mail accounts will have problems with changing personal contact information https://support.plesk.com/hc/articles/213360389
        $this->_checkPleskTCPPorts(); //:INFO: Check the availability of Plesk TCP ports https://support.plesk.com/hc/articles/213932745
        $this->_checkFreeMemory(); //:INFO: Check for free memory http://kb.plesk.com/en/112522
        //:INFO: FIXED in 11.0.0       $this->_checkCustomDNSrecordsEqualToExistedSubdomains(); //:INFO: Customized DNS records with host equal host of existing subdomain will lost after upgrade to Plesk version above 10.4.4 http://kb.plesk.com/en/113310
        //:INFO: FIXED in 11.0.0       $this->_checkForwardingURL(); //:INFO: Wrong GUI behavior if forwarding URL hasn't "http://" after upgrade to Plesk version above 10.4.4 http://kb.plesk.com/en/113359

        if (PleskVersion::is10_4()) {
            $this->_notificationSubDomainsHaveOwnDNSZoneSince104(); //:INFO: Notification about after upgrade all subdomains will have own DNS zone http://kb.plesk.com/en/112966
        }

        if (PleskVersion::is_below_12_0()) {
            if (Util::isWindows()) {
                $this->_unsupportedClamAvWarningOnUpgradeTo12_0x(); //:INFO: Plesk 11.6.3 version does not support ClamAV Antivirus
                $this->_unsupportedUrchinWarningOnUpgradeTo12_0x(); //:INFO: Plesk 11.6.3 version does not support Urchin web statistics
                $this->_unsupportedMla2000WarningOnUpgradeTo12_0x(); //:INFO: Plesk 11.6.4 version does not support mylittleadmin 2000
            }
        }

        if (PleskVersion::is_below_17_0()) {
            $this->_unsupportedMivaErrorOnUpgradeTo17_0x();
            $this->_checkCbm();
        }

        if (version_compare(PleskVersion::getVersion(), '17.0.11', '=') && Util::isLinux()) {
            $this->_stalePreloadForResCtrl();
        }

        if (Util::isWindows()) {
            $this->_checkPhprcSystemVariable(); //:INFO: #PPPM-294  Checking for PHPRC system variable
            $this->_unknownISAPIfilters(); //:INFO: Checking for unknown ISAPI filters and show warning https://support.plesk.com/hc/articles/213913765
            $this->_checkMSVCR(); //:INFO: Just warning about possible issues related to Microsoft Visual C++ Redistributable Packages https://support.plesk.com/hc/articles/115000201014
            // Probably fixed in PPP-10378 $this->_checkConnectToClientMySQL(); //:INFO: #81461 Checking possibility to connect to client's MySQL server https://support.plesk.com/hc/articles/213920385
            $this->_checkIisFcgiDllVersion(); //:INFO: Check iisfcgi.dll file version https://support.plesk.com/hc/articles/115000201594
            $this->_checkCDONTSmailrootFolder(); //:INFO: After upgrade Plesk change permissions on folder of Collaboration Data Objects (CDO) for NTS (CDONTS) to default, https://support.plesk.com/hc/articles/213914325
            $this->_checkNullClientLogin(); //:INFO: #118963 https://support.plesk.com/hc/articles/213937565
            if (Util::isVz()) {
                $this->_checkDotNetFrameworkIssue(); //:INFO: Check that .net framework installed properly http://kb.plesk.com/en/111448
            }
            if (PleskVersion::is_below_12_0()) {
                $this->_checkSharedApplicationPoolSitesNumberOnUpgradeTo12_0OrHigher(); //:INFO: Check that number of application pools in a pool is less than 500 for Plesk versions below 12.0 https://support.plesk.com/hc/articles/213946045
            }
            if (PleskVersion::is_below_12_1()) {
                $this->_unsupportedMSSQLForPleskInternalDbOnUpgradeTo12_1();
            }
            if (PleskVersion::is_below_17_0()) {
                $this->_unsupportedColdFusionErrorOnUpgradeTo17_0x();
                $this->_unsupportedSharedSslErrorOnUpgradeTo17_0x();
                $this->_unsupportedSmarterMailVersionErrorOnUpgradeTo17_0x();
                $this->_unsupportedAspNetVersionErrorOnUpgradeTo17_0x();
            }
        }
    }

    //:INFO: PSA service does not start. Unable to allocate shared memory segment. https://support.plesk.com/hc/articles/213946085
    function _checkShmPagesLimit()
    {
        $log = Log::getInstance("Checking for limit shmpages...", true);
        $ubc = Util::getUserBeanCounters();
        if ((int)$ubc['shmpages']['limit'] < 40960) {
            $log->emergency("Virtuozzo Container set the \"shmpages\" limit to {$ubc['shmpages']['limit']}, which is too low. This may cause the sw-engine service not to start. To resolve this issue, refer to the article at https://support.plesk.com/hc/articles/213946085");
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #PPPM-294
    function _checkPhprcSystemVariable()
    {
        $log = Log::getInstance("Checking for PHPRC system variable...", true);
        
        $phprc = getenv('PHPRC');

        if ($phprc) {
            $log->emergency('The environment variable PHPRC is present in the system. This variable may lead to upgrade failure. Please delete this variable from the system environment.');
            $log->resultWarning();
            return;
        }
        
        $log->resultOk();
    }
    
    //:INFO: #PPPM-1658 https://support.plesk.com/hc/articles/213380709
    function _checkMixedPackagesPhp5Configurators()
    {
        $log = Log::getInstance("Checking if psa-php53-configurator and psa-php5-configurator packages are installed", true);
        $php53 = PackageManager::isInstalled('psa-php53-configurator');
        $php5 = PackageManager::isInstalled('psa-php5-configurator');
        
        if ($php53 && $php5) {
            $log->emergency('Both psa-php53-configurator and psa-php5-configurator packages are installed. This will cause Plesk upgrade failure. To resolve this issue, refer to the article at https://support.plesk.com/hc/articles/213380709');
            $log->resultWarning();
            return;
        }
        
        $log->resultOk();
    }
    
    //:INFO: #PPP-10228 https://support.plesk.com/hc/articles/213923185
    function _checkShmOpenIssue()
    {
        $log = Log::getInstance("Checking the shm_open issue", true);

        $scriptName = APP_PATH . '/shm_open_issue_test.py';
        $scriptContent = "from multiprocessing import Lock\nlock = Lock()";

        file_put_contents($scriptName, $scriptContent);
        $out=shell_exec("python {$scriptName} 2>&1 1>/dev/null");
        if (stristr($out, 'Function not implemented')) {
            $log->emergency('After upgrade to Plesk 12, access to Plesk will be unavailable. Users might encounter the error "Function not implemented". To resolve this issue, refer to https://support.plesk.com/hc/articles/213923185');
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #138655 https://support.plesk.com/hc/articles/213376909
    function _checkAbilityToChownInDumpd()
    {
        $log = Log::getInstance("Checking the possibility to change the owner of files in the DUMP_D directory", true);
        
        $dump_d = Util::getSettingFromPsaConf('DUMP_D');
        if (is_null($dump_d)) {
            $log->warning('Unable to obtain the path to the directory defined by the DUMP_D parameter. Check that the DUMP_D parameter is set in the /etc/psa/psa.conf file.');
            $log->resultWarning();
            return;
        }
        
        $file = $dump_d . '/pre_upgrade_test_checkAbilityToChownInDumpd';
        
        if (false === file_put_contents($file, 'test')) {
            $log->emergency('Unable to write in the ' . $dump_d . ' directory. The upgrade procedure will fail. Check that the folder exists and you have write permissions for it, and repeat upgrading. ');
            $log->resultWarning();
            return;
        } else {
            $chown_result = @chown($file, 'root');
            $chgrp_result = @chgrp($file, 'root');
            unlink($file);
            if (!$chown_result 
                || !$chgrp_result) {
                $log->emergency('Unable to change the owner of files in the ' . $dump_d . ' directory. The upgrade procedure will fail. Please refer to https://support.plesk.com/hc/articles/213376909 for details.');
                $log->resultError();
                return;
            }
        }
        $log->resultOk();
    }

    //:INFO: #128414 https://support.plesk.com/hc/articles/115000775209
    function _checkImmutableBitOnPleskFiles()
    {
        $log = Log::getInstance("Checking Panel files for the immutable bit attribute");
        
        $cmd = 'lsattr -R /usr/local/psa/ 2>/dev/null |awk \'{split($1, a, ""); if (a[5] == "i") {print;}}\'';
        $output = Util::exec($cmd, $code);
        $files = explode('\n', $output);
        
        if (!empty($output)) {
            $log->info('The immutable bit attribute of the following Panel files can interrupt the upgrade procedure:');
            foreach ($files as $file) {
                $log->info($file);
            }
            $log->emergency('Files with the immutable bit attribute were found. Please check https://support.plesk.com/hc/articles/115000775209 for details.');
            $log->resultWarning();
            return;
        }
        
        $log->resultOk();
    }
    
    //:INFO: #PPPM-1195 https://support.plesk.com/hc/articles/213399669
    function _checkCustomWebServerConfigTemplates()
    {
        $log = Log::getInstance("Checking for custom web server configuration templates");
        $pleskDir = Util::getSettingFromPsaConf('PRODUCT_ROOT_D');
        $customTemplatesPath = $pleskDir . '/admin/conf/templates/custom';
        
        if (is_dir($customTemplatesPath)) {
            $log->warning("There are custom web server configuration templates at ${customTemplatesPath}. These custom templates might be incompatible with a new Plesk version, and this might lead to failure to generate web server configuration files."
                    . "Please check https://support.plesk.com/hc/articles/213399669 for details.");
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }
    
    //:INFO: #PPPM-955 https://support.plesk.com/hc/articles/213955065
    function _checkMailDriversConflict()
    {
        $log = Log::getInstance("Checking for a Plesk mail drivers conflict");
        
        if (((true === PackageManager::isInstalled('psa-mail-pc-driver') || true === PackageManager::isInstalled('plesk-mail-pc-driver')) 
        && true === PackageManager::isInstalled('psa-qmail'))
        || ((true === PackageManager::isInstalled('psa-mail-pc-driver') || true === PackageManager::isInstalled('plesk-mail-pc-driver'))
        && true === PackageManager::isInstalled('psa-qmail-rblsmtpd'))) {
            $log->warning("Plesk upgrade by EZ templates failed if psa-mail-pc-driver and psa-qmail or psa-qmail-rblsmtpd packages are installed. "
                    . "Please check https://support.plesk.com/hc/articles/213955065 for details.");
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }
    
    //:INFO: #PPPM-1082 https://support.plesk.com/hc/articles/213922525
    function _checkMailDriversConflictWithSendmail()
    {
        $log = Log::getInstance("Checking for a Plesk mail drivers conflict with sendmail");
        
        if ((true === PackageManager::isInstalled('plesk-mail-pc-driver') 
        || true === PackageManager::isInstalled('psa-mail-pc-driver'))
        && (true === PackageManager::isInstalled('sendmail-doc') 
        || true === PackageManager::isInstalled('sendmail-cf')
        || true === PackageManager::isInstalled('sendmail'))) {
            $log->warning("Plesk upgrade by EZ templates failed if plesk-mail-pc-driver or psa-mail-pc-driver are installed and installed any of following packages: "
                    . "sendmail-doc, sendmail-cf, sendmail. Please check https://support.plesk.com/hc/articles/213922525 for details.");
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }
    
    
    //:INFO: #PPPM-1153
    function _checkClientPasswordInMyCnf()
    {
        $log = Log::getInstance("Checking for a password in my.cnf files");
        
        $mycnf_files = array('/root/.my.cnf', '/etc/my.cnf', '/etc/mysql/my.cnf', '/var/db/mysql/my.cnf');
        foreach ($mycnf_files as $mycnf) {
            if (is_file($mycnf)) {
                $mycnf_content = Util::readfileToArray($mycnf);
                if ($mycnf_content) {
                    foreach($mycnf_content as $line) {
                        if (preg_match('/^password(\s+)?\=/si', $line, $match)) {
                            $log->emergency("The file $mycnf contains a password for the MySQL console client. Please remove this file temporarily and restore it after the upgrade, otherwise the upgrade will fail.");
                            $log->resultWarning();
                            return;
                        }
                    }
                }
            }
        }
        $log->resultOk();
    }
    
    //:INFO: #118963 https://support.plesk.com/hc/articles/213937565
    function _checkNullClientLogin()
    {
        $log = Log::getInstance("Checking for accounts with empty user names");
        
        $mysql = PleskDb::getInstance();
        $sql = "SELECT domains.id, domains.name, clients.login FROM domains LEFT JOIN clients ON clients.id=domains.cl_id WHERE clients.login is NULL";
        $nullLogins = $mysql->fetchAll($sql);

        if (!empty($nullLogins)) {
            $log->warning('There are accounts with empty user names. This problem can cause the backup or migration operation to fail. Please see https://support.plesk.com/hc/articles/213937565 for the solution.');
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #105405 https://support.plesk.com/hc/articles/213362569
    function _checkSwCollectdIntervalSetting()
    {
        $log = Log::getInstance("Checking the 'Interval' parameter in the sw-collectd configuration file");

        $collectd_config = '/etc/sw-collectd/collectd.conf';
        if (file_exists($collectd_config)) {
            if (!is_file($collectd_config) || !is_readable($collectd_config))
            return;

            $config_content = Util::readfileToArray($collectd_config);
            if ($config_content) {
                foreach ($config_content as $line) {
                    if (preg_match('/Interval\s*\d+$/', $line, $match)) {
                        if (preg_match('/Interval\s*10$/', $line, $match)) {
                            $log->warning('If you leave the default value of the "Interval" parameter in the ' . $collectd_config . ', sw-collectd may heavily load the system. Please see https://support.plesk.com/hc/articles/213362569 for details.');
                            $log->resultWarning();
                            return;
                        }
                        $log->resultOk();
                        return;
                    }
                }
                $log->warning('If you leave the default value of the "Interval" parameter in the ' . $collectd_config . ', sw-collectd may heavily load the system. Please see https://support.plesk.com/hc/articles/213362569 for details.');
                $log->resultWarning();
                return;
            }
        }
    }

    private function _checkApacheStatus()
    {
        $log = Log::getInstance("Checking Apache status");

        $apacheCtl = file_exists('/usr/sbin/apache2ctl') ? '/usr/sbin/apache2ctl' : '/usr/sbin/apachectl';

        if (!is_executable($apacheCtl)) {
            return;
        }

        $resultCode = 0;
        Util::Exec("$apacheCtl -t 2>/dev/null", $resultCode);

        if (0 !== $resultCode) {
            $log->error("The Apache configuration is broken. Run '$apacheCtl -t' to see the detailed info.");
            $log->resultError();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #94544  http://kb.plesk.com/en/113119
    function _checkNsRecordInDnsTemplate()
    {
        $log = Log::getInstance("Checking NS type records in the Panel DNS template");

        $mysql = PleskDb::getInstance();
        $sql = "SELECT 1 FROM dns_recs_t WHERE type='NS'";
        $nsRecord = $mysql->fetchAll($sql);

        if (empty($nsRecord)) {
            $log->warning('There are no NS records in the Panel DNS template. This can break the BIND server configuration. Please see http://kb.plesk.com/en/113119 for the solution.');
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #102516 http://kb.plesk.com/en/113620
    function _checkMysqlOdbcConnectorVersion()
    {
        $log = Log::getInstance("Checking the version of MySQL ODBC package");
        if (PleskOS::isRedHatLike() || PleskOS::isSuseLike()) {
            $package = 'mysql-connector-odbc';
        } else {
            $package = 'libmyodbc';
        }

        $version = Package::getVersion($package);

        if ($version === false) {
            return;
        }

        if (preg_match('/\d+\.\d+\.\d+/', $version, $match) && version_compare($match[0], '3.51.21', '<')) {
            $log->warning('The installed version of ' . $package . ' is outdated. Please see http://kb.plesk.com/en/113620 for details.');
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #72751  https://support.plesk.com/hc/articles/213414469
    function _checkIpcollectionReference()
    {
        $log = Log::getInstance("Checking consistency of the IP addresses list in the Panel database");

        $mysql = PleskDb::getInstance();
        $sql = "SELECT 1 FROM ip_pool, clients, IpAddressesCollections, domains, DomainServices, IP_Addresses WHERE DomainServices.ipCollectionId = IpAddressesCollections.ipCollectionId AND domains.id=DomainServices.dom_id AND clients.id=domains.cl_id AND ipAddressId NOT IN (select id from IP_Addresses) AND IP_Addresses.id = ip_pool.ip_address_id AND pool_id = ip_pool.id GROUP BY pool_id";
        $brokenIps = $mysql->fetchAll($sql);
        $sql = "select 1 from DomainServices, domains, clients, ip_pool where ipCollectionId not in (select IpAddressesCollections.ipCollectionId from IpAddressesCollections) and domains.id=DomainServices.dom_id and clients.id = domains.cl_id and ip_pool.id = clients.pool_id and DomainServices.type='web' group by ipCollectionId";
        $brokenCollections = $mysql->fetchAll($sql);

        if (!empty($brokenIps) || !empty($brokenCollections)) {
            $log->warning('Some database entries related to Panel IP addresses are corrupted. Please see https://support.plesk.com/hc/articles/213414469 for the solution.');
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #105256 https://support.plesk.com/hc/articles/213375109
    function _checkMysqlclient15Release()
    {
        $log = Log::getInstance("Checking the version of the mysqlclient15 package");
        if (PleskOS::isRedHatLike()) {
            $release = Package::getRelease('mysqlclient15');

            if ($release === false) {
                return;
            }

            if (preg_match('/1\.el5\.art/', $release)) {
                $log->emergency('The installed version of mysqlclient15 is outdated. This may lead to upgrade fail. Please see https://support.plesk.com/hc/articles/213375109 for the solution');
                $log->resultWarning();
                return;
            }
        }
        $log->resultOk();
    }

    //:INFO: #105806 If there is no execute permission on named(bind) init file upgrade will fail
    function _checkBindInitScriptPermission()
    {
        $log = Log::getInstance("Checking permissions of the BIND initizalization script");

        $redhat = '/etc/init.d/named';
        $debian = '/etc/init.d/bind9';
        $suse = '/etc/init.d/named';
        $bindInitFile = 'unknown';

        if (PleskOS::isRedHatLike()) {
            $bindInitFile = $redhat;
        }
        if (PleskOS::isDebLike()) {
            $bindInitFile = $debian;
        }
        if (PleskOS::isSuseLike()) {
            $bindInitFile = $suse;
        }

        $perms = Util::exec('ls -l ' . $bindInitFile, $code);

        if (!preg_match('/^.+x.+\s/', $perms)
            && $code === 0) {
            $log->emergency('The ' . $bindInitFile . ' does not have the execute premission. This may lead to upgrade fail. Please see https://support.plesk.com/hc/articles/115000202394 for the solution.');
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #101351 #101690 #103125 #104527 #104528 https://support.plesk.com/hc/articles/115000199793
    function _checkModificationOfCustomizedDNSzones()
    {
        $log = Log::getInstance("Checking for user-modified DNS records that will be changed during the upgrade");

        $mysql = PleskDb::getInstance();
        if (PleskVersion::is10_2_or_above()) {
            $ipv4 = "SELECT dns_zone.id, dns_zone.name, dns_recs.val, ip_address from dns_zone, dns_recs, DomainServices, IpAddressesCollections, IP_Addresses, domains where domains.dns_zone_id = dns_zone.id AND dns_zone.id = dns_recs.dns_zone_id AND dns_recs.type = 'A' AND dns_recs.host = concat(domains.name,'.') AND domains.id = DomainServices.dom_id AND DomainServices.type = 'web' AND DomainServices.ipCollectionId = IpAddressesCollections.ipCollectionId AND IpAddressesCollections.ipAddressId = IP_Addresses.id AND IP_Addresses.ip_address not like '%:%' AND dns_recs.val not like '%:%' AND IP_Addresses.ip_address <> dns_recs.val";
            $ipv6 = "SELECT dns_zone.id, dns_zone.name, dns_recs.val, ip_address from dns_zone, dns_recs, DomainServices, IpAddressesCollections, IP_Addresses, domains where domains.dns_zone_id = dns_zone.id AND dns_zone.id = dns_recs.dns_zone_id AND dns_recs.type = 'A' AND dns_recs.host = concat(domains.name,'.') AND domains.id = DomainServices.dom_id AND DomainServices.type = 'web' AND DomainServices.ipCollectionId = IpAddressesCollections.ipCollectionId AND IpAddressesCollections.ipAddressId = IP_Addresses.id AND IP_Addresses.ip_address like '%:%' AND dns_recs.val like '%:%'  AND IP_Addresses.ip_address <> dns_recs.val";
            $ipv4_zones = $mysql->fetchAll($ipv4);
            $ipv6_zones = $mysql->fetchAll($ipv6);
            $dns_zones = array_merge($ipv4_zones, $ipv6_zones);
        } else {
            $fwd = "SELECT dns_zone.id, dns_zone.name, dns_recs.val, ip_address AS hosts from dns_zone, dns_recs, forwarding, IP_Addresses, domains where domains.dns_zone_id = dns_zone.id AND dns_zone.id = dns_recs.dns_zone_id AND dns_recs.type = 'A' AND dns_recs.host = concat(domains.name,'.') AND domains.id = forwarding.dom_id AND forwarding.ip_address_id = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val";
            $hst = "SELECT dns_zone.id, dns_zone.name, dns_recs.val, ip_address AS hosts from dns_zone, dns_recs, hosting, IP_Addresses, domains where domains.dns_zone_id = dns_zone.id AND dns_zone.id = dns_recs.dns_zone_id AND dns_recs.type = 'A' AND dns_recs.host = concat(domains.name,'.') AND domains.id = hosting.dom_id AND hosting.ip_address_id = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val";
            $fwd_zones = $mysql->fetchAll($fwd);
            $hst_zones = $mysql->fetchAll($hst);
            $dns_zones = array_merge($fwd_zones, $hst_zones);
        }

        $warning = false;
        foreach ($dns_zones as $zone) {
            $subdomains = $mysql->fetchAll('SELECT subdomains.name FROM domains, subdomains WHERE subdomains.dom_id = domains.id AND domains.dns_zone_id=' . $zone['id']);
            if (!empty($subdomains)) {
                $log->info('The existing A and AAAA records in the DNS zone ' . $zone['name'] . ' will be modified or removed after the upgrade.');
                $warning = true;
            }
        }

        if ($warning) {
            $log->warning('Some of the existing A or AAAA records in DNS zones will be modified or removed after the upgrade. Please see https://support.plesk.com/hc/articles/115000199793 and ' . LOG_PATH . ' for details.');
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: Broken contexts of the APS applications can lead to errors at building Apache web server configuration https://support.plesk.com/hc/articles/213952505
    function _checkApsApplicationContext()
    {
        $log = Log::getInstance("Checking installed APS applications");
        $mysql = PleskDb::getInstance();
        $sql = "SELECT * FROM apsContexts WHERE (pleskType = 'hosting' OR pleskType = 'subdomain') AND subscriptionId = 0";
        $brokenContexts = $mysql->fetchAll($sql);

        if (!empty($brokenContexts)) {
            $log->warning('Some database entries realted to the installed APS applications are corrupted. Please see https://support.plesk.com/hc/articles/213952505 for the solution.');
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    //:INFO: #94489 FTP service proftpd cannot be started by xinetd if IPv6 is disabled https://support.plesk.com/hc/articles/213397289
    function _checkProftpdIPv6()
    {
        $log = Log::getInstance("Checking proftpd settings");

        $inet6 = '/proc/net/if_inet6';
        if (!file_exists($inet6) || !@file_get_contents($inet6)) {
            $proftpd_config = '/etc/xinetd.d/ftp_psa';
            if (!is_file($proftpd_config) || !is_readable($proftpd_config))
                return null;

            $config_content = Util::readfileToArray($proftpd_config);
            if ($config_content) {
                for ($i=0; $i<=count($config_content)-1; $i++) {
                    if (preg_match('/flags.+IPv6$/', $config_content[$i], $match)) {
                        $log->warning('The proftpd FTP service will fail to start in case the support for IPv6 is disabled on the server. Please check https://support.plesk.com/hc/articles/213397289 for details.');
                        $log->resultWarning();
                        return;
                    }
                }
            }
        }
        $log->resultOk();
    }

    //:INFO: #101168 If DUMP_TMP_D in /etc/psa/psa.conf contains extra space character at the end of string backup procedure will fails permanently https://support.plesk.com/hc/articles/213407289
    function _checkDumpTmpDvalue()
    {
        $log = Log::getInstance("Checking the /etc/psa/psa.conf file for consistency");

        $file = '/etc/psa/psa.conf';
        if (!is_file($file) || !is_readable($file))
            return null;
        $lines = file($file);
        if ($lines === false)
            return null;
        foreach ($lines as $line) {
            if (preg_match('/^DUMP_TMP_D\s.+\w $/', $line, $match_setting)) {
                $log->warning('The DUMP_TMP_D variable in /etc/psa/psa.conf contains odd characters. This can cause backup tasks to fail on this server. Please check https://support.plesk.com/hc/articles/213407289 for details.');
                $log->resultWarning();
                return;
            }
        }
        $log->resultOk();
    }

    //:INFO: Wrong order of loading Zend extensions ionCube declaraion in php.ini can cause to Apache fail http://kb.plesk.com/en/1520
    function _checkZendExtensionsLoadingOrder()
    {
        $log = Log::getInstance("Checking for the Zend extension declaraion in php.ini");

        $phpini = Util::getPhpIni();
        if ($phpini) {
            foreach ($phpini as $line) {
                if (preg_match('/^\s*zend_extension(_debug)?(_ts)?\s*=/i', $line, $match)) {
                    $log->warning('The server-wide php.ini file contains the declaration of the Zend extension. As a result, the Apache server may fail to start after the upgrade. Please check http://kb.plesk.com/en/1520 for more details.');
                    $log->resultWarning();
                    return;
                }
            }
        }
        $log->resultOk();
    }

    //:INFO: JkWorkersFile directive in Apache configuration can lead to failed Apache configs re-generation during and after upgrade procedure https://support.plesk.com/hc/articles/213920725
    function _checkJkWorkersFileDirective()
    {
        $log = Log::getInstance("Checking the JkWorkersFile directive in the Apache configuration");

        $httpd_include_d = Util::getSettingFromPsaConf('HTTPD_INCLUDE_D') . '/';
        if (empty($httpd_include_d)) {
            $warn = 'Unable to open /etc/psa/psa.conf';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }

        $handle = @opendir($httpd_include_d);
        if (!$handle) {
            $warn = 'Unable to open dir ' . $httpd_include_d;
            $log->warning($warn);
            $log->resultWarning();
            return;
            }

        $configs = array();
        while ( false !== ($file = readdir($handle)) ) {
            if (preg_match('/^\./', $file) || preg_match('/zz0.+/i', $file) || is_dir($httpd_include_d . $file))
            continue;
            $configs[] = $file;
        }

        closedir($handle);
        $warning = false;

        foreach ($configs as $config) {
            $config_content = Util::readfileToArray($httpd_include_d . '/' . $config);
            if ($config_content) {
                for ($i=0; $i<=count($config_content)-1; $i++) {
                    if (preg_match('/^(\s+)?JkWorkersFile.+/', $config_content[$i], $match)) {
                        $log->warning('The Apache configuration file "' . $httpd_include_d . $config . '" contains the "' . $match[0] . '" directive.' );
                        $warning = true;
                    }
                }
            }
        }

        if ($warning) {
            $log->warning('The JkWorkersFile directive may cause problems during the Apache reconfiguration after the upgrade. Please check https://support.plesk.com/hc/articles/213920725 for more details.');
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    //:INFO: Wrong GUI behavior if forwarding URL hasn't "http://" after upgrade to Plesk version above 10.4.4 http://kb.plesk.com/en/113359
    function _checkForwardingURL()
    {
        $log = Log::getInstance("Checking domain URLs");

        $mysql = PleskDb::getInstance();
        $sql = "SELECT htype, redirect FROM domains, forwarding WHERE domains.id=forwarding.dom_id AND forwarding.redirect NOT LIKE 'https://%' AND forwarding.redirect NOT LIKE 'http://%'";
        $domains_with_wrong_url = $mysql->fetchAll($sql);

        if (count($domains_with_wrong_url)>0) {
            $log->warning('There are domains registered in Panel which URL does not have the http:// prefix. Such domains will not be shown on the Domains page. Check http://kb.plesk.com/en/113359 for more details.');
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    //:INFO: Customized DNS records with host equal host of existing subdomain will lost after upgrade to Plesk version above 10.4.4
    function _checkCustomDNSrecordsEqualToExistedSubdomains()
    {
        $log = Log::getInstance("Checking DNS records of subdomains");

        $mysql = PleskDb::getInstance();

        if (PleskVersion::is10_2_or_above()) {
            $sql = "SELECT DISTINCT dns_recs.dns_zone_id, dns_recs.type, host, val, opt FROM dns_recs, subdomains, domains WHERE host = concat(subdomains.name,'.', domains.name,'.') AND dns_recs.dns_zone_id IN ( SELECT dns_recs.dns_zone_id FROM dns_recs, subdomains, domains, hosting, IP_Addresses, DomainServices, IpAddressesCollections WHERE host = concat(subdomains.name,'.', domains.name,'.') AND subdomains.dom_id = domains.id AND domains.id = DomainServices.dom_id AND DomainServices.type = 'web' AND DomainServices.ipCollectionId = IpAddressesCollections.ipCollectionId AND IpAddressesCollections.ipAddressId = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val)";
        } else {
            $sql = "SELECT DISTINCT dns_recs.dns_zone_id, dns_recs.type, host, val, opt FROM dns_recs, subdomains, domains WHERE host = concat(subdomains.name,'.', domains.name,'.') AND dns_recs.dns_zone_id IN ( SELECT dns_recs.dns_zone_id FROM dns_recs, subdomains, domains, hosting, IP_Addresses WHERE host = concat(subdomains.name,'.', domains.name,'.') AND subdomains.dom_id = domains.id AND domains.id = hosting.dom_id AND hosting.ip_address_id = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val)";
        }

        if (Util::isWindows()) {
            $dbprovider = Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME');
            if ($dbprovider <> 'MySQL') {
                $sql = "SELECT DISTINCT dns_recs.dns_zone_id, dns_recs.type, host, val, opt FROM dns_recs, subdomains, domains WHERE host = (subdomains.name + '.' + domains.name + '.') AND dns_recs.dns_zone_id IN ( SELECT dns_recs.dns_zone_id FROM dns_recs, subdomains, domains, hosting, IP_Addresses WHERE host = (subdomains.name + '.' + domains.name + '.') AND subdomains.dom_id = domains.id AND domains.id = hosting.dom_id AND hosting.ip_address_id = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val)";
            }
        }
        $problem_dns_records = $mysql->fetchAll($sql);

        if (count($problem_dns_records)>0) {
            $log->warning('There is a number of DNS records for the subdomains that you manually added to domain DNS zones. If you upgrade to Panel 10.4.4, these records will be lost. Check http://kb.plesk.com/en/113310 for more details.');
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    //:INFO: Check windows authentication for PleskControlPanel web site https://support.plesk.com/hc/articles/213372849
    function _checkWindowsAuthForPleskControlPanel()
    {
        $log = Log::getInstance('Checking the authentication settings of the PleskControlPanel website in IIS');

        $PleskControlPanel = 'PleskControlPanel';
        $cmd = 'wmic.exe /namespace:\\\\root\\MicrosoftIISv2 path IIsWebServerSetting where "ServerComment = \'' . $PleskControlPanel . '\'" get name /VALUE';
        $output = Util::exec($cmd, $code);
        if (preg_match_all('/Name=(.+)/', $output, $siteName)) {
            $cmd = 'wmic.exe /namespace:\\\\root\\MicrosoftIISv2 path IIsWebVirtualDirSetting where "Name = \'' . $siteName[1][0] . '/ROOT\'" get AuthNTLM /VALUE';
            $output = Util::exec($cmd, $code);
            if (preg_match_all('/AuthNTLM=FALSE/', $output, $matches)) {
                $log->warning('Windows authentication for the PleskControlPanel website in IIS is disabled. Check https://support.plesk.com/hc/articles/213372849 for more details.');
                $log->resultWarning();
                return;
            }

        }
        $log->resultOk();
    }

    //:INFO: Plesk 11.6.3 version does not support ClamAV Antivirus
    function _unsupportedClamAvWarningOnUpgradeTo12_0x()
    {
        $log = Log::getInstance('Obtaining information about the installed antivirus');
        $antivirus = PleskComponent::CurrentWinAntivirus();
        if ( $antivirus == 'clamav') {
            $warn = 'Starting from Plesk 12.0, the ClamAV antivirus is no longer supported.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }

    //:INFO: Plesk 11.6.4 version does not support mylittleadmin 2000
    function _unsupportedMla2000WarningOnUpgradeTo12_0x()
    {
        $log = Log::getInstance('Obtaining information about the installed administration tool for SQL Server');
        $mssqladmin = PleskComponent::CurrentWinMssqlWebAdmin();
        if ( $mssqladmin == 'mylittleadmin') {
            $warn = 'Starting from Plesk 12.0, MyLittleAdmin 2000 is no longer supported.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }

    //:INFO: Plesk 11.6.3 version does not support Urchin web statistics
    function _unsupportedUrchinWarningOnUpgradeTo12_0x()
    {
        $log = Log::getInstance('Detecting if Urchin is installed');
        $urchin = Util::regQuery('\PLESK\PSA Config\Config\Packages\stats.urchin', '', true);
        if ($urchin == 'urchin'){
            $log->info('Urchin is installed');

            $pleskDb = PleskDb::getInstance();
            $sql = "SELECT * FROM tmpldata WHERE element = 'webstat' AND value = 'urchin'";
            $tmplrow = $pleskDb->fetchRow($sql);
            $sql = "SELECT * FROM hosting WHERE webstat = 'urchin'";
            $hostrow = $pleskDb->fetchRow($sql);

            if (!empty($tmplrow) || !empty($hostrow)){
                $log->info('Urchin is used');

                $warn = 'Starting from Plesk 12.0, Urchin is no longer supported.';
                $log->warning($warn);
                $log->resultWarning();
                return;
            } else
                $log->info('Urchin is not used');

        } else
            $log->info('Urchin is not installed');
        $log->resultOk();
    }

    function _checkSharedApplicationPoolSitesNumberOnUpgradeTo12_0OrHigher()
    {
        $log = Log::getInstance('Detecting if IIS Shared Application Pools do not contain more than 500 sites...');

        $db = PleskDb::getInstance();
        if (PleskVersion::is_below_11_1()) {
            $sql =
                "SELECT COUNT(*) AS cnt FROM " .
                "((" .
                "  SELECT COUNT(*) AS poolSitesCount" .
                "  FROM clients c" .
                "    JOIN cl_param cp ON c.id = cp.cl_id" .
                "    LEFT JOIN domains d ON c.id = d.cl_id" .
                "  WHERE cp.param = 'iis_app_pool' AND cp.val = 'true'" .
                "  GROUP BY c.id" .
                ") UNION (" .
                "  SELECT COUNT(*) AS poolSitesCount" .
                "  FROM domains" .
                "  WHERE webspace_id = 0 AND htype <> 'none' AND id NOT IN (" .
                "    SELECT d.id" .
                "    FROM domains d" .
                "      JOIN hosting h ON d.id = h.dom_id" .
                "      JOIN sys_users su ON su.id = h.sys_user_id" .
                "      JOIN dom_param dp ON d.id = dp.dom_id" .
                "    WHERE d.webspace_id = 0 AND dp.param = 'iis_app_pool' AND dp.val = 'true'" .
                ") AND id NOT IN (" .
                "  SELECT d.id" .
                "  FROM clients c" .
                "    JOIN cl_param cp ON c.id = cp.cl_id" .
                "    LEFT JOIN domains d ON c.id = d.cl_id" .
                "  WHERE cp.param = 'iis_app_pool' AND cp.val = 'true')" .
                ")) PSC " .
                "WHERE PSC.poolSitesCount > 500";
        } else {
            $sql = "SELECT COUNT(*) AS cnt FROM (SELECT COUNT(*) AS poolSitesCount FROM iisapppooldomains GROUP BY poolid) PSC WHERE PSC.poolSitesCount > 500";
        }
        $r = $db->fetchAll($sql);

        if ($r[0]['cnt'] != '0') {
            $warn = 'There are ' . $r[0]['cnt'] . ' IIS Shared Application Pools with more than 500 sites. These sites might stop working after the upgrade. Please refer to https://support.plesk.com/hc/articles/213946045 for the instructions about how to reduce number of sites per IIS Shared Application pool.';

            $log->warning($warn);
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    function _unsupportedMSSQLForPleskInternalDbOnUpgradeTo12_1()
    {
        $log = Log::getInstance('Detecting if Microsoft SQL Server is used for the Plesk\'s internal database...');
        $pleskDbType = Util::getPleskDbType();
        if ($pleskDbType == 'mssql') {
            $msg = "The Plesk's internal database has been running on Microsoft SQL Server. Please switch to MySQL server. For instructions"
                . " see https://docs.plesk.com/current/redirect.html?book=advanced-administration-guide-win&page=69028.htm"
                . "\nOtherwise, the upgrade will fail.";
            $log->emergency($msg);
            $log->resultError();
        } else {
            $log->resultOk();
        }
    }

    function _unsupportedColdFusionErrorOnUpgradeTo17_0x()
    {
        $log = Log::getInstance('Detecting if ColdFusion is installed');
        $coldfusion = Util::regQuery('\PLESK\PSA Config\Config\Packages\coldfusion', '', true);
        if (in_array($coldfusion, array('coldfusion8', 'coldfusion9', 'coldfusion10'))){
            $log->info('ColdFusion is installed');

            $pleskDb = PleskDb::getInstance();
            $sql = "SELECT * FROM hosting WHERE coldfusion = 'true'";
            $hostrow = $pleskDb->fetchRow($sql);

            if (!empty($hostrow)){
                $log->info('ColdFusion is used');

                $err = 'Starting from Plesk 12.5, ColdFusion is no longer supported.' . PHP_EOL .
                    'Please set the Adobe ColdFusion component to "None" in Tools & Settings > Server Components > ColdFusion.';
                $log->emergency($err);
                $log->resultError();
                return;
            } else {
                $log->info('ColdFusion is not used');
            }

        } else {
            $log->info('ColdFusion is not installed');
        }
        $log->resultOk();
    }

    function _unsupportedMivaErrorOnUpgradeTo17_0x()
    {
        $log = Log::getInstance('Detecting if Miva is installed');

        if (Util::isWindows()) {
            $installed = Util::regQuery('\\Miva\\Miva Empresa NT\\Configuration', '', true);
        } else {
            $installed = PackageManager::isInstalled('psa-miva');
        }

        if ($installed) {
            $log->info('Miva is installed');

            $pleskDb = PleskDb::getInstance();
            $sql = "SELECT * FROM hosting WHERE miva = 'true'";
            $hostrow = $pleskDb->fetchRow($sql);

            if (!empty($hostrow)) {
                $log->info('Miva is used');
                $log->warning('Starting from Plesk 17.0, Miva is no longer supported, and sites could be down.');
                $log->resultWarning();
                return;
            } else {
                $log->info('Miva is not used');
            }

        } else {
            $log->info('Miva is not installed');
        }

        $log->resultOk();
    }

    //:INFO: Stale /etc/ld.so.preload for resctrl: https://kb.plesk.com/en/129060
    function _stalePreloadForResCtrl()
    {
        $log = Log::getInstance('Detecting presense of /etc/ld.so.preload with libplesk_resctrl.so');
        $file = @file_get_contents("/etc/ld.so.preload");
        if (strpos($file, "libplesk_resctrl.so") === false) {
            $log->info('Plesk Resource controller is not enabled');
            $log->resultOk();
        } else {
            $log->warning('Plesk resource controller is enabled in /etc/ld.so.preload. Check https://kb.plesk.com/en/129060 for more details.');
            $log->resultWarning();
        }
    }

    function _unsupportedSharedSslErrorOnUpgradeTo17_0x()
    {
        $log = Log::getInstance('Detecting if Shared SSL is used');

        $pleskDb = PleskDb::getInstance();
        $sql = "SELECT * FROM shared_ssl_links";
        $links = $pleskDb->fetchRow($sql);

        if (!empty($links)) {
            $log->info('Shared SSL is used');
            $log->warning('Starting from Plesk 17.0, Shared SSL is no longer supported.');
            $log->resultWarning();
            return;
        } else {
            $log->info('Shared SSL is not used');
        }

        $log->resultOk();
    }

    function _unsupportedSmarterMailVersionErrorOnUpgradeTo17_0x()
    {
        $log = Log::getInstance('Detecting SmarterMail version');

        $currentMailServer = Util::regQuery('\PLESK\PSA Config\Config\Packages\mailserver', '/ve', true);
        if ($currentMailServer == 'smartermail') {
            $version = Util::regQuery('\PLESK\PSA Config\Config\Packages\mailserver\smartermail', '/v version', true);
            if (!empty($version) && version_compare($version, '10.0.0', '<')) {
                $err = "Starting from Plesk 17.0, SmarterMail {$version} is no longer supported." . PHP_EOL .
                    'Please upgrade SmarterMail to 10.0 or higher.';
                $log->warning($err);
                $log->resultWarning();
                return;
            }
        }

        $log->resultOk();
    }

    function _unsupportedAspNetVersionErrorOnUpgradeTo17_0x()
    {
        $log = Log::getInstance('Detecting if ASP.NET 1.1 is installed');

        $comp = new PleskComponent();
        if ($comp->CurrentWinAspNet11() == 'asp.net') {
            $log->info('ASP.NET 1.1 is installed');

            $pleskDb = PleskDb::getInstance();
            $sql = "SELECT * FROM hosting WHERE asp_dot_net = 'true' AND managed_runtime_version = '1.1'";
            $rows = $pleskDb->fetchRow($sql);
            if (!empty($rows)) {
                $log->info('ASP.NET 1.1 is used');
                $log->warning('Starting from Plesk 17.0, ASP.NET 1.1 is no longer supported.');
                $log->resultWarning();
                return;
            } else {
                $log->info('ASP.NET 1.1 is not used');
            }
        } else {
            $log->info('ASP.NET 1.1 is not installed');
        }

        $log->resultOk();
    }

    //:INFO: Notification about after upgrade all subdomains will have own DNS zone http://kb.plesk.com/en/112966
    function _notificationSubDomainsHaveOwnDNSZoneSince104()
    {
        $log = Log::getInstance('Checking for subdomains...');

        $mysql = PleskDb::getInstance();
        $sql = "select val from misc where param='subdomain_own_zones'";
        $subdomain_own_zones = $mysql->fetchOne($sql);

        if ($subdomain_own_zones == "true") {
            $log->warning('Since Panel 10.4, all subdomains have their own DNS zone. Check http://kb.plesk.com/en/112966 for more details.');
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    private function _checkCbm()
    {
        $log = Log::getInstance('Checking for Customer & Business Manager');

        $mysql = PleskDb::getInstance();
        $sql = PleskVersion::is_below_12_0()
            ? "select val from cl_param where param='ppb-url'"
            : "select value from ServiceNodeConfiguration where section='Billing' and name='ppb-url'";
        $url = $mysql->fetchOne($sql);

        if (!empty($url)) {
            $sql = "select count(*) from smb_users where externalId != ''";
            $totalBillingUsers = $mysql->fetchAll($sql);

            if (count($totalBillingUsers) > 0) {
                $log->emergency('Customer & Business Manager is no longer supported. Please refer to https://support.plesk.com/hc/articles/213392009 for details.');
                $log->resultError();
                return;
            }
        }

        $log->resultOk();
    }

    //:INFO: Checking that limits are not exceeded in /proc/user_beancounters
    function _checkUserBeancounters()
    {
        $log = Log::getInstance("Checking that limits are not exceeded in /proc/user_beancounters");

        $warning = false;
        $user_beancounters = Util::readfileToArray('/proc/user_beancounters');
        if ($user_beancounters) {
            for ($i=2; $i<=count($user_beancounters)-1; $i++) {
                if (preg_match('/\d{1,}$/', $user_beancounters[$i], $match)
                && $match[0]>0) {
                    if (preg_match('/^.+?:?.+?\b(\w+)\b/', $user_beancounters[$i], $limit_name)) {
                        $log->warning('Virtuozzo Container limit "' . trim($limit_name[1]) . '" was exceeded ' . $match[0] . ' times.');
                    }
                    $warning = true;
                }
            }
        }

        if ($warning) {
            $log->warning('Limits set by Virtuozzo Container are exceeded. Please, check http://kb.plesk.com/en/112522 for more details.');
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: Checking for available free memory for the upgrade procedure http://kb.plesk.com/en/112522
    function _checkFreeMemory()
    {
        $log = Log::getInstance('Checking for available free memory for the upgrade procedure');

        $freeMem = Util::GetFreeSystemMemory();
        if (!empty($freeMem)
            && $freeMem < 200000) {
            $log->warning('Not enough memory to perform the upgrade: You should have at least 200 megabytes free. The current amount of free memory is: ' . $freeMem . ' Kb');
            $log->resultWarning();
        }
        $log->resultOk();
    }

    //:INFO: Check the availability of Plesk Panel TCP ports
    function _checkPleskTCPPorts()
    {
        $log = Log::getInstance('Checking the availability of Plesk Panel TCP ports');

        $plesk_ports = array('8880' => 'Plesk Panel non-secure HTTP port', '8443' => 'Plesk Panel secure HTTPS port');

        $mysql = PleskDb::getInstance();
        $sql = "select ip_address from IP_Addresses";
        $ip_addresses = $mysql->fetchAll($sql);
        $warning = false;
        if (count($ip_addresses)>0) {
            if (Util::isLinux()) {
                $ipv4 = Util::getIPv4ListOnLinux();
                $ipv6 = Util::getIPv6ListOnLinux();
                if ($ipv6) {
                    $ipsInSystem = array_merge($ipv4, $ipv6);
                } else {
                    $ipsInSystem = $ipv4;
                }
            } else {
                $ipsInSystem = Util::getIPListOnWindows();
            }
            foreach ($ip_addresses as $ip) {
                foreach ($plesk_ports as $port => $description) {
                    if (PleskValidator::validateIPv4($ip['ip_address']) && in_array($ip['ip_address'], $ipsInSystem)) {
                        $fp = @fsockopen($ip['ip_address'], $port, $errno, $errstr, 1);
                    } elseif (PleskValidator::validateIPv6($ip['ip_address']) && in_array($ip['ip_address'], $ipsInSystem)) {
                        $fp = @fsockopen('[' . $ip['ip_address'] . ']', $port, $errno, $errstr, 1);
                    } else {
                        $log->warning('IP address registered in Plesk is invalid or broken: ' . $ip['ip_address']);
                        $log->resultWarning();
                        return;
                    }
                    if (!$fp) {
                        // $errno 110 means "timed out", 111 means "refused"
                        $log->info('Unable to connect to IP address ' . $ip['ip_address'] . ' on ' . $description . ' ' . $port . ': ' . $errstr);
                        $warning = true;
                    }
                }
            }
        }
        if ($warning) {
            $log->warning('Unable to connect to some Plesk ports. Please see ' . LOG_PATH . ' for details. Find the full list of the required open ports at https://support.plesk.com/hc/articles/213932745 ');
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    function _getPAMServiceIncludes($serviceFile)
    {
        // Get array of PAM services that are included from a given PAM configuration file.
        $lines = file($serviceFile);
        $includes = array();

        foreach ($lines as $line) {
            // Note: we do not support here line continuations and syntax variants for old unsupported systems.
            $line = trim( preg_replace('/#.*$/', '', $line) );
            if (empty($line))
                continue;

            // See PAM installation script source for info on possible syntax variants.
            $tokens = preg_split('/\s+/', $line);
            $ref = null;
            if ($tokens[0] == '@include') {
                $ref = $tokens[1];
            } elseif ($tokens[1] == 'include' || $tokens[1] == 'substack') {
                $ref = $tokens[2];
            }

            if (!empty($ref)) {
                $includes[] = $ref;
            }
        }

        return $includes;
    }

    //:INFO: Check that non-existent PAM services are not referenced by other services, i.e. that PAM configuration array is consistent http://kb.plesk.com/en/112944
    function _checkPAMConfigsInclusionConsistency()
    {
        $log = Log::getInstance('Checking PAM configuration array consistency');

        $pamDir = "/etc/pam.d/";
        $handle = @opendir($pamDir);
        if (!$handle) {
            $warn = 'Unable to open the PAM configuration directory "' . $pamDir . '". Check http://kb.plesk.com/en/112944 for more details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }

        $services = array();
        while ( false !== ($file = readdir($handle)) ) {
            if (preg_match('/^\./', $file) || preg_match('/readme/i', $file) || is_dir($pamDir . $file))
                continue;
            $services[] = $file;
        }

        closedir($handle);

        $allIncludes = array();
        foreach ($services as $service) {
            $includes = $this->_getPamServiceIncludes($pamDir . $service);
            $allIncludes = array_unique(array_merge($allIncludes, $includes));
        }

        $missingIncludes = array_diff($allIncludes, $services);

        if (!empty($missingIncludes)) {
            $warn  = 'The PAM configuration is in inconsistent state. ';
            $warn .= 'If you proceed with the installation, the required PAM modules will not be installed. This will cause problems during the authentication. ';
            $warn .= 'Some PAM services reference the following nonexistent services: ' . implode(', ', $missingIncludes) . '. ';
            $warn .= 'Check http://kb.plesk.com/en/112944 for more details.';

            $log->warning($warn);
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: Plesk user "root" for MySQL database servers have not access to phpMyAdmin https://support.plesk.com/hc/articles/115000201714
    function _checkMySQLDatabaseUserRoot()
    {
        $log = Log::getInstance('Checking existence of Plesk user "root" for MySQL database servers');

        $psaroot = Util::getSettingFromPsaConf('PRODUCT_ROOT_D');
        $phpMyAdminConfFile = $psaroot . '/admin/htdocs/domains/databases/phpMyAdmin/libraries/config.default.php';
        if (file_exists($phpMyAdminConfFile)) {
            $phpMyAdminConfFileContent = file_get_contents($phpMyAdminConfFile);
            if (!preg_match("/\[\'AllowRoot\'\]\s*=\s*true\s*\;/", $phpMyAdminConfFileContent)) {
                $mysql = PleskDb::getInstance();
                $sql = "select login, data_bases.name as db_name, displayName as domain_name from db_users, data_bases, domains where db_users.db_id = data_bases.id and data_bases.dom_id = domains.id and data_bases.type = 'mysql' and login = 'root'";
                $dbusers = $mysql->fetchAll($sql);

                foreach ($dbusers as $user) {
                    $log->warning('The database user "' . $user['login'] . '"  (database "' . $user['db_name'] . '" at "' . $user['domain_name'] . '") has no access to phpMyAdmin. Please check https://support.plesk.com/hc/articles/115000201714 for more details.');
                    $log->resultWarning();
                    return;
                }
            }
        }

        $log->resultOk();
    }

    //:INFO: After upgrade Plesk change permissions on folder of Collaboration Data Objects (CDO) for NTS (CDONTS) to default, https://support.plesk.com/hc/articles/213914325
    function _checkCDONTSmailrootFolder()
    {
        $log = Log::getInstance('Checking for CDONTS mailroot folder');

        $mailroot = Util::getSystemDisk() . 'inetpub\mailroot\pickup';

        if (is_dir($mailroot)) {
            $log->warning('After upgrade you have to add write pemissions to psacln group on folder ' . $mailroot . '. Please, check https://support.plesk.com/hc/articles/213914325 for more details.');
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    //:INFO: Check iisfcgi.dll file version https://support.plesk.com/hc/articles/115000201594
    function _checkIisFcgiDllVersion()
    {
        $log = Log::getInstance("Checking the iisfcgi.dll file version");

        $windir = Util::getSystemRoot();
        $iisfcgi = $windir . '\system32\inetsrv\iisfcgi.dll';
        if (file_exists($iisfcgi)) {
            $version = Util::getFileVersion($iisfcgi);
            if (version_compare($version, '7.5.0', '>')
                && version_compare($version, '7.5.7600.16632', '<')) {
                $log->warning('File iisfcgi.dll version ' . $version . ' is outdated. Please, check article https://support.plesk.com/hc/articles/115000201594 for details');
                return;
            }
        }
        $log->resultOk();
    }

    //:INFO: Users with same e-mails as e-mail accounts will have problems with changing personal contact information https://support.plesk.com/hc/articles/213360389
    function _checkUserHasSameEmailAsEmailAccount()
    {
        $log = Log::getInstance("Checking for users with same e-mail address as e-mail account");

        $mysql = PleskDb::getInstance();
        $sql = "select login, email from clients where email in (select concat(m.mail_name, '@', d.displayName) from domains d, mail m, Permissions p where m.perm_id=p.id and (p.permission='cp_access' and value='true'))";
        if (Util::isWindows()) {
            $dbprovider = Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME');
            if ($dbprovider <> 'MySQL') {
                $sql = "select login, email from clients where email in (select (m.mail_name + '@' + d.displayName) from domains d, mail m, Permissions p where m.perm_id=p.id and (p.permission='cp_access' and value='true'))";
            }
        }

        $problem_clients = $mysql->fetchAll($sql);

        if (PleskVersion::is8x()
            || PleskVersion::is9x()) {
            $sql = "select d.name domain_name, c.email domain_admin_email from domains d, dom_level_usrs dl, Cards c where c.id=dl.card_id and dl.dom_id=d.id and c.email in (select concat(m.mail_name, '@', d.displayName) from domains d, mail m, Permissions p where m.perm_id=p.id and (p.permission='cp_access' and value='true'))";
            if (Util::isWindows()) {
                $dbprovider = Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME');
                if ($dbprovider <> 'MySQL') {
                    $sql = "select d.name as domain_name, c.email as domain_admin_email from domains d, dom_level_usrs dl, Cards c where c.id=dl.card_id and dl.dom_id=d.id and c.email in (select (m.mail_name + '@' + d.displayName) from domains d, mail m, Permissions p where d.id=m.dom_id and m.perm_id=p.id and (p.permission='cp_access' and value='true'))";
                }
            }
        }
        $problem_domain_admins = $mysql->fetchAll($sql);

        if (count($problem_clients)>0
            || count($problem_domain_admins)>0) {
            foreach ($problem_clients as $client) {
                $info = 'User ' . $client['login'] . ' has contact mail address as e-mail account ' . $client['email'];
                $log->info($info);
            }
            foreach ($problem_domain_admins as $domain_admin) {
                $info = 'Domain administrator of domain ' . $domain_admin['domain_name'] . ' has contact mail address as e-mail account ' . $domain_admin['domain_admin_email'];
                $log->info($info);
            }
            $log->warning('There are some users found with email matches mailboxes with permission to access Customer Panel. See the ' . LOG_PATH . ' for details.  If a client\'s or domain administrator\'s e-mail address (in the profile) matches a mailbox in Plesk and the mailbox has the permission to access Customer Panel, the upgrade procedure will create two auxiliarily user accounts (with the same e-mail) for such customers and Panel will not allow to change personal information (including passwords) for them. Please, check https://support.plesk.com/hc/articles/213360389 for more details.');

            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: Checking for main IP address https://support.plesk.com/hc/articles/213918705
    function _checkMainIP()
    {
        $log = Log::getInstance("Checking for main IP address");

        $mysql = PleskDb::getInstance();
        $sql = 'select * from IP_Addresses';
        $ips = $mysql->fetchAll($sql);
        $mainexists = false;
        foreach ($ips as $ip) {
            if (isset($ip['main'])) {
                if ($ip['main'] == 'true') {
                    $mainexists = true;
                }
            } else {
                $log->info('No field "main" in table IP_Addresses.');
                $log->resultOk();
                return;
            }
        }

        if (!$mainexists) {
            $warn = 'Unable to find "main" IP address in psa database. Please, check https://support.plesk.com/hc/articles/213918705 for more details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    //:INFO:#66278,70525 Checking SQL mode of default client's MySQL server https://support.plesk.com/hc/articles/213959605
    function _checkDefaultMySQLServerMode()
    {
        $log = Log::getInstance("Checking SQL mode of default client's MySQL server");

        $credentials = Util::getDefaultClientMySQLServerCredentials();
        if (!empty($credentials)) {
            $mysql = new DbClientMysql($credentials['host'], $credentials['admin_login'], $credentials['admin_password'] , 'mysql', $credentials['port']);
            if (!$mysql->hasErrors()) {
                $sql = 'SELECT @@sql_mode';
                $sqlmode = $mysql->fetchOne($sql);
                if (preg_match("/STRICT_/i", $sqlmode, $match)) {
                    $warn = 'Please, switch off strict mode for MySQL server. Read carefully article https://support.plesk.com/hc/articles/213959605 for details.';
                    $log->warning($warn);
                    $log->resultWarning();
                    return;
                }
            }
        }

        $log->resultOk();
    }

    //:INFO: If user has the same address as the admin it should be changed to another https://support.plesk.com/hc/articles/213959005
    function _checkUserHasSameEmailAsAdmin()
    {
        $log = Log::getInstance('Checking for users with the same e-mail address as the administrator');

        $adminEmail = Plesk10BusinessModel::_getAdminEmail();
        if (!empty($adminEmail)) {
            $db = PleskDb::getInstance();
            if (PleskVersion::is10x_or_above()) {
                $sql = "SELECT login, email FROM smb_users WHERE login<>'admin' and email='" . $adminEmail . "'";
                $clients = $db->fetchAll($sql);
            } else {
                $sql = "SELECT login, email FROM clients WHERE login<>'admin' and email='" . $adminEmail . "'";
                $clients = $db->fetchAll($sql);
            }
            if (!empty($clients)) {
                foreach ($clients as $client) {
                    $log->info('The customer with the username ' . $client['login'] . ' has the same e-mail address as the Panel administrator: ' .  $client["email"]);
                }
                $log->warning('Some customers have e-mail addresses coinciding with the Panel administrator\'s e-mail address. Please see the ' . LOG_PATH . ' and check https://support.plesk.com/hc/articles/213959005 for details.');
                $log->resultWarning();
                return;
            }
        }
        $log->resultOk();
    }

    //:INFO: Check that .net framework installed properly http://kb.plesk.com/en/111448
    function _checkDotNetFrameworkIssue()
    {
        $log = Log::getInstance('Checking that .NET framework installed properly');

        $pleskCpProvider = Util::regPleskQuery('PLESKCP_PROVIDER_NAME', true);
        if ($pleskCpProvider == 'iis') {
            if (PleskVersion::is17x_or_above()) {
                $cmd = '"' . Util::regPleskQuery('PRODUCT_ROOT_D', true) . 'admin\bin64\websrvmng" --list-wdirs --vhost-name=pleskcontrolpanel';
            } else {
                $cmd = '"' . Util::regPleskQuery('PRODUCT_ROOT_D', true) . 'admin\bin\websrvmng" --list-wdirs --vhost-name=pleskcontrolpanel';
            }
            $output = Util::exec($cmd, $code);
            if (!preg_match("/wdirs/i", trim($output), $matches)) {
                $log->warning('There is a problem with .NET framework.  Please, check http://kb.plesk.com/en/111448 for details.');
                $log->resultWarning();
                return;
            }
        }
        $log->resultOk();

    }

    //:INFO: Check for custom php.ini on domains https://support.plesk.com/hc/articles/115001152233
    function _checkCustomPhpIniOnDomains()
    {
        $log = Log::getInstance('Checking for custom php.ini on domains');

        $domains = Plesk10BusinessModel::_getDomainsByHostingType('vrt_hst');
        if (empty($domains)) {
            $log->resultOk();
            return;
        }
        $vhost = Util::getSettingFromPsaConf('HTTPD_VHOSTS_D');
        if (empty($vhost)) {
            $warn = 'Unable to read /etc/psa/psa.conf';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $flag = false;
        foreach ($domains as $domain) {
            $filename = $vhost . '/' . $domain['name'] . '/conf/php.ini';
            if (file_exists($filename)) {
                $warn = 'Custom php.ini is used for domain ' . $domain['name'] . '.';
                $log->warning($warn);
                $flag = true;
            }
        }

        if ($flag) {
            $warn = 'After upgrade, Panel will not apply changes to certain website-level PHP settings due to they are predefined in /var/www/vhosts/DOMAINNAME/conf/php.ini. Please check https://support.plesk.com/hc/articles/115001152233 for details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }

        $log->resultOk();
    }

    //:INFO: Checking existing table mysql.servers https://support.plesk.com/hc/articles/213385169
    function _checkMysqlServersTable()
    {
        $log = Log::getInstance('Checking table "servers" in database "mysql"');

        $mySQLServerVersion = Util::getMySQLServerVersion();
        if (version_compare($mySQLServerVersion, '5.1.0', '>=')) {
            $credentials = Util::getDefaultClientMySQLServerCredentials();

            if (preg_match('/AES-128-CBC/', $credentials['admin_password'])) {
                $log->info('The administrator\'s password for the default MySQL server is encrypted.');
                return;
            }

            $mysql = new DbClientMysql($credentials['host'], $credentials['admin_login'], $credentials['admin_password'] , 'information_schema', $credentials['port']);
            if (!$mysql->hasErrors()) {
                $sql = 'SELECT * FROM information_schema.TABLES  WHERE TABLE_SCHEMA="mysql" and TABLE_NAME="servers"';
                $servers = $mysql->fetchAll($sql);
                if (empty($servers)) {
                    $warn = 'The table "servers" in the database "mysql" does not exist. Please check  https://support.plesk.com/hc/articles/213385169 for details.';
                    $log->warning($warn);
                    $log->resultWarning();
                    return;
                }
            }
        }
        $log->resultOk();
    }

    //:INFO: Check that there is symbolic link /usr/local/psa on /opt/psa on Debian-like Oses https://support.plesk.com/hc/articles/115000198773
    function _checkSymLinkToOptPsa()
    {
        $log = Log::getInstance('Checking symbolic link /usr/local/psa on /opt/psa');

        $link = @realpath('/usr/local/psa/version');
        if (!preg_match('/\/opt\/psa\/version/', $link, $macthes)) {
            $warn = "The symbolic link /usr/local/psa does not exist or has wrong destination. Read article https://support.plesk.com/hc/articles/115000198773 to fix the issue.";
            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    //:INFO: 81461 PPP-10378 Checking possibility to connect to client's MySQL server
    function _checkConnectToClientMySQL()
    {
        $log = Log::getInstance('Checking connection to client MySQL server');

        $credentials = Util::getDefaultClientMySQLServerCredentials();

        if ($credentials == NULL) {
            $installedMySQLserver55 = Util::regQuery('\MySQL AB\MySQL Server 5.5', '/v version', true);
            $installedMySQLserver51 = Util::regQuery('\MySQL AB\MySQL Server 5.1', '/v version', true);
            $installedMySQLserver50 = Util::regQuery('\MySQL AB\MySQL Server 5.0', '/v version', true);

            if ($installedMySQLserver55
                || $installedMySQLserver51
                || $installedMySQLserver50
            ) {
                $warn = 'Default MySQL server is not registered in Plesk Onyx. If you use custom MySQL instances you should register one at"
                    . " least according to article https://support.plesk.com/hc/articles/213920385.';
                $log->warning($warn);
                $log->resultWarning();
                return;
            }
        }

        if (preg_match('/AES-128-CBC/', $credentials['admin_password'])) {
            $log->info('The administrator\'s password for the default MySQL server is encrypted.');
            return;
        }

        $mysql = new DbClientMysql($credentials['host'], $credentials['admin_login'], $credentials['admin_password'] , 'mysql', $credentials['port']);
        if ($mysql->hasErrors()) {
            $warn = 'Unable to connect to the local default MySQL server. Please check  https://support.plesk.com/hc/articles/213920385 for details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->info('Connected sucessfully', true);
        $result = $mysql->query('CREATE DATABASE IF NOT EXISTS pre_upgrade_checker_test_db');

        if (!$result) {
            $warn = 'User has not enough privileges. Please check https://support.plesk.com/hc/articles/213920385 for details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $result = $mysql->query('DROP DATABASE IF EXISTS pre_upgrade_checker_test_db');

        if (!$result) {
            $warn = 'User has not enough privileges. Please check https://support.plesk.com/hc/articles/213920385 for details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->resultOk();

    }

    //:INFO: Checking for unknown ISAPI filters and show warning https://support.plesk.com/hc/articles/213913765
    function _unknownISAPIfilters()
    {
        $log = Log::getInstance('Detecting installed ISAPI filters');

        if (Util::isUnknownISAPIfilters()) {
            $warn = 'Please read carefully article https://support.plesk.com/hc/articles/213913765, for avoiding possible problems caused by unknown ISAPI filters.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }

    //:INFO: Warning about possible issues related to Microsoft Visual C++ Redistributable Packages ?https://support.plesk.com/hc/articles/115000201014
    function _checkMSVCR()
    {
        $log = Log::getInstance('Microsoft Visual C++ Redistributable Packages');

        $warn = 'Please read carefully article https://support.plesk.com/hc/articles/115000201014, for avoiding possible problems caused by Microsoft Visual C++ Redistributable Packages.';
        $log->info($warn);

        return;
    }

    function _diagnoseKavOnPlesk10x()
    {
        $log = Log::getInstance('Detecting if antivirus is Kaspersky');

        $pleskComponent = new PleskComponent();
        $isKavInstalled = $pleskComponent->isInstalledKav();

        $log->info('Kaspersky antivirus: ' . ($isKavInstalled ? ' installed' : ' not installed'));

        if (Util::isVz() && $isKavInstalled) {
            $warn = 'An old version of Kasperskiy antivirus is detected. ';
            $warn .= 'If you are upgrading to the Panel 10 using EZ templates, update the template of Kaspersky antivirus on hardware node to the latest version, and then upgrade the container.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }

    function _diagnoseDependCycleOfModules()
    {
        //:INFO: Prevent potential problem with E: Couldn't configure pre-depend plesk-core for psa-firewall, probably a dependency cycle.
        $log = Log::getInstance('Detecting if Plesk modules are installed');

        if (Util::isVz()
            && PleskModule::isInstalledWatchdog()
            && PleskModule::isInstalledVpn()
            && PleskModule::isInstalledFileServer()
            && PleskModule::isInstalledFirewall()
        ) {
            $warn = 'Plesk modules "watchdog, fileserver, firewall, vpn" were installed on container. ';
            $warn .= 'If you are upgrading to the Panel 10 using EZ templates, remove the modules, and then upgrade the container.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }
    
    function _checkForCryptPasswords()
    {
        //:INFO: Prevent potential problem with E: Couldn't configure pre-depend plesk-core for psa-firewall, probably a dependency cycle.
        $log = Log::getInstance('Detecting if encrypted passwords are used');

        $db = PleskDb::getInstance();
        $sql = "SELECT COUNT(*) AS cnt FROM accounts WHERE type='crypt' AND password not like '$%';";
        $r = $db->fetchAll($sql);

        if ($r[0]['cnt'] != '0')
        {
            $warn = 'There are ' . $r[0]['cnt'] . ' accounts with passwords encrypted using a deprecated algorithm. Please refer to https://support.plesk.com/hc/articles/115001450829 for the instructions about how to change the password type to plain.';

            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }
    
    //:INFO: PPPM-1405 https://support.plesk.com/hc/articles/115001450829
    function _checkForCryptPasswordsForMailAccounts()
    {
        $log = Log::getInstance('Detecting if old encrypted passwords are used');

        $db = PleskDb::getInstance();
        
        if (PleskVersion::is10x_or_above()) {
            $sql = "SELECT COUNT(*) AS cnt FROM accounts, mail where mail.account_id = accounts.id and type='crypt' AND mail.userId <> 0";
        } else {
            $sql = "SELECT COUNT(*) AS cnt FROM accounts, mail, Permissions where mail.account_id = accounts.id and type='crypt' and mail.perm_id = Permissions.id AND Permissions.permission = 'cp_access' AND Permissions.value = 'true'";
        }
        
        $r = $db->fetchAll($sql);

        if ($r[0]['cnt'] != '0')
        {
            $warn = 'There are ' . $r[0]['cnt'] . ' mail accounts with passwords encrypted using a deprecated algorithm. These users will be able to log in to mail server with PLAIN authentication mechanism but they will lose the ability to log in to Plesk. Please refer to https://support.plesk.com/hc/articles/213396449 for details.';

            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }
    
    function _checkCustomVhostSkeletonStatisticsSubdir()
    {
        if (!(PleskVersion::is10_4_or_above() && version_compare(PleskVersion::getVersion(), '11.1.18', '<='))) {
            return;
        }

        // 'statistics' subdir in vhosts was removed starting from Plesk 11.1.18. It's customization will have no effect after upgrade.
        $log = Log::getInstance('Checking if the deprecated "statistics" subdirectory in virtual host templates can be removed');

        $unmodifiedSkelStatMd5sum = "3f5517860e8adfa4b05c9ea6268b38eb";
        $vhostsDir = Util::getSettingFromPsaConf('HTTPD_VHOSTS_D');
        $returnCode = 0;
        $currentSkelStatMd5sumList = Util::exec("find -L {$vhostsDir}/.skel/*/statistics -type f 2>/dev/null | xargs --no-run-if-empty md5sum | cut -d ' ' -f 1 | sort | uniq", $returnCode);

        if (empty($currentSkelStatMd5sumList)) {
            $log->info('The deprecated "statistics" subdirectory in virtual host template is already removed.');
            $log->resultOk();
        } elseif ($currentSkelStatMd5sumList == $unmodifiedSkelStatMd5sum) {
            $log->info('The "statistics" subdirectories of vhost templates do not contain custom content and will be safely removed during the upgrade.');
            $log->resultOk();
        } else {
            $warn = 'Some virtual host templates have customized content in the "statistics" subdirectories. In Plesk 11.5 and later, such customizations cannot be applied to domains because the "statistics" subdirectory is no longer used in the templates. ';
            $warn.= 'We recommend that you remove the "statistics" subdirectory from templates manually after the upgrade. ';
            $warn.= "You can find the \"statistics\" virtual hosts templates in {$vhostsDir}/.skel/*/statistics.";
            $log->warning($warn);
            $log->resultWarning();
        }
    }

    function _checkApsTablesInnoDB()
    {
        $log = Log::getInstance('Checking if apsc database tables have InnoDB engine');

        $db = PleskDb::getInstance();
        $apsDatabase = $db->fetchOne("select val from misc where param = 'aps_database'");
        $sql = "SELECT TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA = '$apsDatabase' and ENGINE = 'MyISAM'";
        $myISAMTables = $db->fetchAll($sql);
        if (!empty($myISAMTables)) {
            $myISAMTablesList = implode(', ', array_map('reset', $myISAMTables));
            $warn = 'The are tables in apsc database with MyISAM engine: ' . $myISAMTablesList . '. It would be updated to InnoDB engine.';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }

    private function _checkDbUsersTableCollation()
    {
        $log = Log::getInstance('Checking if the db_users table contains case insensitive duplicate login entries...');

        $db = PleskDb::getInstance();
        $columns = $db->fetchAll("show full columns from db_users");
        $collation = null;
        $groupByColumns = array();
        foreach ($columns as $column) {
            if ('login' == $column['Field']) {
                $collation = $column['Collation'];
                $groupByColumns[] = "login COLLATE ascii_general_ci";
            }
            if (in_array($column['Field'], array('db_server_id', 'db_id'))) {
                $groupByColumns[] = $column['Field'];
            }
        }

        $duplicates = array();
        if ('ascii_bin' == $collation) {
            $uniqueKey = implode(',', $groupByColumns);
            $sql = "SELECT GROUP_CONCAT(login) AS logins FROM db_users GROUP BY {$uniqueKey} HAVING COUNT(*) > 1";
            foreach ($db->fetchAll($sql) as $duplicate) {
                $duplicates[] = $duplicate['logins'];
            }
        }
        if ($duplicates) {
            $msg = "There are duplicate entries in the db_users table: " .
                 implode(" and ", $duplicates) . ". " .
                "Rename the database users to resolve the duplication.";
            $log->emergency($msg);
            return;
        }
        $log->resultOk();
    }
}

class Plesk175Requirements
{
    public function validate()
    {
        if (PleskInstallation::isInstalled() && PleskVersion::is_below_17_5() && Util::isLinux()) {
            //:INFO: Check that DUMP_TMP_D is not inside of (or equal to) DUMP_D
            $this->_checkDumpTmpD();
        }
    }

    public function _checkDumpTmpD()
    {
        $log = Log::getInstance('Checking the DUMP_TMP_D directory');

        $dumpD = Util::getSettingFromPsaConf('DUMP_D');
        if (is_null($dumpD)) {
            $log->warning('Unable to obtain the path to the directory defined by the DUMP_D parameter. Check that the DUMP_D parameter is set in the /etc/psa/psa.conf file.');
            $log->resultWarning();
            return;
        }
        $dumpTmpD = Util::getSettingFromPsaConf('DUMP_TMP_D');
        if (is_null($dumpTmpD)) {
            $log->warning('Unable to obtain the path to the directory defined by the DUMP_TMP_D parameter. Check that the DUMP_TMP_D parameter is set in the /etc/psa/psa.conf file.');
            $log->resultWarning();
            return;
        }

        if (strpos(rtrim($dumpTmpD, '/') . '/', rtrim($dumpD, '/') . '/') === 0) {
            $log->error(sprintf('The directory DUMP_TMP_D = %s should not be inside of (or equal to) the directory DUMP_D = %s. Fix these parameters in the /etc/psa/psa.conf file.', $dumpTmpD, $dumpD));
            $log->resultError();
        }

        $log->resultOk();
    }
}

class Plesk178Requirements
{
    public function validate()
    {
        if (PleskInstallation::isInstalled() && Util::isWindows()) {
            $this->_checkPleskVhostsDir();
        }

        if (PleskVersion::is_below_17_8()) {
            $this->checkTomcat();
        }


        if (PleskVersion::is_below_17_8()) {
            $this->checkMultiServer();
        }
    }

    private function checkTomcat()
    {
        if (Util::isWindows()) {
            $tomcatRegBase = '\\PLESK\\PSA Config\\Config\\Packages\\tomcat\\tomcat';
            /* Supported versions on windows are tomcat5 and tomcat7 */
            $key = '/v InstallDir';
            $isInstalled = Util::regQuery($tomcatRegBase . '7', $key, true) || Util::regQuery($tomcatRegBase . '5', $key, true);
        } else {
            $isInstalled = PackageManager::isInstalled('psa-tomcat-configurator');
        }

        if ($isInstalled
            || (PleskDb::getInstance()->fetchOne('show tables like \'WebApps\'')
                && PleskDb::getInstance()->fetchOne('select count(*) from WebApps'))
        ) {
            $log = Log::getInstance('Checking Apache Tomcat installation');
            $message = <<<EOL
After upgrade Tomcat component will not be available for installing and configuring. Java Tomcat applications will be available via 9080 port only and will not be available via http/https.
You can also completely remove Tomcat component. Please check https://support.plesk.com/hc/en-us/articles/115004715774 for more details.
EOL;
            $log->warning($message);
        }
    }

    private function checkMultiServer()
    {
        if (!PleskModule::isMultiServer()) {
            return;
        }

        $log = Log::getInstance('Checking Plesk Multi Server installation');
        $message = <<<EOL
Upgrade to Plesk Onyx 17.8 and higher is not supported on servers with Plesk Multi Server installed.
Please refer to https://support.plesk.com/hc/en-us/articles/115005075474 for details.
EOL;
        $log->emergency($message);
        $log->resultError();
    }

    private function _checkPleskVhostsDir()
    {
        $log = Log::getInstance('Checking mount volume for HTTPD_VHOSTS_D directory');

        $vhostsDir = rtrim(Util::regPleskQuery('HTTPD_VHOSTS_D'), "\\");
        Util::exec("mountvol \"{$vhostsDir}\" /L", $code);
        if ($code == 0) {
            $msg = "A disk volume is mounted to the {$vhostsDir} directory." .
                " It will be unmounted during the Plesk upgrade." .
                " As a result, all hosted websites will become unavailable." .
                " Make sure to remount the volume to the {$vhostsDir} directory after the upgrade.";
            $log->emergency($msg);
            $log->resultError();
            return;
        }

        $log->resultOk();
    }
}

class PleskComponent
{
    function isInstalledKav()
    {
        return $this->_isInstalled('kav');
    }

    function _isInstalled($component)
    {
        //upgrade from 10.x version, use old database structure
        $sql = "SELECT * FROM ServiceNodeProperties WHERE name LIKE 'components.packages.%{$component}%'";

        $pleskDb = PleskDb::getInstance();
        $row = $pleskDb->fetchRow($sql);

        return (empty($row) ? false : true);
    }

    function CurrentWinFTPServer()
    {
        if (Util::isWindows()) {
            $currentFTPServer = Util::regQuery('\PLESK\PSA Config\Config\Packages\ftpserver', '/ve', true);
            $log = Log::getInstance();
            $log->info('Current FTP server is: ' . $currentFTPServer);
            return $currentFTPServer;
        }
    }

    function CurrentWinAntivirus()
    {
        if (Util::isWindows()) {
            $currentAntivirus = Util::regQuery('\PLESK\PSA Config\Config\Packages\antivirus', '/ve', true);
            $log = Log::getInstance();
            $log->info('Current Antivirus is: ' . $currentAntivirus);
            return $currentAntivirus;
        }
    }

    function CurrentWinMssqlWebAdmin()
    {
        if (Util::isWindows()) {
            $currentMssqlAdmin = Util::regQuery('\PLESK\PSA Config\Config\Packages\sqladminmssql', '', true);
            $log = Log::getInstance();
            $log->info('Current MSSQL Web Admin is: ' . $currentMssqlAdmin);
            return $currentMssqlAdmin;
        }
    }

    function CurrentWinDNSServer()
    {
        if (Util::isWindows()) {
            $currentDNSServer = Util::regQuery('\PLESK\PSA Config\Config\Packages\dnsserver', '/ve', true);
            $log = Log::getInstance();
            $log->info('Current DNS server is: ' . $currentDNSServer);
            return $currentDNSServer;
        }
    }

    function CurrentWinAspNet11()
    {
        if (Util::isWindows()) {
            $currentAspNet11 = Util::regQuery('\PLESK\PSA Config\Config\Packages\script.asp.net', '/ve', true);
            $log = Log::getInstance();
            $log->info('Current ASP.Net 1.1 is: ' . $currentAspNet11);
            return $currentAspNet11;
        }
    }

    function getPackageVersion($package_name)
    {
        if (Util::isWindows()) {
            $cmd = '"' . Util::getPleskRootPath() . 'admin\bin\packagemng" ' . $package_name;
        } else {
            if (PleskVersion::is10_4_or_above()) {
                $cmd = '/usr/local/psa/admin/bin/packagemng -l';
            } else {
                $cmd = '/usr/local/psa/admin/bin/packagemng ' . $package_name . ' 2>/dev/null';
            }
        }
        /* packagemng <package name> - returns "<package name>:<package version>" on Windows all versions and Unix till Plesk 10.4 versions
         * since Plesk 10.4 on linux packagemng -l should be used to return list of all packages
         * if <package name> doesn't exists OR not installed on Windows output will be "<package name>:"
         * if <package name> doesn't installed on Linux output will be "<package name>:not_installed"
         * if <package name> doesn't exists on Linux output will be "packagemng: Package <package name> is not found in Components table"
         */
        $output = Util::exec($cmd, $code);
        if (preg_match('/' . $package_name .  '\:(.+)/', $output, $version)) {
            if ($version[1] <> 'not_installed') {
                return $version[1];
            }
        }
        return null;
    }
}

class PleskModule
{
    function isInstalledWatchdog()
    {
        return PleskModule::_isInstalled('watchdog');
    }

    function isInstalledFileServer()
    {
        return PleskModule::_isInstalled('fileserver');
    }

    function isInstalledFirewall()
    {
        return PleskModule::_isInstalled('firewall');
    }

    function isInstalledVpn()
    {
        return PleskModule::_isInstalled('vpn');
    }

    public static function isMultiServer()
    {
        return PleskModule::_isInstalled('plesk-multi-server') ||
            PleskModule::_isInstalled('plesk-multi-server-node');
    }

    protected static function _isInstalled($module)
    {
        $sql = "SELECT * FROM Modules WHERE name = '{$module}'";

        $pleskDb = PleskDb::getInstance();
        $row = $pleskDb->fetchRow($sql);

        return (empty($row) ? false : true);
    }
}

class PleskInstallation
{
    function validate()
    {
        if (!$this->isInstalled()) {
            $log = Log::getInstance('Checking for Plesk installation');
            $log->step('Plesk installation is not found. You will have no problems with upgrade, go on and install '
                . PleskVersion::getLatestPleskVersionAsString() . ' (https://www.plesk.com/)');
            return;
        }
        $this->_detectVersion();
    }

    function isInstalled()
    {
        $rootPath = Util::getPleskRootPath();
        if (empty($rootPath) || !file_exists($rootPath)) {
            return false;
        }
        return true;
    }

    function _detectVersion()
    {
        $log = Log::getInstance('Installed Plesk version/build: ' . PleskVersion::getVersionAndBuild(), false);

        $currentVersion = PleskVersion::getVersion();
        if (version_compare($currentVersion, PLESK_VERSION, 'eq')) {
            $err = 'You have already installed the latest version ' . PleskVersion::getLatestPleskVersionAsString() . '. ';
            $err .= 'Tool must be launched prior to upgrade to ' . PleskVersion::getLatestPleskVersionAsString() . ' for the purpose of getting a report on potential problems with the upgrade.';
            $log->info($err);
            exit(0);
        }

        if (!PleskVersion::isUpgradeSupportedVersion()) {
            $err = 'Unable to find Plesk 12.x, or Plesk 17.x. ';
            $err .= 'Tool must be launched prior to upgrade to ' . PleskVersion::getLatestPleskVersionAsString() . ' for the purpose of getting a report on potential problems with the upgrade.';
            fatal($err);
        }
    }
}

class PleskVersion
{
    public static function is8x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '8.0.0', '>=') && version_compare($version, '9.0.0', '<');
    }

    public static function is9x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '9.0.0', '>=') && version_compare($version, '10.0.0', '<');
    }

    public static function is11x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '11.0.0', '>=') && version_compare($version, '12.0.0', '<');
    }
    
    public static function is11x_or_above()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '11.0.0', '>=');
    }

    public static function is12x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '12.0.0', '>=') && version_compare($version, '13.0.0', '<');
    }

    public static function is_below_11_1()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '11.1.0', '<');
    }

    public static function is_below_12_0()
    {
        // Historically it started as 11.6, so we check against it
        $version = PleskVersion::getVersion();
        return version_compare($version, '11.6.0', '<');
    }

    public static function is_below_12_1()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '12.1.0', '<');
    }

    public static function is17x()
    {
        // historically it has been started as 13.0
        $version = PleskVersion::getVersion();
        return version_compare($version, '13.0.0', '>=') && version_compare($version, '17.8.11', '<');
    }

    public static function is_below_17_0()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '13.0.0', '<');
    }
        
    public static function is17x_or_above()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '13.0.0', '>=');
    }

    public static function is_below_17_5()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '17.5.0', '<');
    }

    public static function is_below_17_8()
    {
        return version_compare(PleskVersion::getVersion(), '17.8.0', '<');
    }

    public static function is10x_or_above()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '10.0.0', '>=');
    }

    public static function is10_2_or_above()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '10.2.0', '>=');
    }

    public static function is10_3_or_above()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '10.3.0', '>=');
    }

    public static function is10_4()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '10.4.0', '>=') && version_compare($version, '10.5.0', '<');
    }

    public static function is10_4_or_above()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '10.4.0', '>=');
    }

    public static function getVersion()
    {
        $version = PleskVersion::getVersionAndBuild();
        if (!preg_match('/([0-9]+[.][0-9]+[.][0-9]+)/', $version, $matches)) {
            fatal("Incorrect Plesk version format. Current version: {$version}");
        }
        return $matches[1];
    }

    public static function getVersionAndBuild()
    {
        $versionPath = Util::getPleskRootPath().'/version';
        if (!file_exists($versionPath)) {
            fatal("Plesk version file is not exists $versionPath");
        }
        $version = file_get_contents($versionPath);
        $version = trim($version);
        return $version;
    }

    public static function getLatestPleskVersionAsString()
    {
        return 'Plesk ' . PLESK_VERSION;
    }

    public static function getCurrentPleskVersionAsString()
    {
        return 'Plesk ' . PleskVersion::getVersion();
    }

    public static function isUpgradeSupportedVersion()
    {
        return PleskVersion::is12x() || PleskVersion::is17x();
    }
}

class Log
{
    private $errors;
    private $warnings;
    private $emergency;
    private $logfile;
    private $step;
    private $step_header;

    /** @var array */
    private $errorsContent = [];

    /** @var array */
    private $warningsContent = [];

    public static function getInstance($step_msg = '', $step_number = true)
    {
        static $_instance = null;
        if (is_null($_instance)) {
            $_instance = new Log();
        }
        if ($step_msg) {
            $_instance->step($step_msg, $step_number);
        }

        return $_instance;
    }

    private function __construct()
    {
        $this->log_init();
        @unlink($this->logfile);
    }

    private function log_init()
    {
        $this->step      = 0;
        $this->errors    = 0;
        $this->warnings  = 0;
        $this->emergency = 0;
        $this->logfile = LOG_PATH;
        $this->step_header = "Unknown step is running";
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getEmergency()
    {
        return $this->emergency;
    }

    public function fatal($msg)
    {
        $this->errors++;

        $this->errorsContent[] = $msg;
        $content = $this->get_log_string($msg, 'FATAL_ERROR');
        fwrite(STDERR, $content);
        $this->write($content);
    }

    public function error($msg)
    {
        $this->errors++;

        $this->errorsContent[] = $msg;
        $content = $this->get_log_string($msg, 'ERROR');
        fwrite(STDERR, $content);
        $this->write($content);
    }

    public function warning($msg)
    {
        $this->warnings++;

        $this->warningsContent[] = $msg;
        $content = $this->get_log_string($msg, 'WARNING');
        fwrite(STDERR, $content);
        $this->write($content);
    }

    public function emergency($msg)
    {
        $this->emergency++;

        $this->errorsContent[] = $msg;
        $content = $this->get_log_string($msg, 'EMERGENCY');
        fwrite(STDERR, $content);
        $this->write($content);
    }

    public function step($msg, $useNumber=false)
    {
        $this->step_header = $msg;

        echo PHP_EOL;
        $this->write(PHP_EOL);

        if ($useNumber) {
            $msg = "STEP " . $this->step . ": {$msg}...";
            $this->step++;
        } else {
            $msg = "{$msg}...";
        }

        $this->info($msg);
    }

    public function resultOk()
    {
        $this->info('Result: OK');
    }

    public function resultWarning()
    {
        $this->info('Result: WARNING');
    }

    public function resultError()
    {
        $this->info('Result: ERROR');
    }

    public function info($msg)
    {
        $content = $this->get_log_string($msg, 'INFO');
        echo $content;
        $this->write($content);
    }

    public function debug($msg)
    {
        $this->write($this->get_log_string($msg, 'DEBUG'));
    }

    public function dumpStatistics()
    {
        $errors = $this->errors + $this->emergency;
        $str = "Errors found: $errors; Warnings found: {$this->warnings}";
        echo PHP_EOL . $str . PHP_EOL . PHP_EOL;
    }

    private function get_log_string($msg, $type)
    {
        if (getenv('VZ_UPGRADE_SCRIPT')) {
            switch ($type) {
                case 'FATAL_ERROR':
                case 'ERROR':
                case 'WARNING':
                case 'EMERGENCY':
                    $content = "[{$type}]: {$this->step_header} DESC: {$msg}" . PHP_EOL;
                    break;
                default:
                    $content = "[{$type}]: {$msg}" . PHP_EOL;
            }
        } else if (getenv('AUTOINSTALLER_VERSION')) {
            $content = "{$type}: {$msg}" . PHP_EOL;
        } else {
            $date = date('Y-m-d h:i:s');
            $content = "[{$date}][{$type}] {$msg}" . PHP_EOL;
        }

        return $content;
    }

    public function write($content, $file = null, $mode='a+')
    {
        $logfile = $file ? $file : $this->logfile;
        $fp = fopen($logfile, $mode);
        fwrite($fp, $content);
        fclose($fp);
    }

    private function getJsonFileName()
    {
        return (Util::isWindows() ?
                rtrim(Util::regPleskQuery('PRODUCT_DATA_D'), "\\") :
                Util::getSettingFromPsaConf('PRODUCT_ROOT_D')
            ) . '/var/' . LOG_JSON;
    }

    public function writeJsonFile()
    {
        $data = [
            'version' => PRE_UPGRADE_SCRIPT_VERSION,
            'errorsFound' => $this->errors + $this->emergency,
            'errors' => $this->errorsContent,
            'warningsFound' => $this->warnings,
            'warnings' => $this->warningsContent,
        ];
        file_put_contents($this->getJsonFileName(), json_encode($data));
    }
}

class PleskDb
{
    var $_db = null;

    public function __construct($dbParams)
    {
        switch($dbParams['db_type']) {
            case 'mysql':
                $this->_db = new DbMysql(
                    $dbParams['host'], $dbParams['login'], $dbParams['passwd'], $dbParams['db'], $dbParams['port']
                );
                break;

            case 'jet':
                $this->_db = new DbJet($dbParams['db']);
                break;

            case 'mssql':
                $this->_db = new DbMsSql(
                    $dbParams['host'], $dbParams['login'], $dbParams['passwd'], $dbParams['db'], $dbParams['port']
                );
                break;

            default:
                fatal("{$dbParams['db_type']} is not implemented yet");
                break;
        }
    }

    function getInstance()
    {
        global $options;
        static $_instance = array();

        $dbParams['db_type']= Util::getPleskDbType();
        $dbParams['db']     = Util::getPleskDbName();
        $dbParams['port']   = Util::getPleskDbPort();
        $dbParams['login']  = Util::getPleskDbLogin();
        $dbParams['passwd'] = $options->getDbPasswd();
        $dbParams['host']   = Util::getPleskDbHost();

        $dbId = md5(implode("\n", $dbParams));

        $_instance[$dbId] = new PleskDb($dbParams);

        return $_instance[$dbId];
    }

    function fetchOne($sql)
    {
        if (DEBUG) {
            $log = Log::getInstance();
            $log->info($sql);
        }
        return $this->_db->fetchOne($sql);
    }

    function fetchRow($sql)
    {
        $res = $this->fetchAll($sql);
        if (is_array($res) && isset($res[0])) {
            return $res[0];
        }
        return array();
    }

    function fetchAll($sql)
    {
        if (DEBUG) {
            $log = Log::getInstance();
            $log->info($sql);
        }
        return $this->_db->fetchAll($sql);
    }
}

class DbMysql
{
    var $_dbHandler = null;

    public function __construct($host, $user, $passwd, $database, $port)
    {
        if ( extension_loaded('mysql') ) {
            $this->_dbHandler = @mysql_connect("{$host}:{$port}", $user, $passwd);
            if (!is_resource($this->_dbHandler)) {
                $mysqlError = mysql_error();
                if (stristr($mysqlError, 'access denied for user')) {
                    $errMsg = 'Given <password> is incorrect. ' . $mysqlError;
                } else {
                    $errMsg = 'Unable to connect database. The reason of problem: ' . $mysqlError . PHP_EOL;
                }
                $this->_logError($errMsg);
            }
            @mysql_select_db($database, $this->_dbHandler);
        } else if ( extension_loaded('mysqli') ) {

            $this->_dbHandler = @mysqli_connect($host, $user, $passwd, $database, $port);
            if (!$this->_dbHandler) {
                $mysqlError = mysqli_connect_error();
                if (stristr($mysqlError, 'access denied for user')) {
                    $errMsg = 'Given <password> is incorrect. ' . $mysqlError;
                } else {
                    $errMsg = 'Unable to connect database. The reason of problem: ' . $mysqlError . PHP_EOL;
                }
                $this->_logError($errMsg);
            }
        } else {
            fatal("No MySQL extension is available");
        }
    }

    function fetchAll($sql)
    {
        if ( extension_loaded('mysql') ) {
            $res = mysql_query($sql, $this->_dbHandler);
            if (!is_resource($res)) {
                $this->_logError('Unable to execute query. Error: ' . mysql_error($this->_dbHandler));
            }
            $rowset = array();
            while ($row = mysql_fetch_assoc($res)) {
                $rowset[] = $row;
            }
            return $rowset;
        } else if ( extension_loaded('mysqli') ) {
            $res = $this->_dbHandler->query($sql);
            if ($res === false) {
                $this->_logError('Unable to execute query. Error: ' . mysqli_error($this->_dbHandler));
            }
            $rowset = array();
            while ($row = mysqli_fetch_assoc($res)) {
                $rowset[] = $row;
            }
            return $rowset;
        } else {
            fatal("No MySQL extension is available");
        }
    }

    function fetchOne($sql)
    {
        if ( extension_loaded('mysql') ) {
            $res = mysql_query($sql, $this->_dbHandler);
            if (!is_resource($res)) {
                $this->_logError('Unable to execute query. Error: ' . mysql_error($this->_dbHandler));
            }
            $row = mysql_fetch_row($res);
            return $row[0];
        } else if ( extension_loaded('mysqli') ) {
            $res = $this->_dbHandler->query($sql);
            if ($res === false) {
                $this->_logError('Unable to execute query. Error: ' . mysqli_error($this->_dbHandler));
            }
            $row = mysqli_fetch_row($res);
            return $row[0];
        } else {
            fatal("No MySQL extension is available");
        }
    }

    function query($sql)
    {
        if ( extension_loaded('mysql') ) {
            $res = mysql_query($sql, $this->_dbHandler);
            if ($res === false ) {
                $this->_logError('Unable to execute query. Error: ' . mysql_error($this->_dbHandler) );
            }
            return $res;
        } else if ( extension_loaded('mysqli') ) {
            $res = $this->_dbHandler->query($sql);
            if ($res === false ) {
                $this->_logError('Unable to execute query. Error: ' . mysqli_error($this->_dbHandler) );
            }
            return $res;
        } else {
            fatal("No MySQL extension is available");
        }
    }

    function _logError($message)
    {
        fatal("[MYSQL ERROR] $message");
    }
}

class DbClientMysql extends DbMysql
{
    var $errors = array();

    function _logError($message)
    {
        $message = "[MYSQL ERROR] $message";
        $log = Log::getInstance();
        $log->warning($message);
        $this->errors[] = $message;
    }

    function hasErrors() {
        return count($this->errors) > 0;
    }
}

class DbJet
{
    var $_dbHandler = null;

    public function __construct($dbPath)
    {
        $dsn = "Provider='Microsoft.Jet.OLEDB.4.0';Data Source={$dbPath}";
        $this->_dbHandler = new COM("ADODB.Connection", NULL, CP_UTF8);
        if (!$this->_dbHandler) {
            $this->_logError('Unable to init ADODB.Connection');
        }

        $this->_dbHandler->open($dsn);
    }

    function fetchAll($sql)
    {
        $result_id = $this->_dbHandler->execute($sql);
        if (!$result_id) {
            $this->_logError('Unable to execute sql query ' . $sql);
        }
        if ($result_id->BOF && !$result_id->EOF) {
            $result_id->MoveFirst();
        }
        if ($result_id->EOF) {
            return array();
        }

        $rowset = array();
        while(!$result_id->EOF) {
            $row = array();
            for ($i=0;$i<$result_id->Fields->count;$i++) {
                $field = $result_id->Fields($i);
                $row[$field->Name] = (string)$field->value;
            }
            $result_id->MoveNext();
            $rowset[] = $row;
        }
        return $rowset;
    }

    function fetchOne($sql)
    {
        $result_id = $this->_dbHandler->execute($sql);
        if (!$result_id) {
            $this->_logError('Unable to execute sql query ' . $sql);
        }
        if ($result_id->BOF && !$result_id->EOF) {
            $result_id->MoveFirst();
        }
        if ($result_id->EOF) {
            return null;
        }
        $field = $result_id->Fields(0);
        $result = $field->value;

        return (string)$result;
    }

    function _logError($message)
    {
        fatal("[JET ERROR] $message");
    }
}

class DbMsSql extends DbJet
{
    public function __construct($host, $user, $passwd, $database, $port)
    {
        $dsn = "Provider=SQLOLEDB.1;Initial Catalog={$database};Data Source={$host}";
        $this->_dbHandler = new COM("ADODB.Connection", NULL, CP_UTF8);
        if (!$this->_dbHandler) {
            $this->_logError('Unable to init ADODB.Connection');
        }
        $this->_dbHandler->open($dsn, $user, $passwd);
    }

    function _logError($message)
    {
        fatal("[MSSQL ERROR] $message");
    }
}

class Util
{
    function isWindows()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        }
        return false;
    }

    function isLinux()
    {
        return !Util::isWindows();
    }

    function isVz()
    {
        $vz = false;
        if (Util::isLinux()) {
            if (file_exists('/proc/vz/veredir')) {
                $vz = true;
            }
        } else {
            $reg = 'REG QUERY "HKLM\SOFTWARE\SWsoft\Virtuozzo" 2>nul';
            Util::exec($reg, $code);
            if ($code==0) {
                $vz = true;
            }
        }
        return $vz;
    }

    function getArch()
    {
        global $arch;
        if (!empty($arch))
            return $arch;

        $arch = 'i386';
        if (Util::isLinux()) {
            $cmd = 'uname -m';
            $x86_64 = 'x86_64';
            $output = Util::exec($cmd, $code);
            if (!empty($output) && stristr($output, $x86_64)) {
                $arch = 'x86_64';
            }
        } else {
            $cmd = 'systeminfo';
            $output = Util::exec($cmd, $code);
            if (preg_match('/System Type:[\s]+(.*)/', $output, $macthes) && stristr($macthes[1], '64')) {
                $arch = 'x86_64';
            }
        }
        return $arch;
    }

    function getHostname()
    {
        if (Util::isLinux()) {
            $cmd = 'hostname -f';
        } else {
            $cmd = 'hostname';
        }
        $hostname = Util::exec($cmd, $code);

        if (empty($hostname)) {
            $err = 'Command: ' . $cmd . ' returns: ' . $hostname . "\n";
            $err .= 'Hostname is not defined and configured. Unable to get hostname. Server should have properly configured hostname and it should be resolved locally.';
            fatal($err);
        }

        return $hostname;
    }

    function isFQDN($string)
    {
        $tld_list = array(
                'aero', 'asia', 'biz', 'cat', 'com', 'coop', 'edu', 'gov', 'info', 'int', 'jobs', 'mil', 'mobi', 'museum', 'name', 'net',
                'org', 'pro', 'tel', 'travel', 'xxx', 'ac', 'ad', 'ae', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao', 'aq', 'ar', 'as', 'at',
                'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bv',
                'bw', 'by', 'bz', 'ca', 'cc', 'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'cr', 'cs', 'cu', 'cv', 'cx',
                'cy', 'cz', 'dd', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'ee', 'eg', 'eh', 'er', 'es', 'et', 'eu', 'fi', 'fj', 'fk',
                'fm', 'fo', 'fr', 'ga', 'gb', 'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gp', 'gq', 'gr', 'gs', 'gt', 'gu',
                'gw', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'io', 'iq', 'ir', 'is', 'it', 'je', 'jm',
                'jo', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp', 'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls',
                'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mg', 'mh', 'mk', 'ml', 'mm', 'mn', 'mo', 'mp', 'mq', 'mr', 'ms', 'mt',
                'mu', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'nc', 'ne', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'pa',
                'pe', 'pf', 'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'ps', 'pt', 'pw', 'py', 'qa', 're', 'ro', 'rs', 'ru', 'rw', 'sa',
                'sb', 'sc', 'sd', 'se', 'sg', 'sh', 'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'ss', 'st', 'su', 'sv', 'sy', 'sz',
                'tc', 'td', 'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tp', 'tr', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk',
                'us', 'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws', 'ye', 'yt', 'za', 'zm', 'zw' );

        $label = '[a-zA-Z0-9\-]{1,62}\.';
        $tld = '[\w]+';
        if(preg_match( '/^(' . $label. ')+(' . $tld . ')$/', $string, $match ) && in_array( $match[2], $tld_list )) {
            return TRUE;
        } else {
            return FALSE;
        }

    }

    function resolveHostname($hostname)
    {
        $dns_record = @dns_get_record($hostname, DNS_A | DNS_AAAA);
        if (false === $dns_record) {
            $error = error_get_last();
            Log::getInstance()->warning(sprintf(
                "Unable to resolve hostname \"%s\": %s\nMake sure that the operating system`s DNS resolver is set up and works properly."
                , $hostname
                , $error['message']
            ));
            return null;
        }

        if (isset($dns_record[0]['ip'])) {
            return $dns_record[0]['ip'];
        }
        if (isset($dns_record[0]["ipv6"])) {
            return $dns_record[0]['ipv6'];
        }

        return null;
    }

    function getIP()
    {
        $list = Util::getIPList();
        return $list[0]; //main IP
    }

    function getIPList($lo=false)
    {
        if (Util::isLinux()) {
            $ipList = Util::getIPv4ListOnLinux();
            foreach ($ipList as $key => $ip) {
                if (!$lo && substr($ip, 0, 3) == '127') {
                    unset($ipList[$key]);
                    continue;
                }
                trim($ip);
            }
            $ipList = array_values($ipList);
        } else {
            $cmd = 'hostname';
            $hostname = Util::exec($cmd, $code);
            $ip = gethostbyname($hostname);
            $res = ($ip != $hostname) ? true : false;
            if (!$res) {
                fatal('Unable to retrieve IP address');
            }
            $ipList = array(trim($ip));
        }
        return $ipList;
    }

    function getIPv6ListOnLinux()
    {
        return Util::grepCommandOutput(array(
            array('bin' => 'ip', 'command' => '%PATH% addr list', 'regexp' => '#inet6 ([^ /]+)#'),
            array('bin' => 'ifconfig', 'command' => '%PATH% -a', 'regexp' => '#inet6 (?:addr: ?)?([A-F0-9:]+)#i'),
        ));
    }

    function getIPv4ListOnLinux()
    {
        $commands = array(
            array('bin' => 'ip', 'command' => '%PATH% addr list', 'regexp' => '#inet ([^ /]+)#'),
            array('bin' => 'ifconfig', 'command' => '%PATH% -a', 'regexp' => '#inet (?:addr: ?)?([\d\.]+)#'),
        );
        if (!($list = Util::grepCommandOutput($commands))) {
            fatal('Unable to get IP address');
        }
        return $list;
    }

    function grepCommandOutput($cmds)
    {
        foreach ($cmds as $cmd) {
            if ($fullPath = Util::lookupCommand($cmd['bin'])) {
                $output = Util::exec(str_replace("%PATH%", $fullPath, $cmd['command']), $code);
                if (preg_match_all($cmd['regexp'], $output, $matches)) {
                    return $matches[1];
                }
            }
        }
        return false;
    }

    function getIPListOnWindows()
    {
        $cmd = 'wmic.exe path win32_NetworkAdapterConfiguration get IPaddress';
        $output = Util::exec($cmd, $code);
        if (!preg_match_all('/"(.*?)"/', $output, $matches)) {
            fatal('Unable to get IP address');
        }
        return $matches[1];
    }

    function getPleskRootPath()
    {
        global $_pleskRootPath;
        if (empty($_pleskRootPath)) {
            if (Util::isLinux()) {
                if (PleskOS::isDebLike()) {
                    $_pleskRootPath = '/opt/psa';
                } else {
                    $_pleskRootPath = '/usr/local/psa';
                }
            }
            if (Util::isWindows()) {
                $_pleskRootPath = Util::regPleskQuery('PRODUCT_ROOT_D', true);
            }
        }
        return $_pleskRootPath;
    }

    function getPleskDbName()
    {
        $dbName = 'psa';
        if (Util::isWindows()) {
            $dbName = Util::regPleskQuery('mySQLDBName');
        }
        return $dbName;
    }

    function getPleskDbLogin()
    {
        $dbLogin = 'admin';
        if (Util::isWindows()) {
            $dbLogin = Util::regPleskQuery('PLESK_DATABASE_LOGIN');
        }
        return $dbLogin;
    }

    function getPleskDbType()
    {
        $dbType = 'mysql';
        if (Util::isWindows()) {
            $dbType = strtolower(Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME'));
        }
        return $dbType;
    }

    function getPleskDbHost()
    {
        $dbHost = 'localhost';
        if (Util::isWindows()) {
            $dbProvider = strtolower(Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME'));
            if ($dbProvider == 'mysql' || $dbProvider == 'mssql') {
                $dbHost = Util::regPleskQuery('MySQL_DB_HOST');
            }
        }
        return $dbHost;
    }

    function getPleskDbPort()
    {
        $dbPort = '3306';
        if (Util::isWindows()) {
            $dbPort = Util::regPleskQuery('MYSQL_PORT');
        }
        return $dbPort;
    }

    function regPleskQuery($key, $returnResult=false)
    {
        $arch = Util::getArch();
        if ($arch == 'x86_64') {
            $reg = 'REG QUERY "HKLM\SOFTWARE\Wow6432Node\Plesk\Psa Config\Config" /v '.$key;
        } else {
            $reg = 'REG QUERY "HKLM\SOFTWARE\Plesk\Psa Config\Config" /v '.$key;
        }
        $output = Util::exec($reg, $code);

        if ($code) {
            $log = Log::getInstance();
            $log->info($reg);
            $log->info($output);
            if ($returnResult) {
                return false;
            } else {
                fatal("Unable to get '$key' from registry");
            }
        }

        if (!preg_match("/\w+\s+REG_SZ\s+(.*)/i", trim($output), $matches)) {
            fatal('Unable to macth registry value by key '.$key.'. Output: ' .  trim($output));
        }

        return $matches[1];
    }

    function regQuery($path, $key, $returnResult=false)
    {
        $arch = Util::getArch();
        if ($arch == 'x86_64') {
            $reg = 'REG QUERY "HKLM\SOFTWARE\Wow6432Node' . $path .  '" '.$key;
        } else {
            $reg = 'REG QUERY "HKLM\SOFTWARE' . $path .  '" '.$key;
        }
        $output = Util::exec($reg, $code);

        if ($code) {
            $log = Log::getInstance();
            $log->info($reg);
            $log->info($output);
            if ($returnResult) {
                return false;
            } else {
                fatal("Unable to get '$key' from registry");
            }
        }
        
        if (!preg_match("/\s+REG_SZ(\s+)?(.*)/i", trim($output), $matches)) {
            fatal('Unable to match registry value by key '.$key.'. Output: ' .  trim($output));
        }

        return $matches[2];
    }

    function getAutoinstallerVersion()
    {
        if (Util::isLinux()) {
            $rootPath = Util::getPleskRootPath();
            $cmd = $rootPath . '/admin/sbin/autoinstaller --version';
            $output = Util::exec($cmd, $code);
        } else {
            $cmd = '"' . Util::regPleskQuery('PRODUCT_ROOT_D', true) . 'admin\bin\ai.exe" --version';
            $output = Util::exec($cmd, $code);
        }
        if (!preg_match("/\d+\.\d+\.\d+/", trim($output), $matches)) {
            fatal('Unable to match autoinstaller version. Output: ' .  trim($output));
        }
        return $matches[0];
    }

    function getAutointallerVersionEnv()
    {
        return getenv('AUTOINSTALLER_VERSION');
    }
    
    function lookupCommand($cmd, $exit = false, $path = '/bin:/usr/bin:/usr/local/bin:/usr/sbin:/sbin:/usr/local/sbin')
    {
        $dirs = explode(':', $path);
        foreach ($dirs as $dir) {
            $util = $dir . '/' . $cmd;
            if (is_executable($util)) {
                return $util;
            }
        }
        if ($exit) {
            fatal("{$cmd}: command not found");
        }
        return false;
    }

    function getSystemDisk()
    {
        $cmd = 'echo %SYSTEMROOT%';
        $output = Util::exec($cmd, $code);
        return substr($output, 0, 3);
    }

    function getSystemRoot()
    {
        $cmd = 'echo %SYSTEMROOT%';
        $output = Util::exec($cmd, $code);
        return $output;
    }

    function getFileVersion($file)
    {
        $fso = new COM("Scripting.FileSystemObject");
        $version = $fso->GetFileVersion($file);
        $fso = null;
        return $version;
    }

    function isUnknownISAPIfilters()
    {
        if (PleskVersion::is17x_or_above()) {
            return false;
        }
        
        $log = Log::getInstance();

        $isUnknownISAPI = false;
        $knownISAPI = array ("ASP\\.Net.*", "sitepreview", "COMPRESSION", "jakarta");

        foreach ($knownISAPI as &$value) {
            $value = strtoupper($value);
        }
        $cmd='cscript ' . Util::getSystemDisk() . 'inetpub\AdminScripts\adsutil.vbs  ENUM W3SVC/FILTERS';
        $output = Util::exec($cmd,  $code);

        if ($code!=0) {
            $log->info("Unable to get ISAPI filters. Error: " . $output);
            return false;
        }
        if (!preg_match_all('/FILTERS\/(.*)]/', trim($output), $matches)) {
            $log->info($output);
            $log->info("Unable to get ISAPI filters from output: " . $output);
            return false;
        }
        foreach ($matches[1] as $ISAPI) {
            $valid = false;
            foreach ($knownISAPI as $knownPattern) {
                if (preg_match("/$knownPattern/i", $ISAPI)) {
                    $valid = true;
                    break;
                }
            }
            if (! $valid ) {
                $log->warning("Unknown ISAPI filter detected in IIS: " . $ISAPI);
                $isUnknownISAPI = true;
            }
        }

        return $isUnknownISAPI;
    }

    /**
     * @return string
     */
    function getMySQLServerVersion()
    {
        $credentials = Util::getDefaultClientMySQLServerCredentials();

        if (preg_match('/AES-128-CBC/', $credentials['admin_password'])) {
            Log::getInstance()->info('The administrator\'s password for the default MySQL server is encrypted.');

            return '';
        }

        $mysql = new DbClientMysql(
            $credentials['host'],
            $credentials['admin_login'],
            $credentials['admin_password'],
            'information_schema',
            $credentials['port']
        );

        if (!$mysql->hasErrors()) {
            $sql = 'select version()';
            $mySQLversion = $mysql->fetchOne($sql);
            if (!preg_match("/(\d{1,})\.(\d{1,})\.(\d{1,})/", trim($mySQLversion), $matches)) {
                fatal('Unable to match MySQL server version.');
            }

            return $matches[0];
        }

        return '';
    }

    function getDefaultClientMySQLServerCredentials()
    {
        $db = PleskDb::getInstance();
        $sql = "SELECT val FROM misc WHERE param='default_server_mysql'";
        $defaultServerMysqlId = $db->fetchOne($sql);
        if ($defaultServerMysqlId) {
            $where = "id=${defaultServerMysqlId}";
        } else {
            $where = "type='mysql' AND host='localhost'";
        }
        $sql = "SELECT ds.host, ds.port, ds.admin_login, ds.admin_password FROM DatabaseServers ds WHERE {$where}";
        $clientDBServerCredentials = $db->fetchAll($sql)[0];
        if ($clientDBServerCredentials['host'] === 'localhost' && Util::isLinux()) {
            $clientDBServerCredentials['admin_password'] = Util::retrieveAdminMySQLDbPassword();
        }
        if (empty($clientDBServerCredentials['port'])) {
            $clientDBServerCredentials['port'] = self::getPleskDbPort();
        }

        return $clientDBServerCredentials;
    }

    function retrieveAdminMySQLDbPassword()
    {
        if (Util::isLinux())
            return trim( Util::readfile("/etc/psa/.psa.shadow") );
        else
            return null;
    }

    function exec($cmd, &$code)
    {
        $log = Log::getInstance();

        if (!$cmd) {
            $log->info('Unable to execute a blank command. Please see ' . LOG_PATH . ' for details.');

            $debugBacktrace = "";
            foreach (debug_backtrace() as $i => $obj) {
                $debugBacktrace .= "#{$i} {$obj['file']}:{$obj['line']} {$obj['function']} ()\n";
            }
            $log->debug("Unable to execute a blank command. The stack trace:\n{$debugBacktrace}");
            $code = 1;
            return '';
        }
        exec($cmd, $output, $code);
        return trim(implode("\n", $output));
    }

    function readfile($file)
    {
        if (!is_file($file) || !is_readable($file))
            return null;
        $lines = file($file);
        if ($lines === false)
            return null;
        return trim(implode("\n", $lines));
    }

    function readfileToArray($file)
    {
        if (!is_file($file) || !is_readable($file))
            return null;
        $lines = file($file);
        if ($lines === false)
            return null;
        return $lines;
    }

    function getSettingFromPsaConf($setting)
    {
        $file = '/etc/psa/psa.conf';
        if (!is_file($file) || !is_readable($file))
            return null;
        $lines = file($file);
        if ($lines === false)
            return null;
        foreach ($lines as $line) {
            if (preg_match("/^{$setting}\s.*/", $line, $match_setting)) {
                if (preg_match("/[\s].*/i", $match_setting[0], $match_value)) {
                    $value = trim($match_value[0]);
                    return $value;
                }
            }
        }
        return null;
    }

    function GetFreeSystemMemory()
    {
        if (Util::isLinux()) {
            $cmd = 'cat /proc/meminfo';
            $output = Util::exec($cmd, $code);

            $data = array();
            foreach (array('MemFree', 'Buffers', 'Cached', 'SwapFree') as $param) {
                if (preg_match("/$param:.+?(\d+)/", $output, $value)) {
                    $data[$param] = $value[1];  // value is in Kb
                } else {
                    $data[$param] = 0;
                }
            }

            return $data['MemFree'] + $data['Buffers'] + $data['Cached'] + $data['SwapFree'];
        } else {
            $cmd = 'wmic.exe OS get FreePhysicalMemory';
            $output = Util::exec($cmd, $code);
            if (preg_match("/\d+/", $output, $FreePhysicalMemory)) {
                $cmd = 'wmic.exe PAGEFILE get AllocatedBaseSize';
                $output = Util::exec($cmd, $code);
                if (preg_match("/\d+/", $output, $SwapAllocatedBaseSize)) {
                    $cmd = 'wmic.exe PAGEFILE get CurrentUsage';
                    $output = Util::exec($cmd, $code);
                    if (preg_match("/\d+/", $output, $SwapCurrentUsage)) {
                        return $FreePhysicalMemory[0] + ($SwapAllocatedBaseSize[0] - $SwapCurrentUsage[0]) * 1000; // returns value in Kb
                    }
                }
            }
        }
    }

    function getPhpIni()
    {
        if (Util::isLinux()) {
            // Debian/Ubuntu  /etc/php5/apache2/php.ini /etc/php5/conf.d/
            // SuSE  /etc/php5/apache2/php.ini /etc/php5/conf.d/
            // CentOS 4/5 /etc/php.ini /etc/php.d
            if (PleskOS::isRedHatLike()) {
                $phpini = Util::readfileToArray('/etc/php.ini');
            } else {
                $phpini = Util::readfileToArray('/etc/php5/apache2/php.ini');
            }
        }

        return $phpini;
    }
    
    function getUserBeanCounters()
    {
        if (!Util::isLinux()) {
            
            return false;
        }
        $user_beancounters = array();
        $ubRaw = Util::readfileToArray('/proc/user_beancounters');
        
        if (!$ubRaw) {
            
            return false;
        }
        for ($i=2; $i<=count($ubRaw)-1; $i++) {
            
            if (preg_match('/^.+?:?.+?\b(\w+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $ubRaw[$i], $limit_name)) {
                
                $user_beancounters[trim($limit_name[1])] = array(
                    'held' => (int)$limit_name[2],
                    'maxheld' => (int)$limit_name[3],
                    'barrier' => (int)$limit_name[4],
                    'limit' => (int)$limit_name[5],
                    'failcnt' => (int)$limit_name[6]
                );
            }
        }
        
        return $user_beancounters;
    }
}

class PackageManager
{
    function buildListCmdLine($glob)
    {
        if (PleskOS::isRedHatLike() || PleskOS::isSuseLike()) {
            $cmd = "rpm -qa --queryformat '%{NAME} %{VERSION}-%{RELEASE} %{ARCH}\\n'";
        } elseif (PleskOS::isDebLike()) {
            $cmd = "dpkg-query --show --showformat '\${Package} \${Version} \${Architecture}\\n'";
        } else {
            return false;
        }

        if (!empty($glob)) {
            $cmd .= " '" . $glob . "' 2>/dev/null";
        }

        return $cmd;
    }

    /*
     * Fetches a list of installed packages that match given criteria.
     * string $glob - Glob (wildcard) pattern for coarse-grained packages selection from system package management backend. Empty $glob will fetch everything.
     * string $regexp - Package name regular expression for a fine-grained filtering of the results.
     * returns array of hashes with keys 'name', 'version' and 'arch', or false on error.
     */
    function listInstalled($glob, $regexp = null)
    {
        $cmd = PackageManager::buildListCmdLine($glob);
        if (!$cmd) {
            return array();
        }

        $output = Util::exec($cmd, $code);
        if ($code != 0) {
            return false;
        }

        $packages = array();
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            @list($pkgName, $pkgVersion, $pkgArch) = explode(" ", $line);
            if (empty($pkgName) || empty($pkgVersion) || empty($pkgArch))
                continue;
            if (!empty($regexp) && !preg_match($regexp, $pkgName))
                continue;
            $packages[] = array(
                'name' => $pkgName,
                'version' => $pkgVersion,
                'arch' => $pkgArch
            );
        }

        return $packages;
    }

    function isInstalled($glob, $regexp = null)
    {
        $packages = PackageManager::listInstalled($glob, $regexp);
        return !empty($packages);
    }
}

class Package
{
    function getManager($field, $package)
    {
        $redhat = 'rpm -q --queryformat \'%{' . $field . '}\n\' ' . $package;
        $debian = 'dpkg-query --show --showformat=\'${' . $field . '}\n\' '. $package . ' 2> /dev/null';
        $suse = 'rpm -q --queryformat \'%{' . $field . '}\n\' ' . $package;

        if (PleskOS::isRedHatLike()) {
            $manager = $redhat;
        } elseif (PleskOS::isDebLike()) {
            $manager = $debian;
        } elseif (PleskOS::isSuseLike()) {
            $manager = $suse;
        } else {
            return false;
        }

        return $manager;
    }

    /* DPKG doesn't supports ${Release}
     *
     */

    function getRelease($package)
    {
        $manager = Package::getManager('Release', $package);

        if (!$manager) {
            return false;
        }

        $release = Util::exec($manager, $code);
        if (!$code === 0) {
            return false;
        }
        return $release;
    }

    function getVersion($package)
    {
        $manager = Package::getManager('Version', $package);

        if (!$manager) {
            return false;
        }

        $version = Util::exec($manager, $code);
        if (!$code === 0) {
            return false;
        }
        return $version;
    }

}

class PleskOS
{
    public static function isSuse103()
    {
        return PleskOS::_detectOS('suse', '10.3');
    }

    public static function isUbuntu1204()
    {
        return PleskOS::_detectOS('ubuntu', '12.04');
    }

    public static function isDebLike()
    {
        if (PleskOS::_detectOS('ubuntu', '.*')
        || PleskOS::_detectOS('debian', '.*')
        ) {
            return true;
        }
        return false;
    }

    public static function isSuseLike()
    {
        if (PleskOS::_detectOS('suse', '.*')) {
            return true;
        }
        return false;
    }

    public static function isRedHatLike()
    {
        return (PleskOS::isRedHat() || PleskOS::isCentOS() || PleskOS::isCloudLinux() || PleskOS::isVZLinux());
    }

    public static function isRedHat()
    {
        if (PleskOS::_detectOS('red\s*hat', '.*')) {
            return true;
        }
        return false;
    }

    public static function isCloudLinux()
    {
        return PleskOS::_detectOS('CloudLinux', '.*');
    }

    public static function isCentOS()
    {
        if (PleskOS::_detectOS('centos', '.*')) {
            return true;
        }
        return false;
    }

    public static function isCentOS5()
    {
        if (PleskOS::_detectOS('centos', '5.*')) {
            return true;
        }
        return false;
    }

    public static function isVZLinux()
    {
        if (PleskOS::_detectOS('virtuozzo', '.*')) {
            return true;
        }
        return false;
    }
 
    public static function _detectOS($name, $version)
    {
        foreach (array(PleskOs::catPsaVersion(), PleskOS::catEtcIssue()) as $output) {
            if (preg_match("/{$name}[\s]+$version/i", $output)) {
                return true;
            }
        }
        return false;
    }

    public static function catPsaVersion()
    {
        if (is_file('/usr/local/psa/version')) {
            $cmd = 'cat /usr/local/psa/version';
        } elseif (is_file('/opt/psa/version')) {
            $cmd = 'cat /opt/psa/version';
        } else {
            return '';
        }
        $output = Util::exec($cmd, $code);

        return $output;
    }

    public static function catEtcIssue()
    {
        $cmd = 'cat /etc/issue';
        $output = Util::exec($cmd, $code);

        return $output;
    }

    public static function detectSystem()
    {
        $log = Log::getInstance('Detect system configuration');
        $log->info('OS: ' . (Util::isLinux() ? PleskOS::catEtcIssue() : 'Windows'));
        $log->info('Arch: ' . Util::getArch());
    }
}

class PleskValidator
{
    function isValidIp($value)
    {
        if (!is_string($value)) {
            return false;
        }
        if (!PleskValidator::validateIPv4($value) && !PleskValidator::validateIPv6($value)) {
            return false;
        }
        return true;
    }

    function validateIPv4($value)
    {
        $ip2long = ip2long($value);
        if ($ip2long === false) {
            return false;
        }

        return $value == long2ip($ip2long);
    }

    function validateIPv6($value)
    {
        if (strlen($value) < 3) {
            return $value == '::';
        }

        if (strpos($value, '.')) {
            $lastcolon = strrpos($value, ':');
            if (!($lastcolon && PleskValidator::validateIPv4(substr($value, $lastcolon + 1)))) {
                return false;
            }

            $value = substr($value, 0, $lastcolon) . ':0:0';
        }

        if (strpos($value, '::') === false) {
            return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
        }

        $colonCount = substr_count($value, ':');
        if ($colonCount < 8) {
            return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
        }

        // special case with ending or starting double colon
        if ($colonCount == 8) {
            return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
        }

        return false;
    }
}

class CheckRequirements
{
    function validate()
    {
        if (!PleskInstallation::isInstalled()) {
            //:INFO: skip chking mysql extension if plesk is not installed
            return;
        }

        $reqExts = array();
        foreach ($reqExts as $name) {
            $status = extension_loaded($name);
            if (!$status) {
                $this->_fail("PHP extension {$name} is not installed");
            }
        }
    }

    function _fail($errMsg)
    {
        echo '===Checking requirements===' . PHP_EOL;
        echo PHP_EOL . 'Error: ' . $errMsg . PHP_EOL;
        exit(1);
    }
}

class GetOpt
{
    var $_argv = null;
    var $_adminDbPasswd = null;

    public function __construct()
    {
        $this->_argv = $_SERVER['argv'];
        if (empty($this->_argv[1]) && Util::isLinux()) {
            $this->_adminDbPasswd = Util::retrieveAdminMySQLDbPassword();
        } else {
            $this->_adminDbPasswd = $this->_argv[1];
        }
    }

    function validate()
    {
        if (empty($this->_adminDbPasswd) && PleskInstallation::isInstalled()) {
            echo 'Please specify Plesk database password';
            $this->_helpUsage();
        }
    }

    function getDbPasswd()
    {
        return $this->_adminDbPasswd;
    }

    function _helpUsage()
    {
        echo PHP_EOL . "Usage: {$this->_argv[0]} <plesk_db_admin_password>" . PHP_EOL;
        exit(1);
    }
}

function fatal($msg)
{
    $log = Log::getInstance();
    $log->fatal($msg);
    exit(1);
}

$log = Log::getInstance();

//:INFO: Validate options
$options = new GetOpt();
$options->validate();

//:INFO: Validate PHP requirements, need to make sure that PHP extensions are installed
$checkRequirements = new CheckRequirements();
$checkRequirements->validate();

//:INFO: Validate Plesk installation
$pleskInstallation = new PleskInstallation();
$pleskInstallation->validate();

//:INFO: Detect system
$pleskOs = new PleskOS();
$pleskOs->detectSystem();

//:INFO: Need to make sure that given db password is valid
if (PleskInstallation::isInstalled()) {
    $log->step('Validating the database password');
    $pleskDb = PleskDb::getInstance();
    $log->resultOk();
}

//:INFO: Dump script version
$log->step('Pre-Upgrade analyzer version: ' . PRE_UPGRADE_SCRIPT_VERSION);

//:INFO: Validate known OS specific issues with recommendation to avoid bugs in Plesk
$pleskKnownIssues = new Plesk10KnownIssues();
$pleskKnownIssues->validate();

$plesk175Requirements = new Plesk175Requirements();
$plesk175Requirements->validate();

$plesk178Requirements = new Plesk178Requirements();
$plesk178Requirements->validate();

$log->dumpStatistics();
$log->writeJsonFile();

if ($log->getEmergency() > 0) {
    exit(2);
}

if ($log->getErrors() > 0 || $log->getWarnings() > 0) {
    exit(1);
}
// vim:set et ts=4 sts=4 sw=4:
