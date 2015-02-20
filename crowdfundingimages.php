<?php
/**
 * @package         CrowdFunding
 * @subpackage      Plugins
 * @author          Todor Iliev
 * @copyright       Copyright (C) 2015 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license         http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * CrowdFunding Images Plugin
 *
 * @package        CrowdFunding
 * @subpackage     Plugins
 */
class plgContentCrowdFundingImages extends JPlugin
{
    /**
     * @var Joomla\Registry\Registry
     */
    public $params;

    protected $pluginUri;
    protected $imagesUri;

    /**
     * @param string                     $context
     * @param   object                   $item
     * @param   Joomla\Registry\Registry $params
     * @param int                        $page
     *
     * @return null|string
     */
    public function onContentAfterDisplay($context, &$item, &$params, $page = 0)
    {
        $app = JFactory::getApplication();
        /** @var $app JApplicationSite * */

        if ($app->isAdmin()) {
            return null;
        }

        $doc = JFactory::getDocument();
        /**  @var $doc JDocumentHtml * */

        // Check document type
        $docType = $doc->getType();
        if (strcmp("html", $docType) != 0) {
            return null;
        }

        if (strcmp("com_crowdfunding.details", $context) != 0) {
            return null;
        }

        // Load language
        $this->loadLanguage();

        jimport("crowdfunding.images");
        $images = new CrowdFundingImages(JFactory::getDbo());
        $images->load($item->id);

        // If there are no images, return empty string.
        if (count($images) == 0) {
            return "";
        }

        $this->pluginUri = JURI::root() . "plugins/content/crowdfundingimages";

        // Get component params
        $this->imagesUri = CrowdFundingHelper::getImagesFolderUri($item->user_id);

        $html = array();

        $html[] = '<div class="clearfix"></div>';

        // Display title
        if ($this->params->get("display_title", 0)) {
            $html[] = '<h4>' . JText::_("PLG_CONTENT_CROWDFUNDINGIMAGES_GALLERY") . '</h4>';
        }

        // Load jQuery library
        if ($this->params->get("include_jquery", 0)) {
            JHtml::_("jquery.framework");
        }

        switch ($this->params->get("gallery")) {

            case "magnific":
                $html = $this->prepareMagnific($images, $html);
                break;

            default: // FancyBox
                $html = $this->prepareFancybox($images, $html, $item->id);
                break;
        }

        return implode("\n", $html);

    }

    private function prepareMagnific($images, $html)
    {

        $doc = JFactory::getDocument();
        /**  @var $doc JDocumentHtml * */

        $doc->addStyleSheet($this->pluginUri . "/css/magnific-popup.css");

        $doc->addScript($this->pluginUri . "/js/jquery.magnific-popup.min.js");

        $js = '
        jQuery(document).ready(function() {
        	jQuery("#js-extra-images-gallery").magnificPopup({
        		delegate: "a",
        		type: "image",
        		mainClass: "mfp-img-mobile",
        		gallery: {
        			enabled: true,
        			navigateByImgClick: true,
        			preload: [0,1], // Will preload 0 - before current, and 1 after the current image
                    arrowMarkup: \'<button type="button" class="mfp-arrow mfp-arrow-%dir%"></button>\',
        		}
        	});
        });
        ';

        $doc->addScriptDeclaration($js);

        $html[] = '<div id="js-extra-images-gallery">';
        foreach ($images as $image) {
            $html[] = '<a href="' . $this->imagesUri . '/' . $image->image . '">';
            $html[] = '<img src="' . $this->imagesUri . '/' . $image->thumb . '" />';
            $html[] = '</a>';
        }
        $html[] = '</div>';

        return $html;
    }

    private function prepareFancybox($images, $html, $projectId)
    {
        $doc = JFactory::getDocument();
        /**  @var $doc JDocumentHtml * */

        JHtml::_("crowdfunding.jquery_fancybox");

        $doc->addScript($this->pluginUri . "/js/jquery.easing.js");
        $doc->addScript($this->pluginUri . "/js/jquery.mousewheel.js");

        $js = '
        jQuery(document).ready(function() {
                
            jQuery("a.js-extra-images-gallery").fancybox({
        		"transitionIn"	:	"fade",
        		"transitionOut"	:	"fade",
        		"speedIn"		:	600, 
        		"speedOut"		:	200, 
        		"overlayShow"	:	true
        	});
                
        });
        ';

        $doc->addScriptDeclaration($js);

        foreach ($images as $image) {
            $html[] = '<a class="js-extra-images-gallery" rel="eigroup' . (int)$projectId . '" href="' . $this->imagesUri . '/' . $image->image . '">';
            $html[] = '<img src="' . $this->imagesUri . '/' . $image->thumb . '" />';
            $html[] = '</a>';
        }

        return $html;
    }
}
