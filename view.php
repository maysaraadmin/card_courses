<?php

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/card_courses/block_card_courses.php');

$blockid = required_param('blockid', PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$showcourses = optional_param('showcourses', false, PARAM_BOOL);

// Validate block instance.
$blockinstance = $DB->get_record('block_instances', array('id' => $blockid), '*', MUST_EXIST);
$context = context_block::instance($blockid);

// Set up page.
$PAGE->set_context($context);
$PAGE->set_url('/blocks/card_courses/view.php', array('blockid' => $blockid, 'categoryid' => $categoryid, 'showcourses' => $showcourses));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pluginname', 'block_card_courses'));
$PAGE->set_title(get_string('pluginname', 'block_card_courses'));

// Check permissions.
require_login();
require_capability('moodle/block:view', $context);

// Get block instance and content.
$block = block_instance('card_courses', $blockinstance);

echo $OUTPUT->header();
echo $block->get_content()->text;
echo $OUTPUT->footer();