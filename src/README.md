# Source code

This folder contains two subfolders:

- `admin/`: implementation of the plugin part firing on administrative hooks;
- `render/`: implementation of the plugin part firing on rendering hooks;
- `vendor/`: third-party libraries.

Note that although the `vendor/` subfolder is generated (via
`composer install`), we git-commit it as it is intended to be shipped with the
plugin.
