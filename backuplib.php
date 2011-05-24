<?php // $Id: backuplib.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $
    /// This php script contains all the stuff to backup/restore
    /// netucate mods

    //This function executes all the backup procedure about this mod
    function netucate_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        //Iterate over netucate table
        $netucates = get_records ("netucate","course",$preferences->backup_course,"id");
        if ($netucates) {
            foreach ($netucates as $netucate) {
                if (backup_mod_selected($preferences,'netucate',$netucate->id)) {
                    $status = netucate_backup_one_mod($bf,$preferences,$netucate);
                }
            }
        }
        return $status;
    }

    function netucate_backup_one_mod($bf,$preferences,$netucate) {

        global $CFG;

        if (is_numeric($netucate)) {
            $netucate = get_record('netucate','id',$netucate);
        }

        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print netucate data
        fwrite ($bf,full_tag("ID",4,false,$netucate->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"netucate"));
        fwrite ($bf,full_tag("NAME",4,false,$netucate->name));
        fwrite ($bf,full_tag("INTRO",4,false,$netucate->intro));
        fwrite ($bf,full_tag("ACTIVITY_ID",4,false,$netucate->activity_id));
        fwrite ($bf,full_tag("ENCRYPTED_ACTIVITY_ID",4,false,$netucate->encrypted_activity_id));
        fwrite ($bf,full_tag("ACTIVITY_TYPE",4,false,$netucate->activity_type));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$netucate->timemodified));
        fwrite ($bf,full_tag("TIMECREATED",4,false,$netucate->timecreated));
        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }


    //Return an array of info (name,value)
    function netucate_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {

        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += netucate_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","netucate");
        if ($ids = netucate_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }
        return $info;
    }

    //Return an array of info (name,value)
    function netucate_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function netucate_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of netucates
        $buscar="/(".$base."\/mod\/netucate\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@netucateINDEX*$2@$',$content);

        //Link to netucate view by moduleid
        $buscar="/(".$base."\/mod\/netucate\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@netucateVIEWBYID*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of netucates id
    function netucate_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT c.id, c.course
                                 FROM {$CFG->prefix}netucate c
                                 WHERE c.course = '$course'");
    }

    //Returns an array of assignment_submissions id
    function netucate_message_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT m.id , m.netucateid
                                 FROM {$CFG->prefix}netucate_messages m,
                                      {$CFG->prefix}netucate c
                                 WHERE c.course = '$course' AND
                                       m.netucateid = c.id");
    }

    //Returns an array of netucate id
    function netucate_message_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT m.id , m.netucateid
                                 FROM {$CFG->prefix}netucate_messages m
                                 WHERE m.netucateid = $instanceid");
    }
?>
