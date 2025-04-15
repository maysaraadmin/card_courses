<?php

defined('MOODLE_INTERNAL') || die();

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
            $this->title = format_string($this->config->title);
        } else {
            $this->title = get_string('defaulttitle', 'block_card_courses');
        }
    }

    public function get_content() {
        global $OUTPUT, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        require_once($CFG->libdir . '/filelib.php');

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Get configuration settings with defaults.
        $rootcategory = isset($this->config->rootcategory) ? (int)$this->config->rootcategory : 0;
        $showcategories = isset($this->config->showcategories) ? (bool)$this->config->showcategories : true;
        $showcourses = isset($this->config->showcourses) ? (bool)$this->config->showcourses : true;
        $maxcategories = isset($this->config->maxcategories) ? (int)$this->config->maxcategories : 6;
        $maxcourses = isset($this->config->maxcourses) ? (int)$this->config->maxcourses : 12;

        // Prepare data for template.
        $data = [
            'categories' => [],
            'courses' => []
        ];

        if ($showcategories) {
            $categories = $this->get_categories($rootcategory, $maxcategories);
            foreach ($categories as $category) {
                $categorycontext = context_coursecat::instance($category->id);
                $data['categories'][] = [
                    'id' => $category->id,
                    'name' => format_string($category->name, true, ['context' => $categorycontext]),
                    'description' => format_text($category->description, $category->descriptionformat, ['context' => $categorycontext]),
                    'url' => new moodle_url('/course/index.php', ['categoryid' => $category->id]),
                    'course_count' => $category->coursecount,
                    'image_url' => $this->get_category_image($category)
                ];
            }
        }

        if ($showcourses) {
            $courses = $this->get_courses($rootcategory, $maxcourses);
            foreach ($courses as $course) {
                $coursecontext = context_course::instance($course->id);
                $data['courses'][] = [
                    'id' => $course->id,
                    'fullname' => format_string($course->fullname, true, ['context' => $coursecontext]),
                    'summary' => format_text($course->summary, $course->summaryformat, ['context' => $coursecontext]),
                    'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
                    'image_url' => $this->get_course_image($course)
                ];
            }
        }

        // Render the template.
        $this->content->text = $OUTPUT->render_from_template('block_card_courses/card_container', $data);

        return $this->content;
    }

    private function get_categories($parentid, $limit) {
        global $DB;

        $cache = cache::make('block_card_courses', 'categories');
        $cachekey = "cat_{$parentid}_{$limit}";

        if ($categories = $cache->get($cachekey)) {
            return $categories;
        }

        $params = ['parent' => $parentid, 'visible' => 1];
        $sql = "SELECT cc.id, cc.name, cc.description, cc.descriptionformat, 
                       cc.parent, cc.coursecount, cc.visible
                FROM {course_categories} cc
                WHERE cc.parent = :parent
                AND cc.visible = :visible
                ORDER BY cc.sortorder ASC";

        $categories = $DB->get_records_sql($sql, $params, 0, $limit);
        $cache->set($cachekey, $categories);

        return $categories;
    }

    private function get_courses($categoryid, $limit) {
        global $DB;

        $cache = cache::make('block_card_courses', 'courses');
        $cachekey = "crs_{$categoryid}_{$limit}";

        if ($courses = $cache->get($cachekey)) {
            return $courses;
        }

        $params = ['visible' => 1, 'siteid' => SITEID];
        $categoryselect = '';

        if ($categoryid > 0) {
            $categoryselect = 'AND c.category = :categoryid';
            $params['categoryid'] = $categoryid;
        }

        $sql = "SELECT c.id, c.fullname, c.summary, c.summaryformat, 
                       c.visible, c.category
                FROM {course} c
                WHERE c.id <> :siteid
                $categoryselect
                AND c.visible = :visible
                ORDER BY c.sortorder ASC";

        $courses = $DB->get_records_sql($sql, $params, 0, $limit);
        $cache->set($cachekey, $courses);

        return $courses;
    }

    private function get_category_image($category) {
        global $OUTPUT;

        $context = context_coursecat::instance($category->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'coursecat', 'image', 0, 'sortorder DESC, id DESC', false);
        
        if ($file = reset($files)) {
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
        
        return $OUTPUT->image_url('defaultcategory', 'block_card_courses')->out();
    }

    private function get_course_image($course) {
        global $OUTPUT;

        $context = context_course::instance($course->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'sortorder DESC, id DESC', false);
        
        if ($file = reset($files)) {
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
        
        return $OUTPUT->image_url('defaultcourse', 'block_card_courses')->out();
    }
}