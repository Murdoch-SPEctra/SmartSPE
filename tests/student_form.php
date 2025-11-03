<?php

require(__DIR__ . '/../../../config.php');
require_login();

use mod_smartspe\form\attempt_form;


$testFiles = glob(__DIR__ . '/studentform/*.php');

$PAGE->set_context(context_system::instance());

foreach ($testFiles as $file) {
    $test = require($file);

    $mform = new attempt_form(new moodle_url('/mod/smartspe/tests/student_form.php', ['id' => $test['fixture']['cmid']]), $test['fixture']);
    $errors = $mform->validation($test['formdata'], []);

    echo "<pre>";
    mtrace("Test $file ...\n");
    mtrace("Validation errors:\n"); 
    if(empty($errors)) {
        mtrace("No validation errors.\n");
    }else{        
        print_r($errors);
    }
    echo "</pre>";
}
