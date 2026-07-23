<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Company;
use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ReviewType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'form.company_name',
                'mapped' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
                'attr' => [
                    'autocomplete' => 'off',
                ],
                'autocomplete' => true,
                'autocomplete_url' => $this->urlGenerator->generate('company_autocomplete'),
                'tom_select_options' => [
                    'create' => true,
                    'maxItems' => 1,
                ],
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'form.rating',
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                ],
                'expanded' => true,
                'placeholder' => false,
            ])
            ->add('reviewText', TextareaType::class, [
                'label' => 'form.review_text',
                'empty_data' => '',
            ])
            ->add('authorEmail', EmailType::class, [
                'label' => 'form.author_email',
                'empty_data' => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
            'empty_data' => static fn (): Review => new Review(new Company('placeholder'), 1, 'placeholder', 'placeholder@example.com'),
        ]);
    }
}
