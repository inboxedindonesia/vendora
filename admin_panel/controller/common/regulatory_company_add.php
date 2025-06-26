<?php
namespace Opencart\Admin\Controller\Common;

class RegulatoryCompanyAdd extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->model('common/regulatory');

        $company_id = (int)($this->request->get['company_id'] ?? 0);

        if (!$this->user->hasPermission('modify', 'common/regulatory_company')) {
            $this->session->data['error_warning'] = 'Anda tidak memiliki izin!';
            $this->response->redirect($this->url->link('common/regulatory', 'user_token=' . $this->session->data['user_token']));
        }

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $title = $this->request->post['title'] ?? '';
            $date = $this->request->post['date'] ?? date('Y-m-d');
            $expired_date = $this->request->post['expired_date'] ?? '';
            $status = $this->request->post['status'] ?? 'Aktif';
            $file_path = '';

            if (!empty($this->request->files['regulation_file']['name'])) {
                $upload = $this->request->files['regulation_file'];
                $filename = date('YmdHis') . '_' . basename($upload['name']);
                $target = DIR_UPLOAD . $filename;

                if (move_uploaded_file($upload['tmp_name'], $target)) {
                    $file_path = 'upload/' . $filename;
                }
            }

            $this->model_common_regulatory->addRegulation($company_id, $title, $date, $expired_date, $status, $file_path);

            $this->response->redirect($this->url->link('common/regulatory_company', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id));
        }

        $data['company'] = $this->model_common_regulatory->getCompany($company_id);
        $data['user_token'] = $this->session->data['user_token'];
        $data['action'] = $this->url->link('common/regulatory_company_add', 'user_token=' . $data['user_token'] . '&company_id=' . $company_id);
        $data['cancel'] = $this->url->link('common/regulatory_company', 'user_token=' . $data['user_token'] . '&company_id=' . $company_id);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/regulatory_company_add', $data));
    }
}
