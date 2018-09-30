<?php

// core files
require MYBB_ROOT . 'inc/plugins/itscomplicated/common.php';
require MYBB_ROOT . 'inc/plugins/itscomplicated/core.php';
require MYBB_ROOT . 'inc/plugins/itscomplicated/data.php';
require MYBB_ROOT . 'inc/plugins/itscomplicated/list_manager.php';

// hook files
require MYBB_ROOT . 'inc/plugins/itscomplicated/hooks_frontend.php';
require MYBB_ROOT . 'inc/plugins/itscomplicated/hooks_acp.php';

// init
define('itscomplicated\DEVELOPMENT_MODE', 0);

// hooks
\itscomplicated\addHooksNamespace('itscomplicated\Hooks');

function itscomplicated_info()
{
    global $lang;

    $lang->load('itscomplicated');

    return [
        'name'          => 'It\'s Complicated',
        'description'   => $lang->itscomplicated_description,
        'website'       => '',
        'author'        => 'kawaiiforums',
        'authorsite'    => 'https://github.com/kawaiiforums',
        'version'       => '1.0',
        'codename'      => 'itscomplicated',
        'compatibility' => '18*',
    ];
}

function itscomplicated_install()
{
    global $db;

    \itscomplicated\loadPluginLibrary();

    switch ($db->type) {
        case 'pgsql':
            $db->write_query("
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "itscomplicated_relationship_types (
                    id serial,
                    title text NOT NULL,
                    PRIMARY KEY (id)
                )
            ");
            $db->write_query("
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "itscomplicated_relationships (
                    id serial,
                    type_id integer NOT NULL
                        REFERENCES " . TABLE_PREFIX . "itscomplicated_relationship_types(id) ON DELETE CASCADE,
                    active integer NOT NULL,
                    date_start integer NOT NULL,
                    date_end text NOT NULL,
                    PRIMARY KEY (id)
                )
            ");
            $db->write_query("
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "itscomplicated_relationships_users (
                    relationship_id integer
                        REFERENCES " . TABLE_PREFIX . "itscomplicated_relationships(id) ON DELETE CASCADE,
                    user_id integer NOT NULL,
                    initiated integer NOT NULL,
                    accepted integer NOT NULL,
                    PRIMARY KEY (relationship_id, user_id)
                )
            ");

            break;
        case 'sqlite':
            $db->write_query("
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "itscomplicated_relationship_types (
                    id integer,
                    title text NOT NULL,
                    PRIMARY KEY (id)
                )
            ");
            $db->write_query("
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "itscomplicated_relationships (
                    id integer,
                    type_id integer NOT NULL
                        REFERENCES " . TABLE_PREFIX . "itscomplicated_relationship_types(id) ON DELETE CASCADE,
                    active integer NOT NULL,
                    date_start integer NOT NULL,
                    date_end text NOT NULL,
                    PRIMARY KEY (id)
                )
            ");
            $db->write_query("
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "itscomplicated_relationships_users (
                    relationship_id integer
                        REFERENCES " . TABLE_PREFIX . "itscomplicated_relationships(id) ON DELETE CASCADE,
                    user_id integer NOT NULL,
                    initiated integer NOT NULL,
                    accepted integer NOT NULL,
                    PRIMARY KEY (relationship_id, user_id)
                )
            ");

            break;
        default:
            $db->write_query("
                CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "itscomplicated_relationship_types` (
                    `id` int(11) NOT NULL auto_increment,
                    `title` varchar(100) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
            ");
            $db->write_query("
                CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "itscomplicated_relationships` (
                    `id` int(11) NOT NULL auto_increment,
                    `type_id` int(11) NOT NULL,
                    `active` int(1) NOT NULL,
                    `date_start` int(1) NOT NULL,
                    `date_end` text NOT NULL,
                    PRIMARY KEY (`id`),
                    FOREIGN KEY (`type_id`)
                        REFERENCES " . TABLE_PREFIX . "itscomplicated_relationship_types (`id`)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
            ");
            $db->write_query("
                CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "itscomplicated_relationships_users` (
                    `relationship_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `initiated` int(1) NOT NULL,
                    `accepted` int(1) NOT NULL,
                    PRIMARY KEY (`relationship_id`, `user_id`),
                    FOREIGN KEY (`relationship_id`)
                        REFERENCES " . TABLE_PREFIX . "itscomplicated_relationships (`id`)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
            ");
            break;
    }

    $defaultRelatioshipTypeTitles = [
        '<lang:itscomplicated_relationships_type_relationship>',
        '<lang:itscomplicated_relationships_type_civilunion>',
        '<lang:itscomplicated_relationships_type_engaged>',
        '<lang:itscomplicated_relationships_type_married>',
        '<lang:itscomplicated_relationships_type_separated>',
        '<lang:itscomplicated_relationships_type_divorced>',
        '<lang:itscomplicated_relationships_type_onabreak>',
        '<lang:itscomplicated_relationships_type_itscomplicated>',
    ];

    $existingTypeTitles = array_column(\itscomplicated\getRelationshipTypes(), 'title');

    foreach ($defaultRelatioshipTypeTitles as $title) {
        if (!in_array($title, $existingTypeTitles)) {
            \itscomplicated\addRelationshipType([
                'title' => $title,
            ]);
        }
    }
}

function itscomplicated_uninstall()
{
    global $db, $PL;

    \itscomplicated\loadPluginLibrary();

    // database
    if ($db->type == 'sqlite') {
        $db->close_cursors();
    }

    \itscomplicated\dropTables([
        'itscomplicated_relationship_types',
        'itscomplicated_relationships',
        'itscomplicated_relationships_users',
    ], true, true);

    // settings
    $PL->settings_delete('itscomplicated', true);
}

function itscomplicated_is_installed()
{
    global $db;

    // manual check to avoid caching issues
    $query = $db->simple_select('settinggroups', 'gid', "name='itscomplicated'");

    return (bool)$db->num_rows($query);
}

function itscomplicated_activate()
{
    global $PL;

    \itscomplicated\loadPluginLibrary();

    // settings
    $PL->settings(
        'itscomplicated',
        'It\'s Complicated',
        'Settings for the It\'s Complicated extension.',
        [
            'relationship_groups' => [
                'title'       => 'User Groups',
                'description' => 'Select which user groups are allowed to be part of a relationship.',
                'optionscode' => 'groupselect',
                'value'       => '2',
            ],
            'relationship_limit' => [
                'title'       => 'Relationship Limit',
                'description' => 'Choose how many relationships a user can be part of at the same time. Set to 0 to allow unlimited number of relationships.',
                'optionscode' => 'numeric',
                'value'       => '1',
            ],
            'notification_relationship_ended' => [
                'title'       => 'Notifications: Relationship Ended',
                'description' => 'Choose whether to send notifications when relationship is ended.',
                'optionscode' => 'yesno',
                'value'       => '1',
            ],
        ]
    );

    // templates
    $PL->templates(
        'itscomplicated',
        'itscomplicated',
        \itscomplicated\getFilesContentInDirectory(MYBB_ROOT . 'inc/plugins/itscomplicated/templates', '.tpl')
    );

    \itscomplicated\replaceInTemplate('member_profile', '{$profilefields}', '{$profilefields}
{$itscomplicatedRelationships}');
}

function itscomplicated_deactivate()
{
    global $PL;

    \itscomplicated\loadPluginLibrary();

    // templates
    $PL->templates_delete('itscomplicated', true);

    \itscomplicated\replaceInTemplate('member_profile', '
{$itscomplicatedRelationships}', '');
}
