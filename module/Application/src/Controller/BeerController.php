<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class BeerController extends AbstractActionController
{
    public $tableGateway;
    public $cache;

    public function __construct($tableGateway, $cache)
    {
        $this->tableGateway = $tableGateway;
        $this->cache = $cache;
    }

    public function indexAction()
    {
        $key = 'CachedBeers';
        $beers = $this->cache->getItem($key, $success);
        if (! $success) {
            $beers = $this->tableGateway->select()->toArray();
            $this->cache->setItem($key, $beers);
        }

        return new ViewModel(['beers' => $beers]);
    }

    private function getForm()
    {
        $form = new \Application\Form\Login;
        foreach ($form->getElements() as $element) {
            if (! $element instanceof \Zend\Form\Element\Submit) {
                $element->setAttributes([
                    'class' => 'form-control'
                ]);
            }
        }
        return $form;
    }

    public function createAction()
    {
        $form = $this->getForm();

        $form->setAttribute('action', '/beer/create');
        $request = $this->getRequest();
         /* se a requisição é post os dados foram enviados via formulário*/
        if ($request->isPost()) {
            $beer = new \Application\Model\Beer;
            /* configura a validação do formulário com os filtros e validators da entidade*/
            $form->setInputFilter($beer->getInputFilter());
            /* preenche o formulário com os dados que o usuário digitou na tela*/
            $form->setData($request->getPost());
            /* faz a validação do formulário*/
            if ($form->isValid()) {
                /* pega os dados validados e filtrados */
                $data = $form->getData();
                unset($data['send']);
                /* salva a cerveja*/
                $this->tableGateway->insert($data);
                $this->cache->removeItem('CachedBeers');
                /* redireciona para a página inicial que mostra todas as cervejas*/
                return $this->redirect()->toUrl('/beer');
            }
        }
        $view = new ViewModel(['form' => $form]);
        $view->setTemplate('application/beer/save.phtml');

    return $view;

    }

    public function editAction()
    {
        /* configura o form */
        $form = $this->getForm();
        $form->get('send')->setAttribute('value', 'Edit');
        $form->setAttribute('action', '/beer/edit');
        /* adiciona o ID ao form */
        $form->add([
            'name' => 'id',
            'type'  => 'hidden',
        ]);
        $view = new ViewModel(['form' => $form]);
        $view->setTemplate('application/beer/save.phtml');

        $request = $this->getRequest();
         /* se a requisição é post os dados foram enviados via formulário*/
        if ($request->isPost()) {
            $beer = new \Application\Model\Beer;
            /* configura a validação do formulário com os filtros e validators da entidade*/
            $form->setInputFilter($beer->getInputFilter());
            /* preenche o formulário com os dados que o usuário digitou na tela*/
            $form->setData($request->getPost());
            /* faz a validação do formulário*/
            if (!$form->isValid()) {
                return $view;
            }
            /* pega os dados validados e filtrados */
            $data = $form->getData();
            unset($data['send']);
            /* salva a cerveja*/
            $this->tableGateway->update($data, 'id = '.$data['id']);
            $this->cache->removeItem('CachedBeers');
            /* redireciona para a página inicial que mostra todas as cervejas*/
            return $this->redirect()->toUrl('/beer');
        }

        /* Se não é post deve mostrar os dados */
        $id = (int) $this->params()->fromRoute('id',0);
        $beer = $this->tableGateway->select(['id' => $id])->toArray();
        if (count($beer) == 0) {
            throw new \Exception("Beer not found", 404);
        }

         /* preenche o formulário com os  dados do banco de dados */
        $form->get('id')->setValue($beer[0]['id']);
        $form->get('name')->setValue($beer[0]['name']);
        $form->get('style')->setValue($beer[0]['style']);
        $form->get('img')->setValue($beer[0]['img']);

        return $view;
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        $beer = $this->tableGateway->select(['id' => $id]);
        if (count($beer) == 0) {
            throw new \Exception("Beer not found", 404);
        }

        $this->tableGateway->delete(['id' => $id]);
        $this->cache->removeItem('CachedBeers');
        // return $this->redirect()->toUrl('/beer');
        return $this->redirect()->toRoute('beer');
    }
}
