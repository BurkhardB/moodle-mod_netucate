<?php // $Id: settings.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $

$vars = array();

$vars[] = new admin_setting_configtext('netucate_api_url', get_string('api_url', 'netucate'),
                   get_string('api_url_desc', 'netucate'), 'https://<customerid>.netucate.net/api/API30Moodle.asp', PARAM_TEXT, '50');

$vars[] = new admin_setting_configtext('netucate_api_timeout', get_string('api_timeout', 'netucate'),
                   get_string('api_timeout_desc', 'netucate'), '20', PARAM_INT, '2');

$vars[] = new admin_setting_configtext('netucate_admin_login', get_string('admin_login', 'netucate'),
                   get_string('admin_login_desc', 'netucate'), 'XMLAPIUser', PARAM_TEXT);

$vars[] = new admin_setting_configpasswordunmask('netucate_admin_password', get_string('admin_password', 'netucate'),
                   get_string('admin_password_desc', 'netucate'), '');

$vars[] = new admin_setting_configtext('netucate_customer_id', get_string('customer_id', 'netucate'),
                   get_string('customer_id_desc', 'netucate'), 'CustomerID');

foreach ($vars as $var) {
    $var->plugin = 'mod/netucate';
    $settings->add($var);
}

?>
