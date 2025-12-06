<?php

namespace App\Form;

use App\Entity\Module;
use App\Entity\Course;
use App\Entity\Subject;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModuleType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Module Name',
            ])
            ->add('code', TextType::class, [
                'label' => 'Module Code',
            ])
            ->add('course', EntityType::class, [
                'class'        => Course::class,
                'choice_label' => 'name',
                'label'        => 'Course',
                'placeholder'  => 'Select a course',
            ])
            ->add('subject', EntityType::class, [
                'class'        => Subject::class,
                'choice_label' => function (Subject $subject) {
                    $courseName = $subject->getCourse() ? $subject->getCourse()->getName() : 'No course';
                    return sprintf('%s — %s', $courseName, $subject->getName());
                },
                'label'       => 'Subject',
                'placeholder' => 'Select a subject',
            ])
            ->add('instructor', EntityType::class, [
                'class'        => User::class,
                'choice_label' => 'email',
                'label'        => 'Instructor (optional)',
                'required'     => false,
                'placeholder'  => 'Select instructor',
                'query_builder' => function () {
                    // Only users with ROLE_INSTRUCTOR
                    return $this->userRepository->createQueryBuilder('u')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_INSTRUCTOR"%')
                        ->orderBy('u.email', 'ASC');
                },
            ])
            ->add('yearLevel', IntegerType::class, [
                'label' => 'Year Level',
            ])
            ->add('semester', ChoiceType::class, [
                'label'   => 'Semester',
                'choices' => [
                    '1st Semester' => 1,
                    '2nd Semester' => 2,
                ],
            ])
            ->add('schedule', TextType::class, [
                'label'    => 'Schedule (optional)',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'e.g. MWF 1:00–2:00 PM, Room 101',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Status',
                'choices' => [
                    'Active' => 'Active',
                    'Closed' => 'Closed',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Module::class,
        ]);
    }
}
