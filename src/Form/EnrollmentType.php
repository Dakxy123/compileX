<?php

namespace App\Form;

use App\Entity\Enrollment;
use App\Entity\StudentProfile;
use App\Entity\Subject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnrollmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('studentProfile', EntityType::class, [
                'class' => StudentProfile::class,
                'choice_label' => function (StudentProfile $sp) {
                    $user = $sp->getUser();
                    $email = $user ? $user->getEmail() : ('Student #' . $sp->getId());
                    $course = $sp->getCourse();
                    $courseName = $course ? $course->getName() : null;

                    return $courseName ? sprintf('%s — %s', $email, $courseName) : $email;
                },
                'placeholder' => 'Select student',
                'label' => 'Student',
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => function (Subject $subject) {
                    $course = $subject->getCourse();
                    $courseName = $course ? $course->getName() : null;

                    if ($courseName) {
                        return sprintf('%s — %s (%s)', $subject->getCode(), $subject->getName(), $courseName);
                    }

                    return sprintf('%s — %s', $subject->getCode(), $subject->getName());
                },
                'placeholder' => 'Select subject',
                'label' => 'Subject',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Enrollment Status',
                'choices' => [
                    'Enrolled'  => 'Enrolled',
                    'Ongoing'   => 'Ongoing',
                    'Completed' => 'Completed',
                    'Dropped'   => 'Dropped',
                ],
            ])
            ->add('score', NumberType::class, [
                'label' => 'Score (0–100)',
                'required' => false,
                'html5' => true,
                'scale' => 2,
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.01,
                    'placeholder' => 'e.g. 95.50',
                ],
            ])
            ->add('grade', null, [
                'label' => 'Grade (e.g. A, B+, 1.0)',
                'required' => false,
                'attr' => [
                    'maxlength' => 10,
                    'placeholder' => 'e.g. A, B+, 1.0',
                ],
            ])
            ->add('remarks', TextareaType::class, [
                'label' => 'Remarks / Notes',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Optional notes about this enrollment / grade…',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enrollment::class,
        ]);
    }
}
