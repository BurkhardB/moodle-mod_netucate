<?php // $Id: index.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $

/**
 * This page lists all the instances of netucate in a particular course
 *
 * @author  Burkhard Bartelt <burkhard.bartelt@netucate.com>
 * @version $Id: index.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $
 * @package mod/netucate
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course

if (! $course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

require_course_login($course);

add_to_log($course->id, 'netucate', 'view all', "index.php?id=$course->id", '');


/// Get all required stringsnetucate
$strnetucates = get_string('modulenameplural', 'netucate');
$strnetucate  = get_string('modulename', 'netucate');


/// Print the header
$navlinks = array();
$navlinks[] = array('name' => $strnetucates, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strnetucates, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data
if (! $netucates = get_all_instances_in_course('netucate', $course)) {
    notice('There are no instances of netucate', "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)
$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($netucates as $netucate) {
    if (!$netucate->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$netucate->coursemodule.'">'.format_string($netucate->name).'</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="view.php?id='.$netucate->coursemodule.'">'.format_string($netucate->name).'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($netucate->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

print_heading($strnetucates);
print_table($table);

print_footer($course);

?>
