<?php if (!defined('PmWiki')) exit();
# vim: set ts=4 sw=4 et:
/*	=== UserAdmin-Core ===
 *	Copyright 2010-2015 Eemeli Aro <eemeli@gmail.com>
 *
 *	AuthUser account self-registration and management
 *
 *	For more information, please see the online documentation at
 *		http://www.pmwiki.org/wiki/Cookbook/UserAdmin
 *
 *
 *  This script is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$RecipeInfo['UserAdmin']['Version'] = '2016-08-23';

if (strncmp($action, 'user', 4) == 0)
    SDV($HandleActions[$action], 'HandleUserAdmin');
function HandleUserAdmin($pagename, $auth = 'read') {
	global $action, $UserAdmin;

    SDV($UserAdmin->pagename, $pagename);
	$UserAdmin->action = preg_match('#^user/(.+)$#', $action, $m) ? preg_replace('/\W+/', '', $m[1]) : NULL;
	$hm = 'Handle'.ucfirst($UserAdmin->action);
	if (method_exists($UserAdmin, $hm)) $result = $UserAdmin->{$hm}($UserAdmin->pagename);
	else { $UserAdmin->action = NULL; $result = NULL; }

	$UserAdmin->PrintPage($UserAdmin->pagename, $result);
}


########################################################################################################################
##                                                                                                          set defaults

SDV($FmtPV['$Username'], '$GLOBALS["UserAdmin"]->Username($pn, $_REQUEST)');
SDV($FmtPV['$UA_Action'], '$GLOBALS["UserAdmin"]->action');
SDV($Conditions['superuser'], '@$GLOBALS["UserAdmin"]->Superuser()');

SDVA($UAredirects, array()); // keys can be any of the UA*_success_* or UA*_fail_* from below
// for example, to redirect to Mygroup/Mypage after a newly registered user is successfully activated:
//     $UAredirects['UAunlock_success_activated'] = 'Mygroup/Mypage';

XLSDV('en', array(
    'UA_action_signup' => 'Sign Up',
    'UA_action_menu' => 'User Menu',
	'UA_contact_admin' => 'Please contact site admin',
	'UA_diff_userpasswd' => 'New passwords don&#039;t match',
	'UA_email_fail' => 'Error sending email',
	'UA_email_from' => "no-reply@{$_SERVER['HTTP_HOST']}",
	'UA_empty_key' => 'Activation key is required',
	'UA_empty_useremail' => 'An e-mail address is required',
	'UA_empty_username' => 'Username is required',
	'UA_empty_useroldpasswd' => 'Current password is required',
	'UA_empty_userpasswd' => 'Password is required',
	'UA_empty_userpasswd2' => 'Please enter your password twice',
	'UA_exists' => 'User already exists',
	'UA_fail_unknown_action' => 'Error: unknown user action',
	'UA_invalid_useremail' => 'E-mail address is not valid',
	'UA_invalid_username' => 'Username is not valid',
	'UA_invalid_userpasswd' => 'Password is not valid',
	'UA_return_link' => "\\\\\n\n[[ {\$PageUrl}?action=user | $[Return to user actions] ]]",
	'UA_title' => 'User account management',
	'UA_txt_key' => 'Activation key',
	'UA_txt_useremail' => 'E-mail address',
	'UA_txt_usergroups' => 'User groups',
	'UA_txt_username' => 'Username',
	'UA_txt_useroldpasswd' => 'Current password',
	'UA_txt_userpasswd' => 'New password',
	'UA_txt_userpasswd2' => 'New password, again',
	'UA_unauthorized' => 'Not authorized',
	'UA_unsupported_user_format' => 'User data can&#039;t be edited',
	'UA_wrong_passwd' => 'Password not recognized',

	'UAedit_fail' => 'Error updating user account',
	'UAedit_submit' => 'Submit',
	'UAedit_success' => 'User account updated',
	'UAedit_success_unchanged' => 'User account not modified',
	'UAedit_title' => 'Edit user account',

	'UAgroup_fail' => 'Error updating user group',
	'UAgroup_needs_at' => 'Group Name MUST begin with "@" and contain no other special characters',
	'UAgroup_submit' => 'Submit',
	'UAgroup_success' => 'User group updated',
	'UAgroup_success_unchanged' => 'User group not modified',
	'UAgroup_title' => 'Edit user group',

	'UAnew_email_body' => 'Welcome $username!

		Thank you for registering at $WikiTitle.

		To activate your account and confirm your e-mail address, please visit
		the following location:

        $link
        ',
	'UAnew_email_subject' => 'Welcome to $WikiTitle',
	'UAnew_email_sent' => 'E-mail sent with activation link',
	'UAnew_fail' => 'Error creating account',
	'UAnew_submit' => 'Sign up',
	'UAnew_success' => 'New user account created',
	'UAnew_title' => 'Register as a new user',

	'UAresetpasswd_email_body' => 'To set a new password for the user $username at $WikiTitle, please
		visit the following location:

		$link

		If you\'ve received this message in error, please contact the site admin
        at <'.$ScriptUrl.'>.
        ',
	'UAresetpasswd_email_subject' => 'Password reset for $WikiTitle',
	'UAresetpasswd_email_empty' => 'User has no e-mail address defined',
	'UAresetpasswd_email_nomatch' => 'User and e-mail address do not match',
	'UAresetpasswd_fail' => 'Error sending reset link',
	'UAresetpasswd_submit' => 'Send reset link to user&#039;s e-mail address',
	'UAresetpasswd_success' => 'Password reset link sent to user&#039;s e-mail address',
	'UAresetpasswd_title' => 'Reset password',

	'UAunlock_already_active' => 'Account is already active',
	'UAunlock_bad_key' => 'Bad activation key',
	'UAunlock_fail' => 'Error activating account',
	'UAunlock_new_passwd' => 'Enter new password',
	'UAunlock_submit' => 'Activate/set password',
	'UAunlock_success_activated' => 'User account activated',
	'UAunlock_success_set_pw' => 'User password set',
	'UAunlock_title' => 'Account activation',

    'UAinstall_title' => 'Database Installation (admin).',
    'UAinstall_success' => 'Database tables successfully created.',
    'UAinstall_fail' => 'Database tables WERE NOT successfully created. See errors.',

    'UAlogin2edit_title' => 'Login to edit your account details',
    'UAlogin2edit_link' => '{$PageUrl}?action=user/edit',

    'UAloginother_title' => 'Login as another user',
    'UAloginother_link' => '{$PageUrl}?action=login',

    'UAlogin_title' => 'Login to continue',
    'UAlogin_link' => '{$PageUrl}?action=login',
));

define('UA_REQ_NOT_EMPTY', 0);
define('UA_REQ_ANY', 1);
define('UA_REQ_TWICE', 2); ## implies not empty
define('UA_REQ_TWICE_ANY', 3);
define('UA_REQ_PRESET', 4); ## implies not empty
define('UA_REQ_PRESET_ANY', 5);


## return array entries with keys that match the pattern
## ie. exactly like preg_grep but for array keys instead of values
if (!function_exists('preg_grep_keys')) {
function preg_grep_keys($pattern, $input, $flags = NULL) {
	$keys = preg_grep($pattern, array_keys($input), $flags);
	$out = array();
	foreach ($keys as $key) $out[$key] = $input[$key];
	return $out;
}}

########################################################################################################################
##                                                                                                       framework class
class UserAdmin {
	var $confirm_email = TRUE;
	var $username_chars = '\w';
	var $fields = array(
		'new' => array(
			'username' => UA_REQ_NOT_EMPTY,
			#'usergroups' => UA_REQ_PRESET_ANY,
			'userpasswd' => UA_REQ_TWICE,
			'useremail' => UA_REQ_NOT_EMPTY),
		'resetpasswd' => array(
			'username' => UA_REQ_NOT_EMPTY,
			'useremail' => UA_REQ_NOT_EMPTY),
		'unlock' => array(
			'username' => UA_REQ_PRESET,
			'key' => UA_REQ_PRESET),
		'newpasswd' => array(
			'username' => UA_REQ_PRESET,
			'key' => UA_REQ_PRESET,
			'userpasswd' => UA_REQ_TWICE),
		'edit' => array(
			'username' => UA_REQ_PRESET,
			'usergroups' => UA_REQ_PRESET_ANY,
			'useroldpasswd' => UA_REQ_NOT_EMPTY,
			'useremail' => UA_REQ_NOT_EMPTY,
			'userpasswd' => UA_REQ_TWICE_ANY),
		'group' => array(
			'groupname' => UA_REQ_NOT_EMPTY),
	);

	var $action;
	var $input;
    var $AuthUserData = array(); // contains all AuthUser contents
    var $AuthUserAuth = array(); // contains auths (hash or @group)
    var $Author = 'UserAdmin'; // default author to use on page updates
    var $pagename;
    var $EnableNestedGroups = false;
    var $SuperuserFunc = 'Superuser'; // call $this->Superuser() by default

	########################################################################
	## SetupAuthGroups()
    ##   Read groups from the current authstore and place them in $AuthUser
	########################################################################
    function SetupAuthGroups()
    {
        global $AuthUser;
        # Set up groups
        $allgroups = $this->ReadGroups(NULL, $this->EnableNestedGroups);
        foreach ($allgroups as $g => $m)
            if ($AuthUser[$g])
                $AuthUser[$g] = array_merge($AuthUser[$g], $m);
            else
                $AuthUser[$g] = $m;
        #echo "DEBUG: SetupAuthGroups(): AuthUser=<pre>".print_r($AuthUser,true)."</pre><br />\n";
    }

	########################################################################
	## ReadTemplate()
	##   Utility function to read a template section
    ## Arguments:
    ##   $pagename - current pagename (NOT the page the templates are read from)
    ##   $section  - the part of the section following #ua- and preceeding a
    ##               possible -admin.  (i.e., for #ua-email-body you would pass
    ##               "email-body"
	## returns the text of the section/template read in
    function ReadTemplate($pagename, $section)
    {
        global $UATemplatePage, $SiteGroup;
        SDV($UATemplatePage, "$SiteGroup.UserAdminTemplates");
#echo "ReadTemplate(): #ua-{$section}-admin<br>\n";
        if (!empty($UATemplatePage) && PageExists($UATemplatePage))
            if (!$this->Superuser($pagename) || !($text = RetrieveAuthSection($UATemplatePage, "#ua-{$section}-admin")))
                $text = RetrieveAuthSection($UATemplatePage, "#ua-{$section}");
        return $text;
    }

	########################################################################
	## AuthUserPage()
	##   Utility function to read SiteAdmin.AuthUser page
	## returns array('userA' => array(HASH, '@groupA', ...),
	##               '@groupA' => array('userB', ...), ...)
	function AuthUserPage($userpat=null, $forceread=false) {
		global $AuthUserPat, $AuthUserPageFmt;
#echo "DEBUG: AuthUserPage($userpat): Entering<br>\n";
		if ($this->AuthUserAuth && !$forceread) return $this->array_match($userpat, $this->AuthUserAuth);
		SDV($AuthUserPageFmt, '$SiteAdminGroup.AuthUser');
        SDV($AuthUserPat, "/^\\s*([@\\w][^\\s:]*)\\s*:(.*)/m"); // slightly modified from authuser.php
		$pn = FmtPageName($AuthUserPageFmt, '');
		$apage = ReadPage($pn, READPAGE_CURRENT);
		if (!$apage
		    || empty($apage['text'])
		    || !preg_match_all($AuthUserPat, $apage['text'], $this->AuthUserData, PREG_SET_ORDER)
		) return array();
		$this->AuthUserAuth = array();
		foreach($this->AuthUserData as $m) {
            #echo "DEBUG: AUP: m=<pre>".print_r($m,true)."</pre><br />\n";
            if (!preg_match_all('/\\bldaps?:\\S+|[^\\s,]+/', $m[2], $tokens)) {
                if (!isset($this->AuthUserAuth[$m[1]]))  {
                    $this->AuthUserAuth[$m[1]] = array();
                }
                continue;
            }
#echo "DEBUG: m[2]=$m[2], m[3]=$m[3], tokens=<pre>".print_r($tokens,true)."</pre><br>\n";
			$this->AuthUserAuth[$m[1]] = empty($this->AuthUserAuth[$m[1]]) ? $tokens[0] : array_merge($this->AuthUserAuth[$m[1]], $tokens[0]);
		}
		return $this->array_match($userpat, $this->AuthUserAuth);
	}

    // array_match()
    //   Utility function to select elements of an array based on an
    //   optional key-pattern applied by MatchPageName()
    // returns an array with all elements of the array which match keys
    function array_match($pat, $ary)
    {
#echo "array_match(pat=$pat): incoming ary=<pre>".print_r($ary,true)."</pre><br>\n";
#echo "MPN=<pre>".MatchPageNames(array_keys($ary), $pat)."</pre><br>\n";
        if ($pat)
            return array_intersect_key($ary, array_flip(MatchPageNames(array_keys($ary), $pat)));
        return $ary;
    }


	function DelValue(&$page, $name, $value) {
		if (!preg_match('/^[@\w][^\s:]*$/', $name) || !preg_match('/^[^\s,]+$/', $value)) return FALSE;
		$page['text'] = preg_replace("/^(\s*$name:)(?:(.*?)[\s,]+)?$value([\s,]|$)/m", '$1$2$3', $page['text'], -1, $count);
        # Delete any "blank" lines (group-lines with no members, etc.)
		$page['text'] = preg_replace("/^(\s*$name:)[\s,]*\n/m", '', $page['text']);
		return $count;
	}

	function AddValue(&$page, $name, $value) {
		if (!preg_match('/^[@\w][^\s:]*$/', $name) || !preg_match('/^[^\s,]+$/', $value)) return FALSE;
		if (preg_match("/^\s*$name:/m", $page['text'])) {
			if (preg_match("/^\s*$name:(.*[\h,])?$value([\h,]|$)/m", $page['text'])) {
				return 0;
			} else {
				$page['text'] = preg_replace("/^(\s*$name:.*?)\s*$/m", "$1 $value", $page['text'], 1, $count);
				return $count;
			}
		} else {
			$page['text'] .= "\n$name: $value";
			return 1;
		}
	}

    var $Groups = array();
	############################################################################
	## ReadGroups()
	## with username: returns array('@groupA', '@groupB', ...)
	##  w/o username: returns array('@groupA' => array('userA', 'userB', ...), '@groupB' => ...)
	############################################################################
	function ReadGroups($username=NULL, $recursive=false) {
		if (!$this->Groups) {
			$this->Groups = $this->AuthUserPage('/^@/');
			$auth = $this->AuthUserPage('/^[^@]/');
            foreach ($auth as $k => $v)
                foreach (preg_grep('/^@/', $v) as $g) {
                    $this->Groups[$g][] = $k;
                }
            # asdf - check if this works as opposed to modifying &$g
			foreach ($this->Groups as $g => $a) $this->Groups[$g] = array_unique($a);
			ksort($this->Groups);
		}

		if ($username) {
			$ug = array();
			foreach ($this->Groups as $g => $a) {
				if (in_array($username, $a) || in_array('*', $a)) $ug[] = $g;
				//if (in_array("-$username", $a)
			}
			return $ug;
		}

		return $this->Groups;
	}


	## returns array('userA', 'userB', ...)
	function ReadGroup($groupname, $recursive=false) {
		$groups = $this->ReadGroups(NULL, $recursive);
        return (array)@$groups[$groupname];
	}

    # $old is an array of the existing groups this user is a member of
    # $new is an array of the new groups this user will be a member of
    # return: 2 arrays, one the list of groups that needs to be added
    #         and the 2nd the list of groups that needs to be deleted
    # list($add, $del) = GroupDiff($old, $new);
    function GroupDiff($old, $new)
    {
        $del = $add = array();
        foreach ((array)$old as $o)
            if (!in_array($o, (array)$new))
                $del[] = $o;
        foreach ((array)$new as $n)
            if (!in_array($n, (array)$old))
                $add[] = $n;
        return array($add, $del);
    }

	## $add is an array of members to add to group
	## $del is an array of members to remove from group
    function WriteGroup($groupname, $add, $del, $csum='', $auth='read')
    {
		global $AuthUserPageFmt, $Now, $EditFunctions, $IsPagePosted;

		if (!preg_match('/^@\w[^\s:]*$/', $groupname)) return 'UAgroup_needs_at';

        $this->SetAuthor($this->Author);

		SDV($AuthUserPageFmt, '$SiteAdminGroup.AuthUser');
		$pn = FmtPageName($AuthUserPageFmt, $this->pagename);
		Lock(2);
			$page = RetrieveAuthPage($pn, $auth, TRUE);
			if (!$page) Abort("?cannot write to $pn");
			$new = $page;

			$del_count = 0;
			if ($del) foreach ($del as $d) {
				$del_count += $this->DelValue($new, $d, $groupname);
				$del_count += $this->DelValue($new, $groupname, $d);
			}
			$add_count = 0;
			if ($add) foreach ($add as $a) {
				$add_count += $this->AddValue($new, $groupname, $a);
			}

			$new['csum'] = str_replace(array('$add', '$del'), array($add_count, $del_count), $csum);
			if ($csum) $new["csum:$Now"] = $new['csum'];

			PCache($pn, $new);
			$k = array_search('SaveAttributes', $EditFunctions);
			if ($k !== FALSE) unset($EditFunctions[$k]);
            #echo "DEBUG: writegroup(): page=<pre>".print_r($page['text'],true)."</pre><br>\n";
            #echo "DEBUG: writegroup(): new=<pre>".print_r($new['text'],true)."</pre><br>\n";
			UpdatePage($pn, $page, $new);
		Lock(0);
        if ( $IsPagePosted)
            return 'UAgroup_success';
        else
            return 'UAgroup_fail';
	}

    # SetAuthor()
    # Set the global $Author.  Necessary because config may have
    # $EnablePostRequireAuthor, resulting in an error if a new user
    # tries to make any page changes (like creating a new user and writing
    # that info to their profile or SiteAdmin.AuthUser or etc.).  It should
    # be called before any Write*() function.
    #
    # Although the name doesn't really indicate it, we also set $ChangeSummary
    # here to ensure that the cookbook RequireSummary works.
    function SetAuthor($default_author)
    {
        global $Author, $AuthorNameChars, $AuthorCookie, $Author, $ChangeSummary;

        // the code below is modified from scripts/author.php
        SDV($AuthorCookie, $CookiePrefix.'author');
        if (!@$Author) {
          if (@$_POST['author']) {
            $x = stripmagic($_POST['author']).'/UserAdmin';
          } elseif (@$_COOKIE[$AuthorCookie]) {
            $x = stripmagic(@$_COOKIE[$AuthorCookie]).'/UserAdmin';
          } elseif (@$AuthId) {
              $x = @$AuthId.'/UserAdmin';
          } else {
              $x = $default_author;
          }
          $Author = htmlspecialchars(preg_replace("/[^$AuthorNameChars]/", '', $x),
                        ENT_COMPAT);
        }

        // Set $ChangeSummary for the RequireSummary recipe (needed for
        // authuser and profiles stores
        $ChangeSummary = 'Useradmin updating page via UI';
    }

    # Make sure the $pat is either NULL or a regex, not a glob or discrete value
    # Otherwise MatchPageNames() goes case-insensitive
	function ListGroups($pat=NULL) { return MatchPageNames(array_keys($this->ReadGroups(null, false)), $pat); }

	function AdminGroup($groupname) { return preg_match('/_admin$/', $groupname) ? $groupname : "{$groupname}_admin"; }


	############################################################################
	## users
	############################################################################

	function ReadUser($username, $readgroup=false) {
        #echo "DEBUG: core->ReadUser($username): Entering.<br>\n";
        $userpat = "/^$username$/"; // otherwise MatchPageNames() goes case-insensitive on me
		if (!($auth = $this->AuthUserPage($userpat))) return array();
		$data = array('username' => $username);
        #echo "DEBUG: auth=<pre>".print_r($auth,true)."</pre><br>\n";
		foreach ($auth[$username] as $v) if ($v[0] == '$') $data['userpwhash'] = $v;
        if ($readgroup) $data['usergroups'] = $this->ReadGroups($username, $this->EnableNestedGroups);
        #echo "DEBUG: data=<pre>".print_r($data,true)."</pre><br />\n";
		return $data;
	}

    # NOTE: This function MUST be over-ridden in the extending class.  However,
    # if your user info is stored on a page then make sure $this->SetAuthor()
    # is called.  (parent::WriteUser(...) is probably the easiest way and provides
    # for future extensions...)
    function WriteUser($username, $data, $csum='', $auth='read')
    {
        $this->SetAuthor($this->Author);
        if (get_class($this) == 'UserAdmin')
            exit('UserAdmin::WriteUser not implemented'); // extended class must override
    }

    # Write out group memberships from a user-centric view (after a *user* is edited you have a list of
    # groups as contrasted to when you edit a group and have a list of users)
    function WriteUserGroups($username, $newgroups)
    {
        $changes = 0;
        $oldgroups = $this->ReadGroups($username); // make sure it's not recursive
        #echo "DEBUG: oldgroups=<pre>".print_r($oldgroups,true)."</pre><br />\n";
        #echo "DEBUG: PRE: data=<pre>".print_r($data,true)."</pre><br />\n";
        #echo "DEBUG: POST: data=<pre>".print_r($data,true)."</pre><br />\n";
        #echo "DEBUG: newgroups=<pre>".print_r($newgroups,true)."</pre><br />\n";
        list($add, $del) = $this->GroupDiff($oldgroups, $newgroups);
        #echo "DEBUG: add=<pre>".print_r($add,true)."</pre><br />\n";
        #echo "DEBUG: del=<pre>".print_r($del,true)."</pre><br />\n";
        foreach ($add as $a) {
            $changes++;
            $this->WriteGroup($a, array($username), array());
        }
        foreach ($del as $d) {
            $changes++;
            $this->WriteGroup($d, array(), array($username));
        }
        return $changes;
    }

	function Exists($username) {
		return (boolean)$this->ReadUser($username);
	}

    # Make sure $pat is either NULL or a regex, not a single value
    # Otherwise MatchPageNames() goes case-insensitive
	function ListUsers($pat=NULL) {
		global $AuthUserFunctions;
		$pat = (array)$pat;
		$x = array('htpasswd' => 1, 'htgroup' => 1, 'ldap' => 1, 'userprofilegroup' => 1) + (array)@$AuthUserFunctions;
		array_push($pat, '!^('.implode('|', array_keys($x)).')$!');

		$ls = preg_grep('/^\w/', array_keys($this->AuthUserPage()));
		return MatchPageNames($ls, $pat);
	}


	############################################################################
	## utility functions

	function MailUser($username, $fmt, $opt = array()) {
        global $WikiTitle;
		if ($username) $opt = array_merge($this->ReadUser($username), $opt);
		if (empty($opt['useremail'])) return FALSE;

		$fmt = array_merge(array('to' => '$useremail', 'head' => 'From: '.XL('UA_email_from')), $fmt);
        $opt = array_merge($opt, array('WikiTitle'=>$WikiTitle));
		$msg = array();
		foreach ($fmt as $fk => $f) {
			foreach($opt as $k => $v) $f = preg_replace("/\\$$k\b`?/", $v, $f);
			$msg[$fk] = $f;
		}
		$msg['body'] = preg_replace('/^\t+/m', '', $msg['body']); ## allow pretty XLSDV with indentation

    #echo "DEBUG: msg=<pre>".print_r($msg,true)."</pre>\n";
		//exit(pre_r($msg));
		return mail($msg['to'], @$msg['subject'], @$msg['body'], $msg['head']);
	}

	function MakeActivationKey() { return strval(mt_rand() + 1); }

	function MakeUserLink($username, $useraction = '', $opt = array()) {
		$action = $useraction ? 'user/'.urlencode($useraction) : 'user';
        $url = FmtPagename('{$PageUrl}', $this->pagename);
        $url .= "?action=$action&username=".urlencode($username);
		foreach ($opt as $k => $v) $url .= '&'.urlencode($k).'='.urlencode($v);
		return $url;
	}


	############################################################################
	## authentication

	## return TRUE for authenticated user with admin rights (this is the default for useradmin-dbase)
	function Superuser($pagename, $prompt=FALSE) {
		if (RetrieveAuthPage($pagename, 'admin', $prompt, READPAGE_CURRENT))
            return true;
        if ($this->SuperuserFunc && $this->SuperuserFunc != 'Superuser') {
            $fn = $this->SuperuserFunc;
            return (boolean)call_user_func(array($this, $fn), $pagename, $prompt);
        }
		return false;
	}
    ## return TRUE if the user can edit SiteAdmin.AuthUser (this is the default for useradmin-authuser)
	function SuperuserAuthUserEdit($pagename, $prompt=FALSE)
    {
		global $AuthUserPageFmt;
		SDV($AuthUserPageFmt, '$SiteAdminGroup.AuthUser');
		$pn = FmtPageName($AuthUserPageFmt, '');
        return (boolean)RetrieveAuthPage($pn, 'edit', $prompt, READPAGE_CURRENT);
    }


	function Username($pagename, $opt) {
		global $AuthId;
		$n = @$opt['username'] or !$this->Superuser($pagename) and $n = @$AuthId;
		if (!$n) return FALSE;
		if (method_exists($this, 'ValidName') && !$this->ValidName($n)) return FALSE;
		if (!$this->Exists($n)) return FALSE;
		return $n;
	}

	function AuthorizedUser($pagename, $username, $auth='edit', $prompt=FALSE) {
		global $AuthId;
		return ($username && ($AuthId == $username)) || $this->Superuser($pagename, $prompt);
	}

	function AuthorizedGroup($pagename, $groupname, $auth='edit', $prompt=FALSE) {
		global $AuthId, $AuthList;
		return !empty($AuthList[$this->AdminGroup($groupname)]) || $this->Superuser($pagename, $prompt);
	}


	############################################################################
	## input processing

	## returns TRUE if form has been posted
	function ReadInput($pagename, $valid_username=TRUE) {
        if (preg_grep('/^cancel/', array_keys($_POST))) {
            $dest = FmtPagename('{$PageUrl}?action=user', $pagename);
            Redirect($pagename, $dest);
        }

		$this->input = array_merge($_GET, $_POST);
		if ($valid_username) $this->input['username'] = $this->Username($pagename, $_REQUEST);

		return (boolean)preg_grep('/^post/', array_keys($_POST));
	}

	function ValidGroupname($groupname) { return preg_match('/^@\w\S*$/', $groupname); }

	function ValidEmail(&$address) { return !$address || preg_match('/^.+@.+\..+$/', $address); }

	function ValidName(&$username) {
        if (preg_match("/[^{$this->username_chars}]/", $username))
            return false;
		return (boolean)$username;
	}

	function ValidateInput($fmt = '') {
		if (!$fmt) $fmt = $this->action;
		if (empty($this->fields[$fmt])) return 'UA_fail_unknown_action';
		$result = array();
		foreach ($this->fields[$fmt] as $k => $req) {
			$this->input[$k] = stripmagic($this->input[$k]);
			if (!($req & UA_REQ_ANY) && empty($this->input[$k])) {
				$result[$k] = "UA_empty_$k";
				continue;
			}
			if (($req & UA_REQ_TWICE) && (stripmagic(@$this->input["{$k}2"]) != $this->input[$k])) {
				$result[$k] = "UA_diff_$k";
				continue;
			}
			$vm = preg_replace('/^user/', '', $k);
            $vm = 'Valid'.ucfirst($vm);
			if (method_exists($this, $vm) && !$this->{$vm}($this->input[$k])) {
				$result[$k] = "UA_invalid_$k";
				continue;
			}
		}
		return $result;
	}


	############################################################################
	## action handlers

	function HandleNew($pagename) {
		if (!$this->ReadInput($pagename, FALSE)) return NULL;

		$result = $this->ValidateInput();
		$username = $this->input['username'];
		if ($this->Exists($username)) $result['username'] = 'UA_exists';
		if ($result) return $result;

        # get things set up for group editing if superuser is creating user
        if ($this->Superuser($pagename)) {
            $allgroups = $this->ListGroups();
            #echo "DEBUG: HandleEdit: allgroups=<pre>".print_r($allgroups,true)."</pre><br />\n";
            foreach ($allgroups as $g) {
                $fldname = 'usergroups-'.substr($g,1);
                if (isset($this->input[$fldname])) $posted = TRUE;
            }
        }

		$hash = crypt($this->input['userpasswd']);
		if ($this->confirm_email && !$this->Superuser($pagename)) {
			$key = $this->MakeActivationKey();
			$link = $this->MakeUserLink($username, 'unlock', array('key' => $key));
			//$link = $this->MakeActivationLink($username, $key);

#echo "DEBUG: Hello, calling ReadTemplate()<br>\n";
            if (!($body = $this->ReadTemplate($pagename, "new-email-body")))
                $body = XL('UAnew_email_body');
			$mail_fmt = array(
				'subject' => XL('UAnew_email_subject'),
				'body' => $body
			);
			$mail_opt = array(
				'username' => $username,
				'useremail' => $this->input['useremail'],
				'key' => $key,
				'link' => $link
			);
#echo "DEBUG: mail_fmt=<pre>".print_r($mail_fmt,true)."</pre><br>\n";
#echo "DEBUG: mail_opt=<pre>".print_r($mail_opt,true)."</pre><br>\n";

			if (!$this->MailUser('', $mail_fmt, $mail_opt)) return array('UA_email_fail', 'UA_contact_admin');

			$success = array('UAnew_success', 'UAnew_email_sent');
			$data = array(
				'useremail' => $this->input['useremail'],
				'username' => $username,
				'userkey' => "$key $hash",
                'userkeyreason' => 'new',
                'userkeytime' => time()
			);
		} else {
			$success = 'UAnew_success';
			$data = array(
				'userpwhash' => $hash,
				'useremail' => @$this->input['useremail'],
				'username' => $username,
			);
		}
        $this->AppendCustomFields($data, array(), $this->input, array_flip(array('username', 'useremail', 'userpwhash', 'userkey', 'userpasswd', 'userpasswd2')));
        $newgroups = preg_grep_keys('/^usergroups-/', $data);
        $data = array_diff_key($data, array_merge($newgroups, array('usergroups'=>1))); // strip off groups before saving
        $changed = false;
        #echo "DEBUG: HandleEdit: checking superuser to write groups<br />\n";
        if ($this->Superuser($pagename)) {
            $newgroups = preg_grep('/^@/', $newgroups); // get rid of those that are not checked
            #echo "DEBUG: HandleEdit: Calling WriteUserGroups()<br />\n";
            $changed = $this->WriteUserGroups($username, $newgroups);
        }
		$fail = array('UAnew_fail', 'UA_contact_admin');
		return $this->WriteUser($username, $data, implode('; ', array_map('XL', (array)$success))) ? $success : $fail;
	}

    function AppendCustomFields(&$data, $user, $input, $normal_fields)
    {
        $custom_fields = array_diff_key(preg_grep_keys("/^user/", $input), $normal_fields);
        #echo "preg_grep_keys(input)=<pre>".print_r(preg_grep_keys("/^user/", $input),true)."</pre><br>\n";
        #echo "normal_fields=<pre>".print_r($normal_fields,true)."</pre><br>\n";
        #echo "custom_fields=<pre>".print_r($custom_fields,true)."</pre><br>\n";
        #echo "pre-data=<pre>".print_r($data,true)."</pre><br>\n";
        #echo "TEST: Must test this AppendCustomFields() function now that I'm not checking for if (!isset($user[$f]) || $v !== $user[$f]) (it's commented out below)<br>\n";
        foreach ((array)$custom_fields as $f => $v)
            #if (!isset($user[$f]) || $v !== $user[$f])
                $data[$f] = $v;
        #echo "post-data=<pre>".print_r($data,true)."</pre><br>\n";
    }

	function HandleResetpasswd($pagename) {
        if ($this->Superuser($pagename)) {
            #unset($this->fields['resetpasswd']['useremail']);
            $dest = FmtPagename('{$PageUrl}?action=user/edit', $pagename);
            Redirect($pagename, $dest);
        }
		$posted = $this->ReadInput($pagename, FALSE);
		$username = $this->input['username'];
        # At some point it would be nice to set input['username'] to $AuthId
        # so a non-admin user would already have their username filled in
		if (!$posted) return NULL;

		$result = $this->ValidateInput();
#echo "DEBUG: result=<pre>".print_r($result,true)."</pre><br>\n";
		if ($result) return $result;

		$user = $this->ReadUser($username);
        if (!$user)
            return array('UAresetpasswd_fail', 'UA_invalid_username');
        if ($user['useremail'] != $this->input['useremail'])
            return array('UAresetpasswd_email_nomatch', 'UA_contact_admin');

		$key = $this->MakeActivationKey();
		$link = $this->MakeUserLink($username, 'unlock', array('key' => $key));
		//$link = $this->MakeActivationLink($username, $key);

        if (!($body = $this->ReadTemplate($pagename, "resetpasswd-email-body")))
            $body = XL('UAresetpasswd_email_body');
		$mail_fmt = array(
			'subject' => XL('UAresetpasswd_email_subject'),
			'body' => $body
		);
		$mail_opt = array(
			'key' => $key,
			'link' => $link
		);
		if (!$this->MailUser($username, $mail_fmt, $mail_opt)) return array('UA_email_fail', 'UA_contact_admin');

        $data = array(
                    'userkey' => $key,
                    'userkeyreason' => 'resetpasswd',
                    'userkeytime' => time()
                );
		return $this->WriteUser($username, $data, XL('UAresetpasswd_success'))
			? 'UAresetpasswd_success'
			: array('UAresetpasswd_fail', 'UA_contact_admin');
	}


	## handles e-mail address verification and password resets
	function HandleUnlock($pagename) {
		$posted = $this->ReadInput($pagename, FALSE);
#echo "DEBUG: Checking input[username]: <pre>".print_r($this->input,true)."</pre><br>\n";
		if (empty($this->input['username'])) return NULL;
		$user = $this->ReadUser($this->input['username']);
		if (!$user) return array('UA_invalid_username');
#echo "DEBUG: user=<pre>".print_r($user,true)."</pre><br>\n";
		if (empty($user['userkey'])) return array('UAunlock_fail', 'UAunlock_already_active');

		$result = $this->ValidateInput();
		if ($result) return $result;

		$key = preg_replace('/[^0-9]+/', '', $this->input['key']);
		if (!preg_match("/^$key(\s+.*)?$/", $user['userkey'], $match)) return array('UAunlock_fail', 'UAunlock_bad_key');
		$hash = trim($match[1]);

		if (!$hash) {
			$this->fields['unlock'] = $this->fields['newpasswd'];
			if (!$posted) return NULL;
			$result = $this->ValidateInput('newpasswd');
			if ($result) return $result;
			$hash = crypt($this->input['userpasswd']);
			$reset = TRUE;
		} else $reset = FALSE;

		$result = $reset ? 'UAunlock_success_set_pw' : 'UAunlock_success_activated';
        $data = array(
                    'userpwhash' => $hash,
                    'userkey' => '',
                    'userkeyreason' => '',
                    'userkeytime' => ''
                );
		return $this->WriteUser($this->input['username'], $data, XL($result))
			? $result
			: array('UAnew_fail', 'UA_contact_admin');
	}


	function HandleEdit($pagename) {
        global $InputValues;

        #echo "DEBUG: HandleEdit(): Entering<br />\n";
		$posted = $this->ReadInput($pagename);
		unset($this->input['usergroups']);

		$username = $this->input['username'];
		if (empty($username)) {
			$this->fields[$this->action] = array('username' => UA_REQ_NOT_EMPTY);
			return NULL; // results in a list of users to choose from
		}

        #echo "DEBUG: HandleEdit: A<br>\n";
		if (!$this->AuthorizedUser($pagename, $username, 'edit', TRUE)) return array('UAedit_fail', 'UA_unauthorized');

		$admin = $this->Superuser($pagename);
		if ($admin) unset($this->fields[$this->action]['useroldpasswd']);

        #echo "DEBUG: HandleEdit: Calling ReadUser($username,true)<br />\n";
		$user = $this->ReadUser($username);
        #echo "DEBUG: HandleEdit: user=<pre>".print_r($user,true)."</pre><br />\n";
		if (!$user) return array(
			'UAedit_fail',
			$this->Exists($username) ? 'UA_unsupported_user_format' : 'UA_invalid_username'
		);
        $usergroups = $this->ReadGroups($username); // make sure it's not recursive
        foreach ($usergroups as $g) {
            $user['usergroups-'.substr($g,1)] = $g;
        }
        #echo "DEBUG: HandleEdit: usergroups=<pre>".print_r($usergroups,true)."</pre><br />\n";
		$ef = preg_grep('/passwd|^username$/', array_keys($this->fields[$this->action]), PREG_GREP_INVERT);
        #echo "DEBUG: HandleEdit: ef=<pre>".print_r($ef,true)."</pre><br />\n";
		$posted = FALSE;
        #echo "DEBUG: HandleEdit: B<br>\n";
        #echo "DEBUG: HandleEdit: user=<pre>".print_r($user,true)."</pre><br />\n";
		foreach ($ef as $f) {
            #echo "DEBUG: HandleEdit(): top: f=$f this->input=<pre>".print_r($this->input,true)."</pre><br />\n";
			if (isset($this->input[$f])) $posted = TRUE;
			else $this->input[$f] = @$user[$f];
            #echo "DEBUG: HandleEdit(): bottom: this->input=<pre>".print_r($this->input,true)."</pre><br />\n";
		}
        #echo "DEBUG: HandleEdit(): this->input=<pre>".print_r($this->input,true)."</pre><br />\n";
        $allgroups = $this->ListGroups();
        #echo "DEBUG: HandleEdit: allgroups=<pre>".print_r($allgroups,true)."</pre><br />\n";
        foreach ($allgroups as $g) {
            $fldname = 'usergroups-'.substr($g,1);
            if (isset($this->input[$fldname])) $posted = TRUE;
            else
                if (in_array($g, $usergroups))
                    $this->input[$fldname] = $g;
        }
        if (!isset($InputValues)) $InputValues = array();
        #echo "DEBUG: HandleEdit: 1 InputValues=<pre>".(isset($InputValues)?"SET":"UNSET").print_r($InputValues,true)."</pre><br>\n";
        $this->AppendCustomFields($InputValues, $user, $user, $this->fields[$this->action]);

        #echo "DEBUG: HandleEdit: 2 InputValues=<pre>".print_r($InputValues,true)."</pre><br />\n";
		if (!$posted) return NULL;

		$result = $this->ValidateInput();
		if ($result) return $result;

        #echo "DEBUG: HandleEdit: checking pw admin=$admin<br />\n";
		if (!$admin && (_crypt($this->input['useroldpasswd'], $user['userpwhash']) != $user['userpwhash']))
			return 'UA_wrong_passwd';

        #echo "DEBUG: HandleEdit: some final validation stuff<br />\n";
		$data = array();
		foreach ($this->fields[$this->action] as $f => $req) {
			if (($req & UA_REQ_PRESET) || preg_match('/passwd/', $f)) continue;
			if (@$this->input[$f] === @$user[$f]) continue;
			$data[$f] = @$this->input[$f];
		}
		if (!empty($this->input['userpasswd'])) $data['userpwhash'] = crypt($this->input['userpasswd']);
        $this->AppendCustomFields($data, $user, $this->input, array_flip(array('username', 'usergroups', 'useremail', 'userpwhash', 'userkey', 'userpasswd', 'userpasswd2', 'useroldpasswd')));
        #echo "DEBUG: HandleEdit: setting up usergroups data in arrays<br />\n";
        $newgroups = preg_grep_keys('/^usergroups-/', $data);
        $data = array_diff_key($data, $newgroups); // strip off groups before saving
        $changed = false;
        #echo "DEBUG: HandleEdit: checking superuser to write groups<br />\n";
        if ($this->Superuser($pagename)) {
            $newgroups = preg_grep('/^@/', $newgroups); // get rid of those that are not checked
            #echo "DEBUG: HandleEdit: Calling WriteUserGroups()<br />\n";
            $changed = $this->WriteUserGroups($user['username'], $newgroups);
        }

        #echo "DEBUG: HandleEdit: checking if changed<br />\n";
		if (!$data && !$changed) return 'UAedit_success_unchanged';
        elseif (!$data) return 'UAedit_success';

        #echo "DEBUG: HandleEdit: calling WriteUser($user[username], ...)<br />\n";
		return $this->WriteUser($user['username'], $data, XL('UAedit_success'))
			? 'UAedit_success'
			: array('UAedit_fail', 'UA_contact_admin');
	}


	function HandleGroup($pagename) {
		global $UserAdminFmt, $VersionNum;
		$posted = $this->ReadInput($pagename, FALSE);

		$fields = &$this->fields[$this->action];

		if (empty($this->input['gn'])) {
			$fields = array('groupname' => UA_REQ_NOT_EMPTY);
			return NULL;
		} else $groupname = $this->input['gn'];

        if (!$this->ValidGroupname($groupname)) return(array('UAgroup_fail', 'UAgroup_needs_at'));
		if (!$this->AuthorizedGroup($pagename, $groupname, 'edit', TRUE)) return array('UAgroup_fail', 'UA_unauthorized');

        $allgroups = $this->ListGroups();
		$groupmembers = (array)$this->ReadGroup($groupname, false);
        #$groupmembers = $allgroupsmembers[$groupname];
        $allusers = (array)$this->ListUsers();
        #echo "DEBUG: allgroups=<pre>".print_r($allgroups,true)."</pre><br />\n";
        #echo "DEBUG: groupmembers=<pre>".print_r($groupmembers,true)."</pre><br />\n";
        #echo "DEBUG: allusers=<pre>".print_r($allusers,true)."</pre><br />\n";
		$UserAdminFmt = "[++$[Group:] $groupname++]\n\n";
		$UserAdminFmt .= '$[Current group members:]';
		$UserAdminFmt .= "\n(:input form action='{\$PageUrl}?action=user/{$this->action}&gn=$groupname' class='uag-users':)";
        if ($this->EnableNestedGroups)
            $allpossiblemembers = array_merge($allgroups, $allusers);
        else
            $allpossiblemembers = $allusers;
		foreach ($allpossiblemembers as $n) {
            if ($posted)
                $checked = !empty($this->input['select']) && in_array($n, $this->input['select']) ? ' checked' : '';
            else
                $checked = !empty($groupmembers) && in_array($n, $groupmembers) ? ' checked' : '';
			if (preg_match('/^\w/', $n) && $this->Exists($n)) {
				$url = $this->MakeUserLink($n, 'edit');
				$txt = "[[$url|$n]]";
			} else $txt = $n;
            if ($VersionNum >= 2002076)
                $UserAdminFmt .= "\n* (:input checkbox name='select[]' value='$n' $checked label=\"$n\":)";
            else
                $UserAdminFmt .= "\n* (:input checkbox name='select[]' value='$n' $checked:) $txt";
		}
		$UserAdminFmt .= "\n(:input submit name=postupdate value='$[Update Group Membership]':) (:input submit name=cancel value='$[Cancel]':)\n(:input end:)";

		#$UserAdminFmt .= "\n\n$[Add new group members (each on a separate line):]";
		$UserAdminFmt .= "\n(:input form action='{\$PageUrl}?action=user/{$this->action}&gn=$groupname' class='uag-new':)";
		#$UserAdminFmt .= "\n(:input textarea name=new rows=8 cols=30:)";
		#$UserAdminFmt .= "\n(:input submit name=postadd value='$[Add new members]':)\n(:input end:)";
		$UserAdminFmt .= "\n(:input end:)";

        #echo "DEBUG: UserAdminFmt=$UserAdminFmt<br />\n";
        if (!$posted) return NULL; // display the form

        list($add, $del) = $this->GroupDiff($groupmembers, $this->input['select']);
        if (!$add && !$del) {
            $UserAdminFmt = NULL; // go back to menu
            return array('UAgroup_success_unchanged');
        }

        #echo "DEBUG: del=<pre>".print_r($del,true)."</pre><br>, add=<pre>".print_r($add,true)."</pre><br>\n";
        $rtn = $this->WriteGroup($groupname, $add, $del, '', 'edit');
        if ($rtn == 'UAgroup_success') {
            $UserAdminFmt = NULL; // we want to go back to the menu
            return $rtn;
        } else
            return array($rtn, 'UA_contact_admin');
	}


	############################################################################
	## display page

	function Menu($pagename) {
		global $AuthId, $UserAdminActions;

        if ($out = $this->ReadTemplate($pagename, 'mainmenu'))
            return $out;

        SDVA($UserAdminActions, array(
                'onlyadmin' => array('group', 'install'),
                'onlyauth' => array('edit', 'loginother'),
                'anonymous' => array('resetpasswd', 'new', 'unlock', 'login2edit', 'login'),
                'onlyanonymous' => array('login2edit', 'login'),
                'unlock' => array('resetpasswd', 'new', 'unlock', 'login2edit', 'login', 'loginother'),
                'extra' => array('login2edit', 'login', 'loginother'),
                'invisible' => array('install'),
            ));
        #echo "DEBUG: UserAdminActions=<pre>".print_r($UserAdminActions,true)."</pre><br>\n";
        $actions = array_merge(array_map('strtolower',
                        str_replace("Handle", "",
                            preg_grep('/^Handle/', get_class_methods($this))))
                   , $UserAdminActions['extra']);
        #echo "DEBUG: actions=<pre>".print_r($actions,true)."</pre><br />\n";
        $actions = array_diff($actions, $UserAdminActions['invisible']); // get rid of invisible actions

		$username = $this->Username($pagename, $_REQUEST);
        #echo "DEBUG: Checking Superuser()<br />\n";
        if (!$this->Superuser($pagename)) {
            #echo "DEBUG: Peon!<br />\n";
            $actions = array_diff($actions, $UserAdminActions['onlyadmin']);
        }
        #else echo "DEBUG: Congrats, Superuser!<br />\n";
        #echo "DEBUG: AuthId=$AuthId<br />\n";
        if (empty($AuthId) && !$this->Superuser($pagename)) {
			$actions = array_intersect($actions, $UserAdminActions['anonymous']);
			$actions = array_diff($actions, $UserAdminActions['onlyauth']);
        } else
            $actions = array_diff($actions, $UserAdminActions['onlyanonymous']);
        #echo "DEBUG: 6 actions=<pre>".print_r($actions,true)."</pre><br />\n";
        if ($this->action && isset($UserAdminActions[$this->action]))
            $actions = array_intersect($actions, $UserAdminActions[$this->action]);

        #echo "DEBUG: 7 actions=<pre>".print_r($actions,true)."</pre><br />\n";
		$out = "\n!!! Available actions\n";
        foreach($actions as $a) {
            if (($link = XL("UA{$a}_link")) == "UA{$a}_link")
                $link = "{\$PageUrl}?action=user/$a";
            if (($actiontitle = XL("UA{$a}_title")) == "UA{$a}_title") {
                $actiontitle = ucfirst($a);
            }
            $out .= "* [[$link|$actiontitle]]\n";
        }
		#if (empty($AuthId)) $out .= "* [[ {\$PageUrl}?action=user/edit | $[Login to edit your account details] ]]\n";
		return $out;
	}

	function Form($pagename, $result) {
		global $InputValues, $UserAdminFmt;

		if (empty($this->fields[$this->action])) return '';

		$f = $this->fields[$this->action];
		if (count($f) == 1) switch (key($f)) {
			case 'username':
                if (!$this->Superuser($pagename, true)) return '';
				$list = '$[Please select a user:]';
				foreach ($this->ListUsers() as $n) {
					$url = $this->MakeUserLink($n, $this->action);
					$list .= "\n* [[$url|$n]]";
				}
				return $list;
			case 'groupname':
                if (!$this->Superuser($pagename, true)) return '';
				$list = '$[Please select a group:]';
				$url = $this->MakeUserLink('', 'group');
				foreach ($this->ListGroups() as $n) {
					$list .= "\n* [[$url&gn=$n|$n]]";
                }
                $list .= "\n\n$[Or enter a name for a new group:]\\\\\n";
                $list .= "(:input form name=newgroup method=GET:)New Group: (:input text gn:)(:input hidden name=action value='user/group':)(:input submit add '$[Add Group]':)(:input form end:)\n\n";
				return $list;
		}
        if ($UserAdminFmt) { // this usually means the site is using custom templates
            // make sure {{USERGROUPS}} and such are being replaced
            foreach ($f as $k => $req) {
                $pat = strtoupper('{{'.$k.'}}');
                $fm = 'Form'.ucfirst($k);
                #echo "DEBUG: pat=$pat<br />\n";
                if (strpos($UserAdminFmt, $pat) !== false && method_exists($this, $fm)) {
                    #echo "DEBUG: 1k=$k<br />\n";
                    $highlight = isset($result[$k]) ? ' class=ua-error' : '';
                    $fldstart = "\n";
                    #echo "DEBUG: 1method fm=<pre>".print_r($fm,true)."</pre><br />\n";
                    #echo "DEBUG: 1Found method $fm<br />\n";
                    $repl = $this->{$fm}($UserAdmin->pagename, $k, $fldstart);
                    $UserAdminFmt = str_replace($pat, $repl, $UserAdminFmt);
                }
            }
            return $UserAdminFmt;
        }

        #$form = "(:title $[UA{$this->action}_title]:)";
		$form .= "(:input form action='{\$PageUrl}?action=user/{$this->action}':)\n";
        $form .= "(:input default request=1:)\n";
		$form .= "\n(:table class=ua-form:)\n";
		foreach ($f as $k => $req) {
            #echo "DEBUG: k=$k<br />\n";
			$highlight = isset($result[$k]) ? ' class=ua-error' : '';
			$fldstart = "\n(:cellnr$highlight:)$[UA_txt_$k]\n(:cell$highlight:)";

            $fm = 'Form'.ucfirst($k);
            #echo "DEBUG: method fm=<pre>".print_r($fm,true)."</pre><br />\n";
            if (method_exists($this, $fm)) {
                #echo "Found method $fm<br />\n";
                $form .= $this->{$fm}($UserAdmin->pagename, $k, $fldstart);
            } else {
                $form .= $fldstart;
                ## note: autocomplete is not included by default in $InputAttrs, so setting it won't do anything
                $type = (strpos($k, 'passwd') !== FALSE) ? 'password autocomplete=off' : 'text';

                $req_note = !($req & UA_REQ_ANY);
                if ($req & UA_REQ_PRESET) {
                    if (!($req_note && empty($InputValues[$k]))) {
                        #echo "DEBUG: IN presets k=$k, InputValues[$k]={$InputValues[$k]}<br />\n";
                        $req_note = FALSE;
                        $form .= "'''{$InputValues[$k]}'''";
                        $type = 'hidden';
                    }
                }

                $form .= "(:input $type name='$k':)";
                if ($req_note) $form .= ' *';

                if ($req & UA_REQ_TWICE) {
                    $form .= "\n(:cellnr$highlight:)$[UA_txt_{$k}2]\n(:cell$highlight:)(:input $type name='{$k}2':)";
                    if ($req_note) $form .= ' *';
                }
            }
		}

		$form .= "\n(:cellnr:)\n(:cell:)(:input submit name=post value='$[UA{$this->action}_submit]':) (:input submit name=cancel value='$[Cancel]':)";
		$form .= "\n(:tableend:)\n(:input end:)";
#echo "DEBUG: form=$form<br>\n";
		return $form;
	}

    function FormUsergroups($pagename, $fld, $start)
    {
        global $InputValues, $VersionNum;

        #echo "DEBUG: FormGroupname($pagename, $fld, ...): Entering.<br />\n";
        $rtn = $start;
        #Get all values "usergroups-groupname" (the @ on the front is NOT included in the groupname)
        $newvals = preg_grep_keys("/$fld-/", $InputValues);
        $groups = array();
        foreach ($newvals as $g)
            $groups[] = $g;
        #echo "DEBUG: newvals=<pre>".print_r($newvals,true)."</pre><br />\n";
        $allgroups = $this->ListGroups();
        #echo "DEBUG: allgroups=<pre>".print_r($allgroups,true)."</pre><br />\n";
        if ($this->SuperUser($pagename))
            $disabled = '';
        else
            $disabled = "disabled='disabled'";
        foreach ($allgroups as $k=>$g) {
            $fldname = $fld.'-'.substr($g,1); // @foo becomes usergroups-foo
            $rtn .= "(:input hidden $fldname 0:)\n"; // needed because empty checkboxes don't post
            if ($VersionNum >= 2002076)
                $rtn .= "(:input checkbox $fldname \"$g\" $disabled label=\"$g\":)\n";
            else
                $rtn .= "(:input checkbox $fldname \"$g\" $disabled:) $g\n";
        }
        #echo "DEBUG: UG: InputValues=<pre>".print_r($InputValues,true)."</pre><br />\n";
        #echo "DEBUG: rtn=$rtn<br />\n";
        return $rtn;
    }

	function FormExpand($markup) {
    #echo "DEBUG: FormExpand($markup)<br />\n";
		if (!preg_match('/^\(:input select\b(.*?):\)$/', $markup, $match)) {
            if (preg_match('/\(:input select\b.*?:\)/', $markup, $match)) {
                #echo "DEBUG: match=<pre>".print_r($match,true)."</pre>\n";
                return $this->FormExpand($match[0]);
            } else {
                #echo "DEBUG: match=NONE<br />\n";
                return $markup;
            }
        }

		$opt = ParseArgs($match[1]);
		if (empty($opt['name'])) {
			if (empty($opt[''])) return $markup;
			else $opt['name'] = $opt[''][0];
		}
		switch($opt['name']) {
			case 'username':
				//$out = "(:input select name='username' value='' '':)";
				//foreach ($this->ListUsers(NULL, TRUE) as $n) $out .= "\n(:input select name='username' value='$n' '$n':)";
				foreach ($this->ListUsers(NULL, TRUE) as $n) {
					$url = $this->MakeUserLink($n, 'edit');
					$out .= "\n* [[$url|$n]]";
				}
				return $out;
			default:
				return $markup;
		}
	}

	function PrintPage($pagename, $result) {
		global $MessagesFmt, $SiteGroup, $InputValues, $PageStartFmt, $PageEndFmt, $UATemplatePage, $UserAdminFmt, $HandleUserAdminFmt, $PCache, $UAredirects;
#$ls = array($this->ListGroups('@a*'), $this->ListUsers('/^[a-z]/', TRUE));
#exit(pre_r($ls));
#echo "DEBUG: PrintPage(): result=<pre>".print_r($result,true)."</pre><br>\n";
		$status = preg_match('/(\b|_)(fail|success)(\b|_)/', implode(' ', (array)$result), $m) ? " ua-{$m[2]}" : '';

        // If the success/fail entry in $result has a corresponding entry in $UAredirects then redirect to that link
        if (($redirect_key = @preg_grep('/(?:\b|_)(?:fail|success)(?:\b|_)/', (array)$result)[0]) && $dest = @$UAredirects[$redirect_key]) {
            #echo "DEBUG: Trying to redirect pagename=$pagename, dest=$dest<br />\n";
            Redirect($pagename, $dest);
        }
        #echo "DEBUG: NOT Trying to redirect redirect_key=$redirect_key, pagename=$pagename, dest=$dest<br />\n";
        #exit;

		if ($result) $MessagesFmt[] = "<h3 class='wikimessage$status'>$[".implode("]<br />\n$[", (array)$result).']</h3>';

        #echo "DEBUG: this->input=<pre>".print_r($this->input,true)."</pre><br />\n";
		foreach((array)$this->input as $k => $v) {
			#if (!is_array($v) && (strpos($k, 'passwd') === FALSE)) $InputValues[$k] = htmlspecialchars($v, ENT_QUOTES);
            if ((strpos($k, 'passwd') === FALSE))
                if (is_array($v)) {
                    #$InputValues[$k] = array();
                    foreach ($v as $a=>$b)
                        $InputValues[$k."-".substr($b,1)] = $b;
                } else
                    $InputValues[$k] = htmlspecialchars($v, ENT_QUOTES);
        }
        #echo "DEBUG: PrintPage: InputValues=<pre>".print_r($InputValues,true)."</pre><br />\n";

		if (empty($UserAdminFmt)) {
            #echo "DEBUG: empty(useradminFmt) this->action=".$this->action.", status=$status<br />\n";
			if (!$this->action || $status) $UserAdminFmt = $this->Menu($pagename);
			else {
                #echo "SiteGroup=$SiteGroup<br>\n";
                #echo "action=$this->action<br>\n";
                $UserAdminFmt = $this->ReadTemplate($pagename, $this->action);
                #echo "DEBUG: after ReadTemplate: UserAdminFmt=<pre>".print_r($UserAdminFmt,true)."</pre><br />\n";
                $UserAdminFmt = $this->Form($pagename, $result); // uses $UserAdminFmt as a global; possibly replaced with user- or group-menu
				$UserAdminFmt .= '$[UA_return_link]';
                #echo "DEBUG: UserAdminFmt=<pre>".print_r($UserAdminFmt,true)."</pre><br />\n";
			}
			$UserAdminFmt = $this->FormExpand($UserAdminFmt);
            #echo "DEBUG: after expand: UserAdminFmt=<pre>".print_r($UserAdminFmt,true)."</pre><br />\n";
		}

#echo "DEBUG: BEFORE PCache[$pagename]=<pre>".print_r($PCache[$pagename],true)."</pre><br>\n";
		$UserAdminFmt = MarkupToHTML($pagename, "(:messages:)\n\n$UserAdminFmt");
#echo "DEBUG: AFTER PCache[$pagename]=<pre>".print_r($PCache[$pagename],true)."</pre><br>\n";

        if (!@$PCache[$pagename]['title']) {
            $title = XL("UA{$this->action}_title");
            $username = $this->Username($pagename, $_REQUEST);
            if ($username && ($this->action != 'new')) $title .= ": $username";
            PCache($pagename, array('title' => $title));
        }

		SDV($HandleUserAdminFmt, array(&$PageStartFmt, &$UserAdminFmt, &$PageEndFmt));
		PrintFmt($pagename, $HandleUserAdminFmt);
	}

    # Debug function to print off list of groups & users
    # TO USE: INCLUDE IN config.php:
    #   Markup_e('ua_dbg', '<split', '/\\(:ua_dbg:\\)/', '$GLOBALS["UserAdmin"]->ua_dbg()');
    function ua_dbg() {
        $groups = $this->ReadGroups();
        $users = $this->ListUsers();
        $rtn = '!!Defined Groups (no recursion):\\\\'."\n\n";
        foreach ($groups as $g=>$m) {
            if ($_SESSION['authlist'][$g] > 0)
                $rtn .= "* %red%$g%%\n";
            else
                $rtn .= "* $g\n";
            foreach ((array)$m as $x)
                if ($_SESSION['authlist'][$x] > 0 || $_SESSION['authlist']['id:'.$x] > 0)
                    $rtn .= "** %red%$x%%\n";
                else
                    $rtn .= "** $x\n";
        }
        if ($this->EnableNestedGroups) {
            $rgroups = $this->ReadGroups(null,true);
            $rtn .= "\n\n".'!!Final Groups (with recursion):'."\n\n";
            foreach ($rgroups as $g=>$m) {
                if ($_SESSION['authlist'][$g] > 0)
                    $rtn .= "* %red%$g%%\n";
                else
                    $rtn .= "* $g\n";
                foreach ((array)$m as $x)
                    if ($_SESSION['authlist'][$x] > 0 || $_SESSION['authlist']['id:'.$x] > 0)
                        $rtn .= "** %red%$x%%\n";
                    else
                        $rtn .= "** $x\n";
            }
        }
        $rtn .= "\n\n".'!!Users:\\'."\n\n";
        foreach ($users as $u) {
            if ($_SESSION['authlist']['id:'.$u] > 0)
                $rtn .= "* %red%$u%%\n";
            else
                $rtn .= "* $u\n";
        }
        return $rtn;
    }
}