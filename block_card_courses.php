<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

class block_card_courses extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_card_courses');
    }

    /**
     * Whether the block allows multiple instances.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Whether the block has configuration.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Where the block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => true,
            'my' => true
        );
    }

    /**
     * Specialization for block title.
     */
    public function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->config->title;
        } else {
            $this->title = get_string('defaulttitle', 'block_card_courses');
        }
    }

    /**
     * Generate the block content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $OUTPUT, $DB, $PAGE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Get current category from URL or use root.
        $currentcategory = optional_param('categoryid', 0, PARAM_INT);
        $showcourses = optional_param('showcourses', false, PARAM_BOOL);

        // Prepare data for template.
        $data = array(
            'categories' => array(),
            'courses' => array(),
            'parentcategory' => null,
            'currentcategory' => $currentcategory,
            'showcourses' => $showcourses,
            'config' => array('wwwroot' => $CFG->wwwroot),
            'blockid' => $this->instance->id
        );

        // Get current category name for breadcrumb.
        if ($currentcategory > 0) {
            $currentcat = $DB->get_record('course_categories', array('id' => $currentcategory));
            if ($currentcat) {
                $context = context_coursecat::instance($currentcat->id);
                if (!core_course_category::can_view_category($currentcat)) {
                    return $this->content;
                }
                $data['currentcategoryname'] = format_string($currentcat->name);
            }
        }

        // Get parent category info if not root.
        if ($currentcategory > 0) {
            $parentcategory = $DB->get_record('course_categories', array('id' => $currentcategory));
            if ($parentcategory && $parentcategory->parent > 0) {
                $grandparent = $DB->get_record('course_categories', array('id' => $parentcategory->parent));
                if ($grandparent && core_course_category::can_view_category($grandparent)) {
                    $grandparentcontext = context_coursecat::instance($grandparent->id);
                    $data['parentcategory'] = array(
                        'id' => $grandparent->id,
                        'name' => format_string($grandparent->name, true, array('context' => $grandparentcontext)),
                        'url' => new moodle_url('/blocks/card_courses/view.php', array(
                            'blockid' => $this->instance->id,
                            'categoryid' => $grandparent->id
                        ))
                    );
                }
            }
        }

        // Get subcategories of current category.
        $subcategories = $DB->get_records('course_categories', array(
            'parent' => $currentcategory,
            'visible' => 1
        ), 'sortorder ASC');

        foreach ($subcategories as $category) {
            if (!core_course_category::can_view_category($category)) {
                continue;
            }
            
            $categorycontext = context_coursecat::instance($category->id);
            $data['categories'][] = array(
                'id' => $category->id,
                'name' => format_string($category->name, true, array('context' => $categorycontext)),
                'description' => format_text($category->description, $category->descriptionformat, array('context' => $categorycontext)),
                'url' => new moodle_url('/blocks/card_courses/view.php', array(
                    'blockid' => $this->instance->id,
                    'categoryid' => $category->id
                )),
                'course_count' => $category->coursecount,
                'image_url' => $this->get_category_image($category)
            );
        }

        // Get courses for current category.
        if ($currentcategory > 0 && (empty($subcategories) || $showcourses)) {
            $courses = get_courses($currentcategory, 'c.sortorder ASC', 'c.*', 'c.visible = 1');
            
            foreach ($courses as $course) {
                if ($course->id == SITEID) {
                    continue;
                }
                
                $coursecontext = context_course::instance($course->id);
                if (!core_course_category::can_view_course_info($course)) {
                    continue;
                }
                
                $data['courses'][] = array(
                    'id' => $course->id,
                    'fullname' => format_string($course->fullname, true, array('context' => $coursecontext)),
                    'summary' => format_text($course->summary, $course->summaryformat, array('context' => $coursecontext)),
                    'url' => new moodle_url('/course/view.php', array('id' => $course->id)),
                    'image_url' => $this->get_course_image($course)
                );
            }
        }

        // Add view all courses link if there are both subcategories and courses.
        if (!$showcourses && !empty($subcategories) && $this->category_has_courses($currentcategory)) {
            $data['viewcoursesurl'] = new moodle_url('/blocks/card_courses/view.php', array(
                'blockid' => $this->instance->id,
                'categoryid' => $currentcategory,
                'showcourses' => true
            ));
        }

        $this->content->text = $OUTPUT->render_from_template('block_card_courses/main_content', $data);
        $this->content->text .= '<style>' . file_get_contents(__DIR__ . '/styles.css') . '</style>';

        return $this->content;
    }

    /**
     * Check if a category has visible courses.
     *
     * @param int $categoryid The category ID
     * @return bool
     */
    private function category_has_courses($categoryid) {
        global $DB;
        return $DB->count_records('course', array('category' => $categoryid, 'visible' => 1)) > 0;
    }

    /**
     * Get the image URL for a category.
     *
     * @param stdClass $category The category object
     * @return string
     */
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

    /**
     * Get the image URL for a course.
     *
     * @param stdClass $course The course object
     * @return string
     */
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