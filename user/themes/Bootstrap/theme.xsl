<?xml version="1.0" encoding="UTF-8"?>
<!--
    Document   : theme.xsl
    Created on : 28 March 2014, 15:29
    Author     : Half-Shot
    Description:
        Purpose of transformation follows.
-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="html"/>
    <xsl:template match="telement[@id='VerticalNavbar']">
        <ul class="nav nav-pills nav-stacked">
        <xsl:for-each select="./variable/variable">
            <li>
                <a>
                    <xsl:attribute name="href">
                        <xsl:value-of select="./variable[@id='url']"/>
                    </xsl:attribute>
                    <xsl:value-of select="./variable[@id='text']"/>
                </a>
            </li>
        </xsl:for-each>
        </ul>
    </xsl:template>
    <xsl:template match="telement[@id='HorizontalNavbar']">
        <h1>YOLO</h1>
        <h2>Swag</h2>
    </xsl:template>
</xsl:stylesheet>
