<?php

namespace Goteo\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Goteo\Application\Session,
    Goteo\Application\Config,
    Goteo\Library\Text,
    Goteo\Application,
    Goteo\Library\Feed,
    Goteo\Library\PDFContract,
    Goteo\Library\Mail,
    Goteo\Model;

class ContractController extends \Goteo\Core\Controller {

    /**
     * La vista por defecto del contrato ES el pdf
     *
     * @param string(50) $id del proyecto
     * @return raw   Pdf
     */
    public function indexAction($id) {

        $contract = Model\Contract::get($id); // datos del contrato
        if(!Session::isLogged()) {
            Application\Message::error("You're not allowed to access here! [$id]");
            // no lo puede ver y punto
            return $this->redirect('/');
        }
        $user = Session::getUser();

        // solamente se puede ver si....
        // Es un admin, es el impulsor
        //
        $grant = false;
        if (isset($contract) && $contract->project_owner == $user->id)  // es el dueño del proyecto
            $grant = true;
        elseif($user->hasRoleInNode(Config::get('node'), ['admin', 'superadmin', 'root']))
            $grant = true;

        // si lo puede ver
        if ($grant) {

            $pdf_name = 'contrato-goteo_'.$contract->fullnum . '.pdf';
            // $filename = Model\Contract\Document::$dir . $contract->project . '/' . $pdf_name;

            // fecha
            \setlocale(\LC_TIME, 'esp');
            $contract->date = strftime('%e de %B de %Y', strtotime($contract->date));

            // texto para "... en adelante EL IMPULSOR"
            switch ($contract->type) {
                case 0: // persona
                    //el responsable o la entidad %entity_name%
                    $contract->el_impulsor = "La persona responsable, {$contract->name}";
                    break;
                case 1: // asociación
                    $contract->el_impulsor = "La entidad {$contract->entity_name}";
                    break;
                case 2: // entidad
                    $contract->el_impulsor = "La entidad {$contract->entity_name}";

                    break;
            }


            // para generarlo
            $pdf = new PDFContract;
            $pdf->setParameters($contract);
            $pdf->generate();

            // borrador
            if ($contract->draft) {
                $pdf->Output();
                die;
            } else {
                // y se lo damos para descargar
                $pdf->Output($pdf_name, 'D');

                Model\Contract::setStatus($id, array('pdf'=>1));

                die;
            }

        } else {
            // no lo puede ver y punto
            return $this->redirect('/');
        }
    }

    /*
     * Datos en bruto de contrato
     */
    public function rawAction ($id) {
        $user = Session::getUser();
        if(!Session::isLogged()) {
            // no lo puede ver y punto
            return $this->redirect('/');
        }

        // Solo superadmin
        if ($user->hasRoleInNode(Config::get('node'), [ 'superadmin', 'root'])) {
            return $this->redirect('/');
        }

        $contract = Model\Contract::get($id);
        // temporal para testeo, si no tiene contrato lo creamos
        if (!$contract) {
            if (Model\Contract::create($id)) {
                $contract = Model\Contract::get($id);
            } else {
                Application\Message::error('fallo al crear el registro de contrato');
                return $this->redirect('/manage/projects');
            }
        }
        return new Response(\trace($contract));
    }

    // los contratos no se pueden eliminar... ¿o sí?
    public function deleteAction ($id) {
        return $this->redirect('/');
    }

    //Aunque no esté en estado edición un admin siempre podrá editar los datos de contrato
    public function editAction ($id, $step = 'promoter', Request $request) {
        $contract = Model\Contract::get($id);

        $user = Session::getUser();
        if(!Session::isLogged()) {
            // no lo puede ver y punto
            return $this->redirect('/');
        }

        // aunque pueda acceder edit, no lo puede editar si los datos ya se han dado por cerrados
        if ($contract->project_user != $user->id // no es su proyecto
            && $contract->status->owner // cerrado por
            && ! $user->hasRoleInNode(Config::get('node'), ['manager', 'superadmin', 'root']) // no es un gestor ni superadmin
            ) {
            // le mostramos el pdf
            return $this->redirect('/contract/' . $id);
        }

        // checkeamos errores
        $contract->check();

        // todos los pasos, entrando en datos del promotor por defecto
        $steps = array(
            'promoter' => array(
                'name' => Text::get('contract-step-promoter'),
                'title' => 'Title Promotor',
                'class' => 'first-on on',
                'num' => ''
            ),
            'entity' => array(
                'name' => Text::get('contract-step-entity'),
                'title' => 'Title Entidad',
                'class' => 'on-on on',
                'num' => ''
            ),
            'accounts' => array(
                'name' => Text::get('contract-step-accounts'),
                'title' => 'Title Cuentas',
                'class' => 'on-on on',
                'num' => ''
            ),
            'documents' => array(
                'name' => Text::get('contract-step-documents'),
                'title' => 'Title Documentos',
                'class' => 'on-off on',
                'num' => ''
            ),
            'final' => array(
                'name' => Text::get('contract-step-final'),
                'title' => 'Title Revisión',
                'class' => 'off-last off',
                'num' => ''
            )
        );


        if ($request->isMethod('POST')) {
            $errors = array(); // errores al procesar, no son errores en los datos del proyecto
            foreach ($steps as $id => $data) {
                if (call_user_func_array(array($this, 'process_' . $id), array($contract, $request, &$errors))) {
                    // ok
                }
            }



            // guardamos los datos que hemos tratado y los errores de los datos
            $contract->save($errors);

            // checkeamos de nuevo
            $contract->check();

            if (!empty($errors)) {
                Application\Message::error(implode('<br />', $errors));
            }

            // redirect to the next step if not a ajax call:
            if(!$request->isXmlHttpRequest()) {
                // Next step if specified in POST
                $next = $step;
                if(array_key_exists($request->request->get('step'), $steps) && $request->request->get('step') !== $step) {
                    $next = $request->request->get('step');
                }
                return $this->redirect('/contract/edit/' . $contract->project . '/' . $next);
            }
        }

        if (!$contract->status->owner) {
            Application\Message::info(Text::get('form-ajax-info'));
        }

        // variables para la vista
        $viewData = array(
            'contract' => $contract,
            'steps' => $steps,
            'step' => $step
        );

        return $this->viewResponse('contract/steps/' . $step, $viewData);
    }

    /*
     * Promotor
     */
    private function process_promoter($contract, Request $request, &$errors = array()) {
        if (!$request->request->has('process_promoter')) {
            return false;
        }

        // campos que guarda este paso. Verificar luego.
        $fields = array(
            'type',
            'name',
            'nif',
            'birthdate',
            'address',
            'location',
            'region',
            'zipcode',
            'country'
        );

        foreach ($fields as $field) {
            $contract->$field = $request->request->get($field);
        }

        return true;
    }

    /*
     * Entidad
     */
    private function process_entity($contract, Request $request, &$errors = array()) {
        if (!$request->request->has('process_entity')) {
            return false;
        }

        // campos que guarda este paso. Verificar luego.
        $fields = array(
            'office',
            'entity_name',
            'entity_cif',
            'entity_address',
            'entity_location',
            'entity_region',
            'entity_zipcode',
            'entity_country',
            'reg_name',
            'reg_date',
            'reg_number',
            'reg_id',
            'reg_idname',
            'reg_idloc'
        );

        foreach ($fields as $field) {
            $contract->$field = $request->request->get($field);
        }

        return true;
    }

    /*
     * Cuentas
     */
    private function process_accounts($contract, Request $request, &$errors = array()) {
        if (!$request->request->has('process_accounts')) {
            return false;
        }

        // también en la tabla de cuentas
        $accounts = Model\Project\Account::get($contract->project);

        $fields = array(
            'bank',
            'bank_owner',
            // 'paypal', no modificamos la cuenta paypal
            'paypal_owner'
        );

        foreach ($fields as $field) {
            $contract->$field = $request->request->get($field);
            $accounts->$field = $request->request->get($field);
        }

        $accounts->save($errors);

        return true;
    }

    /*
     * Documentación
     */
    private function process_documents($contract, Request $request, &$errors = array()) {
        if (!$request->request->has('process_documents')) {
            return false;
        }

        // tratar el que suben
        if(!empty($_FILES['doc_upload']['name'])) {
            // procesarlo aqui con el submodelo Contract\Doc
            $newdoc = new Model\Contract\Document($_FILES['doc_upload']);
            $newdoc->contract = $contract->project;

            if ($newdoc->save($errors)) {
                $contract->docs[] = $newdoc;
            }
        }

        // tratar el que quitan
        foreach ($contract->docs as $key=>$doc) {
            if ($request->request->get("docs-{$doc->id}-remove")) {
                if ($doc->remove($errors)) {
                    unset($contract->docs[$key]);
                }
            }
        }

        // y los campos de descripción
        $fields = array(
            'project_description',
            'project_invest',
            'project_return'
        );

        foreach ($fields as $field) {
            if ($request->request->has($field)) {
                $contract->$field = $request->request->get($field);
            }
        }



        return true;
    }

    /*
     * Paso final, revisión y cierre
     */
    private function process_final($contract, Request $request, &$errors = array()) {
        if (!$request->request->has('process_final')) {
            return false;
        }

        // este paso solo cambia el campo de cerrado (y flag de cerrado por impulsor)
        if ($request->request->has('finish')) {
            // marcar en el registro de gestión, "datos de contrato" cerrados
            if (Model\Contract::setStatus($contract->project, array('owner'=>true))) {
                Application\Message::info('El formulario de contrato ha sido cerrado para revisión');

                // Evento Feed
                $log = new Feed();
                $log->setTarget($contract->project);
                $log->populate('Impulsor da por cerrados los datos del contrato (dashboard)', '/admin/projects', \vsprintf('%s ha cerrado los datos del contrato del proyecto %s', array(
                            Feed::item('user', $_SESSION['user']->name, $_SESSION['user']->id),
                            Feed::item('project', $contract->project_name, $contract->project)
                        )));
                $log->doAdmin('user');
                unset($log);

                $contract->status = Model\Contract::getStatus($contract->project);

                // mail de aviso
                $mailHandler = new Mail();
                $mailHandler->to = (defined('GOTEO_MANAGER_MAIL')) ? \GOTEO_MANAGER_MAIL : \GOTEO_CONTACT_MAIL;
                $mailHandler->toName = 'Goteo.org';
                $mailHandler->subject = 'Han cerrado los datos del contrato de ' . $contract->project_name;
                $mailHandler->content = 'El formulario de contrato del proyecto proyecto '.$contract->project_name.' está listo para ser revisaro.
                    Gestionar: http://goteo.org/manage/projects?filtered=yes&name=&proj_name='.substr($contract->project_name, 0, 10).'
                    Ver contrato: http://goteo.org/contract/'.$contract->project;
                $mailHandler->html = false;
                $mailHandler->template = null;
                $mailHandler->send();
                unset($mailHandler);

                return true;

            } else {
                Application\Message::error('Ha habido algún error al cerrar los datos de contrato');
                return false;
            }
        }

        return true;
    }

    //-------------------------------------------------------------
    // Hasta aquí los métodos privados para el tratamiento de datos
    //-------------------------------------------------------------
}
