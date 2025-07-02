<?php
namespace Opencart\Admin\Controller\Tenant;

class User extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('tenant/user');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('common/tenant');
        $this->load->model('user/user');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        $users = $this->model_common_tenant->getUsersForCompany($company_id);
        foreach ($users as &$user) {
            $user['edit'] = $this->url->link('tenant/user|edit', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $user['user_id']);
            $user['delete'] = $this->url->link('tenant/user|delete', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $user['user_id']);
        }
        $breadcrumbs = [];
        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Settings',
            'href' => $this->url->link('tenant/user', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'User List',
            'href' => ''
        ];
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'breadcrumbs' => $breadcrumbs,
            'users' => $users,
            'add' => $this->url->link('tenant/user|add', 'user_token=' . $this->session->data['user_token']),
            'delete_bulk' => $this->url->link('tenant/user|deleteBulk', 'user_token=' . $this->session->data['user_token']),
            'user_token' => $this->session->data['user_token'],
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];
        $this->response->setOutput($this->load->view('tenant/user_list', $data));
    }
    public function add(): void {
        $this->load->language('tenant/user');
        $this->document->setTitle($this->language->get('text_add'));
        $this->load->model('common/tenant');
        $this->load->model('user/user');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $new_user_id = $this->model_user_user->addUser($this->request->post);
            $this->model_common_tenant->assignUserToCompany($new_user_id, $company_id);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('tenant/user', 'user_token=' . $this->session->data['user_token']));
        }
        $breadcrumbs = [];
        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Settings',
            'href' => $this->url->link('tenant/user', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Tambah User',
            'href' => ''
        ];
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'breadcrumbs' => $breadcrumbs,
            'action' => $this->url->link('tenant/user|add', 'user_token=' . $this->session->data['user_token']),
            'user_token' => $this->session->data['user_token'],
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];
        $this->response->setOutput($this->load->view('tenant/user_form', $data));
    }
    public function edit(): void {
        $this->load->language('tenant/user');
        $this->document->setTitle($this->language->get('text_edit'));
        $this->load->model('common/tenant');
        $this->load->model('user/user');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        $edit_user_id = $this->request->get['user_id'] ?? 0;
        $user_info = $this->model_user_user->getUser($edit_user_id);
        if (!$user_info || !$this->model_common_tenant->isUserInCompany($edit_user_id, $company_id)) {
            $this->session->data['error'] = $this->language->get('error_not_found');
            $this->response->redirect($this->url->link('tenant/user', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_user_user->editUser($edit_user_id, $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('tenant/user', 'user_token=' . $this->session->data['user_token']));
        }
        $breadcrumbs = [];
        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Settings',
            'href' => $this->url->link('tenant/user', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Edit User',
            'href' => ''
        ];
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'breadcrumbs' => $breadcrumbs,
            'action' => $this->url->link('tenant/user|edit', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $edit_user_id),
            'user' => $user_info,
            'user_token' => $this->session->data['user_token'],
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];
        $this->response->setOutput($this->load->view('tenant/user_form', $data));
    }
    public function delete(): void {
        $this->load->language('tenant/user');
        $this->load->model('common/tenant');
        $this->load->model('user/user');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        $delete_user_id = $this->request->get['user_id'] ?? 0;
        if ($this->model_common_tenant->isUserInCompany($delete_user_id, $company_id)) {
            $this->model_user_user->deleteUser($delete_user_id);
            $this->session->data['success'] = $this->language->get('text_success');
        } else {
            $this->session->data['error'] = $this->language->get('error_not_found');
        }
        $this->response->redirect($this->url->link('tenant/user', 'user_token=' . $this->session->data['user_token']));
    }
    public function deleteBulk(): void {
        $this->load->language('tenant/user');
        $this->load->model('common/tenant');
        $this->load->model('user/user');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        if (isset($this->request->post['selected'])) {
            foreach ($this->request->post['selected'] as $delete_user_id) {
                if ($this->model_common_tenant->isUserInCompany($delete_user_id, $company_id)) {
                    $this->model_user_user->deleteUser($delete_user_id);
                }
            }
            $this->session->data['success'] = $this->language->get('text_success');
        } else {
            $this->session->data['error'] = $this->language->get('error_selection');
        }
        $this->response->redirect($this->url->link('tenant/user', 'user_token=' . $this->session->data['user_token']));
    }
} 