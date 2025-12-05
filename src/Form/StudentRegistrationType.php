<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StudentRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Email
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email is required.']),
                    new Assert\Email(['message' => 'Please enter a valid email address.']),
                ],
            ])

            // Password + confirm
            ->add('password', RepeatedType::class, [
                'type'            => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'first_options'   => [
                    'label' => 'Password',
                    'attr'  => ['autocomplete' => 'new-password'],
                ],
                'second_options'  => [
                    'label' => 'Confirm Password',
                    'attr'  => ['autocomplete' => 'new-password'],
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Password is required.']),
                    new Assert\Regex([
                        'pattern' => "/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/",
                        'message' => 'Password must be at least 8 characters long and contain both letters and numbers.',
                    ]),
                ],
            ])

            // Course
            ->add('course', EntityType::class, [
                'class'        => Course::class,
                'choice_label' => 'name',
                'label'        => 'Course',
                'placeholder'  => 'Select a course',
            ])

            // Year level
            ->add('yearLevel', ChoiceType::class, [
                'label'       => 'Year Level',
                'placeholder' => 'Select year',
                'choices'     => [
                    '1st Year'                        => 1,
                    '2nd Year'                        => 2,
                    '3rd Year'                        => 3,
                    '4th Year'                        => 4,
                    '5th Year (Irregular / Extended)' => 5,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Year level is required.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // No specific data_class; we handle data manually in controller
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
