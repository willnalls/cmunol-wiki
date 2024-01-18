<?php if(!defined('PmWiki')) exit();

 /**
 *  Copyright 2010 Carlos A. Bonamigo <cabsec.pmwiki@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 **/

/// VARIABLES

$RecipeInfo['xmlexport']['version'] = '20110329';
$HandleActions['xmlexport'] = 'HandleXmlExport';
$HandleAuth['xmlexport'] = 'read';

$HandleActions['xmlexportschema'] = 'HandleXmlExportSchema';
$HandleAuth['xmlexportschema'] = 'admin';

SDV($FmtPV['$Charset'] , '$GLOBALS["Charset"]');
SDV($FmtPV['$Revisions'] , "\$page['rev']");
SDV($FmtPV['$LastModTime'] , "strftime('%s',\$page['time'])");
SDV($FmtPV['$Contributors'] , "XmlExportPageContributors(\$pagename)");

SDV($XmlExportSchemaFile, './pub/schema/xmlexport.xsd');

SDV($XmlExportHeaders, array('Expires: Tue, 01 Jan 2002 00:00:00 GMT',
     'Cache-Control: no-store, no-cache, must-revalidate',
     'Content-Type: text/xml; charset=.{$Charset}'
));

SDV($XmlExportXml , '<?xml version="1.0" encoding="{$Charset}"?>
<wikipage xmlns="{$HostUrl}"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="{$PubDirUrl}/schemas xmlexport.xsd">
<title>{$Title}</title>
<titlespaced>{$Titlespaced}</titlespaced>
<group>{$Group}</group>
<groupspaced>{$Groupspaced}</groupspaced>
<name>{$Name}</name>
<namespaced>{$Namespaced}</namespaced>
<basename>{$BaseName}</basename>
<fullname>{$FullName}</fullname>
<pageurl>{$PageUrl}</pageurl>
<description>{$Description}</description>
<contributors>{$Contributors}</contributors>
<lastcontributor>{$LastModifiedBy}</lastcontributor>
<lastmodification>{$LastModTime}</lastmodification>
<lasthost>{$LastModifiedHost}</lasthost>
<lastsummary>{$LastModifiedSummary}</lastsummary>
<revisions>{$Revisions}</revisions>
<pmwikiversion>{$Version}</pmwikiversion>
<pmwikiversionnum>{$VersionNum}</pmwikiversionnum>
<markuptext>{$MarkupText}</markuptext>
</wikipage>
');

// almost no restrictions in the schema
// but you can expand it. 
SDV($XmlExportSchema , '<?xml version="1.0"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"
targetNamespace="{$HostUrl}"
xmlns="{$HostUrl}"
elementFormDefault="qualified">

<xs:element name="wikipage">
  <xs:complexType>
    <xs:sequence>
      <xs:element name="title" type="xs:string"/>
      <xs:element name="group" type="xs:string"/>
      <xs:element name="groupspaced" type="xs:string"/>
      <xs:element name="name" type="xs:string"/>
      <xs:element name="namespaced" type="xs:string"/>
      <xs:element name="basename" type="xs:string"/>
      <xs:element name="fullname" type="xs:string"/>
      <xs:element name="pageurl" type="xs:string"/>
      <xs:element name="description" type="xs:string"/>
      <xs:element name="contributors" type="xs:string"/>
      <xs:element name="lastcontributor" type="xs:string"/>
      <xs:element name="lastmodification" type="xs:integer"/>
      <xs:element name="lasthost" type="xs:string"/>
      <xs:element name="lastsummary" type="xs:string"/>
      <xs:element name="pmwikiversion" type="xs:string"/>
      <xs:element name="pmwikiversionnum" type="xs:integer"/>
      <xs:element name="markuptext" type="xs:string"/>
    </xs:sequence>
  </xs:complexType>
</xs:element>

<xs:element name="title">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="group">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="groupspaced">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="name">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="namespaced">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="basename">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="fullname">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="pageurl">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="description">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="contributors">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="lastcontributor">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="preserve"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="lastmodification">
  <xs:simpleType>
    <xs:restriction base="xs:integer"/>
  </xs:simpleType>
</xs:element>

<xs:element name="lasthost">
  <xs:simpleType>
    <xs:restriction base="xs:integer"/>
  </xs:simpleType>
</xs:element>

<xs:element name="lastsummary">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="pmwikiversion">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="replace"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

<xs:element name="pmwikiversionnum">
  <xs:simpleType>
    <xs:restriction base="xs:integer"/>
  </xs:simpleType>
</xs:element>

<xs:element name="markuptext">
  <xs:simpleType>
    <xs:restriction base="xs:string">
      <xs:whiteSpace value="preserve"/>
    </xs:restriction>
  </xs:simpleType>
</xs:element>

</xs:schema>
');

/// FUNCTIONS

function XmlExportPageContributors( $pagename, $level='read' ) {
   $page = RetrieveAuthPage( $pagename, $level, false );
  if(!is_array($page))return;
  foreach($page as $key => $value)
    if(strstr($key,'author'))
      $authors[$value]= 1;
  if(!is_array($authors))return;
  foreach($authors as $key => $value){
    if($key == '') $key = "anonymous";
    $rauthors .= $key.',';
  }
  if($rauthors{0} != ','){
    $rauthors = substr($rauthors,0,-1);
    return $rauthors;
  }else{
   return 'anonymous';
  }
}

function XmlExportHostUrn($x){
$x = explode('/',$x);
$urn = $x[2];
return $urn;
}

/// HANDLERS

function HandleXmlExport($pagename,$auth='read'){

  global $XmlExportXml,$XmlExportHeaders,
      $ScriptUrl, $PubDirUrl;
  $page = RetrieveAuthPage($pagename, $auth, true, READPAGE_CURRENT);
  
  if(!$page) Abort("Insufficient permissions");

  if(!PageExists($pagename)) return;
  
  $HostUrl = "http://".XmlExportHostUrn($ScriptUrl)."/";
  $MarkupText = htmlspecialchars($page["text"]);
  $PageXmlExportFmt = preg_replace('/\{\$HostUrl\}/',$HostUrl,$XmlExportXml);
  $PageXmlExportFmt = preg_replace('/\{\$PubDirUrl\}/',$PubDirUrl,$PageXmlExportFmt);
  $PageXmlExportFmt = preg_replace('/\{\$MarkupText\}/',$MarkupText,$PageXmlExportFmt);
  $PageXmlExportFmt = FmtPageName($PageXmlExportFmt, $pagename);
  
  foreach($XmlExportHeaders as $h){
    $h = FmtPageName($h,$pagename);
    header($h);
  }

  echo $PageXmlExportFmt;
}

function HandleXmlExportSchema($pagename,$auth='admin'){
  global $XmlExportHeaders,$XmlExportSchema, $XmlExportSchemaFile,
        $ScriptUrl;

  $page = RetrieveAuthPage($pagename, $auth, true, READPAGE_CURRENT);
  if(!$page) Abort("Insufficient permissions");

  $HostUrl = "http://".XmlExportHostUrn($ScriptUrl)."/";
  $XmlExportSchema = preg_replace('/\{\$HostUrl\}/',$HostUrl,$XmlExportSchema);
  mkdirp(dirname($XmlExportSchemaFile));
  file_put_contents($XmlExportSchemaFile,$XmlExportSchema);

  foreach($XmlExportHeaders as $h){
    $h = FmtPageName($h,$pagename);
    header($h);
  }

  echo file_get_contents($XmlExportSchemaFile);
}