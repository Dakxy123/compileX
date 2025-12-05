<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\StudentProfile;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'placeholder' => 'Select User',
                'label' => 'User (Account)',
            ])
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Course',
                'label' => 'Course',
            ])
            ->add('yearLevel', ChoiceType::class, [
                'label' => 'Year Level',
                'placeholder' => 'Select year level',
                'choices' => [
                    'Year 1'   => 1,
                    'Year 2'   => 2,
                    'Year 3'   => 3,
                    'Year 4'   => 4,
                    'Year 5'   => 5,
                    'Year 6+'  => 6, // "extended" students (6 years or more)
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'placeholder' => 'Select status',
                'choices' => [
                    'Ongoing'   => 'Ongoing',
                    'On Leave'  => 'On Leave',
                    'Completed' => 'Completed',
                    'Dropped'   => 'Dropped',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StudentProfile::class,
        ]);
    }
}
