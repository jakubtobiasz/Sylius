<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\AdminBundle\Component\Navbar;

use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

final class NavbarNotifications
{
    /** @return array<array{text: string}> */
    #[ExposeInTemplate]
    public function getNotifications(): array
    {
        // TODO: Implement getNotifications() method.
        return [];
    }
}
