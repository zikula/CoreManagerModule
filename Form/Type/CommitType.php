<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Module\CoreManagerModule\Helper\ProgressDataStorageHelper;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;

class CommitType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $commits;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        TranslatorInterface $translator,
        GitHubApiWrapper $api,
        ProgressDataStorageHelper $storageHelper
    ) {
        $this->translator = $translator;
        $branch = $storageHelper->getData()['branch'];
        $this->commits = $api->getLastNCommitsOfBranch('core', $branch, 10);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $commits = [];
        foreach ($this->commits as $commit) {
            $commits[substr($commit['sha'], 0, 8) . ' - ' . substr($commit['commit']['message'], 0, 80)] = $commit['sha'];
        }
        $builder
            ->add('commit', ChoiceType::class, [
                'label' => $this->translator->__('Commit'),
                'choices' => $commits,
                'choices_as_values' => true
            ])
            ->add('next', SubmitType::class, [
                'label' => $this->translator->__('Next'),
                'icon' => 'fa-angle-double-right',
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])
        ;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'commit';
    }
}
