<?php
namespace Opencart\Admin\Controller\Common;
class TenantList extends \Opencart\System\Engine\Controller {
    private array $error = [];

    public function index(): void {
        $this->load->language('common/tenant_list');
        
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('common/regulatory');

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['add'] = $this->url->link('common/tenant_add', 'user_token=' . $this->session->data['user_token']);
        $data['delete'] = $this->url->link('common/tenant_list|delete', 'user_token=' . $this->session->data['user_token']);

        $data['companies'] = [];
        
        $results = $this->model_common_regulatory->getCompanies();

        foreach ($results as $result) {
            $data['companies'][] = [
                'company_id'  => $result['company_id'],
                'name'        => $result['name'],
                'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'edit'        => $this->url->link('common/tenant_form', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $result['company_id'])
            ];
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/tenant_list', $data));
    }

    public function delete(): void {
        $this->load->language('common/tenant_list');
        $this->load->model('common/regulatory');

        $json = [];

        if (!$this->validateDelete()) {
            $json['error'] = $this->error['warning'];
        } else {
            if (isset($this->request->post['selected'])) {
                foreach ($this->request->post['selected'] as $company_id) {
                    $this->model_common_regulatory->deleteCompany($company_id);
                }
            }
            
            $json['success'] = $this->language->get('text_success');
            $json['redirect'] = $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token'], true);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateDelete(): bool {
        if (!$this->user->hasPermission('modify', 'common/tenant_list')) {
            $this->error['warning'] = $this->language->get('error_permission');
        } elseif (!isset($this->request->post['selected'])) {
            $this->error['warning'] = $this->language->get('error_selection');
        }
        
        return !$this->error;
    }
} 