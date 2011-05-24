<?php // $Id: version.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $

/**
 * Code fragment to define the version of netucate
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author  Burkhard Bartelt <burkhard.bartelt@netucate.com>
 * @version $Id: version.php,v 1.0.0.0 2010/05/20 12:00:00 Burkhard Bartelt Exp $
 * @package mod/netucate
 */

$module->version  = 2011021400;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007101570;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
