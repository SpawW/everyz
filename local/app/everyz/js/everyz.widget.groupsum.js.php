<?php
/*
 * * Purpose: Chart with Top 5 groups
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
$moduleName = "groupsum";
$baseProfile .= $moduleName;
$moduleTitle = 'Top 5 Groups';
$divName = "body-$moduleName";
// Common fields
addFilterActions();
// Specific fields
addFilterParameter("format", T_ZBX_INT, 0, false, false, false);
addFilterParameter("hostids", T_ZBX_INT, 0, false, false, false);
// Field validation
check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
$hosts = checkAccessHost('hostids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function groupSumData() {
    $query = "SELECT COUNT(hgr.hostid) as total, gr.name 
  FROM hosts_groups hgr
 INNER JOIN groups gr 
    ON gr.groupid = hgr.groupid
 INNER JOIN hosts hos 
    ON hos.hostid = hgr.hostid 
   AND hos.status = 0
 GROUP BY gr.name 
 ORDER BY total DESC
LIMIT 0, 5
";
    $res = DBselect($query);
    $jsonResult = [];
    while ($row = DBfetch($res)) {
        $jsonResult[] = [
            "label" => substr($row['name'], 0, 30) . (strlen($row['name']) > 30 ? '...' : ''),
            "value" => (int) $row['total']
        ];
    }
    // Array padrao JSON / Javascript??
    return json_encode($jsonResult, JSON_UNESCAPED_UNICODE); //'[]';
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
zbxeJSLoad(['d3/d3.min.js', 'd3/d3pie.js','everyzD3Functions.js.php']);
?>
<script>
    container="<?php echo $divName; ?>";
    data=<?php echo groupSumData(); ?>;
    newD3Pie(container,data,"{label}: {value} <?php echo strtolower(_("Hosts")); ?>",true,350);
</script>

<?php


