<?php

namespace MauticPlugin\DOIConfirmBundle\Form\Type;

use Mautic\EmailBundle\Form\Type\EmailSendType as MauticEmailSendType;
use Mautic\UserBundle\Form\Type\UserListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnerEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'useremail',
            MauticEmailSendType::class,
            [
                'label' => 'mautic.email.emails',
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.choose.emails_descr',
                    'email'   => $options['data']['useremail']['email'] ?? null,
                ],
                'required'      => false,
                'update_select' => 'formaction_properties_owner_email_useremail_email',
            ]
        );

        $builder->add(
            'user_id',
            UserListType::class,
            [
                'label'      => 'mautic.email.form.users',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.help.autocomplete',
                ],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'    => false,
            'required' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'jw_doi_owner_email';
    }
}
