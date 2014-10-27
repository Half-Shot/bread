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
    <xsl:template match="telement[@id='Panel']">
        <div class="panel">
        <xsl:if test="./variable/title">
            <xsl:if test="./variable/title != 0">
                <h3 class="panel-title"><xsl:value-of select="./variable/title"/></h3>
                <hr/>
            </xsl:if>
        </xsl:if>
        <p>
            <xsl:value-of select="./variable/body"/>
        </p>
            <xsl:if test="./variable/footer != 0">
                <hr/>
                <div class="panel-footer">
                  <xsl:value-of select="./variable/footer"/>
                </div>
            </xsl:if>
      </div>
    </xsl:template>
    <xsl:template match="telement[@id='VerticalNavbar']">
        <ul class="side-nav">
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
        <nav class="top-bar" data-topbar="" role="navigation">
            <ul class="title-area">
              <li class="name">
                <h1>
                    <a href="#">
                        <xsl:attribute name="href"><xsl:value-of select="./variable/variable/url"/></xsl:attribute>
                        <xsl:value-of select="./variable/variable/text"/>
                    </a>
                </h1>
              </li>
               <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
              <li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
            </ul>
            <section class="top-bar-section">

                <!-- Left Nav Section -->
                <ul class="left">
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
                <ul class="right">
                    <xsl:for-each select="./variable/inner/variable">
                        <li><xsl:value-of select="."/></li>
                    </xsl:for-each>
                </ul>
            </section>
        </nav>
    </xsl:template>
    
    <xsl:template match="telement[@id='LabelValuePairs']">
        <xsl:for-each select="./variable/variable">
            <p>
                <xsl:value-of select="./label"/>
                <span class="label">
                    <xsl:value-of select="./data"/>
                </span>
            </p>
        </xsl:for-each>
    </xsl:template>
    
    
    <xsl:template match="telement[@id='Form']">
        <form>
            <xsl:attribute name="name"><xsl:value-of select="./variable/name"/></xsl:attribute>
            <xsl:attribute name="action"><xsl:value-of select="./variable/action"/></xsl:attribute>
            <xsl:attribute name="method"><xsl:value-of select="./variable/method"/></xsl:attribute>
            <xsl:attribute name="formtarget"><xsl:value-of select="./variable/formtarget"/></xsl:attribute>
            <xsl:attribute name="onsubmit"><xsl:value-of select="./variable/onsubmit"/></xsl:attribute>
            <xsl:attribute name="id"><xsl:value-of select="./variable/id"/></xsl:attribute>
            <xsl:for-each select="./variable/elements/variable">
                <div class="row">
                    <xsl:call-template name="FormElement"/>
                </div>
            </xsl:for-each>
        </form>
    </xsl:template>
    
    <xsl:template name="FormElement" match="telement[@id='FormElement']">
        <xsl:choose>
            <xsl:when test="./type = 'rawhtml'">
                <xsl:value-of select="./value"/>
            </xsl:when>
            <xsl:when test="./type = 'button'">
                <button>
                    <xsl:attribute name="action"><xsl:value-of select="./action"/></xsl:attribute>
                    <xsl:attribute name="onclick"><xsl:value-of select="./onclick"/></xsl:attribute>
                    <xsl:if test="./id != 0">
                        <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                    </xsl:if>
                    <xsl:if test="./form != 0">
                        <xsl:attribute name="form"><xsl:value-of select="./form"/></xsl:attribute>
                    </xsl:if>
                    <xsl:if test="./pattern != 0">
                        <xsl:attribute name="pattern"><xsl:value-of select="./pattern"/></xsl:attribute>
                        <xsl:if test="./pattern_help != 0">
                            <xsl:attribute name="title"><xsl:value-of select="./pattern_help"/></xsl:attribute>
                        </xsl:if>
                    </xsl:if>
                    <xsl:if test="./readonly = 1">
                        <xsl:attribute name="disabled"/>
                    </xsl:if>
                    <xsl:if test="./hidden = 1">
                        <xsl:attribute name="style">display:none;</xsl:attribute>
                        <xsl:attribute name="hidden">true</xsl:attribute>
                    </xsl:if>
                    <xsl:if test="./toggle = 1">
                        <xsl:attribute name="data-toggle">button</xsl:attribute>
                    </xsl:if>
                    <xsl:attribute name="class">button form-control <xsl:value-of select="./class"/></xsl:attribute>
                    <xsl:value-of select="./value"/>
                    <xsl:if test="./variable/tooltip = 1">
                        <xsl:attribute name="data-toggle">tooltip</xsl:attribute>
                        <xsl:attribute name="data-placement"><xsl:value-of select="./variable/tooltipDirection"/></xsl:attribute>
                        <xsl:attribute name="title"><xsl:value-of select="./variable/tooltipText"/></xsl:attribute>
                    </xsl:if>
                </button>
            </xsl:when>
            <xsl:when test="./type = 'dropdown'">
                <select>
                    <xsl:if test="./form != 0">
                        <xsl:attribute name="form"><xsl:value-of select="./form"/></xsl:attribute>
                    </xsl:if>
                    
                    <xsl:if test="./pattern != 0">
                        <xsl:attribute name="pattern"><xsl:value-of select="./pattern"/></xsl:attribute>
                        <xsl:if test="./pattern_help != 0">
                            <xsl:attribute name="title"><xsl:value-of select="./pattern_help"/></xsl:attribute>
                        </xsl:if>
                    </xsl:if>
                    
                    <xsl:choose>
                        
                        <xsl:when test="./name = ''">
                            <xsl:attribute name="name"><xsl:value-of select="./id"/></xsl:attribute>
                        </xsl:when>
                        
                        <xsl:otherwise>
                            <xsl:attribute name="name"><xsl:value-of select="./name"/></xsl:attribute>
                        </xsl:otherwise>
                        
                    </xsl:choose>
                    
                    <xsl:attribute name="onclick"><xsl:value-of select="./onclick"/></xsl:attribute>
                    <xsl:attribute name="value"><xsl:value-of select="./value"/></xsl:attribute>
                    <xsl:if test="./multiple = 1">
                        <xsl:attribute name="multiple"/>
                    </xsl:if>
                    <xsl:if test="./id != ''">
                        <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                    </xsl:if>
                    <xsl:attribute name="class">form-control <xsl:value-of select="./class"/></xsl:attribute>
                    <xsl:for-each select="./dataset/variable">
                        <option>
                                <xsl:value-of select="."/>
                        </option>
                    </xsl:for-each>
                    <xsl:if test="./variable/hidden = 1">
                        <xsl:attribute name="hidden">true</xsl:attribute>
                    </xsl:if>
                </select>
            </xsl:when>
            <xsl:otherwise>
            <input>
                <xsl:choose>
                    <xsl:when test="./name = ''">
                        <xsl:attribute name="name"><xsl:value-of select="./id"/></xsl:attribute>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:attribute name="name"><xsl:value-of select="./name"/></xsl:attribute>
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:if test="./variable/hidden = 1">
                    <xsl:attribute name="hidden">true</xsl:attribute>
                </xsl:if>
                <xsl:attribute name="type"><xsl:value-of select="./type"/></xsl:attribute>
                <xsl:attribute name="value"><xsl:value-of select="./value"/></xsl:attribute>
                <xsl:attribute name="onclick"><xsl:value-of select="./onclick"/></xsl:attribute>
                <xsl:if test="./id != ''">
                    <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="./form != 0">
                    <xsl:attribute name="form"><xsl:value-of select="./form"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="./type = 'checkbox'">
                    <xsl:if test="./value = '1'">
                        <xsl:attribute name="checked">checked</xsl:attribute>
                    </xsl:if>
                </xsl:if>
                <xsl:if test="./readonly = 1">
                    <xsl:attribute name="readonly"/>
                </xsl:if>
                <xsl:if test="./required = 1">
                    <xsl:attribute name="required"/>
                    <xsl:if test="./pattern != 0">
                        <xsl:attribute name="pattern"><xsl:value-of select="./pattern"/></xsl:attribute>
                        <xsl:if test="./pattern_help != 0">
                            <xsl:attribute name="title"><xsl:value-of select="./pattern_help"/></xsl:attribute>
                        </xsl:if>
                    </xsl:if>
                </xsl:if>
                <xsl:attribute name="placeholder"><xsl:value-of select="./placeholder"/></xsl:attribute>
                <xsl:attribute name="class">form-control <xsl:value-of select="./class"/></xsl:attribute>
            </input>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    <xsl:template name="InputElement" match="telement[@id='InputElement']">
        <xsl:for-each select="./variable">
           <xsl:call-template name="FormElement"/>
        </xsl:for-each>
    </xsl:template>
    
    <xsl:template name="ErrorScreen" match="telement[@id='ErrorScreen']">
            <xsl:if test="./variable/severity &lt; 3">
                <div class="panel">
                    <div class="panel-heading"><h2>Oh Noes - An error occured!</h2></div>
                      <div class="panel-body">
                          <p>
                              <b>Time:</b>
                              <xsl:value-of select="./variable/time"/>
                          </p>
                          <p>
                              <b>Category:</b>
                              <xsl:value-of select="./variable/category"/>
                          </p>
                          <p>
                              <xsl:value-of select="./variable/message"/>
                          </p>
                      </div>
                </div>
            </xsl:if>
            <xsl:if test="./variable/severity &gt; 2">
                <div class="panel callout">
                      <div class="panel-heading">Oh Noes - An error occured!</div>
                      <div class="panel-body">
                          <p>
                              <b>Time:</b>
                              <xsl:value-of select="./variable/time"/>
                          </p>
                          <p>
                              <b>Category:</b>
                              <xsl:value-of select="./variable/category"/>
                          </p>
                          <p>
                              <xsl:value-of select="./variable/message"/>
                          </p>
                      </div>
                </div>
            </xsl:if>
    </xsl:template>
    <xsl:template name="Modal" match="telement[@id='Modal']">
        <!-- Modal -->
        <div class="reveal-modal" data-reveal="">
            <xsl:attribute name="id">
                <xsl:value-of select="./variable/id"/>
            </xsl:attribute>
            <xsl:attribute name="style">
                width:<xsl:value-of select="./variable/width"/>%;
            </xsl:attribute>
            <div class="modal-header">
                <a class="close-reveal-modal" data-dismiss="modal" aria-hidden="true"><i class="fi-x-circle medium"></i></a>
                <h4 class="modal-title"><xsl:value-of select="./variable/title"/></h4>
            </div>
            <div class="modal-body">
                <xsl:value-of select="./variable/body"/>
            </div>
            <div class="modal-footer">
                <xsl:value-of select="./variable/footer"/>
            </div>
        </div>
    </xsl:template>
    
    <xsl:template name="Button" match="telement[@id='Button']">
          <a>
                <xsl:attribute name="name"><xsl:value-of select="./variable/name"/></xsl:attribute>
                <xsl:if test="./variable/id != ''">
                    <xsl:attribute name="id"><xsl:value-of select="./variable/id"/></xsl:attribute>
                </xsl:if>
                <xsl:attribute name="class">button <xsl:value-of select="./variable/class"/></xsl:attribute>
                <xsl:attribute name="onclick"><xsl:value-of select="./variable/onclick"/></xsl:attribute>
                <xsl:if test="./variable/form != ''">
                    <xsl:attribute name="form"><xsl:value-of select="./variable/form"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="./variable/hidden = 1">
                    <xsl:attribute name="hidden">true</xsl:attribute>
                </xsl:if>
                <xsl:if test="./variable/tooltip = 1">
                    <xsl:attribute name="data-toggle">tooltip</xsl:attribute>
                    <xsl:attribute name="data-placement"><xsl:value-of select="./variable/tooltipDirection"/></xsl:attribute>
                    <xsl:attribute name="title"><xsl:value-of select="./variable/tooltipText"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="./variable/readonly = 1">
                    <xsl:attribute name="disabled"/>
                </xsl:if>
                <xsl:if test="./variable/toggle = 1">
                    <xsl:attribute name="data-toggle">button</xsl:attribute>
                </xsl:if>
                <xsl:if test="./variable/type != ''">
                    <xsl:attribute name="type"><xsl:value-of select="./variable/type"/></xsl:attribute>
                </xsl:if>
                <xsl:value-of select="./variable/value"/>
          </a>
    </xsl:template>
    
    <xsl:template name="Label" match="telement[@id='Label']">
        <span>
            <xsl:attribute name="class">label <xsl:value-of select="./variable/type"/></xsl:attribute>
            <xsl:value-of select="./variable/value"/>
        </span>
    </xsl:template>
    
    <xsl:template name="Badge" match="telement[@id='Badge']">
        <span>
            <xsl:attribute name="class">label round <xsl:value-of select="./variable/type"/></xsl:attribute>
            <xsl:value-of select="./variable/value"/>
        </span>
    </xsl:template>
    
    <xsl:template match="telement[@id='Breadcrumbs']">
        <ul class="breadcrumbs">
            <xsl:for-each select="./variable/variable">
                <li>
                    <xsl:if test="./active = 1">
                        <xsl:attribute name="class">current </xsl:attribute>
                    </xsl:if>
                    <a href="#">
                        <xsl:if test="./url">
                            <xsl:attribute name="href"><xsl:value-of select="./url"/></xsl:attribute>
                        </xsl:if>
                        <xsl:value-of select="./value"/>
                    </a>
                </li>
            </xsl:for-each>
        </ul>
    </xsl:template>
    
    <xsl:template match="telement[@id='Comment']">
        <div class="media">
            <xsl:if test="./variable/id != ''">
                <xsl:attribute name="id"><xsl:value-of select="./variable/id"/></xsl:attribute>
            </xsl:if>
            <xsl:if test="./variable/class != ''">
                <xsl:attribute name="class">media <xsl:value-of select="./variable/class"/></xsl:attribute>
            </xsl:if>
            <div class="row">
                <div class="small-2 columns">
                    <xsl:if test="./variable/thumbnail != ''">
                        <a class="th" href="#">
                        <xsl:if test="./variable/thumbnailurl">
                            <xsl:attribute name="href"><xsl:value-of select="./variable/thumbnailurl"/></xsl:attribute>
                        </xsl:if>
                        <img href="#">
                            <xsl:attribute name="src"><xsl:value-of select="./variable/thumbnail"/></xsl:attribute>
                        </img>
                        </a>
                    </xsl:if>
                </div>
                <div class="small-10 columns">
                    <a>
                        <xsl:if test="./variable/headerurl">
                            <xsl:attribute name="href"><xsl:value-of select="./variable/headerurl"/></xsl:attribute>
                        </xsl:if>
                        <h4 class="media-heading">
                            <xsl:value-of select="./variable/header"/>
                        </h4>
                    </a>
                    <xsl:value-of select="./variable/body"/>
                </div>
            </div>
        </div>
    </xsl:template>
    
    <xsl:template match="telement[@id='Tabs']">
        <dl class="tabs">
            <xsl:for-each select="./variable/variable">
                <dd>
                    <xsl:if test="./active = 1">
                        <xsl:attribute name="class">active</xsl:attribute>
                    </xsl:if>
                    <a>
                        <xsl:attribute name="href"><xsl:value-of select="./url"/></xsl:attribute>
                        <xsl:value-of select="./text"/>
                    </a>
                </dd>
            </xsl:for-each>
        </dl>
    </xsl:template>   
    
    <xsl:template match="telement[@id='Collapse']">
        <div>
            <xsl:attribute name="name"><xsl:value-of select="./variable/name"/></xsl:attribute>
            <xsl:attribute name="id"><xsl:value-of select="./variable/id"/></xsl:attribute>
            <xsl:attribute name="class">panel-group <xsl:value-of select="./variable/class"/></xsl:attribute>
            <xsl:for-each select="./variable/panels/variable">
                <div class="panel panel-default">
                    <a data-toggle="collapse" data-parent="#accordion">
                        <xsl:attribute name="href">#<xsl:value-of select="./id"/></xsl:attribute>
                        <div class="panel-heading">
                            <xsl:attribute name="id"><xsl:value-of select="./id"/>-header</xsl:attribute>
                            <h4 class="panel-title">
                                <xsl:value-of select="./header"/>
                            </h4>
                        </div>
                    </a>
                    <div class="panel-collapse collapse">
                        <xsl:if test="position() = 1">
                            <xsl:attribute name="class">panel-collapse collapse in</xsl:attribute>
                        </xsl:if>
                        
                        <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                        <div class="panel-body">
                            <xsl:value-of select="./body"/>
                        </div>
                    </div>
                </div>
            </xsl:for-each>
        </div>
    </xsl:template>
    <xsl:template match="telement[@id='Table']">
        <table>
            <xsl:attribute name="name"><xsl:value-of select="./variable/name"/></xsl:attribute>
            <xsl:attribute name="id"><xsl:value-of select="./variable/id"/></xsl:attribute>
            <xsl:attribute name="class">table <xsl:value-of select="./variable/class"/></xsl:attribute>
            <thead>
                <xsl:attribute name="name"><xsl:value-of select="./variable/headingRow/name"/></xsl:attribute>
                <xsl:attribute name="id"><xsl:value-of select="./variable/headingRow/id"/></xsl:attribute>
                <xsl:attribute name="class"><xsl:value-of select="./variable/headingRow/class"/></xsl:attribute>
                <xsl:for-each select="./variable/headingRow/cells/variable">
                    <th>
                        <xsl:attribute name="name"><xsl:value-of select="./name"/></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                        <xsl:attribute name="class"><xsl:value-of select="./class"/></xsl:attribute>
                            <xsl:attribute name="style">width: <xsl:value-of select="./width"/>;</xsl:attribute>
                        <xsl:value-of select="./text"/>
                    </th>
                </xsl:for-each>
            </thead>
            <tbody>
                <xsl:for-each select="./variable/rows/variable">
                <tr>
                    <xsl:attribute name="name"><xsl:value-of select="./name"/></xsl:attribute>
                    <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                    <xsl:attribute name="class"><xsl:value-of select="./class"/></xsl:attribute>
                    <xsl:for-each select="./cells/variable">
                        <td>
                            <xsl:attribute name="name"><xsl:value-of select="./name"/></xsl:attribute>
                            <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                            <xsl:attribute name="class"><xsl:value-of select="./class"/></xsl:attribute>
                            <xsl:attribute name="style">width: <xsl:value-of select="./width"/>;</xsl:attribute>
                            <xsl:value-of select="./text"/>
                        </td>
                    </xsl:for-each>
                </tr>
                </xsl:for-each>
            </tbody>
        </table>
    </xsl:template>
    
    <xsl:template match="telement[@id='Dropdown']">
        <button aria-expanded="false">
            <xsl:attribute name="data-dropdown"><xsl:value-of select="./variable/id"/></xsl:attribute>
            <xsl:attribute name="aria-controls"><xsl:value-of select="./variable/id"/></xsl:attribute>
            <xsl:value-of select="./variable/value"/>
        </button>
        <ul data-dropdown-content="" aria-hidden="true" tabindex="-1">
            <xsl:attribute name="name"><xsl:value-of select="./variable/name"/></xsl:attribute>
            <xsl:attribute name="id"><xsl:value-of select="./variable/id"/></xsl:attribute>
            <xsl:attribute name="class">f-dropdown <xsl:value-of select="./variable/class"/></xsl:attribute>
                <xsl:for-each select="./variable/items/variable">
                    <xsl:choose>
                        <xsl:when test="./value = 'seperator'">
                            <li><hr/></li>
                        </xsl:when>
                        <xsl:otherwise>
                            <li>
                                <xsl:value-of select="."/>
                            </li>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:for-each>
        </ul>
    </xsl:template>
</xsl:stylesheet>
