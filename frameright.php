<?php
/**
 * Plugin Name:       Image Display Control
 * Plugin URI:        https://github.com/Frameright/image-display-control-wordpress
 * Description:       An easy way to leverage image cropping metadata on your site. Made by Frameright. Power to the pictures!
 * Author:            Frameright
 * Author URI:        https://frameright.io
 * Version:           0.0.3
 * License:           GPL-3.0-or-later
 * License URI:       license.txt
 * Text Domain:       frameright
 * Domain Path:       /languages
 * Requires PHP:      5.6
 * Requires at least: 5.1
 *
 * Image Display Control WordPress Plugin is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of
 * the License, or any later version.
 *
 * Image Display Control WordPress Plugin is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Image Display Control WordPress Plugin. If not, see
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @package Frameright
 */

namespace Frameright;

require_once __DIR__ . '/src/admin/admin-plugin.php';
require_once __DIR__ . '/src/render/render-plugin.php';

new Admin\AdminPlugin();
new Render\RenderPlugin();
