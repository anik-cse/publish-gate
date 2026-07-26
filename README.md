# Publish Gate

Publish Gate is a lightweight Block Editor quality-control gateway that enforces pre-flight publication rules, blocks unverified posts, and provides role-based permission overrides.

This repository is for developers and contributors. For the end-user documentation, please refer to the WordPress.org Plugin Directory.

## Development Setup

This plugin uses modern JavaScript/React built via `@wordpress/scripts`.

1. Clone the repository:
   ```bash
   git clone https://github.com/anik-cse/publish-gate.git
   cd publish-gate
   ```
2. Install Node dependencies:
   ```bash
   npm install
   ```
3. Start the Webpack watch server for local development:
   ```bash
   npm start
   ```

## Building for Release

Before pushing a new release to WordPress.org, you must compile the production assets and bundle the plugin. You can do this automatically with:

```bash
npm run production
```

This script will run the Webpack build process and automatically generate a deployable `publish-gate.zip` archive containing everything needed for production.

> **Note:** Do not forget to commit the updated `/build/` directory to this repository before deploying to WordPress.org SVN. The `/build/` folder is strictly required for the plugin to function in production.

## Deploying to WordPress.org (SVN)

To release an update to the WordPress.org Plugin Directory using the manual SVN process:

1. Check out your plugin's SVN repository locally:
   ```bash
   svn co https://plugins.svn.wordpress.org/publish-gate publish-gate-svn
   ```
2. Update the `trunk` folder:
   Copy the contents of this Git repository (excluding `.git`, `node_modules`, and `.gitignore`) into the `/trunk/` folder of your SVN checkout.
3. Commit to Trunk:
   ```bash
   cd publish-gate-svn
   svn stat
   # If there are new files, use `svn add`
   svn ci -m "Updating trunk for release 1.0.0"
   ```
4. Create a Tag for the Release:
   ```bash
   svn cp https://plugins.svn.wordpress.org/publish-gate/trunk https://plugins.svn.wordpress.org/publish-gate/tags/1.0.0 -m "Tagging version 1.0.0"
   ```

Your new version will be live on WordPress.org within a few minutes!
