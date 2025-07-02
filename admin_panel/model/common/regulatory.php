<?php
namespace Opencart\Admin\Model\Common;

class Regulatory extends \Opencart\System\Engine\Model {

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
        
        // Notifikasi ke admin
        $company_info = $this->load->model('common/tenant')->getCompany($company_id);
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
        $company_info = $this->load->model('common/tenant')->getCompany($reg_info['company_id']);
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
            $company_info = $this->load->model('common/tenant')->getCompany($reg_info['company_id']);
            $message = "Perusahaan " . $company_info['name'] . " menghapus regulasi: " . $reg_info['title'];
            $link = 'common/regulatory_company&company_id=' . $reg_info['company_id'];
            $this->sendNotificationToAdmins($message, $link);
        }
        
        $this->db->query("DELETE FROM `" . DB_PREFIX . "company_regulation`
                          WHERE regulation_id = '" . (int)$regulation_id . "'");
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
