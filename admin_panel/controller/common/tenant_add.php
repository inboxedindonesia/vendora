<?php
namespace Opencart\Admin\Controller\Common;

class TenantAdd extends \Opencart\System\Engine\Controller {
    private array $error = [];

    public function index(): void {
        // Hanya Super Admin yang bisa mengakses halaman ini
        if ($this->user->getGroupId() != 1) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }

        $this->load->language('common/tenant_add'); // Language file bisa dibuat nanti
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('common/tenant');
        $this->load->model('user/user');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            // Jika validasi sukses, tambahkan tenant dan admin-nya
            $this->model_common_tenant->addTenantWithAdmin($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token']));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('common/tenant_add', 'user_token=' . $this->session->data['user_token'])
        ];

        // Errors
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_company_name'] = $this->error['company_name'] ?? '';
        $data['error_username'] = $this->error['username'] ?? '';
        $data['error_firstname'] = $this->error['firstname'] ?? '';
        $data['error_lastname'] = $this->error['lastname'] ?? '';
        $data['error_email'] = $this->error['email'] ?? '';
        $data['error_password'] = $this->error['password'] ?? '';
        
        $data['save'] = $this->url->link('common/tenant_add', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token']);

        // Form data
        $data['company_name'] = $this->request->post['company_name'] ?? '';
        $data['business_type'] = $this->request->post['business_type'] ?? '';
        $data['owner_name'] = $this->request->post['owner_name'] ?? '';
        $data['address'] = $this->request->post['address'] ?? '';
        $data['city'] = $this->request->post['city'] ?? '';
        $data['province'] = $this->request->post['province'] ?? '';
        $data['country'] = $this->request->post['country'] ?? '';
        $data['postcode'] = $this->request->post['postcode'] ?? '';
        $data['contact'] = $this->request->post['contact'] ?? '';
        $data['username'] = $this->request->post['username'] ?? '';
        $data['firstname'] = $this->request->post['firstname'] ?? '';
        $data['lastname'] = $this->request->post['lastname'] ?? '';
        $data['email'] = $this->request->post['email'] ?? '';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/tenant_add', $data));
    }

    protected function validate(): bool {
        if (!oc_validate_length($this->request->post['company_name'], 2, 128)) {
            $this->error['company_name'] = 'Company name must be between 2 and 128 characters!';
        }
        if (!oc_validate_length($this->request->post['business_type'], 1, 128)) {
            $this->error['business_type'] = 'Business Type is required!';
        }
        if (!oc_validate_length($this->request->post['owner_name'], 1, 128)) {
            $this->error['owner_name'] = 'Owner Name is required!';
        }
        if (!oc_validate_length($this->request->post['address'], 1, 255)) {
            $this->error['address'] = 'Address is required!';
        }
        if (!oc_validate_length($this->request->post['city'], 1, 128)) {
            $this->error['city'] = 'City / Regency is required!';
        }
        if (!oc_validate_length($this->request->post['province'], 1, 128)) {
            $this->error['province'] = 'Province is required!';
        }
        if (!oc_validate_length($this->request->post['country'], 1, 128)) {
            $this->error['country'] = 'Country is required!';
        }
        if (!oc_validate_length($this->request->post['postcode'], 1, 16)) {
            $this->error['postcode'] = 'Postcode is required!';
        }
        if (!oc_validate_length($this->request->post['contact'], 1, 64)) {
            $this->error['contact'] = 'Contact is required!';
        }
        
        if (!oc_validate_length($this->request->post['username'], 3, 20)) {
            $this->error['username'] = 'Username must be between 3 and 20 characters!';
        } else {
            $user_info = $this->model_user_user->getUserByUsername($this->request->post['username']);
            if ($user_info) {
                $this->error['warning'] = 'Warning: Username is already registered!';
                $this->error['username'] = 'This username is already taken.';
            }
        }
        
        if (!oc_validate_length($this->request->post['firstname'], 1, 32)) {
            $this->error['firstname'] = 'First Name must be between 1 and 32 characters!';
        }
        
        if (!oc_validate_length($this->request->post['lastname'], 1, 32)) {
            $this->error['lastname'] = 'Last Name must be between 1 and 32 characters!';
        }
        
        if (!oc_validate_email($this->request->post['email'])) {
            $this->error['email'] = 'E-Mail Address does not appear to be valid!';
        } else {
            $user_info = $this->model_user_user->getUserByEmail($this->request->post['email']);
            if ($user_info) {
                $this->error['warning'] = 'Warning: E-Mail Address is already registered!';
            }
        }
        
        if (!oc_validate_length($this->request->post['password'], 4, 40)) {
             $this->error['password'] = 'Password must be between 4 and 40 characters!';
        }
        
        return !$this->error;
    }
} 