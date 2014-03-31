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
                <xsl:if test="./active = 1">
                    <xsl:attribute name="class">active</xsl:attribute>
                </xsl:if>
                <a>
                    <xsl:attribute name="href">
                        <xsl:value-of select="./url"/>
                    </xsl:attribute>
                    <xsl:value-of select="./text"/>
                </a>
            </li>
        </xsl:for-each>
        </ul>
    </xsl:template>
    <xsl:template match="telement[@id='Title']">
        <h1>
            <xsl:value-of select="./variable/title"/>
            <small>
                <xsl:value-of select="./variable/subtitle"/>
            </small>
        </h1>
    </xsl:template>
    <xsl:template match="telement[@id='Navbar']">
        <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
            <div class="container-fluid">
                <div class="navbar-header">
                    <a class="navbar-brand">
                        <xsl:attribute name="href"><xsl:value-of select="./variable/variable/url"/></xsl:attribute>
                        <xsl:value-of select="./variable/variable/text"/>
                    </a>
                </div>
                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
                        <xsl:for-each select="./variable/variable[position() != 1]">
                            <li>
                                <xsl:if test="./active = 1">
                                    <xsl:attribute name="class">active</xsl:attribute>
                                </xsl:if>
                                <a>
                                    <xsl:attribute name="href"><xsl:value-of select="./url"/></xsl:attribute>
                                    <xsl:value-of select="./text"/>
                                </a>
                            </li>
                        </xsl:for-each>
                    </ul>
                </div>
            </div>
        </nav>
    </xsl:template>
    <xsl:template match="telement[@id='Post-Breadcrumbs']">
        <ol class="breadcrumb">
           <xsl:for-each select="./variable">
            <li>
                <a><xsl:value-of select="./variable"/></a>
            </li>
           </xsl:for-each>
        </ol>
    </xsl:template>
    <xsl:template match="telement[@id='LabelValuePairs']">
        <xsl:for-each select="./variable/variable">
            <span class="label label-info">
                <xsl:value-of select="./data"/>
            </span>
            <xsl:value-of select="./label"/>
            <br></br>
        </xsl:for-each>
    </xsl:template>
    <xsl:template match="telement[@id='Form']">
        <form>
            <xsl:attribute name="name"><xsl:value-of select="./variable/name"/></xsl:attribute>
            <xsl:attribute name="action"><xsl:value-of select="./variable/action"/></xsl:attribute>
            <xsl:attribute name="method"><xsl:value-of select="./variable/method"/></xsl:attribute>
            <xsl:attribute name="formtarget"><xsl:value-of select="./variable/formtarget"/></xsl:attribute>
            <xsl:attribute name="onsubmit"><xsl:value-of select="./variable/onsubmit"/></xsl:attribute>
            <xsl:for-each select="./variable/elements/variable">
               <xsl:call-template name="FormElement"/>
            </xsl:for-each>
        </form>
    </xsl:template>
    <xsl:template name="FormElement" match="telement[@id='FormElement']">
        <label>
        <xsl:attribute name="for"><xsl:value-of select="./name"/></xsl:attribute>
        <xsl:value-of select="./label"/>
        </label>
        <input>
            <xsl:attribute name="name"><xsl:value-of select="./name"/></xsl:attribute>
            <xsl:attribute name="type"><xsl:value-of select="./type"/></xsl:attribute>
            <xsl:attribute name="value"><xsl:value-of select="./value"/></xsl:attribute>
        </input>
        <br></br>
    </xsl:template>
</xsl:stylesheet>
