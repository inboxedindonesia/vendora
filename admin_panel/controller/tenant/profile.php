<?php
namespace Opencart\Admin\Controller\Tenant;

class Profile extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('tenant/profile');
        $this->load->model('common/tenant');
        $this->document->setTitle($this->language->get('heading_title'));

        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        $company = $this->model_common_tenant->getCompany($company_id);

        $breadcrumbs = [];
        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Settings',
            'href' => $this->url->link('tenant/profile', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Profile',
            'href' => ''
        ];

        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'company' => $company,
            'edit' => $this->url->link('tenant/profile|edit', 'user_token=' . $this->session->data['user_token']),
            'breadcrumbs' => $breadcrumbs,
            'user_token' => $this->session->data['user_token'],
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];
        $this->response->setOutput($this->load->view('tenant/profile_view', $data));
    }

    public function edit(): void {
        $this->load->language('tenant/profile');
        $this->load->model('common/tenant');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_common_tenant->editCompany($company_id, $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('tenant/profile', 'user_token=' . $this->session->data['user_token']));
        }
        $company = $this->model_common_tenant->getCompany($company_id);

        $breadcrumbs = [];
        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Settings',
            'href' => $this->url->link('tenant/profile', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Edit Profile',
            'href' => ''
        ];
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'breadcrumbs' => $breadcrumbs,
            'company' => $company,
            'action' => $this->url->link('tenant/profile|edit', 'user_token=' . $this->session->data['user_token']),
            'user_token' => $this->session->data['user_token'],
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];
        $this->response->setOutput($this->load->view('tenant/profile', $data));
    }
} 