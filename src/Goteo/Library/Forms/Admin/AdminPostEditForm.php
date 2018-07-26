<?php

/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Library\Forms\Admin;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\FormInterface;
use Goteo\Library\Forms\AbstractFormProcessor;
use Symfony\Component\Validator\Constraints;
use Goteo\Library\Text;
use Goteo\Model\User;
use Goteo\Library\Forms\Model\ProjectPostForm;
use Goteo\Library\Forms\FormModelException;

class AdminPostEditForm extends ProjectPostForm {

    public function createForm() {
        parent::createForm();
        $builder = $this->getBuilder();
        $options = $builder->getOptions();
        $post = $this->getModel();
        $data = $options['data'];


        // saving images will add that images to the gallery
        // let's show the gallery in the field with nice options
        // for removing and reorder it
        $builder->add('image', 'dropfiles', array(
            'required' => false,
            'data' => $data['gallery'],
            'label' => 'regular-images',
            'markdown_link' => 'text',
            'accepted_files' => 'image/jpeg,image/gif,image/png',
            'url' => '/api/blog/images',
            'constraints' => array(
                new Constraints\Count(array('max' => 20))
            )
        ));

        // Replace markdown by html editor if type
        if($post->type === 'html') {
            $builder->add('text', 'textarea', array(
                'label' => 'admin-title-text',
                'required' => false,
                'html_editor' => true
                // 'constraints' => array(new Constraints\NotBlank()),
            ));
        }

        // Add tags input
        $tags = implode(', ', array_keys($data['tags']));
        $builder->add('tags', 'tags', [
            'label' => 'admin-title-tags',
            'data' => $tags,
            'attr' => ['data-display-value' => 'tag', 'data-display-key' => 'tag'],
            'required' => false,
            'url' => '/api/blog/tags'
        ]);


        return $this;
    }

}
