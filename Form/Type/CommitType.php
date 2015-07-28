<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use vierbergenlars\SemVer\version;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;

class CommitType extends AbstractType
{
    /**
     * @var array
     */
    private $commits;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $commits)
    {
        $this->commits = $commits;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $commits = [];
        foreach ($this->commits as $commit) {
            $commits[$commit['sha']] = substr($commit['sha'], 0, 8) . " - " . $commit['commit']['message'];
        }
        $builder->add('commit', 'choice', [
            'label' => __('Commit', 'ZikulaCoreManagerModule'),
            'label_attr' => ['class' => 'col-sm-3'],
            'choice_list' => new ChoiceList(array_keys($commits), array_values($commits)),
        ])->add('next', 'submit', [
            'label' => __('Next', 'ZikulaCoreManagerModule'),
        ]);
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
