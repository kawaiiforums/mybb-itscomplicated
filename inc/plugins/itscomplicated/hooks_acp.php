<?php

namespace itscomplicated\Hooks;

function admin_load()
{
    global $mybb, $db, $lang, $run_module, $action_file, $page, $sub_tabs;

    $module = 'user';
    $actionFile = 'itscomplicated_relationships';
    $pageUrl = 'index.php?module=' . $module . '-' . $actionFile;

    if ($run_module == $module && $action_file == $actionFile) {
        $lang->load('itscomplicated');
        $lang->load('itscomplicated', true);

        $page->add_breadcrumb_item($lang->itscomplicated_admin_relationships, $pageUrl);

        $sub_tabs['types'] = [
            'link'        => $pageUrl . '&action=types',
            'title'       => $lang->itscomplicated_admin_relationships_types,
            'description' => $lang->itscomplicated_admin_relationships_types_description,
        ];

        if ($mybb->input['action'] == 'types' || empty($mybb->input['action'])) {
            if ($mybb->request_method == 'post' && $mybb->get_input('add') && !empty(trim($mybb->get_input('title')))) {
                \itscomplicated\addRelationshipType([
                    'name' => $mybb->get_input('name'),
                    'title' => $mybb->get_input('title'),
                ]);
                \flash_message($lang->itscomplicated_admin_relationships_types_added, 'success');
                \admin_redirect($pageUrl . '&action=types');
            } elseif ($mybb->get_input('delete')) {
                $relationshipType = \itscomplicated\getRelationshipTypeById($mybb->get_input('delete', \MyBB::INPUT_INT));

                if ($relationshipType) {
                    if ($mybb->request_method == 'post') {
                        if ($mybb->get_input('no')) {
                            \admin_redirect($pageUrl . '&action=types');
                        } else {
                            \itscomplicated\deleteRelationshipTypeById($relationshipType['id']);

                            \flash_message($lang->itscomplicated_admin_relationships_types_deleted, 'success');
                            \admin_redirect($pageUrl . '&action=types');
                        }
                    } else {
                        $page->output_confirm_action(
                            $pageUrl . '&action=types&delete=' . (int)$relationshipType['id'],
                            $lang->itscomplicated_admin_relationships_types_delete_confirm_message,
                            $lang->itscomplicated_admin_relationships_types_delete_confirm_title
                        );
                    }
                }
            } elseif ($mybb->get_input('edit')) {
                $relationshipType = \itscomplicated\getRelationshipTypeById($mybb->get_input('edit', \MyBB::INPUT_INT));

                if ($relationshipType) {
                    if ($mybb->request_method == 'post') {
                        \itscomplicated\updateRelationshipTypeById($relationshipType['id'], [
                            'name' => $mybb->get_input('name'),
                            'title' => $mybb->get_input('title'),
                        ]);
                        \flash_message($lang->itscomplicated_admin_relationships_types_updated, 'success');
                        \admin_redirect($pageUrl . '&action=types');
                    } else {
                        $page->output_header($lang->itscomplicated_admin_relationships_types);
                        $page->output_nav_tabs($sub_tabs, 'types');

                        $form = new \Form($pageUrl . '&action=types&edit=' . (int)$relationshipType['id'], 'post');

                        $form_container = new \FormContainer($lang->itscomplicated_admin_relationships_types_update);
                        $form_container->output_row(
                            $lang->itscomplicated_admin_relationships_name,
                            $lang->itscomplicated_admin_relationships_name_description,
                            $form->generate_text_box('name', $relationshipType['name'])
                        );
                        $form_container->output_row(
                            $lang->itscomplicated_admin_relationships_title,
                            '',
                            $form->generate_text_box('title', $relationshipType['title'])
                        );
                        $form_container->end();

                        $buttons = null;
                        $buttons[] = $form->generate_submit_button($lang->itscomplicated_admin_relationships_submit);
                        $form->output_submit_wrapper($buttons);
                        $form->end();
                    }
                }
            } else {
                $page->output_header($lang->itscomplicated_admin_relationships_types);
                $page->output_nav_tabs($sub_tabs, 'types');


                $itemsNum = $db->fetch_field(
                    $db->query("
                        SELECT
                            COUNT(id) AS n
                        FROM
                            " . $db->table_prefix . "itscomplicated_relationship_types    
                    "),
                    'n'
                );

                $listManager = new \itscomplicated\ListManager([
                    'mybb' => $mybb,
                    'baseurl' => $pageUrl . '&amp;action=types',
                    'order_columns' => ['id', 'title'],
                    'order_dir' => 'asc',
                    'items_num' => $itemsNum,
                    'per_page' => 20,
                ]);

                $query = $db->query("
                    SELECT
                        *
                    FROM
                        " . $db->table_prefix . "itscomplicated_relationship_types
                    " . $listManager->sql() . "
                ");

                $table = new \Table;
                $table->construct_header($listManager->link('name', $lang->itscomplicated_admin_relationships_name), ['width' => '30%', 'class' => 'align_center']);
                $table->construct_header($listManager->link('title', $lang->itscomplicated_admin_relationships_title), ['width' => '50%', 'class' => 'align_center']);
                $table->construct_header($lang->options, ['width' => '20%', 'class' => 'align_center']);

                if ($itemsNum > 0) {
                    while ($row = $db->fetch_array($query)) {
                        $name = \htmlspecialchars_uni(
                            $row['name']
                        );

                        $title = \htmlspecialchars_uni(
                            $lang->parse($row['title'])
                        );

                        $popup = new \PopupMenu('controls_' . $row['id'], $lang->options);
                        $popup->add_item($lang->edit, $pageUrl . '&amp;edit=' . $row['id']);
                        $popup->add_item($lang->delete, $pageUrl . '&amp;delete=' . $row['id']);
                        $controls = $popup->fetch();

                        $table->construct_cell($name, ['class' => 'align_center']);
                        $table->construct_cell($title, ['class' => 'align_center']);
                        $table->construct_cell($controls, ['class' => 'align_center']);
                        $table->construct_row();
                    }
                } else {
                    $table->construct_cell($lang->itscomplicated_admin_relationships_types_empty, ['colspan' => '3', 'class' => 'align_center']);
                    $table->construct_row();
                }

                $table->output($lang->itscomplicated_admin_relationships_types);

                echo $listManager->pagination();

                echo '<br />';

                // add form
                $form = new \Form($pageUrl . '&amp;action=types&amp;add=1', 'post');

                $form_container = new \FormContainer($lang->itscomplicated_admin_relationships_types_add);
                $form_container->output_row(
                    $lang->itscomplicated_admin_relationships_name,
                    $lang->itscomplicated_admin_relationships_name_description,
                    $form->generate_text_box('name')
                );
                $form_container->output_row(
                    $lang->itscomplicated_admin_relationships_title,
                    '',
                    $form->generate_text_box('title')
                );
                $form_container->end();

                $buttons = null;
                $buttons[] = $form->generate_submit_button($lang->itscomplicated_admin_relationships_submit);
                $form->output_submit_wrapper($buttons);
                $form->end();
            }
        }

        $page->output_footer();
    }
}

function admin_user_action_handler(array &$actions): void
{
    $actions['itscomplicated_relationships'] = [
        'active' => 'itscomplicated_relationships',
        'file' => 'itscomplicated_relationships',
    ];
}

function admin_user_menu(array &$sub_menu): void
{
    global $lang;

    $lang->load('itscomplicated');

    $sub_menu[] = [
        'id' => 'itscomplicated_relationships',
        'title' => $lang->itscomplicated_admin_relationships,
        'link' => 'index.php?module=user-itscomplicated_relationships',
    ];
}

function admin_user_users_merge_commit(): void
{
    global $source_user;

    \itscomplicated\deleteUserRelationships($source_user);
}

function admin_user_users_edit_moderator_options(): void
{
    global $mybb, $lang, $form;

    $lang->load('itscomplicated');

    $form_container = new \FormContainer($lang->itscomplicated_admin_relationships);

    $options = '<div class="user_settings_bit">';
    $options .= $form->generate_check_box('itscomplicated_allow_relationships', 1, $lang->itscomplicated_relationships_user_allow, array("checked" => $mybb->input['itscomplicated_allow_relationships']));
    $options .= '</div>';

    $form_container->output_row(
        $lang->itscomplicated_admin_relationships,
        '',
        $options
    );

    $form_container->end();
}

function admin_user_users_edit_commit_start(): void
{
    global $mybb, $extra_user_updates;

    $extra_user_updates['itscomplicated_allow_relationships'] = $mybb->get_input('itscomplicated_allow_relationships', \MyBB::INPUT_INT);
}
