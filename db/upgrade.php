<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the Card Courses block from an older version to the current one.
 *
 * @param int $oldversion The version number of the old version of the block.
 * @return bool
 */
function xmldb_block_card_courses_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024020101) {
        // Define table block_card_courses to be created.
        $table = new xmldb_table('block_card_courses');

        // Adding fields to table block_card_courses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('blockinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rootcategory', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('showcategories', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('showcourses', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('maxcategories', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '6');
        $table->add_field('maxcourses', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '12');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_card_courses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('blockinstanceid_fk', XMLDB_KEY_FOREIGN, ['blockinstanceid'], 'block_instances', ['id']);

        // Adding indexes to table block_card_courses.
        $table->add_index('blockinstanceid_idx', XMLDB_INDEX_UNIQUE, ['blockinstanceid']);

        // Conditionally launch create table for block_card_courses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
            
            // Create default records for existing instances.
            $DB->execute("INSERT INTO {block_card_courses} (blockinstanceid, timecreated, timemodified)
                          SELECT id, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM {block_instances} WHERE blockname = 'card_courses'");
        }

        upgrade_block_savepoint(true, 2024020101, 'card_courses');
    }

    if ($oldversion < 2024020102) {
        // Add new fields for card appearance.
        $table = new xmldb_table('block_card_courses');
        
        $fields = [
            new xmldb_field('cardstyle', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'default'),
            new xmldb_field('showimages', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1'),
            new xmldb_field('showdescriptions', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1')
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_block_savepoint(true, 2024020102, 'card_courses');
    }

    if ($oldversion < 2024020103) {
        // Add fields for custom colors.
        $table = new xmldb_table('block_card_courses');
        
        $fields = [
            new xmldb_field('cardbgcolor', XMLDB_TYPE_CHAR, '7', null, null, null, null),
            new xmldb_field('cardtextcolor', XMLDB_TYPE_CHAR, '7', null, null, null, null),
            new xmldb_field('cardbordercolor', XMLDB_TYPE_CHAR, '7', null, null, null, null)
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_block_savepoint(true, 2024020103, 'card_courses');
    }

    return true;
}