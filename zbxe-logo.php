<?php

/* Used for inicial development: 
 * * Objective: Show a logo for use in frontend logo based on image from Zabbix 
 * * network map images
 * * Copyright 2014 - Adail Horst - http://spinola.net.br/blog
 * *
 * * This file is part of Zabbix-Extras.
 * * It is not authorized any change that would mask the existence of the plugin. 
 * * The menu names, logos, authorship and other items identificatory plugin 
 * * should always be maintained.
 * *
 * * This program is free software; you can redistribute it and/or modify
 * * it under the terms of the GNU General Public License as published by
 * * the Free Software Foundation; either version 2 of the License, or
 * * (at your option) any later version.
 * *
 * * This program is distributed in the hope that it will be useful,
 * * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * * GNU General Public License for more details.
 * *
 * * You should have received a copy of the GNU General Public License
 * * along with this program; if not, write to the Free Software
 * * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * */

$VG_IMAGE = true;
require_once dirname(__FILE__) . '/include/config.inc.php';
require_once dirname(__FILE__) . '/local/app/everyz/include/everyzFunctions.php';
$VG_IMAGE = false;
header("Content-type: image/png");
$imageid = zbxeConfigValue("company_logo_" . (getRequest2("mode") == "login" ? "login" : "site"));
$query = "SELECT image FROM images WHERE imageid = " . $imageid;
zbxeErrorLog($VG_DEBUG, 'Logotipo do EverZ [' . $query . ']');
echo zbx_unescape_image(zbxeFieldValue($query, 'image'));
