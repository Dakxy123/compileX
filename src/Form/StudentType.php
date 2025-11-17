<?php

namespace App\Form;

use App\Entity\section;
use App\Entity\Student;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('student_id')
            ->add('fname')
            ->add('mname')
            ->add('lname')
            ->add('email')
            ->add('isActive')
            ->add('created_at', null, [
                'widget' => 'single_text',
            ])
            ->add('section', EntityType::class, [
                'class' => section::class,
                'choice_label' => 'id',
            ])
            ->add('student', EntityType::class, [
                'class' => Student::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Student::class,
        ]);
    }
}
