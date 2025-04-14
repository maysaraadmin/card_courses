<?php

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
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Get configuration settings.
        $rootcategory = isset($this->config->rootcategory) ? $this->config->rootcategory : 0;
        $showcategories = isset($this->config->showcategories) ? $this->config->showcategories : true;
        $showcourses = isset($this->config->showcourses) ? $this->config->showcourses : true;
        $maxcategories = isset($this->config->maxcategories) ? $this->config->maxcategories : 6;
        $maxcourses = isset($this->config->maxcourses) ? $this->config->maxcourses : 12;

        // Prepare data for template.
        $data = array(
            'categories' => array(),
            'courses' => array()
        );

        if ($showcategories) {
            $categories = $this->get_categories($rootcategory, $maxcategories);
            foreach ($categories as $category) {
                $categorycontext = context_coursecat::instance($category->id);
                $categorydata = array(
                    'id' => $category->id,
                    'name' => format_string($category->name, true, ['context' => $categorycontext]),
                    'description' => format_text($category->description, $category->descriptionformat, ['context' => $categorycontext]),
                    'url' => new moodle_url('/course/index.php', ['categoryid' => $category->id]),
                    'course_count' => $category->coursecount,
                    'image_url' => $this->get_category_image($category)
                );
                $data['categories'][] = $categorydata;
            }
        }

        if ($showcourses) {
            $courses = $this->get_courses($rootcategory, $maxcourses);
            foreach ($courses as $course) {
                $coursecontext = context_course::instance($course->id);
                $coursedata = array(
                    'id' => $course->id,
                    'fullname' => format_string($course->fullname, true, ['context' => $coursecontext]),
                    'shortname' => format_string($course->shortname, true, ['context' => $coursecontext]),
                    'summary' => format_text($course->summary, $course->summaryformat, ['context' => $coursecontext]),
                    'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
                    'category_id' => $course->category,
                    'image_url' => $this->get_course_image($course)
                );
                $data['courses'][] = $coursedata;
            }
        }

        // Render the template.
        $this->content->text = $OUTPUT->render_from_template('block_card_courses/main_content', $data);

        // Add CSS.
        $this->content->text .= '<style>' . file_get_contents(__DIR__ . '/styles.css') . '</style>';

        return $this->content;
    }

    private function get_categories($parentid, $limit) {
        global $DB;

        $params = ['parent' => $parentid, 'visible' => 1];
        $sql = "SELECT cc.id, cc.name, cc.description, cc.descriptionformat, 
                       cc.parent, cc.coursecount, cc.visible
                FROM {course_categories} cc
                WHERE cc.parent = :parent
                AND cc.visible = :visible
                ORDER BY cc.sortorder ASC";

        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    private function get_courses($categoryid, $limit) {
        global $DB;

        $params = ['visible' => 1, 'siteid' => SITEID];
        $categoryselect = '';

        if ($categoryid > 0) {
            $categoryselect = 'AND c.category = :categoryid';
            $params['categoryid'] = $categoryid;
        }

        $sql = "SELECT c.id, c.shortname, c.fullname, c.summary, c.summaryformat, 
                       c.visible, c.category
                FROM {course} c
                WHERE c.id <> :siteid
                $categoryselect
                AND c.visible = :visible
                ORDER BY c.sortorder ASC";

        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    private function get_category_image($category) {
        global $OUTPUT;
        
        $context = context_coursecat::instance($category->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'coursecat', 'image', 0, 'itemid, filepath, filename', false);
        
        if ($file = array_shift($files)) {
            $imageurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
            return $imageurl->out();
        }
        
        return $OUTPUT->image_url('defaultcategory', 'block_card_courses')->out();
    }

    private function get_course_image($course) {
        global $OUTPUT;
        
        $context = context_course::instance($course->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);
        
        if ($file = array_shift($files)) {
            $imageurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
            return $imageurl->out();
        }
        
        return $OUTPUT->image_url('defaultcourse', 'block_card_courses')->out();
    }
}