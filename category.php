<?php
require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/filelib.php');

$categoryid = required_param('id', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 12, PARAM_INT);

// Validate category exists.
try {
    $category = core_course_category::get($categoryid);
} catch (moodle_exception $e) {
    throw new moodle_exception('invalidcategoryid');
}

$context = context_coursecat::instance($categoryid);

// Check if user can view this category.
if (!$category->is_uservisible()) {
    require_login();
    if (has_capability('moodle/category:view', context_system::instance())) {
        throw new moodle_exception('cannotviewcategory', 'error', $CFG->wwwroot.'/course/index.php');
    } else {
        throw new moodle_exception('cannotviewcategory', 'error', $CFG->wwwroot.'/course/index.php');
    }
}

require_login();

$PAGE->set_context($context);
$PAGE->set_url('/blocks/card_courses/category.php', ['id' => $categoryid]);
$PAGE->set_title($category->get_formatted_name());
$PAGE->set_heading($category->get_formatted_name());
$PAGE->set_pagelayout('incourse');
$PAGE->add_body_class('block-card-courses');

// Add breadcrumb navigation.
$PAGE->navbar->add(get_string('categories'), new moodle_url('/course/index.php'));
$PAGE->navbar->add($category->get_formatted_name());

echo $OUTPUT->header();

// Get courses with pagination - returns core_course_list_element objects
$courses = $category->get_courses([
    'recursive' => true,
    'offset' => $page * $perpage,
    'limit' => $perpage,
    'sort' => ['sortorder' => 'ASC']
]);

$totalcourses = $category->get_courses_count(['recursive' => true]);

$data = [
    'category' => [
        'name' => $category->get_formatted_name(),
        'description' => format_text($category->description, $category->descriptionformat, ['context' => $context])
    ],
    'courses' => [],
    'config' => ['wwwroot' => $CFG->wwwroot]
];

foreach ($courses as $course) {
    $coursecontext = context_course::instance($course->id);
    
    if (!core_course_category::can_view_course_info($course)) {
        continue;
    }

    $data['courses'][] = [
        'id' => $course->id,
        'fullname' => format_string($course->fullname, true, ['context' => $coursecontext]),
        'summary' => format_text($course->summary, $course->summaryformat, ['context' => $coursecontext]),
        'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
        'image_url' => get_course_image($course)
    ];
}

echo $OUTPUT->render_from_template('block_card_courses/category_view', $data);

// Add pagination.
if ($totalcourses > $perpage) {
    echo $OUTPUT->paging_bar($totalcourses, $page, $perpage, $PAGE->url);
}

echo $OUTPUT->footer();

function get_course_image(core_course_list_element $course): string {
    global $OUTPUT;
    
    foreach ($course->get_course_overviewfiles() as $file) {
        if ($file->is_valid_image()) {
            return moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                null,
                $file->get_filepath(),
                $file->get_filename()
            )->out();
        }
    }
    
    return $OUTPUT->image_url('defaultcourse', 'block_card_courses')->out();
}