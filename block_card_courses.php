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
        global $OUTPUT, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Get configuration settings with defaults.
        $rootcategory = isset($this->config->rootcategory) ? (int)$this->config->rootcategory : 0;
        $showcategories = isset($this->config->showcategories) ? (bool)$this->config->showcategories : true;
        $maxcategories = isset($this->config->maxcategories) ? (int)$this->config->maxcategories : 6;

        // Prepare data for template.
        $data = ['categories' => []];

        if ($showcategories) {
            $categories = $DB->get_records('course_categories', 
                ['parent' => $rootcategory, 'visible' => 1],
                'sortorder ASC',
                '*',
                0,
                $maxcategories
            );

            foreach ($categories as $category) {
                try {
                    $categorycontext = context_coursecat::instance($category->id);
                    
                    $data['categories'][] = [
                        'id' => $category->id,
                        'name' => format_string($category->name, true, ['context' => $categorycontext]),
                        'description' => format_text($category->description, $category->descriptionformat, ['context' => $categorycontext]),
                        'url' => new moodle_url('/blocks/card_courses/category.php', ['id' => $category->id]),
                        'course_count' => $DB->count_records('course', ['category' => $category->id, 'visible' => 1]),
                        'image_url' => $this->get_category_image($category)
                    ];
                } catch (Exception $e) {
                    debugging('Error loading category '.$category->id.': '.$e->getMessage(), DEBUG_NORMAL);
                    continue;
                }
            }
        }

        if (!empty($data['categories'])) {
            $this->content->text = $OUTPUT->render_from_template('block_card_courses/card_container', $data);
        } else {
            $this->content->text = $OUTPUT->notification(get_string('nocontent', 'block_card_courses'), 'info');
        }

        return $this->content;
    }

    private function get_category_image($category) {
        global $OUTPUT;

        try {
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
        } catch (Exception $e) {
            debugging('Error getting category image: '.$e->getMessage(), DEBUG_NORMAL);
        }
        
        return $OUTPUT->image_url('defaultcategory', 'block_card_courses')->out();
    }
}