<?php
namespace Opencart\Admin\Controller\Common;

class TenantUserForm extends \Opencart\System\Engine\Controller {
    private array $error = [];

    public function index(): void {
        $this->load->language('common/tenant_user_form');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('user/user');
        $this->load->model('common/regulatory');
        
        $company_id = (int)($this->request->get['company_id'] ?? 0);
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm($company_id)) {
            if (!isset($this->request->get['user_id'])) {
                $user_id = $this->model_user_user->addUser($this->request->post);
                $this->model_common_regulatory->assignUserToCompany($user_id, $company_id);
            } else {
                $user_id = (int)$this->request->get['user_id'];
                $this->model_user_user->editUser($user_id, $this->request->post);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('common/tenant_manage', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id));
        }
        
        $this->getForm();
    }
    
    protected function getForm(): void {
        $data['text_form'] = !isset($this->request->get['user_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
        $data['company_id'] = (int)($this->request->get['company_id'] ?? 0);
        $user_id = (int)($this->request->get['user_id'] ?? 0);

        // Errors
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_username'] = $this->error['username'] ?? '';
        $data['error_email'] = $this->error['email'] ?? '';
        $data['error_password'] = $this->error['password'] ?? '';
        $data['error_user_group_id'] = $this->error['user_group_id'] ?? '';

        // Breadcrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_tenants'), 'href' => $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token'])];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_manage'), 'href' => $this->url->link('common/tenant_manage', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $data['company_id'])];
        $data['breadcrumbs'][] = ['text' => $data['text_form'], 'href' => $this->url->link('common/tenant_user_form', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $data['company_id'] . ($user_id ? '&user_id=' . $user_id : ''))];

        // Actions
        $data['save'] = $this->url->link('common/tenant_user_form', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $data['company_id'] . ($user_id ? '&user_id=' . $user_id : ''));
        $data['back'] = $this->url->link('common/tenant_manage', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $data['company_id']);

        // Data for the form
        if ($user_id && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $user_info = $this->model_user_user->getUser($user_id);
        }

        foreach(['username', 'firstname', 'lastname', 'email'] as $field) {
            $data[$field] = $user_info[$field] ?? ($this->request->post[$field] ?? '');
        }
        
        $data['status'] = $user_info['status'] ?? ($this->request->post['status'] ?? 1);
        
        // Fetch user groups dynamically
        $this->load->model('common/tenant_user_group');
        $all_groups = $this->model_common_tenant_user_group->getUserGroups();

        // Filter out Super Admin and Admin Core
        $data['user_groups'] = array_filter($all_groups, function($group) {
            return !in_array($group['user_group_id'], [1, 4]);
        });

        if (!empty($user_info)) {
            $data['user_group_id'] = $user_info['user_group_id'];
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/tenant_user_form', $data));
    }
    
    private function validateForm(int $company_id): bool {
        $this->load->model('user/user');

        if ($company_id == 0) {
            $this->error['warning'] = 'Error: No company specified!';
        }

        if (empty($this->request->post['username']) || strlen($this->request->post['username']) < 3) {
            $this->error['username'] = 'Username must be at least 3 characters!';
        } elseif ($this->model_user_user->getUserByUsername($this->request->post['username'])) {
            $this->error['username'] = 'Username already exists!';
        }

        if (empty($this->request->post['email']) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error['email'] = 'Valid email is required!';
        } elseif ($this->model_user_user->getUserByEmail($this->request->post['email'])) {
            $this->error['email'] = 'Email already exists!';
        }

        if (!isset($this->request->get['user_id']) && (empty($this->request->post['password']) || strlen($this->request->post['password']) < 4)) {
            $this->error['password'] = 'Password must be at least 4 characters!';
        }

        if (empty($this->request->post['user_group_id'])) {
            $this->error['user_group_id'] = 'User group is required!';
        }

        return !$this->error;
    }
} 