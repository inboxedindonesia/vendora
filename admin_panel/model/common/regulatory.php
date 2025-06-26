<?php
namespace Opencart\Admin\Model\Common;

class Regulatory extends \Opencart\System\Engine\Model {

    /**
     * Get list of all companies, optionally filtered by name
     */
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

    /**
     * Get list of companies assigned to a user
     */
    public function getCompaniesForUser(int $user_id): array {
        $sql = "SELECT c.* FROM `" . DB_PREFIX . "company` c
                JOIN `" . DB_PREFIX . "company_user` cu ON c.company_id = cu.company_id
                WHERE cu.user_id = '" . (int)$user_id . "'
                ORDER BY c.name ASC";

        return $this->db->query($sql)->rows ?? [];
    }

    /**
     * Check if user is assigned to a specific company
     */
    public function isUserAssignedToCompany(int $user_id, int $company_id): bool {
        $query = $this->db->query("SELECT 1 FROM `" . DB_PREFIX . "company_user`
                                   WHERE user_id = '" . (int)$user_id . "'
                                     AND company_id = '" . (int)$company_id . "'
                                   LIMIT 1");

        return (bool)$query->num_rows;
    }

    /**
     * Get single company by ID
     */
    public function getCompany(int $company_id): array {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "company` WHERE company_id = '" . (int)$company_id . "'")->row ?? [];
    }

    /**
     * Add new company
     */
    public function addCompany(string $name): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "company`
                          SET name = '" . $this->db->escape($name) . "',
                              date_added = NOW()");
    }

    /**
     * Add a new tenant company along with its first admin user.
     */
    public function addTenantWithAdmin(array $data): void {
        // Step 1: Create the Company
        $this->db->query("INSERT INTO `" . DB_PREFIX . "company` SET `name` = '" . $this->db->escape($data['company_name']) . "', `date_added` = NOW()");
        $company_id = $this->db->getLastId();

        // Step 2: Create the User for the tenant
        // IMPORTANT: Assumes 'Staff Tenant' user group ID is 11. Verify this in your User Groups setting.
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

        // Using the existing user model to add the user is safer
        $this->load->model('user/user');
        $user_id = $this->model_user_user->addUser($user_data);

        // Step 3: Link User and Company
        $this->db->query("INSERT INTO `" . DB_PREFIX . "company_user` SET `user_id` = '" . (int)$user_id . "', `company_id` = '" . (int)$company_id . "', `role` = 'admin_tenant', `date_added` = NOW()");
    }

    /**
     * Edit existing company
     */
    public function editCompany(int $company_id, string $name): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "company`
                          SET name = '" . $this->db->escape($name) . "'
                          WHERE company_id = '" . (int)$company_id . "'");
    }

    /**
     * Delete company and its regulations
     */
    public function deleteCompany(int $company_id): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "company` WHERE company_id = '" . (int)$company_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "company_regulation` WHERE company_id = '" . (int)$company_id . "'");
    }

    /**
     * Get regulations for a company, optionally filtered by title
     */
    public function getRegulations(int $company_id, string $filter_title = ''): array {
        $sql = "SELECT * FROM `" . DB_PREFIX . "company_regulation`
                WHERE company_id = '" . (int)$company_id . "'";

        if (!empty($filter_title)) {
            $sql .= " AND title LIKE '%" . $this->db->escape($filter_title) . "%'";
        }

        $sql .= " ORDER BY date DESC";

        return $this->db->query($sql)->rows ?? [];
    }

    /**
     * Get single regulation
     */
    public function getRegulation(int $regulation_id): array {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "company_regulation`
                                 WHERE regulation_id = '" . (int)$regulation_id . "'")->row ?? [];
    }

    /**
     * Add regulation to a company
     */
    public function addRegulation(
        int $company_id,
        string $title,
        string $date_issued,
        string $date_expired,
        string $status,
        string $file_path
    ): void {
        $days_expired = $this->calculateDaysExpired($date_issued, $date_expired);

        $this->db->query("INSERT INTO `" . DB_PREFIX . "company_regulation` SET
            company_id = '" . (int)$company_id . "',
            title = '" . $this->db->escape($title) . "',
            date = '" . $this->db->escape($date_issued) . "',
            expired_date = '" . $this->db->escape($date_expired) . "',
            status = '" . $this->db->escape($status) . "',
            file_path = '" . $this->db->escape($file_path) . "',
            days_expired = '" . (int)$days_expired . "',
            date_added = NOW()");
        
        $company_info = $this->getCompany($company_id);
        $message = "Perusahaan " . $company_info['name'] . " menambahkan regulasi baru: " . $title;
        $link = 'common/regulatory_company&company_id=' . $company_id;
        $this->sendNotificationToAdmins($message, $link);
    }

    /**
     * Edit regulation
     */
    public function editRegulation(int $regulation_id, array $data): void {
        $days_expired = $this->calculateDaysExpired($data['date'], $data['expired_date']);

        $this->db->query("UPDATE `" . DB_PREFIX . "company_regulation` SET
            title = '" . $this->db->escape($data['title']) . "',
            date = '" . $this->db->escape($data['date']) . "',
            expired_date = '" . $this->db->escape($data['expired_date']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            file_path = '" . $this->db->escape($data['file_path']) . "',
            days_expired = '" . (int)$days_expired . "'
            WHERE regulation_id = '" . (int)$regulation_id . "'");
            
        $reg_info = $this->getRegulation($regulation_id);
        $company_info = $this->getCompany($reg_info['company_id']);
        $message = "Perusahaan " . $company_info['name'] . " mengubah regulasi: " . $data['title'];
        $link = 'common/regulatory_company&company_id=' . $reg_info['company_id'];
        $this->sendNotificationToAdmins($message, $link);
    }

    /**
     * Delete regulation
     */
    public function deleteRegulation(int $regulation_id): void {
        $reg_info = $this->getRegulation($regulation_id);
        if ($reg_info) {
            $company_info = $this->getCompany($reg_info['company_id']);
            $message = "Perusahaan " . $company_info['name'] . " menghapus regulasi: " . $reg_info['title'];
            $link = 'common/regulatory_company&company_id=' . $reg_info['company_id'];
            $this->sendNotificationToAdmins($message, $link);
        }
        
        $this->db->query("DELETE FROM `" . DB_PREFIX . "company_regulation`
                          WHERE regulation_id = '" . (int)$regulation_id . "'");
    }

    /**
     * Get users assigned to a specific company
     */
    public function getUsersForCompany(int $company_id): array {
        $sql = "SELECT u.user_id, u.username, u.firstname, u.lastname, cu.role, u.status, u.date_added
                FROM `" . DB_PREFIX . "user` u
                JOIN `" . DB_PREFIX . "company_user` cu ON u.user_id = cu.user_id
                WHERE cu.company_id = '" . (int)$company_id . "'
                ORDER BY u.username ASC";

        return $this->db->query($sql)->rows;
    }

    /**
     * Assign a user to a company with a specific role
     */
    public function assignUserToCompany(int $user_id, int $company_id, string $role = 'user'): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "company_user` SET 
            `user_id` = '" . (int)$user_id . "', 
            `company_id` = '" . (int)$company_id . "', 
            `role` = '" . $this->db->escape($role) . "', 
            `date_added` = NOW()
        ");
    }

    /**
     * Calculate days between issue and expiry date
     */
    private function calculateDaysExpired(string $start, string $end): int {
        try {
            $startDate = new \DateTime($start);
            $endDate = new \DateTime($end);
            return $startDate->diff($endDate)->days;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get company_user row for a user (first found)
     */
    public function getCompanyUser(int $user_id): ?array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "company_user` WHERE user_id = '" . (int)$user_id . "' LIMIT 1");
        return $query->row ?: null;
    }

    private function sendNotificationToAdmins(string $message, string $link): void {
        $this->load->model('user/user');
        $this->load->model('tool/notification');
        
        // Super Admin group ID is typically 1
        $admin_users = $this->model_user_user->getUsers(['filter_user_group_id' => 1]);
        
        foreach ($admin_users as $user) {
            $notification_data = [
                'user_id' => $user['user_id'],
                'title'   => 'Update Regulasi',
                'text'    => $message,
                'status'  => 0, 
                'link'    => $this->url->link($link, 'user_token=' . $this->session->data['user_token'], true)
            ];

             $this->db->query("INSERT INTO `" . DB_PREFIX . "notification` SET `title` = '" . $this->db->escape($message) . "', `text` = '" . $this->db->escape($this->url->link($link, 'user_token=' . $this->session->data['user_token'], true)) . "', `status` = '0', `date_added` = NOW()");
        }
    }
}
