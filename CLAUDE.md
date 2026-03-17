# Development Notes for Claude Code

## Important: File Permissions in src/Resources/

### Issue
All files and directories in `src/Resources/` **must be readable by the `www-data` user** that runs PHP-FPM inside Docker containers.

### Background
When developing this plugin, we encountered an issue where Twig hooks configuration wasn't being loaded. The problem was that directories in `src/Resources/config/twig_hooks/` had restrictive permissions (700 - owner only), preventing PHP-FPM from reading them.

Directories need both `+r` (to list contents) and `+x` (to traverse into them), so they require mode 755. Files only need `+r` for the "others" group — making files executable with `+x` is unnecessary and undesirable. Use `chmod 644` for files and `chmod 755` for directories. Do **not** use `chmod -R 755` as that makes all files executable too.

### Symptoms
- Configuration files exist and are valid
- `debug:config` shows the configuration is registered
- But templates/hooks don't render in the browser
- Cache clearing doesn't help

### Solution
Ensure all files and directories in `src/Resources/` have appropriate permissions:

```bash
# Fix permissions in twig_hooks — directories get 755, files get 644
find src/Resources/config/twig_hooks/ -type d -exec chmod 755 {} +
find src/Resources/config/twig_hooks/ -type f -exec chmod 644 {} +
```

### Prevention
When creating new files or directories in `src/Resources/`, always check permissions:

```bash
# Check current permissions
ls -la src/Resources/config/

# If you see drwx------ or -rw------- (owner only), fix them:
chmod 755 <directory>
chmod 644 <file>
```

### Technical Details
- PHP-FPM runs as `www-data` user in Docker
- Your local development user (e.g., `jaroslav`) is the file owner
- If permissions are 700/600, only the owner can read
- PHP-FPM gets "Permission denied" but often fails silently
- Result: Configuration files are ignored, features don't work

### Related Files That Need Proper Permissions
- `src/Resources/config/**/*.yaml` - All configuration files
- `src/Resources/views/**/*.twig` - All templates
- `src/Resources/translations/**/*.yml` - Translation files
- `src/Resources/public/**/*` - Public assets

**Remember:** When in doubt, use `chmod -R 755 src/Resources/` to ensure everything is accessible.
