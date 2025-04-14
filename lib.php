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
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK.
    if ($context->contextlevel != CONTEXT_BLOCK) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'course_image' && $filearea !== 'category_image') {
        return false;
    }

    // Make sure the user is logged in and has access to the block.
    require_login($course);

    // Check the relevant capabilities - these may vary depending on your filearea.
    if (!has_capability('moodle/block:view', $context)) {
        return false;
    }

    // Leave this line out if you set the itemid to null in make_pluginfile_url.
    $itemid = array_shift($args); // The first item in the $args array.

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

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Callback to add footer elements to the page.
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $cardcoursesnode The node to add module settings to
 */
function block_card_courses_extend_settings_navigation($settingsnav, $cardcoursesnode) {
    global $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == SITEID) {
        return;
    }

    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('moodle/block:edit', $PAGE->context)) {
        return;
    }

    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $url = new moodle_url('/blocks/card_courses/manage.php', array('courseid' => $PAGE->course->id));
        $cardcoursesnode = $settingnode->add(
            get_string('managecardcourses', 'block_card_courses'),
            $url,
            navigation_node::NODETYPE_LEAF,
            'card_courses',
            'card_courses',
            new pix_icon('i/settings', '')
        );
        $cardcoursesnode->showinflatnavigation = true;
    }
}

/**
 * Get the courses to display in the block.
 *
 * @param int $categoryid The category ID to get courses from
 * @param int $limit Maximum number of courses to return
 * @return array Array of course objects
 */
function block_card_courses_get_courses($categoryid, $limit = 12) {
    global $DB;

    $params = array();
    $categoryselect = '';
    
    if ($categoryid > 0) {
        $categoryselect = "AND c.category = :categoryid";
        $params['categoryid'] = $categoryid;
    }

    $sql = "SELECT c.id, c.shortname, c.fullname, c.summary, c.summaryformat, 
                   c.visible, c.category, cc.name as categoryname
            FROM {course} c
            JOIN {course_categories} cc ON c.category = cc.id
            WHERE c.id <> :siteid
            $categoryselect
            AND c.visible = 1
            ORDER BY c.sortorder ASC";
    
    $params['siteid'] = SITEID;
    
    return $DB->get_records_sql($sql, $params, 0, $limit);
}

/**
 * Get the categories to display in the block.
 *
 * @param int $parentid The parent category ID
 * @param int $limit Maximum number of categories to return
 * @return array Array of category objects
 */
function block_card_courses_get_categories($parentid = 0, $limit = 6) {
    global $DB;

    $params = array('parent' => $parentid);
    $sql = "SELECT cc.id, cc.name, cc.description, cc.descriptionformat, 
                   cc.parent, cc.coursecount, cc.visible
            FROM {course_categories} cc
            WHERE cc.parent = :parent
            AND cc.visible = 1
            ORDER BY cc.sortorder ASC";
    
    return $DB->get_records_sql($sql, $params, 0, $limit);
}

/**
 * Get the course image URL for a course.
 *
 * @param stdClass $course The course object
 * @return string URL of the course image
 */
function block_card_courses_get_course_image_url($course) {
    global $OUTPUT;

    $coursecontext = context_course::instance($course->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($coursecontext->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);
    
    if ($file = reset($files)) {
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out();
    }
    
    return $OUTPUT->image_url('defaultcourse', 'block_card_courses')->out();
}

/**
 * Get the category image URL for a category.
 *
 * @param stdClass $category The category object
 * @return string URL of the category image
 */
function block_card_courses_get_category_image_url($category) {
    global $OUTPUT;

    $categorycontext = context_coursecat::instance($category->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($categorycontext->id, 'coursecat', 'image', 0, 'itemid, filepath, filename', false);
    
    if ($file = reset($files)) {
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out();
    }
    
    return $OUTPUT->image_url('defaultcategory', 'block_card_courses')->out();
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return void
 */
function block_card_courses_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;

    if ($iscurrentuser && has_capability('block/card_courses:view', context_system::instance())) {
        $url = new moodle_url('/blocks/card_courses/mycourses.php', array('id' => $USER->id));
        $node = new core_user\output\myprofile\node('miscellaneous', 'cardcourses', 
            get_string('mycardcourses', 'block_card_courses'), null, $url);
        $tree->add_node($node);
    }
}