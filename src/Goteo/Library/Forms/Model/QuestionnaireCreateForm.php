<?php

/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Library\Forms\Model;

use Goteo\Library\Forms\FormProcessorInterface;
use Symfony\Component\Form\FormInterface;
use Goteo\Library\Forms\AbstractFormProcessor;
use Goteo\Library\Text;
use Goteo\Library\Forms\FormModelException;
use Goteo\Model\Questionnaire;
use Goteo\Model\Questionnaire\Answers;
use Symfony\Component\Validator\Constraints;
use Goteo\Model\Contract\Document;

class QuestionnaireCreateForm extends AbstractFormProcessor implements FormProcessorInterface
{

    public function getConstraints($field)
    {
        $constraints = [];
        if($this->getFullValidation()) {
            // $constraints[] = new Constraints\NotBlank();
        }
        return $constraints;
    }

    public function delQuestion($id)
    {

        $this->getBuilder()
            ->remove("{$id}_typeofquestion")
            ->remove("{$id}_required")
            ->remove("{$id}_question")
            ->remove("{$id}_remove");
    }

    public function addQuestion($question)
    {

        $config = $question->vars;
        
        if ($config->attr) { $config->attr = (array) $config->attr;
        }
        if ($config->type == "dropfiles") {
            $config->url = '/api/matcher/' . $question->matcher . '/project/' . $this->model->project_id . '/documents';
            $config->constraints = $this->getConstraints('docs');
        }
        $this->getBuilder()
            ->add(
                $question->id . '_typeofquestion', 'choice', [
                'label' => Text::get('questionnaire-type-of-question'),
                'choices' => Questionnaire::getTypes(),
                'data' => $config->type
                ]
            )
            ->add(
                $question->id . '_required', 'boolean', [
                'label' => Text::get('questionnaire-required'),
                'data' => $config->vars->required ? true : false,
                'required' => false
                ]
            )
            ->add(
                $question->id . '_question', 'textarea', [
                'label' => Text::get('questionnaire-text'),
                'data' => $question->title,
                ]
            )
            ->add(
                $question->id . "_remove", 'submit', [
                'label' => Text::get('regular-delete'),
                'icon_class' => 'fa fa-trash',
                'span' => 'hidden-xs',
                'attr' => [
                    'class' => 'pull-right btn btn-default remove-question',
                    'data-confirm' => Text::get('project-remove-reward-confirm')
                    ]
                ]
            );

    }
    
    public function createForm()
    {
        $questionnaire = $this->getModel();
        $builder = $this->getBuilder();

        foreach((array) $questionnaire->questions as $question) {
            $this->addQuestion($question);
        }

        return $this;
    }

}
