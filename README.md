# It's Complicated
Allows users to define relationships with one another.

### Dependencies
- MyBB >= 1.8.x
- https://github.com/frostschutz/MyBB-PluginLibrary
- PHP >= 7.1

### Plugin Management Events
- **Install:**
  - Database structure created/altered
- **Uninstall:**
  - Database structure & data deleted/restored
  - Settings deleted
- **Activate:**
  - Settings populated/updated
  - Templates & stylesheets inserted/altered
- **Deactivate:**
  - Templates & stylesheets removed/restored

### Localization

Relationship Types can be translated using MyBB's language system &mdash; language strings added to the `inc/languages/*/itscomplicated.lang.php` file for each language are loaded automatically. 

##### Titles

The _Title_ field supports MyBB language variables in the `<lang:variable-name>` format.

The following definition in the language file (already included):

```php
$l['itscomplicated_relationships_type_married'] = 'Married';
```

will display `Married`, in user's language, when `<lang:itscomplicated_relationships_type_married>` is entered in the _Title_ field.

##### Status Notes

Additionally, it's possible to create custom status notes displayed on profile pages by adding a language definition 
`itscomplicated_relationships_type_NAME_to`, where `NAME` is the value provided in the _Name_ field.

The following definition (already included):
```php
$l['itscomplicated_relationships_type_married_to'] = 'Married to {1} since {3}';
```

will display `Married to Username since 06-26-2015` instead of the default format, substituting:
 - `{1}` for the partner's name,
 - `{2}` for the Relationship Type Title,
 - `{3}` for the start date.

### Development Mode
The plugin can operate in development mode, where plugin templates are being fetched directly from the `templates/` directory - set `itscomplicated\DEVELOPMENT_MODE` to `true` in `inc/plugins/itscomplicated.php`.
