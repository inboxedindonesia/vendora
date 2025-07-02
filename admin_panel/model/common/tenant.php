<?php
namespace Opencart\Admin\Model\Common;

class Tenant extends \Opencart\System\Engine\Model {
    // Company CRUD
    public function getCompanies(string $filter_name = '', int $user_id = 0): array {
        if ($user_id > 0) {
            $sql = "SELECT c.* FROM `" . DB_PREFIX . "company` c
                    JOIN `" . DB_PREFIX . "company_user` cu ON c.company_id = cu.company_id
                    WHERE cu.user_id = '" . (int)$user_id . "'";
            if (!empty($filter_name)) {
                $sql .= " AND c.name LIKE '%" . $this->db->escape($filter_name) . "%'";
            }
            $sql .= " ORDER BY c.name ASC";
            return $this->db->query($sql)->rows ?? [];
        } else {
            $sql = "SELECT * FROM `" . DB_PREFIX . "company`";
            if (!empty($filter_name)) {
                $sql .= " WHERE name LIKE '%" . $this->db->escape($filter_name) . "%'";
            }
            $sql .= " ORDER BY name ASC";
            return $this->db->query($sql)->rows ?? [];
        }
    }

    public function getCompaniesForUser(int $user_id): array {
        $sql = "SELECT c.* FROM `" . DB_PREFIX . "company` c
                JOIN `" . DB_PREFIX . "company_user` cu ON c.company_id = cu.company_id
                WHERE cu.user_id = '" . (int)$user_id . "'
                ORDER BY c.name ASC";
        return $this->db->query($sql)->rows ?? [];
    }

    public function isUserAssignedToCompany(int $user_id, int $company_id): bool {
        $query = $this->db->query("SELECT 1 FROM `" . DB_PREFIX . "company_user`
                                   WHERE user_id = '" . (int)$user_id . "'
                                     AND company_id = '" . (int)$company_id . "'
                                   LIMIT 1");
        return (bool)$query->num_rows;
    }

    public function getCompany(int $company_id): array {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "company` WHERE company_id = '" . (int)$company_id . "'")->row ?? [];
    }

    public function addCompany(string $name): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "company`
                          SET name = '" . $this->db->escape($name) . "',
                              date_added = NOW()");
    }

    public function addTenantWithAdmin(array $data): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "company` SET 
            `name` = '" . $this->db->escape($data['company_name']) . "',
            `business_type` = '" . $this->db->escape($data['business_type'] ?? '') . "',
            `owner_name` = '" . $this->db->escape($data['owner_name'] ?? '') . "',
            `address` = '" . $this->db->escape($data['address'] ?? '') . "',
            `city` = '" . $this->db->escape($data['city'] ?? '') . "',
            `province` = '" . $this->db->escape($data['province'] ?? '') . "',
            `country` = '" . $this->db->escape($data['country'] ?? '') . "',
            `postcode` = '" . $this->db->escape($data['postcode'] ?? '') . "',
            `contact` = '" . $this->db->escape($data['contact'] ?? '') . "',
            `date_added` = NOW()");
        $company_id = $this->db->getLastId();

        $user_group_id = 11;
        $user_data = [
            'user_group_id' => (int)$user_group_id,
            'username'      => $this->db->escape($data['username']),
            'firstname'     => $this->db->escape($data['firstname']),
            'lastname'      => $this->db->escape($data['lastname']),
            'email'         => $this->db->escape($data['email']),
            'password'      => password_hash(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT),
            'status'        => 1,
            'image'         => ''
        ];
        $this->load->model('user/user');
        $user_id = $this->model_user_user->addUser($user_data);
        $this->db->query("INSERT INTO `" . DB_PREFIX . "company_user` SET `user_id` = '" . (int)$user_id . "', `company_id` = '" . (int)$company_id . "', `role` = 'admin_tenant', `date_added` = NOW()");
    }

    public function editCompany(int $company_id, array $data): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "company` SET 
            `name` = '" . $this->db->escape($data['name']) . "',
            `business_type` = '" . $this->db->escape($data['business_type'] ?? '') . "',
            `owner_name` = '" . $this->db->escape($data['owner_name'] ?? '') . "',
            `email` = '" . $this->db->escape($data['email'] ?? '') . "',
            `address` = '" . $this->db->escape($data['address'] ?? '') . "',
            `city` = '" . $this->db->escape($data['city'] ?? '') . "',
            `province` = '" . $this->db->escape($data['province'] ?? '') . "',
            `country` = '" . $this->db->escape($data['country'] ?? '') . "',
            `postcode` = '" . $this->db->escape($data['postcode'] ?? '') . "',
            `contact` = '" . $this->db->escape($data['contact'] ?? '') . "'
            WHERE `company_id` = '" . (int)$company_id . "'");
    }

    public function deleteCompany(int $company_id): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "company` WHERE company_id = '" . (int)$company_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "company_regulation` WHERE company_id = '" . (int)$company_id . "'");
    }

    public function getUsersForCompany(int $company_id): array {
        $sql = "SELECT u.user_id, u.username, u.firstname, u.lastname, u.email, cu.role, u.status, u.date_added
                FROM `" . DB_PREFIX . "user` u
                JOIN `" . DB_PREFIX . "company_user` cu ON u.user_id = cu.user_id
                WHERE cu.company_id = '" . (int)$company_id . "'
                ORDER BY u.username ASC";
        return $this->db->query($sql)->rows;
    }

    public function assignUserToCompany(int $user_id, int $company_id, string $role = 'user'): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "company_user` SET 
            `user_id` = '" . (int)$user_id . "', 
            `company_id` = '" . (int)$company_id . "', 
            `role` = '" . $this->db->escape($role) . "', 
            `date_added` = NOW()
        ");
    }

    public function getCompanyUser(int $user_id): ?array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "company_user` WHERE user_id = '" . (int)$user_id . "' LIMIT 1");
        return $query->row ?: null;
    }

    public function isUserInCompany(int $user_id, int $company_id): bool {
        $query = $this->db->query("SELECT 1 FROM `" . DB_PREFIX . "company_user` WHERE user_id = '" . (int)$user_id . "' AND company_id = '" . (int)$company_id . "' LIMIT 1");
        return (bool)$query->num_rows;
    }
} 