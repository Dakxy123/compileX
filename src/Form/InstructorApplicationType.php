<?php

namespace App\Form;

use App\Entity\InstructorApplication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class InstructorApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', TextareaType::class, [
                'label' => 'Why do you want to become an instructor?',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Briefly describe your experience, background, and why you want to teach on CompileX.',
                    'class' => 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm',
                ],
            ])
            ->add('portfolioFile', FileType::class, [
                'label' => 'Portfolio / Supporting Document',
                'mapped' => false,
                'required' => false,
                'help' => 'Optional but recommended. Allowed: PDF, DOC, DOCX, PPT, PPTX, ZIP. Max 10MB.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InstructorApplication::class,
        ]);
    }
}
