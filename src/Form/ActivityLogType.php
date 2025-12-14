<?php

namespace App\Form;

use App\Entity\ActivityLog;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityLogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class'        => User::class,
                'choice_label' => 'email',
                'placeholder'  => 'System / unknown',
                'required'     => false,
                'label'        => 'User',
            ])
            ->add('action', TextType::class, [
                'label' => 'Action',
            ])
            ->add('context', TextType::class, [
                'label'    => 'Context (optional)',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description (optional)',
                'required' => false,
                'attr'     => [
                    'rows' => 4,
                ],
            ])
            ->add('ipAddress', TextType::class, [
                'label'    => 'IP Address (optional)',
                'required' => false,
            ])
            ->add('createdAt', DateTimeType::class, [
                'label'  => 'Created at',
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActivityLog::class,
        ]);
    }
}
