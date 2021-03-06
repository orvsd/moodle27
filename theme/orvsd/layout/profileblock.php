<?php 
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @author    Shaun Daubney
 * @package   theme_orvsd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

	function get_content () {
	global $USER, $CFG, $SESSION, $COURSE;
	$wwwroot = '';
	$signup = '';}

	if (empty($CFG->loginhttps)) {
		$wwwroot = $CFG->wwwroot;
	} else {
		$wwwroot = str_replace("http://", "https://", $CFG->wwwroot);
	}

if (!isloggedin() or isguestuser()) {
    require_once($CFG->dirroot . '/auth/googleoauth2/lib.php');
    // Load the CSS social buttons
    echo '
    <script language="javascript">
        linkElement = document.createElement("link");
        linkElement.rel = "stylesheet";
        linkElement.href = "' . $CFG->wwwroot . '/auth/googleoauth2/csssocialbuttons/css/zocial.css";
        document.head.appendChild(linkElement);
    </script>
    ';
	echo '<form class="navbar-form pull-left" method="post" action="'.$wwwroot.'/login/index.php?authldap_skipntlmsso=1">';
    //auth_googleoauth2_display_buttons();
    $displayprovider = ((empty($authprovider) || $authprovider == 'google' || $allauthproviders) && get_config('auth/googleoauth2', 'googleclientid'));
    $providerdisplaystyle = $displayprovider?'display:inline-block;padding:10px;':'display:none;';
    echo '<a class="zocial googleplus" href="https://accounts.google.com/o/oauth2/auth?client_id='.
              get_config('auth/googleoauth2', 'googleclientid') .'&redirect_uri='.$CFG->wwwroot .'/auth/googleoauth2/google_redirect.php&scope=https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email&response_type=code">
                Sign-in with Google
            </a> ';
	echo '</form>';
} else { 

 echo html_writer::start_tag('div', array('id'=>'profilepic','class'=>'profilepic'));
		
			echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'"><img src="'.$CFG->wwwroot.'/user/pix.php?file=/'.$USER->id.'/f1.jpg" width="80px" height="80px" title="'.$USER->firstname.' '.$USER->lastname.'" alt="'.$USER->firstname.' '.$USER->lastname.'" /></a>';
		
	echo html_writer::end_tag('div');
	echo '<ul class="nav">

<li class="dropdown">
<a class="dropdown-toggle" data-toggle="dropdown" href="#cm_submenu_5">
'.$USER->firstname.'
<b class="caret"></b>
</a>
<ul class="dropdown-menu profiledrop">';
echo '<li>';
echo '<a href="'.$CFG->wwwroot.'/my">';
echo get_string('mycourses');
echo '</a>';
echo '</li>';

echo '<li>';
echo '<a href="'.$CFG->wwwroot.'/user/profile.php">';
echo get_string('viewprofile');
echo '</a>';
echo '</li>';

echo '<li>';
echo '<a href="'.$CFG->wwwroot.'/user/edit.php">';
echo get_string('editmyprofile');
echo '</a>';
echo '</li>';

echo '<li>';
echo '<a href="'.$CFG->wwwroot.'/user/files.php">';
echo get_string('myfiles');
echo '</a>';
echo '</li>';

echo '<li>';
echo '<a href="'.$CFG->wwwroot.'/calendar/view.php?view=month">';
echo get_string('calendar','calendar');
echo '</a>';
echo '</li>';

if ($hasemailurl) {
echo '<li>';
echo '<a href="'.$PAGE->theme->settings->emailurl.'">';
echo get_string('email','theme_orvsd');
echo '</a>';
echo '</li>';
}

echo '<li>';
echo '<a href="'.$CFG->wwwroot.'/login/logout.php">';
echo get_string('logout');
echo '</a>';
echo '</li>';


echo '</ul></li></ul>';

}?>
