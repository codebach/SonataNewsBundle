<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\NewsBundle\Block;

use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractAdminBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Form\Type\ImmutableArrayType;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\CoreBundle\Model\Metadata;
use Sonata\NewsBundle\Model\PostManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class RecentPostsBlockService extends AbstractAdminBlockService
{
    /**
     * @var PostManagerInterface
     */
    protected $manager;

    /**
     * @var Pool
     */
    private $adminPool;

    /**
     * @param string           $name
     * @param EngineInterface  $templating
     * @param ManagerInterface $postManager
     * @param Pool             $adminPool
     */
    public function __construct($name, EngineInterface $templating, ManagerInterface $postManager, Pool $adminPool = null)
    {
        if (!$postManager instanceof PostManagerInterface) {
            @trigger_error(
                'Calling the '.__METHOD__.' method with a Sonata\CoreBundle\Model\ManagerInterface is deprecated'
                .' since version 2.4 and will be removed in 3.0.'
                .' Use the new signature with a Sonata\NewsBundle\Model\PostManagerInterface instead.',
                E_USER_DEPRECATED
            );
        }

        $this->manager = $postManager;
        $this->adminPool = $adminPool;

        parent::__construct($name, $templating);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $criteria = [
            'mode' => $blockContext->getSetting('mode'),
        ];

        $parameters = [
            'context' => $blockContext,
            'settings' => $blockContext->getSettings(),
            'block' => $blockContext->getBlock(),
            'pager' => $this->manager->getPager($criteria, 1, $blockContext->getSetting('number')),
            'admin_pool' => $this->adminPool,
        ];

        if ('admin' === $blockContext->getSetting('mode')) {
            return $this->renderPrivateResponse($blockContext->getTemplate(), $parameters, $response);
        }

        return $this->renderResponse($blockContext->getTemplate(), $parameters, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('settings', ImmutableArrayType::class, [
            'keys' => [
                ['number', IntegerType::class, [
                    'required' => true,
                    'label' => 'form.label_number',
                ]],
                ['title', TextType::class, [
                    'required' => false,
                    'label' => 'form.label_title',
                ]],
                ['mode', ChoiceType::class, [
                    'choices' => [
                        'public' => 'form.label_mode_public',
                        'admin' => 'form.label_mode_admin',
                    ],
                    'label' => 'form.label_mode',
                ]],
            ],
            'translation_domain' => 'SonataNewsBundle',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'number' => 5,
            'mode' => 'public',
            'title' => 'Recent Posts',
            'template' => 'SonataNewsBundle:Block:recent_posts.html.twig',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (!is_null($code) ? $code : $this->getName()), false, 'SonataNewsBundle', [
            'class' => 'fa fa-pencil',
        ]);
    }
}
