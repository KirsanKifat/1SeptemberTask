<?php


namespace App\Form\Type;


use App\Entity\YoutubeVideo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class YoutubeVideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('videoId', null, [
                    'label' => 'label.youtube.id',
                    'required' => false,
                    ])
            ->add('name', null, [
                'label' => 'label.youtube.name',
                'required' => false,
            ])
            ->add('description', null, [
                'label' => 'label.youtube.description',
                'required' => false,
            ])
            ->add('previewImage', null, [
                'label' => 'label.youtube.previewImage',
                'required' => false,
            ])
            ->add('active', null, [
                'constraints' => [
                    new IsTrue(['message' => 'post.youtube.inactively'])
                ],
                'block_prefix' => 'hidden',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'error_mapping' => [
                'active' => 'videoId',
            ],
            'data_class' => YoutubeVideo::class,
        ));
    }
}
