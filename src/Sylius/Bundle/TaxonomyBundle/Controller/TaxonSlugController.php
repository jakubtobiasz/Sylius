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

namespace Sylius\Bundle\TaxonomyBundle\Controller;

use Sylius\Component\Taxonomy\Generator\TaxonSlugGeneratorInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TaxonSlugController
{
    /**
     * @param RepositoryInterface<TaxonInterface> $taxonRepository
     * @param FactoryInterface<TaxonInterface> $taxonFactory
     */
    public function __construct(
        private TaxonSlugGeneratorInterface $taxonSlugGenerator,
        private RepositoryInterface $taxonRepository,
        private FactoryInterface $taxonFactory,
    ) {
    }

    public function generateAction(Request $request): Response
    {
        $name = (string) $request->query->get('name');
        if ('' === trim($name)) {
            throw new BadRequestException('Cannot generate slug without a name');
        }

        $locale = (string) $request->query->get('locale');

        /** @var TaxonInterface $taxon */
        $taxon = $this->taxonFactory->createNew();
        $taxon->setCurrentLocale($locale);
        $taxon->setFallbackLocale($locale);
        $taxon->setName($name);

        $taxon->setParent($this->getParentTaxon($request));

        return new JsonResponse([
            'slug' => $this->taxonSlugGenerator->generate($taxon, $locale),
        ]);
    }

    private function getParentTaxon(Request $request): ?TaxonInterface
    {
        $parentCode = $request->query->get('parentCode');

        return $this->taxonRepository->findOneBy(['code' => $parentCode]);
    }
}
