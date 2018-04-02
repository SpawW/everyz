<?php
/*
* * Purpose: HTML configuration of geolocation host popup
* * Adail Horst.
* *
* * This program is free software; you can redistribute it and/or modify
* * it under the terms of the GNU General Public License as published by
* * the Free Software Foundation; either version 2 of the License, or
* * (at your option) any later version.
* *
* * This program is distributed in the hope that it will be useful,
* * but WITHOUT ANY WARRANTY; without even the implied warranty of
* * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* * GNU General Public License for more details.
* *
* * You should have received a copy of the GNU General Public License
* * along with this program; if not, write to the Free Software
* * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
* */
$popupHost = '<div><hostIdentify>: <b><host></b> (<b><conn></b>)</div>'
.'<div><shortcuts><editbutton></div>'
.'<div><eventsList></div>';

// Sample conversion of this popup template
//echo parse_html($popupHost,array('host' => 'adail', 'ip' => '127.0.0.1'));
