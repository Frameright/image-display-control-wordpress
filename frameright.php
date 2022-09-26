<?php
/**
 * Plugin Name:       Frameright
 * Plugin URI:        https://frameright.io
 * Description:       An easy way to leverage image cropping metadata on your site. Power to the pictures!
 * Author:            Frameright
 * Author URI:        https://frameright.io
 * Version:           0.0.1
 * License:           GPL-3.0-or-later
 * License URI:       license.txt
 * Text Domain:       frameright
 * Domain Path:       /languages
 * Requires PHP:      5.6
 * Requires at least: 5.1
 *
 * Frameright WordPress Plugin is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Frameright WordPress Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Frameright WordPress Plugin. If not, see
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @package Frameright
 */

namespace Frameright;

if ( is_admin() ) {
    require_once __DIR__ . '/admin/admin.php';
}
