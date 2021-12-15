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

namespace Respinar\ContaoLinksBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Date;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

use Respinar\ContaoLinksBundle\Model\LinksModel;
use Respinar\ContaoLinksBundle\Model\LinksCategoryModel;

use Contao\StringUtil;
use Contao\FrontendTemplate;


/**
 * Class LinkListingController
 *
 * @FrontendModule(LinkListingController::TYPE, category="miscellaneous", template="mod_links_listing")
 */
class LinkListingController extends AbstractFrontendModuleController
{
    public const TYPE = 'links_listing';

    /**
     * @var PageModel
     */
    protected $page;

    /**
     * This method extends the parent __invoke method,
     * its usage is usually not necessary
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Get the page model
        $this->page = $page;

        if ($this->page instanceof PageModel && $this->get('contao.routing.scope_matcher')->isFrontendRequest($request))
        {
            // If TL_MODE === 'FE'
            $this->page->loadDetails();
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * Lazyload some services
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['database_connection'] = Connection::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['security.helper'] = Security::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    /**
     * Generate the module
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        
        $template->empty = $GLOBALS['TL_LANG']['MSC']['emptyLinks'];

        $links_categories = StringUtil::deserialize($model->links_categories);

		$intTotal = LinksModel::countPublishedByPids($links_categories);

        //print_r($links_categories);

		if ($intTotal < 1)
		{
			//return;
		}

		$arrOptions = array();
		if ($model->links_sortBy)
		{
			switch ($model->links_sortBy)
			{
				case 'title_asc':
					$arrOptions['order'] = "title ASC";
					break;
				case 'title_desc':
					$arrOptions['order'] = "title DESC";
					break;
				case 'date_asc':
					$arrOptions['order'] = "tstamp ASC";
					break;
				case 'date_desc':
					$arrOptions['order'] = "tstamp DESC";
					break;
				case 'custom':
					$arrOptions['order'] = "sorting ASC";
					break;
			}
		}


		$objLinks = LinksModel::findPublishedByPids($links_categories,null,0,0,$arrOptions);        

		// No items found
		if ($objLinks !== null)
		{
			$template->links = $this->parseLinks($objLinks,$model->links_template);
		}

        return $template->getResponse();
    }


    /**
	 * Generate the module
	 */
	protected function parseLink($objLink, $link_template ,$strClass='', $intCount=0)
	{

		$objTemplate = new FrontendTemplate($link_template);

		$objTemplate->setData($objLink->row());

		$objTemplate->addImage = false;

		// Add an image
		if ($objLink->singleSRC != '')
		{
			$objModel = FilesModel::findByUuid($objLink->singleSRC);

			if ($objModel === null)
			{
				if (!Validator::isUuid($objLink->singleSRC))
				{
					$objTemplate->text = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
				}
			}
			elseif (is_file(TL_ROOT . '/' . $objModel->path))
			{
				// Do not override the field now that we have a model registry (see #6303)
				$arrLink = $objLink->row();

				// Override the default image size
				if ($this->imgSize != '')
				{
					$size = deserialize($this->imgSize);

					if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]))
					{
						$arrLink['size'] = $this->imgSize;
					}
				}

				$arrLink['singleSRC'] = $objModel->path;				
				$this->addImageToTemplate($objTemplate, $arrLink);
			}
		}		

		$objTemplate->class     = $strClass;
		$objTemplate->hrefclass = $objLink->class;
		$objTemplate->linkTitle = $objLink->linkTitle ? $objLink->linkTitle : $objLink->title;

		return $objTemplate->parse();

	}

	protected function parseLinks($objLinks, $link_template)
	{
		$limit = $objLinks->count();

		if ($limit < 1)
		{
			return array();
		}

		$count = 0;
		$arrLinks = array();

		while ($objLinks->next())
		{
			$arrLinks[] = $this->parseLink($objLinks, $link_template ,((++$count == 1) ? ' first' : '') . (($count == $limit) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even'), $count);
		}

		return $arrLinks;
	}

	/**
	 * Sort out protected archives
	 * @param array
	 * @return array
	 */
	protected function sortOutProtected($arrCategories)
	{
		if (BE_USER_LOGGED_IN || !is_array($arrCategories) || empty($arrCategories))
		{
			return $arrCategories;
		}

		$this->import('FrontendUser', 'User');
		$objCategory = LinksCategoryModel::findMultipleByIds($arrCategories);
		$arrCategories = array();

		if ($objCategory !== null)
		{
			while ($objCategory->next())
			{
				if ($objCategory->protected)
				{
					if (!FE_USER_LOGGED_IN)
					{
						continue;
					}

					$groups = deserialize($objCategory->groups);

					if (!is_array($groups) || empty($groups) || !count(array_intersect($groups, $this->User->groups)))
					{
						continue;
					}
				}

				$arrCategories[] = $objCategory->id;
			}
		}

		return $arrCategories;
	}
}
