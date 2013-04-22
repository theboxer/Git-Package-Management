<?php
/**
 * GitPackageManagement
 *
 * Copyright 2010 by Shaun McCormick <shaun+gitpackagemanagement@modx.com>
 *
 * GitPackageManagement is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * GitPackageManagement is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * GitPackageManagement; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package gitpackagemanagement
 */
/**
 * Properties for the GitPackageManagement snippet.
 *
 * @package gitpackagemanagement
 * @subpackage build
 */
$properties = array(
    array(
        'name' => 'tpl',
        'desc' => 'prop_gitpackagemanagement.tpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'Item',
        'lexicon' => 'gitpackagemanagement:properties',
    ),
    array(
        'name' => 'sortBy',
        'desc' => 'prop_gitpackagemanagement.sortby_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'name',
        'lexicon' => 'gitpackagemanagement:properties',
    ),
    array(
        'name' => 'sortDir',
        'desc' => 'prop_gitpackagemanagement.sortdir_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'ASC',
        'lexicon' => 'gitpackagemanagement:properties',
    ),
    array(
        'name' => 'limit',
        'desc' => 'prop_gitpackagemanagement.limit_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 5,
        'lexicon' => 'gitpackagemanagement:properties',
    ),
    array(
        'name' => 'outputSeparator',
        'desc' => 'prop_gitpackagemanagement.outputseparator_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'gitpackagemanagement:properties',
    ),
    array(
        'name' => 'toPlaceholder',
        'desc' => 'prop_gitpackagemanagement.toplaceholder_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => true,
        'lexicon' => 'gitpackagemanagement:properties',
    ),
/*
    array(
        'name' => '',
        'desc' => 'prop_gitpackagemanagement.',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'gitpackagemanagement:properties',
    ),
    */
);

return $properties;