<?php

namespace itscomplicated\Hooks;

function global_start(): void
{
    global $mybb;

    switch (\THIS_SCRIPT) {
        case 'usercp.php':
            if ($mybb->get_input('action') == 'relationships') {
                \itscomplicated\loadTemplates([
                    'relationships_usercp',
                    'relationships_usercp_create',
                    'relationships_usercp_none',
                    'relationships_usercp_relationship',
                    'relationships_usercp_relationship_type_option',
                    'relationships_usercp_request',
                    'relationships_usercp_request_accept',
                    'relationships_usercp_request_cancel',
                    'relationships_usercp_request_ignore',
                    'relationships_usercp_requests',
                    'relationships_usercp_requests_none',
                ], 'itscomplicated_');
            }

            \itscomplicated\loadTemplates([
                'relationships_usercp_menu',
            ], 'itscomplicated_');

            break;
        case 'member.php':
            if ($mybb->get_input('action') == 'profile') {
                \itscomplicated\loadTemplates([
                    'relationships_profile',
                    'relationships_profile_relationship',
                    'relationships_profile_relationship_avatar',
                ], 'itscomplicated_');
            }
    }
}

function member_profile_end(): void
{
    global $mybb, $lang, $memprofile, $theme, $itscomplicatedRelationships;

    $lang->load('itscomplicated');

    $userRelationships = \itscomplicated\getUserRelationshipsWithTypes($memprofile['uid'], true);

    if ($userRelationships) {
        $relationshipsHtml = null;

        foreach ($userRelationships as $userRelationship) {
            $relationshipUsers = \itscomplicated\getRelationshipUsers($userRelationship['id']);
            unset($relationshipUsers[$memprofile['uid']]);

            if (count($relationshipUsers) == 1) {
                $relationshipUser = end($relationshipUsers);

                if ($relationshipUser['avatar']) {
                    $useravatar = \format_avatar($relationshipUser['avatar'], $relationshipUser['avatardimensions'], '20x20');
                } else {
                    $useravatar = \format_avatar($mybb->settings['useravatar'], $mybb->settings['useravatardims'], '20x20');
                }

                eval('$avatar = "' . \itscomplicated\tpl('relationships_profile_relationship_avatar') . '";');

                $username = \itscomplicated\getFormattedProfileLink($relationshipUser);

                $relationshipTypeTitle = \htmlspecialchars_uni(
                    $lang->parse($userRelationship['title'])
                );

                $relationshipDateStart = \my_date($mybb->settings['dateformat'], $userRelationship['date_start']);

                $customNote = &$lang->{'itscomplicated_relationships_type_' . $userRelationship['name'] . '_to'};

                if (isset($customNote)) {
                    $noteString = $customNote;
                } else {
                    $noteString = $lang->itscomplicated_relationships_in_relationship_with;
                }

                $note = $lang->sprintf(
                    $noteString,
                    $username,
                    $relationshipTypeTitle,
                    $relationshipDateStart
                );

                eval('$relationshipsHtml .= "' . \itscomplicated\tpl('relationships_profile_relationship') . '";');
            }
        }

        eval('$itscomplicatedRelationships = "' . \itscomplicated\tpl('relationships_profile') . '";');
    } else {
        $itscomplicatedRelationships = null;
    }
}

function usercp_start(): void
{
    global $mybb, $lang,
    $headerinclude, $header, $theme, $usercpnav, $footer;

    if ($mybb->get_input('action') == 'relationships') {
        \add_breadcrumb($lang->itscomplicated_relationships);

        $errors = [];
        $message = null;

        if ($mybb->get_input('create_request')) {
            if (\verify_post_check($mybb->get_input('my_post_key'))) {
                $relationshipType = \itscomplicated\getRelationshipTypeById($mybb->get_input('relationship_type_id', \MyBB::INPUT_INT));

                if ($relationshipType) {
                    $receivingUser = \get_user_by_username($mybb->get_input('receiving_user_username'), [
                        'fields' => '*',
                    ]);

                    if ($receivingUser) {
                        $firstFalseCondition = \itscomplicated\getFirstFalseCondition(
                            \itscomplicated\getRelationshipRequestConditionResultsForUsers($mybb->user, $receivingUser, $relationshipType)
                        );

                        if ($firstFalseCondition) {
                            $errors[] = $lang->{'itscomplicated_relationships_error_' . $firstFalseCondition};
                        } else {
                            \itscomplicated\createRelationshipRequest($mybb->user, $receivingUser, $relationshipType);

                            \redirect('usercp.php?action=relationships', $lang->itscomplicated_relationships_request_created);
                        }
                    } else {
                        $errors[] = $lang->itscomplicated_relationships_error_user_not_found;
                    }
                }
            }
        } elseif ($mybb->get_input('remove_request')) {
            if (\verify_post_check($mybb->get_input('my_post_key'))) {
                $relationship = \itscomplicated\getRelationshipById($mybb->get_input('remove_request', \MyBB::INPUT_INT));

                if ($relationship && $relationship['active'] == 0 && $relationship['date_start'] == 0) {
                    $relationshipUsers = \itscomplicated\getRelationshipUsers($relationship['id']);

                    if (in_array($mybb->user['uid'], array_column($relationshipUsers, 'user_id'))) {
                        \itscomplicated\removeRelationshipRequest($relationship['id']);
                        \redirect('usercp.php?action=relationships', $lang->itscomplicated_relationships_request_removed);
                    }
                }
            }
        } elseif ($mybb->get_input('accept_request')) {
            if (\verify_post_check($mybb->get_input('my_post_key'))) {
                $relationship = \itscomplicated\getRelationshipById($mybb->get_input('accept_request', \MyBB::INPUT_INT));
                $relationshipType = \itscomplicated\getRelationshipTypeById($relationship['type_id']);

                if ($relationship && $relationship['active'] == 0 && $relationship['date_start'] == 0) {
                    $relationshipUsers = \itscomplicated\getRelationshipUsers($relationship['id']);

                    $relationshipUser = &$relationshipUsers[$mybb->user['uid']];

                    if (isset($relationshipUser)) {
                        if ($relationshipUser['accepted'] == 0) {
                            $receivingUsers = $relationshipUsers;
                            unset($receivingUsers[$mybb->user['uid']]);

                            if (count($receivingUsers) == 1) {
                                $firstFalseCondition = \itscomplicated\getFirstFalseCondition(
                                    \itscomplicated\getRelationshipRequestConditionResultsForUsers(end($receivingUsers), $mybb->user, $relationshipType)
                                );

                                if ($firstFalseCondition) {
                                    $errors[] = $lang->{'itscomplicated_relationships_error_' . $firstFalseCondition};
                                } else {
                                    \itscomplicated\setRelationshipRequestAcceptedForUser($relationship, $mybb->user['uid']);
                                    \redirect('usercp.php?action=relationships', $lang->itscomplicated_relationships_request_accepted);
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($mybb->get_input('end_relationship')) {
            if (\verify_post_check($mybb->get_input('my_post_key'))) {
                $relationship = \itscomplicated\getRelationshipById($mybb->get_input('end_relationship', \MyBB::INPUT_INT));

                if ($relationship && $relationship['active'] == 1 && $relationship['date_start'] != 0) {
                    $relationshipUsers = \itscomplicated\getRelationshipUsers($relationship['id']);

                    if (in_array($mybb->user['uid'], array_column($relationshipUsers, 'user_id'))) {
                        \itscomplicated\endRelationshipForUser($relationship, $mybb->user);
                        \redirect('usercp.php?action=relationships', $lang->itscomplicated_relationships_relationship_ended);
                    }
                }
            }
        }

        if ($errors) {
            $errorMessage = \inline_error($errors);
        } else {
            $errorMessage = '';
        }

        $userRelationships = \itscomplicated\getUserRelationshipsWithTypesByState($mybb->user['uid']);

        $currentRelationships = $lang->sprintf($lang->itscomplicated_relationships_current, count($userRelationships['active']));

        if ($userRelationships['active']) {
            $relationshipsList = null;

            foreach ($userRelationships['active'] as $userRelationship) {
                $relationshipUsers = \itscomplicated\getRelationshipUsers($userRelationship['id']);

                unset($relationshipUsers[$mybb->user['uid']]);

                if (count($relationshipUsers) == 1) {
                    $relationshipUser = end($relationshipUsers);

                    $username = \itscomplicated\getFormattedProfileLink($relationshipUser);

                    $relationshipTypeTitle = \htmlspecialchars_uni(
                        $lang->parse($userRelationship['title'])
                    );

                    $relationshipDateStart = \my_date($mybb->settings['dateformat'], $userRelationship['date_start']);

                    eval('$relationshipsList .= "' . \itscomplicated\tpl('relationships_usercp_relationship') . '";');
                }
            }
        } else {
            eval('$relationshipsList = "' . \itscomplicated\tpl('relationships_usercp_none') . '";');
        }


        if (\itscomplicated\userInRelationshipGroup($mybb->user)) {
            $relationshipTypeSelectOptions = null;

            $relationshipTypes = \itscomplicated\getRelationshipTypes();

            foreach ($relationshipTypes as $relationshipType) {
                if (\itscomplicated\userInRelationshipTypeGroup($mybb->user, $relationshipType)) {
                    $relationshipTypeTitle = \htmlspecialchars_uni(
                        $lang->parse($relationshipType['title'])
                    );

                    eval('$relationshipTypeSelectOptions .= "' . \itscomplicated\tpl('relationships_usercp_relationship_type_option') . '";');
                }
            }

            if ($relationshipTypeSelectOptions !== null) {
                eval('$createRelationship = "' . \itscomplicated\tpl('relationships_usercp_create') . '";');
            } else {
                $message = $lang->itscomplicated_relationships_error_no_types;
            }

            if ($userRelationships['requests_received']) {
                $receivedRequests = null;

                foreach ($userRelationships['requests_received'] as $userRelationship) {
                    $relationshipUsers = \itscomplicated\getRelationshipUsers($userRelationship['id']);

                    unset($relationshipUsers[$mybb->user['uid']]);

                    if (count($relationshipUsers) == 1) {
                        $relationshipUser = end($relationshipUsers);

                        $username = \itscomplicated\getFormattedProfileLink($relationshipUser);

                        $relationshipTypeTitle = \htmlspecialchars_uni(
                            $lang->parse($userRelationship['title'])
                        );

                        $options = null;
                        eval('$options .= "' . \itscomplicated\tpl('relationships_usercp_request_accept') . '";');
                        eval('$options .= "' . \itscomplicated\tpl('relationships_usercp_request_ignore') . '";');

                        eval('$receivedRequests .= "' . \itscomplicated\tpl('relationships_usercp_request') . '";');
                    }
                }
            } else {
                eval('$receivedRequests = "' . \itscomplicated\tpl('relationships_usercp_requests_none') . '";');
            }


            if ($userRelationships['requests_sent']) {
                $sentRequests = null;

                foreach ($userRelationships['requests_sent'] as $userRelationship) {
                    $relationshipUsers = \itscomplicated\getRelationshipUsers($userRelationship['id']);

                    unset($relationshipUsers[$mybb->user['uid']]);

                    if (count($relationshipUsers) == 1) {
                        $relationshipUser = end($relationshipUsers);

                        $username = \itscomplicated\getFormattedProfileLink($relationshipUser);

                        $relationshipTypeTitle = \htmlspecialchars_uni(
                            $lang->parse($userRelationship['title'])
                        );

                        $options = null;
                        eval('$options .= "' . \itscomplicated\tpl('relationships_usercp_request_cancel') . '";');

                        eval('$sentRequests .= "' . \itscomplicated\tpl('relationships_usercp_request') . '";');
                    }
                }
            } else {
                eval('$sentRequests = "' . \itscomplicated\tpl('relationships_usercp_requests_none') . '";');
            }

            eval('$relationshipRequests = "' . \itscomplicated\tpl('relationships_usercp_requests') . '";');
        } else {
            $createRelationship = null;
            $relationshipRequests = null;

            $message = $lang->itscomplicated_relationships_error_initiating_user_in_group;
        }

        eval('$page = "' . \itscomplicated\tpl('relationships_usercp') . '";');

        \output_page($page);
    }
}

function usercp_menu31(): void
{
    global $lang, $usercpmenu;

    $lang->load('itscomplicated');

    eval('$usercpmenu .= "' . \itscomplicated\tpl('relationships_usercp_menu') . '";');
}

function datahandler_user_delete_end(\UserDataHandler $userDataHandler): void
{
    foreach ($userDataHandler->delete_uids as $userId) {
        \itscomplicated\deleteUserRelationships($userId);
    }
}
