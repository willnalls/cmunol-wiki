<?php if (!defined('PmWiki')) exit();


$HandleActions['exportgrades'] = 'ExportGrades';
$HandleAuth['exportgrades'] = 'admin';

// pages to skip when collecting profiles
$skip = ['Profiles.GroupAttributes','Profiles.Profiles','Profiles.RecentChanges'];
$skip_categories = [];

// statuses and item types
$statuses = ["complete","ungraded","revised","needsrevised","nocredit"];
$items = ["articles","expansions"];


$DEBUG = -1;
$start = time();
function Now() {
    global $start;
    $duration = time() - $start;
    return $duration;
}

$pages = ListPages();


$articles = GetAllArticles();

function DPrint($notice,$lvl) {
    global $DEBUG;
    $string = "";
    for ($x = 0; $x < $lvl; $x++) {
        $string .= "--";
    }
    $string .= $lvl . "]  ";
    if ($lvl <= $DEBUG) {
        $string .= $notice . "\r\n";
        echo nl2br($string);
    }
    return;
}


// returns array of profiles
function GetProfiles(){
    DPrint("Getting Profiles...",0);
    global $pages;
    global $skip;
    $profiles = array();
    foreach ($pages as $k => $v) {
        if (preg_match('/Profiles/',$v)) {
            if (in_array($v,$skip)) { continue; }
            //if ($v == "Profiles.Profiles") { continue; }
            // $profile_name = ProfileToName($v);
            DPrint("Collecting profile " . $v,3);
            $profiles[] = $v;
        }
    }
    DPrint("Finished getting profiles at " . Now(),0);
    return $profiles;
}

// changes name of profile page to username
function ProfileToName($profile){
    DPrint("Changing profile " . $profile . " to name ", 4);
    $name = strtolower(substr($profile,9));
    DPrint("Name is " . $name,3);
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
    $items["articles"] = array();
    $items["expansions"] = array();
    foreach ($pages as $k => $v) {
        if (preg_match('/GradebookArticles/',$v)) { 
            $items["articles"][] = $v;
        }
        if (preg_match('/GradebookExpansions/',$v)) { 
            $items["expansions"][] = $v;
        }
    }
    return $items;
}


function CheckEssential($pagename,&$categories) {
    $category = "Category." . PageVar($pagename,'$:Category');
    $essential = PageVar($category,'$:Essential');
    
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



// given profile list, assigns work to each profile in the gradebook: each entry is a pair of (ess., non-ess.) count.
function AssignWork(&$gradebook, $auth){
    $wiki_gradebook = GetGradebookItems();
    
    DPrint("Assigning work to gradebook",0);

    DPrint("Creating temporary gradebook",1);
    // Initialiate 'complete' Gradebook: keys are profile names, $articles is list of essential cats used for article, $expansions
    $cgradebook = array();
    foreach ($gradebook as $k => $v) {
        $cgradebook[$k][$articles] = [];
        $cgradebook[$k][$expansions] = [];
    }

    global $skip_categories;

    $gradebookarticles = $wiki_gradebook["articles"];
    $gradebookexpansions = $wiki_gradebook["expansions"];
    
    
    
    //// Adding articles
    foreach($gradebookarticles as $k => $v){
        $pagename = $v;
        DPrint("Checking article " . $pagename,2);
        $status = PageVar($pagename,'$:Status');
        $author = strtolower(PageVar($pagename,'$:Author'));
        $category = PageVar($pagename,'$:Category');
        $essential = PageVar("Category." . $category,'$:essential');

        DPrint("Article author, status, and category is " . $author . ", " . $status . ", " . $category, 3);
        DPrint("Essential status is " . $essential,3);

        // complete 
        if ($status == "complete") {
            // essential
            DPrint("Article is complete.",3);
            if ($essential == "yes") {
                DPrint("Article is essential.",3);
                if (in_array($category,$cgradebook[$author][$articles])) {
                    // if this category was already used for an essential category, nonessential
                    DPrint("Category is already used.",4);
                    // $gradebook[$author]["complete articles"][1] += 1;
                } else {
                    DPrint("Category is not yet used, counting as essential.",4);
                    $cgradebook[$author][$articles][] = $category;
                    $gradebook[$author]["complete articles"][0] += 1;
                }
            }
            // non-essential
            else {
                DPrint("Article is non-essential.",3);
                DPrint("Current cgradebook entry for author is:",4);
                if (in_array($category,$cgradebook[$author][$articles])) {
                    // if this category was already used for an essential category, nonessential
                    DPrint("Category is already used.",4);
                    // $gradebook[$author]["complete articles"][1] += 1;
                } else {
                    DPrint("Category is not yet used, counting as essential.",4);
                    $cgradebook[$author][$articles][] = $category;
                    $gradebook[$author]["complete articles"][1] += 1;
                }
            }
        }
        
        // ungraded
        elseif ($status == "ungraded") {
            if ($essential == "yes") {
                $gradebook[$author]["ungraded articles"][0] += 1;
            } 
            else {
            $gradebook[$author]["ungraded articles"][1] += 1;
            }
        }

        // revised
        elseif ($status == "revised") {
            if ($essential == "yes") {
                $gradebook[$author]["revised articles"][0] += 1;
            } 
            else {
            $gradebook[$author]["revised articles"][1] += 1;
            }
        }
        // needsrevised
        elseif ($status == "needsRevised") {
            if ($essential == "yes") {
                $gradebook[$author]["needsrevised articles"][0] += 1;
            } 
            else {
            $gradebook[$author]["needsrevised articles"][1] += 1;
            }
        }
        // nocredit
        elseif ($status == "noCredit") {
            if ($essential == "yes") {
                $gradebook[$author]["nocredit articles"][0] += 1;
            } 
            else {
            $gradebook[$author]["nocredit articles"][1] += 1;
            }
        }
    }
    
    //// Adding expansions
    foreach($gradebookexpansions as $k => $v){
        $pagename = $v;
        DPrint("Checking article " . $pagename,2);
        $status = PageVar($pagename,'$:Status');
        $author = PageVar($pagename,'$:Author');
        $category = PageVar($pagename,'$:Category');
        $essential = PageVar($category,'$:Essential');

        DPrint("Expansion author, status, and category is " . $author . ", " . $status . ", " . $category, 2);

        // complete 
        if ($status == "complete") {
            // essential
            if ($essential == "yes") {
                if (in_array($category,$cgradebook[$author][$expansions])) {
                    // if this category was already used for an essential category, nonessential
                    $gradebook[$author]["complete expansions"][1] += 1;
                } else {
                    $cgradebook[$author][$expansions][] = $category;
                    $gradebook[$author]["complete expansions"][0] += 1;
                }
            }
            // non-essential
            else {
                $gradebook[$author]["complete expansions"][1] += 1;
            }
        }
        
        // ungraded
        elseif ($status == "ungraded") {
            if ($essential == "yes") {
                $gradebook[$author]["ungraded expansions"][0] += 1;
            } 
            else {
            $gradebook[$author]["ungraded expansions"][1] += 1;
            }
        }

        // revised
        elseif ($status == "revised") {
            if ($essential == "yes") {
                $gradebook[$author]["revised expansions"][0] += 1;
            } 
            else {
            $gradebook[$author]["revised expansions"][1] += 1;
            }
        }
        // needsrevised
        elseif ($status == "needsRevised") {
            if ($essential == "yes") {
                $gradebook[$author]["needsrevised expansions"][0] += 1;
            } 
            else {
            $gradebook[$author]["needsrevised expansions"][1] += 1;
            }
        }
        // nocredit
        elseif ($status == "noCredit") {
            if ($essential == "yes") {
                $gradebook[$author]["nocredit expansions"][0] += 1;
            } 
            else {
            $gradebook[$author]["nocredit expansions"][1] += 1;
            }
        }
    }
    // Comments
    $articles = GetAllArticles();
    DPrint("Now counting comments...",0);
    foreach($articles as $k => $v){
        $total = 0;
        DPrint("Counting comments in paper " . $v,2);
        $pagename = $v;
        $page = RetrieveAuthPage($pagename, $auth);
        $text = $page['text'];
        foreach($gradebook as $name => $e) {
            DPrint("Checking for profile " . $name,3);
            $flag = '!!!!!' . $name;
            $pattern = "/" . $flag . "/";
            DPrint("Pattern is " . $pattern,3);
            if (preg_match($pattern,$text)) {
                DPrint("Match found, adding.",4);
                $gradebook[$name]["comments"] += 1;
                $total += 1;
            }
        }
        DPrint("Total of " . $total . " comments",2);
    }
}

function GetSectionAndID($profile, $auth){
    DPrint("Getting section and ID for " . $profile,4);
    $section = PageVar($profile,'$:section');
    $id = PageVar($profile,'$:andrewid');

    // fix both (backslashes may come from profile pages)
    $id = str_replace(" \\\\","",$id);
    $section = str_replace(" \\\\","",$section);
    

    $details = [$section,$id];
    DPrint("Section and ID are " . $section . ", " . $id,4);
    return $details;
}




function ExportGrades($pagename, $auth) {
    global $start;
    global $statuses;
    global $items;
    $start = time();
    DPrint("Starting Export...",0);
    // Getting profiles from pages
    $profiles = GetProfiles();

    // Creating gradebook
    $gradebook = array();
    
    // Open CSV file
    $write = fopen("./pub/export/grades.csv","w");

    // Iterate over profiles and create gradebook entries
    foreach ($profiles as $v) {
        $sectid = GetSectionAndID($v,$auth);
        $name = ProfileToName($v);
        if ($name == ".profiles") { continue; }
        $gradebook[$name] = array();

        $gradebook[$name]["section"] = $sectid[0];
        $gradebook[$name]["id"] = $sectid[1];
        foreach ($statuses as $s) {
            foreach ($items as $i) {
                $key = $s . " ". $i;
                $gradebook[$name][$key] = [0,0];
            }
        }
        $gradebook[$name]["comments"] = 0;
    }
    // Get work into gradebook
    AssignWork($gradebook,$auth);


    // Construct header
    $export_header = "Username,Section,AndrewID";
    foreach ($statuses as $v) {
        foreach ($items as $k) {
            $export_header .= "," . $v . " " . $k;
        }
    }
    $export_header .= ",Comments";


    // Construct export string
    DPrint("Constructing export string",0);
    $exportstring = $export_header . "\n";
    
    foreach ($profiles as $v) {
        DPrint("Adding profile to export string: " . $v,2);
        $name = ProfileToName($v);
        if ($name == ".profiles") { continue; }
        $section = $gradebook[$name]["section"];
        $id = $gradebook[$name]["id"];
        
        $string = $name . "," . $section . "," . $id . ",";
        DPrint("Gathering records for " . $v,2);
        foreach ($statuses as $j) {
            foreach ($items as $k) {
                $key = $j . " " . $k;
                DPrint("Checking with key " . $key,3);
                DPrint("Value for " . $name . " at key " . $name . " is " . $gradebook[$name][$key],4);
                $string .= $gradebook[$name][$key][0] . "|" . $gradebook[$name][$key][1] . ",";
            }
        }
        $string .= $gradebook[$name]["comments"] . ",";
        DPrint("Records for " . $name . " are: " . $string,2);
        $exportstring .= $string . "\n";
    }

    $duration = time() - $start;
    DPrint("Export string:\n" . $exportstring, 1);


    fwrite($write,$exportstring);
    HandleBrowse('Gradebook.Export');
}
