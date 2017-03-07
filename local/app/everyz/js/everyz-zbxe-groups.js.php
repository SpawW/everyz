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
<link href="http://wwwendt.de/tech/dynatree/src/skin/ui.dynatree.css" rel="stylesheet" type="text/css" id="skinSheet">
 * */
?>

<link href="local/app/everyz/css/dynatree/ui.dynatree.css" rel="stylesheet" type="text/css" id="skinSheet">
 
<?php
// Load Scripts
//$scripts = ['jquery/jquery.js', 'jquery/jquery-ui.custom.js', 'jquery/jquery.cookie.js', 'jquery/ui.dynatree.css', 'jquery/jquery.dynatree.js'];
//zbxeJSLoad($scripts);

/**
 * Explode any single-dimensional array into a full blown tree structure,
 * based on the delimiters found in it's keys.
 *
 * @author	Kevin van Zonneveld &lt;kevin@vanzonneveld.net>
 * @author	Lachlan Donald
 * @author	Takkie
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: explodeTree.inc.php 89 2008-09-05 20:52:48Z kevin $
 * @link	  http://kevin.vanzonneveld.net/
 *
 * @param array   $array
 * @param string  $delimiter
 * @param boolean $baseval
 *
 * @return array
 */
function explodeTree($array, $delimiter = '_', $baseval = false) {
    $baseIdent = '__base_val';
    if (!is_array($array))
        return false;
    $splitRE = '/' . preg_quote($delimiter, '/') . '/';
    $returnArr = array();
    foreach ($array as $key => $val) {
        // Get parent parts and the current leaf
        $parts = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);

        // Build parent structure
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
            if (!isset($parentArr[$part])) {
                $parentArr[$part] = array();
            } elseif (!is_array($parentArr[$part])) {
                if ($baseval) {
                    $parentArr[$part] = array($baseIdent => $parentArr[$part]);
                } else {
                    $parentArr[$part] = array();
                }
            }
            $parentArr = &$parentArr[$part];
        }

        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
            $parentArr[$leafPart] = $val;
        } elseif ($baseval && is_array($parentArr[$leafPart])) {
            $parentArr[$leafPart][$baseIdent] = $val;
        }
    }
    return $returnArr;
}

foreach ($groups as $key => $value) {
    $key_files[$value['name']] = $value['name'];
    $groupIndex[$value['name']] = $value;
}
// Execute //
$tree = explodeTree($key_files, "/", true);

function treeData($arr, $groupIndex, $baseIdent, $vIndex) {
    $return = '';
    foreach ($arr as $key => $value) {
        $extra = '';
        if (is_array($value)) {
            $extra .= ', children: [' . treeData($value, $groupIndex, $baseIdent, $vIndex) . ']';
            if (!isset($value[$baseIdent]) || !isset($groupIndex[$value[$baseIdent]])) {
                $vIndex++;
                $ident = 'zbxe' . $vIndex;
                $name = $key;
            } else {
                $ident = $groupIndex[$value[$baseIdent]]['groupid'];
                $name = $groupIndex[$value[$baseIdent]]['name'];
                if (strpos($name, '/')) {
                    $name = explode('/', $name);
                    $name = $name[count($name) - 1];
                }
            }
            
            $return .= ($return == '' ? '' : "\n, ") . '{title: "' . $name
                    . '", key: "' . $ident . '", expand: false, isFolder: true' . $extra . '}';
        } else {
            $ident = $groupIndex[$value]['groupid'];
            $name = $groupIndex[$value]['name'];
            if (strpos($name, '/')) {
                $name = explode('/', $name);
                $name = $name[count($name) - 1];
            }
            if ($key != $baseIdent)
                $return .= ($return == '' ? '' : "\n, ") . '{title: "' . $name
                        . '", key: "' . $ident . '", expand: false, isFolder: true}';
        }
    }
    return $return;
}

$groupData = treeData($tree, $groupIndex, '__base_val', 0);
?>

<script type="text/javascript">
    var treeData = [<?php echo $groupData; ?>];

    $(function () {
        $("#groupsNow").dynatree({
            checkbox: true,
            selectMode: 3,
            children: treeData,
            onSelect: function (select, node) {
                // Get a list of all selected nodes, and convert to a key array:
                var selKeys = $.map(node.tree.getSelectedNodes(), function (node) {
                    return node.data.key;
                });
                $("#echoSelection3").text(selKeys.join(", "));

                // Get a list of all selected TOP nodes
                var selRootNodes = node.tree.getSelectedNodes(true);
                // ... and convert to a key array:
                var selRootKeys = $.map(selRootNodes, function (node) {
                    return node.data.key;
                });
                $("#echoSelectionRootKeys3").text(selRootKeys.join(", "));
                $("#echoSelectionRoots3").text(selRootNodes.join(", "));
            },
            onDblClick: function (node, event) {
                node.toggleSelect();
            },
            onKeydown: function (node, event) {
                if (event.which == 32) {
                    node.toggleSelect();
                    return false;
                }
            },
            // The following options are only required, if we have more than one tree on one page:
//        initId: "treeData",
            cookieId: "dynatree-Cb3",
            idPrefix: "dynatree-Cb3-"
        });

    });
</script>

<div id="groupsNow"></div>
