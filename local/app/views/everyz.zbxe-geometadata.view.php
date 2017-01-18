<?php

/*
 * * Purpose: Generate ZabGeo Metadata
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
$moduleName = "zbxe-geometadata";
$baseProfile .= $moduleName;
$moduleTitle = 'ZabGeo - Metadata';
// Common fields
addFilterActions();
// Specific fields
addFilterParameter("format", T_ZBX_INT);
// Field validation
check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */

/* * ***************************************************************************
 * Display
 * ************************************************************************** */
commonModuleHeader($moduleName, $moduleTitle, true);

insert_show_color_picker_javascript();

$table->addRow(
        (new CFormList())
                ->addRow(_zeT('Circle'))
                ->addRow(_('Color'), new CColor('circle_color', '', false))
                ->addRow(_zeT('Line'))
                ->addRow(_zeT('Link'))
)
;

/* * ***************************************************************************
 * Display Footer 
 * ************************************************************************** */
$form->addItem([ $table]);
$dashboard->addItem($form)->show();
