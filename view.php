<?php  // $Id: view.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $

/**
 * This page prints a particular instance of netucate
 *
 * @author  Burkhard Bartelt <burkhard.bartelt@netucate.com>
 * @version $Id: view.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $
 * @package mod/netucate
 */

//require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once("../../config.php");
require_once(dirname(__FILE__).'/lib.php');
require_once ('class.ilnetucateXMLAPI.php');

$mystyle = 'font-size: .9em; padding: 2px 6px 1px 6px; margin-right: 3px; border-top: 1px solid #BBB; border-left: 1px solid #9D9D9D; border-right: 1px solid #9D9D9D; border-bottom: 1px solid #666; background: #C0C0C0 url(images/tab_unselected.gif) repeat-x 0 0; color: #000; width: 150px; text-decoration: none;';

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // netucate instance ID

if ($id) {
    if (! $cm = get_coursemodule_from_id('netucate', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (! $netucate = get_record('netucate', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

} else if ($a) {
    if (! $netucate = get_record('netucate', 'id', $a)) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', 'id', $netucate->course)) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('netucate', $netucate->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

add_to_log($course->id, "netucate", "view", "view.php?id=$cm->id", "$netucate->id");

// show some info for guests
if (isguestuser()) {
    $navigation = build_navigation('', $cm);
    print_header_simple(($netucate->name), '', $navigation,
                  '', '', true, '', navmenu($course, $cm));
    $wwwroot = $CFG->wwwroot.'/login/index.php';
    if (!empty($CFG->loginhttps)) {
        $wwwroot = str_replace('http:','https:', $wwwroot);
    }

    notice_yesno(get_string('noguests', 'netucate').'<br /><br />'.get_string('liketologin'),
            $wwwroot, $CFG->wwwroot.'/course/view.php?id='.$course->id);

    print_footer($course);
    exit;
}

/// Print the page header
$strnetucates = get_string('modulenameplural', 'netucate');
$strnetucate  = get_string('modulename', 'netucate');

$navlinks = array();
$navlinks[] = array('name' => $strnetucates, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => ($netucate->name), 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(($netucate->name), '', $navigation, '', '', true,
    update_module_button($cm->id, $course->id, $strnetucate), navmenu($course, $cm));

$netucateuserinfofield = get_record('user_info_field','shortname','netucateuserid');
$netucateuserinfofieldid = $netucateuserinfofield->id;

$userdatauptodate = 0;
$updateuserdata = 0;

//when a assigned user hits this page -> create an iLinc user if not exists
if (! $netucate_user = get_record('user_info_data', 'userid', $USER->id, 'fieldid',$netucateuserinfofieldid )) {
    $_POST['Fobject']['firstname'] = $USER->firstname;
    $_POST['Fobject']['lastname'] = $USER->lastname;
    $_POST['Fobject']['password'] = md5(microtime());
    $_POST['Fobject']['email'] = $USER->email;
    
    $ilinc = new ilnetucateXMLAPI();
    $ilinc->addUser($_POST['Fobject']);
    $response = $ilinc->sendRequest();
        if($response->isError())
    {
        error($response->getErrorMsg());
    }
    $user_obj -> userid = $USER->id;
    $user_obj -> fieldid = $netucateuserinfofieldid;
    $user_obj -> data = $response->data['return']['EncryptedUserID'];
    insert_record('user_info_data', $user_obj);
    $userdatauptodate = 1;
}
else
{
    if (! get_field('user_info_data', 'data', 'userid', $USER->id, 'fieldid',$netucateuserinfofieldid )) {
        $_POST['Fobject']['firstname'] = $USER->firstname;
        $_POST['Fobject']['lastname'] = $USER->lastname;
        $_POST['Fobject']['password'] = md5(microtime());
        $_POST['Fobject']['email'] = $USER->email;
        $ilinc = new ilnetucateXMLAPI();
        $ilinc->addUser($_POST['Fobject']);
        $response = $ilinc->sendRequest();
            if($response->isError())
        {
            error($response->getErrorMsg());
        }
        $user_obj -> userid = $USER->id;
        $user_obj -> fieldid = $netucateuserinfofieldid;
        $user_obj -> data = $response->data['return']['EncryptedUserID'];
        $user_obj -> id = $netucate_user->id;
        update_record('user_info_data', $user_obj);
        $userdatauptodate = 1;
    }
}

$netucate_user = get_record('user_info_data', 'userid', $USER->id, 'fieldid',$netucateuserinfofieldid);
$user_id = $netucate_user->data;

// check if userdata up to date
if (! $userdatauptodate) {
    $ilinc = new ilnetucateXMLAPI();
    $ilinc->GetUser($user_id);
    $response = $ilinc->sendRequest();
    if($response->isError())
    {
        error($response->getErrorMsg());
    }

    $firstname = $response->data['return']['FirstName'];
    $lastname = $response->data['return']['LastName'];
    $email = $response->data['return']['Email'];

    if ($firstname != $USER->firstname) {
        $firstname = $USER->firstname;
        $updateuserdata = 1;
    }
    if ($lastname != $USER->lastname) {
        $lastname = $USER->lastname;
        $updateuserdata = 1;
    }
    if ($email != $USER->email) {
        $email = $USER->email;
        $updateuserdata = 1;
    }
    
    if ($updateuserdata) {
        $_POST['Fobject']['userid'] = $user_id;
        $_POST['Fobject']['firstname'] = $firstname;
        $_POST['Fobject']['lastname'] = $lastname;
        $_POST['Fobject']['email'] = $email;

        $ilinc = new ilnetucateXMLAPI();
        $ilinc->EditUser($_POST['Fobject']);
        $response = $ilinc->sendRequest();
            if($response->isError())
        {
            error($response->getErrorMsg());
        }
    }   
}

$activity_id = $netucate->activity_id;
$isassistant = 0;
//check if the user is already registered for this activity as assistant
//if yes and not has_capability('mod/netucate:activityassistant', $context) -> unregister them
$ilinc = new ilnetucateXMLAPI();
$ilinc->GetUserRegistration($user_id);

$response = $ilinc->sendRequest();
if($response->isError())
{
    //do nothing -> user is not assistant for any activity
}
else
{
foreach ($response->data['ActivityIDs'] as $activity_id1 => $data)
        {
            if ($activity_id1 == $activity_id) {
                $isassistant = 1;
            }
        }
}

$ilinc = new ilnetucateXMLAPI();
$ilinc->getActivity($activity_id);

$response = $ilinc->sendRequest();
if($response->isError())
{
    error($response->getErrorMsg());
}
$EncryptedLeaderID = $response->data['return']['EncryptedLeaderID'];
$ActivityTitle = $response->data['return']['Title'];
$ActivityType = $response->data['return']['ActivityType'];

switch($ActivityType) {
    case "class":
        $icon = '<img src="images/img_class.png" title="'.get_string('class', 'netucate').'">';
        $instructorlabelling = get_string('primaryreferent', 'netucate');
        break;
     case "meeting":
        $icon = '<img src="images/img_meeting.png" title="'.get_string('meeting', 'netucate').'">';
        $instructorlabelling = get_string('meetingleader', 'netucate');
        break;
    case "conference":
        $icon = '<img src="images/img_conference.png" title="'.get_string('conference', 'netucate').'">';
        $instructorlabelling = get_string('conferenceleader', 'netucate');
        break;
    case "support":
        $icon = '<img src="images/img_room.png" title="'.get_string('support', 'netucate').'">';
        $instructorlabelling = get_string('roomtechnician', 'netucate');
        break;
}

$isinstructor = 0;
if ($user_id == $EncryptedLeaderID) {
    $isinstructor = 1;
}

$netucateuserinfofield = get_record('user_info_field','shortname','netucateuserid');
$netucateuserinfofieldid = $netucateuserinfofield->id;
$moodle_user = get_record('user_info_data', 'data', $EncryptedLeaderID, 'fieldid', $netucateuserinfofieldid);

if ($moodle_user) {
    $instructor = get_complete_user_data('id', $moodle_user->userid, $mnethostid=null);
    $instructorname = $instructor->firstname. " " .$instructor->lastname;
}
    else
{
    $instructorname = '';
}
//GetActivitySchedule
$ilinc = new ilnetucateXMLAPI();
$ilinc->GetActivitySchedule($activity_id);
$response = $ilinc->sendRequest();
if($response->isError())
{
    error($response->getErrorMsg());
}
$isopen = 0;
if ($response->data['return']['ScheduleType'] == 'single') {
    $scheduletype = 1;
    $joinbuffer = $response->data['return']['JoinBuffer'];
    $duration = $response->data['return']['Duration'];
    $startdate = strtotime($response->data['return']['StartDate']);
    $startdatedisplay = userdate($startdate, '', $timezone=99, $fixday = false);
    if ((time() > $startdate - $joinbuffer * 60) and (time() <= $startdate + $duration * 60)) {
        $isopen = 1;
    }
}
if ($response->data['return']['ScheduleType'] == 'open') {
    $isopen = 1;
    $startdatedisplay = get_string('open', 'netucate');
    $duration = '-';
    $joinbuffer = '-';
}

$contextcourse = get_context_instance(CONTEXT_COURSE, $course->id);
$contextmodule = get_context_instance(CONTEXT_MODULE, $id);

if ((has_capability('moodle/legacy:teacher', $contextcourse) || has_capability('moodle/legacy:teacher', $contextmodule)) and !has_capability('moodle/site:doanything', $contextcourse) and ! $isassistant) {    
    if (! $isinstructor) {
            $ilinc = new ilnetucateXMLAPI();
            $ilinc->RegisterUser($activity_id, $user_id);
            $response = $ilinc->sendRequest();
            if($response->isError())
            {
                error($response->getErrorMsg());
            }
            else
            {
               $isassistant = 1;
            }
    } else {
        error(get_string('apierror-240', 'netucate'));
    }
}
else if (!has_capability('moodle/legacy:teacher', $contextcourse) and !has_capability('moodle/legacy:teacher', $contextmodule) and (!has_capability('moodle/site:doanything', $contextcourse) and $isassistant)) {
     //unregister them as assistant
    $ilinc = new ilnetucateXMLAPI();
    $ilinc->UnRegisterUser($activity_id, $user_id);

    $response = $ilinc->sendRequest();
    if($response->isError())
    {
        error($response->getErrorMsg());
    }
    else
    {
        $isassistant = 0;
    }
    
}

//display join only for instructor, assistant or if $isopen
if ($isinstructor || $isassistant || $isopen) {

    $ilinc = new ilnetucateXMLAPI();
    $ilinc->GetJoinUrl($user_id,$activity_id);

    $response = $ilinc->sendRequest();
    if($response->isError())
    {
        error($response->getErrorMsg());
    }
    else
    {
        $joinurl = "<div style='" . $mystyle . "'><a href=".$response->data['return']['cdata']." target='_blank'>" . get_string('join', 'netucate') ."</a></div>";
    }
}
else
{
   $joinurl = "<div class='highlight2' style='font-size:8pt'>" . get_string('activityiscurrentlyclosed', 'netucate') . "</div>";
}

if (($isinstructor || $isassistant) and !$isopen) {
    $joinurl .= " " . "<p class='highlight2' style='font-size:8pt'>" . get_string('activityiscurrentlyclosedforparticipants', 'netucate') . "</p>";
}

$ilinc = new ilnetucateXMLAPI();
$ilinc->GetUploadUserPictureURL($user_id);

$response = $ilinc->sendRequest();
if($response->isError())
{
    error($response->getErrorMsg());
}
else
{
    $strhelpbuttonupload = get_string('helpbuttonupload', 'netucate');
    $helpbuttonupload = helpbutton('upload', $strhelpbuttonupload, 'netucate', true, false, '', true);
    $uploaduserpictureurl = "<div style='" . $mystyle . "'><a href=".$response->data['return']['cdata']." target='_blank'>" . get_string('uploaduserpicture', 'netucate') ."</a></div>";
}
$loginurl = '';

if ($isinstructor){
    switch($ActivityType) {
        case "class":
            $yourrole = get_string('primaryreferent', 'netucate');
            break;
         case "meeting":
            $yourrole = get_string('meetingleader', 'netucate');
            break;
        case "conference":
            $yourrole = get_string('conferenceleader', 'netucate');
            break;
        case "support":
            $yourrole = get_string('roomtechnician', 'netucate');
            break;
    }
}
elseif ($isassistant)
{
    $yourrole = get_string('assistant', 'netucate');
}
else
{
    $yourrole = get_string('participant', 'netucate');
}

if (($user_id == $EncryptedLeaderID) || $isassistant) {
    
    $ilinc = new ilnetucateXMLAPI();
    $ilinc->GetLoginUrl($user_id);

    $response = $ilinc->sendRequest();
    if($response->isError())
    {
        error($response->getErrorMsg());
    }
    else
    {
        $strhelpbuttoncc = get_string('helpbuttoncc', 'netucate');
        $helpbuttoncc = helpbutton('cc', $strhelpbuttoncc, 'netucate', true, false, '', true);
        $loginurl = "<div style='" . $mystyle . "'><a href=".$response->data['return']['cdata']." target='_blank'>" . get_string('login', 'netucate') ."</a></div>";
    }
}

print_box('<h3><img src="icon.gif">&nbsp;' . format_string($netucate->name) . '</h3>' . $netucate->intro, $classes='generalbox boxaligncenter boxwidthwide centerpara', $ids='', $return=false);

if ($loginurl) {
    $displaytable->align = array ('center', 'center');
    $displaytable->wrap = array ('nowrap','nowrap');
    $displaytable->data[] = array ('<div style=float:left;>'. $helpbuttoncc . '</div>' . $loginurl, '<div style=float:right;>'. $helpbuttonupload . '</div>' . $uploaduserpictureurl);
} else {
    $displaytable->align = array ('center');
    $displaytable->data[] = array ('<div style=float:right;>'. $helpbuttonupload . '</div>' . $uploaduserpictureurl);
}
$displaytable->width = '1%';
print_table($displaytable);
unset($displaytable);

$displaytable->head  = array (get_string('type', 'netucate'), get_string('DateTime', 'netucate'), get_string('joinallowed', 'netucate'), get_string('Duration', 'netucate'),  $instructorlabelling,  get_string('yourrole', 'netucate'), get_string('Action', 'netucate'));
$displaytable->align = array ('center','left','center','center','left','left','center');
$displaytable->wrap = array ('nowrap','nowrap','nowrap','nowrap','nowrap','nowrap','nowrap');
$displaytable->data[] = array ($icon, $startdatedisplay, $joinbuffer . get_string('minutesbeforestarttime', 'netucate'), $duration, $instructorname, $yourrole, $joinurl);

$displaytable->width = '80%';
print_table($displaytable);
unset($displaytable);

$warningstr = '';

/// list the assistants in course context
$context = get_context_instance(CONTEXT_COURSE, $course->id);
$roles = get_roles_with_capability('moodle/legacy:teacher', CAP_ALLOW, $context);
$courseassistants = array();
$role = current($roles);
$courseassistants = get_role_users($role->id, $context, false, 'u.id,u.firstname,u.lastname,u.username,u.email', 'u.lastname ASC');

echo '<br>';
print_box_start($classes='generalbox boxaligncenter boxwidthwide', $ids='', $return=false);
print_heading(get_string('assistants in course', 'netucate'), $align='', $size=4, $class='main', $return=false);

if (!empty($courseassistants)) {
    $displaytable1->head  = array (get_string('lastname', 'netucate'), get_string('firstname', 'netucate'),  get_string('email', 'netucate'), '');
    $displaytable1->align = array ('left', 'left', 'left','left');
    $displaytable1->wrap = array ('nowrap', 'nowrap', 'nowrap','nowrap');
    foreach ($courseassistants as $courseassistant) {
        $warningstr = '';
        if ($courseassistant->id == $instructor->id) {
            $warningstr = "<div class='highlight2' style='font-size:8pt'>" . get_string('assistantisinstructor', 'netucate') . "</div>";
        }
        $displaytable1->data[] = array ($courseassistant->lastname, $courseassistant->firstname, $courseassistant->email, $warningstr);
    }
    unset($courseassistants);
} else {
    echo "<div align='center'>" . get_string('none', 'netucate') . "</div>";
}

$displaytable1->width = '50%';
print_table($displaytable1);
print_box_end($return=false);
unset($displaytable1);

/// list the assistants in module context
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$roles = get_roles_with_capability('moodle/legacy:teacher', CAP_ALLOW, $context);
$activityassistants = array();
$role = current($roles);
$activityassistants = get_role_users($role->id, $context, false, 'u.id,u.firstname,u.lastname,u.username,u.email', 'u.lastname ASC');

echo '<br>';

print_box_start($classes='generalbox boxaligncenter boxwidthwide', $ids='', $return=false);
print_heading(get_string('assistants in activity', 'netucate'), $align='', $size=4, $class='main', $return=false);

if (!empty($activityassistants)) {
    $displaytable1->head  = array (get_string('lastname', 'netucate'), get_string('firstname', 'netucate'),  get_string('email', 'netucate'), '');
    $displaytable1->align = array ('left', 'left', 'left','left');
    $displaytable1->wrap = array ('nowrap', 'nowrap', 'nowrap', 'nowrap');
    foreach ($activityassistants as $activityassistant) {
        $warningstr = '';
        if ($activityassistant->id == $instructor->id) {
            $warningstr = "<div class='highlight2' style='font-size:8pt'>" . get_string('assistantisinstructor', 'netucate') . "</div>";
        }        
        $displaytable1->data[] = array ($activityassistant->lastname, $activityassistant->firstname, $activityassistant->email, $warningstr);
    }
    unset($activityassistants);
} else {
    echo "<divalign='center'>" . get_string('none', 'netucate') . "</div>";
}

$displaytable1->width = '50%';
print_table($displaytable1);
print_box_end($return=false);
unset($displaytable1);

/// list the participants in course context
$context = get_context_instance(CONTEXT_COURSE, $course->id);
$roles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW, $context);
$courseparticipants = array();
$role = current($roles);
$courseparticipants = get_role_users($role->id, $context, false, 'u.id,u.firstname,u.lastname,u.username,u.email', 'u.lastname ASC');
print_box_start($classes='generalbox boxaligncenter boxwidthwide', $ids='', $return=false);
print_heading(get_string('students in course', 'netucate'), $align='', $size=4, $class='main', $return=false);

if (!empty($courseparticipants)) {
    $displaytable1->head  = array (get_string('lastname', 'netucate'), get_string('firstname', 'netucate'),  get_string('email', 'netucate'));
    $displaytable1->align = array ('left', 'left', 'left');
    $displaytable1->wrap = array ('nowrap', 'nowrap', 'nowrap');
    foreach ($courseparticipants as $courseparticipant) {
        $displaytable1->data[] = array ($courseparticipant->lastname, $courseparticipant->firstname, $courseparticipant->email);
    }
    unset($courseparticipants);
} else {
    echo "<div align='center'>" . get_string('none', 'netucate') . "</div>";
}

$displaytable1->width = '50%';
print_table($displaytable1);
print_box_end($return=false);
unset($displaytable1);

/// list the participants in module context
$context = get_context_instance(CONTEXT_MODULE, $cm->id);    
$roles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW, $context);   
$activityparticipants = array();
$role = current($roles);
$activityparticipants = get_role_users($role->id, $context, false, 'u.id,u.firstname,u.lastname,u.username,u.email', 'u.lastname ASC');

print_box_start($classes='generalbox boxaligncenter boxwidthwide', $ids='', $return=false);
print_heading(get_string('students in activity', 'netucate'), $align='', $size=4, $class='main', $return=false);

if (!empty($activityparticipants)) {
    $displaytable1->head  = array (get_string('lastname', 'netucate'), get_string('firstname', 'netucate'),  get_string('email', 'netucate'));
    $displaytable1->align = array ('left', 'left', 'left');
    $displaytable1->wrap = array ('nowrap', 'nowrap', 'nowrap');
    foreach ($activityparticipants as $activityparticipant) {
        $displaytable1->data[] = array ($activityparticipant->lastname, $activityparticipant->firstname, $activityparticipant->email);
    }
    unset($activityparticipants);
 } else {
    echo "<div align='center'>" . get_string('none', 'netucate') . "</div>";
}

$displaytable1->width = '50%';
print_table($displaytable1);
print_box_end($return=false);
unset($displaytable1);

/// Finish the page
print_footer($course);

?>
