<?php
namespace Opencart\Admin\Model\Common;

class TenantUserGroup extends \Opencart\System\Engine\Model {
    public function addUserGroup(array $data): int {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "user_group` SET `name` = '" . $this->db->escape($data['name']) . "', `permission` = '" . $this->db->escape(json_encode($data['permission'])) . "'");
        return $this->db->getLastId();
    }

    public function editUserGroup(int $user_group_id, array $data): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET `name` = '" . $this->db->escape($data['name']) . "', `permission` = '" . $this->db->escape(json_encode($data['permission'])) . "' WHERE `user_group_id` = '" . (int)$user_group_id . "'");
    }

    public function deleteUserGroup(int $user_group_id): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "user_group` WHERE `user_group_id` = '" . (int)$user_group_id . "'");
    }

    public function getUserGroup(int $user_group_id): array {
        $query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "user_group` WHERE `user_group_id` = '" . (int)$user_group_id . "'");
        
        $user_group = [
            'name'       => $query->row['name'],
            'permission' => json_decode($query->row['permission'], true)
        ];

        return $user_group;
    }

    public function getUserGroups(array $data = []): array {
        // Filter out system-level user groups
        $sql = "SELECT * FROM `" . DB_PREFIX . "user_group` WHERE `name` NOT IN ('Super Admin', 'Admin')";
        
        $sql .= " ORDER BY `name`";

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalUsersByGroupId(int $user_group_id): int {
        $query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "user` WHERE `user_group_id` = '" . (int)$user_group_id . "'");
        return (int)$query->row['total'];
    }
} 