<?php
/*
 * * Purpose: Chart with Top 5 templates
 * * Adail Horst - http://spinola.net.br/blog
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
 * TodoS: ===========================================================================
 * */

/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */
// Configuration variables =====================================================
$moduleName = "top5templates";
$baseProfile .= $moduleName;
$moduleTitle = 'Top 5 Templates';
$divName = "body-$moduleName";
// Common fields
addFilterActions();
// Specific fields
//addFilterParameter("format", T_ZBX_INT, 0, false, false, false);
//addFilterParameter("hostids", T_ZBX_INT, 0, false, false, false);
// Field validation
check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
//$hosts = checkAccessHost('hostids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function top5TemplatesData() {
    $query = "SELECT COUNT(hte.templateid) as total, hte.templateid
  FROM hosts_templates hte
 INNER JOIN hosts hos 
    ON hos.hostid = hte.hostid 
   AND hos.status = 0
  GROUP BY hte.templateid
 ORDER BY total DESC
LIMIT 5 OFFSET 0
";
    $res = DBselect($query);
    $jsonResult = [];
    while ($row = DBfetch($res)) {
        $templateName = templateName($row['templateid']);
        $jsonResult[] = [
            "label" => substr($templateName, 0, 20) . (strlen($templateName) > 20 ? '...' : ''),
            "value" => (int) $row['total']
        ];
    }
    // Array padrao JSON / Javascript??
    return json_encode($jsonResult, JSON_UNESCAPED_UNICODE); //'[]';
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
zbxeJSLoad(['d3/d3.min.js', 'd3/d3pie.js','everyzD3Functions.js']);
?>
<script>
    container="<?php echo $divName; ?>";
    data=<?php echo top5TemplatesData(); ?>;
    newD3Pie(container,data,"{label}: {value} <?php echo strtolower(_("Hosts")); ?>",false,250);
</script>
    
<?php


