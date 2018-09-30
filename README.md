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

### Development Mode
The plugin can operate in development mode, where plugin templates are being fetched directly from the `templates/` directory - set `itscomplicated\DEVELOPMENT_MODE` to `true` in `inc/plugins/itscomplicated.php`.
