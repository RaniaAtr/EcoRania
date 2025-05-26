<?php

namespace App\Form;

use App\Dto\SearchActivityData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('minPrice', NumberType::class, [
                'required' => false,
                'label' => 'Prix minimum',
            ])
            ->add('maxPrice', NumberType::class, [
                'required' => false,
                'label' => 'Prix maximum',
            ])
            ->add('date', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date',
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'label' => 'Lieu',
            ])
            ->add('category', TextType::class, [ 
                'required' => false,
                'label' => 'Catégorie',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchActivityData::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return ''; // Pour éviter des noms comme search_activity[minPrice]
    }
}
