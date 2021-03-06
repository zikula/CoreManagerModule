<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Entity\Repository;
use Doctrine\ORM\EntityRepository;

/**
 * Core Release repository class.
 */
class CoreReleaseRepository extends EntityRepository
{
    /**
     * Get Release objects based on searching the title for fragment provided.
     *
     * @param array $fragments
     * @return array|null
     */
    public function getByFragment(array $fragments)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('r')
            ->from('Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity', 'r');
        $or = $qb->expr()->orX();
        $i = 1;
        foreach ($fragments as $fragment) {
            $or->add($qb->expr()->like('r.name', '?' . $i));
            $qb->setParameter($i, '%' . $fragment . '%');
            $or->add($qb->expr()->like('r.semver', '?' . ($i + 1)));
            $qb->setParameter($i + 1, '%' . $fragment . '%');
            $i = $i + 2;
        }
        $qb->where($or);

        return $qb->getQuery()->getResult();
    }
}
