# Source code

This folder contains two subfolders:

- `admin/`: implementation of the plugin when inside the admin panel;
- `website/`: implementation of the plugin when outside the admin panel;
- `vendor/`: third-party libraries.

Note that although the `vendor/` subfolder is generated (via
`composer install`), we git-commit it as it is intended to be shipped with the
plugin.
