<?php
namespace Opencart\Admin\Controller\Common;

class TenantManage extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('common/tenant_manage');
        $this->load->model('common/regulatory');

        $company_id = (int)$this->request->get['company_id'];
        $company_info = $this->model_common_regulatory->getCompany($company_id);

        if (!$company_info) {
            $this->session->data['error'] = $this->language->get('error_company_not_found');
            $this->response->redirect($this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token']));
            return;
        }

        $this->document->setTitle(sprintf($this->language->get('heading_title'), $company_info['name']));
        
        $data['heading_title'] = sprintf($this->language->get('heading_title'), $company_info['name']);
        $data['company_name'] = $company_info['name'];

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
            'text' => $data['heading_title'],
            'href' => $this->url->link('common/tenant_manage', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id)
        ];

        $data['users'] = [];
        $users = $this->model_common_regulatory->getUsersForCompany($company_id);

        foreach ($users as $user) {
            $data['users'][] = [
                'user_id'    => $user['user_id'],
                'username'   => $user['username'],
                'name'       => $user['firstname'] . ' ' . $user['lastname'],
                'role'       => $this->language->get('' . $user['role']) ?? $user['role'],
                'status'     => $user['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'date_added' => date($this->language->get('date_format_short'), strtotime($user['date_added'])),
                'edit'       => $this->url->link('common/tenant_user_form', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id . '&user_id=' . $user['user_id'])
            ];
        }

        $data['add_user'] = $this->url->link('common/tenant_user_form', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id);
        $data['back'] = $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token']);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/tenant_manage', $data));
    }
} 