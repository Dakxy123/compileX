<?php

namespace App\Form;

use App\Entity\CourseOffering;
use App\Entity\instructors;
use App\Entity\section;
use App\Entity\Subject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseOfferingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('term')
            ->add('capacity')
            ->add('status')
            ->add('academic_year')
            ->add('schedule')
            ->add('section', EntityType::class, [
                'class' => section::class,
                'choice_label' => 'id',
            ])
            ->add('instructor', EntityType::class, [
                'class' => instructors::class,
                'choice_label' => 'id',
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CourseOffering::class,
        ]);
    }
}
