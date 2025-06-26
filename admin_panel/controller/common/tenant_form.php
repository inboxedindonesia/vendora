<?php
namespace Opencart\Admin\Controller\Common;

class TenantForm extends \Opencart\System\Engine\Controller {
    private $error = [];

    public function index(): void {
        $this->load->language('common/tenant_form');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('common/regulatory');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_common_regulatory->editCompany($this->request->get['company_id'], $this->request->post['name']);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token']));
        }

        $this->getForm();
    }

    protected function getForm(): void {
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_form'] = !isset($this->request->get['company_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
        }

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_tenants'),
            'href' => $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token'])
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('common/tenant_form', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $this->request->get['company_id'])
        ];
        
        $data['save'] = $this->url->link('common/tenant_form', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $this->request->get['company_id']);
        $data['back'] = $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token']);

        if (isset($this->request->get['company_id'])) {
            $company_info = $this->model_common_regulatory->getCompany($this->request->get['company_id']);
        }

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($company_info)) {
            $data['name'] = $company_info['name'];
        } else {
            $data['name'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/tenant_form', $data));
    }

    protected function validateForm(): bool {
        if (!$this->user->hasPermission('modify', 'common/tenant_form')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        return !$this->error;
    }
} 