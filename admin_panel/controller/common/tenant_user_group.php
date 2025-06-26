<?php
namespace Opencart\Admin\Controller\Common;

class TenantUserGroup extends \Opencart\System\Engine\Controller {
    private array $error = [];

    public function index(): void {
        $this->load->language('common/tenant_user_group');
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('common/tenant_user_group');
        
        $this->getList();
    }

    public function getList(): void {
        // Breadcrumbs and basic page setup
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('common/tenant_user_group', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['add'] = $this->url->link('common/tenant_user_group.form', 'user_token=' . $this->session->data['user_token']);
        $data['delete'] = $this->url->link('common/tenant_user_group.delete', 'user_token=' . $this->session->data['user_token']);

        $data['user_groups'] = [];
        $results = $this->model_common_tenant_user_group->getUserGroups();

        foreach ($results as $result) {
            $data['user_groups'][] = [
                'user_group_id' => $result['user_group_id'],
                'name'          => $result['name'],
                'edit'          => $this->url->link('common/tenant_user_group.form', 'user_token=' . $this->session->data['user_token'] . '&user_group_id=' . $result['user_group_id'])
            ];
        }

        if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/tenant_user_group', $data));
    }
    
    public function form(): void {
        $this->load->language('common/tenant_user_group');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('common/tenant_user_group');

        if (isset($this->request->get['user_group_id'])) {
            $user_group_info = $this->model_common_tenant_user_group->getUserGroup($this->request->get['user_group_id']);
        }

        $data['text_form'] = !isset($this->request->get['user_group_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])];
        $data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('common/tenant_user_group', 'user_token=' . $this->session->data['user_token'])];

        $data['save'] = $this->url->link('common/tenant_user_group.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('common/tenant_user_group', 'user_token=' . $this->session->data['user_token']);

        if (isset($this->request->get['user_group_id'])) {
            $data['user_group_id'] = (int)$this->request->get['user_group_id'];
        } else {
            $data['user_group_id'] = 0;
        }
        
        if (!empty($user_group_info)) {
            $data['name'] = $user_group_info['name'];
        } else {
            $data['name'] = '';
        }

        // Permissions
        $data['permissions'] = [];
        $data['extensions'] = [];
        $files = [];
        
        $path = DIR_APPLICATION . 'controller/*/*.php';
        $files = glob($path);
        
        // Exclude system-level routes that tenants should not access from being assigned, but still show in permission list
        $ignore = [
            'common/column_left', 'common/developer', 'common/filemanager',
            'common/footer', 'common/header', 'common/login', 'common/logout', 'common/forgotten',
            'common/security', 'common/startup',
            'error/not_found', 'error/permission', 'error/exception',
            'user/'
        ];
        // Jangan pernah ignore common/regulatory_edit_company

        foreach ($files as $file) {
            $part = explode('/', dirname($file));
            $route = end($part) . '/' . basename($file, '.php');

            $is_extension = str_starts_with($route, 'extension/') || str_starts_with($route, 'marketplace/');

            // Tampilkan SEMUA route kecuali yang di-ignore (kecuali dashboard dan regulatory TETAP DITAMPILKAN)
            $should_ignore = false;
            foreach ($ignore as $ignore_route) {
                if ($ignore_route !== 'common/dashboard' && $ignore_route !== 'common/regulatory' && str_starts_with($route, $ignore_route)) {
                    $should_ignore = true;
                    break;
                }
            }
            if (!$should_ignore) {
                if ($is_extension) {
                    $data['extensions'][] = $route;
                } else {
                    $data['permissions'][] = $route;
                }
            }
        }
        sort($data['permissions']);
        sort($data['extensions']);

        if (!empty($user_group_info) && isset($user_group_info['permission']['access'])) {
            $data['access'] = $user_group_info['permission']['access'];
        } else {
            $data['access'] = [];
        }

        if (!empty($user_group_info) && isset($user_group_info['permission']['modify'])) {
            $data['modify'] = $user_group_info['permission']['modify'];
        } else {
            $data['modify'] = [];
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/tenant_user_group_form', $data));
    }

    public function save(): void {
        $this->load->language('common/tenant_user_group');
        $this->load->model('common/tenant_user_group');
        
        $json = [];

        if (!$this->user->hasPermission('modify', 'common/tenant_user_group')) {
            $json['error']['warning'] = $this->language->get('error_permission');
        }

        if ((oc_strlen($this->request->post['name']) < 3) || (oc_strlen($this->request->post['name']) > 64)) {
            $json['error']['name'] = $this->language->get('error_name');
        }

        if (!isset($this->request->post['permission']['access'])) {
            $this->request->post['permission']['access'] = [];
        }

        if (!isset($this->request->post['permission']['modify'])) {
            $this->request->post['permission']['modify'] = [];
        }

        if (!$json) {
            if (!$this->request->post['user_group_id']) {
                $this->model_common_tenant_user_group->addUserGroup($this->request->post);
            } else {
                $this->model_common_tenant_user_group->editUserGroup($this->request->post['user_group_id'], $this->request->post);
            }
            $json['success'] = $this->language->get('text_success');
            $json['redirect'] = $this->url->link('common/tenant_user_group', 'user_token=' . $this->session->data['user_token']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete(): void {
        $this->load->language('common/tenant_user_group');
        $this->load->model('common/tenant_user_group');

        $json = [];
        
        if (isset($this->request->post['selected'])) {
            $selected = $this->request->post['selected'];
        } else {
            $selected = [];
        }

        if (!$this->user->hasPermission('modify', 'common/tenant_user_group')) {
            $json['error'] = $this->language->get('error_permission');
        }

        foreach ($selected as $user_group_id) {
            // Do not allow deleting core groups
            if (in_array($user_group_id, [1, 4])) {
                 $json['error'] = $this->language->get('error_permission');
                 break;
            }
            
            $user_total = $this->model_common_tenant_user_group->getTotalUsersByGroupId($user_group_id);
            if ($user_total) {
                $json['error'] = sprintf($this->language->get('error_user'), $user_total);
                break;
            }
        }

        if (!$json) {
            foreach ($selected as $user_group_id) {
                $this->model_common_tenant_user_group->deleteUserGroup($user_group_id);
            }
            $json['success'] = $this->language->get('text_success');
            $json['redirect'] = $this->url->link('common/tenant_user_group', 'user_token=' . $this->session->data['user_token']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
} 