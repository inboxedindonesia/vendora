<?php
namespace Opencart\Admin\Controller\Common;

class RegulatoryEditRegulation extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->model('common/regulatory');
        $regulation_id = (int)($this->request->get['regulation_id'] ?? 0);

        $reg = $this->model_common_regulatory->getRegulation($regulation_id);
        $company_id = $reg['company_id'];

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $file_path = $reg['file_path'];
            if (!empty($this->request->files['regulation_file']['name'])) {
                $upload = $this->request->files['regulation_file'];
                $filename = date('YmdHis') . '_' . basename($upload['name']);
                $target = DIR_UPLOAD . $filename;
                if (move_uploaded_file($upload['tmp_name'], $target)) {
                    $file_path = 'upload/' . $filename;
                }
            }

            $this->model_common_regulatory->editRegulation($regulation_id, [
                'title' => $this->request->post['title'],
                'date' => $this->request->post['date'],
                'expired_date' => $this->request->post['expired_date'],
                'status' => $this->request->post['status'],
                'file_path' => $file_path
            ]);

            $this->response->redirect($this->url->link('common/regulatory_company', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id));
        }

        $data['reg'] = $reg;
        $data['action'] = $this->url->link('common/regulatory_edit_regulation', 'user_token=' . $this->session->data['user_token'] . '&regulation_id=' . $regulation_id);
        $data['cancel'] = $this->url->link('common/regulatory_company', 'user_token=' . $this->session->data['user_token'] . '&company_id=' . $company_id);
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/regulatory_edit_regulation', $data));
    }
}
