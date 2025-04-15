<?php
require_once('../../config.php');
require_once($CFG->libdir . '/filelib.php');

$categoryid = required_param('id', PARAM_INT);
$category = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);
$context = context_coursecat::instance($categoryid);

$PAGE->set_context($context);
$PAGE->set_url('/blocks/card_courses/category.php', ['id' => $categoryid]);
$PAGE->set_title($category->name);
$PAGE->set_heading($category->name);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

// Get all visible courses in this category
$courses = $DB->get_records('course', 
    ['category' => $categoryid, 'visible' => 1],
    'sortorder ASC'
);

$data = [
    'category' => [
        'name' => format_string($category->name, true, ['context' => $context]),
        'description' => format_text($category->description, $category->descriptionformat, ['context' => $context])
    ],
    'courses' => []
];

foreach ($courses as $course) {
    $coursecontext = context_course::instance($course->id);
    
    $data['courses'][] = [
        'id' => $course->id,
        'fullname' => format_string($course->fullname, true, ['context' => $coursecontext]),
        'summary' => format_text($course->summary, $course->summaryformat, ['context' => $coursecontext]),
        'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
        'image_url' => get_course_image($course)
    ];
}

echo $OUTPUT->render_from_template('block_card_courses/category_view', $data);
echo $OUTPUT->footer();

function get_course_image($course) {
    global $OUTPUT, $CFG;
    require_once($CFG->libdir . '/filelib.php');

    try {
        $context = context_course::instance($course->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'sortorder DESC, id DESC', false);
        
        if ($file = reset($files)) {
            if ($file->is_valid_image()) {
                return moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                )->out();
            }
        }
    } catch (Exception $e) {
        debugging('Error getting course image: '.$e->getMessage(), DEBUG_NORMAL);
    }
    
    return null; // Return null when no valid image exists
}