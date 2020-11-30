<?php
// Make sure that the IEM controller does NOT redirect request.
define('IEM_NO_CONTROLLER', true);

// Include index file
require_once(dirname(__FILE__).'/../../index.php');

header('Content-type: text/xml; charset='.SENDSTUDIO_CHARSET);
echo '<';
?>
?xml version="1.0" encoding="<?php echo SENDSTUDIO_CHARSET; ?>" <?php echo '?>'; ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/">
<html>
<head>
<title><xsl:value-of select="rss/channel/title"/></title>
<style>

body {
	font-size: 12px;
	color: black;
	line-height: 1.5;
	background-color: #F3F2E9;
	margin: 20px;
	font-family: tahoma;
}

.popupContainer {
	border: 1px #CAC7BD solid;
	background-color: #FFFFFF;
	padding: 20px
}

.Heading1
{
	font-size: 18px;
	font-weight: normal;
	font-family: Tahoma;
}

</style>
</head>
<body>
<div class="popupContainer">
    <div class="Heading1"><xsl:value-of select="rss/channel/title" disable-output-escaping="yes"/></div><br />
    <xsl:for-each select="rss/channel/item">
   <a href='{link}'><xsl:value-of select="title" disable-output-escaping="yes"/></a><br />
	<xsl:value-of select="description" disable-output-escaping="yes"/><br /><br />
    </xsl:for-each>
</div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>


