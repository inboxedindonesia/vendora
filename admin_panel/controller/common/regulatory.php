<?php
namespace Opencart\Admin\Controller\Common;

class Regulatory extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('common/regulatory');
        $this->load->model('common/regulatory');
        $this->document->setTitle($this->language->get('heading_title'));

        $user_id = $this->user->getId();
        $user_group_id = $this->user->getGroupId();

        // --- Routing Logic ---
        // If user is a system-level admin, show the company list.
        if ($user_group_id == 1 || $user_group_id == 2) {
            $this->getList();
        } else {
            // If user is a tenant-level user, redirect them to their company's edit page.
            $company_user = $this->model_common_regulatory->getCompanyUser($user_id);

            if ($company_user) {
                // Redirect ke halaman regulatory_company miliknya
                $this->response->redirect($this->url->link('common/regulatory_company', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_user['company_id']));
            } else {
                // This user is not assigned to any company. For security, send to dashboard.
                // Optionally, you can show an error page.
                $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            }
        }
    }

    protected function getList(): void {
        $filter_name = $this->request->get['filter_name'] ?? '';
        $data['filter_name'] = $filter_name;

        $user_id = $this->user->getId();
        $user_group_id = $this->user->getGroupId();

        // Admins see all companies, tenant only sees their own
        if ($user_group_id == 1 || $user_group_id == 2) {
            $data['companies'] = $this->model_common_regulatory->getCompanies($filter_name);
        } else {
            $data['companies'] = $this->model_common_regulatory->getCompanies($filter_name, $user_id);
        }

        $data['action'] = $this->url->link('common/regulatory', 'user_token=' . $this->session->data['user_token']);
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/regulatory', $data));
    }
}
