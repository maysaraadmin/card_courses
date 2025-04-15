<?php
require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/blocklib.php');

$blockid = required_param('blockid', PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$showcourses = optional_param('showcourses', false, PARAM_BOOL);

// Validate block instance
$blockinstance = $DB->get_record('block_instances', ['id' => $blockid], '*', MUST_EXIST);
$context = context_block::instance($blockid);

// Set up page
$PAGE->set_context($context);
$PAGE->set_url('/blocks/card_courses/view.php', [
    'blockid' => $blockid,
    'categoryid' => $categoryid,
    'showcourses' => $showcourses
]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'block_card_courses'));
$PAGE->set_heading(get_string('pluginname', 'block_card_courses'));

// Check permissions
require_login();
require_capability('block/card_courses:view', $context);

// Get block instance
$block = block_instance('card_courses', $blockinstance);
if (!$block) {
    throw new moodle_exception('cannotfindblock', 'block_card_courses');
}

// Load CSS
$PAGE->requires->css('/blocks/card_courses/styles.css');

// Get content directly from the block
$content = $block->get_content();

echo $OUTPUT->header();
echo $content->text;
echo $OUTPUT->footer();