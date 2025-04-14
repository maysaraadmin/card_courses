<?php

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/card_courses/block_card_courses.php');

$blockid = required_param('blockid', PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$showcourses = optional_param('showcourses', false, PARAM_BOOL);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/card_courses/view.php', array('blockid' => $blockid, 'categoryid' => $categoryid, 'showcourses' => $showcourses));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pluginname', 'block_card_courses'));

require_login();

// Get block instance and config
$blockinstance = $DB->get_record('block_instances', array('id' => $blockid), '*', MUST_EXIST);
$block = block_instance('card_courses', $blockinstance);

echo $OUTPUT->header();
echo $block->get_content()->text;
echo $OUTPUT->footer();