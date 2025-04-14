<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Root category setting.
    $options = array();
    $options[0] = get_string('top');
    $categories = core_course_category::get(0)->get_children();
    foreach ($categories as $category) {
        $options[$category->id] = $category->get_formatted_name();
    }
    
    $settings->add(new admin_setting_configselect('block_card_courses/rootcategory',
        get_string('rootcategory', 'block_card_courses'),
        get_string('rootcategorydesc', 'block_card_courses'), 0, $options));

    // Show categories setting.
    $settings->add(new admin_setting_configcheckbox('block_card_courses/showcategories',
        get_string('showcategories', 'block_card_courses'),
        get_string('showcategoriesdesc', 'block_card_courses'), 1));

    // Show courses setting.
    $settings->add(new admin_setting_configcheckbox('block_card_courses/showcourses',
        get_string('showcourses', 'block_card_courses'),
        get_string('showcoursesdesc', 'block_card_courses'), 1));

    // Max categories setting.
    $settings->add(new admin_setting_configtext('block_card_courses/maxcategories',
        get_string('maxcategories', 'block_card_courses'),
        get_string('maxcategoriesdesc', 'block_card_courses'), 6, PARAM_INT));

    // Max courses setting.
    $settings->add(new admin_setting_configtext('block_card_courses/maxcourses',
        get_string('maxcourses', 'block_card_courses'),
        get_string('maxcoursesdesc', 'block_card_courses'), 12, PARAM_INT));
}