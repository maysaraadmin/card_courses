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
    if ($context->contextlevel != CONTEXT_BLOCK) {
        return false;
    }

    if ($filearea !== 'course_image' && $filearea !== 'category_image') {
        return false;
    }

    require_login($course);

    if (!has_capability('moodle/block:view', $context)) {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_card_courses', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}