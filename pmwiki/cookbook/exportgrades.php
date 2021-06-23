<?php if (!defined('PmWiki')) exit();


$HandleActions['exportgrades'] = 'ExportGrades';
$HandleAuth['exportgrades'] = 'admin';

// pages to skip when collecting profiles
$skip = ['Profiles.GroupAttributes','Profiles.Profiles','Profiles.RecentChanges'];

$pages = ListPages();
$gradebook = GetGradebookItems();
$articles = GetAllArticles();


// returns array of profiles
function GetProfiles(){
    global $pages;
    global $skip;
    $profiles = array();
    foreach ($pages as $k => $v) {
        if (preg_match('/Profiles/',$v)) {
            if (in_array($v,$skip)) { continue; }
            $profiles[] = $v;
        }
    }
    return $profiles;
}

// changes name of profile page to username
function ProfileToName($profile){
    $name = PageVar($profile,'$:name');
    return $name;
}


// collect all articles
function GetAllArticles(){
    global $pages;
    $articles = array();
    foreach ($pages as $k => $v) {
        if (preg_match('/Articles./',$v)) { 
            $articles[] = $v;
        }
    }
    return $articles;
}



// returns array of (gradebook articles, gradebook expansions)
function GetGradebookItems(){
    global $pages;
    $items = array();
    $items[] = array();
    $items[] = array();
    foreach ($pages as $k => $v) {
        if (preg_match('/GradebookArticles/',$v)) { 
            $items[0][] = $v;
        }
        if (preg_match('/GradebookExpansions/',$v)) { 
            $items[1][] = $v;
        }
    }
    return $items;
}



function CheckEssential($pagename,&$categories) {
    $category = "Category." . PageVar($pagename,'$:Category');
    $essential = PageVar($category,'$:essential');
    
    if ($essential == "no") {
        return false;
    }
    if (in_array($category,$categories)) {
        return false;
    } else {
        $categories[] = $category;
        return true;
    }
}



// given profile, returns array of (#ess articles, #noness articles, #ess extensions, #noness extensions, #comments) of completed work
function GetWork($profile, $auth){
    global $gradebook;
    global $articles;
    $gradebookarticles = $gradebook[0];
    $gradebookexpansions = $gradebook[1];
    $name = ProfileToName($profile);
    // $categories keeps track of which categories the essential items are from (only one essential item per category)
    $item_count = [0,0,0,0,0];
    $categories = array();
    // articles
    
    foreach($gradebookarticles as $k => $v){
        $pagename = $v;
        if (PageVar($pagename,'$:Status') != 'complete'){
            continue;
        }
        if (strtolower(PageVar($pagename,'$:Author')) != $name) {
            continue;
        }
        if (CheckEssential($pagename,$categories)) {
            $item_count[0] += 1;
        } else {
            $item_count[1] += 1;
        }
        
    }
    
    $categories = array();
    // extensions
    foreach($gradebookexpansions as $k => $v){
        $pagename = $v;
        if (PageVar($pagename,'$:Status') != 'complete'){
            continue;
        }
        if (strtolower(PageVar($pagename,'$:Author')) != $name) {
            continue;
        }
        if (CheckEssential($pagename,$categories)) {
            $item_count[2] += 1;
        } else {
            $item_count[3] += 1;
        }
    }
    
    // comments
    $flag = '!!!!!' . $name;
    $pattern = "/" . $flag . "/";
    foreach($articles as $k => $v){
        $pagename = $v;
        $page = RetrieveAuthPage($pagename, $auth);
        $text = $page['text'];
        if (!preg_match($pattern,$text)) {continue;}
        $item_count[4] += 1;
    }

    return $item_count;
}

function GetSectionAndID($profile, $auth){
    $section = PageVar($profile,'$:section');
    $id = PageVar($profile,'$:andrewid');

    // fix both (backslashes may come from profile pages)
    $id = str_replace(" \\\\","",$id);
    $section=

    $details = [$section,$id];
    return $details;
}




function ExportGrades($pagename, $auth) {
    $profiles = GetProfiles();
    $gradebook = array();
    
    // echo "Opening file...";
    $write = fopen("./pub/export/grades.csv","w");

    
    foreach ($profiles as $v) {
        echo $v;
        $work = GetWork($v,$auth);
        $sectid = GetSectionAndID($v,$auth);
        $gradebook[$v] = array();

        $gradebook[$v][] = $sectid[0];
        $gradebook[$v][] = $sectid[1];
        foreach ($work as $w) {
            $gradebook[$v][] = $w;
        }
        print_r($gradebook[$v]);
    }
    
    $header = "Username,Section,AndrewID,Essential Article Count,Non-essential Article Count,Essential Expansion Count,Non-essential Expansion Count,Comment Count";

    $exportstring = $header . "\n";
    
    foreach ($gradebook as $k => $v) {
        $name = ProfileToName($k);
        $exportstring .= $name . ",";
        foreach ($v as $n) {
            $exportstring .= $n . ",";
        }
        $exportstring .= "\n";
    }
    // echo nl2br($exportstring);
    fwrite($write,$exportstring);    
    HandleBrowse('Gradebook.Export');
}


