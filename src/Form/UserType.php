<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserPicture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => ['class' => 'border-2 border-indigo-600 rounded-md bg-slate-900 p-4 placeholder:text-indigo-400']
            ])
            ->add('email', TextType::class, [
                'attr' => ['class' => 'border-2 border-indigo-600 rounded-md bg-slate-900 p-4 placeholder:text-indigo-400']
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'border-2 border-indigo-600 rounded-md bg-slate-900 p-4 placeholder:text-indigo-400']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}