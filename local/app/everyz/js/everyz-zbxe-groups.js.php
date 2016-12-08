<?php
/*
 * * Purpose: Groups Manager - Javascript
 * * Adail Horst / Aristoteles Araujo
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
  <script src="local/app/everyz/js/jquery3.1.1.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
  <script src="local/app/everyz/js/jstree.min.js"></script>
 
 * */
?>
<link rel="stylesheet" href="local/app/everyz/css/style.min.css" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
<script src="local/app/everyz/js/jstree.min.js"></script>

<h1>HTML demo</h1>
<div id="html" class="demo">
    <ul>
        <li data-jstree='{ "opened" : true }'>Root node
            <ul>
                <li data-jstree='{ "selected" : true }'>Child node 1</li>
                <li>Child node 2</li>
            </ul>
        </li>
    </ul>
</div>
<div id="#data" class="demo"></div>

<script type="text/javascript">
    if (!window.jQuery) {
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.async = false; // CHANGES
        script.src = "//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js";
        var oScripts = document.getElementsByTagName("script");
        var s = oScripts[0];
        s.parentNode.insertBefore(script, s);
    }
</script>
<script src="local/app/everyz/js/jstree.min.js"></script>


<script type="text/javascript">
// html demo
    $('#html').jstree();
    /* *************************************************************************
     * 
     * Dynamic Data 
     * 
     * ********************************************************************** */


</script>