<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:tei="http://www.tei-c.org/ns/1.0">
 <xsl:output method="html" omit-xml-declaration="no"/>

<!-- tous les elements -->
<xsl:template match="*">
<xsl:element name="tei:{local-name()}">
<xsl:apply-templates select="@* | node()"/>
</xsl:element>
 </xsl:template>

<!-- la racine -->
 <xsl:template match="/*">
<tei:TEI version="5.0" n="0" xml:lang="de">
<xsl:apply-templates select="@* | node()"/>
</tei:TEI>
 </xsl:template>



<!-- les attributs -->
<xsl:template match="@*">
<xsl:copy/>
</xsl:template>

</xsl:stylesheet>
