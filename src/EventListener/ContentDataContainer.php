<?php

namespace C4Y\One4you\EventListener;

use Contao\DataContainer;
use Contao\ContentModel;
use Contao\StringUtil;
use Contao\Input;
use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

class ContentDataContainer
{
    public function __construct()
    {
    }
    /**
     * Adjust the palettes
     */
    public function adjustPalettes(DataContainer $dc)
    {
        if (!$dc->id || null === ($element = ContentModel::findById($dc->id))) {
            return;
        }

        $parent = ContentModel::findOneBy(['sorting < ? AND pid = ? AND invisible=""'], [$element->sorting, $element->pid]);

        if ($parent == null || \in_array($element->type, ['rsce_wrapper_start', 'rsce_wrapper_stop', 'rsce_wrapper_end', 'c4y_wrapper', 'c4y_wrapper_stop'], true)) {
            return;
        }

        foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $k => $v) {
            if (is_array($v)) {
                continue;
            }

            $GLOBALS['TL_DCA']['tl_content']['palettes'][$k] = str_replace(
                'protected;',
                'protected;{elementsFilter_legend},elementsFilter_filters;',
                $v
            );
        }
    }

    /**
     * Get the filters
     *
     * @return array
     */
    public function getFilters()
    {
        return [];
    }

}
