<?php

namespace itscomplicated;

function addHooks(array $hooks, string $namespace = null)
{
    global $plugins;

    if ($namespace) {
        $prefix = $namespace . '\\';
    } else {
        $prefix = null;
    }

    foreach ($hooks as $hook) {
        $plugins->add_hook($hook, $prefix . $hook);
    }
}

function addHooksNamespace(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;
        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

function getSettingValue(string $name): string
{
    global $mybb;
    return $mybb->settings['itscomplicated_' . $name];
}

function getCsvSettingValues(string $name): array
{
    static $values;

    if (!isset($values[$name])) {
        $values[$name] = array_filter(explode(',', getSettingValue($name)));
    }

    return $values[$name];
}

function getDelimitedSettingValues(string $name): array
{
    static $values;

    if (!isset($values[$name])) {
        $values[$name] = array_filter(preg_split("/\\r\\n|\\r|\\n/", getSettingValue($name)));
    }

    return $values[$name];
}

function loadTemplates(array $templates, string $prefix = null): void
{
    global $templatelist;

    if (!empty($templatelist)) {
        $templatelist .= ',';
    }
    if ($prefix) {
        $templates = preg_filter('/^/', $prefix, $templates);
    }

    $templatelist .= implode(',', $templates);
}

function tpl(string $name): string
{
    global $templates;

    $templateName = 'itscomplicated_' . $name;
    $directory = MYBB_ROOT . 'inc/plugins/itscomplicated/templates/';

    if (DEVELOPMENT_MODE) {
        $templateContent = str_replace(
            "\\'",
            "'",
            addslashes(
                file_get_contents($directory . $name . '.tpl')
            )
        );

        if (!isset($templates->cache[$templateName]) && !isset($templates->uncached_templates[$templateName])) {
            $templates->uncached_templates[$templateName] = $templateName;
        }

        return $templateContent;
    } else {
        return $templates->get($templateName);
    }
}

function replaceInTemplate(string $title, string $find, string $replace): bool
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    return \find_replace_templatesets($title, '#' . preg_quote($find, '#') . '#', $replace);
}

function getArrayWithColumnAsKey(array $array, string $column): array
{
    return array_combine(array_column($array, $column), $array);
}

function getCacheValue(string $key): ?string
{
    global $cache;

    return $cache->read('itscomplicated')[$key] ?? null;
}

function updateCache(array $values, bool $overwrite = false): void
{
    global $cache;

    if ($overwrite) {
        $cacheContent = $values;
    } else {
        $cacheContent = $cache->read('itscomplicated');
        $cacheContent = array_merge($cacheContent, $values);
    }

    $cache->update('itscomplicated', $cacheContent);
}

function getFilesContentInDirectory(string $path, string $fileNameSuffix)
{
    $contents = [];

    $directory = new \DirectoryIterator($path);

    foreach ($directory as $file) {
        if (!$file->isDot() && !$file->isDir()) {
            $templateName = $file->getPathname();
            $templateName = basename($templateName, $fileNameSuffix);
            $contents[$templateName] = file_get_contents($file->getPathname());
        }
    }

    return $contents;
}

function dropTables(array $tableNames, bool $onlyIfExists = false, bool $cascade = false): void
{
    global $db;

    if ($cascade) {
        if (in_array($db->type, ['mysqli', 'mysql'])) {
            $db->write_query('SET foreign_key_checks = 0');
        } elseif ($db->type == 'sqlite') {
            $db->write_query('PRAGMA foreign_keys = OFF');
        }
    }

    foreach ($tableNames as $tableName) {
        if (!$onlyIfExists || $db->table_exists($tableName)) {
            if ($db->type == 'pgsql' && $cascade) {
                $db->write_query("DROP TABLE " . TABLE_PREFIX . $tableName . " CASCADE");
            } else {
                $db->drop_table($tableName, true);
            }
        }
    }

    if ($cascade) {
        if (in_array($db->type, ['mysqli', 'mysql'])) {
            $db->write_query('SET foreign_key_checks = 1');
        } elseif ($db->type == 'sqlite') {
            $db->write_query('PRAGMA foreign_keys = ON');
        }
    }
}

function loadPluginLibrary(): void
{
    global $lang, $PL;

    $lang->load('itscomplicated');

    if (!defined('PLUGINLIBRARY')) {
        define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
    }

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->itscomplicated_admin_pluginlibrary_missing, 'error');

        admin_redirect('index.php?module=config-plugins');
    } elseif (!$PL) {
        require_once PLUGINLIBRARY;
    }
}
