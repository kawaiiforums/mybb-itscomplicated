<?php

namespace itscomplicated;

function addRelationshipType(array $data): int
{
    global $db;

    $db->insert_query('itscomplicated_relationship_types', [
        'title' => $db->escape_string($data['title']),
    ]);

    if ($db->type == 'pgsql') {
        $result = $db->fetch_field(
            $db->query('SELECT lastval() AS i'),
            'i'
        );
    } else {
        $result = $db->insert_id();
    }

    return $result;
}

function getRelationshipTypeById(int $id): array
{
    global $db;

    return $db->fetch_array(
        $db->simple_select('itscomplicated_relationship_types', '*', 'id=' . (int)$id)
    );
}

function getRelationshipTypes(): array
{
    global $db;

    $rows = [];

    $query = $db->simple_select('itscomplicated_relationship_types', '*');

    while ($row = $db->fetch_array($query)) {
        $rows[$row['id']] = $row;
    }

    return $rows;
}

function updateRelationshipTypeById(int $id, array $data): bool
{
    global $db;

    return (bool)$db->update_query('itscomplicated_relationship_types', [
        'title' => $db->escape_string($data['title']),
    ], 'id=' . (int)$id);
}

function deleteRelationshipTypeById(int $id): bool
{
    global $db;

    return (bool)$db->delete_query('itscomplicated_relationship_types', 'id=' . (int)$id);
}


function addRelationship(array $data): int
{
    global $db;

    $db->insert_query('itscomplicated_relationships', [
        'type_id' => (int)$data['type_id'],
        'active' => (int)($data['active'] ?? 0),
        'date_start' => (int)($data['date_start'] ?? 0),
        'date_end' => (int)($data['date_end'] ?? 0),
    ]);

    if ($db->type == 'pgsql') {
        $result = $db->fetch_field(
            $db->query('SELECT lastval() AS i'),
            'i'
        );
    } else {
        $result = $db->insert_id();
    }

    return $result;
}

function getRelationshipById(int $id): ?array
{
    global $db;

    $query = $db->simple_select('itscomplicated_relationships', '*', 'id=' . (int)$id);

    if ($db->num_rows($query)) {
        return $db->fetch_array($query);
    } else {
        return null;
    }
}

function updateRelationshipById(int $id, array $data): bool
{
    global $db;

    $updates = [];

    if (isset($data['type_id'])) {
        $updates['type_id'] = (int)$data['type_id'];
    }

    if (isset($data['active'])) {
        $updates['active'] = (int)$data['active'];
    }

    if (isset($data['date_start'])) {
        $updates['date_start'] = (int)$data['date_start'];
    }

    if (isset($data['date_end'])) {
        $updates['date_end'] = (int)$data['date_end'];
    }

    return (bool)$db->update_query('itscomplicated_relationships', $updates, 'id=' . (int)$id);
}

function deleteRelationshipById(int $id): bool
{
    return \itscomplicated\deleteRelationshipWhere('id=' . (int)$id);
}

function deleteRelationshipWhere(string $where): bool
{
    global $db;

    return (bool)$db->delete_query('itscomplicated_relationships', $where);
}


function addRelationshipUser(int $relationshipId, int $userId, bool $accepted = false, bool $initiated = false): int
{
    global $db;

    $db->insert_query('itscomplicated_relationships_users', [
        'relationship_id' => (int)$relationshipId,
        'user_id' => (int)$userId,
        'accepted' => (int)$accepted,
        'initiated' => (int)$initiated,
    ]);

    if ($db->type == 'pgsql') {
        $result = $db->fetch_field(
            $db->query('SELECT lastval() AS i'),
            'i'
        );
    } else {
        $result = $db->insert_id();
    }

    return $result;
}

function updateRelationshipUser(int $relationshipId, int $userId, array $data): bool
{
    global $db;

    $updates = [];

    if (isset($data['accepted'])) {
        $updates['accepted'] = (int)$data['accepted'];
    }

    return (bool)$db->update_query('itscomplicated_relationships_users', $updates, "relationship_id = " . (int)$relationshipId . " AND user_id = " . (int)$userId);
}

function removeRelationshipUser(int $relationshipId, int $userId): bool
{
    global $db;

    return (bool)$db->delete_query(
        'itscomplicated_relationships_users',
        "user_id=" . (int)$userId . " AND relationship_id=" . (int)$relationshipId
    );
}


function countActiveUserRelationships(int $userId): int
{
    global $db;

    return (int)$db->fetch_field(
        $db->query("
            SELECT
                COUNT(ru.relationship_id) AS n
            FROM
                " . TABLE_PREFIX . "itscomplicated_relationships_users ru
                INNER JOIN " . TABLE_PREFIX . "itscomplicated_relationships r ON ru.relationship_id = r.id
            WHERE
                ru.user_id = " . (int)$userId . " AND
                r.active = 1
        "),
        'n'
    );
}

function getRelationshipUsers(int $relationshipId): array
{
    global $db;

    $rows = [];

    $query = $db->query("
        SELECT
            ru.*, u.*
        FROM
            " . TABLE_PREFIX . "itscomplicated_relationships_users ru
            INNER JOIN " . TABLE_PREFIX . "users u ON ru.user_id = u.uid
        WHERE
            relationship_id = " . (int)$relationshipId . "
    ");

    while ($row = $db->fetch_array($query)) {
        $rows[$row['user_id']] = $row;
    }

    return $rows;
}

function getUserRelationships(int $userId): array
{
    global $db;

    $rows = [];

    $query = $db->query("
        SELECT
            r.*
        FROM
            " . TABLE_PREFIX . "itscomplicated_relationships_users ru
            INNER JOIN " . TABLE_PREFIX . "itscomplicated_relationships r ON ru.relationship_id = r.id
        WHERE
            ru.user_id = " . (int)$userId . "
    ");

    while ($row = $db->fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
}

function deleteUserRelationships(int $userId): int
{
    $count = 0;

    $userRelationships = \itscomplicated\getUserRelationships($userId);

    foreach ($userRelationships as $row) {
        $result = \itscomplicated\deleteRelationshipById($row['id']);

        if ($result) {
            $count++;
        }
    }

    return $count;
}

function getUserRelationshipsWithTypes(int $userId, bool $activeOnly = false): array
{
    global $db;

    $rows = [];

    $query = $db->query("
        SELECT
            r.*, ru.*, rt.title
        FROM
            " . TABLE_PREFIX . "itscomplicated_relationships_users ru
            INNER JOIN " . TABLE_PREFIX . "itscomplicated_relationships r ON ru.relationship_id = r.id
            INNER JOIN " . TABLE_PREFIX . "itscomplicated_relationship_types rt ON r.type_id = rt.id
        WHERE
            ru.user_id = " . (int)$userId . "
            " . ($activeOnly ? "AND active = 1" : null) . "
    ");

    while ($row = $db->fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
}
