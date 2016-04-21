<?xml version="1.0" encoding="UTF-8"?>
<!--

LGPL  http://www.gnu.org/licenses/lgpl.html
© 2016 Frederic.Glorieux@fictif.org et LABEX OBVIL

Transfromation adhoc sur Du TEI

filename	creator	date	title	publisher	extent	description	identifier	source
-->
<xsl:transform version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.tei-c.org/ns/1.0"
  xmlns:tei="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="tei"
  >
  <xsl:output encoding="UTF-8" method="text" omit-xml-declaration="yes"/>
  <xsl:variable name="LF" select="'&#10;'"/>
  <xsl:variable name="TAB" select="'&#9;'"/>
  <xsl:param name="filename"/>
  <xsl:variable name="who1">ABCDEFGHIJKLMNOPQRSTUVWXYZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöùúûüý’' </xsl:variable>
  <xsl:variable name="who2">abcdefghijklmnopqrstuvwxyzaaaaaaceeeeiiiinooooouuuuyaaaaaaceeeeiiiinooooouuuuy--</xsl:variable>
  
  <xsl:template match="/">
    <xsl:variable name="author" select="/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author/@key"/>
    <xsl:variable name="date" select="/tei:TEI/tei:teiHeader/tei:profileDesc/tei:creation/tei:date/@when"/>
    <xsl:variable name="title" select="/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title"/>
    <xsl:text>http://obvil-dev.paris-sorbonne.fr/corpus/critique/</xsl:text>
    <xsl:value-of select="$filename"/>
    <xsl:text>.xml</xsl:text>
    <xsl:value-of select="$TAB"/>
    <xsl:value-of select="$author"/>
    <xsl:value-of select="$TAB"/>
    <xsl:value-of select="$date"/>
    <xsl:value-of select="$TAB"/>
    <xsl:value-of select="$title"/>
    <xsl:value-of select="$TAB"/>
    <xsl:value-of select="(/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:idno|/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:idno)[1]"/>
    <xsl:value-of select="$TAB"/>
    <xsl:value-of select="/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:publisher"/>
    <xsl:value-of select="$LF"/>
  </xsl:template>
</xsl:transform>