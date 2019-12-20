<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:tei="http://www.tei-c.org/ns/1.0">
 <xsl:output method="html" omit-xml-declaration="no"/>

<!-- tous les elements -->
<xsl:template match="*">
	<xsl:choose>
	<xsl:when test="local-name() = 'note'"></xsl:when>
	<xsl:otherwise>
		<xsl:apply-templates select="node()"/>
	</xsl:otherwise>
</xsl:choose>

</xsl:template>

<!-- la racine -->
 <xsl:template match="/">
	<xsl:apply-templates select="@* | node()"/>
	<xsl:apply-templates select="//tei:note" mode="notes"/>
 </xsl:template>

<xsl:template match="tei:note" mode="notes">
	<xsl:apply-templates select="node()"/>
</xsl:template>

</xsl:stylesheet>
