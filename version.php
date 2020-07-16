<?php

/**
 * Attendance Register plugin version info
 *
 * @package    mod
 * @subpackage attendanceregister
 * @version $Id
 *
 * @author Lorenzo Nicora <fad@nicus.it>
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
$plugin->version  = 2020071601;
$plugin->requires = 2014051200;  // Requires this Moodle version
//$plugin->cron     = 300;
$plugin->component = 'mod_attendanceregister'; // Full name of the plugin (used for diagnostics)
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = "2020.07.16.01 cineca build: 2020071601"; // User-friendly version number
