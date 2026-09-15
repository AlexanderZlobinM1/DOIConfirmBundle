<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DOIConfirmBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Mautic\EmailBundle\Form\Type\EmailSendType as MauticEmailSendType;
use Mautic\LeadBundle\Form\Type\TagType;
use Mautic\LeadBundle\Form\Type\LeadListType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Class EmailSendType.
 */
class EmailSendType extends AbstractType
{
    private RouterInterface $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;    

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->translator = $translator;

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!empty($options['update_select'])) {
            (new MauticEmailSendType($this->router))->buildForm($builder, $options);

            $builder->add(
                'add_campaign_doi_success_tags',
                TagType::class,
                [
                    'label' => 'jw.mautic.lead.tags.add_campaign_doi_success_tags',
                    'attr'  => [
                        'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                        'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                        'data-allow-add'       => 'true',
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ],
                    'data'            => (isset($options['data']['add_campaign_doi_success_tags'])) ? $options['data']['add_campaign_doi_success_tags'] : null,
                    'add_transformer' => true,
                ]
            );
              
            $builder->add(
                'remove_tags_doi_success_tags',
                TagType::class,
                [
                    'label' => 'jw.remove_tags_doi_success_tags',
                    'attr'  => [
                        'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                        'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                        'data-allow-add'       => 'true',
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ],
                    'data'            => (isset($options['data']['remove_tags_doi_success_tags'])) ? $options['data']['remove_tags_doi_success_tags'] : null,
                    'add_transformer' => true,
                ]
            );          
            
            $builder->add('add_campaign_doi_success_lists', LeadListType::class, [
                'label'      => 'jw.add_campaign_doi_success_lists',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple' => true,
                'expanded' => false,
            ]);
    
            $builder->add('remove_campaign_doi_success_lists', LeadListType::class, [
                'label'      => 'jw.remove_campaign_doi_success_lists',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple' => true,
                'expanded' => false,
            ]);            
            
            $builder->add(
                'post_url',
                UrlType::class,
                [
                    'label'      => 'jw.mautic.form.action.redirect_url',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'preaddon' => 'fa fa-globe',
                    ],
                    'constraints' => [
                        new NotBlank(
                            [
                                'message' => 'mautic.core.value.required',
                            ]
                        ),
                        new Url(
                            [
                                'message' => 'mautic.core.valid_url_required',
                            ]
                        ),
                    ],
                ]
            );   
            
            $builder->add(
                'lead_field_update',
                TextType::class,
                [
                    'label'      => 'jw.mautic.form.action.lead_field_update',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control'
                    ],
                    'constraints' => [
                    ],
                ]
            );
            
            $builder->add(
                'lead_field_update_before',
                TextType::class,
                [
                    'label'      => 'jw.mautic.form.action.lead_field_update_before',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control'
                    ],
                    'constraints' => [
                    ],
                ]
            );   
            
            $builder->add(
                'alternative_email_field',
                TextType::class,
                [
                    'label'      => 'jw.mautic.form.action.alternative_email_field',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control'
                    ],
                    'constraints' => [
                    ],
                ]
            );

            $builder->add(
                'send_owner_email',
                CheckboxType::class,
                [
                    'label'    => 'jw.mautic.email.form.action.sendemail.owner.after_doi',
                    'required' => false,
                    'data'     => !empty($options['data']['send_owner_email']),
                ]
            );

            $builder->add(
                'owner_email',
                OwnerEmailType::class,
                [
                    'data'     => $options['data']['owner_email'] ?? [],
                    'required' => false,
                ]
            );
    
           
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'with_email_types' => false,
            ]
        );

        $resolver->setDefined(['update_select', 'with_email_types']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'jw.mautic.form.type.jw_emailsend_list';
    }
}
