<?php //$Id: mod_form.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $

/**
 * This file defines the main netucate configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 * Also, it's usual to use these fields:
 *   - intro: one htmlarea element to describe the activity
 *            (will be showed in the list of activities of
 *             netucate type (index.php) and in the header
 *             of the netucate main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_netucate_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE;
        global $USER;

        $mform =& $this->_form;

        $a  = optional_param('update', 0, PARAM_INT);  // netucate instance ID from table course_modules

        if ($a) {

            $netucate_instance = get_record('course_modules', 'id', $a);
            $activity_obj = get_record('netucate', 'id', $netucate_instance->instance);
            $activity_id = $activity_obj->activity_id;

            $ilinc = new ilnetucateXMLAPI();
            $ilinc->getActivity($activity_id);

            $response = $ilinc->sendRequest();
            if($response->isError())
            {
                error($response->getErrorMsg());
            }

            $netucateuserinfofield = get_record('user_info_field','shortname','netucateuserid');
            $netucateuserinfofieldid = $netucateuserinfofield->id;
            $moodle_user = get_record('user_info_data', 'data', $response->data['return']['EncryptedLeaderID'], 'fieldid', $netucateuserinfofieldid);

            $ilinc = new ilnetucateXMLAPI();
            $ilinc->GetActivitySchedule($activity_id);

            $response = $ilinc->sendRequest();
            if($response->isError())
            {
                error($response->getErrorMsg());
            }
            if ($response->data['return']['ScheduleType'] == 'single') {
                $scheduletype = 1;
                $joinbuffer = $response->data['return']['JoinBuffer'];
                $duration = $response->data['return']['Duration'];
                $startdate = strtotime($response->data['return']['StartDate']);
            }
            if ($response->data['return']['ScheduleType'] == 'open') {
                $scheduletype = 0;
            }

        }

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('netucatename', 'netucate'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the required "intro" field to hold the description of the instance
        $mform->addElement('htmleditor', 'intro', get_string('netucateintro', 'netucate'));
        $mform->setType('intro', PARAM_RAW);
        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

    /// Adding the "Activity Type" field; disable if update
        if ($a) {
            $mform->addElement('select', 'activity_type', get_string('activitytype', 'netucate'), array('class'=>'LearnLinc', 'meeting'=>'MeetingLinc', 'conference'=>'ConferenceLinc', 'support'=>'SupportLinc'),$attributes='disabled');
        } else {
            $mform->addElement('select', 'activity_type', get_string('activitytype', 'netucate'), array('class'=>'LearnLinc', 'meeting'=>'MeetingLinc', 'conference'=>'ConferenceLinc', 'support'=>'SupportLinc'));
        }

        $mform->setHelpButton('activity_type', array('activitytype', get_string('activitytype', 'netucate'), 'netucate'));
        
        $schedulearray=array();
        $schedulearray[] = &MoodleQuickForm::createElement('radio', 'schedule', '', get_string('open','netucate'), 0);
        $schedulearray[] = &MoodleQuickForm::createElement('radio', 'schedule', '', get_string('single','netucate'), 1);
        $mform->addGroup($schedulearray, 'scheduletype', get_string('Schedule','netucate'), array(' '), true);
        if ($a) {
            $mform->setDefault('scheduletype[schedule]', $scheduletype);
        }
        else
        {
            $mform->setDefault('scheduletype[schedule]', 0);
        }
        
        $date_selector_settings = array(
            'startyear' => 2010,
            'stopyear'  => 2020,
            'timezone'  => 99,
            'applydst'  => true,
            'optional'  => false
        );

        $mform->addElement('date_time_selector', 'starttime', get_string('DateTime', 'netucate'), $date_selector_settings);

        if (isset($startdate)){
            $mform->setDefault('starttime', $startdate);
        }

        $mform->disabledIf('starttime', 'scheduletype[schedule]', 'eq', 0);

        $duration_settings = array(
            '15'  => "15 " . get_string('minutes', 'netucate'),
            '30'  => "30 " . get_string('minutes', 'netucate'),
            '45'  => "45 " . get_string('minutes', 'netucate'),
            '60'  => "1 " . get_string('hour', 'netucate'),
            '90'  => "1,5 " . get_string('hours', 'netucate'),
            '120' => "2 " . get_string('hours', 'netucate'),
            '150' => "2,5 " . get_string('hours', 'netucate'),
            '180' => "3 " . get_string('hours', 'netucate'),
            '240' => "4 " . get_string('hours', 'netucate'),
            '-1'  => get_string('other', 'netucate')
        );

        $mform->addElement('select', 'duration', get_string('Duration', 'netucate'), $duration_settings);
        $mform->disabledIf('duration', 'scheduletype[schedule]', 'eq', 0);

        if (isset($duration)) {
            if (array_key_exists($duration, $duration_settings)) {
                $mform->setDefault('duration', $duration);
            } else if ($duration){
                $mform->setDefault('duration', -1);
            }
        }

        if (isset($duration)) {
            $hours = floor($duration/60);
            $minutes = $duration % 60;
        }
        
        $duration2_settings = array(
            '0'  => 0,
            '10'  => 10,
            '15'  => 15,
            '20'  => 20,
            '25'  => 25,
            '30'  => 30,
            '35'  => 35,
            '40' => 40,
            '45' => 45,
            '50' => 50,
            '55' => 55
        );
        
        if (isset($duration)) {
            $attributes=array('size'=>'2', 'maxlength'=>'2', 'value'=>$hours);
        } else {
            $attributes=array('size'=>'2', 'maxlength'=>'2');
        }
        
        
        $durationarray=array();
        $durationarray[] =& $mform->createElement('text', 'duration1', '', $attributes);
        $durationarray[] =& $mform->createElement('select', 'duration2', '', $duration2_settings);
        $mform->addGroup($durationarray, 'durationarray', get_string('Duration1', 'netucate'), ' ', false);

        if (isset($minutes)) {
            $mform->setDefault('duration2', $minutes);
        }

        $mform->disabledIf('duration1', 'duration', 'neq', -1);
        $mform->disabledIf('duration1', 'scheduletype[schedule]', 'eq', 0);
        $mform->disabledIf('duration2', 'duration', 'neq', -1);
        $mform->disabledIf('duration2', 'scheduletype[schedule]', 'eq', 0);
        
        if (isset($joinbuffer)) {
            $attributes=array('size'=>'2', 'maxlength'=>'2','value'=>$joinbuffer);
        } else {
            $attributes=array('size'=>'2', 'maxlength'=>'2','value'=>20);
        }
        
        $joinbufferarray=array();
        $joinbufferarray[] =& $mform->createElement('text', 'joinbuffer', '', $attributes);
        $joinbufferarray[] =& $mform->createElement('static', '', '', get_string('joinbuffer1', 'netucate'));
        $mform->addGroup($joinbufferarray, 'joinbufferarray', get_string('joinbuffer', 'netucate'), ' ', false);

        $mform->disabledIf('joinbuffer', 'scheduletype[schedule]', 'eq', 0);
        
        $mform->disabledIf('scheduletype[schedule]', 'activity_type', 'eq', 'support');
        $mform->disabledIf('starttime', 'activity_type', 'eq', 'support');
        $mform->disabledIf('duration', 'activity_type', 'eq', 'support');
        $mform->disabledIf('duration1', 'activity_type', 'eq', 'support');
        $mform->disabledIf('duration2', 'activity_type', 'eq', 'support');
        $mform->disabledIf('joinbuffer', 'activity_type', 'eq', 'support');

        ///get edtiting teachers and non editing teachers in course context
        ///only editing teachers are potentially Instructors, non editing teachers are potentially Activity Assistants
        $courseid = $COURSE->id;
        $contextcourse = get_context_instance(CONTEXT_COURSE, $courseid);

        $roleseditingteacher = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW, $contextcourse);
        $editingteachers = array();
        $role = current($roleseditingteacher);
        $editingteachers = get_role_users($role->id, $contextcourse, false, 'u.id,u.firstname,u.lastname,u.username,u.email', 'u.lastname ASC');

        $users1 = $editingteachers;

        $usernames = array();
        if (!empty($users1)) {
            foreach ($users1 as $user1) {
                $key = $user1->id;
                $value = $user1->firstname. " " .$user1->lastname;
                $usernames[$key] = $value;
            }
            $key = $USER->id;
            $value = $USER->firstname. " " .$USER->lastname;
            $usernames[$key] = $value;
        } else {
            $key = $USER->id;
            $value = $USER->firstname. " " .$USER->lastname;
            $usernames[$key] = $value;
        }

        if ($a) {
            ///get edtiting teachers and non editing teachers in course context
            ///only editing teachers are potentially Instructors, non editing teachers are potentially Activity Assistants
            $contextmodule = get_context_instance(CONTEXT_MODULE, $a);
            $roleseditingteacher = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW, $contextmodule);
            $editingteachers = array();
            $role = current($roleseditingteacher);
            $editingteachers = get_role_users($role->id, $contextmodule, false, 'u.id,u.firstname,u.lastname,u.username,u.email', 'u.lastname ASC');

            $users = $editingteachers;
 
            if (!empty($users)) {
                foreach ($users as $user1) {
                    $key = $user1->id;
                    $value = $user1->firstname. " " .$user1->lastname;
                    $usernames[$key] = $value;
                }
                $key = $USER->id;
                $value = $USER->firstname. " " .$USER->lastname;
                $usernames[$key] = $value;
            } else {
                if ($moodle_user) {
                    $user = get_complete_user_data('id', $moodle_user->userid, $mnethostid=null);
                   $key = $moodle_user->userid;
                    $value = $user->firstname. " " .$user->lastname;
                    $usernames[$key] = $value;
                }
                $key = $USER->id;
                $value = $USER->firstname. " " .$USER->lastname;
                $usernames[$key] = $value;
            }
        }
        
        $mform->addElement('select', 'instructor', get_string('instructor', 'netucate'), $usernames);
        $mform->setHelpButton('instructor', array('instructor', get_string('instructor', 'netucate'), 'netucate'));

        if ($a) {
            if ($moodle_user) {
                $mform->setDefault('instructor', $moodle_user->userid);
            }
        }

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------

        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }
}

?>
