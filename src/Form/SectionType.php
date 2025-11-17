<?php

namespace App\Form;

use App\Entity\course;
use App\Entity\Section;
use App\Entity\subject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('section_code')
            ->add('year_level')
            ->add('isActive')
            ->add('created_at', null, [
                'widget' => 'single_text',
            ])
            ->add('updated_at')
            ->add('academic_year')
            ->add('course_program', EntityType::class, [
                'class' => course::class,
                'choice_label' => 'id',
            ])
            ->add('name', EntityType::class, [
                'class' => subject::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Section::class,
        ]);
    }
}
