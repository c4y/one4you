<?php

declare(strict_types=1);

namespace C4Y\One4you\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @Callback(table="tl_article", target="fields.bg_size.options")
 */
class ImageSizeOptionsListener
{
    private Security $security;
    private ImageSizes $imageSizes;

    public function __construct(Security $security, ImageSizes $imageSizes)
    {
        $this->security = $security;
        $this->imageSizes = $imageSizes;
    }

    public function __invoke(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return [];
        }

        return $this->imageSizes->getOptionsForUser($user);
    }
}
