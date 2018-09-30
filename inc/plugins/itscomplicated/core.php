<?php

namespace itscomplicated;

function createRelationshipRequest(array $initiatingUser, array $receivingUser, array $relationshipType): int
{
    $relationshipId = \itscomplicated\addRelationship([
        'type_id' => $relationshipType['id'],
    ]);

    \itscomplicated\addRelationshipUser($relationshipId, $initiatingUser['uid'], true, true);
    \itscomplicated\addRelationshipUser($relationshipId, $receivingUser['uid']);

    \itscomplicated\sendRelationshipNotificationPm(
        'request_received',
        $initiatingUser,
        $receivingUser,
        $relationshipType
    );

    return $relationshipId;
}

function removeRelationshipRequest(int $relationshipId): bool
{
    return \itscomplicated\deleteRelationshipWhere("id=" . (int)$relationshipId . " AND active = 0 AND date_start = 0");
}

function setRelationshipRequestAcceptedForUser(array $relationship, int $userId): bool
{
    $result = \itscomplicated\updateRelationshipUser($relationship['id'], $userId, [
        'accepted' => 1,
    ]);

    $relationshipUsers = \itscomplicated\getRelationshipUsers($relationship['id']);

    if (!array_search(0, array_column($relationshipUsers, 'accepted'))) {
        \itscomplicated\updateRelationshipById($relationship['id'], [
            'active' => 1,
            'date_start' => \TIME_NOW,
        ]);

        $relationshipInitiatingUserId = null;

        foreach ($relationshipUsers as $relationshipUser) {
            if ($relationshipUser['initiated']) {
                $relationshipInitiatingUserId = $relationshipUser['user_id'];
                break;
            }
        }

        if ($relationshipInitiatingUserId) {
            \itscomplicated\sendRelationshipNotificationPm(
                'request_accepted',
                $userId,
                $relationshipInitiatingUserId,
                $relationship['type_id']
            );
        }
    }

    return $result;
}

function endRelationshipForUser(array $relationship, array $user): bool
{
    $result = \itscomplicated\updateRelationshipById($relationship['id'], [
        'active' => 0,
        'date_end' => \TIME_NOW,
    ]);

    if (\itscomplicated\getSettingValue('notification_relationship_ended') == 1) {
        $relationshipUsers = \itscomplicated\getRelationshipUsers($relationship['id']);

        foreach ($relationshipUsers as $relationshipUser) {
            if ($relationshipUser['user_id'] != $user['uid']) {
                \itscomplicated\sendRelationshipNotificationPm(
                    'relationship_ended',
                    $user,
                    $relationshipUser['user_id'],
                    $relationship['type_id']
                );
            }
        }
    }

    return (bool)$result;
}

function sendRelationshipNotificationPm(string $action, $initiatingUser, $receivingUser, $relationshipType): bool
{
    global $mybb, $lang;

    if (!is_array($initiatingUser)) {
        $initiatingUser = \get_user($initiatingUser);
    }

    if (!is_array($receivingUser)) {
        $receivingUser = \get_user($receivingUser);
    }

    if (!is_array($relationshipType)) {
        $relationshipType = \itscomplicated\getRelationshipTypeById($relationshipType);
    }

    $relationshipTypeTitle = \htmlspecialchars_uni(
        $lang->parse($relationshipType['title'])
    );

    switch ($action) {
        case 'request_received':
            $subject = 'itscomplicated_relationships_notification_request_received';
            $message = ['itscomplicated_relationships_notification_request_received_message', $initiatingUser['username'], $relationshipTypeTitle];
            break;
        case 'request_accepted':
            $subject = 'itscomplicated_relationships_notification_request_accepted';
            $message = ['itscomplicated_relationships_notification_request_accepted_message', $initiatingUser['username'], $relationshipTypeTitle];
            break;
        case 'relationship_ended':
        default:
            $subject = 'itscomplicated_relationships_notification_relationship_ended';
            $message = ['itscomplicated_relationships_notification_relationship_ended_message', $initiatingUser['username'], $relationshipTypeTitle];
            break;
    }

    if (!$receivingUser['language'] || !$lang->language_exists($receivingUser['language'])) {
        $language = $mybb->settings['bblanguage'];
    } else {
        $language = $receivingUser['language'];
    }

    $pm = array(
        'subject' => $subject,
        'message' => $message,
        'touid' => $receivingUser['uid'],
        'receivepms' => $receivingUser['buddyrequestspm'],
        'language' => $language,
        'language_file' => 'itscomplicated',
    );

    return \send_pm($pm, -1);
}

function getRelationshipRequestConditionResultsForUsers(array $initiatingUser, array $receivingUser)
{
    return [
        'not_with_self' => $initiatingUser['uid'] != $receivingUser['uid'],
        'initiating_user_in_group' => \itscomplicated\userInRelationshipGroup($initiatingUser),
        'receiving_user_in_group' => \itscomplicated\userInRelationshipGroup($receivingUser),
        'initiating_user_under_limit' => \itscomplicated\activeUserRelationshipsUnderLimit($initiatingUser['uid']),
        'receiving_user_under_limit' => \itscomplicated\activeUserRelationshipsUnderLimit($receivingUser['uid']),
        'initiating_user_on_ignored_list' => !\itscomplicated\userOnIgnoreList($initiatingUser['uid'], $receivingUser['uid']),
    ];
}

function getFirstFalseCondition(array $conditionResults): ?string
{
    $key = array_search(false, $conditionResults);

    if ($key) {
        return $key;
    } else {
        return null;
    }
}

function userInRelationshipGroup($user): bool
{
    $userGroups = \itscomplicated\getCsvSettingValues('relationship_groups');

    return (
        in_array(-1, $userGroups) ||
        count(\is_member($userGroups, $user)) != 0
    );
}

function activeUserRelationshipsUnderLimit(int $userId): bool
{
    return (
        \itscomplicated\getSettingValue('relationship_limit') == 0 ||
        \itscomplicated\countActiveUserRelationships($userId) < \itscomplicated\getSettingValue('relationship_limit')
    );
}

function userOnIgnoreList(int $subjectUserId, $receivingUser): bool
{
    if (!is_array($receivingUser)) {
        $receivingUser = \get_user($receivingUser);
    }

    return (
        !empty($receivingUser['ignorelist']) &&
        strpos(',' . $receivingUser['ignorelist'] . ',', ',' . $subjectUserId . ',')
    );
}

function getUserRelationshipsWithTypesByState(int $userId): array
{
    $userRelationshipsWithTypesByState = [
        'active' => [],
        'requests_received' => [],
        'requests_sent' => [],
    ];

    $userRelationships = \itscomplicated\getUserRelationshipsWithTypes($userId);

    foreach ($userRelationships as $relationship) {
        if ($relationship['active'] == 1) {
            $state = 'active';
        } elseif ($relationship['date_end'] == 0 && $relationship['initiated']) {
            $state = 'requests_sent';
        } elseif ($relationship['accepted'] == 0) {
            $state = 'requests_received';
        } else {
            $state = null;
        }

        if ($state) {
            $userRelationshipsWithTypesByState[$state][] = $relationship;
        }
    }

    return $userRelationshipsWithTypesByState;
}
