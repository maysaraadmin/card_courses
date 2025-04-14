<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/course/lib.php');

class block_card_courses extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_card_courses');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => true,
            'my' => true
        );
    }

    public function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->config->title;
        } else {
            $this->title = get_string('defaulttitle', 'block_card_courses');
        }
    }

    public function get_content() {
        global $OUTPUT, $DB, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $categoryid = optional_param('categoryid', 0, PARAM_INT);
        $showcourses = optional_param('showcourses', false, PARAM_BOOL);

        $data = array(
            'categories' => array(),
            'courses' => array(),
            'parentcategory' => null,
            'currentcategory' => $categoryid,
            'showcourses' => $showcourses,
            'config' => array('wwwroot' => $CFG->wwwroot),
            'blockid' => $this->instance->id
        );

        // Get current category info
        if ($categoryid > 0) {
            $currentcategory = $DB->get_record('course_categories', array('id' => $categoryid));
            if ($currentcategory) {
                $context = context_coursecat::instance($currentcategory->id);
                $data['currentcategoryname'] = format_string($currentcategory->name, true, array('context' => $context));

                // Get parent category if exists
                if ($currentcategory->parent > 0) {
                    $parentcategory = $DB->get_record('course_categories', array('id' => $currentcategory->parent));
                    if ($parentcategory) {
                        $parentcontext = context_coursecat::instance($parentcategory->id);
                        $data['parentcategory'] = array(
                            'id' => $parentcategory->id,
                            'name' => format_string($parentcategory->name, true, array('context' => $parentcontext)),
                            'url' => new moodle_url('/blocks/card_courses/view.php', array(
                                'blockid' => $this->instance->id,
                                'categoryid' => $parentcategory->id
                            ))
                        );
                    }
                }
            }
        } else {
            $data['currentcategoryname'] = get_string('allcategories', 'block_card_courses');
        }

        // Get subcategories
        $subcategories = core_course_category::get($categoryid)->get_children();
        foreach ($subcategories as $category) {
            $context = context_coursecat::instance($category->id);
            $data['categories'][] = array(
                'id' => $category->id,
                'name' => format_string($category->name, true, array('context' => $context)),
                'description' => format_text($category->description, $category->descriptionformat, array('context' => $context)),
                'url' => new moodle_url('/blocks/card_courses/view.php', array(
                    'blockid' => $this->instance->id,
                    'categoryid' => $category->id
                )),
                'course_count' => $category->get_courses_count(array('recursive' => false)),
                'image_url' => $this->get_category_image($category)
            );
        }

        // Get courses if requested or no subcategories
        if ($showcourses || empty($subcategories)) {
            $courses = get_courses($categoryid, 'c.sortorder ASC', 'c.*');
            foreach ($courses as $course) {
                if ($course->id == SITEID) {
                    continue;
                }
                $coursecontext = context_course::instance($course->id);
                $data['courses'][] = array(
                    'id' => $course->id,
                    'fullname' => format_string($course->fullname, true, array('context' => $coursecontext)),
                    'summary' => format_text($course->summary, $course->summaryformat, array('context' => $coursecontext)),
                    'url' => new moodle_url('/course/view.php', array('id' => $course->id)),
                    'image_url' => $this->get_course_image($course)
                );
            }
        }

        // Add view courses link if there are both subcategories and courses
        if (!$showcourses && !empty($subcategories)) {
            $coursecount = core_course_category::get($categoryid)->get_courses_count(array('recursive' => false));
            if ($coursecount > 0) {
                $data['viewcoursesurl'] = new moodle_url('/blocks/card_courses/view.php', array(
                    'blockid' => $this->instance->id,
                    'categoryid' => $categoryid,
                    'showcourses' => true
                ));
            }
        }

        $this->content->text = $OUTPUT->render_from_template('block_card_courses/main_content', $data);
        return $this->content;
    }

    private function get_category_image($category) {
        global $OUTPUT;
        
        $context = context_coursecat::instance($category->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'coursecat', 'image', 0, 'itemid, filepath, filename', false);
        
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

    private function get_course_image($course) {
        global $OUTPUT;
        
        $context = context_course::instance($course->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);
        
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
}