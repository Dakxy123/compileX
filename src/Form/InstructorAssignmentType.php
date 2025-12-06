<?php

namespace App\Form;

use App\Entity\InstructorAssignment;
use App\Entity\User;
use App\Entity\Subject;
use App\Repository\UserRepository;
use App\Repository\SubjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstructorAssignmentType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository,
        private SubjectRepository $subjectRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('instructor', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Instructor',
                'placeholder' => 'Select instructor',
                'query_builder' => function (UserRepository $ur) {
                    return $ur->createQueryBuilder('u')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_INSTRUCTOR"%')
                        ->orderBy('u.email', 'ASC');
                },
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => function (Subject $subject) {
                    $code = $subject->getCode();
                    $name = $subject->getName();
                    return sprintf('%s — %s', $code, $name);
                },
                'label' => 'Subject',
                'placeholder' => 'Select subject',
                'query_builder' => function (SubjectRepository $sr) {
                    return $sr->createQueryBuilder('s')
                        ->leftJoin('s.course', 'c')
                        ->addSelect('c')
                        ->orderBy('c.name', 'ASC')
                        ->addOrderBy('s.code', 'ASC');
                },
            ])
            ->add('isPrimary', CheckboxType::class, [
                'label'    => 'Primary instructor for this subject',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InstructorAssignment::class,
        ]);
    }
}
