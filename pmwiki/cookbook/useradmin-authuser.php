<?php if (!defined('PmWiki')) exit();
# vim: set ts=4 sw=4 et:
/*	=== UserAdminAuthUser ===
 *	Copyright 2010-2015 Peter Bowers
 *
 *      Extension to Cookbook/UserAdmin to allow self-registration info
 *      and other user info to be stored in SiteAdmin.AuthUser in an
 *      extended syntax as compared to the standard syntax for that page.
 *
 *	To install, add the following line to your configuration file :
		include_once("$FarmD/cookbook/useradmin-authuser.php");
 *
 *	For more information, please see the online documentation at
 *		http://www.pmwiki.org/wiki/Cookbook/UserAdmin
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

$RecipeInfo['UserAdmin-AuthUser']['Version'] = '2015-05-16';

if (!IsEnabled($EnableAuthorTracking,1)) Abort('$EnableAuthorTracking required for UserAdmin-AuthUser');
$AuthUserPat = "/^\\s*([@\\w][^\\s:]*)\\s*:([^:\\n]*)(?::(.*$)|$)/m";

if (strncmp($action, 'user', 4)) return;
require_once('useradmin-core.php');

class UserAdminAuthUser extends UserAdmin {

    var $AuthUserProps = array();
    # Any user that has edit privileges on SiteAdmin.AuthUser automatically
    # should have capabilities to make any administrative changes in this
    # module.
    var $SuperuserFunc = 'SuperuserAuthUserEdit';

    // Get entities, using the extended syntax in AuthUser 
    //   (this is ParseArgs() format after a second colon)
    // user: HASH : useremail='user@example.com' userfoo='bar' ...
    function AuthUserProps($userpat = null, $forceread = false)
    {
        if ($this->AuthUserProps && !$forceread)
            return $this->array_match($userpat, $this->AuthUserProps);
        if (!$this->AuthUserData) $this->AuthUserPage(); // cache AuthUser
        $this->AuthUserProps = array();
        foreach($this->AuthUserData as $m) {
            if (!@$m[3]) continue;
            $fields = preg_grep_keys("/^user/", ParseArgs($m[3]));
            $this->AuthUserProps[$m[1]] = empty($this->AuthUserProps[$m[1]]) ? $fields : array_merge($this->AuthUserProps[$m[1]], $fields);
        }
#echo "AuthUserProps: Full Props=<pre>".print_r($this->AuthUserProps,true)."</pre><br>\n";
#echo "AuthUserProps(pat=".print_r($pat,true)."): Returning ".pre_r($this->array_match($userpat, $this->AuthUserProps))."<br>\n";
		return $this->array_match($userpat, $this->AuthUserProps);
	}

    function ReadUser($username, $readgroup=false)
    {
        $data = (array)parent::ReadUser($username, $readgroup);
        if ($data && $props = (array)$this->AuthUserProps($username)) {
            $data = array_merge($data, $props[$username]);
        }
        return $data;
    }

	// Write the data for a given user back to SiteAdmin.AuthUser
	//
	//   Note that if a given @group is included in a normal group specification
	//   then it will be stripped from the authtokens as an inline group
	//   specification.
	//   Thus this:
	//      @group1: user1, user2
	//      user1: HASH @group1 @group2: email="user1@example.com"
	//   will be changed to this:
	//      @group1: user1, user2
	//      user1: HASH @group2: email="user1@example.com"
	//
	// MISSING FUNCTIONALITY:
	//   If a user is a member of @group1 by means of a normal group 
	//   specification in SiteAdmin.AuthUser but they are removed from that
	//   group by manipulations in the forms that user will NOT be removed
	//   from @group1 in the current implementation.
    function WriteUser($username, $data, $csum='', $auth=false) 
    {
		global $AuthUserPageFmt, $Now, $EditFunctions, $IsPagePosted;
        parent::WriteUser($username, $data, $csum, $auth);
		SDV($AuthUserPageFmt, '$SiteAdminGroup.AuthUser');
		$pn = FmtPageName($AuthUserPageFmt, '');
		$olddata = $this->ReadUser($username);
		$data = array_merge($olddata, $data);
		Lock(2);
            if ($auth) $page = RetrieveAuthPage($pn, $auth, TRUE);
            else $page = ReadPage($pn, 0); // often AuthUser is unreadable
			if (!$page) Abort("?cannot write to $pn"); 
			$new = $page;

#echo "UserAdminAuthUser::Write: Data=<pre>".print_r($data,true)."</pre><br>\n";
#echo "Starting with this text: <pre><br>\n".print_r($page['text'],true)."</pre><br>\n";
			$authtoken = '';
#echo "entering loop: data=<pre>".print_r($data,true)."</pre><br>\n";
			foreach ($data as $k => $v) {
#echo "top loop k=$k, v=".print_r($v,true)."<br>\n";
				if (!$v) continue;
				switch ($k) {
				case 'username': 
				case '#': 
				case '': 
					break;
				case 'userpwhash':
					$authtoken .= ' '.$v;
					break;
				case 'usergroups': 
                    echo "DEVELOP: writing usergroups is NOT fully implemented!<br>\n";
                    /*
					// Get a full list of group memberships which are in the
					// "normal" group specification syntax ($mynormalgroups)
echo "usergroups: $v<br>\n";
					$mynormalgroups = array();
					foreach ($this->normalGCache as $g => $members)
						if (in_array($username, $members)) 
							$mynormalgroups[] = $g;
					// Now create $myinlinegroups as all groups which are not
					// in the $mynormalgroups (above)
					$mynewgroups = array_unique(preg_split("/[\\s,]+/", $v, null, PREG_SPLIT_NO_EMPTY));
					$myinlinegroups = '';
echo "mynewgroups=<pre>".print_r($mynewgroups,true)."</pre><br>\n";
					foreach ($mynewgroups as $g) {
						if (!in_array($g, $mynormalgroups))
							$myinlinegroups .= ' '.$g;
echo "g=$g, myinlinegroups=$myinlinegroups<br>\n";
					}
					if ($myinlinegroups) $authtoken .= $myinlinegroups;

					// Now check if there are groups in $mynormalgroups which
					// are NOT in $mynewgroups 
					//    if so then issue an error message (until implemented)
					foreach ($mynormalgroups as $g)
						if (!in_array($g, $mynewgroups))
							echo "ERROR: Cannot remove group ($g) membership from \"normal\" groups via forms.  Please edit ".$this->AuthUserPageName." page manually.<br>\n";

                     */
					break;
				default:
					$userinfo .= " $k='$v'";
					break;
				}
#echo "After $k=>$v: authtoken=$authtoken, userinfo=$userinfo<br>\n";
			}
			$newline = "$username: $authtoken : $userinfo";
            if (preg_match_all("/^\\s*$username\\s*:[^\\n]*$/m", $page['text'], $userlines)) {
#echo "user=<pre>".print_r($user,true)."</pre><br>\n";
				$new['text'] = str_replace($userlines[0][0], $newline, $new['text']);
#echo "DEBUG: Deleting userlines=<pre>".print_r(array_slice($userlines[0], 1),true)."</pre><br>\n";
                $new['text'] = str_replace(array_slice($userlines[0], 1), '', $new['text']);
            } else
				$new['text'] .= "\n$newline";
#echo "Would have written this text: <pre><br>\n".print_r($new['text'],true)."</pre><br>\n";
			$new['csum'] = $csum;
			if ($csum) $new["csum:$Now"] = $csum;
			UpdatePage($pn, $page, $new);
		Lock(0);
		return $IsPagePosted;
	}

	// Return true/false whether the given $user is a member of the given 
	// $group
	function UserInGroup($user, $group)
	{
		$users = $this->_readallusers(); // sets $normalGCache
		return (in_array($user, $this->normalGCache[$group]) || 
		        preg_match("/\\b$group\\b/", $users[$user]['authtokens']));
	}

}

$UserAdmin = new UserAdminAuthUser($pagename, null, array());
