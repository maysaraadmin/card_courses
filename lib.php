<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Serve the files from the block_card_courses file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function block_card_courses_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected.
    if ($context->contextlevel != CONTEXT_BLOCK) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'defaultcategory' && $filearea !== 'defaultcourse') {
        return false;
    }

    // Make sure the user is logged in and has access to the module.
    require_login($course, true, $cm);

    // Check the relevant capabilities.
    if (!has_capability('moodle/block:view', $context)) {
        return false;
    }

    // Leave this line out if you set the itemid to null in make_pluginfile_url.
    $itemid = array_shift($args);

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_card_courses', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // Send the file.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}