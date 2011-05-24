<?php  // $Id: lib.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $

/**
 * Library of functions and constants for module netucate
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the netucate specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */

require_once ('class.ilnetucateXMLAPI.php');

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $netucate An object from the form in mod_form.php
 * @return int The id of the newly inserted netucate record
 */
function netucate_add_instance($netucate) {
    
    //because of formatting the starttime with am/pm
    setlocale(LC_TIME, "en");

    $netucate->timecreated = time();

    global $USER;

    $return = get_record('user', 'id', $netucate->instructor);

    $netucateuserinfofield = get_record('user_info_field','shortname','netucateuserid');
    $netucateuserinfofieldid = $netucateuserinfofield->id;
    //when a assigned user hits this page -> create an iLinc user if not exists
    if (! $netucate_user = get_record('user_info_data', 'userid', $return->id, 'fieldid', $netucateuserinfofieldid )) {
        $_POST['Fobject']['firstname'] = $return->firstname;
        $_POST['Fobject']['lastname'] = $return->lastname;
        $_POST['Fobject']['email'] = $return->email;
        $_POST['Fobject']['password'] = md5(microtime());

        $ilinc = new ilnetucateXMLAPI();
        $ilinc->addUser($_POST['Fobject']);
        $response = $ilinc->sendRequest();
        if($response->isError())
        {
            error($response->getErrorMsg());
        }
        $user_obj -> userid = $return->id;
        $user_obj -> fieldid = $netucateuserinfofieldid;
        $user_obj -> data = $response->data['return']['EncryptedUserID'];
        insert_record('user_info_data', $user_obj);
    }

    $netucate_user = get_record('user_info_data','userid', $return->id, 'fieldid',$netucateuserinfofieldid );
    $leader_id = $netucate_user->data;
    if (! $leader_id ) {
        $_POST['Fobject']['firstname'] = $return->firstname;
        $_POST['Fobject']['lastname'] = $return->lastname;
        $_POST['Fobject']['email'] = $return->email;
        $_POST['Fobject']['password'] = md5(microtime());

        $ilinc = new ilnetucateXMLAPI();
        $ilinc->addUser($_POST['Fobject']);
        $response = $ilinc->sendRequest();
        if($response->isError())
        {
            error($response->getErrorMsg());
        }
        $user_obj -> id = $netucate_user->id;
        $user_obj -> userid = $return->id;
        $user_obj -> fieldid = $netucateuserinfofieldid;
        $user_obj -> data = $response->data['return']['EncryptedUserID'];
        update_record('user_info_data', $user_obj);
    }

    $netucate_user = get_record('user_info_data','userid', $return->id, 'fieldid',$netucateuserinfofieldid );
    $leader_id = $netucate_user->data;
    
    $_POST['Fobject']['title'] = $netucate->name;
    $_POST['Fobject']['activity_type'] = $netucate->activity_type;
    $_POST['Fobject']['desc'] = $netucate->intro;
    $_POST['Fobject']['leader_id'] = $leader_id;

    $ilinc = new ilnetucateXMLAPI();
    $ilinc->addActivity($_POST['Fobject']);

    $response = $ilinc->sendRequest();
    if($response->isError())
    {
        error($response->getErrorMsg());
    }
    $activity_id = $response->data['return']['ActivityID'];
    $netucate->activity_id = $activity_id;

    $encrypted_activity_id = $response->data['return']['EncryptedActivityID'];
    $netucate->encrypted_activity_id = $encrypted_activity_id;

    if ($netucate->scheduletype['schedule'] == 0) {
        $_POST['Fobject']['scheduletype'] = 'open';
    }
    if ($netucate->scheduletype['schedule'] == 1) {
        $_POST['Fobject']['scheduletype'] = 'single';
        $_POST['Fobject']['startdate'] = strftime('%m/%d/%Y %I:%M %p',$netucate->starttime);
        if ($netucate->duration != -1) {
            $_POST['Fobject']['duration'] = $netucate->duration;
        }
        if ($netucate->duration == -1) {
            $_POST['Fobject']['duration'] = $netucate->duration1 * 60 + $netucate->duration2;            
        }        
        $_POST['Fobject']['joinbuffer'] = $netucate->joinbuffer;
    }

    if ($netucate->activity_type != 'support') {
        $ilinc = new ilnetucateXMLAPI();
        $ilinc->EditActivitySchedule($activity_id, $_POST['Fobject']);

        $response = $ilinc->sendRequest();
        if($response->isError())
        {
            error($response->getErrorMsg());
        }

    }

    ///Entry as an Event for Calendar
    if ($returnid = insert_record('netucate', $netucate) and ($netucate->scheduletype['schedule'] == 1)) {
        $event = NULL;
        $event->name        = $netucate->name;
        $event->description = $netucate->intro;
        $event->courseid    = $netucate->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'netucate';
        $event->instance    = $returnid;
        $event->eventtype   = $netucate->scheduletype['schedule'];
        $event->timeduration = $netucate->duration * 60;
        $event->timestart   = $netucate->starttime;

        add_event($event);
    }

    return $returnid;

}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $netucate An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function netucate_update_instance($netucate) {

    //because of formatting the starttime as am/pm
    setlocale(LC_TIME, "en");

    $netucate->timemodified = time();
    $netucate->id = $netucate->instance;

    $return = get_record('netucate', 'id', $netucate->id);
    $activity_id = $return->activity_id;
    
    $netucateuserinfofield = get_record('user_info_field','shortname','netucateuserid');
    $netucateuserinfofieldid = $netucateuserinfofield->id;

    $_POST['Fobject']['title'] = $netucate->name;
    //check whether an entry exists, if not -> addUser
    if (! $netucate_user = get_record('user_info_data', 'userid', $netucate->instructor, 'fieldid', $netucateuserinfofieldid)) {
        $user = get_complete_user_data('id', $netucate->instructor, $mnethostid=null);
        $_POST['Fobject']['firstname'] = $user->firstname;
        $_POST['Fobject']['lastname'] = $user->lastname;
        $_POST['Fobject']['password'] = md5(microtime());
        $_POST['Fobject']['email'] = $user->email;
        $ilinc = new ilnetucateXMLAPI();
        $ilinc->addUser($_POST['Fobject']);
        $response = $ilinc->sendRequest();
            if($response->isError())
        {
            error($response->getErrorMsg());
        }
        $user_obj -> userid = $user->id;
        $user_obj -> fieldid = $netucateuserinfofieldid;
        $user_obj -> data = $response->data['return']['EncryptedUserID'];
        insert_record('user_info_data', $user_obj);  
    }

    $netucate_user = get_record('user_info_data', 'userid', $netucate->instructor, 'fieldid',$netucateuserinfofieldid);
    $leader_id = $netucate_user->data;
    if (! $leader_id ) {
        $user = get_complete_user_data('id', $netucate->instructor, $mnethostid=null);
        $_POST['Fobject']['firstname'] = $user->firstname;
        $_POST['Fobject']['lastname'] = $user->lastname;
        $_POST['Fobject']['email'] = $user->email;
        $_POST['Fobject']['password'] = md5(microtime());

        $ilinc = new ilnetucateXMLAPI();
        $ilinc->addUser($_POST['Fobject']);
        $response = $ilinc->sendRequest();
        if($response->isError())
        {
            error($response->getErrorMsg());
        }
        $user_obj -> id = $netucate_user->id;
        $user_obj -> userid = $user->id;
        $user_obj -> fieldid = $netucateuserinfofieldid;
        $user_obj -> data = $response->data['return']['EncryptedUserID'];
        update_record('user_info_data', $user_obj);
    }

    $netucate_user = get_record('user_info_data','userid', $netucate->instructor, 'fieldid',$netucateuserinfofieldid );
    $_POST['Fobject']['leader_id'] = $netucate_user->data;

    $ilinc = new ilnetucateXMLAPI();
    $ilinc->editActivity($activity_id,$_POST['Fobject']);

    $response = $ilinc->sendRequest();
    if($response->isError())
    {
        error($response->getErrorMsg());
    }

    if ($netucate->scheduletype['schedule'] == 0) {
        $_POST['Fobject']['scheduletype'] = 'open';
    }
    if ($netucate->scheduletype['schedule'] == 1) {
        $_POST['Fobject']['scheduletype'] = 'single';
        $_POST['Fobject']['startdate'] = strftime('%m/%d/%Y %I:%M %p',$netucate->starttime);
        
        if ($netucate->duration != -1) {
            $_POST['Fobject']['duration'] = $netucate->duration;
        }
        if ($netucate->duration == -1) {
            $_POST['Fobject']['duration'] = $netucate->duration1 * 60 + $netucate->duration2;
        }
        $_POST['Fobject']['joinbuffer'] = $netucate->joinbuffer;
    }

    $ilinc = new ilnetucateXMLAPI();
    $ilinc->EditActivitySchedule($activity_id, $_POST['Fobject']);

    $response = $ilinc->sendRequest();
    if($response->isError())
    {
        error($response->getErrorMsg());
    }

    # You may have to add extra stuff in here #

    return update_record('netucate', $netucate);
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function netucate_delete_instance($id) {

    if (! $netucate = get_record('netucate', 'id', $id)) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    $return = get_record('netucate', 'id', $id);
    $activity_id = $return->activity_id;
    $ilinc = new ilnetucateXMLAPI();
    $ilinc->DeleteActivity($activity_id);

    $response = $ilinc->sendRequest();

    if (! delete_records('netucate', 'id', $netucate->id)) {
        $result = false;
    }
    return $result;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function netucate_user_outline($course, $user, $mod, $netucate) {
    return $return;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function netucate_user_complete($course, $user, $mod, $netucate) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in netucate activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function netucate_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function netucate_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of netucate. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $netucateid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function netucate_get_participants($netucateid) {
    return false;
}


/**
 * This function returns if a scale is being used by one netucate
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $netucateid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function netucate_scale_used($netucateid, $scaleid) {
    $return = false;

    //$rec = get_record("netucate","id","$netucateid","scale","-$scaleid");
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


/**
 * Checks if scale is being used by any instance of netucate.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any netucate
 */
function netucate_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('netucate', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function netucate_install() {
    $result = true;
    $timenow = time();
    $sysctx  = get_context_instance(CONTEXT_SYSTEM);

    //create user_info_category
    if (! get_record('user_info_category','name','netucate')) {
        $userinfocategorynetucate = new object();
        $userinfocategorynetucate->name = 'netucate';
        $userinfocategorynetucate->sortorder = count_records('user_info_category') + 1;
        $result = insert_record('user_info_category', $userinfocategorynetucate);
    }

    $userinfocategorynetucateid  = get_field('user_info_category', 'id', 'name', 'netucate');

    if (! $data = get_record('user_info_field','categoryid',$userinfocategorynetucateid)) {
        $data->shortname = 'netucateuserid';
        $data->name = 'netucate UserID (is automatically created)';
        $data->datatype = 'text';
        $data->description = 'This is the netucate UserID, which is automatically created.';
        $data->categoryid = $userinfocategorynetucateid;
        $data->sortorder = 1;
        $data->locked = 1;
        $data->visible = 1;
        $data->param2 = 30;
        $data->param2 = 2048;
        $data->param3 = 0;

        $result = insert_record('user_info_field', $data);
    }
    return $result;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function netucate_uninstall() {
    $result = true;

    if ($netucateuserinfofield = get_record('user_info_field','shortname','netucateuserid')) {
        $netucateuserinfofieldid = $netucateuserinfofield->id;
        $result = $result && delete_records('user_info_data', 'fieldid', $netucateuserinfofieldid);
        $result = $result && delete_records('user_info_field', 'id', $netucateuserinfofieldid);
    }
    
    if ($userinfocategory = get_record('user_info_category','name','netucate')) {
        $result = $result && delete_records('user_info_category', 'name', 'netucate');
    }

    if (record_exists('config_plugins','plugin','mod/netucate')) {
        $result = $result && delete_records('config_plugins', 'plugin', 'mod/netucate');
    }

    return $result;
}

?>
