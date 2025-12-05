<?php

namespace App\Form;

use App\Entity\Subject;
use App\Entity\Course;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Subject Name',
            ])
            ->add('code', TextType::class, [
                'label' => 'Subject Code',
            ])
            ->add('yearLevel', ChoiceType::class, [
                'label' => 'Year Level',
                'placeholder' => 'Select year level',
                'choices' => [
                    'Year 1' => 1,
                    'Year 2' => 2,
                    'Year 3' => 3,
                    'Year 4' => 4,
                    'Year 5' => 5,
                ],
            ])
            ->add('semester', ChoiceType::class, [
                'label' => 'Semester',
                'placeholder' => 'Select semester',
                'choices' => [
                    '1st Semester' => 1,
                    '2nd Semester' => 2,
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
            ])
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Course',
                'label' => 'Course',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subject::class,
        ]);
    }
}
