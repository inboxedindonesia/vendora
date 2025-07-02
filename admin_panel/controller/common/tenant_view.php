<?php
namespace Opencart\Admin\Controller\Common;

class TenantView extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('common/tenant_view');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('common/tenant');

        $company_id = $this->request->get['company_id'] ?? 0;
        $company = $this->model_common_tenant->getCompany($company_id);

        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'company' => $company,
            'back' => $this->url->link('common/tenant_list', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/tenant_view', $data));
    }
} 