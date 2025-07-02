<?php
namespace Opencart\Admin\Controller\Common;

class TenantForm extends \Opencart\System\Engine\Controller {
    private $error = [];

    public function index(): void {
        $this->load->language('common/tenant_form');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getForm();
    }

    public function save(): void {
        $this->load->language('common/tenant_form');
        
        $json = [];

        if (!$this->user->hasPermission('modify', 'common/tenant_form')) {
            $json['error']['warning'] = $this->language->get('error_permission');
        }

        if (!$this->validateForm()) {
            $json['error'] = $this->error;
        }

        if (!$json) {
            $this->load->model('common/tenant');
            
            if (!empty($this->request->get['company_id'])) {
                $this->model_common_tenant->editCompany($this->request->get['company_id'], $this->request->post);
            }
            
            $json['success'] = $this->language->get('text_success');
            // Add redirect to the list page after successful save
            $json['redirect'] = $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token'], true);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
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
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
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
        
        $company_id_param = isset($this->request->get['company_id']) ? '&company_id=' . $this->request->get['company_id'] : '';

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('common/tenant_form', 'user_token=' . $this->session->data['user_token'] . $company_id_param)
        ];
        
        $data['save'] = $this->url->link('common/tenant_form|save', 'user_token=' . $this->session->data['user_token'] . $company_id_param);
        $data['back'] = $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token']);

        $company_info = null;
        if (isset($this->request->get['company_id'])) {
            $this->load->model('common/tenant');
            $company_info = $this->model_common_tenant->getCompany($this->request->get['company_id']);
        }

        $fields = ['name', 'business_type', 'owner_name', 'email', 'address', 'contact'];
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($company_info)) {
                $data[$field] = $company_info[$field];
            } else {
                $data[$field] = '';
            }
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

        if ((mb_strlen($this->request->post['name']) < 3) || (mb_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        return !$this->error;
    }
}