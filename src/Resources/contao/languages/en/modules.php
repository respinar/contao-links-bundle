<?php

declare(strict_types=1);

/*
 * This file is part of Contao Links Bundle.
 * 
 * (c) Hamid Abbaszadeh 2021 <abbaszadeh.h@gmail.com>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/respinar/contao-links-bundle
 */

use Respinar\ContaoLinksBundle\Controller\FrontendModule\LinkListingController;

/**
 * Backend modules
 */
$GLOBALS['TL_LANG']['MOD']['links'] = ['Links', 'Links manager'];

/**
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD'][LinkListingController::TYPE] = ['Links listing', 'List links in the website'];

