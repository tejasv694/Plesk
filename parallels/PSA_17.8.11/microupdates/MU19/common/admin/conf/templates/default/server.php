<?php
/**
 * @var Template_VariableAccessor $VAR
 */
echo AUTOGENERATED_CONFIGS;
?>

<?php if ($VAR->server->webserver->apache->useNameVirtualHost): ?>
<?php echo $VAR->includeTemplate('server/nameVirtualHost.php', array(
    'ssl' => false,
)) ?>
<?php echo $VAR->includeTemplate('server/nameVirtualHost.php', array(
    'ssl' => true,
)) ?>
<?php endif; ?>

<?php if ($VAR->server->fullHostName): ?>
ServerName "<?php echo $VAR->server->fullHostName ?>"
<?php endif; ?>

DocumentRoot "<?php echo $VAR->server->webserver->httpDir ?>"

<IfModule mod_logio.c>
    LogFormat "<?php echo $VAR->server->webserver->apache->pipelogEnabled ? '%v@@%p@@' : ''?>%a %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\"" plesklog
</IfModule>
<IfModule !mod_logio.c>
    LogFormat "<?php echo $VAR->server->webserver->apache->pipelogEnabled ? '%v@@%p@@' : ''?>%a %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" plesklog
</IfModule>
<?php if ($VAR->server->webserver->apache->pipelogEnabled): ?>
CustomLog  "|<?php echo $VAR->server->productRootDir ?>/admin/sbin/pipelog <?php echo $VAR->server->webserver->httpsPort ?>" plesklog
<?php endif; ?>

<?php echo $VAR->includeTemplate('server/PCI_compliance.php') ?>

<Directory "<?php echo $VAR->server->webserver->vhostsDir ?>">
    AllowOverride "<?php echo $VAR->server->webserver->apache->allowOverrideDefault ?>"
    Options SymLinksIfOwnerMatch
<?php if ($VAR->server->webserver->apache->useRequireOption): ?>
    Require all granted
<?php endif; ?>
    Order allow,deny
    Allow from all

<?php echo $VAR->includeTemplate('service/php.php', array(
    'enabled' => false,
)) ?>

</Directory>

<Directory "<?php echo $VAR->server->mailman->rootDir ?>">
    AllowOverride All
    Options SymLinksIfOwnerMatch
<?php if ($VAR->server->webserver->apache->useRequireOption): ?>
    Require all granted
<?php else: ?>
    Order allow,deny
    Allow from all
<?php endif; ?>
    <IfModule <?php echo $VAR->server->webserver->apache->php4ModuleName ?>>
        php_admin_flag engine off
    </IfModule>
    <IfModule mod_php5.c>
        php_admin_flag engine off
    </IfModule>
</Directory>

<?php if (!$VAR->server->webserver->proxyActive): ?>
<IfModule mod_headers.c>
    Header add X-Powered-By PleskLin
</IfModule>
<?php endif ?>

<IfModule mod_security2.c>
<?php echo $VAR->server->webserver->webAppFirewallSettings ?>
</IfModule>

<?php echo $VAR->server->webserver->includeOptionalConfig($VAR->server->webserver->ipDefaultConfigs) ?>

<?php echo $VAR->includeTemplate('server/vhosts.php', array(
    'ssl' => false,
    'ipLimit' => $VAR->server->webserver->apache->vhostIpCapacity,
)) ?>
<?php echo $VAR->includeTemplate('server/vhosts.php', array(
    'ssl' => true,
    'ipLimit' => 1,
)) ?>

<?php echo $VAR->includeTemplate('server/mailman.php') ?>

<?php echo $VAR->includeTemplate('server/rpaf.php', array('mod' => 'mod_rpaf.c')); ?>

<?php echo $VAR->includeTemplate('server/rpaf.php', array('mod' => 'mod_rpaf-2.0.c')); ?>

<?php echo $VAR->includeTemplate('server/remoteip.php', array('mod' => 'mod_remoteip.c')); ?>
