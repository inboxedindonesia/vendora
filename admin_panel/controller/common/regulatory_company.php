<?php
namespace Opencart\Admin\Controller\Common;

class RegulatoryCompany extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->model('common/regulatory');

        $user_id = $this->user->getId();
        $company_id = (int)($this->request->get['company_id'] ?? 0);
        $filter_title = $this->request->get['filter_title'] ?? '';

        // ✅ Cek jika user hanya boleh akses tenant miliknya
        if (!$this->model_common_regulatory->isUserAssignedToCompany($user_id, $company_id) && $this->user->getGroupId() != 1) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
        }

        // ✅ Handle delete regulasi
        if (isset($this->request->get['delete_regulation_id'])) {
            $this->model_common_regulatory->deleteRegulation((int)$this->request->get['delete_regulation_id']);
            $this->response->redirect($this->url->link('common/regulatory_company', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id));
        }

        $data['company'] = $this->model_common_regulatory->getCompany($company_id);
        $data['regulations'] = $this->model_common_regulatory->getRegulations($company_id, $filter_title);

        $data['filter_title'] = $filter_title;
        $data['company']['company_id'] = $company_id;
        $data['action'] = $this->url->link('common/regulatory_company', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id);
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/regulatory_company_detail', $data));
    }
}
