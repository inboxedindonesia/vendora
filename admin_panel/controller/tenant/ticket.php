<?php
namespace Opencart\Admin\Controller\Tenant;

class Ticket extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('tenant/ticket');
        $this->load->model('tenant/ticket');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        $tickets = $this->model_tenant_ticket->getTicketsByCompany($company_id);
        foreach ($tickets as &$ticket) {
            $ticket['edit'] = $this->url->link('tenant/ticket|edit', 'user_token=' . $this->session->data['user_token'] . '&ticket_id=' . $ticket['ticket_id']);
            $ticket['delete'] = $this->url->link('tenant/ticket|delete', 'user_token=' . $this->session->data['user_token'] . '&ticket_id=' . $ticket['ticket_id']);
        }
        $breadcrumbs = [];
        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Settings',
            'href' => $this->url->link('tenant/ticket', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Ticket List',
            'href' => ''
        ];
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'breadcrumbs' => $breadcrumbs,
            'tickets' => $tickets,
            'add' => $this->url->link('tenant/ticket|add', 'user_token=' . $this->session->data['user_token']),
            'user_token' => $this->session->data['user_token'],
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];
        $this->response->setOutput($this->load->view('tenant/ticket_list', $data));
    }
    public function add(): void {
        $this->load->language('tenant/ticket');
        $this->load->model('tenant/ticket');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_tenant_ticket->addTicket($company_id, $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('tenant/ticket', 'user_token=' . $this->session->data['user_token']));
        }
        $breadcrumbs = [];
        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Settings',
            'href' => $this->url->link('tenant/ticket', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Tambah Tiket',
            'href' => ''
        ];
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'breadcrumbs' => $breadcrumbs,
            'action' => $this->url->link('tenant/ticket|add', 'user_token=' . $this->session->data['user_token']),
            'user_token' => $this->session->data['user_token'],
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];
        $this->response->setOutput($this->load->view('tenant/ticket_form', $data));
    }
    public function edit(): void {
        $this->load->language('tenant/ticket');
        $this->load->model('tenant/ticket');
        $this->load->model('common/tenant');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        $ticket_id = $this->request->get['ticket_id'] ?? 0;
        $ticket_info = $this->model_tenant_ticket->getTicket($ticket_id);
        if (!$ticket_info || $ticket_info['company_id'] != $company_id) {
            $this->session->data['error'] = $this->language->get('error_not_found');
            $this->response->redirect($this->url->link('tenant/ticket', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_tenant_ticket->editTicket($ticket_id, $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('tenant/ticket', 'user_token=' . $this->session->data['user_token']));
        }
        $breadcrumbs = [];
        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Settings',
            'href' => $this->url->link('tenant/ticket', 'user_token=' . $this->session->data['user_token'])
        ];
        $breadcrumbs[] = [
            'text' => 'Edit Tiket',
            'href' => ''
        ];
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'breadcrumbs' => $breadcrumbs,
            'action' => $this->url->link('tenant/ticket|edit', 'user_token=' . $this->session->data['user_token'] . '&ticket_id=' . $ticket_id),
            'ticket' => $ticket_info,
            'user_token' => $this->session->data['user_token'],
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];
        $this->response->setOutput($this->load->view('tenant/ticket_form', $data));
    }
    public function delete(): void {
        $this->load->language('tenant/ticket');
        $this->load->model('tenant/ticket');
        $this->load->model('common/tenant');
        $user_id = $this->user->getId();
        $company_user = $this->model_common_tenant->getCompanyUser($user_id);
        if (!$company_user) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        $company_id = $company_user['company_id'];
        $ticket_id = $this->request->get['ticket_id'] ?? 0;
        $ticket_info = $this->model_tenant_ticket->getTicket($ticket_id);
        if ($ticket_info && $ticket_info['company_id'] == $company_id) {
            $this->model_tenant_ticket->deleteTicket($ticket_id);
            $this->session->data['success'] = $this->language->get('text_success');
        } else {
            $this->session->data['error'] = $this->language->get('error_not_found');
        }
        $this->response->redirect($this->url->link('tenant/ticket', 'user_token=' . $this->session->data['user_token']));
    }
} 