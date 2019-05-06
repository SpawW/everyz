<?php
/*
 * * Purpose: Chart with Events / Last 5 weeks
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
 * */

/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */
// Configuration variables =====================================================
$moduleName = "events-5week";
$baseProfile .= $moduleName;
$moduleTitle = 'Events / Last 5 weeks';
$divName = "body-$moduleName";
check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function getStartAndEndDate($week, $year) {
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $ret['week_start'] = $dto->format('d m');
    $dto->modify('+6 days');
    $ret['week_end'] = $dto->format('d m');
    $start = explode(' ', $ret['week_start']);
    $end = explode(' ', $ret['week_end']);
    if ($start[1] == $end[1]) {
        return $start[0] . " a " . $end[0] . "/" . _($end[1]);
    } else {
        return $start[0] . "/" . _($start[1]) . " a " . $end[0] . "/" . _($end[1]);
    }
}

function events5WeekData() {
    global $DB;
    $query = "SELECT COUNT(subt.week) as total, subt.week FROM
  (SELECT CONCAT(" . ($DB['TYPE'] == ZBX_DB_POSTGRESQL ? " TO_CHAR(sub1.clock,'YYYY'),TO_CHAR(sub1.clock,'WW') " :
                    //MySQL
                    "DATE_FORMAT(sub1.clock,'%Y'),DATE_FORMAT(sub1.clock,'%U') " )
            . ") AS week FROM
    (SELECT " . ($DB['TYPE'] == ZBX_DB_POSTGRESQL ? "TO_TIMESTAMP" : "FROM_UNIXTIME") . "(eve.clock) AS clock FROM events eve WHERE
    source = 0 AND value =1) sub1
  ) subt
GROUP BY week
ORDER BY week DESC
LIMIT 5 OFFSET 0
";
    $res = DBselect($query);
    $jsonResult = [];
    while ($row = DBfetch($res)) {
        $year = substr($row['week'], 0, 4);
        $weekNum = substr($row['week'], 4, 2);
        $weekInfo = getStartAndEndDate((int) $weekNum, (int) $year);
        $jsonResult[] = [
            "label" => $weekInfo,
            "value" => (int) $row['total']
        ];
    }
    return json_encode($jsonResult, JSON_UNESCAPED_UNICODE);
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
zbxeJSLoad(['d3/d3.min.js', 'd3/d3pie.js', 'everyzD3Functions.js']);
?>
<script>
    container = "<?php echo $divName; ?>";
    data =<?php echo events5WeekData(); ?>;
    newD3Pie(container, data, "{label}: {value} <?php echo strtolower(_zeT("Events")); ?>", true, 350);
</script>
<?php
