<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;
use Zikula\Core\RouteUrl;
use Zikula\SearchModule\Entity\SearchResultEntity;
use Zikula\SearchModule\SearchableInterface;

class SearchHelper implements SearchableInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface $session
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ) {
        $this->entityManager = $entityManager;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function amendForm(FormBuilderInterface $form)
    {
        // not needed because `active` child object is already added and that is all that is needed.
    }

    /**
     * Get the search results
     *
     * @param array $words array of words to search for
     * @param string $searchType AND|OR|EXACT
     * @param array|null $modVars module form vars passed though
     * @return array
     */
    public function getResults(array $words, $searchType = 'AND', $modVars = null)
    {
        // this is an 'eager' search - it doesn't compensate for search type indicated in search UI
        $results = $this->entityManager->getRepository(CoreReleaseEntity::class)->getByFragment($words);

        $records = array();
        foreach ($results as $result) {
            /** @var $result CoreReleaseEntity */
            $result = new SearchResultEntity();
            $result->setTitle($result->getName())
                ->setText($result->getDescription())
                ->setModule('ZikulaCoreManagerModule')
                ->setCreated(new \DateTime())
                ->setUrl(RouteUrl::createFromRoute('zikulacoremanagermodule_user_viewcorereleases'))
                ->setSesid($this->session->getId());
            $records[] = $result;
        }

        return $records;
    }

    public function getErrors()
    {
        return [];
    }
}
