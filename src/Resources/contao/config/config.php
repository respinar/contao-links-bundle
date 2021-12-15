<?php

/*
 * This file is part of Contao Links Bundle.
 * 
 * (c) Hamid Abbaszadeh 2021 <abbaszadeh.h@gmail.com>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/respinar/contao-links-bundle
 */

use Respinar\ContaoLinksBundle\Model\LinksModel;
use Respinar\ContaoLinksBundle\Model\LinksCategoryModel;

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['links'] = array(
    'tables' => array('tl_links_category','tl_links')
);

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_links'] = LinksModel::class;
$GLOBALS['TL_MODELS']['tl_links_category'] = LinksCategoryModel::class;
