<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'iomad';
$CFG->dbuser    = 'root';
$CFG->dbpass    = 'password';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = 'http://localhost/iomad';
$CFG->dataroot  = 'C:\\xampp\\iomad_new';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

// SMTP settings
$CFG->smtp_host = 'smtp.office365.com';
$CFG->smtp_user = 'info@tech-sculpt.com';
$CFG->smtp_pass = 'tst@98765';
$CFG->smtp_port = '587';
$CFG->smtp_secure = 'tls';

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
